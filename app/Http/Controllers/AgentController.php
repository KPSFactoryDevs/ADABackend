<?php

namespace App\Http\Controllers;

use App\Services\RagService;
use App\Models\Document;
use App\Helpers\Bilanci\BilanciHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AgentController extends Controller
{
    /**
     * POST /api/agent/chat
     */
    public function chat(Request $request)
    {
        $request->validate([
            'question'   => 'required|string|min:2|max:15000',
            'company_id' => 'required|integer',
            'history'    => 'nullable|array',
            'history.*.role'    => 'required_with:history|string|in:user,assistant',
            'history.*.content' => 'required_with:history|string',
            'doc_type'   => 'nullable|string',
            'page_context'        => 'nullable|array',
            'page_context.page'   => 'nullable|string',
            'page_context.summary' => 'nullable|string',
            'page_context.data'   => 'nullable',
        ]);

        $question    = trim($request->input('question'));
        $companyId   = (int) $request->input('company_id');
        $history     = $request->input('history', []);
        $docType     = $request->input('doc_type');
        $pageContext = $request->input('page_context');

        $rag = new RagService();

        if (!$rag->isHealthy()) {
            return response()->json([
                'ok'      => false,
                'message' => 'Il servizio AI non è al momento disponibile. Riprova tra qualche istante.',
            ], 503);
        }

        try {
            $result = $rag->query($question, $companyId, $history, $docType, $pageContext);

            return response()->json([
                'ok'          => true,
                'answer'      => $result['answer'] ?? '',
                'sources'     => $result['sources'] ?? [],
                'chunks_used' => $result['chunks_used'] ?? 0,
            ]);
        } catch (\Throwable $e) {
            Log::error('AgentController::chat error', [
                'question' => $question,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'Errore durante l\'elaborazione della domanda.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * POST /api/agent/ingest
     */
    public function ingestDocument(Request $request)
    {
        $request->validate([
            'file'        => 'required|file|max:20480',
            'document_id' => 'required|string',
            'company_id'  => 'required|integer',
            'doc_type'    => 'nullable|string',
        ]);

        $file       = $request->file('file');
        $documentId = $request->input('document_id');
        $companyId  = (int) $request->input('company_id');
        $docType    = $request->input('doc_type', 'documento');

        $rag = new RagService();

        $result = $rag->ingest(
            $file->getRealPath(),
            $documentId,
            $companyId,
            $docType,
            $file->getClientOriginalName()
        );

        if (!($result['ok'] ?? false)) {
            return response()->json([
                'ok'      => false,
                'message' => 'Errore durante l\'indicizzazione del documento.',
                'error'   => $result['error'] ?? null,
            ], 500);
        }

        return response()->json($result);
    }

    /**
     * DELETE /api/agent/document/{id}
     */
    public function deleteDocument(Request $request, $id)
    {
        $companyId = (int) $request->input('company_id', $request->header('CurrentCompany', 0));

        if (!$companyId) {
            return response()->json(['ok' => false, 'message' => 'company_id is required'], 422);
        }

        $rag    = new RagService();
        $result = $rag->delete((string) $id, $companyId);

        return response()->json($result);
    }

    /**
     * POST /api/agent/reindex
     *
     * Re-index all existing documents (bilanci + CR) for a company.
     * Useful for bootstrapping the vector store with pre-existing data.
     */
    public function reindex(Request $request)
    {
        $companyId = (int) ($request->input('company_id') ?: $request->header('CurrentCompany', 0));
        if (!$companyId) {
            return response()->json(['ok' => false, 'message' => 'company_id is required'], 422);
        }

        $rag = new RagService();
        $indexed = [];
        $errors = [];

        // ── Bilanci ──
        $bilanci = Document::where('company_id', $companyId)
            ->where('type', 'bilancio')
            ->get();

        foreach ($bilanci as $doc) {
            try {
                global $use_xbrl_functions;
                $use_xbrl_functions = true;

                $filePath = base_path() . '/public/bilanci/' . $doc->filename;
                if (!file_exists($filePath)) {
                    $errors[] = ['type' => 'bilancio', 'id' => $doc->id, 'error' => 'File not found'];
                    continue;
                }

                $bilanciHelper = new BilanciHelper();
                $taxonomyPath = base_path() . "/taxonomies/2018-11-04/" . $doc->taxonomy;

                $emptyInstance = false;
                $readXBRL = \XBRL_Instance::FromInstanceDocument($filePath, $taxonomyPath, $emptyInstance);
                if (!$readXBRL) {
                    $errors[] = ['type' => 'bilancio', 'id' => $doc->id, 'error' => 'XBRL parse failed'];
                    continue;
                }

                $bilancioJSON = $readXBRL->toJSON();
                $nomeAzienda = $doc->nome_azienda ?? 'N/D';
                $period = ['anno_inizio' => $doc->anno_inizio, 'anno_fine' => $doc->anno_fine];

                $bilancioAnalisi = $bilanciHelper->getIndexesForBalanceTaxonomy(
                    $doc->id, $filePath, $readXBRL, $doc->codice_documento, null
                );

                $bilController = new \App\Financial\Bilanci\Controllers\BilanciController();
                $ragText = "BILANCIO - {$nomeAzienda}\n"
                    . "Periodo: " . json_encode($period) . "\n"
                    . "Analisi Indici: " . json_encode($bilancioAnalisi, JSON_UNESCAPED_UNICODE) . "\n"
                    . "Voci di Bilancio: " . json_encode(
                        $bilController->cleanBilancioData($bilancioJSON),
                        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
                    );

                $rag->ingestText(
                    $ragText,
                    'bilancio_' . $doc->id,
                    $companyId,
                    'bilancio',
                    'Bilancio ' . $nomeAzienda . ' ' . ($period['anno_fine'] ?? ''),
                    true // structured=true for larger chunks
                );

                $indexed[] = ['type' => 'bilancio', 'id' => $doc->id, 'name' => $nomeAzienda];
            } catch (\Throwable $e) {
                $errors[] = ['type' => 'bilancio', 'id' => $doc->id, 'error' => $e->getMessage()];
            }
        }

        // ── Centrale Rischi (PDF) ──
        $crDocs = Document::where('company_id', $companyId)
            ->where('type', 'centrale rischi')
            ->get();

        foreach ($crDocs as $doc) {
            try {
                $filePath = $doc->path ?: base_path() . '/public/crdocument/' . $doc->filename;
                if (!file_exists($filePath)) {
                    $errors[] = ['type' => 'centrale_rischi', 'id' => $doc->id, 'error' => 'File not found'];
                    continue;
                }

                $rag->ingest(
                    $filePath,
                    'cr_' . $doc->id,
                    $companyId,
                    'centrale_rischi',
                    'Centrale Rischi ' . $doc->filename
                );

                $indexed[] = ['type' => 'centrale_rischi', 'id' => $doc->id, 'name' => $doc->filename];
            } catch (\Throwable $e) {
                $errors[] = ['type' => 'centrale_rischi', 'id' => $doc->id, 'error' => $e->getMessage()];
            }
        }

        return response()->json([
            'ok'      => true,
            'indexed' => count($indexed),
            'errors'  => count($errors),
            'details' => ['indexed' => $indexed, 'errors' => $errors],
        ]);
    }

    /**
     * GET /api/agent/health
     */
    public function health()
    {
        $rag = new RagService();
        $healthy = $rag->isHealthy();

        return response()->json([
            'ok'      => $healthy,
            'service' => 'rag',
            'status'  => $healthy ? 'up' : 'down',
        ], $healthy ? 200 : 503);
    }
}
