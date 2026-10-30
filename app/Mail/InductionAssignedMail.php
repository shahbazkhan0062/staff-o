<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InductionAssignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $userName;

    /** @var string[] */
    public array $inductionTitles;

    /**
     * @param \App\Models\User $user
     * @param \App\Models\Questionnaire[]|array $inductions
     */
    public function __construct($user, array $inductions)
    {
        $this->userName = $user->name;
        $this->inductionTitles = collect($inductions)->pluck('title')->filter()->values()->all();
    }

    public function build()
    {
        $count = count($this->inductionTitles);

        return $this->from('no-reply@staffoo.com.au', 'STAFFOO')
            ->subject($count > 1 ? 'New Inductions Assigned to You' : 'New Induction Assigned to You')
            ->view('emails.induction-assigned')
            ->with([
                'userName' => $this->userName,
                'inductionTitles' => $this->inductionTitles,
            ]);
    }
}
