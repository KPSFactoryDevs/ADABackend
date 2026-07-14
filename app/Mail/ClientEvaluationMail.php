<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
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

    public function build()
    {
        $mail = $this->subject("Richiesta valutazione cliente — {$this->client->nome}")
                     ->view('emails.client-evaluation');

        // Allega tutti i documenti del cliente (bilancio, CR, fatture XML)
        $documents = $this->client->documents()->get();

        foreach ($documents as $doc) {
            $storagePath = $doc->path;

            if (Storage::disk('local')->exists($storagePath)) {
                $fullPath = Storage::disk('local')->path($storagePath);
                $mail->attach($fullPath, [
                    'as'   => $doc->original_filename ?: $doc->filename,
                    'mime' => $doc->mime_type ?: 'application/octet-stream',
                ]);
            }
        }

        return $mail;
    }
}
