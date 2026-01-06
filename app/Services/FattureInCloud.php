<?php
// app/Services/FattureInCloud.php

namespace App\Services;

use GuzzleHttp\Client;
use Carbon\Carbon;

class FattureInCloud
{
    private Client $http;
    private string $base;
    private string $clientId;
    private string $clientSecret;
    private string $redirect;
    private string $scopes;

    public function __construct()
    {
        $this->base         = rtrim(config('services.fic.base', env('FIC_BASE_URL', 'https://api-v2.fattureincloud.it')), '/');
        $this->clientId     = config('services.fic.client_id', env('FIC_CLIENT_ID'));
        $this->clientSecret = config('services.fic.client_secret', env('FIC_CLIENT_SECRET'));
        $this->redirect     = config('services.fic.redirect', env('FIC_REDIRECT_URI'));
        $this->scopes       = config('services.fic.scopes', env('FIC_SCOPES', 'situation:r entity.clients:r entity.suppliers:r issued_documents.invoices:r received_documents:r archive:r emails:r'));

        $this->http = new Client([
            'base_uri' => $this->base . '/',
            'timeout'  => 20,
        ]);
    }

    public function makeAuthUrl(string $state, array $scopes = []): string
    {
        $defaultScopes = explode(' ', $this->scopes);
        $scope = implode(' ', $scopes ?: $defaultScopes);

        $q = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirect,
            'response_type' => 'code',
            'scope'         => $scope,
            'state'         => $state,
        ]);

        return "{$this->base}/oauth/authorize?{$q}";
    }

    public function exchangeCode(string $code): array
    {
        $resp = $this->http->post('oauth/token', [
            'headers'     => ['Accept' => 'application/json'],
            'form_params' => [
                'grant_type'    => 'authorization_code',
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri'  => $this->redirect,
                'code'          => $code,
            ],
        ]);
        return json_decode((string) $resp->getBody(), true);
    }

    public function refresh(\App\Models\FicToken $row): \App\Models\FicToken
    {
        $resp = $this->http->post('oauth/token', [
            'headers'     => ['Accept' => 'application/json'],
            'form_params' => [
                'grant_type'    => 'refresh_token',
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $row->refresh_token,
            ],
        ]);
        $tok = json_decode((string)$resp->getBody(), true);

        $row->access_token  = $tok['access_token'];
        $row->refresh_token = $tok['refresh_token'] ?? $row->refresh_token;
        $row->expires_at    = Carbon::now()->addSeconds((int)($tok['expires_in'] ?? 3600));
        $row->save();

        return $row;
    }

    /** -------- Helpers di estrazione -------- */
    private function extractItems(array $decoded): array
    {
        // accetta più formati: {data:[...]}, {data:{items:[...]}}, {items:[...]}
        if (isset($decoded['data']) && is_array($decoded['data'])) {
            if (array_keys($decoded['data']) === range(0, count($decoded['data']) - 1)) {
                return $decoded['data'];
            }
            if (isset($decoded['data']['items']) && is_array($decoded['data']['items'])) {
                return $decoded['data']['items'];
            }
        }
        if (isset($decoded['items']) && is_array($decoded['items'])) {
            return $decoded['items'];
        }
        return [];
    }


    // App\Services\FattureInCloud.php
public function getCompanies(string $accessToken): array
{
    $resp = $this->http->get('user/companies', [
        'headers' => [
            'Accept'        => 'application/json',
            'Authorization' => "Bearer {$accessToken}",
        ],
    ]);
    return json_decode((string) $resp->getBody(), true);
}



    public function fetchAllIssued(int $companyId, string $accessToken, array $query = [], int $perPage = 200): array
{
    $page = 1;
    $all  = [];
    $seen = [];
 
    while (true) {
        $resp = $this->http->get("c/{$companyId}/issued_documents", [
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => "Bearer {$accessToken}",
            ],
            'query' => array_merge([
                'type'     => $query['type'] ?? 'invoice',
                'per_page' => $perPage,
                'page'     => $page,
            ], $query),
        ]);

        $decoded = json_decode((string)$resp->getBody(), true);
        $items   = $this->extractItems($decoded);
        $count   = is_array($items) ? count($items) : 0;

        if ($count === 0) break; // fine pagine

        // dedup su id/document_id per sicurezza
        foreach ($items as $doc) {
            $key = (string)($doc['id'] ?? $doc['document_id'] ?? md5(json_encode($doc)));
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $all[] = $doc;
        }

        $page++;
        if ($page > 10000) break; // hard guard
    }

    return $all;
}

