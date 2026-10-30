<?php

namespace App\Mail;

use App\Models\JobRoster;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

/**
 * Sent to the contractor (job->accepted_by) or the staff member
 * (job->assigned_to) when a job they were on is cancelled.
 */
class JobCancellationNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $job;
    public $recipientName;
    public $roleLabel;
    public $emailData;

    /**
     * @param JobRoster $job          The cancelled job roster row.
     * @param string    $recipientName Name of the contractor/staff member.
     * @param string    $roleLabel    'resource partner' or 'staff member' — used
     *                                in the body copy so wording fits whoever
     *                                is being emailed.
     */
    public function __construct(JobRoster $job, string $recipientName, string $roleLabel = 'resource partner')
    {
        $this->job            = $job;
        $this->recipientName  = $recipientName;
        $this->roleLabel      = $roleLabel;

        $start = $job->start ? Carbon::parse($job->start) : null;
        $end   = $job->end ? Carbon::parse($job->end) : null;

        $this->emailData = [
            'recipient_name' => $recipientName,
            'role_label'     => $roleLabel,
            'job_id'         => $job->id,
            'site_name'      => $job->site->site_name ?? ($job->site->address ?? 'the assigned site'),
            'date'           => $start ? $start->format('d M Y') : 'N/A',
            'start_time'     => $start ? $start->format('g:i A') : 'N/A',
            'end_time'       => $end ? $end->format('g:i A') : 'N/A',
        ];
    }

    public function build()
    {
        $subject = sprintf(
            'Job Cancellation Notice – %s to %s Assignment',
            $this->emailData['start_time'],
            $this->emailData['end_time']
        );

        return $this->from(config('mail.from.address'), config('mail.from.name'))
                    ->subject($subject)
                    ->view('emails.job-cancellation-notice')
                    ->with($this->emailData);
    }
}
