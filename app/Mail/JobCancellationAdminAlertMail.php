<?php

namespace App\Mail;

use App\Models\JobRoster;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

/**
 * Admin alert sent whenever a job is cancelled, regardless of which
 * cancellation path (admin delete / no-fee / fee-applied) triggered it.
 */
class JobCancellationAdminAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $job;
    public $emailData;

    public function __construct(JobRoster $job, ?string $clientName, ?string $contractorName, ?string $staffName, ?string $cancelledBy = null)
    {
        $this->job = $job;

        $start = $job->start ? Carbon::parse($job->start) : null;
        $end   = $job->end ? Carbon::parse($job->end) : null;

        $this->emailData = [
            'job_id'           => $job->id,
            'site_name'        => $job->site->site_name ?? ($job->site->address ?? 'Unknown site'),
            'date'             => $start ? $start->format('d M Y') : 'N/A',
            'start_time'       => $start ? $start->format('g:i A') : 'N/A',
            'end_time'         => $end ? $end->format('g:i A') : 'N/A',
            'client_name'      => $clientName ?? 'Unknown client',
            'contractor_name'  => $contractorName,
            'staff_name'       => $staffName,
            'cancelled_by'     => $cancelledBy ?? 'System',
            'cancelled_at'     => Carbon::now()->format('d/m/Y H:i'),
        ];
    }

    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
                    ->subject('Job Cancelled – #' . $this->emailData['job_id'] . ' – Admin Alert')
                    ->view('emails.job-cancellation-admin-alert')
                    ->with($this->emailData);
    }
}
