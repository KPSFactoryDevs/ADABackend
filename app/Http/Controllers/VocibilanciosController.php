<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\vocibilancio;
use Illuminate\Http\Request;
use Exception;

class VocibilanciosController extends Controller
{

    /**
     * Display a listing of the vocibilancios.
     *
     * @return Illuminate\View\View
     */
    public function index()
    {
        $vocibilancios = vocibilancio::paginate(25);

        return view('vocibilancios.index', compact('vocibilancios'));
    }

    /**
     * Show the form for creating a new vocibilancio.
     *
     * @return Illuminate\View\View
     */
    public function create()
    {
        
        
        return view('vocibilancios.create');
    }

    /**
     * Store a new vocibilancio in the storage.
     *
     * @param Illuminate\Http\Request $request
     *
     * @return Illuminate\Http\RedirectResponse | Illuminate\Routing\Redirector
     */
    public function store(Request $request)
    {
        try {
            
            $data = $this->getData($request);
            
            vocibilancio::create($data);

            return redirect()->route('admin.vocibilancios.vocibilancio.index')
                ->with('success_message', 'Vocibilancio was successfully added.');
        } catch (Exception $exception) { 
            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }
    }

    /**
     * Display the specified vocibilancio.
     *
     * @param int $id
     *
     * @return Illuminate\View\View
     */
    public function show($id)
    {
        $vocibilancio = vocibilancio::findOrFail($id);

        return view('vocibilancios.show', compact('vocibilancio'));
    }

    /**
     * Show the form for editing the specified vocibilancio.
     *
     * @param int $id
     *
     * @return Illuminate\View\View
     */
    public function edit($id)
    {
        $vocibilancio = vocibilancio::findOrFail($id);
        

        return view('vocibilancios.edit', compact('vocibilancio'));
    }

    /**
     * Update the specified vocibilancio in the storage.
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
            
            $vocibilancio = vocibilancio::findOrFail($id);
            $vocibilancio->update($data);

            return redirect()->route('admin.vocibilancios.vocibilancio.index')
                ->with('success_message', 'Vocibilancio was successfully updated.');
        } catch (Exception $exception) {

            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }        
    }

    /**
     * Remove the specified vocibilancio from the storage.
     *
     * @param int $id
     *
     * @return Illuminate\Http\RedirectResponse | Illuminate\Routing\Redirector
     */
    public function destroy($id)
    {
        try {
            $vocibilancio = vocibilancio::findOrFail($id);
            $vocibilancio->delete();

            return redirect()->route('admin.vocibilancios.vocibilancio.index')
                ->with('success_message', 'Vocibilancio was successfully deleted.');
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
                'name' => 'required',
            'tassonomia' => 'required', 
        ];

        
        $data = $request->validate($rules);




        return $data;
    }

}
