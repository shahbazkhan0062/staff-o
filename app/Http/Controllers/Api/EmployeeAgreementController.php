<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\EmployeeAgreementSignatureRequestMail;
use App\Mail\EmployeeAgreementSignedMail;
use App\Models\EmployeeAgreement;
use App\Models\Onboarding;
use App\Models\Staff;
use App\Models\User;
use App\Services\EmployeeAgreementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmployeeAgreementController extends Controller
{
    /**
     * STEP 0 — Admin opens "Send Employment Agreement" for a guard. Returns
     * everything we can already auto-fill for Schedule 1 from data the guard
     * entered during registration/onboarding, so HR only has to key in the
     * handful of fields that genuinely don't exist anywhere yet (employment
     * category, classification level, guaranteed hours, commencement date).
     *
     * GET /admin/staff/{userId}/employment-agreement/prefill
     */
    public function prefill($userId)
    {
        $user = User::find($userId);
        $staff = Staff::where('user_id', $userId)->first();
        $onboarding = Onboarding::where('user_id', $userId)->first();

        if (!$user || !$staff) {
            return response()->json(['success' => false, 'message' => 'Staff member not found.'], 200);
        }

        return response()->json([
            'success' => true,
            'data' => [
                // Auto-filled — editable by HR before sending, but pre-populated
                // from registration/onboarding so nobody re-types it.
                'employee_name'    => $onboarding->full_name ?? $user->name,
                'employee_address' => $onboarding->address ?? $user->address,
                'employee_phone'   => $onboarding->mobile ?? $staff->phone ?? $user->phone,
                'employee_email'   => $onboarding->email ?? $user->email,
                'security_license_no' => $onboarding->security_license ?? $staff->security_license_no,

                // Previously saved values, if this is a re-issue (e.g. the
                // employee moved from Casual to Part-Time) — otherwise null
                // and HR fills them in for the first time.
                'employment_type'      => $staff->employment_type,
                'classification_level' => $staff->classification_level,
                'guaranteed_hours'     => $staff->guaranteed_hours,
                'commencement_date'    => $staff->commencement_date,

                'existing_agreement' => EmployeeAgreement::where('staff_id', $staff->id)
                    ->orderByDesc('created_at')->first(),
            ],
        ], 200);
    }

    /**
     * STEP 1 — Admin submits the completed Schedule 1 (auto-filled fields +
     * the ones they just keyed in) for a given staff member. Generates the
     * unsigned agreement PDF and emails the signing link to the employee.
     *
     * POST /admin/staff/{userId}/employment-agreement
     * Body: {
     *   employee_name, employee_address, employee_phone, employee_email,
     *   employment_type: full_time|part_time|casual,
     *   classification_level: 1-5,
     *   security_license_no,
     *   guaranteed_hours (required if employment_type = part_time),
     *   commencement_date (YYYY-MM-DD)
     * }
     */
    public function store(Request $request, $userId)
    {
        $staff = Staff::where('user_id', $userId)->first();
        if (!$staff) {
            return response()->json(['success' => false, 'message' => 'Staff member not found.'], 200);
        }

        $validated = $request->validate([
            'employee_name'        => 'required|string|max:200',
            'employee_address'     => 'nullable|string|max:255',
            'employee_phone'       => 'nullable|string|max:30',
            'employee_email'       => 'nullable|email|max:150',
            'employment_type'      => 'required|in:full_time,part_time,casual',
            'classification_level' => 'required|integer|min:1|max:5',
            'security_license_no'  => 'nullable|string|max:50',
            'guaranteed_hours'     => 'required_if:employment_type,part_time|nullable|string|max:100',
            'commencement_date'    => 'required|date',
        ]);

        // Keep Staff (the canonical, current record) in sync — rostering/
        // payroll reads these columns going forward.
        $staff->update([
            'employment_type'      => $validated['employment_type'],
            'classification_level' => $validated['classification_level'],
            'guaranteed_hours'      => $validated['guaranteed_hours'] ?? null,
            'commencement_date'     => $validated['commencement_date'],
            'security_license_no'   => $validated['security_license_no'] ?? $staff->security_license_no,
        ]);

        $isCasual = $validated['employment_type'] === 'casual';
        $agreementNumber = 'EMP-AGR-' . strtoupper(Str::random(8));
        $signingToken = Str::random(48);

        $pdfData = array_merge($validated, [
            'agreement_number' => $agreementNumber,
            'date'             => now()->format('d M Y'),
            'fwis_attached'    => true,
            'ceis_attached'    => $isCasual,
        ]);

        $service = new EmployeeAgreementService();
        $pdfBytes = $service->generatePdf($pdfData);

        $directory = storage_path('app/public/employee-agreements');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        $filename = $agreementNumber . '.pdf';
        file_put_contents($directory . DIRECTORY_SEPARATOR . $filename, $pdfBytes);

        $agreement = EmployeeAgreement::create([
            'staff_id'   => $staff->id,
            'user_id'    => $userId,
            'created_by' => auth()->id(),
            'agreement_number' => $agreementNumber,
            'employee_name'    => $validated['employee_name'],
            'employee_address' => $validated['employee_address'] ?? null,
            'employee_phone'   => $validated['employee_phone'] ?? null,
            'employee_email'   => $validated['employee_email'] ?? null,
            'employment_type'      => $validated['employment_type'],
            'classification_level' => $validated['classification_level'],
            'security_license_no'  => $validated['security_license_no'] ?? null,
            'guaranteed_hours'      => $validated['guaranteed_hours'] ?? null,
            'commencement_date'     => $validated['commencement_date'],
            'schedule1_snapshot' => $validated,
            'fwis_attached' => true,
            'ceis_attached' => $isCasual,
            'pdf_path'      => 'employee-agreements/' . $filename,
            'signing_token' => $signingToken,
            'status'        => 'pending_signature',
        ]);

        $signingLink = rtrim(config('app.frontend_url', config('app.url')), '/')
            . '/employment-agreement/sign?token=' . $signingToken;

        if (!empty($validated['employee_email'])) {
            try {
                Mail::to($validated['employee_email'])->send(new EmployeeAgreementSignatureRequestMail(
                    $validated['employee_name'],
                    $agreementNumber,
                    $signingLink
                ));
            } catch (\Exception $e) {
                Log::error('Failed to send employment agreement signature request', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Employment agreement generated and sent to the employee for signature.',
            'data' => [
                'agreement_id'     => $agreement->id,
                'agreement_number' => $agreementNumber,
                'signing_link'     => $signingLink,
            ],
        ], 200);
    }

    /**
     * STEP 2 — Employee opens the link on their phone. Public endpoint.
     * GET /employment-agreement/sign/{token}
     */
    public function getByToken($token)
    {
        $agreement = EmployeeAgreement::where('signing_token', $token)->first();

        if (!$agreement) {
            return response()->json(['success' => false, 'message' => 'Agreement not found or link expired.'], 200);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'agreement_number'      => $agreement->agreement_number,
                'employee_name'         => $agreement->employee_name,
                'employment_type'       => $agreement->employment_type,
                'classification_level'  => $agreement->classification_level,
                'commencement_date'     => optional($agreement->commencement_date)->format('d M Y'),
                'status'                => $agreement->status,
                'pdf_url'               => $agreement->pdf_path ? asset('storage/' . $agreement->pdf_path) : null,
                'fwis_url'              => $agreement->fwis_attached ? asset('storage/statutory/fwis.pdf') : null,
                'ceis_url'              => $agreement->ceis_attached ? asset('storage/statutory/ceis.pdf') : null,
                'already_signed'        => in_array($agreement->status, ['employee_signed', 'fully_executed']),
                'fully_executed'        => $agreement->status === 'fully_executed',
            ],
        ], 200);
    }

    /**
     * STEP 3 — Employee submits their drawn/auto-generated signature.
     * POST /employment-agreement/sign/{token}
     * Body: { signature_name, signature_image (base64 data URI) }
     */
    public function sign(Request $request, $token)
    {
        $request->validate([
            'signature_name'  => 'required|string|max:255',
            'signature_image' => 'required|string',
        ]);

        $agreement = EmployeeAgreement::where('signing_token', $token)->first();
        if (!$agreement) {
            return response()->json(['success' => false, 'message' => 'Agreement not found or link expired.'], 200);
        }
        if (in_array($agreement->status, ['employee_signed', 'fully_executed'])) {
            return response()->json(['success' => false, 'message' => 'This agreement has already been signed.'], 200);
        }

        $signatureData = preg_replace('/^data:image\/(png|jpeg);base64,/', '', $request->signature_image);
        $decodedImage = base64_decode($signatureData, true);
        if ($decodedImage === false || strlen($decodedImage) < 50) {
            return response()->json(['success' => false, 'message' => 'Invalid signature image.'], 422);
        }

        $signatureDirectory = storage_path('app/public/employee-agreements/signatures');
        if (!file_exists($signatureDirectory)) {
            mkdir($signatureDirectory, 0755, true);
        }
        $signatureFilename = $agreement->agreement_number . '-employee-signature.png';
        file_put_contents($signatureDirectory . DIRECTORY_SEPARATOR . $signatureFilename, $decodedImage);

        $signedAt = now();

        $this->regenerateAndStorePdf($agreement, [
            'employee_signature_name'         => $request->signature_name,
            'employee_signature_image_base64' => base64_encode($decodedImage),
            'employee_signed_at'              => $signedAt->format('d M Y, g:i A'),
        ]);

        $agreement->update([
            'status'                        => 'employee_signed',
            'employee_signature_name'       => $request->signature_name,
            'employee_signature_image_path' => 'employee-agreements/signatures/' . $signatureFilename,
            'employee_signed_at'            => $signedAt,
            'employee_signed_ip'            => $request->ip(),
        ]);

        // Notify admin that the employee's half is done and a countersignature is needed.
        $adminAddress = config('mail.staffoo_admin_address', 'Amrit.Arora@Staffoo.com.au');
        try {
            Mail::to($adminAddress)->send(new EmployeeAgreementSignedMail(
                'Admin', $agreement->employee_name, $agreement->agreement_number,
                file_get_contents(storage_path('app/public/' . $agreement->pdf_path))
            ));
        } catch (\Exception $e) {
            Log::error('Failed to notify admin of employee signature', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Signed. This is now awaiting Staffoo\'s countersignature — you\'ll receive the final copy by email once that\'s done.',
        ], 200);
    }

    /**
     * STEP 4 — Ops Manager / Director countersigns (admin-authenticated route).
     * Completes dual execution; emails the fully executed PDF + statutory
     * attachments (FWIS always, CEIS if casual) to the employee and admin.
     *
     * POST /admin/employment-agreement/{id}/countersign
     * Body: { signature_name, signature_image }
     */
    public function counterSign(Request $request, $id)
    {
        $request->validate([
            'signature_name'  => 'required|string|max:255',
            'signature_image' => 'required|string',
        ]);

        $agreement = EmployeeAgreement::find($id);
        if (!$agreement) {
            return response()->json(['success' => false, 'message' => 'Agreement not found.'], 200);
        }
        if ($agreement->status !== 'employee_signed') {
            return response()->json(['success' => false, 'message' => 'Agreement must be signed by the employee first.'], 200);
        }

        $signatureData = preg_replace('/^data:image\/(png|jpeg);base64,/', '', $request->signature_image);
        $decodedImage = base64_decode($signatureData, true);
        if ($decodedImage === false || strlen($decodedImage) < 50) {
            return response()->json(['success' => false, 'message' => 'Invalid signature image.'], 422);
        }

        $signatureDirectory = storage_path('app/public/employee-agreements/signatures');
        $signatureFilename = $agreement->agreement_number . '-employer-signature.png';
        file_put_contents($signatureDirectory . DIRECTORY_SEPARATOR . $signatureFilename, $decodedImage);

        $signedAt = now();

        $this->regenerateAndStorePdf($agreement, [
            'employer_signature_name'         => $request->signature_name,
            'employer_signature_image_base64' => base64_encode($decodedImage),
            'employer_signed_at'              => $signedAt->format('d M Y, g:i A'),
        ], asFinal: true);

        $agreement->update([
            'status'                        => 'fully_executed',
            'employer_signature_name'       => $request->signature_name,
            'employer_signature_image_path' => 'employee-agreements/signatures/' . $signatureFilename,
            'employer_signed_at'            => $signedAt,
            'employer_signed_by'            => auth()->id(),
            'employer_signed_ip'            => $request->ip(),
        ]);

        $finalPdfBytes = file_get_contents(storage_path('app/public/' . $agreement->signed_pdf_path));

        $statutoryPaths = [
            'fwis' => $agreement->fwis_attached ? storage_path('app/public/statutory/fwis.pdf') : null,
            'ceis' => $agreement->ceis_attached ? storage_path('app/public/statutory/ceis.pdf') : null,
        ];

        if (!empty($agreement->employee_email)) {
            try {
                Mail::to($agreement->employee_email)->send(new EmployeeAgreementSignedMail(
                    $agreement->employee_name, $agreement->employee_name, $agreement->agreement_number,
                    $finalPdfBytes, $statutoryPaths
                ));
            } catch (\Exception $e) {
                Log::error('Failed to send fully executed agreement to employee', ['error' => $e->getMessage()]);
            }
        }

        $adminAddress = config('mail.staffoo_admin_address', 'Amrit.Arora@Staffoo.com.au');
        try {
            Mail::to($adminAddress)->send(new EmployeeAgreementSignedMail(
                'Admin', $agreement->employee_name, $agreement->agreement_number, $finalPdfBytes, $statutoryPaths
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send fully executed agreement to admin', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Agreement fully executed and emailed to the employee with statutory attachments.',
        ], 200);
    }

    /**
     * Shared PDF-regeneration step used by both sign() and counterSign().
     * Rebuilds from the frozen schedule1_snapshot plus whichever signature
     * fields are being added, so earlier signatures already on file are
     * preserved automatically (they're read back from the DB columns).
     */
    private function regenerateAndStorePdf(EmployeeAgreement $agreement, array $newFields, bool $asFinal = false): void
    {
        $pdfData = array_merge($agreement->schedule1_snapshot ?? [], [
            'agreement_number' => $agreement->agreement_number,
            'date'             => $agreement->created_at->format('d M Y'),
            'employee_name'    => $agreement->employee_name,
            'employee_address' => $agreement->employee_address,
            'employee_phone'   => $agreement->employee_phone,
            'employee_email'   => $agreement->employee_email,
            'employment_type'      => $agreement->employment_type,
            'classification_level' => $agreement->classification_level,
            'security_license_no'  => $agreement->security_license_no,
            'guaranteed_hours'      => $agreement->guaranteed_hours,
            'commencement_date'     => optional($agreement->commencement_date)->format('d M Y'),
            'fwis_attached' => $agreement->fwis_attached,
            'ceis_attached' => $agreement->ceis_attached,
            'employee_signature_name'         => $agreement->employee_signature_name,
            'employee_signature_image_base64' => $agreement->employee_signature_image_path
                ? base64_encode(file_get_contents(storage_path('app/public/' . $agreement->employee_signature_image_path)))
                : null,
            'employee_signed_at' => $agreement->employee_signed_at?->format('d M Y, g:i A'),
        ], $newFields);

        $service = new EmployeeAgreementService();
        $pdfBytes = $service->generatePdf($pdfData);

        $directory = storage_path('app/public/employee-agreements');
        $filename = $agreement->agreement_number . ($asFinal ? '-fully-executed.pdf' : '-employee-signed.pdf');
        file_put_contents($directory . DIRECTORY_SEPARATOR . $filename, $pdfBytes);

        $relativePath = 'employee-agreements/' . $filename;
        $agreement->pdf_path = $relativePath;
        if ($asFinal) {
            $agreement->signed_pdf_path = $relativePath;
        }
        $agreement->save();
    }

    /**
     * Admin dashboard list — GET /admin/employment-agreements?status=pending_signature
     */
    public function index(Request $request)
    {
        $query = EmployeeAgreement::with(['user:id,name,email']);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $agreements = $query->orderByDesc('created_at')->paginate(25);

        return response()->json(['success' => true, 'data' => $agreements], 200);
    }

    /**
     * Resend the signing-link email (e.g. employee lost it).
     * POST /admin/employment-agreement/{id}/resend
     */
    public function resend($id)
    {
        $agreement = EmployeeAgreement::find($id);
        if (!$agreement || $agreement->status !== 'pending_signature') {
            return response()->json(['success' => false, 'message' => 'Nothing to resend.'], 200);
        }

        $signingLink = rtrim(config('app.frontend_url', config('app.url')), '/')
            . '/employment-agreement/sign?token=' . $agreement->signing_token;

        if (!empty($agreement->employee_email)) {
            Mail::to($agreement->employee_email)->send(new EmployeeAgreementSignatureRequestMail(
                $agreement->employee_name, $agreement->agreement_number, $signingLink
            ));
        }

        return response()->json(['success' => true, 'message' => 'Signing link re-sent.'], 200);
    }

    /**
     * One-time admin upload of the master statutory PDFs (download the
     * official FWIS / CEIS once from fairwork.gov.au and upload them here —
     * every agreement generated afterwards attaches the current copy).
     * POST /admin/employment-agreement/statutory-documents
     * Body: multipart, fields "fwis" and/or "ceis" (PDF files)
     */
    public function uploadStatutoryDocuments(Request $request)
    {
        $request->validate([
            'fwis' => 'nullable|file|mimes:pdf|max:5120',
            'ceis' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $directory = storage_path('app/public/statutory');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $saved = [];
        foreach (['fwis', 'ceis'] as $key) {
            if ($request->hasFile($key)) {
                $request->file($key)->move($directory, "{$key}.pdf");
                $saved[] = strtoupper($key);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $saved ? implode(' and ', $saved) . ' uploaded and will be attached to every agreement going forward.' : 'No files uploaded.',
        ], 200);
    }
}
