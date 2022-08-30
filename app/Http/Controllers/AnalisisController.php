<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Analisi;
use Illuminate\Http\Request;
use Exception;
use App\Helpers\Bilanci\BilanciHelper;
use Illuminate\Support\Facades\DB;

class AnalisisController extends Controller
{


    public function getAnalisiBilancioFull($idBilancio)
    {
        $bilancioHelper = new BilanciHelper;
        $analisiResult = $bilancioHelper->getAnalisiBilancio($idBilancio);


        return response()->json([
            'error' => 'false',
            'result' =>  $analisiResult
        ]);
    }


    public function storeAnalisiBilancioData(Request $request)
    {
        $idBilancio = $request->input('idBilancio');
        $allData = $request->all();

        if (DB::table('basic')->where('bilancio_id', '=', $idBilancio)->count() == 0) {
            DB::table('basic')->insert([
                'bilancio_id' => $allData["idBilancio"],
                'DSCR' => $allData["DSCR"],
                'alertDSCR' => $allData['alertDSCR'],
                'DSCRDate' => $allData["DSCRDate"],
                'DSCRdispLiquida' => $allData["DSCRdispLiquida"],
                'entrataDSCRCFmese1' => $allData["entrataDSCRCFmese1"],
                'entrataDSCRCFmese2' => $allData["entrataDSCRCFmese2"],
                'entrataDSCRCFmese3' => $allData["entrataDSCRCFmese3"],
                'entrataDSCRCFmese4' => $allData["entrataDSCRCFmese4"],
                'entrataDSCRCFmese5' => $allData["entrataDSCRCFmese5"],
                'entrataDSCRCFmese6' => $allData["entrataDSCRCFmese6"],
                'uscitaDSCRCFmese1' => $allData["uscitaDSCRCFmese1"],
                'uscitaDSCRCFmese2' => $allData["uscitaDSCRCFmese2"],
                'uscitaDSCRCFmese3' => $allData["uscitaDSCRCFmese3"],
                'uscitaDSCRCFmese4' => $allData["uscitaDSCRCFmese4"],
                'uscitaDSCRCFmese5' => $allData["uscitaDSCRCFmese5"],
                'uscitaDSCRCFmese6' => $allData["uscitaDSCRCFmese6"],
                'rimborsoDSCRmese1' => $allData["rimborsoDSCRmese1"],
                'rimborsoDSCRmese2' => $allData["rimborsoDSCRmese2"],
                'rimborsoDSCRmese3' => $allData["rimborsoDSCRmese3"],
                'rimborsoDSCRmese4' => $allData["rimborsoDSCRmese4"],
                'rimborsoDSCRmese5' => $allData["rimborsoDSCRmese5"],
                'rimborsoDSCRmese6' => $allData["rimborsoDSCRmese6"],
                'agenziaEntrate1' => $allData["agenziaEntrate1"],
                'agenziaEntrate3' => $allData["agenziaEntrate3"],
                'agenziaEntrate2' => $allData["agenziaEntrate2"],
                'agenziaEntrate4' => $allData["agenziaEntrate4"],
                'alertAgenziaEntrate' => $allData['alertAgenziaEntrate'],
                'INPS1' => $allData["INPS1"],
                'INPS2' => $allData["INPS2"],
                'INPS3' => $allData["INPS3"],
                'alertINPS' => $allData['alertINPS'],
                'riscossione' => $allData["riscossione"],
                'alertRiscossione' => $allData['alertRiscossione'],
                'retribuzioni1' => $allData["retribuzioni1"],
                'retribuzioni2' => $allData["retribuzioni2"],
                'retribuzioni3' => $allData["retribuzioni3"],
                'alertRetribuzioni' => $allData['alertRetribuzioni'],
                'fornitori1' => $allData["fornitori1"],
                'fornitori2' => $allData["fornitori2"],
                'alertFornitori' => $allData['alertFornitori']
            ]);
        } else {
            DB::table('basic')->where('bilancio_id', '=', $idBilancio)->update([
                'bilancio_id' => $allData["idBilancio"],
                'DSCR' => $allData["DSCR"],
                'alertDSCR' => $allData['alertDSCR'],
                'DSCRDate' => $allData["DSCRDate"],
                'DSCRdispLiquida' => $allData["DSCRdispLiquida"],
                'entrataDSCRCFmese1' => $allData["entrataDSCRCFmese1"],
                'entrataDSCRCFmese2' => $allData["entrataDSCRCFmese2"],
                'entrataDSCRCFmese3' => $allData["entrataDSCRCFmese3"],
                'entrataDSCRCFmese4' => $allData["entrataDSCRCFmese4"],
                'entrataDSCRCFmese5' => $allData["entrataDSCRCFmese5"],
                'entrataDSCRCFmese6' => $allData["entrataDSCRCFmese6"],
                'uscitaDSCRCFmese1' => $allData["uscitaDSCRCFmese1"],
                'uscitaDSCRCFmese2' => $allData["uscitaDSCRCFmese2"],
                'uscitaDSCRCFmese3' => $allData["uscitaDSCRCFmese3"],
                'uscitaDSCRCFmese4' => $allData["uscitaDSCRCFmese4"],
                'uscitaDSCRCFmese5' => $allData["uscitaDSCRCFmese5"],
                'uscitaDSCRCFmese6' => $allData["uscitaDSCRCFmese6"],
                'rimborsoDSCRmese1' => $allData["rimborsoDSCRmese1"],
                'rimborsoDSCRmese2' => $allData["rimborsoDSCRmese2"],
                'rimborsoDSCRmese3' => $allData["rimborsoDSCRmese3"],
                'rimborsoDSCRmese4' => $allData["rimborsoDSCRmese4"],
                'rimborsoDSCRmese5' => $allData["rimborsoDSCRmese5"],
                'rimborsoDSCRmese6' => $allData["rimborsoDSCRmese6"],
                'agenziaEntrate1' => $allData["agenziaEntrate1"],
                'agenziaEntrate2' => $allData["agenziaEntrate2"],
                'agenziaEntrate3' => $allData["agenziaEntrate3"],
                'agenziaEntrate4' => $allData["agenziaEntrate4"],
                'alertAgenziaEntrate' => $allData['alertAgenziaEntrate'],
                'INPS1' => $allData["INPS1"],
                'INPS2' => $allData["INPS2"],
                'INPS3' => $allData["INPS3"],
                'alertINPS' => $allData['alertINPS'],
                'riscossione' => $allData["riscossione"],
                'alertRiscossione' => $allData['alertRiscossione'],
                'retribuzioni1' => $allData["retribuzioni1"],
                'retribuzioni2' => $allData["retribuzioni2"],
                'retribuzioni3' => $allData["retribuzioni3"],
                'alertRetribuzioni' => $allData['alertRetribuzioni'],
                'fornitori1' => $allData["fornitori1"],
                'fornitori2' => $allData["fornitori2"],
                'alertFornitori' => $allData['alertFornitori']
            ]);
        }
        return back();
    }
}
