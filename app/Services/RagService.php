<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * Service class to communicate with the Python RAG microservice.
 */
class RagService
{
    private Client $client;

    public function __construct()
    {
        $baseUrl = rtrim(env('RAG_SERVICE_URL', 'http://127.0.0.1:8100'), '/');

        $this->client = new Client([
            'base_uri' => $baseUrl . '/',
            'timeout'  => 120,
            'headers'  => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Ask the RAG agent a question.
     *
     * @param string     $question
     * @param int|string $companyId
     * @param array      $history   Previous messages [{role, content}, ...]
     * @param string|null $docType  Optional filter by document type
     * @return array {answer, sources, chunks_used}
     */
    public function query(string $question, $companyId, array $history = [], ?string $docType = null): array
    {
        try {
            $response = $this->client->post('query', [
                'json' => [
                    'question'   => $question,
                    'company_id' => (int) $companyId,
                    'history'    => $history,
                    'doc_type'   => $docType,
                ],
            ]);

            return json_decode((string) $response->getBody(), true) ?: [];
        } catch (RequestException $e) {
            Log::error('RagService::query failed', [
                'question' => $question,
                'error'    => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Ingest a document file into the vector store.
     *
     * @param string     $filePath     Absolute path to the file
     * @param string     $documentId   Unique identifier for the document
     * @param int|string $companyId
     * @param string     $docType      e.g. 'bilancio', 'cr', 'fattura', 'estratto_conto'
     * @param string     $filename     Original filename
     * @return array {ok, document_id, chunks_stored}
     */
    public function ingest(string $filePath, string $documentId, $companyId, string $docType = 'documento', string $filename = ''): array
    {
        try {
            $response = $this->client->post('ingest', [
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => $filename ?: basename($filePath),
                    ],
                    ['name' => 'document_id', 'contents' => (string) $documentId],
                    ['name' => 'company_id',  'contents' => (string) $companyId],
                    ['name' => 'doc_type',    'contents' => $docType],
                    ['name' => 'filename',    'contents' => $filename ?: basename($filePath)],
                ],
                // Override JSON content-type for multipart
                'headers' => ['Content-Type' => null],
            ]);

            return json_decode((string) $response->getBody(), true) ?: [];
        } catch (RequestException $e) {
            Log::error('RagService::ingest failed', [
                'document_id' => $documentId,
                'error'       => $e->getMessage(),
            ]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Ingest raw text directly (no file upload).
     *
     * @param string     $text
     * @param string     $documentId
     * @param int|string $companyId
     * @param string     $docType
     * @param string     $filename
     * @return array
     */
    public function ingestText(string $text, string $documentId, $companyId, string $docType = 'documento', string $filename = ''): array
    {
        try {
            $response = $this->client->post('ingest-text', [
                'form_params' => [
                    'text'        => $text,
                    'document_id' => (string) $documentId,
                    'company_id'  => (string) $companyId,
                    'doc_type'    => $docType,
                    'filename'    => $filename,
                ],
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            ]);

            return json_decode((string) $response->getBody(), true) ?: [];
        } catch (RequestException $e) {
            Log::error('RagService::ingestText failed', [
                'document_id' => $documentId,
                'error'       => $e->getMessage(),
            ]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a document from the vector store.
     *
     * @param string     $documentId
     * @param int|string $companyId
     * @return array {ok, chunks_deleted}
     */
    public function delete(string $documentId, $companyId): array
    {
        try {
            $response = $this->client->delete('document', [
                'query' => [
                    'document_id' => (string) $documentId,
                    'company_id'  => (string) $companyId,
                ],
            ]);

            return json_decode((string) $response->getBody(), true) ?: [];
        } catch (RequestException $e) {
            Log::error('RagService::delete failed', [
                'document_id' => $documentId,
                'error'       => $e->getMessage(),
            ]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check if the RAG service is healthy.
     *
     * @return bool
     */
    public function isHealthy(): bool
    {
        try {
            $response = $this->client->get('health');
            $data = json_decode((string) $response->getBody(), true);
            return ($data['status'] ?? '') === 'ok';
        } catch (\Throwable $e) {
            return false;
        }
    }
}
