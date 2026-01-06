<?php

namespace App\Http\Controllers;

use App\Models\FicToken;
use App\Services\FattureInCloud;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class FICOAuthController extends Controller
{
    /** Hard-code richiesti */
    private const APP_USER_ID          = 1;   // utente locale
    private const APP_COMPANY_ID       = 103; // company locale (nostra tabella)
    private const FIC_DEFAULT_COMPANY  = 811709; // company remota FIC (fallback)

    // === API (Bearer) ===
    public function prepare(Request $req)
    {
        $this->authorizeApiUser();

        $state = base64_encode(json_encode([
            'uid' => Auth::guard('api')->id(),
            'ts'  => time(),
            'rnd' => Str::random(8),
        ]));

        return response()->json([
            'state'         => $state,
            'auth_redirect' => url('/fic/connect?state='.urlencode($state)),
        ]);
    }
// App\Http\Controllers\FICOAuthController.php
public function status(Request $req, \App\Services\FattureInCloud $fic)
{
    $this->authorizeApiUser();
    $row = FicToken::where('user_id', Auth::guard('api')->id())->latest('id')->first();
    if (!$row) return response()->json(['connected' => false]);

    // 1) refresh se scaduto o in scadenza breve
    if (!$row->expires_at || now()->addMinutes(2)->gte($row->expires_at)) {
        try { $row = $fic->refresh($row); } catch (\Throwable $e) {
            return response()->json(['connected' => false]);
        }
    }

    // 2) verifica reale chiamando user/companies
    try {
        $companies = $fic->getCompanies($row->access_token);
        $list = $companies['data'] ?? ($companies['companies'] ?? []);
        if (!empty($list)) {
            // se manca la company remota salvata, prendi la prima
            if (!$row->company_id || $row->company_id < 100000) {
                $row->company_id = $list[0]['id'] ?? $row->company_id;
                $row->save();
            }
            return response()->json([
                'connected'  => true,
                'company_id' => $row->company_id,
                'expires_at' => $row->expires_at,
                'scope'      => $row->scope,
            ]);
        }
        return response()->json(['connected' => false]);
    } catch (\Throwable $e) {
        return response()->json(['connected' => false]);
    }
}


    public function disconnect(Request $req)
    {
        $this->authorizeApiUser();
        FicToken::where('user_id', Auth::guard('api')->id())->delete();
        return response()->json(['ok' => true]);
    }

    // === WEB (session) ===
    public function connect(FattureInCloud $fic, Request $req)
    {
        $state = $req->query('state') ?: \Illuminate\Support\Str::uuid()->toString();
        $req->session()->put('fic_oauth_state', $state);
        return redirect()->away($fic->makeAuthUrl($state));
    }

    public function callback(FattureInCloud $fic, Request $req)
    {
        $expected = $req->session()->pull('fic_oauth_state');
        $got      = $req->query('state');
        if (!$expected || !$got || $expected !== $got) abort(403, 'Invalid state');

        $code = $req->query('code');
        if (!$code) abort(400, 'Missing code');

        $tok = $fic->exchangeCode($code);

        

        $payload = json_decode(base64_decode($got), true) ?: [];
        $userId  = $payload['uid'] ?? null;

        $row = FicToken::create([
            'user_id'       => self::APP_USER_ID, // hard-code
            'company_id'    => self::APP_COMPANY_ID,              // lo settiamo dopo se possibile
            'scope'         => $tok['scope'] ?? null,
            'access_token'  => $tok['access_token'],
            'refresh_token' => $tok['refresh_token'] ?? null,
            'expires_at'    => Carbon::now()->addSeconds((int)($tok['expires_in'] ?? 3600)),
        ]);



        // prova a caricare la prima company remota
    // dopo aver creato $row = FicToken::create([...]);

try {
    $companies = $fic->getCompanies($row->access_token);
    $list = $companies['data'] ?? ($companies['companies'] ?? []);
    if (!empty($list)) {
        // scegli quella “principale” o la prima
        $remoteCompanyId = $list[0]['id'];
        $row->company_id = $remoteCompanyId; // <-- REMOTA
        $row->save();
    } else {
        // fallback esplicito
        $row->company_id = self::FIC_DEFAULT_COMPANY;
        $row->save();
    }
} catch (\Throwable $e) {
    // come fallback, usa quella di default
    $row->company_id = self::FIC_DEFAULT_COMPANY;
    $row->save();
}


        return redirect('http://localhost:5173/fatture');
    }

    private function authorizeApiUser(): void
    {
        if (!Auth::guard('api')->check()) {
            abort(401, 'Unauthenticated');
        }
    }

    /** =============== IMPORT CON PAGINAZIONE =============== */
   // App\Http\Controllers\FICOAuthController::import()

public function import(Request $request, FattureInCloud $fic)
{
    $userId    = self::APP_USER_ID;
    $companyId = self::APP_COMPANY_ID;

    $tok = FicToken::where('user_id', $userId)->latest('id')->first();
    if (!$tok) return response()->json(['message' => 'FIC non connesso per questo utente'], 400);

    $accessToken  = $tok->access_token;
    $ficCompanyId = $tok->company_id ?: self::FIC_DEFAULT_COMPANY;

    $q = array_filter([
        'date_from' => $request->query('date_from'),
        'date_to'   => $request->query('date_to'),
    ]);

    $inserted = 0; $updated = 0; $clientsUpserted = 0;
    $inboxImported = 0;

    // 1) già registrate (come prima)
    $issued   = $fic->fetchAllIssued($ficCompanyId,  $accessToken, $q, 200);
    $received = $fic->fetchAllReceived($ficCompanyId, $accessToken, $q, 200);

    $emails = $fic->fetchAllEmails($ficCompanyId, $accessToken, [], 200);
$attachments = $fic->flattenEmailAttachments($emails);



    // 2) “Acquisti da registrare” (solo se richiesto)
    $includeInbox = true;
    $inboxDocs = $includeInbox ? $fic->fetchAllUnregisteredPurchases($ficCompanyId, $accessToken, [], 200) : [];
 
    DB::beginTransaction();
    try {
        // emesse
        foreach ($issued as $doc) {
            if (!is_array($doc)) continue;
            [$client, $changed] = $this->upsertClientFromIssued($doc, $userId, $companyId);
            if ($changed) $clientsUpserted++;
            [$inv, $isNew] = $this->upsertInvoiceFromIssued($doc, $userId, $companyId, $client?->id);
            $isNew ? $inserted++ : $updated++;
        }

        // ricevute (registrate)
        foreach ($received as $doc) {
            if (!is_array($doc)) continue;
            [$client, $changed] = $this->upsertClientFromReceived($doc, $userId, $companyId);
            if ($changed) $clientsUpserted++;
            [$inv, $isNew] = $this->upsertInvoiceFromReceived($doc, $userId, $companyId, $client?->id);
            $isNew ? $inserted++ : $updated++;
        }

        // inbox (da registrare): li salvo come “received_pending”
        foreach ($inboxDocs as $doc) {
            // mappa minima: molte info qui stanno nell’XML/PDF, ma almeno salvo il link
            $supplierName = $doc['description'] ?? 'Documento da registrare';
            $attachment   = $doc['attachment_url'] ?? null;

            // opzionale: prova a inferire P.IVA dal filename/descrizione (grezzo)
            $vat = null;

            [$client, $changed] = $this->findOrCreateClient([
                'name'       => $supplierName,
                'vat_number' => $vat,
                'email'      => null,
            ], $userId, $companyId, 'supplier');
            if ($changed) $clientsUpserted++;

            // salva come riga “pending”, senza importi (li estrarrai quando registri)
            $row = Invoice::updateOrCreate([
                'company_id'  => $companyId,
                'direction'   => 'received_pending',
                'external_id' => 'archive#'.($doc['id'] ?? md5(json_encode($doc))),
            ], [
                'user_id'        => $userId,
                'client_id'      => $client?->id,
                'date'           => $doc['created_at'] ?? null,
                'number'         => null,
                'document_number'=> null,
                'client_name'    => $supplierName,
                'client_vat'     => $vat,
                'amount_net'     => 0,
                'amount_gross'   => 0,
                'remote_url'     => $attachment,
                'status'         => 'Da registrare',
            ]);

            $inboxImported += $row->wasRecentlyCreated ? 1 : 0;
        }

        DB::commit();
    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['message'=>'Import error', 'error'=>$e->getMessage()], 500);
    }

    $rows = Invoice::with('client')
        ->where('user_id', $userId)
        ->where('company_id', $companyId)
        ->orderByDesc('date')->orderByDesc('id')
        ->get();

    return response()->json([
        'summary' => [
            'inserted'         => $inserted,
            'updated'          => $updated,
            'clients_upserted' => $clientsUpserted,
            'inbox_imported'   => $inboxImported,
        ],
        'data' => $rows,
    ]);
}

    /* ---------------- Helpers ---------------- */
