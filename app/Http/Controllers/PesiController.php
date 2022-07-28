<?php

namespace App\Http\Controllers;

use App\Models\pesi;
use App\Models\range;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class PesiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pesis = pesi::paginate(25);

        return view('pesi.index', compact('pesis'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $range = range::pluck('indice','id')->all();
        
        return view('pesi.create', compact('range'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            
            $data = $this->getData($request);
            
            pesi::create($data);

            return redirect()->route('admin.pesis.pesi.index')
                ->with('success_message', 'peso was successfully added.');
        } catch (Exception $exception) {

            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\pesi  $pesi
     * @return \Illuminate\Http\Response
     */
    public function show(pesi $pesi)
    {
        return view('pesi.show', compact('pesi'));

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\pesi  $pesi
     * @return \Illuminate\Http\Response
     */
    public function edit(pesi $pesi)
    {
        $ranges = range::pluck('id')->all();

        return view('pesi.edit', compact('pesi'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\pesi  $pesi
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, pesi $pesi)
    {
        try {
            
            $data = $this->getData($request);
            
            pesi::create($data);

            return redirect()->route('admin.pesis.pesi.index')
                ->with('success_message', 'peso was successfully added.');
        } catch (Exception $exception) {

            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\pesi  $pesi
     * @return \Illuminate\Http\Response
     */
    public function destroy(pesi $pesi)
    {
        try {
            $pesi = pesi::findOrFail($pesi->id);
            $pesi->delete();

            return redirect()->route('admin.pesis.pesi.index')
                ->with('success_message', 'pesi was successfully deleted.');
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
            'peso' => 'numeric',
            'indice' => 'required',
            'range_id' => 'required'
        ];

        
        $data = $request->validate($rules);




        return $data;
    }

}
