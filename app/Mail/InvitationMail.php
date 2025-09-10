<?php

namespace App\Mail;

use App\Models\Collection;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Collection $collection,
        public string $token,
        public bool $is_new_user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Convite para coleção: ' . $this->collection->name,
        );
    }

    public function content(): Content
    {
        $url = $this->is_new_user
            ? "/register-and-accept-invitation-route?token={$this->token}"
            : "/accept-invitation-route?token={$this->token}";

        return new Content(
            view: 'emails.invitation',
            with: [
                'collection' => $this->collection,
                'url' => $url,
                'is_new_user' => $this->is_new_user,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