private function normalizeVat(?string $v): ?string {
    $v = strtoupper(trim($v ?? ''));
    if ($v === '') return null;
    $v = preg_replace('/\s+/', '', $v);
    $v = preg_replace('/^IT/', '', $v);
    return $v ?: null;
}
private function canonName(?string $s): string {
    $s = trim($s ?? '');
    $s = mb_strtoupper($s, 'UTF-8');
    // togli punteggiatura/spazi multipli
    $s = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $s);
    $s = preg_replace('/\s+/u', ' ', $s);
    return $s;
}

private function findOrCreateClient(array $payload, int $userId, int $companyId, string $role='customer'): array
{
    $name = trim($payload['name'] ?? $payload['nome'] ?? $payload['business_name'] ?? 'Senza nome');
    $vat  = $this->normalizeVat($payload['vat_number'] ?? $payload['vat'] ?? $payload['piva'] ?? null);
    $email= $payload['email'] ?? $payload['certified_email'] ?? $payload['pec'] ?? null;
    $citta= $payload['address_city'] ?? $payload['city'] ?? $payload['citta'] ?? null;

    $provCod = $payload['address_province'] ?? null;
    $country = $payload['country'] ?? 'IT';
    $regione = $payload['region'] ?? null;
    if (!$regione) $regione = $this->regionFromProvince($provCod, $country);

    $nameUpper = mb_strtoupper($name, 'UTF-8');

    $client = null;

    if ($vat) {
        // 1) match diretto per P.IVA
        $client = Client::where('company_id', $companyId)->where('piva', $vat)->first();

        // 2) se non trovato, aggancia record senza P.IVA ma stesso nome
        if (!$client) {
            $client = Client::where('company_id', $companyId)
                ->whereNull('piva')
                ->whereRaw('UPPER(nome) = ?', [$nameUpper])
                ->first();
            if ($client) {
                $client->piva       = $vat;
                $client->vat_number = $vat;
            }
        }
    } else {
        // 3) nessuna P.IVA:
        //    - se esiste un record con P.IVA e stesso nome → riusa quello (non crearne un altro “senza P.IVA”)
        $client = Client::where('company_id', $companyId)
            ->whereNotNull('piva')
            ->whereRaw('UPPER(nome) = ?', [$nameUpper])
            ->first();

        //    - altrimenti riusa/crea il record senza P.IVA (match per nome)
        if (!$client) {
            $client = Client::where('company_id', $companyId)
                ->whereNull('piva')
                ->whereRaw('UPPER(nome) = ?', [$nameUpper])
                ->first();
        }
    }

    // campi aggiornabili (non annullare P.IVA se manca)
    $data = [
        'user_id'    => $userId,
        'company_id' => $companyId,
        'nome'       => $name,
        'email'      => $email,
        'citta'      => $citta,
        'regione'    => $regione,
    ];
    if ($vat) { $data['piva'] = $vat; $data['vat_number'] = $vat; }

    if ($client) {
        $client->fill($data);

        // OR logico sui ruoli
        if ($role === 'customer' && !$client->is_customer) $client->is_customer = 1;
        if ($role === 'supplier' && !$client->is_supplier) $client->is_supplier = 1;

        $changed = $client->isDirty();
        if ($changed) $client->save();
        return [$client, $changed];
    }

    // nuovo
    $client = Client::create(array_merge($data, [
        'is_customer' => $role === 'customer' ? 1 : 0,
        'is_supplier' => $role === 'supplier' ? 1 : 0,
        'status'      => 'non_approvato',
    ]));
    return [$client, true];
}


