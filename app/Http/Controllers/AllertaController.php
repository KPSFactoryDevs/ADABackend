<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Analisi;
use App\Models\Bilanci;
use App\Models\indici;
use App\Models\range;
use App\Models\cr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\CentraleRischi\CrExtractorHelper;
use App\Helpers\Allerta\AllertaHelper;
use App\Helpers\Bilanci\BilanciHelper;
use DateTime;

class AllertaController extends Controller
{

    public function questionarioSistemaAllerta(Request $request)
    {

        $documentId = $request->document_id;
        $bilancioId = $request->bilancio_id;
        $arrayQuestionario = $request->questionario;


        $questionarioAsIs = DB::table('questionario')->where('document_id', $documentId)->where('bilancio_id', $bilancioId)->get();

        if (count($questionarioAsIs) > 0) {
            DB::table('questionario')->where('document_id', $documentId)->where('bilancio_id', $bilancioId)->delete();
        }

        foreach ($arrayQuestionario as $domanda => $risposta) {
            DB::table('questionario')->insert([
                'result' => $risposta['response'],
                'parameter' => $domanda,
                'details' => $risposta['details'],
                'date' => date("Y/m/d"),
                'document_id' => $documentId,
                'bilancio_id' =>  $bilancioId
            ]);
        }

        return response()->json([
            'error' => false,
            'data' => 'Dati inviati correttamente'
        ], 200);
    }


    public function forwardLooking(Request $request)
    {
        $documentId = $request->document_id;
        $bilancioId = $request->bilancio_id;
        $arrayForwarLooking = $request->forwardlooking;

        unset($arrayForwarLooking['_token']);

        foreach ($arrayForwarLooking as $index => $singleAnswer) {
            DB::table('forwardlooking')->insert([
                'question' => $index,
                'answer' => $singleAnswer,
                'date' => date("Y/m/d"),
                'document_id' => $documentId,
                'bilancio_id' => $bilancioId
            ]);
        }

        return response()->json([
            'error' => false,
            'data' => 'Dati inviati correttamente',
        ], 200);
    }


