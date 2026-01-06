<?php

// app/Http/Controllers/ClientsController.php
namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Auth;
class ClientsController extends Controller
{
  public function index(Request $req)
{
    if (!Auth::guard('api')->check()) abort(401);

    $role = $req->query('role', 'customer'); // customer|supplier|all
    $cid  = (int) $req->header('CurrentCompany');

    // se vuoi obbligarlo:
    if (!$cid) {
        return response()->json(['message' => 'Missing CurrentCompany header'], 422);
    }

    $q = Client::query()
        ->where('user_id', Auth::guard('api')->id())
        ->where('company_id', $cid);

    if ($role === 'customer') {
        $q->where('is_customer', 1);
    } elseif ($role === 'supplier') {
        $q->where('is_supplier', 1);
    }

    // cintura e bretelle: nessuna join qui, ma non fa male
    return response()->json(
        $q->select('clients.*')->distinct()->orderBy('nome')->get()
    );
}


    // app/Http/Controllers/ClientsController.php
public function store(Request $request) {
    $user = $request->user();
    $cid  = $request->header('CurrentCompany');
    if (!$cid) return response()->json(['message'=>'CurrentCompany header mancante'], 422);

    $role = $request->input('role', 'customer'); // 👈 FIX

    $data = $request->validate([
        'nome' => ['required','string','max:150'],
        'piva' => ['required','string','max:32',
          Rule::unique('clients','piva')->where(fn($q)=>$q->where('company_id',$cid))
        ],
        'regione'=>['nullable','string','max:80'],
        'citta'  =>['nullable','string','max:120'],
        'email'  =>['nullable','email','max:150'],
        'fatturato'=>['nullable','numeric','min:0'],
        'rating' =>['nullable','integer','min:0','max:5'],
        'status' =>['required','string', Rule::in(['approvato','non_approvato'])],
    ]);

    $data['company_id']  = (int)$cid;
    $data['user_id']     = $user->id;
    $data['vat_number']  = $data['piva']; // opzionale: tienili allineati
    $data['is_customer'] = $role === 'customer';
    $data['is_supplier'] = $role === 'supplier';

    return response()->json(Client::create($data), 201);
}


    public function show(Request $request, Client $client) {
        $this->authorizeClient($request, $client);
        return response()->json($client);
    }

    public function update(Request $request, Client $client) {
        $this->authorizeClient($request, $client);
        $cid = $client->company_id;

        $data = $request->validate([
            'nome'       => ['sometimes','required','string','max:150'],
            'piva'       => ['sometimes','required','string','max:32',
                Rule::unique('clients','piva')->where(fn($q)=>$q->where('company_id',$cid))->ignore($client->id)
            ],
            'regione'    => ['nullable','string','max:80'],
            'citta'      => ['nullable','string','max:120'],
            'email'      => ['nullable','email','max:150'],
            'fatturato'  => ['nullable','numeric','min:0'],
            'rating'     => ['nullable','integer','min:0','max:5'],
            'status'     => ['nullable','string', Rule::in(['approvato','non_approvato'])],
        ]);

        $client->update($data);
        return response()->json($client);
    }

    public function destroy(Request $request, Client $client) {
        $this->authorizeClient($request, $client);
        $client->delete();
        return response()->json(['message'=>'Cliente eliminato']);
    }

    private function authorizeClient(Request $request, Client $client) {
        abort_unless($client->user_id === $request->user()->id, 403, 'Non autorizzato');
        // opzionale: anche match su CurrentCompany
        if ($request->hasHeader('CurrentCompany')) {
            abort_unless((int)$request->header('CurrentCompany') === (int)$client->company_id, 403, 'Azienda non coerente');
        }
    }
}