private function upsertClientFromIssued(array $doc, int $userId, int $companyId): array
{
    $e = $doc['entity'] ?? $doc['client'] ?? [];
    return $this->findOrCreateClient([
        'name' => $e['name'] ?? null,
        'vat_number' => $e['vat_number'] ?? null,
        'tax_code' => $e['tax_code'] ?? null,
        'email' => $e['email'] ?? $e['certified_email'] ?? null,
        'address_city' => $e['address_city'] ?? ($e['city'] ?? null),
        'address_province' => $e['address_province'] ?? ($e['province'] ?? null),
        'country' => $e['country'] ?? 'IT',
        'region' => $e['region'] ?? null,
    ], $userId, $companyId, 'customer'); // 👈
}

private function upsertClientFromReceived(array $doc, int $userId, int $companyId): array
{
    $s = $doc['entity'] ?? $doc['supplier'] ?? [];
    return $this->findOrCreateClient([
        'name' => $s['name'] ?? null,
        'vat_number' => $s['vat_number'] ?? null,
        'tax_code' => $s['tax_code'] ?? null,
        'email' => $s['email'] ?? $s['certified_email'] ?? null,
        'address_city' => $s['address_city'] ?? ($s['city'] ?? null),
        'address_province' => $s['address_province'] ?? ($s['province'] ?? null),
        'country' => $s['country'] ?? 'IT',
        'region' => $s['region'] ?? null,
    ], $userId, $companyId, 'supplier'); // 👈
}


    private function uiStatusFromFIC(array $doc): string
    {
        $paid  = ($doc['payment_status'] ?? null) === 'paid';
        $total = (float)($doc['total_gross'] ?? $doc['amount_gross'] ?? 0);

        if ($paid) return 'Acquistata';
        if ($total <= 0) return 'Non Elegibile';
        return 'Esigibile';
    }

    private function upsertInvoiceFromIssued(array $doc, int $userId, int $companyId, ?int $clientId): array
    {
        $externalId = (string)($doc['id'] ?? $doc['document_id'] ?? '');
        $row = Invoice::where([
            'company_id'  => $companyId,
            'direction'   => 'issued',
            'external_id' => $externalId,
        ])->first();

        $data = [
            'user_id'         => $userId,
            'company_id'      => $companyId,
            'client_id'       => $clientId,
            'direction'       => 'issued',
            'external_id'     => $externalId,
            'date'            => $doc['date'] ?? null,
            'due_date'        => $doc['due_date'] ?? null,
            'number'          => $doc['number'] ?? null,
            'document_number' => $doc['document_number'] ?? null,
            'client_name'     => $doc['entity']['name'] ?? null,
            'client_vat'      => $doc['entity']['vat_number'] ?? null,
            'amount_net'      => $doc['total_net'] ?? $doc['amount_net'] ?? 0,
            'amount_gross'    => $doc['total_gross'] ?? $doc['amount_gross'] ?? 0,
            'payment_method'  => $doc['payment_method'] ?? null,
            'remote_url'      => $doc['attachment_url'] ?? null,
            'status'          => $this->uiStatusFromFIC($doc),
        ];

        if ($row) {
            $row->fill($data)->save();
            return [$row, false];
        }
        return [Invoice::create($data), true];
    }

    private function upsertInvoiceFromReceived(array $doc, int $userId, int $companyId, ?int $clientId): array
    {
        $externalId = (string)($doc['id'] ?? $doc['document_id'] ?? '');
        $row = Invoice::where([
            'company_id'  => $companyId,
            'direction'   => 'received',
            'external_id' => $externalId,
        ])->first();

        $data = [
            'user_id'         => $userId,
            'company_id'      => $companyId,
            'client_id'       => $clientId,
            'direction'       => 'received',
            'external_id'     => $externalId,
            'date'            => $doc['date'] ?? null,
            'due_date'        => $doc['due_date'] ?? null,
            'number'          => $doc['number'] ?? null,
            'document_number' => $doc['document_number'] ?? null,
            'client_name'     => $doc['entity']['name'] ?? null,
            'client_vat'      => $doc['entity']['vat_number'] ?? null,
            'amount_net'      => $doc['total_net'] ?? $doc['amount_net'] ?? 0,
            'amount_gross'    => $doc['total_gross'] ?? $doc['amount_gross'] ?? 0,
            'payment_method'  => $doc['payment_method'] ?? null,
            'remote_url'      => $doc['attachment_url'] ?? null,
            'status'          => $this->uiStatusFromFIC($doc),
        ];

        if ($row) {
            $row->fill($data)->save();
            return [$row, false];
        }
        return [Invoice::create($data), true];
    }

    private function regionFromProvince(?string $prov, ?string $country = 'IT'): ?string
{
    $p = strtoupper(trim($prov));

    $map = [
        'Abruzzo' => ['AQ','CH','PE','TE'],
        'Basilicata' => ['MT','PZ'],
        'Calabria' => ['CS','CZ','KR','RC','VV'],
        'Campania' => ['AV','BN','CE','NA','SA'],
        'Emilia-Romagna' => ['BO','FE','FC','MO','PR','PC','RA','RE','RN'],
        'Friuli-Venezia Giulia' => ['GO','PN','TS','UD'],
        'Lazio' => ['FR','LT','RI','RM','VT'],
        'Liguria' => ['GE','IM','SP','SV'],
        'Lombardia' => ['BG','BS','CO','CR','LC','LO','MN','MI','MB','PV','SO','VA'],
        'Marche' => ['AN','AP','FM','MC','PU'],
        'Molise' => ['CB','IS'],
        'Piemonte' => ['AL','AT','BI','CN','NO','TO','VB','VC'],
        'Puglia' => ['BA','BAT','BR','FG','LE','TA'],
        'Sardegna' => ['CA','NU','OR','SS','SU','CI','VS','OT','OG'], // includo sigle “storiche”
        'Sicilia' => ['AG','CL','CT','EN','ME','PA','RG','SR','TP'],
        'Toscana' => ['AR','FI','GR','LI','LU','MS','PI','PO','PT','SI'],
        'Trentino-Alto Adige/Südtirol' => ['BZ','TN'],
        'Umbria' => ['PG','TR'],
        'Valle d\'Aosta/Vallée d\'Aoste' => ['AO'],
        'Veneto' => ['BL','PD','RO','TV','VE','VI','VR'],
    ];

    foreach ($map as $reg => $codes) {
        if (in_array($p, $codes, true)) return $reg;
    }
    return null;
}
}
