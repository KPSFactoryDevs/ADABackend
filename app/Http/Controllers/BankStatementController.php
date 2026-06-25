<?php

namespace App\Http\Controllers;

use App\Models\BankStatement;
use App\Services\BankStatementAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BankStatementController extends Controller
{
    /**
     * Lista estratti conto per l'azienda corrente.
     */
    public function index(Request $request)
    {
        $companyId = $request->header('CurrentCompany') ?: $request->query('company_id');

        $statements = BankStatement::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($s) => [
                'id'              => $s->id,
                'company_id'      => $s->company_id,
                'filename'        => $s->filename,
                'bank_name'       => $s->bank_name,
                'iban'            => $s->iban,
                'fido_accordato'  => $s->fido_accordato,
                'date_from'       => $s->date_from?->format('Y-m-d'),
                'date_to'         => $s->date_to?->format('Y-m-d'),
                'status'          => $s->status,
                'error_message'   => $s->error_message,
                'entries_count'   => $s->entries()->count(),
                'created_at'      => $s->created_at?->toISOString(),
                'updated_at'      => $s->updated_at?->toISOString(),
            ]);

        return response()->json(['data' => $statements]);
    }

    /**
     * Upload di un nuovo estratto conto PDF.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:51200', // max 50MB
        ]);

        $companyId = $request->header('CurrentCompany') ?: $request->input('company_id', 0);
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();

        // Salva il file su disco
        $disk = Storage::disk('estratticonto');
        $storedName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $disk->putFileAs('', $file, $storedName);

        // Crea il record
        $statement = BankStatement::create([
            'company_id' => (int) $companyId,
            'filename'   => $originalName,
            'filepath'   => $storedName,
            'status'     => 'uploaded',
        ]);

        return response()->json([
            'message' => 'Estratto conto caricato con successo.',
            'data'    => [
                'id'       => $statement->id,
                'filename' => $statement->filename,
                'status'   => $statement->status,
            ],
        ], 201);
    }

    /**
     * Lancia l'analisi del PDF (parsing + calcoli).
     */
    public function analyze(Request $request, int $id)
    {
        $statement = BankStatement::findOrFail($id);

        $analyzer = new BankStatementAnalyzer();
        $analysis = $analyzer->analyze($statement);

        // Ricarica per avere i dati aggiornati
        $statement->refresh();

        return response()->json([
            'message'  => 'Analisi completata.',
            'data'     => [
                'id'              => $statement->id,
                'filename'        => $statement->filename,
                'bank_name'       => $statement->bank_name,
                'iban'            => $statement->iban,
                'fido_accordato'  => $statement->fido_accordato,
                'date_from'       => $statement->date_from?->format('Y-m-d'),
                'date_to'         => $statement->date_to?->format('Y-m-d'),
                'status'          => $statement->status,
                'analysis'        => $analysis,
            ],
        ]);
    }

    /**
     * Dettaglio estratto conto con dati analisi.
     */
    public function show(Request $request, int $id)
    {
        $statement = BankStatement::findOrFail($id);

        $entries = $statement->entries()
            ->orderBy('date_operazione')
            ->get()
            ->map(fn($e) => [
                'id'              => $e->id,
                'date_operazione' => $e->date_operazione?->format('Y-m-d'),
                'date_valuta'     => $e->date_valuta?->format('Y-m-d'),
                'descrizione'     => $e->descrizione,
                'dare'            => $e->dare,
                'avere'           => $e->avere,
                'saldo'           => $e->saldo,
            ]);

        return response()->json([
            'id'              => $statement->id,
            'company_id'      => $statement->company_id,
            'filename'        => $statement->filename,
            'bank_name'       => $statement->bank_name,
            'iban'            => $statement->iban,
            'fido_accordato'  => $statement->fido_accordato,
            'date_from'       => $statement->date_from?->format('Y-m-d'),
            'date_to'         => $statement->date_to?->format('Y-m-d'),
            'status'          => $statement->status,
            'error_message'   => $statement->error_message,
            'analysis'        => $statement->analysis_data,
            'entries'         => $entries,
            'created_at'      => $statement->created_at?->toISOString(),
        ]);
    }

    /**
     * Aggiorna dati dell'estratto conto (es. fido manuale).
     */
    public function update(Request $request, int $id)
    {
        $statement = BankStatement::findOrFail($id);

        $data = $request->only(['fido_accordato', 'bank_name', 'iban']);
        $statement->update(array_filter($data, fn($v) => $v !== null));

        // Se è stato aggiornato il fido, ricalcola l'analisi
        if ($request->has('fido_accordato') && $statement->status === 'completed') {
            $analyzer = new BankStatementAnalyzer();
            $analysis = $analyzer->analyze($statement);
        }

        $statement->refresh();

        return response()->json([
            'message' => 'Estratto conto aggiornato.',
            'data'    => [
                'id'             => $statement->id,
                'fido_accordato' => $statement->fido_accordato,
                'status'         => $statement->status,
            ],
        ]);
    }

    /**
     * Elimina un estratto conto e i suoi movimenti.
     */
    public function destroy(int $id)
    {
        $statement = BankStatement::findOrFail($id);

        // Elimina file dal disco
        $disk = Storage::disk('estratticonto');
        if ($statement->filepath && $disk->exists($statement->filepath)) {
            $disk->delete($statement->filepath);
        }

        $statement->delete(); // cascade elimina anche le entries

        return response()->json(['message' => 'Estratto conto eliminato.']);
    }
}
