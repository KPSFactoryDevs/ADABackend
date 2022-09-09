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

            $bilancioHelper = new BilanciHelper;
            $calcoloDSCR = $bilancioHelper->getCalcoloDSCR($allData);
            $dataBasic = $bilancioHelper->saveAnalisiBasicToDB($allData, $idBilancio);
  
            if($allData['DSCR'] == 1 && $calcoloDSCR == "Attenzione, alcuni campi sono vuoti, compila tutti i campi.") {
                return response()->json([
                    'error' => false,
                    'Message' => $dataBasic['Message'],
                    'DSCRAlert' => $dataBasic['Alert'],
                    'result' => "Dati Mancanti",
                ], 202);
            } elseif($allData['DSCR'] == 1) {
                return response()->json([
                    'error' => false,
                    'Message' => $dataBasic['Message'],
                    'DSCRAlert' => $dataBasic['Alert'],
                    'DSCRResult' => $calcoloDSCR,
                ], 200);
            } elseif ($allData['DSCR'] == 0) {
                return response()->json([
                    'error' => false,
                    'Message' => $dataBasic['Message'],
                    'DSCRAlert' => $dataBasic['Alert'],
                ], 200);
            }         
    }
}
