<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class EmployeeAgreementService
{
    /**
     * Security Services Industry Award 2020 [MA000016] — 2026 baseline rates
     * from the master template (Schedule 2). These are fixed award minimums,
     * not per-employee — every agreement shows the same matrix, with the
     * employee's own row highlighted.
     *
     * NOTE: when the Fair Work Commission's annual wage review lands each
     * 1 July, update this one array — every agreement generated afterwards
     * (new hires, re-prints) picks up the new numbers automatically. It does
     * NOT retroactively change PDFs already signed and on file.
     */
    public const RATE_MATRIX = [
        1 => ['ft_pt' => [28.42, 34.59, 42.63, 56.84, 71.05], 'casual' => [35.53, 41.69, 49.74, 63.95, 78.16]],
        2 => ['ft_pt' => [29.24, 35.59, 43.86, 58.48, 73.10], 'casual' => [36.55, 42.89, 51.17, 65.79, 80.41]],
        3 => ['ft_pt' => [29.73, 36.18, 44.60, 59.46, 74.33], 'casual' => [37.16, 43.61, 52.03, 66.89, 81.76]],
        4 => ['ft_pt' => [30.23, 36.79, 45.35, 60.46, 75.58], 'casual' => [37.79, 44.35, 52.90, 68.02, 83.13]],
        5 => ['ft_pt' => [31.20, 37.97, 46.80, 62.40, 78.00], 'casual' => [39.00, 45.77, 54.60, 70.20, 85.80]],
    ];
    private const RATE_COLUMNS = ['Ordinary Weekday', 'Night Span / Shift', 'Saturday Penalty', 'Sunday Penalty', 'Public Holiday'];

    /**
     * Expected $data keys:
     *  agreement_number, date,
     *  employee_name, employee_address, employee_phone, employee_email,
     *  employment_type (full_time|part_time|casual), classification_level (1-5),
     *  security_license_no, guaranteed_hours, commencement_date,
     *  fwis_attached, ceis_attached,
     *
     *  Optional (only present once one or both parties have signed):
     *  employee_signature_name, employee_signature_image_base64, employee_signed_at,
     *  employer_signature_name, employer_signature_image_base64, employer_signed_at,
     */
    public function generatePdf(array $data): string
    {
        $pdf = Pdf::loadHTML($this->buildHtml($data))->setPaper('a4', 'portrait');
        return $pdf->output();
    }

    private function employmentTypeLabel(string $type): string
    {
        return match ($type) {
            'full_time' => 'Full-Time',
            'part_time' => 'Part-Time',
            'casual'    => 'Casual',
            default     => $type,
        };
    }

    private function renderRateMatrix(string $employmentType, int $highlightLevel): string
    {
        $isCasual = $employmentType === 'casual';
        $rowKey = $isCasual ? 'casual' : 'ft_pt';

        $html = "<table class='rate-table'><thead><tr>";
        $html .= "<th>Classification</th><th>Employment Type</th>";
        foreach (self::RATE_COLUMNS as $col) {
            $html .= "<th>" . htmlspecialchars($col) . "</th>";
        }
        $html .= "</tr></thead><tbody>";

        foreach (self::RATE_MATRIX as $level => $rates) {
            foreach (['ft_pt' => 'Full-Time / Part-Time', 'casual' => 'Casual (incl. 25%)'] as $key => $label) {
                $isHighlighted = ($level === $highlightLevel && $key === $rowKey);
                $rowClass = $isHighlighted ? "class='rate-row-highlight'" : '';
                $html .= "<tr {$rowClass}>";
                $html .= "<td>Security Officer Level {$level}</td><td>{$label}</td>";
                foreach ($rates[$key] as $v) {
                    $html .= "<td>$" . number_format($v, 2) . "/hr</td>";
                }
                $html .= "</tr>";
            }
        }
        $html .= "</tbody></table>";
        return $html;
    }

    private function signatureBlockHtml(string $title, ?string $name, ?string $imageBase64, ?string $signedAt, string $roleNote = ''): string
    {
        $isSigned = !empty($name) || !empty($imageBase64);
        $html = "<div class='sign-col'>";
        $html .= "<div class='sign-subtitle'>{$title}</div>";
        if ($roleNote) {
            $html .= "<div class='sign-role-note'>{$roleNote}</div>";
        }
        if ($isSigned) {
            if ($imageBase64) {
                if (!str_starts_with($imageBase64, 'data:image')) {
                    $imageBase64 = 'data:image/png;base64,' . $imageBase64;
                }
                $html .= "<img src='{$imageBase64}' class='signature-image' />";
            } else {
                $html .= "<div class='unsigned-line'></div>";
            }
            $html .= "<div class='sign-row'><span class='sign-label'>Name:</span><strong>" . htmlspecialchars($name ?? '') . "</strong></div>";
            $html .= "<div class='sign-row'><span class='sign-label'>Date:</span>" . htmlspecialchars($signedAt ?? '') . "</div>";
        } else {
            $html .= "<div class='unsigned-line'></div>";
            $html .= "<div class='sign-row'><span class='sign-label'>Name:</span><span class='unsigned-line-inline'></span></div>";
            $html .= "<div class='sign-row'><span class='sign-label'>Date:</span><span class='unsigned-line-inline'></span></div>";
        }
        $html .= "</div>";
        return $html;
    }

    private function buildHtml(array $d): string
    {
        $agreementNumber = htmlspecialchars($d['agreement_number']);
        $date            = htmlspecialchars($d['date']);
        $employeeName    = htmlspecialchars($d['employee_name']);
        $employeeAddress = htmlspecialchars($d['employee_address'] ?? '—');
        $employeePhone   = htmlspecialchars($d['employee_phone'] ?? '—');
        $employeeEmail   = htmlspecialchars($d['employee_email'] ?? '—');
        $employmentType  = $d['employment_type'];
        $classification  = (int) $d['classification_level'];
        $licenseNo       = htmlspecialchars($d['security_license_no'] ?? '—');
        $guaranteedHours = htmlspecialchars($d['guaranteed_hours'] ?? 'N/A');
        $commencement    = htmlspecialchars($d['commencement_date'] ?? '—');
        $typeLabel       = $this->employmentTypeLabel($employmentType);

        $employeeSigHtml = $this->signatureBlockHtml(
            'SIGNED by the EMPLOYEE',
            $d['employee_signature_name'] ?? null,
            $d['employee_signature_image_base64'] ?? null,
            $d['employee_signed_at'] ?? null
        );
        $employerSigHtml = $this->signatureBlockHtml(
            'For and on behalf of CAPITAL SERVICES PTY LTD',
            $d['employer_signature_name'] ?? null,
            $d['employer_signature_image_base64'] ?? null,
            $d['employer_signed_at'] ?? null,
            'Director / Operations Manager'
        );

        $fwisAttached = $d['fwis_attached'] ?? true;
        $ceisAttached = $d['ceis_attached'] ?? false;

        $css = '
        @page { margin-top: 46px; margin-bottom: 46px; margin-left: 0; margin-right: 0; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a2e; line-height: 1.4; padding-left: 50px; padding-right: 50px; }
        .wrapper { max-width: 800px; margin: 0 auto; }
        .header { border-bottom: 3px solid #0A7C6E; padding-bottom: 10px; margin-bottom: 14px; }
        .header-title { font-size: 15px; font-weight: bold; }
        .header-subtitle { font-size: 9px; color: #6B7280; margin-top: 2px; }
        .header-meta { font-size: 9px; color: #6B7280; text-align: right; }
        .section-title { font-size: 12.5px; font-weight: bold; color: #0A7C6E; margin: 12px 0 6px; page-break-after: avoid; }
        p { margin-bottom: 7px; text-align: justify; }
        .notice-box { background:#f0fdf4; border:1px solid #86efac; border-radius:6px; padding:10px 12px; margin-bottom:12px; font-size:9.3px; }
        .schedule-table { width:100%; border-collapse: collapse; margin-bottom: 10px; page-break-inside: avoid; }
        .schedule-table td { border: 1px solid #d1d5db; padding: 6px 8px; font-size: 9.5px; vertical-align: top; }
        .schedule-table td.label-cell { background:#f8fafc; font-weight:bold; width: 34%; color:#374151; }
        .rate-table { width:100%; border-collapse: collapse; margin-bottom: 10px; font-size: 7.6px; page-break-inside: avoid; }
        .rate-table th { background:#0A7C6E; color:#fff; padding: 4px 3px; font-size: 7.4px; }
        .rate-table td { border: 1px solid #e2e8f0; padding: 3px 4px; text-align:center; }
        .rate-table td:first-child, .rate-table td:nth-child(2) { text-align:left; }
        .rate-row-highlight td { background:#ecfdf5; font-weight:bold; color:#065f46; }
        .attach-list { margin: 4px 0 10px 4px; font-size: 9.3px; }
        .attach-list li { margin-bottom: 3px; }
        .sign-section { margin-top: 16px; }
        .sign-cols { width:100%; }
        .sign-col { display:inline-block; width:48%; vertical-align:top; padding: 10px; border:1px solid #d1d5db; border-radius:6px; }
        .sign-col + .sign-col { margin-left: 3%; }
        .sign-subtitle { font-weight:bold; font-size:10px; margin-bottom:6px; }
        .sign-role-note { font-size:8.5px; color:#6B7280; margin-bottom: 6px; }
        .sign-row { font-size:9px; margin-top:4px; }
        .sign-label { color:#6B7280; display:inline-block; width: 45px; }
        .unsigned-line { border-bottom:1px solid #9ca3af; height: 40px; margin-bottom:4px; }
        .unsigned-line-inline { border-bottom:1px solid #9ca3af; width:120px; display:inline-block; height:12px; }
        .signature-image { height: 40px; max-width: 95%; border-bottom:1px solid #9ca3af; margin-bottom:4px; }
        .footer { margin-top: 14px; font-size: 8px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 8px; }
        ';

        $html = "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><style>{$css}</style></head><body><div class='wrapper'>";

        $html .= "<div class='header'><table style='width:100%;'><tr>";
        $html .= "<td><div class='header-title'>Comprehensive Security Staff Employment Agreement</div>";
        $html .= "<div class='header-subtitle'>Capital Services Pty Ltd t/a Staffoo &middot; ABN 48 613 317 838 &middot; Framework Contract — Full-Time, Part-Time &amp; Casual Security Personnel</div></td>";
        $html .= "<td class='header-meta'>Agreement #: {$agreementNumber}<br>Date: {$date}</td>";
        $html .= "</tr></table></div>";

        $html .= "<div class='notice-box'><strong>Employment Framework Notice:</strong> This document constitutes a binding contract of employment between "
               . "Capital Services Pty Ltd (t/a Staffoo) and the Employee named in Schedule 1. It operates in conjunction with the Fair Work Act 2009 (Cth), "
               . "state Security Industry legislation, and state Labour Hire Licensing laws, and incorporates the Security Services Industry Award 2020 [MA000016].</div>";

        // Abbreviated summary of key clauses (full text lives in the master template / company policy library — not duplicated per-employee PDF to keep print size small)
        $html .= "<div class='section-title'>Key Terms Summary</div>";
        $html .= "<p>The Employee is engaged on the terms of the Security Services Industry Award 2020 [MA000016] and the Fair Work Act 2009 (Cth) National Employment Standards. "
               . "Duties include static guarding, access control, crowd control, patrols, and incident/CCTV monitoring as rostered via the Staffoo Mobile Application, which is used "
               . "exclusively for shift check-in/check-out, geofenced attendance, break logging and incident reporting. The Employee must maintain a current State Security Licence and "
               . "any role-specific credentials (First Aid, RSA, WWCC) at all times, must present in standard uniform, and is subject to a zero-tolerance drug and alcohol policy. "
               . "Full clause text (classification, termination notice scale, confidentiality, non-solicitation, deductions, dispute resolution) is set out in the Staffoo Employment "
               . "Agreement master terms (Ref: EMP-AGR-2026-V4), which this Schedule incorporates by reference and which the Employee is provided in full alongside this signing page.</p>";

        // Schedule 1
        $html .= "<div class='section-title'>Schedule 1 — Employment Particulars</div>";
        $html .= "<table class='schedule-table'>";
        $html .= "<tr><td class='label-cell'>Employer Legal Entity</td><td>Capital Services Pty Ltd t/a Staffoo (ABN 48 613 317 838)</td></tr>";
        $html .= "<tr><td class='label-cell'>Employee Full Name</td><td>{$employeeName}</td></tr>";
        $html .= "<tr><td class='label-cell'>Residential Address</td><td>{$employeeAddress}</td></tr>";
        $html .= "<tr><td class='label-cell'>Contact Phone &amp; Email</td><td>{$employeePhone} &middot; {$employeeEmail}</td></tr>";
        $html .= "<tr><td class='label-cell'>Category of Employment</td><td><strong>{$typeLabel}</strong></td></tr>";
        $html .= "<tr><td class='label-cell'>Award &amp; Classification</td><td>Security Services Industry Award 2020 [MA000016] — Level {$classification}</td></tr>";
        $html .= "<tr><td class='label-cell'>State Security Licence #</td><td>{$licenseNo}</td></tr>";
        $html .= "<tr><td class='label-cell'>Agreed Ordinary Hours</td><td>" . ($employmentType === 'full_time' ? '38 hours per week' : ($employmentType === 'part_time' ? $guaranteedHours : 'On-demand (casual, no guaranteed hours)')) . "</td></tr>";
        $html .= "<tr><td class='label-cell'>Commencement Date</td><td>{$commencement}</td></tr>";
        $html .= "</table>";

        // Schedule 2
        $html .= "<div class='section-title'>Schedule 2 — Statutory Pay Rate Matrix (2026 Baseline &amp; Award Penalties)</div>";
        $html .= "<p style='font-size:8.5px;'>Pay rates conform to the Security Services Industry Award 2020 [MA000016], effective 1 July 2026. Casual rates include the mandatory 25% casual loading. "
               . "The Employee's own classification and employment type is highlighted below. Rates adjust automatically each 1 July following the Fair Work Commission Annual Wage Review — "
               . "current rates can always be verified at www.fairwork.gov.au/pay-and-wages/pay-guides.</p>";
        $html .= $this->renderRateMatrix($employmentType, $classification);

        // Statutory attachments
        $html .= "<div class='section-title'>Mandatory Statutory Attachments</div>";
        $html .= "<p style='font-size:9px;'>Under the Fair Work Act 2009 (Cth), this Agreement is not complete on its own. The following statutory documents are provided to the Employee together with this Agreement:</p>";
        $html .= "<ul class='attach-list'>";
        $html .= "<li>" . ($fwisAttached ? '&#9745;' : '&#9744;') . " <strong>Fair Work Information Statement (FWIS)</strong> — provided to every new employee.</li>";
        if ($employmentType === 'casual') {
            $html .= "<li>" . ($ceisAttached ? '&#9745;' : '&#9744;') . " <strong>Casual Employment Information Statement (CEIS)</strong> — provided in addition to the FWIS for Casual employees.</li>";
        }
        $html .= "</ul>";

        // Execution
        $html .= "<div class='section-title'>Execution &amp; Acknowledgement</div>";
        $html .= "<p style='font-size:9px;'>By signing below, the Employee acknowledges that they have read, understood, and accepted all terms, conditions, and schedules of this Employment Agreement, "
               . "including the requirement to maintain active licensing, comply with WHS policies, and utilise the Staffoo Mobile Application for shift management, and confirms receipt of the statutory attachments listed above.</p>";

        $html .= "<div class='sign-section'>{$employeeSigHtml}{$employerSigHtml}</div>";

        $html .= "<div class='footer'>Staffoo (Capital Services Pty Ltd) — ABN 48 613 317 838 — Agreement {$agreementNumber}</div>";
        $html .= "</div></body></html>";

        return $html;
    }
}
