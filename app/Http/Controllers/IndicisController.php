<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Analisistype;
use App\Models\indici;
use Illuminate\Http\Request;
use Exception;

class IndicisController extends Controller
{

    /**
     * Display a listing of the indicis.
     *
     * @return Illuminate\View\View
     */
    public function index()
    {
        $indicis = indici::paginate(25);

        return view('indicis.index', compact('indicis'));
    }

    /**
     * Show the form for creating a new indici.
     *
     * @return Illuminate\View\View
     */
    public function create()
    {
        $Analisistypes = Analisistype::pluck('name','id')->all();
        
        return view('indicis.create', compact('Analisistypes'));
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
            
            indici::create($data);

            return redirect()->route('indicis.indici.index')
                ->with('success_message', 'Indici was successfully added.');
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
        $indici = indici::with('analisistype')->findOrFail($id);

        return view('indicis.show', compact('indici'));
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
        $indici = indici::findOrFail($id);
        $Analisistypes = Analisistype::pluck('name','id')->all();

        return view('indicis.edit', compact('indici','Analisistypes'));
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
            
            $indici = indici::findOrFail($id);
            $indici->update($data);

            return redirect()->route('indicis.indici.index')
                ->with('success_message', 'Indici was successfully updated.');
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
            $indici = indici::findOrFail($id);
            $indici->delete();

            return redirect()->route('indicis.indici.index')
                ->with('success_message', 'Indici was successfully deleted.');
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
                'name' => 'required|string|min:1|max:255',
            'formula' => 'required',
            'analisistype_id' => 'required', 
        ];

        
        $data = $request->validate($rules);




        return $data;
    }

}
