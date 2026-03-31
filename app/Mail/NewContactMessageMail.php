<?php

namespace App\Mail;

use App\Filament\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Versand synchron (ohne ShouldQueue), damit die Admin-Mail direkt nach Absenden rausgeht.
 */
class NewContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contactMessage) {}

    public function envelope(): Envelope
    {
        $sub = $this->contactMessage->subject instanceof \BackedEnum
            ? $this->contactMessage->subject->label()
            : (string) $this->contactMessage->subject;

        return new Envelope(
            subject: '[Mochi Kontakt] '.$sub.' — '.$this->contactMessage->name,
            replyTo: [
                $this->contactMessage->email => $this->contactMessage->name,
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact.notify',
            with: [
                'record' => $this->contactMessage,
                'adminUrl' => ContactMessageResource::getUrl('view', ['record' => $this->contactMessage]),
            ],
        );
    }
}
