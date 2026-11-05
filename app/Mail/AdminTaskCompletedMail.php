<?php

namespace App\Mail;

use App\Models\AdminTask;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminTaskCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AdminTask $task)
    {
    }

    public function build()
    {
        return $this->from('no-reply@staffoo.com.au', 'STAFFOO')
            ->subject('Task completed: ' . $this->task->title)
            ->view('emails.admin-task-completed', [
                'task' => $this->task,
            ]);
    }
}
