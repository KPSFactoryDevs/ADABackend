<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index(Request $req) {
        $companyId = (int)($req->query('company_id', 103));

        $rows = BankAccount::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get()
            ->map(fn($a)=>[
                'id'       => $a->id,
                'company_id'=> $a->company_id,
                'name'     => $a->name,
                'bank_code'=> $a->bank_code,
                'iban'     => $a->iban,
                'currency' => $a->currency,
                'balance'  => (float)$a->balance_cached,
                'canSync'  => false,
                'hasClock' => true,
            ]);

        // totale saldi
        $total = $rows->sum('balance');

        return response()->json([
            'total_balance' => $total,
            'data' => $rows,
        ]);
    }

    public function show(Request $req, int $id) {
        $acc = BankAccount::findOrFail($id);
        return response()->json([
            'id' => $acc->id,
            'company_id'=> $acc->company_id,
            'name' => $acc->name,
            'bank_code' => $acc->bank_code,
            'iban' => $acc->iban,
            'currency' => $acc->currency,
            'balance' => (float)$acc->balance_cached,
        ]);
    }
}
