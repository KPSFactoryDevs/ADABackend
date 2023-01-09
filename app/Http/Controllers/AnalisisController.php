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
                    'DSCRError' => true
                ], 200);
            }

            return response()->json([
                    'error' => false,
                    'Message' => $dataBasic['Message'],
                    'DSCRAlert' => $dataBasic['AlertDSCR'],
                    'DSCRResult' => $calcoloDSCR,
                ], 200);
        
    }

    public function getDSCRAnalisi(Request $dscrReq)
    {
        $bilancioHelper = new BilanciHelper;

        $saveDscrData = $bilancioHelper->saveDscrAnalisi($dscrReq->idBilancio, $dscrReq->all());

        if($saveDscrData === 'Dati DSCR salvati correttamente') {
            $calcoloDSCR = $bilancioHelper->getCalcoloDSCR($dscrReq->all());

            if ($calcoloDSCR > 1) {
                $dscrData = 'Azienda non a rischio';
            } else {
                $dscrData = 'Azienda a rischio';
            }

            return response()->json([
                'error' => false,
                'result' => $calcoloDSCR,
                'alert' => $dscrData
            ]);
        } else {
            return $saveDscrData;
        }
    }

    public function getAgenziaEntrateAlert(Request $agenziaEntrateReq)
    {
        $bilancioHelper = new BilanciHelper;
        $checkAgenziaEntrateData = $bilancioHelper->checkAgenziaEntrateData($agenziaEntrateReq->all());
       
        if($checkAgenziaEntrateData === null) {
            $agenziaEntrate = $bilancioHelper->calculateAgenziaEntrate($agenziaEntrateReq->all());

            return response()->json([
                'error' => false,
                'result' => $agenziaEntrate["agenziaEntrate4"]['agenziaEntrate4'],
                'alert' => $agenziaEntrate['alert']
            ]);
        } else {
            return $checkAgenziaEntrateData;
        }
    }

    public function getInpsAlert(Request $inpsReq)
    {
        $bilancioHelper = new BilanciHelper;
        $checkInpsData = $bilancioHelper->checkInpsData($inpsReq->all());
       
        if($checkInpsData === null) {
            $inps = $bilancioHelper->calcoloINPS($inpsReq->all());

            return response()->json([
                'error' => false,
                'result' => $inps["inps3"],
                'alert' => $inps['alert']
            ]);
        } else {
            return $checkInpsData;
        }
    }

    public function getRetribuzioniAlert(Request $retribuzioniReq)
    {
        $bilancioHelper = new BilanciHelper;
        $checkRetribuzioniData = $bilancioHelper->checkRetribuzioniData($retribuzioniReq->all());
       
        if($checkRetribuzioniData === null) {
            $retribuzioni = $bilancioHelper->calculateRetribuzione($retribuzioniReq->all());

            return response()->json([
                'error' => false,
                'result' => $retribuzioni["retribuzioni3"],
                'alert' => $retribuzioni['alert']
            ]);
        } else {
            return $checkRetribuzioniData;
        } 
    }

    public function getFornitoriAlert(Request $fornitoriReq)
    {
        $bilancioHelper = new BilanciHelper;
        $checkFornitoriData = $bilancioHelper->checkFornitoriData($fornitoriReq->all());
       
        if($checkFornitoriData === null) {
            $fornitori = $bilancioHelper->calculateFornitori($fornitoriReq->all());

            return response()->json([
                'error' => false,
                'alert' => $fornitori
            ]);
        } else {
            return $checkFornitoriData;
        }  
    }

    public function getRiscossione(Request $riscossioneReq)
    {
        $bilancioHelper = new BilanciHelper;
        $checkRiscossioneData = $bilancioHelper->checkRiscossioneData($riscossioneReq->all());
       
        if($checkRiscossioneData === null) { 
            $riscossione = $bilancioHelper->calculateRiscossione($riscossioneReq->all());

            return response()->json([
                'error' => false,
                'alert' => $riscossione
            ]);
        } else {
            return $checkRiscossioneData;
        }  
    }
}
