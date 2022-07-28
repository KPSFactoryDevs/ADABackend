<?php

namespace App\Http\Controllers;


use App\Models\pesi;
use App\Models\range;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class RangeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ranges = range::paginate(25);

        return view('range.index', compact('ranges'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pesi = pesi::pluck('id')->all();
        $ranges = range::pluck('pesi_id')->all();
        
        return view('range.create', compact('pesi','ranges'));
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
            
            range::create($data);

            return redirect()->route('admin.ranges.range.index')
                ->with('success_message', 'peso was successfully added.');
        } catch (Exception $exception) {

            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\range  $range
     * @return \Illuminate\Http\Response
     */
    public function show(range $range)
    {
        return view('range.show', compact('range'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\range  $range
     * @return \Illuminate\Http\Response
     */
    public function edit(range $range)
    {
        $pesi = pesi::pluck('id')->all();
        
        return view('range.edit', compact('range'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\range  $range
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, range $range)
    {
        try {
            
            $data = $this->getData($request);
            
            $range = range::findOrFail($id);
            $range->update($data);

            return redirect()->route('ranges.range.index')
                ->with('success_message', 'range was successfully updated.');
        } catch (Exception $exception) {

            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\range  $range
     * @return \Illuminate\Http\Response
     */
    public function destroy(range $range)
    {
        try {
            $range = range::findOrFail($range->id);
            $range->delete();

            return redirect()->route('admin.ranges.range.index')
                ->with('success_message', 'range was successfully deleted.');
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
            'pesi_id' => 'string',
            'indice' => 'string',
            'range_min' => 'required',
            'range_max' => 'required', 
            'score' => 'required', 
        ];

        
        $data = $request->validate($rules);




        return $data;
    }

}