    public function allertaGeneral($id, $idCr)
    {
        if (cr::where('document_id', $idCr)->get()->count() == 0 || !isset($idCr)) {
            return response()->json([
                'error' => true,
                'message' => 'Non è stata caricata nessuna Centrale Rischi'
            ], 400);
        }

        if (!Bilanci::findOrFail($id) || !isset($id)) {
            return response()->json([
                'error' => true,
                'message' => 'Non è stato trovato nessun Bilancio'
            ], 400);
        }


        $allertaHelper = new AllertaHelper;
        $allertaHelper->setDocumentId($idCr);
        $getDate = $allertaHelper->getDate($idCr);

        $crHelper = new CrExtractorHelper;
        $crHelper->setPeriod($getDate['periods']);
        $crHelper->setDocumentId($idCr);

        $allertaHelper->setCrExtractor($crHelper);

        $bilancioHelper = new BilanciHelper;

        $ASISfinalScore = false;
        $scoreASIS = array('1' => 0, '2' => 0, '3' => 0, '4' => 0);
        $scoreFL = array('Giudizio' => '', 'Valore' => '0');

        $periods = $getDate['periods'];
        $latestYear = $getDate['latestYear'];
        $latestMonth = $getDate['latestMonth'];
        $categories = $getDate['categories'];
        $upperBoundDate = $getDate['upperBoundDate'];
        $lowerBoundDate = $getDate['lowerBoundDate'];
        $lastYearPeriod = $periods;
        $banks = cr::select('nome_banca')->where('document_id', $idCr)->where('date', '>=', $lowerBoundDate->format('Y-m-d'))->where('date', '<=', $upperBoundDate->format('Y-m-d'))->distinct()->get()->pluck('nome_banca')->toArray();




        $trimestrePeriod = $allertaHelper->getTrimestrePeriod($periods);
        $triennioPeriod = $allertaHelper->getTriennioPeriod($periods, $banks);
        $crHelper->setPeriod($lastYearPeriod);
        $sofferenze = $crHelper->getSofferenze($banks);
        $sconfini = $crHelper->getTotaleSconfini($banks);
        $countBanks = $crHelper->getCountBanks($banks);
        $creditiPassatiPerdita = $crHelper->getCreditiPassatiPerdita($banks);
        $scoreCR = $crHelper->getScoring($banks, $countBanks, $sconfini, $sofferenze, $creditiPassatiPerdita);

        // ALERTS CENTRALE RISCHI GENERAL
        $alerts = array();
        $alerts['1'] = $allertaHelper->getAnalisiCRUno($banks);
        $alerts['2'] = $allertaHelper->getAnalisiCRDue($banks);
        $alerts['3'] = $allertaHelper->getAnalisiCRTre($banks);
        $alerts['4'] = $allertaHelper->getAnalisiCRQuattro($triennioPeriod, $trimestrePeriod, $latestYear, $latestMonth, $categories);
        $alerts['5'] = $allertaHelper->getAnalisiCRCinque($periods);
        $alerts['6'] = $allertaHelper->getAnalisiCRSei($lastYearPeriod, array('RISCHI AUTOLIQUIDANTI'), $banks);
        $alerts['7'] = $allertaHelper->getAnalisiCRSette($lastYearPeriod);
        $alerts['8'] = $allertaHelper->getAnalisiCROtto($lastYearPeriod, $latestYear, $latestMonth, $trimestrePeriod, $triennioPeriod);
        $alerts['9'] = $allertaHelper->getAnalisiCRNove($lastYearPeriod, $latestYear, $latestMonth);
        $alerts['10'] = $allertaHelper->getAnalisiCRDieci($triennioPeriod, $latestYear, $latestMonth);
        $alerts['11'] = $allertaHelper->getAnalisiCRUndici($triennioPeriod, $trimestrePeriod, $lastYearPeriod, $latestYear, $latestMonth, array('RISCHI A REVOCA'));
        $alerts['12'] = $allertaHelper->getAnalisiCRDodici($triennioPeriod, $trimestrePeriod, $lastYearPeriod, $latestYear, $latestMonth, array('RISCHI AUTOLIQUIDANTI', 'RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI'), $banks);
        $alerts['13'] = $allertaHelper->getAnalisiCRTredici($triennioPeriod, $trimestrePeriod, $lastYearPeriod, $latestYear, $latestMonth, $categories, $banks);
        $alerts['14'] = $allertaHelper->getAnalisiCRQuattordici($banks);
        $alerts['15'] = $allertaHelper->getAnalisiCRQuindici($banks);
        $alerts['16'] = $allertaHelper->getAnalisiCRSedici($banks);
        $punteggioCR = $allertaHelper->getPunteggioCR($alerts);

        $arrayQuestionario = $allertaHelper->getArrayQuestionarioAsIs($id, $idCr);
        $arrayForwardLooking = $allertaHelper->getArrayQuestionarioToBe($id, $idCr);



        if (count($arrayQuestionario) > 0) {
            $scoreASIS = $allertaHelper->valutazioneQuestionarioQualitativo($arrayQuestionario);
        }


        if (count($arrayForwardLooking) == 12) {
            $scoreFL = $allertaHelper->valutazioneFL($arrayForwardLooking);
        }

        $bilancioData = $bilancioHelper->getAnalisiBilancio($id);


        $ASISfinalScore = $allertaHelper->getAsIsFinalScore($bilancioData['AnalisiAdvanced'], $scoreCR, $scoreASIS);
        $getScoreHelper = $allertaHelper->getScores($punteggioCR, $bilancioData['AnalisiAdvanced'], $scoreASIS, $ASISfinalScore, $scoreFL);



        $arrayScoring = [
            'error' => false,
            'pageData' => [
                'scoreCR' => $scoreCR,
                'crAlerts' => $alerts,
                'arrayQuestionarioAsIs' => $arrayQuestionario,  // questionario per document id e bilancio id
                'arrayForwardLookingToBe' => $arrayForwardLooking,
                'bilancioData' => $bilancioData
            ],
            'GeneralScore' => [
                'Giudizio_CR' => $getScoreHelper['resultCentraleRischi'],
                'Giudizio_Bilancio' => $getScoreHelper['resultAnalisiBilancio'],
                'Minacce rapporti commerciali' => $getScoreHelper['resultMinacceRapportiCommerciali'],
                'Minacce gestione aziendale' => $getScoreHelper['resultMinacceGestioneAziendale'],
                'Minacce da eventi pregiudizievoli' => $getScoreHelper['resultMinacceEventiPregiudizievoli'],
                'Minacce erariali e rischi caratteristici' =>  $getScoreHelper['resultMinacceRischiCaratteristici'],
                'Profilo rischio AS IS' => $getScoreHelper['ASISScore'],
                'Questionario TO BE' => $getScoreHelper['scoreGiudizioFL']
            ],
        ];



        return response()->json(
            $arrayScoring,
            200
        );
    }
}
