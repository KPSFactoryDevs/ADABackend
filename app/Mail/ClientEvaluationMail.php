<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ClientEvaluationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Client $client;
    public string $notes;
    public string $companyName;

    public function __construct(Client $client, string $notes = '', string $companyName = '')
    {
        $this->client      = $client;
        $this->notes       = $notes;
        $this->companyName = $companyName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Richiesta valutazione cliente — {$this->client->nome}",
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.client-evaluation',
        );
    }

    /**
     * Allega tutti i documenti del cliente (bilancio, CR, fatture XML).
     */
    public function attachments(): array
    {
        $attachments = [];

        $documents = $this->client->documents()->get();

        foreach ($documents as $doc) {
            $storagePath = $doc->path;

            if (Storage::disk('local')->exists($storagePath)) {
                $attachments[] = Attachment::fromStorage($storagePath)
                    ->as($doc->original_filename ?: $doc->filename)
                    ->withMime($doc->mime_type ?: 'application/octet-stream');
            }
        }

        return $attachments;
    }
}
