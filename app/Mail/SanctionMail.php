<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SanctionMail extends Mailable
{
    use Queueable, SerializesModels;

    public Reservation $reservation;
    public string $type;

    /**
     * Create a new message instance.
     */
    public function __construct(Reservation $reservation, string $type)
    {
        $this->reservation = $reservation;
        $this->type = $type;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->type === 'no_show'
            ? "Sanción: No se presentó — {$this->reservation->date->format('d/m/Y')}"
            : "Sanción: No registró salida — {$this->reservation->date->format('d/m/Y')}";

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.sanction',
            with: [
                'reservation' => $this->reservation,
                'type' => $this->type,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
