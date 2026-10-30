<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

/**
 * Admin alert sent whenever a new job (one or more shifts) is posted.
 */
class JobPostingAdminAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $emailData;

    /**
     * @param string $clientName
     * @param string $siteName
     * @param int    $shiftCount
     * @param array  $jobIds
     */
    public function __construct(string $clientName, string $siteName, int $shiftCount, array $jobIds)
    {
        $this->emailData = [
            'client_name' => $clientName,
            'site_name'   => $siteName,
            'shift_count' => $shiftCount,
            'job_ids'     => implode(', ', $jobIds),
            'posted_at'   => Carbon::now()->format('d/m/Y H:i'),
        ];
    }

    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
                    ->subject('New Job Posted – ' . $this->emailData['site_name'] . ' – Admin Alert')
                    ->view('emails.job-posting-admin-alert')
                    ->with($this->emailData);
    }
}
