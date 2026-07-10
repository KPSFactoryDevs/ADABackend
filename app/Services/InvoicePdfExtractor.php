<?php

namespace App\Services;

use GuzzleHttp\Client;
use RuntimeException;

/**
 * Estrae i dati di una fattura da un file PDF usando OpenAI GPT-4o-mini (Vision).
 *
 * Restituisce un array con la stessa struttura di FatturaPaParser::parse():
 *   - cedente:      array (nome, piva, cf, indirizzo, citta, provincia, cap, nazione)
 *   - cessionario:  array (idem)
 *   - fatture:      array di fatture (numero, data, tipo_documento, importo_netto, importo_lordo, ...)
 */
class InvoicePdfExtractor
{
    private const TIMEOUT = 120;

    /**
     * Prompt di sistema per l'estrazione strutturata.
     */
    private const SYSTEM_PROMPT = <<<'PROMPT'
Sei un esperto contabile italiano. Ti verrà fornita un'immagine (o testo) di una fattura.
Estrai tutti i dati e rispondi SOLO con un JSON valido, senza testo aggiuntivo, con questa struttura esatta:

{
  "cedente": {
    "nome": "Ragione sociale del fornitore/cedente",
    "piva": "Partita IVA completa (es. IT01234567890)",
    "cf": "Codice Fiscale se presente, altrimenti null",
    "indirizzo": "Indirizzo sede",
    "citta": "Comune",
    "provincia": "Sigla provincia (es. MI, RM)",
    "cap": "CAP",
    "nazione": "IT"
  },
  "cessionario": {
    "nome": "Ragione sociale del cliente/cessionario",
    "piva": "Partita IVA completa",
    "cf": "Codice Fiscale se presente, altrimenti null",
    "indirizzo": "Indirizzo sede",
    "citta": "Comune",
    "provincia": "Sigla provincia",
    "cap": "CAP",
    "nazione": "IT"
  },
  "fatture": [
    {
      "numero": "Numero fattura (es. 123/2025)",
      "data": "Data fattura in formato YYYY-MM-DD",
      "tipo_documento": "TD01 per fattura, TD04 per nota di credito, TD06 per parcella, ecc.",
      "tipo_label": "Fattura, Nota di Credito, Parcella, ecc.",
      "importo_netto": 0.00,
      "importo_lordo": 0.00,
      "causale": "Descrizione/oggetto della fattura se presente, altrimenti null",
      "scadenza": "Data scadenza pagamento in formato YYYY-MM-DD, o null se non presente",
      "metodo_pagamento": "Bonifico, Contanti, RiBa, ecc. o null se non specificato",
      "linee": [
        {
          "descrizione": "Descrizione riga",
          "quantita": 1,
          "prezzo_unitario": 0.00,
          "prezzo_totale": 0.00,
          "aliquota_iva": 22.0
        }
      ]
    }
  ]
}

REGOLE IMPORTANTI:
- Se un dato non è leggibile o non presente, usa null (non stringhe vuote).
- Per la P.IVA, includi sempre il prefisso paese (es. "IT01234567890").
- L'importo_netto è l'imponibile SENZA IVA; l'importo_lordo è il TOTALE fattura CON IVA.
- Se ci sono più fatture nel documento, inserisci più elementi nell'array "fatture".
- Per tipo_documento usa i codici FatturaPA standard (TD01, TD04, TD06, ecc.).
- Rispondi SOLO con il JSON, niente altro testo prima o dopo.
PROMPT;

    /**
     * Estrai i dati da un file PDF.
     *
     * @param string $pdfPath  Percorso assoluto al file PDF
     * @return array  Stessa struttura di FatturaPaParser::parse()
     * @throws RuntimeException
     */
    public function extract(string $pdfPath): array
    {
        if (!file_exists($pdfPath)) {
            throw new RuntimeException("File PDF non trovato: {$pdfPath}");
        }

        // Strategia 1: Imagick → converte pagine in immagini
        if (extension_loaded('imagick')) {
            return $this->extractViaVision($pdfPath);
        }

        // Strategia 2: pdftotext → estrae testo e lo invia a OpenAI
        return $this->extractViaText($pdfPath);
    }

