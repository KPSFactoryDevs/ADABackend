<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Analisi;
use App\Models\Bilanci;
use App\Models\indici;
use App\Models\Document;
use App\Models\range;
use App\Models\cr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\CentraleRischi\CrExtractorHelper;
use App\Helpers\Allerta\AllertaHelper;
use App\Helpers\Bilanci\BilanciHelper;
use DateTime;
use Auth;

class AllertaController extends Controller
{


    public function allertaGeneral($id, $idCr, $userId)
    {
        if (cr::where('document_id', $idCr)->get()->count() == 0 || !isset($idCr)) {
            return response()->json([
                'error' => true,
                'message' => 'Non è stata trovata nessuna Centrale Rischi'
            ], 400);
        }

        if (Document::where('id', $id)->where('type', 'bilancio')->get()->count() == 0 || !isset($id)) {
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
        $bilancioData = $bilancioHelper->getIndexesForBalanceTaxonomy($id, false, false, false, $userId);

        $valutazioneBilancio = $bilancioHelper->valutazioneIndici($bilancioData['Indici']['Advanced'], 'Comemrcio', date('Y'));

        $bilancioData['ValutazioneGenerale'] = $valutazioneBilancio;

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



        $ASISfinalScore = $allertaHelper->getAsIsFinalScore($bilancioData['ValutazioneGenerale'], $scoreCR, $scoreASIS);
        $getScoreHelper = $allertaHelper->getScores($punteggioCR, $bilancioData['ValutazioneGenerale'], $scoreASIS, $ASISfinalScore, $scoreFL);

        return response()->json([
            'error' => false,
            'pageData' => [
                'scoreCR' => number_format($scoreCR, 2, ',', '.'),
                'crAlerts' => $alerts,
                'arrayQuestionarioAsIs' => $arrayQuestionario,  // questionario per document id e bilancio id
                'arrayForwardLookingToBe' => $arrayForwardLooking,
                'bilancioData' => $bilancioData,
                'FinalScore' => $getScoreHelper['FinalScore'],
                'ValutazioneGeneraleBilancio' => $bilancioData['ValutazioneGenerale']
            ],
            'GeneralScore' => [
                'Giudizio Centrale Rischi' => $getScoreHelper['resultCentraleRischi'],
                'Giudizio_Bilancio' => $getScoreHelper['resultAnalisiBilancio'],
                'Minacce rapporti commerciali' => $getScoreHelper['resultMinacceRapportiCommerciali'],
                'Minacce gestione aziendale' => $getScoreHelper['resultMinacceGestioneAziendale'],
                'Minacce da eventi pregiudizievoli' => $getScoreHelper['resultMinacceEventiPregiudizievoli'],
                'Minacce erariali e rischi caratteristici' => $getScoreHelper['resultMinacceRischiCaratteristici'],
                'Profilo rischio AS IS' => $getScoreHelper['ASISScore'],
                'Questionario TO BE' => $getScoreHelper['scoreGiudizioFL'],
            ],
        ]);
    }

    public function getQuestionari()
    {
        $allertaHelper = new AllertaHelper;
        $arrayQuestionario = $allertaHelper->getArrayQuestionarioAsIs(null, null);
        $arrayForwardLooking = $allertaHelper->getArrayQuestionarioToBe(null, null);

        return response()->json([
            'error' => false,
            'asIs' => $arrayQuestionario,
            'toBe' => $arrayForwardLooking,
        ]);
    }

    public function questionarioSistemaAllerta(Request $request)
    {

        $userId = Auth::id();
        $arrayQuestionario = $request->questionario;

        DB::table('user_questionari')->where('user_id', $userId)->where('type', 'asis')->delete();

        foreach ($arrayQuestionario as $domanda => $risposta) {
            DB::table('user_questionari')->insert([
                'user_id' => $userId,
                'type' => 'asis',
                'result' => $risposta['response'] ?? '',
                'parameter' => $domanda,
                'details' => $risposta['details'] ?? '',
                'date' => date("Y/m/d")
            ]);
        }

        return response()->json([
            'error' => false,
            'data' => 'Dati inviati correttamente'
        ], 200);
    }


    public function forwardLooking(Request $request)
    {

        if (!isset($request->forwardlooking)) {
            return response()->json([
                'error' => true,
                'data' => 'Dati mancanti',
            ], 400);
        }

        $userId = Auth::id();
        $arrayForwarLooking = $request->forwardlooking;
        unset($arrayForwarLooking['_token']);

        DB::table('user_questionari')->where('user_id', $userId)->where('type', 'tobe')->delete();

        foreach ($arrayForwarLooking as $index => $singleAnswer) {
            DB::table('user_questionari')->insert([
                'user_id' => $userId,
                'type' => 'tobe',
                'parameter' => $index,
                'result' => $singleAnswer ?? '',
                'date' => date("Y/m/d")
            ]);
        }

        return response()->json([
            'error' => false,
            'data' => 'Dati inviati correttamente',
        ], 200);
    }

}
