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
            $bilancioHelper->calculateRiscossione($allData);

            if(isset($calcoloDSCR['error']) && $calcoloDSCR['error'] == true) {
                return response()->json([
                    'error' => false,
                    'Message' => $dataBasic['Message'],
                    'DSCRAlert' => $dataBasic['AlertDSCR'],
                    'DSCRResult' => "Attenzione, alcuni campi sono vuoti, compila tutti i campi.",
                ], 400);
            }

            return response()->json([
                    'error' => false,
                    'Message' => $dataBasic['Message'],
                    'DSCRAlert' => $dataBasic['AlertDSCR'],
                    'DSCRResult' => $calcoloDSCR,
                ], 200);
        
    }
}
