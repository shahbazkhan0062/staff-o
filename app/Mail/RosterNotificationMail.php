<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Generic branded notification email (logo + footer) for the
 * roster-time-change flow (JobRosterController::notifyByEmail()) and any
 * other place that just needs to send a { title, message } notification
 * without a dedicated Mailable/Blade view of its own.
 */
class RosterNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $title,
        public string $message,
        public array $meta = [] // optional [ ['label' => 'Shift', 'value' => '...'], ... ]
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->title);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.roster-notification',
            with: [
                'recipientName' => $this->recipientName,
                'title'         => $this->title,
                'message'       => $this->message,
                'meta'          => $this->meta,
            ]
        );
    }
}
