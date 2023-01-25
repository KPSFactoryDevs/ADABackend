<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Analisi;
use Illuminate\Http\Request;
use Exception;
use App\Helpers\Bilanci\BilanciCalculationsHelperAdvanced;
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
        $bilancioHelper = new BilanciCalculationsHelperAdvanced;

        $saveDscrData = $bilancioHelper->saveDscrAnalisi($dscrReq->all());

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
        $bilancioHelper = new BilanciCalculationsHelperAdvanced;
        $saveAgenziaEntrate = $bilancioHelper->saveAgenziaEntrate($agenziaEntrateReq->all());
 
       if($saveAgenziaEntrate === 'Dati AgenziaEntrate salvati correttamente') {
            $agenziaEntrate = $bilancioHelper->calculateAgenziaEntrate($agenziaEntrateReq->all());

            return response()->json([
                'error' => false,
                'result' => $agenziaEntrate["agenziaEntrate4"],
                'alert' => $agenziaEntrate['alert']
            ]);
        } else {
            return $saveAgenziaEntrate;
        }
    }

    public function getInpsAlert(Request $inpsReq)
    {
        $bilancioHelper = new BilanciCalculationsHelperAdvanced;
        $checkInpsData = $bilancioHelper->saveInps($inpsReq->all());
       
        if($checkInpsData === 'Dati Inps salvati correttamente') {
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
        $bilancioHelper = new BilanciCalculationsHelperAdvanced;
        $checkRetribuzioniData = $bilancioHelper->saveRetribuzione($retribuzioniReq->all());

        if($checkRetribuzioniData === 'Dati retribuzioni salvati correttamente') {
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
        $bilancioHelper = new BilanciCalculationsHelperAdvanced;
        $checkFornitoriData = $bilancioHelper->saveFornitori($fornitoriReq->all());
       
        if($checkFornitoriData === 'Dati fornitori salvati correttamente') {
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
        $bilancioHelper = new BilanciCalculationsHelperAdvanced;
        $checkRiscossioneData = $bilancioHelper->saveRiscossione($riscossioneReq->all());
       
        if($checkRiscossioneData === 'Dati riscossione salvati correttamente') { 
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
