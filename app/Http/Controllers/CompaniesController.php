<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Exception;

class CompaniesController extends Controller
{

    /**
     * Display a listing of the indicis.
     *
     * @return Illuminate\View\View
     */
    public function index()
    {
        $companies = Company::paginate(25);

        return response()->json([
			'error' => false,
			'data' => $companies
		]);
    }

    /**
     * Show the form for creating a new indici.
     *
     * @return Illuminate\View\View
     */
    public function create()
    {
        return response()->json([
			'error' => false,
		]);
    }

    /**
     * Store a new indici in the storage.
     *
     * @param Illuminate\Http\Request $request
     *
     * @return Illuminate\Http\RedirectResponse | Illuminate\Routing\Redirector
     */
    public function store(Request $request)
    {
        try {

            $data = $this->getData($request);

          	$company = Company::create($data);

            return response()->json([
				'error' => false,
				'data' => 'Azienda creata correttamente'
			]);

        } catch (Exception $exception) {
            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }
    }

    /**
     * Display the specified indici.
     *
     * @param int $id
     *
     * @return Illuminate\View\View
     */
    public function show($id)
    {
        $company = Company::findOrFail($id);

        return response()->json([
			'error' => false,
			'data' => $company
		]);
    }

    /**
     * Show the form for editing the specified indici.
     *
     * @param int $id
     *
     * @return Illuminate\View\View
     */
    public function edit($id)
    {
        $company = Company::findOrFail($id);

        return response()->json([
			'error' => false,
			'data' => $company
		]);
    }

    /**
     * Update the specified indici in the storage.
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

         
            $company = Company::findOrFail($id);
            $company->update($data);

            return response()->json([
				'error' => false,
				'data' => 'Azienda aggiornata correttamente.'
			]);
        } catch (Exception $exception) {

            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }
    }

    /**
     * Remove the specified indici from the storage.
     *
     * @param int $id
     *
     * @return Illuminate\Http\RedirectResponse | Illuminate\Routing\Redirector
     */
    public function destroy($id)
    {
        try {
            $company = Company::findOrFail($id);
            $company->delete();

            return response()->json([
				'error' => false,
				'data' => 'Azienda eliminata correttamente.'
			]);
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
                'partita_iva' => 'nullable|string|min:1|max:255',
				'ragione_sociale' => 'nullable|string|max:100',
				'indirizzo' => 'nullable|string|max:100',
				'provincia' => 'nullable|string|max:20',
				'citta' => 'nullable|string|max:100',
				'cap' => 'nullable|string|max:25',
				'ateco' => 'nullable|string|max:50',
				'capitale_sociale' => 'nullable|string|max:100',
				'pec' => 'nullable|string|max:75',
				'ultimo_bilancio' => 'nullable',
				'fatturato' => 'nullable|string|max:150',
				'settore' => 'nullable',
				'telefono' => 'nullable|string|max:20',
        ];

        $data = $request->validate($rules);

        return $data;
    }

}
