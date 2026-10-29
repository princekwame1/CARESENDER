<?php

namespace App\Mail;

use App\Models\ContactMessage;
use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** The team's answer to a message sent from the contact page. Sent straight away so the sender sees whether it went. */
class ContactReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contactMessage) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Re: your message to '.Branding::name());
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.contact-reply');
    }
}
