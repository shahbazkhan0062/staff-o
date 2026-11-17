<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmployeeAgreementSignatureRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $employeeName;
    public $agreementNumber;
    public $signingLink;

    public function __construct($employeeName, $agreementNumber, $signingLink)
    {
        $this->employeeName    = $employeeName;
        $this->agreementNumber = $agreementNumber;
        $this->signingLink     = $signingLink;
    }

    public function build()
    {
        return $this->subject("Action Required: Sign Your Employment Agreement ({$this->agreementNumber})")
            ->view('emails.employee-agreement-signature-request')
            ->with([
                'employeeName'    => $this->employeeName,
                'agreementNumber' => $this->agreementNumber,
                'signingLink'     => $this->signingLink,
            ]);
    }
}
