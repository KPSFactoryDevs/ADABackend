<?php

// app/Http/Controllers/CompaniesController.php
namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompaniesController extends Controller
{
    public function index(Request $request) {
        return response()->json(
            $request->user()->companies()->orderBy('created_at','desc')->get()
        );
    }

    public function store(Request $request) {
        $data = $request->validate([
            'ragione_sociale' => 'required|string|max:100',
            'partita_iva'     => 'nullable|string|max:11',
            'indirizzo'       => 'nullable|string|max:100',
            'provincia'       => 'nullable|string|max:20',
            'citta'           => 'nullable|string|max:100',
            'cap'             => 'nullable|string|max:25',
            'ateco'           => 'nullable|string|max:50',
            'capitale_sociale'=> 'nullable|string|max:100',
            'pec'             => 'nullable|email|max:75',
            'ultimo_bilancio' => 'nullable|date',
            'fatturato'       => 'nullable|string|max:150',
            'settore'         => 'nullable|string',
            'telefono'        => 'nullable|string|max:20',
        ]);

        $data['user_id'] = $request->user()->id;

        $company = Company::create($data);
        return response()->json($company, 201);
    }

    public function show(Request $request, Company $company) {
        $this->authorizeCompany($request, $company);
        return response()->json($company);
    }

    public function update(Request $request, Company $company) {
        $this->authorizeCompany($request, $company);
        $data = $request->validate([
            'ragione_sociale' => 'sometimes|required|string|max:100',
            'partita_iva'     => 'nullable|string|max:11',
            // (altri campi come in store)
        ]);
        $company->update($data);
        return response()->json($company);
    }

    public function destroy(Request $request, Company $company) {
        $this->authorizeCompany($request, $company);
        $company->delete();
        return response()->json(['message'=>'Azienda eliminata']);
    }

    private function authorizeCompany(Request $request, Company $company) {
        abort_unless($company->user_id === $request->user()->id, 403, 'Non autorizzato');
    }
}