    /**
     * Estrazione tramite Vision API: converte PDF in immagini e le invia a GPT-4o-mini.
     */
    private function extractViaVision(string $pdfPath): array
    {
        $images = $this->pdfToImages($pdfPath);

        if (empty($images)) {
            // Fallback a testo se la conversione immagini fallisce
            return $this->extractViaText($pdfPath);
        }

        // Costruisci i content parts per Vision
        $contentParts = [];
        foreach ($images as $i => $base64) {
            $contentParts[] = [
                'type'      => 'image_url',
                'image_url' => [
                    'url'    => "data:image/png;base64,{$base64}",
                    'detail' => 'high',
                ],
            ];
        }

        $contentParts[] = [
            'type' => 'text',
            'text' => 'Analizza questa fattura ed estrai i dati nel formato JSON richiesto.',
        ];

        $messages = [
            ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
            ['role' => 'user',   'content' => $contentParts],
        ];

        return $this->callOpenAI($messages);
    }

    /**
     * Estrazione tramite testo: usa pdftotext e invia il testo a OpenAI.
     */
    private function extractViaText(string $pdfPath): array
    {
        $text = $this->pdfToText($pdfPath);

        if (empty(trim($text))) {
            throw new RuntimeException(
                'Impossibile estrarre testo dal PDF. ' .
                'Assicurati che il PDF contenga testo selezionabile (non solo immagini scannerizzate).'
            );
        }

        $messages = [
            ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
            [
                'role'    => 'user',
                'content' => "Ecco il testo estratto da una fattura PDF:\n\n---\n{$text}\n---\n\nEstrai i dati nel formato JSON richiesto.",
            ],
        ];

        return $this->callOpenAI($messages);
    }

    /**
     * Converte le pagine di un PDF in immagini PNG base64 usando Imagick.
     *
     * @return string[]  Array di stringhe base64 (una per pagina, max 4 pagine)
     */
    private function pdfToImages(string $pdfPath): array
    {
        $images = [];

        try {
            $imagick = new \Imagick();
            $imagick->setResolution(200, 200); // 200 DPI — buon compromesso qualità/peso
            $imagick->readImage($pdfPath);

            $pageCount = min($imagick->getNumberImages(), 4); // max 4 pagine

            for ($i = 0; $i < $pageCount; $i++) {
                $imagick->setIteratorIndex($i);
                $imagick->setImageFormat('png');
                $imagick->setImageCompressionQuality(85);

                // Ridimensiona se troppo grande (max 2000px di larghezza)
                $width = $imagick->getImageWidth();
                if ($width > 2000) {
                    $imagick->resizeImage(2000, 0, \Imagick::FILTER_LANCZOS, 1);
                }

                $images[] = base64_encode($imagick->getImageBlob());
            }

            $imagick->clear();
            $imagick->destroy();
        } catch (\Exception $e) {
            // Imagick fallito → ritorna array vuoto, si userà il fallback testo
            \Log::warning("InvoicePdfExtractor: Imagick fallito, fallback a testo", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }

        return $images;
    }

    /**
     * Estrae testo da un PDF usando pdftotext (poppler-utils).
     */
    private function pdfToText(string $pdfPath): string
    {
        $escapedPath = escapeshellarg($pdfPath);

        // Prova pdftotext (poppler-utils)
        $output = shell_exec("pdftotext -layout {$escapedPath} - 2>/dev/null");

        if ($output !== null && trim($output) !== '') {
            return $output;
        }

        // Fallback: prova con php-poppler o lettura base
        // Se niente funziona, ritorna stringa vuota
        return '';
    }

    /**
     * Chiama OpenAI e parsa la risposta JSON.
     */
    private function callOpenAI(array $messages): array
    {
        $apiKey = env('OPENAI_API_KEY');
        $model  = env('OPENAI_MODEL', 'gpt-4o-mini');

        if (!$apiKey) {
            throw new RuntimeException('OPENAI_API_KEY non configurata nel file .env');
        }

        $client = new Client([
            'base_uri' => env('OPENAI_API_BASE', 'https://api.openai.com/v1') . '/',
            'timeout'  => self::TIMEOUT,
        ]);

        $response = $client->post('chat/completions', [
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'model'       => $model,
                'messages'    => $messages,
                'temperature' => 0.1, // bassa per output deterministico
                'max_tokens'  => 4096,
            ],
        ]);

        $body = json_decode((string) $response->getBody(), true) ?: [];
        $text = $body['choices'][0]['message']['content'] ?? '';

        return $this->parseJsonResponse($text);
    }

