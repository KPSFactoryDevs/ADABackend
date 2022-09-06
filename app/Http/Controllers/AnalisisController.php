<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Analisi;
use Illuminate\Http\Request;
use Exception;
use App\Helpers\Bilanci\BilanciHelper;
use Illuminate\Support\Facades\DB;
use DateTime;

class AnalisisController extends Controller
{


    public function getAnalisiBilancioFull($idBilancio)
    {
        $bilancioHelper = new BilanciHelper;
        $analisiResult = $bilancioHelper->getAnalisiBilancio($idBilancio);
        $nomeAzienda = $bilancioHelper->getNameCompany($idBilancio);

        return response()->json([
            'error' => 'false',
            'nomeAzienda' => $nomeAzienda,
            'result' =>  $analisiResult,
        ]);
    }


    public function storeAnalisiBilancioData(Request $request)
    {   
        $idBilancio = $request->input('idBilancio');
        $allData = $request->all();
        $now = new DateTime();

        if (DB::table('basic')->where('bilancio_id', '=', $idBilancio)->count() == 0) {
            DB::table('basic')->insert([
                'bilancio_id' => ($allData["idBilancio"] != null) ? $allData["idBilancio"] : 0,
                'DSCR' => ($allData["DSCR"] != null) ? $allData["DSCR"] : 0,
                'alertDSCR' => ($allData['alertDSCR'] != null) ? $allData["alertDSCR"] : 0,
                'DSCRDate' => ($allData["DSCRDate"] != null) ? $allData["DSCRDate"] : 0,
                'DSCRdispLiquida' => ($allData["DSCRdispLiquida"] != null) ? $allData["DSCRdispLiquida"] : 0,
                'entrataDSCRCFmese1' => ($allData["entrataDSCRCFmese1"] != null) ? $allData["entrataDSCRCFmese1"] : 0,
                'entrataDSCRCFmese2' => ($allData["entrataDSCRCFmese2"] != null) ? $allData["entrataDSCRCFmese2"] : 0,
                'entrataDSCRCFmese3' => ($allData["entrataDSCRCFmese3"] != null) ? $allData["entrataDSCRCFmese3"] : 0,
                'entrataDSCRCFmese4' => ($allData["entrataDSCRCFmese4"] != null) ? $allData["entrataDSCRCFmese4"] : 0,
                'entrataDSCRCFmese5' => ($allData["entrataDSCRCFmese5"] != null) ? $allData["entrataDSCRCFmese5"] : 0,
                'entrataDSCRCFmese6' => ($allData["entrataDSCRCFmese6"] != null) ? $allData["entrataDSCRCFmese6"] : 0,
                'uscitaDSCRCFmese1' => ($allData["uscitaDSCRCFmese1"] != null) ? $allData["uscitaDSCRCFmese1"] : 0,
                'uscitaDSCRCFmese2' => ($allData["uscitaDSCRCFmese2"] != null) ? $allData["uscitaDSCRCFmese2"] : 0,
                'uscitaDSCRCFmese3' => ($allData["uscitaDSCRCFmese3"] != null) ? $allData["uscitaDSCRCFmese3"] : 0,
                'uscitaDSCRCFmese4' => ($allData["uscitaDSCRCFmese4"] != null) ? $allData["uscitaDSCRCFmese4"] : 0,
                'uscitaDSCRCFmese5' => ($allData["uscitaDSCRCFmese5"] != null) ? $allData["uscitaDSCRCFmese5"] : 0,
                'uscitaDSCRCFmese6' => ($allData["uscitaDSCRCFmese6"] != null) ? $allData["uscitaDSCRCFmese6"] : 0,
                'rimborsoDSCRmese1' => ($allData["rimborsoDSCRmese1"] != null) ? $allData["rimborsoDSCRmese1"] : 0,
                'rimborsoDSCRmese2' => ($allData["rimborsoDSCRmese2"] != null) ? $allData["rimborsoDSCRmese2"] : 0,
                'rimborsoDSCRmese3' => ($allData["rimborsoDSCRmese3"] != null) ? $allData["rimborsoDSCRmese3"] : 0,
                'rimborsoDSCRmese4' => ($allData["rimborsoDSCRmese4"] != null) ? $allData["rimborsoDSCRmese4"] : 0,
                'rimborsoDSCRmese5' => ($allData["rimborsoDSCRmese5"] != null) ? $allData["rimborsoDSCRmese5"] : 0,
                'rimborsoDSCRmese6' => ($allData["rimborsoDSCRmese6"] != null) ? $allData["rimborsoDSCRmese6"] : 0,
                'agenziaEntrate1' => ($allData["agenziaEntrate1"] != null) ? $allData["agenziaEntrate1"] : 0,
                'agenziaEntrate3' => ($allData["agenziaEntrate3"] != null) ? $allData["agenziaEntrate3"] : 0,
                'agenziaEntrate2' => ($allData["agenziaEntrate2"] != null) ? $allData["agenziaEntrate2"] : 0,
                'agenziaEntrate4' => ($allData["agenziaEntrate4"] != null) ? $allData["agenziaEntrate4"] : 0,
                'alertAgenziaEntrate' => ($allData['alertAgenziaEntrate'] != null) ? $allData["alertAgenziaEntrate"] : 0,
                'INPS1' => ($allData["INPS1"] != null) ? $allData["INPS1"] : 0,
                'INPS2' => ($allData["INPS2"] != null) ? $allData["INPS2"] : 0,
                'INPS3' => ($allData["INPS3"] != null) ? $allData["INPS3"] : 0,
                'alertINPS' => ($allData['alertINPS'] != null) ? $allData["alertINPS"] : 0,
                'riscossione' => ($allData["riscossione"] != null) ? $allData["riscossione"] : 0,
                'alertRiscossione' => ($allData['alertRiscossione'] != null) ? $allData["alertRiscossione"] : 0,
                'retribuzioni1' => ($allData["retribuzioni1"] != null) ? $allData["retribuzioni1"] : 0,
                'retribuzioni2' => ($allData["retribuzioni2"] != null) ? $allData["retribuzioni2"] : 0,
                'retribuzioni3' => ($allData["retribuzioni3"] != null) ? $allData["retribuzioni3"] : 0,
                'alertRetribuzioni' => ($allData['alertRetribuzioni'] != null) ? $allData["alertRetribuzioni"] : 0,
                'fornitori1' => ($allData["fornitori1"] != null) ? $allData["fornitori1"] : 0,
                'fornitori2' => ($allData["fornitori2"] != null) ? $allData["fornitori2"] : 0,
                'alertFornitori' => ($allData['alertFornitori'] != null) ? $allData["alertFornitori"] : 0,
                'created_at' => $now,
            ]);

            return response()->json([
                'error' => false,
                'Message' => "Analisi effettuata correttamente"
            ]);
        } else {
            DB::table('basic')->where('bilancio_id', '=', $idBilancio)->update([
                'bilancio_id' => ($allData["idBilancio"] != null) ? $allData["idBilancio"] : 0,
                'DSCR' => ($allData["DSCR"] != null) ? $allData["DSCR"] : 0,
                'alertDSCR' => ($allData['alertDSCR'] != null) ? $allData["alertDSCR"] : 0,
                'DSCRDate' => ($allData["DSCRDate"] != null) ? $allData["DSCRDate"] : 0,
                'DSCRdispLiquida' => ($allData["DSCRdispLiquida"] != null) ? $allData["DSCRdispLiquida"] : 0,
                'entrataDSCRCFmese1' => ($allData["entrataDSCRCFmese1"] != null) ? $allData["entrataDSCRCFmese1"] : 0,
                'entrataDSCRCFmese2' => ($allData["entrataDSCRCFmese2"] != null) ? $allData["entrataDSCRCFmese2"] : 0,
                'entrataDSCRCFmese3' => ($allData["entrataDSCRCFmese3"] != null) ? $allData["entrataDSCRCFmese3"] : 0,
                'entrataDSCRCFmese4' => ($allData["entrataDSCRCFmese4"] != null) ? $allData["entrataDSCRCFmese4"] : 0,
                'entrataDSCRCFmese5' => ($allData["entrataDSCRCFmese5"] != null) ? $allData["entrataDSCRCFmese5"] : 0,
                'entrataDSCRCFmese6' => ($allData["entrataDSCRCFmese6"] != null) ? $allData["entrataDSCRCFmese6"] : 0,
                'uscitaDSCRCFmese1' => ($allData["uscitaDSCRCFmese1"] != null) ? $allData["uscitaDSCRCFmese1"] : 0,
                'uscitaDSCRCFmese2' => ($allData["uscitaDSCRCFmese2"] != null) ? $allData["uscitaDSCRCFmese2"] : 0,
                'uscitaDSCRCFmese3' => ($allData["uscitaDSCRCFmese3"] != null) ? $allData["uscitaDSCRCFmese3"] : 0,
                'uscitaDSCRCFmese4' => ($allData["uscitaDSCRCFmese4"] != null) ? $allData["uscitaDSCRCFmese4"] : 0,
                'uscitaDSCRCFmese5' => ($allData["uscitaDSCRCFmese5"] != null) ? $allData["uscitaDSCRCFmese5"] : 0,
                'uscitaDSCRCFmese6' => ($allData["uscitaDSCRCFmese6"] != null) ? $allData["uscitaDSCRCFmese6"] : 0,
                'rimborsoDSCRmese1' => ($allData["rimborsoDSCRmese1"] != null) ? $allData["rimborsoDSCRmese1"] : 0,
                'rimborsoDSCRmese2' => ($allData["rimborsoDSCRmese2"] != null) ? $allData["rimborsoDSCRmese2"] : 0,
                'rimborsoDSCRmese3' => ($allData["rimborsoDSCRmese3"] != null) ? $allData["rimborsoDSCRmese3"] : 0,
                'rimborsoDSCRmese4' => ($allData["rimborsoDSCRmese4"] != null) ? $allData["rimborsoDSCRmese4"] : 0,
                'rimborsoDSCRmese5' => ($allData["rimborsoDSCRmese5"] != null) ? $allData["rimborsoDSCRmese5"] : 0,
                'rimborsoDSCRmese6' => ($allData["rimborsoDSCRmese6"] != null) ? $allData["rimborsoDSCRmese6"] : 0,
                'agenziaEntrate1' => ($allData["agenziaEntrate1"] != null) ? $allData["agenziaEntrate1"] : 0,
                'agenziaEntrate3' => ($allData["agenziaEntrate3"] != null) ? $allData["agenziaEntrate3"] : 0,
                'agenziaEntrate2' => ($allData["agenziaEntrate2"] != null) ? $allData["agenziaEntrate2"] : 0,
                'agenziaEntrate4' => ($allData["agenziaEntrate4"] != null) ? $allData["agenziaEntrate4"] : 0,
                'alertAgenziaEntrate' => ($allData['alertAgenziaEntrate'] != null) ? $allData["alertAgenziaEntrate"] : 0,
                'INPS1' => ($allData["INPS1"] != null) ? $allData["INPS1"] : 0,
                'INPS2' => ($allData["INPS2"] != null) ? $allData["INPS2"] : 0,
                'INPS3' => ($allData["INPS3"] != null) ? $allData["INPS3"] : 0,
                'alertINPS' => ($allData['alertINPS'] != null) ? $allData["alertINPS"] : 0,
                'riscossione' => ($allData["riscossione"] != null) ? $allData["riscossione"] : 0,
                'alertRiscossione' => ($allData['alertRiscossione'] != null) ? $allData["alertRiscossione"] : 0,
                'retribuzioni1' => ($allData["retribuzioni1"] != null) ? $allData["retribuzioni1"] : 0,
                'retribuzioni2' => ($allData["retribuzioni2"] != null) ? $allData["retribuzioni2"] : 0,
                'retribuzioni3' => ($allData["retribuzioni3"] != null) ? $allData["retribuzioni3"] : 0,
                'alertRetribuzioni' => ($allData['alertRetribuzioni'] != null) ? $allData["alertRetribuzioni"] : 0,
                'fornitori1' => ($allData["fornitori1"] != null) ? $allData["fornitori1"] : 0,
                'fornitori2' => ($allData["fornitori2"] != null) ? $allData["fornitori2"] : 0,
                'alertFornitori' => ($allData['alertFornitori'] != null) ? $allData["alertFornitori"] : 0,
                'updated_at' => $now,
            ]);

            return response()->json([
                'error' => false,
                'Message' => "Analisi aggiornata correttamente"
            ]);
        }
    }
}
