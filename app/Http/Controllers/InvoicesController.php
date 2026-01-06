<?php

namespace App\Http\Controllers;

use App\Models\FicToken;
use App\Models\Invoice;
use App\Services\FattureInCloud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoicesController extends Controller
{

     public function index(Request $request)
    {
    
        $userId = 1;
        if ($userId === 'Unauthorized') {
            return response()->json(['message'=>'Unauthorized'], 401);
        }

        $companyId = $request->header('CurrentCompany');
        $q = Invoice::with('customer')
            ->where('user_id', $userId);

        if ($companyId !== null && $companyId !== '') {
            $q->where('company_id', $companyId);
        }

        $rows = $q->orderByDesc('date')->orderByDesc('id')->get();

        return response()->json($rows);
    }


    public function importFromCloud(Request $req, FattureInCloud $fic)
    {
        $this->authorizeUser();

        /** @var \App\Models\FicToken $row */
        $row = FicToken::where('user_id', auth()->id())->latest('id')->firstOrFail();
        if ($row->isExpired()) $fic->refresh($row);
        if (!$row->company_id) {
            return response()->json(['error' => true, 'message' => 'Nessuna company collegata'], 400);
        }

        $issued   = $fic->fetchIssued($row->company_id, $row->access_token, $req->only(['page','per_page','date_from','date_to']));
        $received = $fic->fetchReceived($row->company_id, $row->access_token, $req->only(['page','per_page','date_from','date_to']));

        // TODO: mappa e salva in tabelle laravel (invoices, customers, ecc.)
        // Esempio di massima:
        DB::transaction(function () use ($issued, $received) {
            // 1) clienti da issued/received: crea se non esiste (match su piva/cf o name)
            // 2) fatture emesse => invoices (type=issued), fatture ricevute => invoices (type=received)
            // 3) evita duplicati usando external_id = id documento FIC
        });

        return response()->json(['error'=>false, 'imported'=>[
            'issued_count'   => count($issued['data'] ?? $issued),
            'received_count' => count($received['data'] ?? $received),
        ]]);
    }

    private function authorizeUser(): void
    {
        if (!auth()->check()) abort(401, 'Login richiesto');
    }
}
