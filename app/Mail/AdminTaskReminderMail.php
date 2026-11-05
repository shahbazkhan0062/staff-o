<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class AdminTaskReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param Collection<int, \App\Models\AdminTask> $tasks All pending tasks assigned to this admin.
     */
    public function __construct(
        public string $adminName,
        public Collection $tasks,
    ) {
    }

    public function build()
    {
        return $this->from('no-reply@staffoo.com.au', 'STAFFOO')
            ->subject('Reminder: you have ' . $this->tasks->count() . ' pending task(s)')
            ->view('emails.admin-task-reminder', [
                'adminName' => $this->adminName,
                'tasks'     => $this->tasks,
            ]);
    }
}
