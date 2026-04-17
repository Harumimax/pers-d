<?php

namespace App\Mail;

use App\Models\AboutContactMessage as AboutContactMessageRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AboutContactMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AboutContactMessageRecord $contactMessage,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->contactMessage->contact_email)],
            subject: __('about.contact.mail.subject_line', [
                'subject' => $this->contactMessage->subject,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.about-contact-message',
        );
    }
}
