<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\soglie;
use Illuminate\Http\Request;
use Exception;
use App\Helpers\Bilanci\BilanciHelper;

class SogliesController extends Controller
{

    /**
     * Display a listing of the soglies.
     *
     * @return Illuminate\View\View
     */
    public function index()
    {
        $soglies = soglie::paginate(25);

        return view('soglies.index', compact('soglies'));
    }

    /**
     * Show the form for creating a new soglie.
     *
     * @return Illuminate\View\View
     */
    public function create()
    {

        $bilanciHelper = new BilanciHelper;

        $tipiAziende = $bilanciHelper->getTipiAziende();

        return view('soglies.create',compact(['tipiAziende']));
    }

    /**
     * Store a new soglie in the storage.
     *
     * @param Illuminate\Http\Request $request
     *
     * @return Illuminate\Http\RedirectResponse | Illuminate\Routing\Redirector
     */
    public function store(Request $request)
    {
        try {

            $data = $this->getData($request);

            soglie::create($data);

            return redirect()->route('admin.soglies.soglie.index')
                ->with('success_message', 'Soglie was successfully added.');
        } catch (Exception $exception) {
            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }
    }

    /**
     * Display the specified soglie.
     *
     * @param int $id
     *
     * @return Illuminate\View\View
     */
    public function show($id)
    {
        $soglie = soglie::findOrFail($id);

        return view('soglies.show', compact('soglie'));
    }

    /**
     * Show the form for editing the specified soglie.
     *
     * @param int $id
     *
     * @return Illuminate\View\View
     */
    public function edit($id)
    {
        $soglie = soglie::findOrFail($id);

        $bilanciHelper = new BilanciHelper;

        $tipiAziende = $bilanciHelper->getTipiAziende();

        return view('soglies.edit', compact('soglie','tipiAziende','id'));
    }

    /**
     * Update the specified soglie in the storage.
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

            $soglie = soglie::findOrFail($id);
            $soglie->update($data);

            return redirect()->route('admin.soglies.soglie.index')
                ->with('success_message', 'Soglie was successfully updated.');
        } catch (Exception $exception) {
            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }
    }

    /**
     * Remove the specified soglie from the storage.
     *
     * @param int $id
     *
     * @return Illuminate\Http\RedirectResponse | Illuminate\Routing\Redirector
     */
    public function destroy($id)
    {
        try {
            $soglie = soglie::findOrFail($id);
            $soglie->delete();

            return redirect()->route('soglies.soglie.index')
                ->with('success_message', 'Soglie was successfully deleted.');
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
                'rischio_elevato' => 'required|numeric|min:-999999.99|max:999999.99',
            'situazione_critica' => 'required|numeric|min:-999999.99|max:999999.99',
            'buono' => 'required|numeric|min:-999999.99|max:999999.99',
            'ottimo' => 'required|numeric|min:-999999.99|max:999999.99',
            'indice_riferimento' => 'required|string|min:1|max:191',
            'tipo_azienda' => 'nullable|string|min:0|max:191',
        ];

        $data = $request->validate($rules);


        return $data;
    }

}
