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

/**
 * REVISED FLOW (per updated requirements):
 *
 *  1. Admin clicks "Send Employment Agreement" for a staff member — no form,
 *     nothing to fill in. send() auto-fills Schedule 1 entirely from
 *     Onboarding/User/Staff and defaults employment_type to "casual". The
 *     agreement + signing link is emailed straight to the employee.
 *  2. Employee opens the link. Every field is read-only EXCEPT Employment
 *     Category (defaults to Casual, selectable) and Classification Level.
 *     If they switch to Part-Time, they also set Guaranteed Hours (Fair
 *     Work Act requires it for Part-Time).
 *  3. Employee signs & submits -> sign() locks in whichever employment_type/
 *     classification_level/guaranteed_hours they picked, regenerates the
 *     PDF with their signature, and emails a copy (with the PDF attached)
 *     to the admin. Status -> employee_signed.
 *  4. Admin opens the agreement in the admin portal and countersigns ->
 *     counterSign() produces the fully executed PDF and emails it to BOTH
 *     the employee and the admin, with FWIS (+ CEIS if casual) attached.
 *  5. Admin can see every agreement, and open the signed PDF, from the
 *     admin portal list (index()).
 */
class EmployeeAgreementController extends Controller
{
    private const DEFAULT_EMPLOYMENT_TYPE = 'casual';
    private const DEFAULT_CLASSIFICATION_LEVEL = 1;

    /**
     * STEP 1 — Admin triggers this for a staff member. No request body
     * needed — everything is auto-filled; the employee makes the two calls
     * (employment type / classification) themselves when they sign.
     *
     * POST /admin/staff/{userId}/employment-agreement/send
     */
    public function send(Request $request, $userId)
    {
        $user = User::find($userId);
        $staff = Staff::where('user_id', $userId)->first();
        $onboarding = Onboarding::where('user_id', $userId)->first();

        if (!$user || !$staff) {
            return response()->json(['success' => false, 'message' => 'Staff member not found.'], 200);
        }

        $employeeName    = $onboarding->full_name ?? $user->name;
        $employeeAddress = $onboarding->address ?? $user->address;
        $employeePhone   = $onboarding->mobile ?? $staff->phone ?? $user->phone;
        $employeeEmail   = $onboarding->email ?? $user->email;
        $licenseNo       = $onboarding->security_license ?? $staff->security_license_no;

        if (empty($employeeEmail)) {
            return response()->json(['success' => false, 'message' => 'This staff member has no email on file — cannot send the agreement.'], 200);
        }

        // Defaults — the employee changes these themselves on the signing
        // page before they sign. Carry over whatever was set last time
        // (re-issue case) rather than always resetting to the default.
        $employmentType      = $staff->employment_type ?: self::DEFAULT_EMPLOYMENT_TYPE;
        $classificationLevel = $staff->classification_level ?: self::DEFAULT_CLASSIFICATION_LEVEL;
        $guaranteedHours     = $staff->guaranteed_hours;
        $commencementDate    = $staff->commencement_date ?: now()->format('Y-m-d');

        // Reuse the existing pending agreement for this staff member instead
        // of minting a new one every time admin clicks "send" (e.g. resend
        // after the employee lost the email) — mirrors the `contracts`
        // table convention of one live record per party.
        $existing = EmployeeAgreement::where('staff_id', $staff->id)
            ->where('status', 'pending_signature')
            ->first();

        $agreementNumber = $existing->agreement_number ?? ('EMP-AGR-' . strtoupper(Str::random(8)));
        $signingToken = $existing->signing_token ?? Str::random(48);

        $snapshot = [
            'employee_name'    => $employeeName,
            'employee_address' => $employeeAddress,
            'employee_phone'   => $employeePhone,
            'employee_email'   => $employeeEmail,
            'security_license_no' => $licenseNo,
            'employment_type'      => $employmentType,
            'classification_level' => $classificationLevel,
            'guaranteed_hours'      => $guaranteedHours,
            'commencement_date'     => $commencementDate,
        ];

        $pdfData = array_merge($snapshot, [
            'agreement_number' => $agreementNumber,
            'date'             => now()->format('d M Y'),
            'fwis_attached'    => true,
            'ceis_attached'    => $employmentType === 'casual',
        ]);

        $service = new EmployeeAgreementService();
        $pdfBytes = $service->generatePdf($pdfData);

        $directory = storage_path('app/public/employee-agreements');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        $filename = $agreementNumber . '.pdf';
        file_put_contents($directory . DIRECTORY_SEPARATOR . $filename, $pdfBytes);

        $payload = array_merge($snapshot, [
            'staff_id'   => $staff->id,
            'user_id'    => $userId,
            'created_by' => auth()->id(),
            'agreement_number' => $agreementNumber,
            'schedule1_snapshot' => $snapshot,
            'fwis_attached' => true,
            'ceis_attached' => $employmentType === 'casual',
            'pdf_path'      => 'employee-agreements/' . $filename,
            'signing_token' => $signingToken,
            'status'        => 'pending_signature',
        ]);

        $agreement = $existing
            ? tap($existing)->update($payload)
            : EmployeeAgreement::create($payload);

        $signingLink = rtrim(config('app.frontend_url', config('app.url')), '/')
            . '/employment-agreement/sign?token=' . $signingToken;

        try {
            Mail::to($employeeEmail)->send(new EmployeeAgreementSignatureRequestMail(
                $employeeName,
                $agreementNumber,
                $signingLink
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send employment agreement signature request', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Agreement generated but the email failed to send. Use Resend to try again.'], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Employment agreement emailed to the employee for signature.',
            'data' => [
                'agreement_id'     => $agreement->id,
                'agreement_number' => $agreementNumber,
                'signing_link'     => $signingLink,
            ],
        ], 200);
    }

