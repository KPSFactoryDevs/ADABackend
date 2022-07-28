<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\cr;
use Illuminate\Http\Request;
use Exception;

class CrsController extends Controller
{

    /**
     * Display a listing of the crs.
     *
     * @return Illuminate\View\View
     */
    public function index()
    {
        $crs = cr::with('account')->paginate(25);

        return view('crs.index', compact('crs'));
    }

    /**
     * Show the form for creating a new cr.
     *
     * @return Illuminate\View\View
     */
    public function create()
    {
        $accounts = Account::pluck('Name', 'id')->all();

        return view('crs.create', compact('accounts'));
    }

    /**
     * Store a new cr in the storage.
     *
     * @param Illuminate\Http\Request $request
     *
     * @return Illuminate\Http\RedirectResponse | Illuminate\Routing\Redirector
     */
    public function store(Request $request)
    {
        try {

            $data = $this->getData($request);

            cr::create($data);

            return redirect()->route('crs.cr.index')
                ->with('success_message', 'Cr was successfully added.');
        } catch (Exception $exception) {

            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }
    }

    /**
     * Display the specified cr.
     *
     * @param int $id
     *
     * @return Illuminate\View\View
     */
    public function show($id)
    {
        $cr = cr::with('account')->findOrFail($id);

        return view('crs.show', compact('cr'));
    }

    /**
     * Show the form for editing the specified cr.
     *
     * @param int $id
     *
     * @return Illuminate\View\View
     */
    public function edit($id)
    {
        $cr = cr::findOrFail($id);
        $accounts = Account::pluck('Name', 'id')->all();

        return view('crs.edit', compact('cr', 'accounts'));
    }

    /**
     * Update the specified cr in the storage.
     *
     * @param int $id
     * @param Illuminate\Http\Request $request
     *
     * @return Illuminate\Http\RedirectResponse | Illuminate\Routing\Redirector
     */
    public function update($id, Request $request)
    {
        try {

            $data = $this->getData($request);

            $cr = cr::findOrFail($id);
            $cr->update($data);

            return redirect()->route('crs.cr.index')
                ->with('success_message', 'Cr was successfully updated.');
        } catch (Exception $exception) {

            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }
    }

    /**
     * Remove the specified cr from the storage.
     *
     * @param int $id
     *
     * @return Illuminate\Http\RedirectResponse | Illuminate\Routing\Redirector
     */
    public function destroy($id)
    {
        try {
            $cr = cr::findOrFail($id);
            $cr->delete();

            return redirect()->route('crs.cr.index')
                ->with('success_message', 'Cr was successfully deleted.');
        } catch (Exception $exception) {

            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }
    }


    /**
     * Get the request's data from the request.
     *
     * @param Illuminate\Http\Request\Request $request 
     * @return array
     */
    protected function getData(Request $request)
    {
        $rules = [
            'account_id' => 'required|numeric|min:0|max:4294967295',
            'anno' => 'required|numeric|min:-2147483648|max:2147483647',
            'mese' => 'required|string|min:1|max:191',
            'nome_banca' => 'required|string|min:1|max:191',
            'sezione' => 'required|string|min:1|max:191',
            'categoria' => 'required|string|min:1|max:191',
            'accordato' => 'required|numeric|min:-999999.99|max:999999.99',
            'accordato_operativo' => 'required|numeric|min:-999999.99|max:999999.99',
            'utilizzato' => 'required|numeric|min:-999999.99|max:999999.99',
            'durata_residua' => 'required|string|min:1|max:191',
            'durata_originaria' => 'required|string|min:1|max:191',
            'localizzazione' => 'required|string|min:1|max:191',
            'divisa' => 'required|string|min:1|max:191',
            'tipo_garanzia' => 'required|string|min:1|max:191',
            'stato_rapporto' => 'required|string|min:1|max:191',
            'tipo_attivita' => 'required|string|min:1|max:191',
            'ruolo_affidato' => 'required|string|min:1|max:191',
            'import_export' => 'required|string|min:1|max:191',
        ];

        $data = $request->validate($rules);


        return $data;
    }
}
