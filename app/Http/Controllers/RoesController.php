<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Roe;
use Illuminate\Http\Request;
use Exception;

class RoesController extends Controller
{

    /**
     * Display a listing of the roes.
     *
     * @return Illuminate\View\View
     */
    public function index()
    {
        $roes = Roe::paginate(25);

        return view('roes.index', compact('roes'));
    }

    /**
     * Show the form for creating a new roe.
     *
     * @return Illuminate\View\View
     */
    public function create()
    {
        
        
        return view('roes.create');
    }

    /**
     * Store a new roe in the storage.
     *
     * @param Illuminate\Http\Request $request
     *
     * @return Illuminate\Http\RedirectResponse | Illuminate\Routing\Redirector
     */
    public function store(Request $request)
    {
        try {
            
            $data = $this->getData($request);
            
            Roe::create($data);

            return redirect()->route('roes.roe.index')
                ->with('success_message', 'Roe was successfully added.');
        } catch (Exception $exception) {

            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }
    }

    /**
     * Display the specified roe.
     *
     * @param int $id
     *
     * @return Illuminate\View\View
     */
    public function show($id)
    {
        $roe = Roe::findOrFail($id);

        return view('roes.show', compact('roe'));
    }

    /**
     * Show the form for editing the specified roe.
     *
     * @param int $id
     *
     * @return Illuminate\View\View
     */
    public function edit($id)
    {
        $roe = Roe::findOrFail($id);
        

        return view('roes.edit', compact('roe'));
    }

    /**
     * Update the specified roe in the storage.
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
            
            $roe = Roe::findOrFail($id);
            $roe->update($data);

            return redirect()->route('roes.roe.index')
                ->with('success_message', 'Roe was successfully updated.');
        } catch (Exception $exception) {

            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }        
    }

    /**
     * Remove the specified roe from the storage.
     *
     * @param int $id
     *
     * @return Illuminate\Http\RedirectResponse | Illuminate\Routing\Redirector
     */
    public function destroy($id)
    {
        try {
            $roe = Roe::findOrFail($id);
            $roe->delete();

            return redirect()->route('roes.roe.index')
                ->with('success_message', 'Roe was successfully deleted.');
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
                'year' => 'required|numeric|min:-2147483648|max:2147483647',
            'value' => 'required|numeric|min:-99999999.99|max:99999999.99', 
        ];
        
        $data = $request->validate($rules);


        return $data;
    }

}