public function fetchAllReceived(int $companyId, string $accessToken, array $query = [], int $perPage = 200): array
{
    $page = 1; $all = []; $seen = [];
    $type = $query['type'] ?? 'expense'; // expense | passive_credit_note | passive_delivery_note | self_invoice

    while (true) {
       $queryParams = array_merge([
    'type'     => $type,
    'per_page' => $perPage,
    'page'     => $page,
], $query);

// Ora imposta (o sovrascrivi) il parametro 'is_marked' per essere sicuro
// che la ricerca sia sempre per i documenti non registrati.
// Usa il booleano `false` che è più corretto.
$queryParams['is_marked'] = true;

$resp = $this->http->get("c/{$companyId}/received_documents", [
    'headers' => [
        'Accept'        => 'application/json',
        'Authorization' => "Bearer {$accessToken}",
    ],
    'query' => $queryParams,
]);
        $decoded = json_decode((string)$resp->getBody(), true);
        $items   = $this->extractItems($decoded);
     
        if (empty($items)) break;

        foreach ($items as $doc) {
            $key = (string)($doc['id'] ?? $doc['document_id'] ?? md5(json_encode($doc)));
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $all[] = $doc;
        }
        $page++;
        if ($page > 10000) break;
    }

  
    return $all;
}


// app/Services/FattureInCloud.php

public function fetchAllArchive(int $companyId, string $accessToken, array $query = [], int $perPage = 200): array
{
    $page = 1; $all = [];
    while (true) {
        $resp = $this->http->get("c/{$companyId}/archive", [
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => "Bearer {$accessToken}",
            ],
            'query' => array_merge([
                'per_page' => $perPage,
                'page'     => $page,
                // opzionale: 'fieldset' => 'detailed', 'fields' => 'id,category,attachment_url,created_at,description'
            ], $query),
        ]);
        $decoded = json_decode((string)$resp->getBody(), true);
      
        $items   = $this->extractItems($decoded);
        if (empty($items)) break;
        $all = array_merge($all, $items);
        $page++;
        if ($page > 10000) break;
    }
    return $all;
}


public function fetchAllEmails(int $companyId, string $accessToken, array $query = [], int $perPage = 200): array
{
    $page = 1; $all = []; $seen = [];
    while (true) {
        $resp = $this->http->get("c/{$companyId}/emails", [
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => "Bearer {$accessToken}",
            ],
            'query' => array_merge([
                'per_page' => $perPage,
                'page'     => $page,
            ], $query),
        ]);

        $decoded = json_decode((string)$resp->getBody(), true);
        $items   = $this->extractItems($decoded);
        if (!is_array($items) || count($items) === 0) break;

        foreach ($items as $email) {
            $key = (string)($email['id'] ?? md5(json_encode($email)));
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $all[] = $email;
        }
        $page++;
        if ($page > 10000) break;
    }
    return $all;
}

public function flattenEmailAttachments(array $emails): array
{
    $out = [];
    foreach ($emails as $e) {
        foreach (($e['attachments'] ?? []) as $a) {
            $out[] = [
                'email_id'     => $e['id'] ?? null,
                'date'         => $e['date'] ?? null,
                'from'         => $e['from'] ?? ($e['sender_email'] ?? null),
                'subject'      => $e['subject'] ?? null,
                'attachment_id'=> $a['id'] ?? null,
                'filename'     => $a['filename'] ?? null,
                'download_url' => $a['download_url'] ?? null,
                'content_type' => $a['content_type'] ?? null,
            ];
        }
    }
    return $out;
}



/**
 * Ritorna SOLO i documenti d’archivio "da registrare".
 * Criteri pratici:
 *  - categoria che contiene "Fatture elettroniche" / "Acquisti" / "Da registrare"
 *  - allegato .xml (tipico FE passive) o pdf/email importate in inbox
 *  - NON già mappati in una tua tabella come registrati (filtra lato applicazione)
 */
public function fetchAllUnregisteredPurchases(int $companyId, string $accessToken, array $query = [], int $perPage = 200): array
{
    $all = $this->fetchAllArchive($companyId, $accessToken, $query, $perPage);

    $isInboxCategory = static function ($cat) {
        $c = mb_strtolower((string)$cat, 'UTF-8');
        // nomi/categorie comuni nell’UI (possono variare per lingua/account)
        return str_contains($c, 'elettroniche') || str_contains($c, 'acquisti') || str_contains($c, 'da registrare') || str_contains($c, 'inbox');
    };

    return array_values(array_filter($all, function ($row) use ($isInboxCategory) {
        $cat = $row['category'] ?? '';
        $url = $row['attachment_url'] ?? '';
        $looksLikeEinvoice = is_string($url) && (str_ends_with(strtolower($url), '.xml') || str_contains(strtolower($url), '.xml?'));
        return $isInboxCategory($cat) || $looksLikeEinvoice;
    }));
}


}