    /**
     * STEP 2 — Employee opens the link on their phone. Public endpoint.
     * Returns the read-only fields plus the current/default employment_type
     * ("casual" on a first send) and classification_level, so the frontend
     * can pre-select them in editable controls.
     *
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
                'employee_address'      => $agreement->employee_address,
                'employee_phone'        => $agreement->employee_phone,
                'employee_email'        => $agreement->employee_email,
                'security_license_no'   => $agreement->security_license_no,
                // Editable by the employee before they sign:
                'employment_type'       => $agreement->employment_type,      // default "casual"
                'classification_level'  => $agreement->classification_level, // default 1
                'guaranteed_hours'      => $agreement->guaranteed_hours,
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
     * STEP 3 — Employee confirms employment_type + classification_level
     * (and guaranteed_hours if Part-Time) and submits their signature.
     * This is also where those fields get LOCKED IN for this agreement.
     *
     * POST /employment-agreement/sign/{token}
     * Body: {
     *   signature_name, signature_image,
     *   employment_type: full_time|part_time|casual,
     *   classification_level: 1-5,
     *   guaranteed_hours (required if employment_type = part_time)
     * }
     */
    public function sign(Request $request, $token)
    {
        $validated = $request->validate([
            'signature_name'  => 'required|string|max:255',
            'signature_image' => 'required|string',
            'employment_type'      => 'required|in:full_time,part_time,casual',
            'classification_level' => 'required|integer|min:1|max:5',
            'guaranteed_hours'      => 'required_if:employment_type,part_time|nullable|string|max:100',
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
        $isCasual = $validated['employment_type'] === 'casual';

        // Lock in whichever employment type / classification / guaranteed
        // hours the employee picked, BEFORE regenerating the PDF, so the
        // signed PDF reflects their actual choice.
        $agreement->employment_type      = $validated['employment_type'];
        $agreement->classification_level = $validated['classification_level'];
        $agreement->guaranteed_hours      = $validated['guaranteed_hours'] ?? null;
        $agreement->ceis_attached         = $isCasual;
        $agreement->schedule1_snapshot    = array_merge($agreement->schedule1_snapshot ?? [], [
            'employment_type'      => $validated['employment_type'],
            'classification_level' => $validated['classification_level'],
            'guaranteed_hours'      => $validated['guaranteed_hours'] ?? null,
        ]);
        $agreement->save();

        $this->regenerateAndStorePdf($agreement, [
            'employee_signature_name'         => $validated['signature_name'],
            'employee_signature_image_base64' => base64_encode($decodedImage),
            'employee_signed_at'              => $signedAt->format('d M Y, g:i A'),
        ]);

        $agreement->update([
            'status'                        => 'employee_signed',
            'employee_signature_name'       => $validated['signature_name'],
            'employee_signature_image_path' => 'employee-agreements/signatures/' . $signatureFilename,
            'employee_signed_at'            => $signedAt,
            'employee_signed_ip'            => $request->ip(),
        ]);

        // Keep the canonical Staff record in sync now that the employee has
        // actually locked these values in — rostering/payroll read these.
        Staff::where('id', $agreement->staff_id)->update([
            'employment_type'      => $agreement->employment_type,
            'classification_level' => $agreement->classification_level,
            'guaranteed_hours'      => $agreement->guaranteed_hours,
        ]);

        // Send a copy to admin with the (employee-signed) PDF attached, so
        // admin can review before countersigning in the portal.
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
     * STEP 4 — Ops Manager / Director countersigns from the admin portal.
     * Completes dual execution; emails the fully executed PDF + statutory
     * attachments (FWIS always, CEIS if the employee ended up Casual) to
     * BOTH the employee and the admin.
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
            'message' => 'Agreement fully executed and emailed to the employee and admin with statutory attachments.',
        ], 200);
    }

    /**
     * Shared PDF-regeneration step used by both sign() and counterSign().
     * Rebuilds from the agreement's CURRENT columns (not a stale snapshot),
     * so whichever employment_type/classification the employee just locked
     * in, and whichever signatures already exist, always come through.
     */
    private function regenerateAndStorePdf(EmployeeAgreement $agreement, array $newFields, bool $asFinal = false): void
    {
        $pdfData = array_merge([
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
     * Admin portal list — GET /admin/employment-agreements?status=pending_signature
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