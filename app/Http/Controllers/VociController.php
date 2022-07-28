<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Voci;


class VociController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $vocis = Voci::paginate(25);


        return view('voci.index', compact('vocis'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('voci.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $voce = null;
        try {

            $data = $this->getData($request);
            if (!isset($data['required'])) {
                $data['required'] = false;
            } else {
                $data['required'] = true;
            }

            if (!isset($data['voce_padre'])) {
                $data['voce_padre'] = "";
            }

            $voce = Voci::where('extended_name', $data['extended_name'])->get();

            if (count($voce) > 0) {
                return back()->withInput()
                    ->withErrors('unexpected_error', "E' già presente una voce con il seguente nome esteso: " . $data['extended_name']);
            } else {
                Voci::create($data);
            }

            return redirect()->route('admin.vocis.voci.index')
                ->with('success_message', 'voci was successfully added.');
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
        $voci = Voci::findOrFail($id);


        return view('voci.show', compact('voci'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $voci = Voci::findOrFail($id);

        return view('voci.edit', compact('voci'));
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
            if (!isset($data['required'])) {
                $data['required'] = false;
            } else {
                $data['required'] = true;
            }
            $voci = Voci::findOrFail($id);
            $voci->update($data);

            return redirect()->route('admin.vocis.voci.index')
                ->with('success_message', 'Vocibilancio was successfully updated.');
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
            $voci = Voci::findOrFail($id);
            $voci->delete();

            return redirect()->route('admin.vocis.voci.index')
                ->with('success_message', 'voci was successfully deleted.');
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
            'name' => 'string',
            'extended_name' => 'string',
            'required' => 'string',
            'voce_padre' => 'nullable'
        ];


        $data = $request->validate($rules);




        return $data;
    }
}
