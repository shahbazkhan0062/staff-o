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
        // NOTE: returns just the inner content — the caller wraps this in a
        // <td class="sign-col"> table cell (see buildHtml()), not a <div>.
        // dompdf mis-paginates an inline-block box with page-break-inside:
        // avoid (it can render a solid black bar where the page splits);
        // a table cell paginates cleanly, so the box is never cut in half.
        $isSigned = !empty($name) || !empty($imageBase64);
        $html = "<div class='sign-subtitle'>{$title}</div>";
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
        return $html;
    }

    /**
     * Clauses 1-15, reproduced verbatim from the Staffoo master template
     * (Ref: EMP-AGR-2026-V4) — same wording, same clause numbers/headings,
     * same sub-clause structure as the source PDF. This is static legal
     * text (identical on every agreement); only Schedule 1/2 below it are
     * per-employee. Clause 16 and the signature section are rendered by
     * buildHtml() itself, immediately after this.
     */
    private function fullClausesHtml(): string
    {
        $html = "<div class='notice-box'><strong>Employment Framework Notice:</strong> This document constitutes a binding contract of employment between Capital "
               . "Services Pty Ltd (t/a Staffoo) and the Employee named in Schedule 1. It operates in full conjunction with the Fair Work Act 2009 (Cth), state Security "
               . "Industry acts/regulations, and state Labour Hire Licensing laws.</div>";

        $html .= "<div class='section-title'>1. Parties &amp; Operational Framework</div>";
        $html .= "<p>This Employment Agreement (the \"Agreement\") is made between Capital Services Pty Ltd trading as Staffoo (ABN 48 613 317 838) (\"the Employer\" or "
               . "\"Company\"), located at Melbourne VIC Australia, and the individual identified as the Employee in Schedule 1 of this Agreement (\"the Employee\").</p>";
        $html .= "<p>The Employer operates a specialized security manpower, technology, and labour hire platform providing verified security personnel, crowd controllers, "
               . "monitoring staff, and event security across Australia. The Employee agrees to perform security services for the Employer and its deployed clients in strict "
               . "compliance with the terms and conditions set out herein.</p>";

        $html .= "<div class='section-title'>2. Industrial Instrument &amp; Classification</div>";
        $html .= "<p><span class='clause-sub'>2.1 Award Coverage:</span> This Agreement is governed by the Fair Work Act 2009 (Cth), the National Employment Standards (NES), "
               . "and the Security Services Industry Award 2020 [MA000016] (the \"Award\"), as amended or replaced from time to time. To the extent of any inconsistency "
               . "between this Agreement and the Award, the statutory minimums of the Award or NES shall prevail, except where this Agreement provides a more favorable "
               . "benefit to the Employee overall.</p>";
        $html .= "<p><span class='clause-sub'>2.2 Classification Level:</span> The Employee is engaged at the Award Classification Level specified in Schedule 1 (typically "
               . "Security Officer Level 1 through Level 5). The Employee agrees to perform all duties assigned within their statutory competency, licensing class, and "
               . "training level.</p>";

        $html .= "<div class='section-title'>3. Category of Employment</div>";
        $html .= "<p>The employment category shall be specified in Schedule 1 as one of the following:</p>";
        $html .= "<p><span class='clause-sub'>3.1 Full-Time Employment:</span> A Full-Time Employee is engaged to work an average of 38 ordinary hours per week, structured "
               . "over an agreed roster cycle in accordance with Award Clause 13. Full-Time Employees accumulate paid annual leave and personal/carer's leave pursuant to the "
               . "NES.</p>";
        $html .= "<p><span class='clause-sub'>3.2 Part-Time Employment:</span> A Part-Time Employee is engaged to work fewer than 38 ordinary hours per week on a regular "
               . "rostered pattern. In accordance with Award Clause 10, the Employer and Employee shall agree in writing (via Schedule 1 or roster confirmation within the "
               . "Staffoo Platform) on a guaranteed minimum number of ordinary hours per week or fortnightly roster cycle. Hours worked beyond agreed ordinary hours or "
               . "rostered patterns shall be compensated in accordance with Award overtime provisions.</p>";
        $html .= "<p><span class='clause-sub'>3.3 Casual Employment:</span> A Casual Employee is engaged on an hourly, shift-by-shift basis with no firm advance commitment "
               . "to ongoing work or guaranteed rostered hours. Casual Employees receive a 25% casual loading on their ordinary base hourly rate as prescribed in Schedule 2, "
               . "which is paid in lieu of statutory paid leave entitlements (annual leave, personal leave, notice of termination, and redundancy pay under the NES).</p>";

        $html .= "<div class='section-title'>4. Multi-Site Deployment &amp; Duties</div>";
        $html .= "<p><span class='clause-sub'>4.1 Mobile Roster &amp; Site Flexibility:</span> The security industry requires operational agility. The Employee accepts that "
               . "deployment may occur across multiple client locations, venues, commercial properties, public infrastructure, or event spaces as allocated by the Employer "
               . "via the Staffoo Mobile Application (\"Staffoo App\").</p>";
        $html .= "<p><span class='clause-sub'>4.2 Core Operational Duties:</span> The Employee's duties include, but are not limited to:</p>";
        $html .= "<ul class='attach-list'>"
               . "<li>Access control, static guarding, perimeter patrols, crowd control, and asset protection.</li>"
               . "<li>Enforcing client venue rules, screening entrants, managing conflict, and preventing unlawful trespass.</li>"
               . "<li>Monitoring security control rooms, CCTV systems, alarm panels, and automated access gates.</li>"
               . "<li>Maintaining detailed electronic shift logs, incident reports, and risk assessments via the Staffoo App.</li>"
               . "<li>Rendering emergency First Aid and co-ordinating emergency evacuations in accordance with site SOPs.</li>"
               . "<li>Adhering strictly to all client site Standard Operating Procedures (SOPs) and lawful directions.</li>"
               . "</ul>";

        $html .= "<div class='section-title'>5. Remuneration, Penalties &amp; Dynamic Fair Work Rate Linkage</div>";
        $html .= "<p><span class='clause-sub'>5.1 Hourly Rates &amp; Allowances:</span> The Employer shall pay the Employee not less than the applicable minimum hourly rate, "
               . "penalty rates (night shift, Saturday, Sunday, Public Holiday), and allowances prescribed under the Award for the Employee's classification level. The "
               . "baseline rates applying as of 2026 are set out in Schedule 2.</p>";
        $html .= "<p><span class='clause-sub'>5.2 Statutory Superannuation:</span> The Employer will make compulsory superannuation contributions into the Employee's "
               . "nominated eligible superannuation fund in accordance with the Superannuation Guarantee (Administration) Act 1992 (Cth).</p>";
        $html .= "<p><span class='clause-sub'>5.3 Dynamic Annual Wage Increases:</span> Minimum wage rates under the Award are reviewed annually by the Fair Work Commission "
               . "(FWC), with adjustments taking effect on 1 July each year. The Employer guarantees that the Employee's base rate shall automatically adjust to match or "
               . "exceed any updated minimum Award rates. The Employee may independently inspect and verify live statutory rates via the official Fair Work Ombudsman portal: "
               . "www.fairwork.gov.au/pay-and-wages/pay-guides</p>";
        $html .= "<p><span class='clause-sub'>5.4 Pay Cycle &amp; Electronic Funds Transfer:</span> Remuneration is calculated fortnightly for hours validated through Staffoo "
               . "App geofenced check-ins and paid via direct electronic funds transfer (EFT) into the Employee's nominated Australian bank account, accompanied by an "
               . "itemized electronic payslip.</p>";

        $html .= "<div class='section-title'>6. Mandatory Statutory Licensing &amp; Labour Hire Compliance</div>";
        $html .= "<p><span class='clause-sub'>6.1 Security Licence Condition Precedent:</span> It is a fundamental condition precedent of employment that the Employee "
               . "maintains at all times a current, valid, and unencumbered State Security Licence (e.g., VIC LRD, NSW SLED, QLD QPS, WA Police, SA Consumer Affairs) with "
               . "appropriate sub-class endorsements (Unarmed Guard, Crowd Controller, Monitoring Operator) corresponding to their assigned duties.</p>";
        $html .= "<p><span class='clause-sub'>6.2 First Aid &amp; Statutory Credentials:</span> The Employee must maintain an active HLTAID011 (Provide First Aid) "
               . "certificate, HLTAID009 (Provide CPR) certificate, RSA (where venue requires), and Working With Children Check (WWCC, where assigned to educational/event "
               . "sites).</p>";
        $html .= "<p><span class='clause-sub'>6.3 Immediate Reporting Duty:</span> The Employee must immediately (within 12 hours) notify the Employer in writing if their "
               . "security licence or driver's licence is suspended, revoked, expired, subjected to court conditions, or if they are charged with an indictable criminal "
               . "offence or disqualifying offence under state security industry legislation.</p>";
        $html .= "<p><span class='clause-sub'>6.4 Labour Hire Licensing Act Compliance:</span> The Employee acknowledges that the Employer operates as a registered Labour "
               . "Hire Provider under applicable state legislation (e.g., Labour Hire Licensing Act 2018 (Vic), Labour Hire Licensing Act 2017 (Qld), Labour Hire Licensing "
               . "Act 2017 (SA), Labour Hire Licensing Act 2020 (ACT)). The Employee agrees to co-operate with all statutory audits and identity verification checks required "
               . "under labour hire laws.</p>";

        $html .= "<div class='section-title'>7. Staffoo App Usage, Geofencing &amp; Timesheet Integrity</div>";
        $html .= "<p><span class='clause-sub'>7.1 Mandatory Mobile Platform Usage:</span> Shift roster allocation, site briefs, check-ins, break logging, incident "
               . "reporting, and check-outs are managed exclusively via the Staffoo Mobile Application.</p>";
        $html .= "<p><span class='clause-sub'>7.2 Geofencing Consent:</span> By accepting shifts, the Employee explicitly consents to the Staffoo App recording GPS "
               . "location coordinates at the time of shift check-in and check-out to verify physical presence on the client site. Location tracking is restricted strictly "
               . "to active shift windows.</p>";
        $html .= "<p><span class='clause-sub'>7.3 Prohibition of Device Tampering:</span> The use of GPS-spoofing software, VPN masking, device jailbreaking, or fraudulent "
               . "proxy check-ins by third parties is strictly prohibited. Any attempt to falsify shift attendance or location data constitutes serious misconduct and will "
               . "result in summary dismissal without notice and immediate reporting to police security licensing units for fraud.</p>";

        $html .= "<div class='section-title'>8. Uniform, Equipment &amp; Standards of Dress</div>";
        $html .= "<p><span class='clause-sub'>8.1 Mandatory Uniform Code:</span> Unless explicitly instructed otherwise by a written client site brief, the Employee must "
               . "attend every shift in the standard professional security uniform:</p>";
        $html .= "<ul class='attach-list'>"
               . "<li>Clean, ironed black or white collared security shirt displaying approved security patches/epaulettes;</li>"
               . "<li>Tailored black trousers (jeans, trackpants, or cargo pants are strictly prohibited);</li>"
               . "<li>Polished black safety footwear (enclosed steel-toe boots where required by site WHS rules);</li>"
               . "<li>High-Visibility (Hi-Vis) safety vest bearing \"SECURITY\" markings where mandated by site SOPs;</li>"
               . "<li>State Security Licence card prominently displayed on outer clothing at all times during shift hours.</li>"
               . "</ul>";
        $html .= "<p><span class='clause-sub'>8.2 Return of Company Property:</span> All badges, uniforms, access cards, keys, body-worn cameras, radios, and devices "
               . "issued by the Employer or Client remain the exclusive property of the Employer/Client and must be returned immediately upon request or termination.</p>";

        $html .= "<div class='section-title'>9. Work Health and Safety (WHS) &amp; Zero Tolerance Drug/Alcohol Policy</div>";
        $html .= "<p><span class='clause-sub'>9.1 WHS Compliance:</span> The Employee must comply with all health and safety instructions, risk assessments, safe work "
               . "method statements (SWMS), and site safety policies issued by the Employer or client.</p>";
        $html .= "<p><span class='clause-sub'>9.2 Zero Tolerance Drug &amp; Alcohol Policy:</span> The security industry is a safety-critical sector. The Employee must "
               . "maintain 0.00% Blood Alcohol Concentration (BAC) and be completely free of illicit drugs, unprescribed narcotics, or impairing substances while on duty or "
               . "presenting for a shift. The Employee agrees to submit to random, post-incident, or reasonable-suspicion drug and alcohol testing conducted by authorized "
               . "testing officers.</p>";
        $html .= "<p><span class='clause-sub'>9.3 Fitness for Work:</span> The Employee warrants that they possess the physical capability, mental alertness, and "
               . "visual/auditory acuity required to perform active security patrols, emergency response, and standing duties without hazard to themselves or others.</p>";

        $html .= "<div class='section-title'>10. Client-Driven Removal &amp; Site Barring (\"Client Stand Down\")</div>";
        $html .= "<p><span class='clause-sub'>10.1 Client Right of Removal:</span> The Employee acknowledges that third-party clients retain the right under commercial "
               . "contracts to request the immediate removal or replacement of any security staff deployed to their site. A client request for removal does not "
               . "automatically terminate employment with Staffoo.</p>";
        $html .= "<p><span class='clause-sub'>10.2 Operational Reallocation:</span> In the event a client bars or requests the removal of an Employee, the Employer will "
               . "assess the circumstances. Where the removal is due to operational resizing or client preference unrelated to misconduct, the Employer will attempt to "
               . "redeploy the Employee to alternative client sites. For Casual Employees, unallocated shifts are not guaranteed. For Full-Time/Part-Time Employees, "
               . "redeployment will be offered subject to available roster vacancies and operational feasibility.</p>";

        $html .= "<div class='section-title'>11. Confidentiality, Media &amp; Intellectual Property</div>";
        $html .= "<p><span class='clause-sub'>11.1 Strict Confidentiality:</span> The Employee shall not, during or after employment, disclose to any unauthorized person, "
               . "firm, or entity any confidential information regarding the Employer, Staffoo software algorithms, client lists, site security codes, CCTV footage, alarm "
               . "layouts, incident logs, or commercial pricing structures.</p>";
        $html .= "<p><span class='clause-sub'>11.2 Media &amp; Social Media Restrictions:</span> The Employee is strictly forbidden from speaking to news media, filming "
               . "site operations, or uploading videos, photographs, or commentaries regarding client sites, incidents, or security duties to social media platforms "
               . "(TikTok, Instagram, Facebook, LinkedIn, X, YouTube) without prior written authorization from the Company Director.</p>";

        $html .= "<div class='section-title'>12. Non-Solicitation &amp; Direct Dealing Restraint</div>";
        $html .= "<p><span class='clause-sub'>12.1 Non-Solicitation of Clients:</span> During employment and for a period of six (6) months following the termination of "
               . "employment for any reason, the Employee shall not directly or indirectly solicit, canvas, service, or contract with any client of the Employer to whom the "
               . "Employee provided security services on behalf of Staffoo during the preceding twelve (12) months.</p>";
        $html .= "<p><span class='clause-sub'>12.2 Restraint Justification:</span> The Employee acknowledges that this restraint is reasonable and necessary to protect the "
               . "legitimate commercial interests, customer relationships, and goodwill of the Employer.</p>";

        $html .= "<div class='section-title'>13. Deductions &amp; Set-Off Right</div>";
        $html .= "<p>In accordance with Section 324 of the Fair Work Act 2009 (Cth), the Employee expressly authorizes the Employer to deduct from their final pay or wages "
               . "any outstanding amounts lawfully owed by the Employee to the Employer, including unreturned equipment/uniform replacement costs, overpayments, or "
               . "unreturned client property, provided such deduction is authorized by law or an applicable Award provision.</p>";

        $html .= "<div class='section-title'>14. Termination of Employment &amp; Summary Dismissal</div>";
        $html .= "<p><span class='clause-sub'>14.1 Notice of Termination (Full-Time and Part-Time):</span> Either party may terminate Full-Time or Part-Time employment "
               . "by providing written notice in accordance with the NES scale set out below, or by payment in lieu of notice by the Employer:</p>";
        $html .= "<table class='notice-table'><thead><tr><th>Employee's Period of Continuous Service with Employer</th><th>Statutory Notice Period</th></tr></thead><tbody>"
               . "<tr><td>Not more than 1 year</td><td>1 week</td></tr>"
               . "<tr><td>More than 1 year but not more than 3 years</td><td>2 weeks</td></tr>"
               . "<tr><td>More than 3 years but not more than 5 years</td><td>3 weeks</td></tr>"
               . "<tr><td>More than 5 years</td><td>4 weeks</td></tr>"
               . "</tbody></table>";
        $html .= "<p class='notice-table-footnote'>*Employees aged over 45 years with at least 2 years of continuous service receive an additional 1 week of notice from "
               . "the Employer.</p>";
        $html .= "<p><span class='clause-sub'>14.2 Termination of Casual Employment:</span> Casual employment may be terminated by either party giving one (1) hour's "
               . "notice, or by the conclusion of an accepted shift assignment.</p>";
        $html .= "<p><span class='clause-sub'>14.3 Summary Dismissal for Serious Misconduct:</span> The Employer reserves the right to terminate employment immediately "
               . "without notice (or payment in lieu of notice) if the Employee engages in serious misconduct, including but not limited to:</p>";
        $html .= "<ul class='attach-list'>"
               . "<li>Theft, fraud, bribery, or dishonesty;</li>"
               . "<li>Physical assault, verbal abuse, sexual harassment, or severe bullying on shift;</li>"
               . "<li>Being under the influence of alcohol or illicit drugs while on duty;</li>"
               . "<li>Abandonment of post, sleeping on shift, or leaving a client site unattended;</li>"
               . "<li>Falsification of timesheets, GPS data, security licences, or First Aid credentials;</li>"
               . "<li>Breaching security confidentiality, broadcasting site footage, or unauthorized entry;</li>"
               . "<li>Severe breach of Work Health and Safety duties resulting in danger to life or property.</li>"
               . "</ul>";
        $html .= "<p><span class='clause-sub'>14.4 Licence Expiry / Frustration of Contract:</span> If the Employee's security licence is cancelled, suspended, or lapses, "
               . "or if an international student visa work limitation is breached, the contract of employment is immediately frustrated and terminated by operation of "
               . "law, as the Employee can no longer legally perform security work under state licensing acts.</p>";
        $html .= "<p><span class='clause-sub'>14.5 Abandonment of Employment:</span> If an Employee fails to report for three (3) consecutive rostered shifts without "
               . "notifying the Employer or providing a valid medical certificate, the Employee will be deemed to have abandoned their employment, resulting in termination "
               . "for cause.</p>";

        $html .= "<div class='section-title'>15. Governing Law &amp; Dispute Resolution</div>";
        $html .= "<p>This Agreement is governed by the laws of the State of Victoria and the Commonwealth of Australia. Any industrial dispute arising under this "
               . "Agreement or the Award shall be resolved in accordance with the dispute resolution procedures set out in Clause 9 of the Award and Section 739 of the "
               . "Fair Work Act 2009 (Cth).</p>";

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
        .clause-sub { font-weight: bold; }
        .notice-table { width:100%; border-collapse: collapse; margin: 8px 0 4px; font-size: 8.8px; page-break-inside: avoid; }
        .notice-table th { background:#0A7C6E; color:#fff; padding: 5px 8px; text-align:left; font-size: 8.6px; }
        .notice-table td { border: 1px solid #e2e8f0; padding: 5px 8px; }
        .notice-table-footnote { font-size: 8px; color:#6B7280; margin: 2px 0 10px; }
        /* The page break onto a fresh page is forced by the explicit empty
           "page-break" div placed directly in the HTML flow (see
           buildHtml()), immediately before this block -- NOT by a
           page-break-before rule here. Having both fired a break-before-
           the-div AND a break-before-this-block, which rendered an extra
           blank page in between. Only page-break-inside stays here, to
           keep the heading glued to its signature boxes. */
        .execution-block { page-break-inside: avoid; padding-top: 4px; }
        .sign-section { margin-top: 16px; width: 100%; border-collapse: separate; border-spacing: 0; page-break-inside: avoid; }
        .sign-section tr { page-break-inside: avoid; }
        /* No border on the <td> itself -- a border on a table cell that
           dompdf ends up splitting is what produces the black-fill artifact.
           The border lives on an inner div instead, so even in the worst
           case a split only loses the inner boxs visible edge, never
           paints a black bar. */
        .sign-col { width:48.5%; vertical-align:top; padding: 0; }
        .sign-box { padding: 10px; border:1px solid #d1d5db; }
        .sign-spacer { width:3%; }
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

        // Full clause text, reproduced verbatim from the Staffoo master template
        // (Ref: EMP-AGR-2026-V4) — Clauses 1-15. Clause 16 (Execution &
        // Acknowledgement) is rendered further down, right before the
        // signature section, exactly as it appears in the master.
        $html .= $this->fullClausesHtml();

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

        // Execution (Clause 16 — verbatim from the master template). Forced
        // onto its own fresh page via the explicit empty page-break div
        // below (dompdf honors this reliably, unlike a CSS page-break-before
        // rule on a block with prior siblings), then wrapped together with
        // the signature boxes in a page-break-avoid block so the heading is
        // never separated from its boxes.
        $html .= "<div style='page-break-before: always;'></div>";
        $html .= "<div class='execution-block'>";
        $html .= "<div class='section-title'>16. Execution &amp; Acknowledgement</div>";
        $html .= "<p>By signing below, the Employee acknowledges that they have read, understood, and accepted all terms, conditions, and schedules of this Employment Agreement, "
               . "including the requirement to maintain active licensing, comply with WHS policies, and utilize the Staffoo Mobile Application for shift management.</p>";

        $html .= "<table class='sign-section'><tr>"
               . "<td class='sign-col'><div class='sign-box'>{$employeeSigHtml}</div></td>"
               . "<td class='sign-spacer'></td>"
               . "<td class='sign-col'><div class='sign-box'>{$employerSigHtml}</div></td>"
               . "</tr></table>";
        $html .= "</div>";

        $html .= "<div class='footer'>Staffoo (Capital Services Pty Ltd) — ABN 48 613 317 838 — Agreement {$agreementNumber}</div>";
        $html .= "</div></body></html>";

        return $html;
    }
}