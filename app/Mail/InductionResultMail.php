<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InductionResultMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $userName;
    public string $title;
    public int $marks;
    public bool $passed;
    public ?string $certificateUrl;

    /**
     * @param string $userName
     * @param string $title induction/questionnaire title
     * @param int $marks score out of 100
     * @param bool $passed marks >= 80
     * @param string|null $certificateUrl only set when $passed is true
     */
    public function __construct(string $userName, string $title, int $marks, bool $passed, ?string $certificateUrl = null)
    {
        $this->userName = $userName;
        $this->title = $title;
        $this->marks = $marks;
        $this->passed = $passed;
        $this->certificateUrl = $certificateUrl;
    }

    public function build()
    {
        return $this->from('no-reply@staffoo.com.au', 'STAFFOO')
            ->subject($this->passed
                ? 'Congratulations! You passed - ' . $this->title
                : 'Induction Not Passed - You Can Try Again - ' . $this->title)
            ->view('emails.induction-result')
            ->with([
                'userName' => $this->userName,
                'title' => $this->title,
                'marks' => $this->marks,
                'passed' => $this->passed,
                'certificateUrl' => $this->certificateUrl,
            ]);
    }
}
