<?php

namespace App\Http\Controllers;

use App\Mail\ClientEvaluationMail;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\Company;
use App\Models\Invoice;
use App\Services\FatturaPaParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FactoringController extends Controller
{
    /**
     * GET /factoring/clients
     * Lista clienti con score, flag documenti e conteggio fatture.
     */
    public function clientsIndex(Request $request)
    {
        $userId    = Auth::guard('api')->id();
        $companyId = (int) $request->header('CurrentCompany');

        if (!$companyId) {
            return response()->json(['message' => 'Missing CurrentCompany header'], 422);
        }

        $clients = Client::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('is_customer', true)
            ->withCount(['invoices', 'documents'])
            ->orderBy('nome')
            ->get()
            ->map(function (Client $c) {
                return [
                    'id'                 => $c->id,
                    'nome'               => $c->nome,
                    'piva'               => $c->piva,
                    'regione'            => $c->regione,
                    'citta'              => $c->citta,
                    'email'              => $c->email,
                    'fatturato'          => $c->fatturato,
                    'has_invoices'       => $c->has_invoices,
                    'has_bilancio'       => $c->has_bilancio,
                    'has_cr'             => $c->has_cr,
                    'bilancio_score'     => $c->bilancio_score,
                    'cr_score'           => $c->cr_score,
                    'evaluation_status'  => $c->evaluation_status,
                    'evaluation_sent_at' => $c->evaluation_sent_at,
                    'invoices_count'     => $c->invoices_count,
                    'documents_count'    => $c->documents_count,
                ];
            });

        return response()->json($clients);
    }

    /**
     * POST /factoring/invoices/upload-xml
     * Upload multiplo di XML FatturaPA → parsing → creazione fatture + clienti.
     */
    public function uploadInvoiceXml(Request $request, FatturaPaParser $parser)
    {
        $request->validate([
            'files'   => 'required|array|min:1',
            'files.*' => 'required|file|max:10240', // max 10MB each
        ]);

        $userId    = Auth::guard('api')->id();
        $companyId = (int) $request->header('CurrentCompany');

        if (!$companyId) {
            return response()->json(['message' => 'Missing CurrentCompany header'], 422);
        }

        $results = [];
        $errors  = [];

        foreach ($request->file('files') as $file) {
            try {
                $xmlContent = file_get_contents($file->getRealPath());
                $parsed     = $parser->parse($xmlContent);

                DB::transaction(function () use ($parsed, $userId, $companyId, $file, &$results) {
                    // Il cessionario/committente è il cliente a cui è indirizzata la fattura
                    $clientData = $parsed['cessionario'];

                    // Cerca o crea il cliente
                    $client = $this->findOrCreateClient($clientData, $userId, $companyId);

                    // Salva le fatture
                    $savedInvoices = [];
                    foreach ($parsed['fatture'] as $fattura) {
                        $invoice = Invoice::updateOrCreate(
                            [
                                'company_id' => $companyId,
                                'direction'  => 'issued',
                                'number'     => $fattura['numero'],
                                'date'       => $fattura['data'],
                                'client_vat' => $clientData['piva'],
                            ],
                            [
                                'user_id'         => $userId,
                                'client_id'       => $client->id,
                                'document_number' => $fattura['numero'],
                                'client_name'     => $clientData['nome'],
                                'amount_net'      => $fattura['importo_netto'],
                                'amount_gross'    => $fattura['importo_lordo'],
                                'due_date'        => $fattura['scadenza'] ?: null,
                                'payment_method'  => $fattura['metodo_pagamento'],
                                'status'          => 'Esigibile',
                            ]
                        );
                        $savedInvoices[] = $invoice;
                    }

                    // Salva il file XML come documento del cliente
                    $storedPath = $file->store("client_documents/{$client->id}", 'local');

                    ClientDocument::create([
                        'client_id'         => $client->id,
                        'user_id'           => $userId,
                        'company_id'        => $companyId,
                        'type'              => 'fattura_xml',
                        'filename'          => basename($storedPath),
                        'original_filename' => $file->getClientOriginalName(),
                        'path'              => $storedPath,
                        'mime_type'         => $file->getMimeType() ?: 'text/xml',
                        'size'              => $file->getSize(),
                        'extracted_data'    => $parsed,
                    ]);

                    // Aggiorna flag
                    $client->update(['has_invoices' => true]);

                    $results[] = [
                        'file'           => $file->getClientOriginalName(),
                        'client'         => $client->nome,
                        'client_id'      => $client->id,
                        'client_piva'    => $client->piva,
                        'invoices_count' => count($savedInvoices),
                        'cedente'        => $parsed['cedente']['nome'] ?? null,
                    ];
                });
            } catch (\Exception $e) {
                $errors[] = [
                    'file'    => $file->getClientOriginalName(),
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success'  => count($results),
            'errors'   => count($errors),
            'results'  => $results,
            'failures' => $errors,
        ]);
    }

    /**
     * POST /factoring/clients/{client}/documents
     * Upload bilancio o CR per un cliente specifico.
     */
    public function uploadDocument(Request $request, Client $client)
    {
        $request->validate([
            'file' => 'required|file|max:20480',       // max 20MB
            'type' => 'required|in:bilancio,cr',
        ]);

        $userId    = Auth::guard('api')->id();
        $companyId = (int) $request->header('CurrentCompany');

        // Verifica ownership
        abort_unless($client->user_id === $userId, 403, 'Non autorizzato');
        if ($companyId) {
            abort_unless((int) $client->company_id === $companyId, 403, 'Azienda non coerente');
        }

        $file = $request->file('file');
        $type = $request->input('type');

        // Store file
        $storedPath = $file->store("client_documents/{$client->id}", 'local');

        $doc = ClientDocument::create([
            'client_id'         => $client->id,
            'user_id'           => $userId,
            'company_id'        => $client->company_id,
            'type'              => $type,
            'filename'          => basename($storedPath),
            'original_filename' => $file->getClientOriginalName(),
            'path'              => $storedPath,
            'mime_type'         => $file->getMimeType(),
            'size'              => $file->getSize(),
        ]);

        // Aggiorna flag e score (lo score per ora mock, in futuro verrà dal parsing)
        $updateData = [];
        if ($type === 'bilancio') {
            $updateData['has_bilancio'] = true;
            // Score placeholder — in produzione calcolato dal parser bilancio
            if (!$client->bilancio_score) {
                $updateData['bilancio_score'] = null;
            }
        } elseif ($type === 'cr') {
            $updateData['has_cr'] = true;
            if (!$client->cr_score) {
                $updateData['cr_score'] = null;
            }
        }

        if ($updateData) {
            $client->update($updateData);
        }

        return response()->json([
            'message'  => 'Documento caricato',
            'document' => $doc,
            'client'   => $client->fresh(),
        ]);
    }

    /**
     * POST /factoring/clients/{client}/evaluate
     * Invia email di valutazione a f.megna@kpsfactory.com con documenti allegati.
     */
    public function sendForEvaluation(Request $request, Client $client)
    {
        $userId    = Auth::guard('api')->id();
        $companyId = (int) $request->header('CurrentCompany');

        // Verifica ownership
        abort_unless($client->user_id === $userId, 403, 'Non autorizzato');
        if ($companyId) {
            abort_unless((int) $client->company_id === $companyId, 403, 'Azienda non coerente');
        }

        $notes = $request->input('notes', '');

        // Nome azienda dell'utente
        $company = Company::find($client->company_id);
        $companyName = $company ? $company->ragione_sociale : 'N/D';

        // Carica relazione documenti
        $client->load('documents');

        // Invia mail
        Mail::to('f.megna@kpsfactory.com')
            ->send(new ClientEvaluationMail($client, $notes, $companyName));

        // Aggiorna stato
        $client->update([
            'evaluation_status'  => 'pending',
            'evaluation_sent_at' => now(),
        ]);

        return response()->json([
            'message' => 'Valutazione inviata con successo',
            'client'  => $client->fresh(),
        ]);
    }

    /* ========================================================================
     * HELPERS
     * ======================================================================== */

    /**
     * Trova un client esistente per P.IVA nella stessa company, oppure lo crea.
     */
    private function findOrCreateClient(array $soggetto, int $userId, int $companyId): Client
    {
        $piva = $soggetto['piva'] ?? $soggetto['cf'] ?? null;
        $nome = $soggetto['nome'] ?? 'Cliente sconosciuto';

        if ($piva) {
            $client = Client::where('company_id', $companyId)
                ->where('piva', $piva)
                ->first();

            if ($client) {
                // Aggiorna dati se più recenti
                $client->update(array_filter([
                    'nome'    => $nome,
                    'citta'   => $soggetto['citta'] ?? $client->citta,
                    'regione' => $this->provinciaToRegione($soggetto['provincia'] ?? '') ?: $client->regione,
                ]));
                return $client;
            }
        }

        return Client::create([
            'company_id'  => $companyId,
            'user_id'     => $userId,
            'nome'        => $nome,
            'piva'        => $piva ?: 'ND-' . Str::random(8),
            'vat_number'  => $piva,
            'citta'       => $soggetto['citta'] ?? null,
            'regione'     => $this->provinciaToRegione($soggetto['provincia'] ?? ''),
            'email'       => null,
            'is_customer' => true,
            'is_supplier' => false,
            'status'      => 'non_approvato',
        ]);
    }

    /**
     * Mappa (basilare) sigla provincia → regione.
     */
    private function provinciaToRegione(string $prov): ?string
    {
        $map = [
            // Lombardia
            'MI' => 'Lombardia', 'MB' => 'Lombardia', 'BG' => 'Lombardia', 'BS' => 'Lombardia',
            'CO' => 'Lombardia', 'CR' => 'Lombardia', 'LC' => 'Lombardia', 'LO' => 'Lombardia',
            'MN' => 'Lombardia', 'PV' => 'Lombardia', 'SO' => 'Lombardia', 'VA' => 'Lombardia',
            // Lazio
            'RM' => 'Lazio', 'FR' => 'Lazio', 'LT' => 'Lazio', 'RI' => 'Lazio', 'VT' => 'Lazio',
            // Piemonte
            'TO' => 'Piemonte', 'AL' => 'Piemonte', 'AT' => 'Piemonte', 'BI' => 'Piemonte',
            'CN' => 'Piemonte', 'NO' => 'Piemonte', 'VB' => 'Piemonte', 'VC' => 'Piemonte',
            // Veneto
            'VE' => 'Veneto', 'BL' => 'Veneto', 'PD' => 'Veneto', 'RO' => 'Veneto',
            'TV' => 'Veneto', 'VI' => 'Veneto', 'VR' => 'Veneto',
            // Emilia-Romagna
            'BO' => 'Emilia-Romagna', 'FE' => 'Emilia-Romagna', 'FC' => 'Emilia-Romagna',
            'MO' => 'Emilia-Romagna', 'PC' => 'Emilia-Romagna', 'PR' => 'Emilia-Romagna',
            'RA' => 'Emilia-Romagna', 'RE' => 'Emilia-Romagna', 'RN' => 'Emilia-Romagna',
            // Toscana
            'FI' => 'Toscana', 'AR' => 'Toscana', 'GR' => 'Toscana', 'LI' => 'Toscana',
            'LU' => 'Toscana', 'MS' => 'Toscana', 'PI' => 'Toscana', 'PO' => 'Toscana',
            'PT' => 'Toscana', 'SI' => 'Toscana',
            // Campania
            'NA' => 'Campania', 'AV' => 'Campania', 'BN' => 'Campania', 'CE' => 'Campania', 'SA' => 'Campania',
            // Puglia
            'BA' => 'Puglia', 'BT' => 'Puglia', 'BR' => 'Puglia', 'FG' => 'Puglia', 'LE' => 'Puglia', 'TA' => 'Puglia',
            // Sicilia
            'PA' => 'Sicilia', 'AG' => 'Sicilia', 'CL' => 'Sicilia', 'CT' => 'Sicilia',
            'EN' => 'Sicilia', 'ME' => 'Sicilia', 'RG' => 'Sicilia', 'SR' => 'Sicilia', 'TP' => 'Sicilia',
            // Sardegna
            'CA' => 'Sardegna', 'NU' => 'Sardegna', 'OR' => 'Sardegna', 'SS' => 'Sardegna', 'SU' => 'Sardegna',
            // Altre
            'GE' => 'Liguria', 'IM' => 'Liguria', 'SP' => 'Liguria', 'SV' => 'Liguria',
            'AO' => "Valle d'Aosta",
            'TN' => 'Trentino-Alto Adige', 'BZ' => 'Trentino-Alto Adige',
            'TS' => 'Friuli Venezia Giulia', 'GO' => 'Friuli Venezia Giulia', 'PN' => 'Friuli Venezia Giulia', 'UD' => 'Friuli Venezia Giulia',
            'PG' => 'Umbria', 'TR' => 'Umbria',
            'AN' => 'Marche', 'AP' => 'Marche', 'FM' => 'Marche', 'MC' => 'Marche', 'PU' => 'Marche',
            'AQ' => 'Abruzzo', 'CH' => 'Abruzzo', 'PE' => 'Abruzzo', 'TE' => 'Abruzzo',
            'CB' => 'Molise', 'IS' => 'Molise',
            'CZ' => 'Calabria', 'CS' => 'Calabria', 'KR' => 'Calabria', 'RC' => 'Calabria', 'VV' => 'Calabria',
            'MT' => 'Basilicata', 'PZ' => 'Basilicata',
        ];

        $prov = strtoupper(trim($prov));
        return $map[$prov] ?? null;
    }
}
