<?php

namespace App\Http\Controllers;

use App\Services\RagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AgentController extends Controller
{
    /**
     * POST /api/agent/chat
     *
     * Receives a user question, forwards it to the RAG microservice,
     * and returns the AI-generated answer with sources.
     */
    public function chat(Request $request)
    {
        $request->validate([
            'question'   => 'required|string|min:2|max:2000',
            'company_id' => 'required|integer',
            'history'    => 'nullable|array',
            'history.*.role'    => 'required_with:history|string|in:user,assistant',
            'history.*.content' => 'required_with:history|string',
            'doc_type'   => 'nullable|string',
        ]);

        $question  = trim($request->input('question'));
        $companyId = (int) $request->input('company_id');
        $history   = $request->input('history', []);
        $docType   = $request->input('doc_type');

        $rag = new RagService();

        // Check service availability
        if (!$rag->isHealthy()) {
            return response()->json([
                'ok'      => false,
                'message' => 'Il servizio AI non è al momento disponibile. Riprova tra qualche istante.',
            ], 503);
        }

        try {
            $result = $rag->query($question, $companyId, $history, $docType);

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
     *
     * Manually trigger document ingestion into the vector store.
     */
    public function ingestDocument(Request $request)
    {
        $request->validate([
            'file'        => 'required|file|max:20480', // max 20MB
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
     *
     * Remove a document from the vector store.
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
     * GET /api/agent/health
     *
     * Check the RAG service status.
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
