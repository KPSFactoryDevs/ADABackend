<?php

namespace App\Http\Controllers;

use App\Models\AnalisisType;
use Illuminate\Http\Request;

class AnalisisTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $analisiType = AnalisisType::paginate(25);

        return view('analisistypes.index', compact('analisisType'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {		
        return view('analisistypes.create');
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
            
            AnalisisType::create($data);

            return redirect()->route('admin.analisistypes.analisistype.index')
                ->with('success_message', 'Account was successfully added.');
        } catch (Exception $exception) {

            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $analisisType = AnalisisType::findOrFail($id);

        return view('analisistypes.show', compact('AnalisisType'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $analisisType = AnalisisType::findOrFail($id);
        

        return view('analisistypes.edit', compact('AnalisisType'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            
            $data = $this->getData($request);
            
            $analisisType = AnalisisType::findOrFail($id);
            $analisisType->update($data);

            return redirect()->route('admin.analisistypes.analisistype.index')
                ->with('success_message', 'AnalisisType was successfully updated.');
        } catch (Exception $exception) {

            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $analisisType = AnalisisType::findOrFail($id);
            $analisisType->delete();

            return redirect()->route('admin.analisistypes.analisistype.index')
                ->with('success_message', 'AnalisisType was successfully deleted.');
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
        ];

        
        $data = $request->validate($rules);




        return $data;
    }

}
