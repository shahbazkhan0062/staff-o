<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmployeeAgreementSignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $recipientName;
    public $employeeName;
    public $agreementNumber;
    public $pdfBytes;
    public $attachStatutory; // bool — only true on the final, fully_executed email

    /**
     * $statutoryPaths = ['fwis' => absolute_path|null, 'ceis' => absolute_path|null]
     * Only passed (and only attached) once, on the fully-executed copy — not
     * on the "employee has signed, awaiting countersignature" notice.
     */
    public function __construct($recipientName, $employeeName, $agreementNumber, $pdfBytes, array $statutoryPaths = [])
    {
        $this->recipientName   = $recipientName;
        $this->employeeName    = $employeeName;
        $this->agreementNumber = $agreementNumber;
        $this->pdfBytes        = $pdfBytes;
        $this->attachStatutory = $statutoryPaths;
    }

    public function build()
    {
        $mail = $this->subject("Signed Employment Agreement — {$this->agreementNumber}")
            ->view('emails.employee-agreement-signed')
            ->with([
                'recipientName'   => $this->recipientName,
                'employeeName'    => $this->employeeName,
                'agreementNumber' => $this->agreementNumber,
            ])
            ->attachData($this->pdfBytes, "{$this->agreementNumber}-signed.pdf", [
                'mime' => 'application/pdf',
            ]);

        foreach ($this->attachStatutory as $label => $path) {
            if ($path && file_exists($path)) {
                $name = $label === 'ceis' ? 'Casual-Employment-Information-Statement.pdf' : 'Fair-Work-Information-Statement.pdf';
                $mail->attach($path, ['as' => $name, 'mime' => 'application/pdf']);
            }
        }

        return $mail;
    }
}
