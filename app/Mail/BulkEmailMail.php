<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BulkEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $emailSubject;
    public string $htmlContent;
    public array $attachmentPaths;

    /**
     * Create a new message instance.
     */
    public function __construct(string $subject, string $htmlContent, array $attachmentPaths = [])
    {
        $this->emailSubject = $subject;
        $this->htmlContent = $htmlContent;
        $this->attachmentPaths = $attachmentPaths;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.bulk-email',
            with: [
                'htmlContent' => $this->htmlContent,
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
        $attachments = [];

        foreach ($this->attachmentPaths as $path) {
            if (file_exists($path)) {
                $attachments[] = Attachment::fromPath($path);
            }
        }

        return $attachments;
    }
}

