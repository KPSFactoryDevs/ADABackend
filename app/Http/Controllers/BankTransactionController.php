<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Http\Request;

class BankTransactionController extends Controller
{
    public function index(Request $req, int $accountId) {
        $acc = BankAccount::findOrFail($accountId);

        // Filtri
        $q         = $req->query('q');
        $from      = $req->query('date_from');
        $to        = $req->query('date_to');
        $type      = $req->query('type');            // in | out | (null = tutti)
        $verified  = $req->query('verified');        // yes | no | todo
        $category  = $req->query('category');        // string
        $page      = (int)$req->query('page', 1);
        $perPage   = min(200, (int)$req->query('per_page', 25));

        $base = BankTransaction::query()
            ->where('bank_account_id', $acc->id)
            ->search($q)
            ->between($from, $to)
            ->type($type)
            ->verifiedState($verified)
            ->category($category);

        // summary prima della paginazione (sul set filtrato)
        $sumIn  = (clone $base)->where('amount','>',0)->sum('amount');
        $sumOut = (clone $base)->where('amount','<',0)->sum('amount');
        $sumNet = $sumIn + $sumOut;

        $rows = $base->orderByDesc('date')->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        $data = collect($rows->items())->map(function ($m) {
            return [
                'id'       => $m->id,
                'date'     => $m->date?->format('d MMMM Y'),
                'method'   => $m->method,
                'title'    => $m->title,
                'subtitle' => $m->subtitle,
                'amount'   => (float)$m->amount,
                'category' => ['name'=>$m->category_name, 'muted'=> $m->category_name === 'Non categorizzata'],
                'verified' => $m->verified, // null/true/false
                'logo'     => ['type'=>'circle','initials'=>'BA'],
            ];
        });

        return response()->json([
            'account' => [
                'id' => $acc->id,
                'name' => $acc->name,
                'iban' => $acc->iban,
                'balance' => (float)$acc->balance_cached,
            ],
            'summary' => [
                'inc' => (float)$sumIn,
                'out' => (float)$sumOut,
                'net' => (float)$sumNet,
                'count' => $rows->total(),
            ],
            'pagination' => [
                'total' => $rows->total(),
                'per_page' => $rows->perPage(),
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
            ],
            'data' => $data,
        ]);
    }
}