    /**
     * Parsa e valida la risposta JSON di OpenAI.
     */
    private function parseJsonResponse(string $text): array
    {
        // Tenta parse diretto
        $decoded = json_decode($text, true);

        // Se il modello ha aggiunto testo extra, estrai il blocco JSON
        if (!is_array($decoded)) {
            // Cerca blocco ```json ... ```
            if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $text, $m)) {
                $decoded = json_decode($m[1], true);
            }
        }

        // Ultimo tentativo: cerca qualsiasi blocco JSON
        if (!is_array($decoded)) {
            if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $text, $m)) {
                $decoded = json_decode($m[0], true);
            }
        }

        if (!is_array($decoded)) {
            throw new RuntimeException(
                'OpenAI non ha restituito JSON valido per l\'estrazione della fattura PDF.'
            );
        }

        // Valida struttura minima
        return $this->normalizeExtractedData($decoded);
    }

    /**
     * Normalizza e valida i dati estratti per garantire compatibilità
     * con la struttura di FatturaPaParser.
     */
    private function normalizeExtractedData(array $data): array
    {
        $emptySoggetto = [
            'nome' => null, 'piva' => null, 'cf' => null,
            'indirizzo' => null, 'citta' => null, 'provincia' => null,
            'cap' => null, 'nazione' => 'IT',
        ];

        $cedente     = array_merge($emptySoggetto, $data['cedente'] ?? []);
        $cessionario = array_merge($emptySoggetto, $data['cessionario'] ?? []);

        // Normalizza fatture
        $fatture = [];
        foreach (($data['fatture'] ?? []) as $f) {
            $fatture[] = [
                'numero'           => $f['numero'] ?? null,
                'data'             => $f['data'] ?? null,
                'tipo_documento'   => $f['tipo_documento'] ?? 'TD01',
                'tipo_label'       => $f['tipo_label'] ?? 'Fattura',
                'importo_netto'    => round((float) ($f['importo_netto'] ?? 0), 2),
                'importo_lordo'    => round((float) ($f['importo_lordo'] ?? 0), 2),
                'causale'          => $f['causale'] ?? null,
                'scadenza'         => $f['scadenza'] ?? null,
                'metodo_pagamento' => $f['metodo_pagamento'] ?? null,
                'linee'            => $f['linee'] ?? [],
            ];
        }

        // Se nessuna fattura estratta, crea un placeholder
        if (empty($fatture)) {
            $fatture[] = [
                'numero' => null, 'data' => null, 'tipo_documento' => 'TD01',
                'tipo_label' => 'Fattura', 'importo_netto' => 0, 'importo_lordo' => 0,
                'causale' => null, 'scadenza' => null, 'metodo_pagamento' => null,
                'linee' => [],
            ];
        }

        return [
            'cedente'              => $cedente,
            'cessionario'          => $cessionario,
            'fatture'              => $fatture,
            'formato_trasmissione' => 'PDF',
            'progressivo_invio'    => '',
            'source'               => 'pdf_ai_extraction',
        ];
    }
}
