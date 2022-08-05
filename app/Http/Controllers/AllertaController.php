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
        $risposte = $request->all();

        $arrayRisposte = array();

        foreach ($risposte as $index => $value) {
            if (str_contains($index, '_desc')) {
                $exploded = explode('_', $index);
                if ($risposte[$exploded[0] . '_' . $exploded[1]] == "Si") {
                    $arrayRisposte[$exploded[1]]['Esito'] = 'Si';
                    $arrayRisposte[$exploded[1]]['Motivazione'] = $value;
                } else {
                    $arrayRisposte[$exploded[1]]['Esito'] = 'No';
                    $arrayRisposte[$exploded[1]]['Motivazione'] = $value == null ? '' : $value;
                }
            }
        }

        DB::table('questionario')->truncate();

        foreach ($arrayRisposte as $data => $values) {
            DB::table('questionario')->insert(['result' => $values['Esito'], 'parameter' => $data, 'details' => $values['Motivazione'], 'date' => date("Y/m/d")]);
        }

        return back();
    }


    public function forwardLooking(Request $request)
    {
        $risposte = $request->all();

        unset($risposte['_token']);

        DB::table('forwardLooking')->truncate();

        foreach ($risposte as $index => $singleAnswer) {
            DB::table('forwardLooking')->insert([
                ['question' => $index, 'answer' => $singleAnswer, 'date' => date("Y/m/d")]
            ]);
        }

        return back();
    }

    public function index()
    {
        $accounts =  Account::all();
        return view('allerta.index', compact(['accounts']));
    }


    public function getAllScores()
    {
        // $allScores = json_decode(file_get_contents('https://fintechdemo.kpsfactory.com/assets/json/scores.json'));

        $ultimoBilancio = Bilanci::latest('id')->first();

        $bilancioData = $this->analisiBilancio($ultimoBilancio->id);
        $scoreBilancioAllerta = round($bilancioData['Giudizi']['Score'] * 10, 1) . '/' . "10";

        // Score centrale rischi

        $crs = cr::select('anno', 'mese', 'date')->distinct()->orderBy('date', 'asc')->get();

        for ($i = (count($crs) - 12 >= 0) ? count($crs) - 12 : 0; $i < count($crs); $i++) {
            $periods[$crs[$i]->anno][$crs[$i]->mese] = null;
        }

		if(!isset($periods)) {
			    $scoresClean = array(
            array("title" => "Scoring Bilancio", "iconClass" => "bx-copy-alt", "description" => "N/A"),
            array("title" => "Scoring Centrale Rischi", "iconClass" => "bx-archive-in", "description" => "N/A"),
            array("title" => "Giudizio Sistema Allerta", "iconClass" => "bx-purchase-tag-alt", "description" => "N/A"),
        );

        return response()->json(
            $scoresClean
        );
		}

        $latestYear = array_key_last($periods);
        $latestMonth = array_key_last($periods[$latestYear]);

        $crData = array();

        $categories = array(
            'RISCHI A SCADENZA',
            'RISCHI AUTOLIQUIDANTI',
            'RISCHI A REVOCA',
        );

        $earliestYear = array_key_first($periods);
        $earliestMonth = array_key_first($periods[$earliestYear]);

        $upperBoundDate = new DateTime((cr::select('date')->where('anno', $earliestYear)->where('mese', $earliestMonth)->get()->first())->date);
        $lowerBoundDate = new DateTime($upperBoundDate->format('Y-m-d'));
        $lowerBoundDate = $lowerBoundDate->modify('-11 months');

        $banks = array();

        foreach (cr::select('nome_banca')->where('date', '>=', $lowerBoundDate->format('Y-m-d'))->where('date', '<=', $upperBoundDate->format('Y-m-d'))->distinct()->get()->toArray() as $label => $nomeBanca) {
            $banks[] = $nomeBanca["nome_banca"];
        }

        $allertaHelper = new AllertaHelper;
        $crExtractorHelper = new CrExtractorHelper;

        $allertaHelper->setCrExtractor($crExtractorHelper);

        $trimestrePeriod = $allertaHelper->getTrimestrePeriod($periods);
        $lastYearPeriod = $periods;

        $triennioPeriod = $allertaHelper->getTriennioPeriod($periods, $banks);
        $crExtractorHelper->setPeriod($lastYearPeriod);
        $scoreCR = $crExtractorHelper->getScoring($banks);
        $alerts = array();

        $alerts['1'] = $allertaHelper->getAnalisiCRUno($banks);

        $alerts['2'] = $allertaHelper->getAnalisiCRDue($banks);

        $alerts['3'] = $allertaHelper->getAnalisiCRTre($banks);

        $alerts['4'] = $allertaHelper->getAnalisiCRQuattro($triennioPeriod, $trimestrePeriod, $latestYear, $latestMonth, $categories);

        $alerts['5'] = $allertaHelper->getAnalisiCRCinque($periods);

        // $allertaHelper->getAnalisiCRCinqueTest($periods);

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

        $scoreCr = round($punteggioCR * 10, 1) . '/' . "10";



        // SCORE SISTEMA ALLERTA


        $arrayQuestionario = array();

        $questionario = DB::table('questionario')->get();
        foreach ($questionario as $item => $data) {
            $arrayQuestionario[$data->parameter]['Result'] = $data->result;
            $arrayQuestionario[$data->parameter]['Details'] = $data->details == null ? '' : $data->details;
        }

        // dd($questionario);

        $arrayForwardLooking = array();

        $forwardLooking = DB::table('forwardLooking')->get();
        foreach ($forwardLooking as $item => $data) {
            $arrayForwardLooking[$data->question] = $data->answer;
        }

        if (count($arrayQuestionario) > 0) {
            $scoreASIS = $this->valutazioneQuestionarioQualitativo($arrayQuestionario);
        } else {
            $scoreASIS = array('3' => 0, '4' => 0, '5' => 0, '6' => 0);
        }
        if (count($arrayForwardLooking) == 12) {
            $scoreFL = $this->valutazioneFL($arrayForwardLooking);
        } else {
            $arrayForwardLooking = array(
                "forwardLooking1" => 0,
                "forwardLooking2" => 0,
                "forwardLooking3" => 0,
                "forwardLooking4" => 0,
                "forwardLooking5" => 0,
                "forwardLooking6" => 0,
                "forwardLooking7" => 0,
                "forwardLooking8" => 0,
                "forwardLooking9" => 0,
                "forwardLooking10" => 0,
                "forwardLooking11" => 0,
                "forwardLooking12" => 0,
            );
            $scoreFL = array('Giudizio' => '', 'Valore' => '0');
        }


        if (Bilanci::count() != 0) {


            if (DB::table('questionario')->count() != 0) {
                $ASISfinalScore = array("Score" => ($bilancioData['Giudizi']['Score'] * 0.25) + ($scoreCR * 0.25) + ($scoreASIS['3'] * 0.1) + ($scoreASIS['4'] * 0.1) + ($scoreASIS['5'] * 0.15) + ($scoreASIS['6'] * 0.15));

                $rangeGiudizi = array(
                    0 => array("Min" => 0, "Max" => 0.14, "Giudizio" => "Default"),
                    1 => array("Min" => 0.14, "Max" => 0.28, "Giudizio" => "Situazione Grave"),
                    2 => array("Min" => 0.28, "Max" => 0.42, "Giudizio" => "Alert"),
                    3 => array("Min" => 0.42, "Max" => 0.56, "Giudizio" => "Rischio alert"),
                    4 => array("Min" => 0.56, "Max" => 0.70, "Giudizio" => "Fragilità elevata"),
                    5 => array("Min" => 0.70, "Max" => 0.85, "Giudizio" => "Fragilità"),
                    6 => array("Min" => 0.85, "Max" => 1, "Giudizio" => "Solidità")
                );

                foreach ($rangeGiudizi as $index => $ranges) {
                    if ($ASISfinalScore["Score"] >= $ranges["Min"] && $ASISfinalScore["Score"] < $ranges["Max"]) {
                        $ASISfinalScore["Giudizio"] = $ranges['Giudizio'];
                        $ASISfinalScore["Index"] = $index;
                    }
                }

                $generalScore = array();
                $generalScore['Score'] = 0;
                if ($scoreASIS['6'] < 0.75) {
                    $generalScore["Giudizio"] = $rangeGiudizi[$ASISfinalScore["Index"] - 1]['Giudizio'];
                    $generalScore["Index"] = $ASISfinalScore["Index"] - 1;
                } else {
                    $generalScore = $ASISfinalScore;
                }


                if ($scoreFL['Giudizio'] == 'Miglioramento') {
                    if ($generalScore["Index"] != 6) {
                        $generalScore["Giudizio"] = $rangeGiudizi[$ASISfinalScore["Index"] + 1]['Giudizio'];
                        $generalScore["Index"] = $generalScore["Index"] + 1;
                    }
                } else if ($scoreFL['Giudizio'] == 'Peggioramento') {
                    if ($generalScore["Index"] != 0) {
                        $generalScore["Giudizio"] = $rangeGiudizi[$ASISfinalScore["Index"] - 1]['Giudizio'];
                        $generalScore["Index"] = $generalScore["Index"] - 1;
                    }
                }
            }
        }
	
        $scoreAllerta = /*round($generalScore['Index'], 1).'/'."10".' - '.*/$generalScore['Giudizio'];
 
        $scoresClean = array(
            array("title" => "Scoring Bilancio", "iconClass" => "bx-copy-alt", "description" => $scoreBilancioAllerta),
            array("title" => "Scoring Centrale Rischi", "iconClass" => "bx-archive-in", "description" => $scoreCr),
            array("title" => "Giudizio Sistema Allerta", "iconClass" => "bx-purchase-tag-alt", "description" => $scoreAllerta),
        );

        return response()->json(
            $scoresClean
        );
    }


    public function selezione(Request $request)
    {
        $idAzienda = $request->input('account_id');
        $account = Account::find($idAzienda);
        $bilanci = Bilanci::where('account_id', $idAzienda)->get();
        $cr = DB::table('centralerischi')
            ->where('account_id', $idAzienda)
            ->get();

        return view('allerta.selezione', compact('account', 'bilanci', 'cr'));
    }

    public function analisi(Request $request)
    {
        if (isset($questionario)) {
            return view('allerta.questionario');
        } else {
            $accountId = $request->input('account_id');
            $bilanci = $request->input('multiselectBilanci');
            $centralirischi = $request->input('multiselectCR');
            foreach ($centralirischi as $data) {
                $year = explode('_', $data)[0];
                $month = explode('_', $data)[1];
                $arrayCR[] = $this->analisiCR($year, $month);
            }

            foreach ($bilanci as $data => $value) {
                try {
                    $analisi = Analisi::where(['account_id' => $accountId, 'bilanci_id' => $value])->get();
                    if (count($analisi) == 0) {
                        Analisi::create(['account_id' => $accountId, 'bilanci_id' => $value]);
                    }
                    $id = $analisi[0]->id;

                    $supporto = $this->analisiBilancio($id);


                    $anni = (Bilanci::where('id', $value)->get(['current_year']))[0]->current_year;
                    $date = explode(' ', $anni);
                    $anno = date('Y', strtotime($date[0]));

                    $analisiCompleta[$anno]['dataAnalisis'] = $supporto['dataAnalisis'];
                    $analisiCompleta[$anno]['indiciBilancio'] = $supporto['indiciBilancio'];

                    foreach ($analisiCompleta as $anno => $array) {
                        $basic[$anno]['PN NEGATIVO'] = $array['dataAnalisis']['PN_NEGATIVO'];
                        $basic[$anno]['OF RICAVI'] = $array['dataAnalisis']['OF_RICAVI'];
                        $basic[$anno]['ADEGUATEZZA PATRIMONIALE'] = $array['dataAnalisis']['ADEGUATEZZA_PATRIMONIALE'];
                        $basic[$anno]['RITORNO LIQUIDO ATTIVO'] = $array['dataAnalisis']['RITORNO_LIQUIDO_ATTIVO'];
                        $basic[$anno]["LIQUIDITA'"] = $array['dataAnalisis']['LIQUIDITA'];
                        $basic[$anno]["INDEBITAMENTO PREVIDENZIALE TRIBUTARIO"] = $array['dataAnalisis']['INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO'];

                        $advanced[$anno]['PN NEGATIVO'] = $array['dataAnalisis']['PN_NEGATIVO'];
                        $advanced[$anno]['ANDAMENTO DEL FATTURATO'] = $array['dataAnalisis']['Andamento_Del_Fatturato'];
                        $advanced[$anno]['ANDAMENTO DEL MOL'] = $array['dataAnalisis']['ANDAMENTO_DEL_MOL'];
                        $advanced[$anno]['ROI'] = $array['dataAnalisis']['ROI'];
                        $advanced[$anno]['ROS'] = $array['dataAnalisis']['ROS'];
                        $advanced[$anno]['ROE'] = $array['dataAnalisis']['ROE'];
                        $advanced[$anno]['EBITDA FATTURATO'] = $array['dataAnalisis']['EBITDA_FATTURATO'];
                        $advanced[$anno]['ANDAMENTO DEI MEZZI PROPRI'] = $array['dataAnalisis']['Andamento_Dei_Mezzi_Propri'];
                        $advanced[$anno]['MARGINE STRUTTURA PRIMARIO'] = $array['dataAnalisis']['Margine_Struttura_Primario'];
                        $advanced[$anno]['MARGINE STRUTTURA SECONDARIO SEMPLIFICATO'] = $array['dataAnalisis']['Margine_Struttura_Secondario_Semplificato'];
                        $advanced[$anno]['MARGINE STRUTTURA SECONDARIO ORDINARIO'] = $array['dataAnalisis']['Margine_Struttura_Secondario_Ordinario'];
                        $advanced[$anno]['ATTIVITA A BREVE PASSIVITA A BREVE SEMPLIFICATO'] = $array['dataAnalisis']['Attivita_a_breve_Passività_a_Breve_Semplificato'];
                        $advanced[$anno]['ATTIVITA A BREVE PASSIVITA A BREVE ORDINARIO'] = $array['dataAnalisis']['Attivita_a_breve_Passività_a_Breve_Ordinario'];
                        $advanced[$anno]['ACID TEST SEMPLIFICATO'] = $array['dataAnalisis']['ACID_TEST_Semplificato'];
                        $advanced[$anno]['ACID TEST ORDINARIO'] = $array['dataAnalisis']['ACID_TEST_Ordinario'];
                        $advanced[$anno]['ACID TEST'] = $array['dataAnalisis']['AcidTest'];
                        $advanced[$anno]['AUTONOMIA FINANZIARIA'] = $array['dataAnalisis']['AUTONOMIA_FINANZIARIA'];
                        $advanced[$anno]['LIVELLO INVESTIMENTI AZIENDALI'] = $array['dataAnalisis']['LIVELLO_INVESTIMENTI_AZIENDALI'];
                        $advanced[$anno]['PFN EBITDA'] = $array['dataAnalisis']['PFN_EBITDA'];
                        $advanced[$anno]['PESO ONERI FINANZIARI'] = $array['dataAnalisis']['Peso_Oneri_Finanziari'];
                        $advanced[$anno]['COPERTURA LORDA DEGLI ONERI FINANZIARI'] = $array['dataAnalisis']['Copertura_Lorda_degli_Oneri_Finanziari'];
                        $advanced[$anno]['EBIT OF'] = $array['dataAnalisis']['EBIT_OF'];
                        $advanced[$anno]['COSTO DEL PERSONALE'] = $array['dataAnalisis']['Costo_del_personale'];
                        $advanced[$anno]['CF ATTIVO'] = $array['dataAnalisis']['CF_ATTIVO'];
                        $advanced[$anno]['INDICE DI INDEBITAMENTO'] = $array['dataAnalisis']['Indice_di_Indebitamento'];
                        $advanced[$anno]['SALDO DEBITI VS FISCO'] = $array['dataAnalisis']['SALDO_DEBITI_VS_FISCO'];
                    }
                } catch (Exception $exception) {
                    var_dump($exception->getMessage());
                    exit;
                    return back()->withInput()
                        ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
                }
            }
            return view('allerta.analisi', compact(['basic', 'advanced']));
        }
    }

    public function analisiBilancio($id)
    {

        $bilancio = Bilanci::findOrFail($id);

        $tipoAzienda = $bilancio->tipo_azienda;

        $attributes = $bilancio->getAttributes();
        $jsonData['current'] = json_decode($attributes['json_data'], true);
        $jsonData['prev'] = json_decode($attributes['json_data_prev'], true);
        $jsonData['anagrafic'] = json_decode($attributes['json_data_anag'], true);
        $jsonData['currentYear'] = $attributes['current_year'];
        $jsonData['prevYear'] = $attributes['prev_year'];
        $tipoAzienda = $attributes['tipo_azienda'];

        $bilancioJSON = json_decode($bilancio['json_data']);
        $bilancioJSONprev = json_decode($bilancio['json_data_prev']);
        $dataAnalisis = array();
        $righeUtilizzate = array();
        $imposteRedditoEsercizioImposteAnticipate = isset($bilancioJSON->ImposteRedditoEsercizioCorrentiDifferiteAnticipateImposteDifferiteAnticipate) ? $bilancioJSON->ImposteRedditoEsercizioCorrentiDifferiteAnticipateImposteDifferiteAnticipate : 0;

        if ($bilancio->provvisorio == 1) {
            $vociContoEconomico = array(
                "ValoreProduzioneRicaviVenditePrestazioni",
                "ValoreProduzioneVariazioniRimanenzeProdottiCorsoLavorazioneSemilavoratiFiniti",
                "ValoreProduzioneVariazioniLavoriCorsoOrdinazione",
                "ValoreProduzioneIncrementiImmobilizzazioniLavoriInterni",
                "ValoreProduzioneAltriRicaviProventiTotaleAltriRicaviProventi",
                "CostiProduzioneMateriePrimeSussidiarieConsumoMerci",
                "CostiProduzioneServizi",
                "CostiProduzioneGodimentoBeniTerzi",
                "CostiProduzionePersonaleTotaleCostiPersonale",
                "CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni",
                "CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci",
                "CostiProduzioneAccantonamentiRischi",
                "CostiProduzioneAltriAccantonamenti",
                "CostiProduzioneOneriDiversiGestione",
                "ProventiOneriFinanziariProventiPartecipazioniTotaleProventiPartecipazioni",
                "ProventiOneriFinanziariAltriProventiFinanziariTotaleAltriProventiFinanziari",
                "ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari",
                "ProventiOneriStraordinariProventiTotaleProventi",
                "ProventiOneriStraordinariOneriTotaleOneri",
            );

            $tmpPeriod = explode(' ', $bilancio->year);

            $periodStart = new DateTime($tmpPeriod[0]);

            $periodEnd = new DateTime($tmpPeriod[1]);

            $days = $periodEnd->diff($periodStart)->format("%a");

            $daysToYear = $days / 365;

            $bilancioJSON = (array)$bilancioJSON;

            $bilancioJSON['ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate'] = (float)$bilancioJSON['RisultatoPrimaImposte'] * 0.28;

            $bilancioJSON['UtilePerditaEsercizio'] = (float)$bilancioJSON['RisultatoPrimaImposte'] - (float)$bilancioJSON['ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate'];

            foreach ($vociContoEconomico as $tmp => $singolaVoce) {
                if (isset($bilancioJSON[$singolaVoce])) {
                    $bilancioJSON[$singolaVoce] = ($bilancioJSON[$singolaVoce]) * $daysToYear;
                }
            }

            $bilancioJSON = (object)$bilancioJSON;

            // dd($periodStart, $periodEnd, $daysToYear, $bilancioJSON);
        }

        $TotaleAttivo = (isset($bilancioJSON->TotaleAttivo) ? $bilancioJSON->TotaleAttivo : 0);
        $CostiProduzioneAltriAccantonamenti = (isset($bilancioJSON->CostiProduzioneAltriAccantonamenti) ? $bilancioJSON->CostiProduzioneAltriAccantonamenti : 0);
        $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni = (isset($bilancioJSON->CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni) ? $bilancioJSON->CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni : 0);
        $TotaleCreditiVersoSociVersamentiAncoraDovuti = (isset($bilancioJSON->TotaleCreditiVersoSociVersamentiAncoraDovuti) ? $bilancioJSON->TotaleCreditiVersoSociVersamentiAncoraDovuti : 0);
        $TotalePatrimonioNetto = (isset($bilancioJSON->TotalePatrimonioNetto) ? $bilancioJSON->TotalePatrimonioNetto : 0);
        $PN_NEGATIVO = $TotalePatrimonioNetto - $TotaleCreditiVersoSociVersamentiAncoraDovuti;
        $dataAnalisis['PATRIMONIO_NETTO'] = $PN_NEGATIVO * 100;
        $arrayConVoci['PATRIMONIO_NETTO'] = array('TotalePatrimonioNetto', 'TotaleCreditiVersoSociVersamentiAncoraDovuti');
        $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo : 0));
        $UtilePerditaEsercizio = (isset($bilancioJSON->UtilePerditaEsercizio) ? $bilancioJSON->UtilePerditaEsercizio : 0);

        // Valori bilancio
        // ### OF_RICAVI ###
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = (isset($bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari) ? $bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari : 0);
        $ValoreProduzioneRicaviVenditePrestazioni = (isset($bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni) ? $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni : 0);
        if($ValoreProduzioneRicaviVenditePrestazioni != 0) {
            $OF_RICAVI = number_format((float)($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari / $ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, '.', '');
        } else {
            $OF_RICAVI = "NON CALCOLABILE";
        }
        $dataAnalisis['OF_Fatturato'] = $OF_RICAVI . '%';
        $arrayConVoci['OF_Fatturato'] = array('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 'ValoreProduzioneRicaviVenditePrestazioni');

        // ### ADEGUATEZZA_PATRIMONIALE ###
        $TotaleDebiti = (isset($bilancioJSON->TotaleDebiti) ? $bilancioJSON->TotaleDebiti : 0);
        $PassivoRateiRisconti = (isset($bilancioJSON->PassivoRateiRisconti) ? $bilancioJSON->PassivoRateiRisconti : 0);
 
        $ADEGUATEZZA_PATRIMONIALE = number_format((float)($PN_NEGATIVO / ($TotaleDebiti + (float)$PassivoRateiRisconti)) * 100, 2, ',', '');
		
        $dataAnalisis['ADEGUATEZZA_PATRIMONIALE'] = $ADEGUATEZZA_PATRIMONIALE . '%';
        $arrayConVoci['ADEGUATEZZA_PATRIMONIALE'] = array('TotaleDebiti', 'PassivoRateiRisconti');

        $arrayConVoci['ADEGUATEZZA_PATRIMONIALE'] = array_merge($arrayConVoci['ADEGUATEZZA_PATRIMONIALE'], $arrayConVoci['PATRIMONIO_NETTO']);

        // ### RITORNO_LIQUIDO_ATTIVO ###
        $DebitiEsigibiliEntroEsercizioSuccessivo = isset($bilancioJSON->DebitiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiEsigibiliEntroEsercizioSuccessivo : 0;

        $TotaleDisponibilitaLiquide = (isset($bilancioJSON->TotaleDisponibilitaLiquide) ? $bilancioJSON->TotaleDisponibilitaLiquide : $val = (isset($bilancioJSONprev->TotaleDisponibilitaLiquide) ? $bilancioJSONprev->TotaleDisponibilitaLiquide : 0));
        $AttivoRateiRisconti = (isset($bilancioJSON->AttivoRateiRisconti) ? $bilancioJSON->AttivoRateiRisconti : 0);
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = (isset($bilancioJSON->TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni) ? $bilancioJSON->TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni : 0);
        $TotaleRimanenze = (isset($bilancioJSON->TotaleRimanenze) ? $bilancioJSON->TotaleRimanenze : 0);
        $TotaleCrediti = (isset($bilancioJSON->TotaleCrediti) ? $bilancioJSON->TotaleCrediti : 0);
        $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiImposteAnticipateTotaleImposteAnticipate = (isset($bilancioJSON->CreditiImposteAnticipateTotaleImposteAnticipate) ? $bilancioJSON->CreditiImposteAnticipateTotaleImposteAnticipate : 0);

        $TotaleCreditiEntroDodiciMesi = $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + $CreditiImposteAnticipateTotaleImposteAnticipate + $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo;

        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiAccontiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiAccontiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiAccontiEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo : 0);
        $PassivoRateiRisconti = (isset($bilancioJSON->PassivoRateiRisconti) ? $bilancioJSON->PassivoRateiRisconti : 0);


        $TotaleDebitiEntroDodiciMesi = $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + $DebitiAccontiEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo;

        //        dd($TotaleDebitiEntroDodiciMesi);

        //        dd($PassivoRateiRisconti,$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo,$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo,$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo,$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo,$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo,$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo,$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo,$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo,$DebitiAccontiEsigibiliEntroEsercizioSuccessivo,$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo,$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo,$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo,$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo,$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo);


        // dd($arrayConVoci);
        // ### LIQUIDITA ###
        $CostiProduzioneAccantonamentiRischi = (isset($bilancioJSON->CostiProduzioneAccantonamentiRischi) ? $bilancioJSON->CostiProduzioneAccantonamentiRischi : 0);
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari;
        $ValoreProduzioneRicaviVenditePrestazioni = $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni;
        if ($TotaleAttivo == 0) {
            $LIQUIDITA = 0;
            $dataAnalisis['LIQUIDITA'] = $LIQUIDITA . '%';
        } else {
            $LIQUIDITA = number_format((float)(($UtilePerditaEsercizio + (float)$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni + (float)$CostiProduzioneAccantonamentiRischi + (float)$CostiProduzioneAltriAccantonamenti) / (float)$TotaleAttivo) * 100, 2, ',', '');
            $dataAnalisis['LIQUIDITA'] = $LIQUIDITA . '%';
        }
        $arrayConVoci['LIQUIDITA'] = array('UtilePerditaEsercizio', 'CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 'CostiProduzioneAccantonamentiRischi', 'CostiProduzioneAltriAccantonamenti', 'TotaleAttivo');

        // ### INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO ###
        $DebitiDebitiTributariTotaleDebitiTributariCorrente = (isset($bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari) ? $bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari : 0);
        $DebitiDebitiTributariTotaleDebitiTributari = (isset($bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari) ? $bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari : 0);
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale = (isset($bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) ? $bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale : 0);
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari;
        $ValoreProduzioneRicaviVenditePrestazioni = $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni;
        if ($TotaleAttivo == 0) {
            $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO = number_format((($DebitiDebitiTributariTotaleDebitiTributari + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) / 1) * 100, 2, ',', '');
            $dataAnalisis['INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO'] = $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO . '%';
        } else {
            $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO = number_format((($DebitiDebitiTributariTotaleDebitiTributari + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) / $TotaleAttivo) * 100, 2, ',', '');
            $dataAnalisis['INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO'] = $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO . '%';
        }

        $arrayConVoci['INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO'] = array('DebitiDebitiTributariTotaleDebitiTributari', 'DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale', 'TotaleAttivo');

        //        dd($INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO);

        // INDICI ADVANCED

        //### Andamento del fatturato
        $ValoreProduzioneRicaviVenditePrestazioniCurr = (isset($bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni) ? $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni : 0);
        $ValoreProduzioneRicaviVenditePrestazioniPrev = (isset($bilancioJSONprev->ValoreProduzioneRicaviVenditePrestazioni) ? $bilancioJSONprev->ValoreProduzioneRicaviVenditePrestazioni : 0);
        if ($ValoreProduzioneRicaviVenditePrestazioniPrev == 0) {
            $AndamentoDelFatturato = number_format((float)(- (1 - (($ValoreProduzioneRicaviVenditePrestazioniCurr) / (1)))) * 100, 2, ',', '');
            $dataAnalisis['Andamento_del_fatturato'] = $AndamentoDelFatturato . '%';
        } else {
            $AndamentoDelFatturato = number_format((float)(- (1 - (($ValoreProduzioneRicaviVenditePrestazioniCurr) / ($ValoreProduzioneRicaviVenditePrestazioniPrev)))) * 100, 2, ',', '');
            $dataAnalisis['Andamento_del_fatturato'] = $AndamentoDelFatturato . '%';
        }

        $arrayConVoci['Andamento_del_fatturato'] = array('ValoreProduzioneRicaviVenditePrestazioniCurr', 'ValoreProduzioneRicaviVenditePrestazioniPrev');

        // ANDAMENTO DEL MOL
        $TotaleValoreProduzione = (isset($bilancioJSON->TotaleValoreProduzione) ? $bilancioJSON->TotaleValoreProduzione : 0);
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerci = (isset($bilancioJSON->CostiProduzioneMateriePrimeSussidiarieConsumoMerci) ? $bilancioJSON->CostiProduzioneMateriePrimeSussidiarieConsumoMerci : 0);
        $CostiProduzioneGodimentoBeniTerzi = (isset($bilancioJSON->CostiProduzioneGodimentoBeniTerzi) ? $bilancioJSON->CostiProduzioneGodimentoBeniTerzi : 0);
        $CostiProduzioneServizi = (isset($bilancioJSON->CostiProduzioneServizi) ? $bilancioJSON->CostiProduzioneServizi : 0);
        $CostiProduzionePersonaleTotaleCostiPersonale = (isset($bilancioJSON->CostiProduzionePersonaleTotaleCostiPersonale) ? $bilancioJSON->CostiProduzionePersonaleTotaleCostiPersonale : 0);
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci = (isset($bilancioJSON->CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci) ? $bilancioJSON->CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci : 0);
        $CostiProduzioneOneriDiversiGestione = (isset($bilancioJSON->CostiProduzioneOneriDiversiGestione) ? $bilancioJSON->CostiProduzioneOneriDiversiGestione : 0);

        $TotaleValoreProduzionePrecedente = (isset($bilancioJSONprev->TotaleValoreProduzione) ? $bilancioJSONprev->TotaleValoreProduzione : 0);
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerciPrecedente = (isset($bilancioJSONprev->CostiProduzioneMateriePrimeSussidiarieConsumoMerci) ? $bilancioJSONprev->CostiProduzioneMateriePrimeSussidiarieConsumoMerci : 0);
        $CostiProduzioneGodimentoBeniTerziPrecedente = (isset($bilancioJSONprev->CostiProduzioneGodimentoBeniTerzi) ? $bilancioJSONprev->CostiProduzioneGodimentoBeniTerzi : 0);
        $CostiProduzioneServiziPrecedente = (isset($bilancioJSONprev->CostiProduzioneServizi) ? $bilancioJSONprev->CostiProduzioneServizi : 0);
        $CostiProduzionePersonaleTotaleCostiPersonalePrecedente = (isset($bilancioJSONprev->CostiProduzionePersonaleTotaleCostiPersonale) ? $bilancioJSONprev->CostiProduzionePersonaleTotaleCostiPersonale : 0);
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerciPrecedente = (isset($bilancioJSONprev->CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci) ? $bilancioJSONprev->CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci : 0);
        $CostiProduzioneOneriDiversiGestionePrecedente = (isset($bilancioJSONprev->CostiProduzioneOneriDiversiGestione) ? $bilancioJSONprev->CostiProduzioneOneriDiversiGestione : 0);


        $MOLcurr = $TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzioneServizi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione;
        $MOLprev = $TotaleValoreProduzionePrecedente - $CostiProduzioneMateriePrimeSussidiarieConsumoMerciPrecedente - $CostiProduzioneGodimentoBeniTerziPrecedente - $CostiProduzioneServiziPrecedente - $CostiProduzionePersonaleTotaleCostiPersonalePrecedente - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerciPrecedente - $CostiProduzioneOneriDiversiGestionePrecedente;

        if ($MOLprev == 0) {
            $AndamentoMOL = number_format(- (1 - ($MOLcurr / 1)) * 100, 2, ',', '');
            $dataAnalisis['Andamento_del_MOL'] = $AndamentoMOL . '%';
        } else {
            $AndamentoMOL = number_format(- (1 - ($MOLcurr / $MOLprev)) * 100, 2, ',', '');
            $dataAnalisis['Andamento_del_MOL'] = $AndamentoMOL . '%';
        }

        $arrayConVoci['Andamento_del_MOL'] = array('TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzioneServizi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione');

        // ### ROI

        $DifferenzaValoreCostiProduzione = (isset($bilancioJSON->DifferenzaValoreCostiProduzione) ? $bilancioJSON->DifferenzaValoreCostiProduzione : 0);
        if ($TotaleAttivo == 0) {
            $ROI = number_format((float)($DifferenzaValoreCostiProduzione / 1) * 100, 2, ',', '');
            $dataAnalisis['ROI'] = $ROI . '%';
        } else {
            $ROI = number_format((float)($DifferenzaValoreCostiProduzione / $TotaleAttivo) * 100, 2, ',', '');
            $dataAnalisis['ROI'] = $ROI . '%';
        }

        $arrayConVoci['ROI'] = array('DifferenzaValoreCostiProduzione', 'TotaleAttivo');

        // ### ROS

        if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
            $ROS = number_format((float)($DifferenzaValoreCostiProduzione / 1) * 100, 2, ',', '');
            $dataAnalisis['ROS'] = $ROS . '%';
        } else {
            $ROS = number_format((float)($DifferenzaValoreCostiProduzione / $ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '');
            $dataAnalisis['ROS'] = $ROS . '%';
        }

        $arrayConVoci['ROS'] = array('DifferenzaValoreCostiProduzione', 'ValoreProduzioneRicaviVenditePrestazioni');

        //### ROE

        if ($TotalePatrimonioNetto == 0) {
            $ROE = number_format((float)($UtilePerditaEsercizio / 1) * 100, 2, ',', '');
            $dataAnalisis['ROE'] = $ROE . '%';
        } else {
            $ROE = number_format((float)($UtilePerditaEsercizio / $TotalePatrimonioNetto) * 100, 2, ',', '');
            $dataAnalisis['ROE'] = $ROE . '%';
        }

        $arrayConVoci['ROE'] = array('UtilePerditaEsercizio', 'TotalePatrimonioNetto');

        //### EBITDA/Fatturato

        if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
            $EBITDA_FATTURATO = number_format((float)(($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / 1) * 100, 2, ',', '');
            $dataAnalisis['EBITDA_Fatturato'] = $EBITDA_FATTURATO . '%';
        } else {
            $EBITDA_FATTURATO = number_format((float)(($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / $ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '');
            $dataAnalisis['EBITDA_Fatturato'] = $EBITDA_FATTURATO . '%';
        }

        $arrayConVoci['EBITDA_Fatturato'] = array('TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneServizi', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione', 'ValoreProduzioneRicaviVenditePrestazioni');

        //### Andamento dei mezzi propri

        $TotalePatrimonioNettoCurr = (isset($bilancioJSON->TotalePatrimonioNetto) ? $bilancioJSON->TotalePatrimonioNetto : 0);
        $TotalePatrimonioNettoPrev = (isset($bilancioJSONprev->TotalePatrimonioNetto) ? $bilancioJSONprev->TotalePatrimonioNetto : 0);
        if ($TotalePatrimonioNettoPrev == 0) {
            $AndamentoDeiMezziPropri = number_format((float)(($TotalePatrimonioNettoCurr / 1) - 1) * 100, 2, ',', '');
            $dataAnalisis['Andamento_dei_mezzi_propri'] = $AndamentoDeiMezziPropri . '%';
        } else {
            $AndamentoDeiMezziPropri = number_format((float)(($TotalePatrimonioNettoCurr / $TotalePatrimonioNettoPrev) - 1) * 100, 2, ',', '');
            $dataAnalisis['Andamento_dei_mezzi_propri'] = $AndamentoDeiMezziPropri . '%';
        }

        $arrayConVoci['Andamento_dei_mezzi_propri'] = array('TotalePatrimonioNetto');

        //### Margine Struttura Primario
        $TotaleImmobilizzazioni = (isset($bilancioJSON->TotaleImmobilizzazioni) ? $bilancioJSON->TotaleImmobilizzazioni : 0);
        if ($TotaleImmobilizzazioni == 0) {
            $Margine_Struttura_Primario = number_format((float)($TotalePatrimonioNetto / 1) * 100, 2, ',', '');
            $dataAnalisis['Margine_Struttura_Primario'] = $Margine_Struttura_Primario;
        } else {
            $Margine_Struttura_Primario = number_format((float)($TotalePatrimonioNetto / $TotaleImmobilizzazioni) * 100, 2, ',', '');
            $dataAnalisis['Margine_Struttura_Primario'] = $Margine_Struttura_Primario;
        }

        $arrayConVoci['Margine_Struttura_Primario'] = array('TotalePatrimonioNetto', 'TotaleImmobilizzazioni');

        //### Margine Struttura Secondario
        $TrattamentoFineRapportoLavoroSubordinato = (isset($bilancioJSON->TrattamentoFineRapportoLavoroSubordinato) ? $bilancioJSON->TrattamentoFineRapportoLavoroSubordinato : 0);
        $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSONprev->DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo : 0));
        $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiAccontiEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiAccontiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiAccontiEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSONprev->DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo : 0));
        $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSONprev->DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo : 0));
        $DebitiEsigibiliOltreEsercizioSuccessivo = isset($bilancioJSON->DebitiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiEsigibiliOltreEsercizioSuccessivo : 0;


        $QuarantaTre = $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo + $DebitiAccontiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo; //(isset($bilancioJSON->DebitiOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiOltreEsercizioSuccessivo : 0);

        if ($TotaleImmobilizzazioni == 0) {
            $Margine_Struttura_Secondario_Semplificato = number_format((float)(($TotalePatrimonioNetto + $TrattamentoFineRapportoLavoroSubordinato + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo + $DebitiAccontiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) / 1) * 100, 2, ',', '');
            $Margine_Struttura_Secondario_Ordinario = number_format((float)(($TotalePatrimonioNetto + $TrattamentoFineRapportoLavoroSubordinato + $QuarantaTre) / 1) * 100, 2, ',', '');
            $dataAnalisis['Margine_Struttura_Secondario'] = $Margine_Struttura_Secondario_Semplificato;
        } else {
            $Margine_Struttura_Secondario_Semplificato = number_format((float)(($TotalePatrimonioNetto + $TrattamentoFineRapportoLavoroSubordinato + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo + $DebitiAccontiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) / $TotaleImmobilizzazioni) * 100, 2, ',', '');
            $Margine_Struttura_Secondario_Ordinario = number_format((float)(($TotalePatrimonioNetto + $TrattamentoFineRapportoLavoroSubordinato + $QuarantaTre) / $TotaleImmobilizzazioni) * 100, 2, ',', '');
            $dataAnalisis['Margine_Struttura_Secondario'] = $Margine_Struttura_Secondario_Semplificato;
        }

        $arrayConVoci['Margine_Struttura_Secondario'] = array('TotalePatrimonioNetto', 'TrattamentoFineRapportoLavoroSubordinat', 'DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 'DebitiAccontiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo', 'DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo', 'TotaleImmobilizzazioni');

        // CURRENT RADIO (VEDI INDICE RITORNO LIQUIDO ATT)
        $denominatoreRitornoLiquidoAttivo = 0;
        if (((float)$TotaleDebitiEntroDodiciMesi + (float)$PassivoRateiRisconti) == 0) {
            $denominatoreRitornoLiquidoAttivo = 1;
        }

        $formula = ((float)$TotaleDisponibilitaLiquide + (float)$AttivoRateiRisconti + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$TotaleRimanenze + (float)$TotaleCreditiEntroDodiciMesi) / ((float)$TotaleDebitiEntroDodiciMesi + (float)$PassivoRateiRisconti + (float)$denominatoreRitornoLiquidoAttivo);

        $RITORNO_LIQUIDO_ATTIVO = number_format((float)$formula * 100, 2, ',', '');
        $dataAnalisis['Current_Ratio'] = $RITORNO_LIQUIDO_ATTIVO . '%';
        $arrayConVoci['Current_Ratio'] = array('TotaleDisponibilitaLiquide', 'AttivoRateiRisconti', 'TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 'TotaleRimanenze', 'TotaleCreditiEntroDodiciMesi', 'TotaleDebitiEntroDodiciMesi', 'PassivoRateiRisconti');

        //### Attivita a breve / Passività a Breve

        // TRENTACINQUE
        $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiVersoControllantiEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoControllantiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoControllantiEsigibiliOltreEsercizioSuccessivo : 0);
        $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo : 0));
        $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo : 0));
        $TrentaCinque = $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo;
        $CreditiCreditiTributariTotaleCreditiTributari = (isset($bilancioJSON->CreditiCreditiTributariTotaleCreditiTributari) ? $bilancioJSON->CreditiCreditiTributariTotaleCreditiTributari : $val = (isset($bilancioJSONprev->CreditiCreditiTributariTotaleCreditiTributari) ? $bilancioJSONprev->CreditiCreditiTributariTotaleCreditiTributari : 0));

        // QUARANTANOVE
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo : 0));
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiAccontiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiAccontiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiAccontiEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo : 0));
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo : 0));
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo : 0));
        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo : 0);
        $QuarantaNove = $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiAccontiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo;
        $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo : 0);

        if ($QuarantaNove > 0 || $PassivoRateiRisconti > 0) {
            $Attivita_a_breve_Passivita_a_Breve_Semplificato = number_format((float)((((float)$TotaleDisponibilitaLiquide + (float)$TrentaCinque + (float)$TotaleRimanenze + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$AttivoRateiRisconti) / ((float)$QuarantaNove + (float)$PassivoRateiRisconti))), 2, ',', '');
            //$dataAnalisis['Attivita_a_breve_Passività_a_Breve_Semplificato'] = $Attivita_a_breve_Passivita_a_Breve_Semplificato.'%';
        }
        $Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore = (float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAccontiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + (float)$PassivoRateiRisconti;

        if ($Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore > 0) {
            $Attivita_a_breve_Passivita_a_Breve_Ordinario = number_format((float)((($TotaleDisponibilitaLiquide + (float)$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo + (float)$TotaleRimanenze + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$AttivoRateiRisconti) / ($Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore))) * 100, 2, ',', '');
            $dataAnalisis['Attivita_a_breve_Passività_a_Breve_Ordinario'] = $Attivita_a_breve_Passivita_a_Breve_Ordinario . '%';
        }

        $arrayConVoci['Attivita_a_breve_Passività_a_Breve_Ordinario'] = array();

        //   dd($DebitiEsigibiliEntroEsercizioSuccessivo);
        // ACID TEST
        if ($DebitiEsigibiliEntroEsercizioSuccessivo > 0 || $PassivoRateiRisconti > 0) {

            $AcidTest = number_format((float)(((float)$TotaleCrediti + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$TotaleDisponibilitaLiquide + (float)$AttivoRateiRisconti) / ((float)$DebitiEsigibiliEntroEsercizioSuccessivo + (float)$PassivoRateiRisconti)), 2, ',', '');
            //            $dataAnalisis['AcidTest'] = $AcidTest.'%';
        }
        if ($QuarantaNove > 0 || $PassivoRateiRisconti > 0) {
            $ACID_TEST_Semplificato = number_format((float)((((float)$TotaleDisponibilitaLiquide + (float)$TrentaCinque + (float)$TotaleRimanenze + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$AttivoRateiRisconti - (float)$TotaleRimanenze) / ((float)$QuarantaNove + (float)$PassivoRateiRisconti))), 2, ',', '');
            // $dataAnalisis['ACID_TEST_Semplificato'] = $ACID_TEST_Semplificato.'%';
        }
        $ACID_TEST_Ordinario_divisore = (float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAccontiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + (float)$PassivoRateiRisconti;

        if ($ACID_TEST_Ordinario_divisore > 0) {
            $ACID_TEST_Ordinario = number_format((float)((((float)$TotaleDisponibilitaLiquide + (float)$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo + (float)$TotaleRimanenze + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$AttivoRateiRisconti - $TotaleRimanenze) / ($ACID_TEST_Ordinario_divisore))) * 100, 2, ',', '');
            $dataAnalisis['Acid_Test'] = $ACID_TEST_Ordinario . '%';
        }
        //dd('TotaleDisponibilitaLiquide', $TotaleDisponibilitaLiquide, 'CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo', $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo, 'CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo, 'CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo, 'CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo', $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo, 'CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo, 'CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo, 'TotaleRimanenze', $TotaleRimanenze, 'TotaleRimanenze', $TotaleRimanenze, 'AttivoRateiRisconti', $AttivoRateiRisconti, 'TotaleRimanenze', $TotaleRimanenze, 'DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo, 'DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo, 'DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo, 'DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo, 'DebitiAccontiEsigibiliEntroEsercizioSuccessivo', $DebitiAccontiEsigibiliEntroEsercizioSuccessivo, 'DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo, 'DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo', $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo, 'DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo, 'DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo, 'DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo', $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo, 'DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo, 'DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo, 'DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo', $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo, 'DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo, 'PassivoRateiRisconti', $PassivoRateiRisconti);
        $arrayConVoci['Acid_Test'] = array('TotaleDisponibilitaLiquide', 'CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo', 'CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 'CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 'CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 'CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 'CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 'TotaleRimanenze', 'TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 'AttivoRateiRisconti', 'TotaleRimanenze', 'DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 'DebitiAccontiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 'DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 'PassivoRateiRisconti');

        // AUTONOMIA FINANZIARIA
        if (($TotalePatrimonioNetto + $TotaleDebiti) == 0) {
            $AUTONOMIA_FINANZIARIA = number_format((float)(($TotalePatrimonioNetto / 1)) * 100, 2, ',', '');
            $dataAnalisis['Autonomia_Finanziaria'] = $AUTONOMIA_FINANZIARIA . '%';
        } else {
            $AUTONOMIA_FINANZIARIA = number_format((float)(($TotalePatrimonioNetto / ($TotalePatrimonioNetto + $TotaleDebiti))) * 100, 2, ',', '');
            $dataAnalisis['Autonomia_Finanziaria'] = $AUTONOMIA_FINANZIARIA . '%';
        }

        $arrayConVoci['Autonomia_Finanziaria'] = array('TotalePatrimonioNetto', 'TotalePatrimonioNetto', 'TotaleDebiti');

        // LIVELLO INVESTIMENTI AZIENDALI
        if ($TotaleAttivo == 0) {
            $LIVELLO_INVESTIMENTI_AZIENDALI = number_format((float)($TotalePatrimonioNetto / 0.1) * 100, 2, ',', '');
            $dataAnalisis['Livello_investimenti_aziendali'] = $LIVELLO_INVESTIMENTI_AZIENDALI . '%';
        } else {
            $LIVELLO_INVESTIMENTI_AZIENDALI = number_format((float)($TotalePatrimonioNetto / $TotaleAttivo) * 100, 2, ',', '');
            $dataAnalisis['Livello_investimenti_aziendali'] = $LIVELLO_INVESTIMENTI_AZIENDALI . '%';
        }

        $arrayConVoci['Livello_investimenti_aziendali'] = array('TotalePatrimonioNetto', 'TotaleAttivo');


        // PFN / EBITDA
        $ImmobilizzazioniFinanziarieCreditiTotaleCrediti = (isset($bilancioJSON->ImmobilizzazioniFinanziarieCreditiTotaleCrediti) ? $bilancioJSON->ImmobilizzazioniFinanziarieCreditiTotaleCrediti : 0);
        $debitiFinanziariCurr = $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;
        if ($MOLcurr == 0) {
            $PFN_EBITDA = number_format((float)(($debitiFinanziariCurr - $TotaleDisponibilitaLiquide - $ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / 1), 2, '.', ',');
            $dataAnalisis['PFN_EBITDA'] = (float)$PFN_EBITDA * 100;
        } else {
            $PFN_EBITDA = number_format((float)(((float)$debitiFinanziariCurr - (float)$TotaleDisponibilitaLiquide - (float)$ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / (float)$MOLcurr), 2, '.', ',');
            $dataAnalisis['PFN_EBITDA'] = (float)$PFN_EBITDA * 100;
        }
        // dd($PFN_EBITDA, $OF_RICAVI);

        $arrayConVoci['PFN_EBITDA'] = array('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 'TotaleDisponibilitaLiquide', 'ImmobilizzazioniFinanziarieCreditiTotaleCrediti', 'TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzioneServizi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione');

        // Peso Oneri Finanziari (OF/Fatturato)
        $denominatoreOFfatturato = $ValoreProduzioneRicaviVenditePrestazioni;
        if ($denominatoreOFfatturato == 0) {
            $denominatoreOFfatturato = 1;
        }
        $Peso_Oneri_Finanziari = number_format((float)($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari / $denominatoreOFfatturato) * 100,  2, ',', '');
        $dataAnalisis['Peso_Oneri_Finanziari'] = $Peso_Oneri_Finanziari . '%';
        $arrayConVoci['Peso_Oneri_Finanziari'] = array('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 'ValoreProduzioneRicaviVenditePrestazioni');

        // Copertura Lorda degli Oneri Finanziari
        if ($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari == 0) {
            $Copertura_Lorda_degli_Oneri_Finanziari = number_format((float)(($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / 1), 2, '.', ',');
            $dataAnalisis['Copertura_Lorda_OF'] = (float)$Copertura_Lorda_degli_Oneri_Finanziari * 100;
        } else {
            $Copertura_Lorda_degli_Oneri_Finanziari = number_format((float)(($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari), 2, '.', ',');
            $dataAnalisis['Copertura_Lorda_OF'] = (float)$Copertura_Lorda_degli_Oneri_Finanziari * 100;
        }

        $arrayConVoci['Copertura_Lorda_OF'] = array('TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneServizi', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione', 'ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari');

        // EBIT / OF
        if ($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari == 0) {
            $EBIT_OF = number_format((float)(($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione - $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - $CostiProduzioneAccantonamentiRischi - $CostiProduzioneAltriAccantonamenti) / 1), 2, '.', ',');
            $dataAnalisis['EBIT_OF'] = (float)$EBIT_OF * 100;
        } else {
            $EBIT_OF = number_format((float)(((float)$TotaleValoreProduzione - (float)$CostiProduzioneMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneServizi - (float)$CostiProduzioneGodimentoBeniTerzi - (float)$CostiProduzionePersonaleTotaleCostiPersonale - (float)$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneOneriDiversiGestione - (float)$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - (float)$CostiProduzioneAccantonamentiRischi - (float)$CostiProduzioneAltriAccantonamenti) / (float)$ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari), 2, '.', ',');
            $dataAnalisis['EBIT_OF'] = (float)$EBIT_OF * 100;
        }

        $arrayConVoci['EBIT_OF'] = array('TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneServizi', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione', 'CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 'CostiProduzioneAccantonamentiRischi', 'CostiProduzioneAltriAccantonamenti', 'ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari');

        //Costo del personale
        if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
            $Costo_del_personale = number_format((float)($CostiProduzionePersonaleTotaleCostiPersonale / 1) * 100, 2, ',', '');
            $dataAnalisis['Costo_del_personale'] = $Costo_del_personale . '%';
        } else {
            $Costo_del_personale = number_format((float)($CostiProduzionePersonaleTotaleCostiPersonale / $ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '');
            $dataAnalisis['Costo_del_personale'] = $Costo_del_personale . '%';
        }

        $arrayConVoci['Costo_del_personale'] = array('CostiProduzionePersonaleTotaleCostiPersonale', 'ValoreProduzioneRicaviVenditePrestazioni');

        // CF / Attivo
        if ($TotaleAttivo == 0) {
            $CreditiImposteAnticipateTotaleImposteAnticipate = (isset($bilancioJSON->CreditiImposteAnticipateTotaleImposteAnticipate) ? $bilancioJSON->CreditiImposteAnticipateTotaleImposteAnticipate : 0);
            $CF_ATTIVO = number_format((float)(($UtilePerditaEsercizio + $CostiProduzioneAccantonamentiRischi + $CostiProduzioneAltriAccantonamenti + $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - $imposteRedditoEsercizioImposteAnticipate) / 0.1) * 100, 2, '.', ',');
            $dataAnalisis['CF_Attivo'] = $CF_ATTIVO . '%';
        } else {
            $CreditiImposteAnticipateTotaleImposteAnticipate = (isset($bilancioJSON->CreditiImposteAnticipateTotaleImposteAnticipate) ? $bilancioJSON->CreditiImposteAnticipateTotaleImposteAnticipate : 0);
            $CF_ATTIVO = number_format((float)(((float)$UtilePerditaEsercizio + (float)$CostiProduzioneAccantonamentiRischi + (float)$CostiProduzioneAltriAccantonamenti + (float)$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - (float)$imposteRedditoEsercizioImposteAnticipate) / (float)$TotaleAttivo) * 100, 2, '.', ',');
            $dataAnalisis['CF_Attivo'] = $CF_ATTIVO . '%';
        }


        $arrayConVoci['CF_Attivo'] = array('UtilePerditaEsercizio', 'CostiProduzioneAccantonamentiRischi', 'CostiProduzioneAltriAccantonamenti', 'CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 'CreditiImposteAnticipateTotaleImposteAnticipate', 'TotaleAttivo');

        //Indice di Indebitamento (PFN/PN)
        if ($TotalePatrimonioNetto == 0) {
            $MOLannoCorrente = $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;
            $Indice_di_Indebitamento = number_format((float)(($MOLannoCorrente - $TotaleDisponibilitaLiquide - $ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / 0.1) * 100, 2, ',', '');
            $dataAnalisis['Indice_di_Indebitamento'] = $Indice_di_Indebitamento . '%';
        } else {
            $MOLannoCorrente = $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;
            $Indice_di_Indebitamento = number_format((float)(((float)$MOLannoCorrente - (float)$TotaleDisponibilitaLiquide - (float)$ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / (float)$TotalePatrimonioNetto) * 100, 2, ',', '');
            $dataAnalisis['Indice_di_Indebitamento'] = $Indice_di_Indebitamento . '%';
        }

        $arrayConVoci['Indice_di_Indebitamento'] = array('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 'TotaleDisponibilitaLiquide', 'ImmobilizzazioniFinanziarieCreditiTotaleCrediti', 'TotalePatrimonioNetto');

        $indiciBilancio = array();

        //SALDO DEBITI VS FISCO

        $DebitiDebitiTributariTotaleDebitiTributariCorrente = (isset($bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari) ? $bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari : 0);
        $FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente = (isset($bilancioJSON->FondiRischiOneriTrattamentoQuiescenzaObblighiSimili) ? $bilancioJSON->FondiRischiOneriTrattamentoQuiescenzaObblighiSimili : 0);
        $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipatePrecedente = isset($bilancioJSONprev->ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate) ? $bilancioJSONprev->ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate : 0;
        $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate = isset($bilancioJSON->ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate) ? $bilancioJSON->ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate : 0;

        $DifferenzaImposteReddito = ($ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate + $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipatePrecedente) / 2;
        if ($DifferenzaImposteReddito == 0) {
            $SaldoDebitiVSFisco = number_format(($FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente + $DebitiDebitiTributariTotaleDebitiTributariCorrente) / 1, 2, '.', ',');
            $dataAnalisis['Saldo_dei_Debiti_verso_il_Fisco'] = $SaldoDebitiVSFisco * 100;
        } else {
            $SaldoDebitiVSFisco = number_format(((float)$FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente + (float)$DebitiDebitiTributariTotaleDebitiTributariCorrente) / (float)$DifferenzaImposteReddito, 2, '.', ',');
            $dataAnalisis['Saldo_dei_Debiti_verso_il_Fisco'] = (float)$SaldoDebitiVSFisco * 100;
        }

        $arrayConVoci['Saldo_dei_Debiti_verso_il_Fisco'] = array('FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente', 'DebitiDebitiTributariTotaleDebitiTributariCorrente', 'DifferenzaImposteReddito');

        $bilanciHelper = new BilanciHelper;

        $explodedDate = new DateTime((explode(' ', $bilancio->year))[0]);

        $valutazioneBilancio = $bilanciHelper->valutazioneIndici($dataAnalisis, $tipoAzienda, $explodedDate->format('Y'));

        $dataAnalisis["PFN_EBITDA"] = $dataAnalisis["PFN_EBITDA"] / 100;
        $dataAnalisis["Copertura_Lorda_OF"] = $dataAnalisis["Copertura_Lorda_OF"] / 100;
        $dataAnalisis["EBIT_OF"] = $dataAnalisis["EBIT_OF"] / 100;
        $dataAnalisis["Saldo_dei_Debiti_verso_il_Fisco"] = $dataAnalisis["Saldo_dei_Debiti_verso_il_Fisco"] / 100;
        $dataAnalisis["Margine_Struttura_Primario"] = (float)str_replace('.', '', $dataAnalisis["Margine_Struttura_Primario"]) / 100;
        $dataAnalisis["Margine_Struttura_Secondario"] = (float)str_replace('.', '', $dataAnalisis["Margine_Struttura_Secondario"]) / 100;

        return array('Indici' => $dataAnalisis, 'Giudizi' => $valutazioneBilancio);
    }

    public function analisiCR($anno, $mese)
    {



        $test = DB::table('centralerischi')->where('anno', $anno)->where('mese', $mese)->get();
        $cleanCR[$anno][$mese] = json_decode($test[0]->jsonData, true);
        $tensioniAnnuali = array();
        $sofferenze = array();
        $soldini = array();
        $sconfini = array();
        $yearlyDivision = array();
        $sconfiniPerAnniBanche = array();
        $tensioniLineCtredito = array();
        foreach ($cleanCR as $singleYearKey => $months) {
            foreach ($months as $singleMonthKey => $banks) {
                foreach ($banks as $bankKey => $creditRow) {
                    foreach ($creditRow as $singleCreditRowTypeKey => $singleCreditValue) {
                        if ($singleCreditRowTypeKey == 'Cassa') {
                            foreach ($singleCreditValue as $credit) {
                                $TipoGaranzia = $credit['Tipo Garanzia'];
                                $TipoAttività = false;
                                $Categoria = $credit['Categoria'];
                                $Accordato = $credit['Accordato'];
                                $Utilizzato = $credit['Utilizzato'];
                                $AccordatoOperativo = $credit['Accordato
Operativo'];
                                $SaldoMedio = $credit['Saldo Medio'];
                                $ImportoGarantito = $credit['Importo
Garantito'];
                                $TipoGaranzia = preg_replace("/\n/", " ", $TipoGaranzia);

                                if (str_contains($TipoGaranzia, 'Assenza') && str_contains($TipoGaranzia, 'garanzie') && str_contains($TipoGaranzia, 'e/o')) {
                                    $TipoGaranzia = "Assenza di garanzie reali e/o privilegi";
                                }

                                if (str_contains($Categoria, 'SCADENZA')) {
                                    if (isset($credit['Tipo Attività'])) {
                                        $TipoAttività = $credit['Tipo Attività'];
                                    }
                                }

                                $currentRow = $Categoria . ' ' . $Accordato . ' ' . $AccordatoOperativo . ' ' . $TipoGaranzia;

                                $currentRow = str_contains($Categoria, 'SCADENZA') ? $currentRow . ' ' . $TipoAttività : $currentRow;

                                $isSameRow = false;

                                if (isset($prevRow)) {
                                    if ($currentRow == $prevRow) {
                                        $isSameRow = true;
                                    }
                                }

                                if ($Accordato != '') {

                                    if (!array_key_exists($singleYearKey, $sconfiniPerAnniBanche)) {
                                        $sconfiniPerAnniBanche[$singleYearKey] = array();
                                    }

                                    if (!array_key_exists($bankKey, $sconfiniPerAnniBanche[$singleYearKey])) {
                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey] = array();
                                    }
                                    if (!array_key_exists($Categoria, $sconfiniPerAnniBanche[$singleYearKey][$bankKey])) {
                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria] = array();
                                    }

                                    if (!array_key_exists($currentRow, $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria])) {
                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow] = array();
                                    }
                                }

                                if ($Utilizzato > $AccordatoOperativo) {
                                    // PER BANCHE ANNI

                                    if (!array_key_exists('TotaleSconfini', $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow])) {

                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TotaleSconfini'] = 1;
                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TensioneLineaCredito'] = false;
                                    } else {

                                        //                                      //SE NON è SETTATA LA SETTO A 1, SE è SETTATA LA INCREMENTO DI 1

                                        if ($sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TotaleSconfini'] < 3) {

                                            //SE NON SIAMO IN SITUAZIONE DI TENSIONE

                                            $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TensioneLineaCredito'] = false;
                                        } else {

                                            //SE SIAMO IN TENSIONE, DICIAMO PER QUALE ANNO E LINEA DI CREDITO

                                            if (!array_key_exists($singleYearKey, $tensioniLineCtredito)) {

                                                $tensioniLineCtredito[$singleYearKey] = array();
                                            }

                                            $tensioniLineCtredito[$singleYearKey][$Categoria][$currentRow] = true;


                                            $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TensioneLineaCredito'] = true;
                                        }

                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TotaleSconfini'] = ($sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TotaleSconfini'] + 1);
                                        if ($sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TotaleSconfini'] >= 3) {
                                            $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TensioneLineaCredito'] = true;
                                        }
                                    }
                                } else {

                                    if (!array_key_exists('TotaleSconfini', $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow])) {

                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TotaleSconfini'] = 0;
                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TensioneLineaCredito'] = false;
                                    }

                                    if (!($sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TotaleSconfini'] >= 3)) {

                                        //SE NON SIAMO IN SITUAZIONE DI TENSIONE

                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TensioneLineaCredito'] = false;
                                    } else {

                                        if (!array_key_exists($singleYearKey, $tensioniLineCtredito)) {

                                            $tensioniLineCtredito[$singleYearKey] = array();
                                        }

                                        $tensioniLineCtredito[$singleYearKey][$Categoria][$currentRow] = true;


                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TensioneLineaCredito'] = true;
                                    }
                                }

                                //SE NON HO SCONFINATO
                                if (str_contains($Categoria, 'SCADENZA')) {
                                    if (isset($credit['Tipo Attività'])) {
                                        $TipoAttività = $credit['Tipo Attività'];
                                    }
                                }
                                $prevRow = $Categoria . ' ' . $Accordato . ' ' . $AccordatoOperativo . ' ' . $TipoGaranzia;

                                $prevRow = str_contains($Categoria, 'SCADENZA') ? $currentRow . ' ' . $TipoAttività : $currentRow;
                                if (!$isSameRow) {
                                    $soldini[$singleYearKey][$bankKey][$currentRow]['Accordato Operativo'] = str_replace('.', '', $AccordatoOperativo);
                                    $soldini[$singleYearKey][$bankKey][$currentRow]['Utilizzato'] = str_replace('.', '', $Utilizzato);
                                }
                            }
                        }
                        if ($singleCreditRowTypeKey == 'Informativa') {
                            foreach ($singleCreditValue as $credit) {
                                //                                dd($credit);
                            }
                        }
                    }
                }
            }
        }


        foreach ($soldini as $anno => $banca) {
            foreach ($banca as $nomeBanca => $val) {
                foreach ($val as $data => $value) {
                    if (!isset($soldini[$anno][$nomeBanca]['Totale Accordato Operativo'])) {
                        $soldini[$anno][$nomeBanca]['Totale Accordato Operativo'] = 0;
                        $soldini[$anno][$nomeBanca]['Totale Utilizzato'] = 0;
                    }
                    $soldini[$anno][$nomeBanca]['Totale Accordato Operativo'] += $value['Accordato Operativo'];
                    $soldini[$anno][$nomeBanca]['Totale Utilizzato'] += $value['Utilizzato'];
                }
            }
        }


        $sconfinoAnnuale = false;
        $arraySconfiniAnnuali = array();
        $conteggioSconfini = 0;

        foreach ($sconfiniPerAnniBanche as $anno => $arrayBanche) {
            foreach ($arrayBanche as $nomeBanca => $arrayRischi) {
                foreach ($arrayRischi as $risk => $righe) {
                    foreach ($righe as $row => $tensione) {
                        if ($tensione['TotaleSconfini'] > 0) {
                            $sconfinoAnnuale = true;
                        }
                    }
                    if ($sconfinoAnnuale) {
                        $arraySconfiniAnnuali[$anno][$risk] = true;
                        $sconfinoAnnuale = false;
                    }
                }
            }
        }

        $sconfiniGeneral = array();
        foreach ($sconfiniPerAnniBanche as $singleYear => $banks) {
            foreach ($banks as $singleBank => $riskType) {
                foreach ($riskType as $singleRisk => $riskRow) {
                    foreach ($riskRow as $singleRiskRow => $riskData) {
                        $sconfiniGeneral[$singleYear][$singleBank][$singleRisk]['TotaleSconfini'] = 0;
                    }
                }
            }
        }
        $tensioniLine = array();
        foreach ($sconfiniPerAnniBanche as $singleYear => $banks) {
            foreach ($banks as $singleBank => $riskType) {
                foreach ($riskType as $singleRisk => $riskRow) {
                    foreach ($riskRow as $singleRiskRow => $riskData) {
                        $sconfiniGeneral[$singleYear][$singleBank][$singleRisk]['TotaleSconfini'] = $sconfiniGeneral[$singleYear][$singleBank][$singleRisk]['TotaleSconfini'] + $riskData['TotaleSconfini'];
                        if ($riskData['TensioneLineaCredito']) {
                            $tensioniLine[$singleYear][$singleRisk] = true;
                        }
                    }
                }
            }
        }

        $singleCR['sconfiniGeneral'] = $sconfiniGeneral;
        $singleCR['cleanCR'] = $cleanCR;
        $singleCR['soldini'] = $soldini;
        $singleCR['tensioniLineCtredito'] = $tensioniLineCtredito;
        $singleCR['arraySconfiniAnnuali'] = $arraySconfiniAnnuali;
        $singleCR['tensioniLine'] = $tensioniLine;

        return $singleCR;
    }

    public function select()
    {
        $bilancis = Bilanci::where('provvisorio', null)->paginate(25);
        $bilanciProvvisori = Bilanci::where('provvisorio', 1)->paginate(25);

        return view('allerta.select', compact('bilancis', 'bilanciProvvisori'));
    }

    public function allertaGeneral($id)
    {
        $ASISfinalScore = false;
        $generalScore = false;

        if (cr::All()->count() == 0) {

            $msg = "Non è stata caricata nessuna Centrale Rischi";
            return view('allerta.empty', compact(['msg']));
        } else {

            $crs = cr::select('anno', 'mese', 'date')->distinct()->orderBy('date', 'asc')->get();

            for ($i = (count($crs) - 12 >= 0) ? count($crs) - 12 : 0; $i < count($crs); $i++) {
                $periods[$crs[$i]->anno][$crs[$i]->mese] = null;
            }


            $latestYear = array_key_last($periods);
            $latestMonth = array_key_last($periods[$latestYear]);

            $crData = array();

            $categories = array(
                'RISCHI A SCADENZA',
                'RISCHI AUTOLIQUIDANTI',
                'RISCHI A REVOCA',
            );

            $earliestYear = array_key_first($periods);
            $earliestMonth = array_key_first($periods[$earliestYear]);

            $upperBoundDate = new DateTime((cr::select('date')->where('anno', $earliestYear)->where('mese', $earliestMonth)->get()->first())->date);
            $lowerBoundDate = new DateTime($upperBoundDate->format('Y-m-d'));
            $lowerBoundDate = $lowerBoundDate->modify('-11 months');

            $banks = array();

            foreach (cr::select('nome_banca')->where('date', '>=', $lowerBoundDate->format('Y-m-d'))->where('date', '<=', $upperBoundDate->format('Y-m-d'))->distinct()->get()->toArray() as $label => $nomeBanca) {
                $banks[] = $nomeBanca["nome_banca"];
            }

            $crHelper = new CrExtractorHelper;
            $crHelper->setPeriod($periods);
            $cleanCR = $crHelper->getAllDataToArray($banks);
            $intermediari = $crHelper->getCountBanks($banks);

            $allertaHelper = new AllertaHelper;
            $crExtractorHelper = new CrExtractorHelper;

            $allertaHelper->setCrExtractor($crExtractorHelper);

            $trimestrePeriod = $allertaHelper->getTrimestrePeriod($periods);
            $lastYearPeriod = $periods;

            $triennioPeriod = $allertaHelper->getTriennioPeriod($periods, $banks);
            $crExtractorHelper->setPeriod($lastYearPeriod);
            $scoreCR = $crExtractorHelper->getScoring($banks);
            $alerts = array();

            $alerts['1'] = $allertaHelper->getAnalisiCRUno($banks);

            $alerts['2'] = $allertaHelper->getAnalisiCRDue($banks);

            $alerts['3'] = $allertaHelper->getAnalisiCRTre($banks);

            $alerts['4'] = $allertaHelper->getAnalisiCRQuattro($triennioPeriod, $trimestrePeriod, $latestYear, $latestMonth, $categories);

            $alerts['5'] = $allertaHelper->getAnalisiCRCinque($periods);

            // $allertaHelper->getAnalisiCRCinqueTest($periods);

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

            // dump('Lista dei parametri di allerta con relativo indicatore (Si/No)');

            // foreach ($alerts as $label => $value) {
            //     if ($value) {
            //         dump('Parametro di allerta n. ' . $label . ' : Si');
            //     } else {
            //         dump('Parametro di allerta n. ' . $label . ' : No');
            //     }
            // }
            // dd('');

            $arrayQuestionario = array();

            $questionario = DB::table('questionario')->get();
            foreach ($questionario as $item => $data) {
                $arrayQuestionario[$data->parameter]['Result'] = $data->result;
                $arrayQuestionario[$data->parameter]['Details'] = $data->details == null ? '' : $data->details;
            }

            // dd($questionario);

            $arrayForwardLooking = array();

            $forwardLooking = DB::table('forwardLooking')->get();
            foreach ($forwardLooking as $item => $data) {
                $arrayForwardLooking[$data->question] = $data->answer;
            }

            if (count($arrayQuestionario) > 0) {
                $scoreASIS = $this->valutazioneQuestionarioQualitativo($arrayQuestionario);
            } else {
                $scoreASIS = array('3' => 0, '4' => 0, '5' => 0, '6' => 0);
            }
            if (count($arrayForwardLooking) == 12) {
                $scoreFL = $this->valutazioneFL($arrayForwardLooking);
            } else {
                $arrayForwardLooking = array(
                    "forwardLooking1" => 0,
                    "forwardLooking2" => 0,
                    "forwardLooking3" => 0,
                    "forwardLooking4" => 0,
                    "forwardLooking5" => 0,
                    "forwardLooking6" => 0,
                    "forwardLooking7" => 0,
                    "forwardLooking8" => 0,
                    "forwardLooking9" => 0,
                    "forwardLooking10" => 0,
                    "forwardLooking11" => 0,
                    "forwardLooking12" => 0,
                );
                $scoreFL = array('Giudizio' => '', 'Valore' => '0');
            }


            if (Bilanci::count() != 0) {

                $bilancioData = $this->analisiBilancio($id);

                if (DB::table('questionario')->count() != 0) {
                    $ASISfinalScore = array("Score" => ($bilancioData['Giudizi']['Score'] * 0.25) + ($scoreCR * 0.25) + ($scoreASIS['3'] * 0.1) + ($scoreASIS['4'] * 0.1) + ($scoreASIS['5'] * 0.15) + ($scoreASIS['6'] * 0.15));

                    $rangeGiudizi = array(
                        0 => array("Min" => 0, "Max" => 0.14, "Giudizio" => "Default"),
                        1 => array("Min" => 0.14, "Max" => 0.28, "Giudizio" => "Situazione Grave"),
                        2 => array("Min" => 0.28, "Max" => 0.42, "Giudizio" => "Alert"),
                        3 => array("Min" => 0.42, "Max" => 0.56, "Giudizio" => "Rischio alert"),
                        4 => array("Min" => 0.56, "Max" => 0.70, "Giudizio" => "Fragilità elevata"),
                        5 => array("Min" => 0.70, "Max" => 0.85, "Giudizio" => "Fragilità"),
                        6 => array("Min" => 0.85, "Max" => 1, "Giudizio" => "Solidità")
                    );

                    foreach ($rangeGiudizi as $index => $ranges) {
                        if ($ASISfinalScore["Score"] >= $ranges["Min"] && $ASISfinalScore["Score"] < $ranges["Max"]) {
                            $ASISfinalScore["Giudizio"] = $ranges['Giudizio'];
                            $ASISfinalScore["Index"] = $index;
                        }
                    }

                    $generalScore = array();

                    if ($scoreASIS['6'] < 0.75) {
                        $generalScore["Giudizio"] = $rangeGiudizi[$ASISfinalScore["Index"] - 1]['Giudizio'];
                        $generalScore["Index"] = $ASISfinalScore["Index"] - 1;
                    } else {
                        $generalScore = $ASISfinalScore;
                    }


                    if ($scoreFL['Giudizio'] == 'Miglioramento') {
                        if ($generalScore["Index"] != 6) {
                            $generalScore["Giudizio"] = $rangeGiudizi[$ASISfinalScore["Index"] + 1]['Giudizio'];
                            $generalScore["Index"] = $generalScore["Index"] + 1;
                        }
                    } else if ($scoreFL['Giudizio'] == 'Peggioramento') {
                        if ($generalScore["Index"] != 0) {
                            $generalScore["Giudizio"] = $rangeGiudizi[$ASISfinalScore["Index"] - 1]['Giudizio'];
                            $generalScore["Index"] = $generalScore["Index"] - 1;
                        }
                    }
                }
				
				
				
				if($punteggioCR >= 0 && $punteggioCR < 0.14){
					$resultCentraleRischi = "Default";
				}
				else if($punteggioCR >= 0.14 && $punteggioCR < 0.28){
					$resultCentraleRischi = "Situazione Grave";
				}
				else if($punteggioCR >= 0.28 && $punteggioCR < 0.42){
					$resultCentraleRischi = "Alert";
				}
				else if($punteggioCR >= 0.42 && $punteggioCR < 0.56){
					$resultCentraleRischi = "Rischio alert";
				}
				else if($punteggioCR >= 0.56 && $punteggioCR < 0.70){
					$resultCentraleRischi = "Fragilità elevata";
				}
				else if($punteggioCR >= 0.70 && $punteggioCR < 0.85){
					$resultCentraleRischi = "Fragilità";
				}
				else if($punteggioCR >= 0.85 && $punteggioCR <= 1){
					$resultCentraleRischi = "Solidità";
				}
				
																  
				if($bilancioData['Giudizi']['Score'] >= 0 && $bilancioData['Giudizi']['Score'] < 0.14){
					$resultAnalisiBilancio = "Default";
				}
				else if($bilancioData['Giudizi']['Score'] >= 0.14 && $bilancioData['Giudizi']['Score'] < 0.28){
					$resultAnalisiBilancio = "Situazione Grave";
				}
				else if($bilancioData['Giudizi']['Score'] >= 0.28 && $bilancioData['Giudizi']['Score'] < 0.42){
					$resultAnalisiBilancio = "Alert";
				}
				else if($bilancioData['Giudizi']['Score'] >= 0.42 && $bilancioData['Giudizi']['Score'] < 0.56){
					echo "Rischio alert";
				}
				else if($bilancioData['Giudizi']['Score'] >= 0.56 && $bilancioData['Giudizi']['Score'] < 0.70){
					$resultAnalisiBilancio = "Fragilità elevata";
				}
				else if($bilancioData['Giudizi']['Score'] >= 0.70 && $bilancioData['Giudizi']['Score'] < 0.85){
					$resultAnalisiBilancio = "Fragilità";
				}
				else if($bilancioData['Giudizi']['Score'] >= 0.85 && $bilancioData['Giudizi']['Score'] <= 1){
					$resultAnalisiBilancio = "Solidità";
				}
				
				
				if($scoreASIS['3'] >= 0 && $scoreASIS['3'] < 0.14){
					$resultMinacceRapportiCommerciali = "Default";
				}
				else if($scoreASIS['3'] >= 0.14 && $scoreASIS['3'] < 0.28){
					$resultMinacceRapportiCommerciali = "Situazione Grave";
				}
				else if($scoreASIS['3'] >= 0.28 && $scoreASIS['3'] < 0.42){
					$resultMinacceRapportiCommerciali = "Alert";
				}
				else if($scoreASIS['3'] >= 0.42 && $scoreASIS['3'] < 0.56){
					$resultMinacceRapportiCommerciali = "Rischio alert";
				}
				else if($scoreASIS['3'] >= 0.56 && $scoreASIS['3'] < 0.70){
					$resultMinacceRapportiCommerciali = "Fragilità elevata";
				}
				else if($scoreASIS['3'] >= 0.70 && $scoreASIS['3'] < 0.85){
					$resultMinacceRapportiCommerciali = "Fragilità";
				}
				else if($scoreASIS['3'] >= 0.85 && $scoreASIS['3'] <= 1){
					$resultMinacceRapportiCommerciali = "Solidità";
				}
				
				
				 if($scoreASIS['4'] >= 0 && $scoreASIS['4'] < 0.14){
					 $resultMinacceGestioneAziendale = "Default";
				 }
				else if($scoreASIS['4'] >= 0.14 && $scoreASIS['4'] < 0.28){
					$resultMinacceGestioneAziendale = "Situazione Grave";
				}
				else if($scoreASIS['4'] >= 0.28 && $scoreASIS['4'] < 0.42){
					$resultMinacceGestioneAziendale = "Alert";
				}
				else if($scoreASIS['4'] >= 0.42 && $scoreASIS['4'] < 0.56){
					$resultMinacceGestioneAziendale = "Rischio alert";
				}
				else if($scoreASIS['4'] >= 0.56 && $scoreASIS['4'] < 0.70){
					$resultMinacceGestioneAziendale = "Fragilità elevata";
				}
				else if($scoreASIS['4'] >= 0.70 && $scoreASIS['4'] < 0.85){
					$resultMinacceGestioneAziendale = "Fragilità";
				}
				else if($scoreASIS['4'] >= 0.85 && $scoreASIS['4'] <= 1){
					$resultMinacceGestioneAziendale = "Solidità";
				}
				
				
				 if($scoreASIS['5'] >= 0 && $scoreASIS['5'] < 0.14){
					 $resultMinacceEventiPregiudizievoli = "Default";
				 }
				else if($scoreASIS['5'] >= 0.14 && $scoreASIS['5'] < 0.28){
					$resultMinacceEventiPregiudizievoli = "Situazione Grave";
				}
				else if($scoreASIS['5'] >= 0.28 && $scoreASIS['5'] < 0.42){
					$resultMinacceEventiPregiudizievoli = "Alert";
				}
				else if($scoreASIS['5'] >= 0.42 && $scoreASIS['5'] < 0.56){
					$resultMinacceEventiPregiudizievoli = "Rischio alert";
				}
				else if($scoreASIS['5'] >= 0.56 && $scoreASIS['5'] < 0.70){
					$resultMinacceEventiPregiudizievoli = "Fragilità elevata";
				}
				else if($scoreASIS['5'] >= 0.70 && $scoreASIS['5'] < 0.85){
					$resultMinacceEventiPregiudizievoli = "Fragilità";
				}
				else if($scoreASIS['5'] >= 0.85 && $scoreASIS['5'] <= 1){
					$resultMinacceEventiPregiudizievoli = "Solidità";
				}
				
				
				if($scoreASIS['6'] >= 0 && $scoreASIS['6'] < 0.14){
					$resultMinacceRischiCaratteristici = "Default";
				}
				else if($scoreASIS['6'] >= 0.14 && $scoreASIS['6'] < 0.28){
					$resultMinacceRischiCaratteristici = "Situazione Grave";
				}
				else if($scoreASIS['6'] >= 0.28 && $scoreASIS['6'] < 0.42){
					$resultMinacceRischiCaratteristici = "Alert";
				}
				else if($scoreASIS['6'] >= 0.42 && $scoreASIS['6'] < 0.56){
					$resultMinacceRischiCaratteristici = "Rischio alert";
				}
				else if($scoreASIS['6'] >= 0.56 && $scoreASIS['6'] < 0.70){
					$resultMinacceRischiCaratteristici = "Fragilità elevata";
				}
				else if($scoreASIS['6'] >= 0.70 && $scoreASIS['6'] < 0.85){
					$resultMinacceRischiCaratteristici = "Fragilità";
				}
				else if($scoreASIS['6'] >= 0.85 && $scoreASIS['6'] <= 1){
					$resultMinacceRischiCaratteristici = "Solidità";
				}
				
				if($ASISfinalScore) {
					$ASISScore = $ASISfinalScore['Giudizio'];
				}
				
				if($scoreFL) {
					$scoreGiudizioFL = $scoreFL['Giudizio'];
				}
				
				
				$risultato = [
					'giudizio' => [
					'Area Esaminata' => [
						'name' => 'Analisi Centrale Rischi', 
						'risultato' => $resultCentraleRischi,
					],
					'Analisi bilancio' => [
						'risultato' => $resultAnalisiBilancio,
					],
					'Minacce rapporti commerciali' => [
						'risultato' => $resultMinacceRapportiCommerciali,
					],
					'Minacce gestione aziendale' => [
						'risultato' => $resultMinacceGestioneAziendale,
					],
					'Minacce da eventi pregiudizievoli' => [
						'risultato' => $resultMinacceEventiPregiudizievoli,
					],
					'Minacce erariali e rischi caratteristici' => [
						'risultato' => $resultMinacceRischiCaratteristici,
					],
					'Profilo rischio AS IS' => [
						'risultato' => $ASISScore
					],
					'Questionario TO BE' => [
						'risultato' => $scoreGiudizioFL
					],
				 ],
				];
				

                return response()->json([
                    'error' => 'false',
                    'ASISfinalScore' => $ASISfinalScore,
                    'generalScore' => $generalScore,
                    'bilancioData' => $bilancioData, // Giudizi score bilancio
                    'punteggioCR' => $punteggioCR, // Score allerta
                    'alerts' => $alerts,
                    'arrayQuestionario' => $arrayQuestionario,
                    'arrayForwardLooking' => $arrayForwardLooking,
                    'scoreFL' => $scoreFL,
                    'scoreASIS' => $scoreASIS,
					'giudizioFinaleSistemaAllerta' => $risultato,
                    'id' => $id,

                ], 200);

                return view('allerta.general', compact(['id', 'ASISfinalScore', 'generalScore', 'bilancioData', 'punteggioCR', 'alerts', 'arrayQuestionario', 'arrayForwardLooking', 'scoreFL', 'scoreASIS']));
            } else {
                dd('test');
                $msg = "Non è stata caricata nessun Bilancio";
                return view('allerta.empty', compact(['msg']));
            }
        }
    }
    public function valutazioneFL($arrayForwardLooking)
    {
        $scoreFL = 0;
        $peso = 0.08333;
        $giudizio = '';

        foreach ($arrayForwardLooking as $label => $value) {
            switch ($value) {
                case 1:
                    $scoreFL += (0 * $peso);
                    break;
                case 2:
                    $scoreFL += (1 * $peso);
                    break;
                case 3:
                    $scoreFL += (-1 * $peso);
                    break;
            }
        }

        if ($scoreFL >= -1 && $scoreFL <= -0.17) {
            $giudizio = 'Peggioramento';
        } else if ($scoreFL >= -0.016 && $scoreFL <= 0.32) {
            $giudizio = 'Stabilità';
        } else if ($scoreFL >= 0.33 && $scoreFL <= 1) {
            $giudizio = 'Miglioramento';
        }

        return array("Valore" => $scoreFL, "Giudizio" => $giudizio);
    }

    public function valutazioneQuestionarioQualitativo($arrayQuestionario)
    {
        $pesi = $this->getPesiASIS();
        $scoreASIS = array('3' => 0, '4' => 0, '5' => 0, '6' => 0);

        // dd($arrayQuestionario);

        // dd($arrayQuestionario);

        foreach ($arrayQuestionario as $label => $result) {

            $explodedLabel = explode('-', $label)[0];

            $scoreASIS[$explodedLabel] += $pesi[$label]['peso'] * $pesi[$label]['score'][$result['Result']];
        }


        return $scoreASIS;
    }

    public function getPesiASIS()
    {
        return array(
            "3-1" => array("peso" => 0.09, "score" => array("Si" => -0.5, "No" => 1)),
            "3-2" => array("peso" => 0.15, "score" => array("Si" => -0.5, "No" => 1)),
            "3-3" => array("peso" => 0.15, "score" => array("Si" => -0.5, "No" => 1)),
            "3-4" => array("peso" => 0.15, "score" => array("Si" => -0.5, "No" => 1)),
            "3-5" => array("peso" => 0.15, "score" => array("Si" => -0.5, "No" => 1)),
            "3-6" => array("peso" => 0.11, "score" => array("Si" => -0.5, "No" => 1)),
            "3-7" => array("peso" => 0.11, "score" => array("Si" => -0.5, "No" => 1)),
            "3-8" => array("peso" => 0.09, "score" => array("Si" => -0.5, "No" => 1)),

            "4-1" => array("peso" => 0.11, "score" => array("Si" => -0.7, "No" => 1)),
            "4-2" => array("peso" => 0.1, "score" => array("Si" => -0.5, "No" => 1)),
            "4-3" => array("peso" => 0.1, "score" => array("Si" => -0.5, "No" => 1)),
            "4-4" => array("peso" => 0.11, "score" => array("Si" => -1, "No" => 1)),
            "4-5" => array("peso" => 0.1, "score" => array("Si" => -0.5, "No" => 1)),
            "4-6" => array("peso" => 0.11, "score" => array("Si" => -0.7, "No" => 1)),
            "4-7" => array("peso" => 0.1, "score" => array("Si" => -0.5, "No" => 1)),
            "4-8" => array("peso" => 0.0, "score" => array("Si" => -0.5, "No" => 1)),
            "4-9" => array("peso" => 0.1, "score" => array("Si" => -0.5, "No" => 1)),
            "4-10" => array("peso" => 0.1, "score" => array("Si" => -0.5, "No" => 1)),

            "5-1" => array("peso" => 0.3, "score" => array("Si" => -0.7, "No" => 1)),
            "5-2" => array("peso" => 0.3, "score" => array("Si" => -0.5, "No" => 1)),
            "5-3" => array("peso" => 0.2, "score" => array("Si" => -0.5, "No" => 1)),
            "5-4" => array("peso" => 0.2, "score" => array("Si" => -0.5, "No" => 1)),

            "6-1" => array("peso" => 0.166, "score" => array("Si" => -0.5, "No" => 1)),
            "6-2" => array("peso" => 0.166, "score" => array("Si" => -0.5, "No" => 1)),
            "6-3" => array("peso" => 0.156, "score" => array("Si" => -0.5, "No" => 1)),
            "6-4" => array("peso" => 0.166, "score" => array("Si" => -0.5, "No" => 1)),
            "6-5" => array("peso" => 0.18, "score" => array("Si" => -1, "No" => 1)),
            "6-6" => array("peso" => 0.166, "score" => array("Si" => -0.5, "No" => 1))
        );
    }
}
