<?php

namespace App\Mail;

use App\Models\AdminTaskComment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminTaskCommentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AdminTaskComment $comment)
    {
    }

    public function build()
    {
        return $this->from('no-reply@staffoo.com.au', 'STAFFOO')
            ->subject('New comment on task: ' . $this->comment->task->title)
            ->view('emails.admin-task-comment', [
                'comment' => $this->comment,
                'task'    => $this->comment->task,
            ]);
    }
}
