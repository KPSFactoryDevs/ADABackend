<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Bilanci;
use App\Models\cr;
use App\Helpers\CentraleRischi\CrExtractorHelper;
use App\Helpers\CentraleRischi\newCrExtractor;
use Illuminate\Support\Facades\DB;
use App\Helpers\Bilanci\BilanciHelper;
use App\Helpers\Allerta\AllertaHelper;
use DateTime;
use PDF;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Response;
use App\Helpers\printpdf;
use HTTP_Request2;

class PDFController extends Controller
{
	public function reportBasicPdf($idBilancio)
	{
		$bilanciHelper = new BilanciHelper;
		$getAnalisiBilancio = $bilanciHelper->getAnalisiBilancio($idBilancio);

		$bilancio = Bilanci::findOrFail($idBilancio);

		$nomeAzienda = json_decode($bilancio->json_data_anag)->DatiAnagraficiDenominazione;

		$tipoAzienda = $bilancio->tipo_azienda;

		$patrimonioNettoSiNo = false;
		$DSCR6Mesi = "No";
		if ($getAnalisiBilancio['AnalisiAdvanced']['Indici']['PATRIMONIO_NETTO'] < 0) {
			$patrimonioNettoSiNo = "Si";
			if (isset($getAnalisiBilancio['AnalisiBasic']['InputData']) && $getAnalisiBilancio['AnalisiBasic']['InputData']['DSCR'] == 0) {
				$DSCR6Mesi = "No";
			} else {
				$DSCR6Mesi = "Si";
			}
		} else {
			$patrimonioNettoSiNo = "No";
		}

		$pdfBilancioData = [
			'currentDate' => date('d/m/y'),
			'nomeAzienda' => $nomeAzienda,
			'DSCR' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->DSCR : null,
			'DSCR6MesiAttendibile' => $DSCR6Mesi,
			'DSCRDate' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->DSCRDate : null,
			'DSCRdispLiquida' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->DSCRdispLiquida : null,
			'riscossione' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->riscossione : null,
			'alertRiscossione' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->alertRiscossione : null,
			'rischioAzienda' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->alertDSCR : null,
			'patrimonioNetto' => $patrimonioNettoSiNo,
			'soglie' => [
				"Indici" => [
					'Sostenibilità Oneri Finanziari' => $getAnalisiBilancio['AnalisiBasic']['Soglie']['Sostenibilità Oneri Finanziari'],
					'Adeguatezza Patrimoniale' => $getAnalisiBilancio['AnalisiBasic']['Soglie']['Adeguatezza Patrimoniale'],
					'Liquidità' => $getAnalisiBilancio['AnalisiBasic']['Soglie']['Liquidità'],
					'Indebitamento Previdenziale Tributario' => $getAnalisiBilancio['AnalisiBasic']['Soglie']['Indebitamento Previdenziale Tributario'],
					'Ritorno Liquido Attivo' => $getAnalisiBilancio['AnalisiBasic']['Soglie']['Ritorno Liquido Attivo'],
				],
				"Valori" => [
					'Sostenibilità Oneri Finanziari' => $getAnalisiBilancio['AnalisiBasic']['Valori']['Sostenibilità Oneri Finanziari'],
					'Adeguatezza Patrimoniale' => $getAnalisiBilancio['AnalisiBasic']['Valori']['Adeguatezza Patrimoniale'],
					'Liquidità' => $getAnalisiBilancio['AnalisiBasic']['Valori']['Liquidità'],
					'Indebitamento Previdenziale Tributario' => $getAnalisiBilancio['AnalisiBasic']['Valori']['Indebitamento Previdenziale Tributario'],
					'Ritorno Liquido Attivo' => $getAnalisiBilancio['AnalisiBasic']['Valori']['Ritorno Liquido Attivo'],
				]
			],
			'entrateDSCRCF' => [
				'DSCRCFmese1' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->entrataDSCRCFmese1 : null,
				'DSCRCFmese2' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->entrataDSCRCFmese2 : null,
				'DSCRCFmese3' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->entrataDSCRCFmese3 : null,
				'DSCRCFmese4' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->entrataDSCRCFmese4 : null,
				'DSCRCFmese5' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->entrataDSCRCFmese5 : null,
				'DSCRCFmese6' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->entrataDSCRCFmese6 : null,
			],
			'uscitaDSCRCF' => [
				'DSCRCFmese1' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->uscitaDSCRCFmese1 : null,
				'DSCRCFmese2' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->uscitaDSCRCFmese2 : null,
				'DSCRCFmese3' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->uscitaDSCRCFmese3 : null,
				'DSCRCFmese4' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->uscitaDSCRCFmese4 : null,
				'DSCRCFmese5' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->uscitaDSCRCFmese5 : null,
				'DSCRCFmese6' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->uscitaDSCRCFmese6 : null,
			],
			'rimborsoDSCR' => [
				'DSCRmese1' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->rimborsoDSCRmese1 : null,
				'DSCRmese2' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->rimborsoDSCRmese2 : null,
				'DSCRmese3' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->rimborsoDSCRmese3 : null,
				'DSCRmese4' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->rimborsoDSCRmese4 : null,
				'DSCRmese5' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->rimborsoDSCRmese5 : null,
				'DSCRmese6' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->rimborsoDSCRmese6 : null,
			],
			'agenziaEntrate' => [
				'agenziaEntrate1' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->agenziaEntrate1 : null,
				'agenziaEntrate2' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->agenziaEntrate2 : null,
				'agenziaEntrate3' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->agenziaEntrate3 : null,
				'agenziaEntrate4' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->agenziaEntrate4 : null,
				'alertAgenziaEntrate' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->alertAgenziaEntrate : null,
			],
			'INPS' => [
				'INPS1' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->INPS1 : null,
				'INPS2' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->INPS2 : null,
				'INPS3' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->INPS3 : null,
				'alertINPS' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->alertINPS : null,
			],
			'retribuzioni' => [
				'retribuzioni1' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->retribuzioni1 : null,
				'retribuzioni2' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->retribuzioni2 : null,
				'retribuzioni3' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->retribuzioni3 : null,
				'alertRetribuzioni' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->alertRetribuzioni : null,
			],
			'fornitori' => [
				'fornitori1' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->fornitori1 : null,
				'fornitori2' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->fornitori2 : null,
				'alertFornitori' => (isset($getAnalisiBilancio['AnalisiBasic']['InputData'][0])) ? $getAnalisiBilancio['AnalisiBasic']['InputData'][0]->alertFornitori : null,
			],
		];

		$printPDF = new printpdf;
		$printPDF->currentPayload = $pdfBilancioData;
		$documentId = $printPDF->generateDocument('bilancio');
		sleep(5);

		return $printPDF->getDocumentData($documentId);
	}

	public function reportCrAndamentale($period, $data_inizio = false, $data_fine = false, $inputBanks = null)
	{
		$crAndamentaleData['period'] = $period;
        $crAndamentaleData['data_inizio'] = $data_inizio;
        $crAndamentaleData['data_fine'] = $data_fine;

        unset($crAndamentaleData['_token']);

        if (!isset($crAndamentaleData['period'])) {
            return response()->json([
                'error' => true,
                'message' => 'Invalid period specified'
            ], 400);
        } else {

            $categories = array(
                'RISCHI A SCADENZA',
                'RISCHI AUTOLIQUIDANTI',
                'RISCHI A REVOCA',
                'SOFFERENZE'
            );

            $crHelper = new CrExtractorHelper;

            if (cr::select('date')->where('document_id', $crAndamentaleData['period'])->count() == 0) {
                return response()->json([
                    'error' => true,
                    'message' => 'Invalid period specified'
                ], 400);
            }

            if (!$crAndamentaleData['data_inizio'] || !$crAndamentaleData['data_fine']) {
                $lastDate = new DateTime(
                    cr::select('date')->where('document_id', $crAndamentaleData['period'])->orderBy('date', 'desc')->first()->date
                );
                $lastDate = $lastDate->modify('last day of this month')->format('Y-m-d');

                $earlierDate = new DateTime(cr::select('date')->where('document_id', $crAndamentaleData['period'])->orderBy('date', 'desc')->first()->date);
                $earlierDate = $earlierDate->modify('-11 months')->modify('first day of this month');
            } else {
                if (!is_numeric($crAndamentaleData['data_inizio']) || !is_numeric($crAndamentaleData['data_fine'])) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Invalid data range specified'
                    ], 400);
                }
                $earlierDate = $crAndamentaleData['data_inizio'];
                $lastDate = $crAndamentaleData['data_fine'];

                $earlierDate = new DateTime('@' . $earlierDate);
                $earlierDate = $earlierDate->modify('first day of this month')->format('Y-m-d');
                $lastDate = new DateTime('@' . $lastDate);
                $lastDate = $lastDate->modify('last day of this month')->format('Y-m-d');
            }



            $lastAvailableDate = new DateTime(
                cr::select('date')->where('document_id', $crAndamentaleData['period'])->orderBy('date', 'asc')->first()->date
            );
            $lastAvailableDate = $lastAvailableDate->modify('first day of this month')->format('Y-m-d');

            $firstAvailableDate = new DateTime(
                cr::select('date')->where('document_id', $crAndamentaleData['period'])->orderBy('date', 'desc')->first()->date
            );
            $firstAvailableDate = $firstAvailableDate->modify('last day of this month')->format('Y-m-d');


            $unrefinedPeriods = json_decode(DB::table('crs')
                ->select('anno', 'mese', 'date')
                ->where("date", '>', $earlierDate)->where("date", '<', $lastDate)
                ->where('document_id', $crAndamentaleData['period'])
                ->groupBy('date', 'anno', 'mese')
                ->orderBy('date')
                ->get(), true);

            if (empty($unrefinedPeriods)) {
                return response()->json([
                    'error' => true,
                    'message' => 'No data available in this period range'
                ]);
            }

            $periods = $crHelper->getCleanPeriods($unrefinedPeriods);

            $crHelper->setPeriod($periods);
            $periodsCorrect = $crHelper->buildPeriodArray();
            $crHelper->setDocumentId($crAndamentaleData['period']);
            $banks = $crHelper->getGeneratedbanks($inputBanks, $crAndamentaleData, $periodsCorrect, $categories);

            $latestYear = array_key_last($periods);
            $latestMonth = array_key_last($periods[$latestYear]);
            $earliestYear = array_key_first($periods);
            $earliestMonth = array_key_first($periods[$earliestYear]);
            $finePeriodo = $latestMonth . ' ' . $latestYear;
            $inizioPeriodo = $earliestMonth . ' ' . $earliestYear;

            //  $missingMonths = $crHelper->missingMonths($unrefinedPeriods, $crAndamentaleData);
            $intermediari = $crHelper->getCountBanks($banks);
            // $mediaAnalisiIndebitamento = $crHelper->getMediaIndebitamento($banks);
            $numeroSconfiniTotali = $crHelper->getTotaleSconfini($banks);
            $rischiGaranzie = $crHelper->getRischiGaranzie($banks);
            $totaleAffidamentiTable = $crHelper->getTotaleAffidamenti($categories, $latestYear, $latestMonth, $banks);
            //  $totaleAffidamentiGeneral = $crHelper->getTotaleAffidamentiGeneral($categories, $latestYear, $latestMonth, $banks);
            // $totAffidamentiConPesiPerBanca = $crHelper->getPesiAffidamentiPerBanca($categories, $latestYear, $latestMonth, $banks);

            //dd($totAffidamentiConPesiPerBanca);
            $sofferenze = $crHelper->getSofferenze($banks);
            $creditiPassatiPerdita = $crHelper->getCreditiPassatiPerdita($banks);
            $scoreCR = $crHelper->getScoring($banks, $intermediari, $numeroSconfiniTotali, $sofferenze, $creditiPassatiPerdita);
            $creditiContestati = $crHelper->getCreditiContestati($banks);
            $numeroRapportiContestati = count($creditiContestati);
            $impagati = $crHelper->getAlertImpagati($banks);
            $garanzieEsitoNegativo = $crHelper->getGaranzieEsitoNegativo($banks);

            $anomalie = $crHelper->getAnomalie($banks);  

            $incidenzaImpagati = $crHelper->getPercentualeMediaImpagati($banks);
            $informazioniGaranti = $crHelper->getInformazioniGaranti($banks);
            $garanzieRicevute = $crHelper->getGaranzieRicevute($banks);
            // $importiSconfini = $crHelper->getImportiSconfini($banks);
            // $affidamentiPerMese = $crHelper->getTotaleAffidamentiPerMese($periods, $categories, $banks);
            // $anomalieStatoRapporto = $crHelper->mancateSegnalazioniStatoRapporto($banks);
            $sconfiniDivisi = $crHelper->divideAnomalie($numeroSconfiniTotali, $banks);   
            // $banksScoring = $crHelper->singleBankData($banks, $periods);
            // $informazioniGarantiAnomalie = $crHelper->informazioniSuiGaranti($informazioniGaranti);
            // $percentualiAccordato = $crHelper->percentualiAccordato($totAffidamentiConPesiPerBanca);
            // $percentualiUtilizzato = $crHelper->percentualiUtilizzato($totAffidamentiConPesiPerBanca);
            // $totaleUtilizzatoGeneral = $crHelper->totAffidamentiConPesiPerBanca($totAffidamentiConPesiPerBanca);
            // $monthsList = array_keys($affidamentiPerMese);


            $lastAvailableDate = new DateTime($lastAvailableDate);
            $firstAvailableDate = new DateTime($firstAvailableDate);

            $generalDates = [
                'periodoMinimoDisponibile' => $lastAvailableDate->format('U'),
                'periodoMassimoDisponibile' => $firstAvailableDate->format('U'),
            ];

            $response = [
                'Scoring' => [
                    'Panoramica' => [
                        'PeriodoRiferimento' => [
                            'Inizio' =>  ucFirst($inizioPeriodo),
                            'Fine' => ucFirst($finePeriodo),
                        ],
                        'NumeroIntermediari' => $intermediari,
                        'NumeroPosizioniContestate' => $numeroRapportiContestati,
                        'FinalScore' => $scoreCR
                    ],
                    'AnomalieUtilizzi' => [
                        'TensioneAutoliquidanti' => $numeroSconfiniTotali['Tensioni']['RISCHI AUTOLIQUIDANTI'],
                        'TensioneRevoca' => $numeroSconfiniTotali['Tensioni']['RISCHI A REVOCA'],
                        'TensioneScadenza' => $numeroSconfiniTotali['Tensioni']['RISCHI A SCADENZA'],
                    ],
                    'AnomalieLievi' => [
                        'Impagati' => $impagati,
                        'Sconfini' => $numeroSconfiniTotali['PresenzaSconfini'],
                        'NumeroSconfiniPerTipo' => $numeroSconfiniTotali['CountSconfiniPerCategoria'],
                    ],
                    'AnomalieQuasiPregiudizievoli' => [
                        'SconfiniEntroNovantaGiorni' => (!empty($numeroSconfiniTotali['SconfiniEntro90Giorni'])),
                        'SconfiniEntroCentoOttantaGiorni' => (!empty($numeroSconfiniTotali['SconfiniOltre90Giorni'])),
                        'SconfiniOltreCentoOttantaGiorni' => (!empty($numeroSconfiniTotali['SconfiniOltre180Giorni'])),
                    ],
                    'AnomaliePregiudizievoli' => [
                        'GaranzieAttivateEsitoNegativo' => ($garanzieEsitoNegativo > 0),
                        'Sofferenze' => (!empty($sofferenze)),
                        'CreditiPassatiPerdita' => (!empty($creditiPassatiPerdita)),
                    ],
                ],
                'ResocontoAnomalie' => [
                    'ListaSconfiniEntroNovantaGiorni' => $sconfiniDivisi['SconfiniEntro90Giorni'],
                    'ListaSconfiniEntroCentoOttantaGiorni' => $sconfiniDivisi['SconfiniOltre90Giorni'],
                    'ListaSconfiniOltreCentoOttantaGiorni' => $sconfiniDivisi['SconfiniOltre180Giorni'],
                    'ListaAnomalie' => $anomalie,
                ],
                'AnalisiAffidamenti' => [
                    'ListaAffidamenti' => $totaleAffidamentiTable
                ],
                'AnalisiIndebitamento' => [],
                'AnalisiPerBanca' => [
                    //      'ListaScoringBanche' => $banksScoring
                ],
                'RischiGaranzie' => [
                    'PosizioniDiRischio' => [
                        'Gestibili' => [
                            'TotaleCreditiScaduti' => $rischiGaranzie['CreditiScaduti'],
                            'TotaleCreditiScadutiImpagati' => $rischiGaranzie['CreditiScadutiImpagati'],
                            'PercentualeIncidenzaImpagati' => $incidenzaImpagati,
                        ],
                        'QuasiPregiudizievoli' => [
                            'TotaleScadutiSconfinatiEntroNovantaGiorni' => $rischiGaranzie['Entro90'],
                            'TotaleScadutiSconfinatiEntroCentoOttantaGiorni' => $rischiGaranzie['Oltre90'],
                            'TotaleScadutiSconfinatiOltreCentoOttantaGiorni' => $rischiGaranzie['Oltre180'],
                        ],
                        'Pregiudizievoli' => [
                            'TotaleSofferenze' => $rischiGaranzie['Sofferenze'],
                            'TotaleCreditiPassatiPerdita' => $rischiGaranzie['CreditiPassatiPerdita'],
                            'TotaleCreditiContestati' => $rischiGaranzie['CreditiContestati'],
                        ],
                    ],
                    'Garanzie' => [
                        'InfoGaranti' => [
                            'TotaleValore' => $informazioniGaranti['Tot. Valore Garanzia'],
                            'TotaleImporto' => $informazioniGaranti['Tot. importo garantito'],
                        ],
                        'GaranzieRicevute' => [
                            'TotaleValore' => $garanzieRicevute['Garantito'],
                            'TotaleImporto' => $garanzieRicevute['Garanzia'],
                        ],
                    ]
                ],
                'generalDates' => $generalDates
            ];

			$printPDF = new printpdf;

			$printPDF->currentPayload = $response;

			$documentId = $printPDF->generateDocument('crAndamentale');
			sleep(5);

			return $printPDF->getDocumentData($documentId);
		}
	}


	public function reportAllerta($idBilancio, $idCr)
	{
		if (cr::where('document_id', $idCr)->get()->count() == 0 || !isset($idCr)) {
            return response()->json([
                'error' => true,
                'message' => 'Non è stata caricata nessuna Centrale Rischi'
            ], 400);
        }

        if (!Bilanci::findOrFail($idBilancio) || !isset($idBilancio)) {
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


		$latestYear = array_key_last($periods);
		$latestMonth = array_key_last($periods[$latestYear]);
		$earliestYear = array_key_first($periods);
		$earliestMonth = array_key_first($periods[$earliestYear]);
		$finePeriodo = $latestMonth . ' ' . $latestYear;
		$inizioPeriodo = $earliestMonth . ' ' . $earliestYear;

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

        $arrayQuestionario = $allertaHelper->getArrayQuestionarioAsIs($idBilancio, $idCr);
        $arrayForwardLooking = $allertaHelper->getArrayQuestionarioToBe($idBilancio, $idCr);

		$questionarioAsIs = [];

        if (count($arrayQuestionario) > 0) {
            $scoreASIS = $allertaHelper->valutazioneQuestionarioQualitativo($arrayQuestionario);
			
			if ($arrayQuestionario['1-1']['Result'] == 'Si') {
				$questionarioAsIs['MinacceRapportiCommerciali']['1'] = "Si";
				if (isset($arrayQuestionario['1-1']['Details'])) {
					$questionarioAsIs['MinacceRapportiCommerciali']['1-details'] = $arrayQuestionario['1-1']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceRapportiCommerciali']['1'] = "No";
				if (isset($arrayQuestionario['1-1']['Details'])) {
					$questionarioAsIs['MinacceRapportiCommerciali']['1-details'] = $arrayQuestionario['1-1']['Details'];
				}
			}
			if ($arrayQuestionario['1-2']['Result'] == 'Si') {
				$questionarioAsIs['MinacceRapportiCommerciali']['2'] = "Si";
				if (isset($arrayQuestionario['1-2']['Details'])) {
					$questionarioAsIs['MinacceRapportiCommerciali']['2-details'] = $arrayQuestionario['1-2']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceRapportiCommerciali']['2'] = "No";
				if (isset($arrayQuestionario['1-2']['Details'])) {
					$questionarioAsIs['MinacceRapportiCommerciali']['2-details'] = $arrayQuestionario['1-2']['Details'];
				}
			}
			if ($arrayQuestionario['1-3']['Result'] == 'Si') {
				$questionarioAsIs['MinacceRapportiCommerciali']['3'] = "Si";
				if (isset($arrayQuestionario['1-3']['Details'])) {
					$questionarioAsIs['MinacceRapportiCommerciali']['3-details'] = $arrayQuestionario['1-3']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceRapportiCommerciali']['3'] = "No";
				if (isset($arrayQuestionario['1-3']['Details'])) {
					$questionarioAsIs['MinacceRapportiCommerciali']['3-details'] = $arrayQuestionario['1-3']['Details'];
				}
			}
			if ($arrayQuestionario['1-4']['Result'] == 'Si') {
				$questionarioAsIs['MinacceRapportiCommerciali']['4'] = "Si";
				if (isset($arrayQuestionario['1-4']['Details'])) {
					$questionarioAsIs['MinacceRapportiCommerciali']['4-details'] = $arrayQuestionario['1-4']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceRapportiCommerciali']['4'] = "No";
				if (isset($arrayQuestionario['1-4']['Details'])) {
					$questionarioAsIs['MinacceRapportiCommerciali']['4-details'] = $arrayQuestionario['1-4']['Details'];
				}
			}
			if ($arrayQuestionario['1-5']['Result'] == 'Si') {
				$questionarioAsIs['MinacceRapportiCommerciali']['5'] = "Si";
				if (isset($arrayQuestionario['1-5']['Details'])) {
					$questionarioAsIs['MinacceRapportiCommerciali']['5-details'] = $arrayQuestionario['1-5']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceRapportiCommerciali']['5'] = "No";
				if (isset($arrayQuestionario['1-5']['Details'])) {
					$questionarioAsIs['MinacceRapportiCommerciali']['5-details'] = $arrayQuestionario['1-5']['Details'];
				}
			}
			if ($arrayQuestionario['1-6']['Result'] == 'Si') {
				$questionarioAsIs['MinacceRapportiCommerciali']['6'] = "Si";
				if (isset($arrayQuestionario['1-6']['Details'])) {
					$questionarioAsIs['MinacceRapportiCommerciali']['6-details'] = $arrayQuestionario['1-6']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceRapportiCommerciali']['6'] = "No";
				if (isset($arrayQuestionario['1-6']['Details'])) {
					$questionarioAsIs['MinacceRapportiCommerciali']['6-details'] = $arrayQuestionario['1-6']['Details'];
				}
			}
			if ($arrayQuestionario['1-7']['Result'] == 'Si') {
				$questionarioAsIs['MinacceRapportiCommerciali']['7'] = "Si";
				if (isset($arrayQuestionario['1-7']['Details'])) {
					$questionarioAsIs['MinacceRapportiCommerciali']['7-details'] = $arrayQuestionario['1-7']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceRapportiCommerciali']['7'] = "No";
				if (isset($arrayQuestionario['1-7']['Details'])) {
					$questionarioAsIs['MinacceRapportiCommerciali']['7-details'] = $arrayQuestionario['1-7']['Details'];
				}
			}
			if ($arrayQuestionario['1-8']['Result'] == 'Si') {
				$questionarioAsIs['MinacceRapportiCommerciali']['8'] = "Si";
				if (isset($arrayQuestionario['1-8']['Details'])) {
					$questionarioAsIs['MinacceRapportiCommerciali']['8-details'] = $arrayQuestionario['1-8']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceRapportiCommerciali']['8'] = "No";
				if (isset($arrayQuestionario['1-8']['Details'])) {
					$questionarioAsIs['MinacceRapportiCommerciali']['8-details'] = $arrayQuestionario['1-8']['Details'];
				}
			}
			if ($arrayQuestionario['2-1']['Result'] == 'Si') {
				$questionarioAsIs['MinacceGestioneAziendale']['1'] = "Si";
				if (isset($arrayQuestionario['2-1']['Details'])) {
					$questionarioAsIs['MinacceGestioneAziendale']['1-details'] = $arrayQuestionario['2-1']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceGestioneAziendale']['1'] = "No";
				if (isset($arrayQuestionario['2-1']['Details'])) {
					$questionarioAsIs['MinacceGestioneAziendale']['1-details'] = $arrayQuestionario['2-1']['Details'];
				}
			}
			if ($arrayQuestionario['2-2']['Result'] == 'Si') {
				$questionarioAsIs['MinacceGestioneAziendale']['2'] = "Si";
				if (isset($arrayQuestionario['2-2']['Details'])) {
					$questionarioAsIs['MinacceGestioneAziendale']['2-details'] = $arrayQuestionario['2-2']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceGestioneAziendale']['2'] = "No";
				if (isset($arrayQuestionario['2-2']['Details'])) {
					$questionarioAsIs['MinacceGestioneAziendale']['2-details'] = $arrayQuestionario['2-2']['Details'];
				}
			}
			if ($arrayQuestionario['2-3']['Result'] == 'Si') {
				$questionarioAsIs['MinacceGestioneAziendale']['3'] = "Si";
				if (isset($arrayQuestionario['2-3']['Details'])) {
					$questionarioAsIs['MinacceGestioneAziendale']['3-details'] = $arrayQuestionario['2-3']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceGestioneAziendale']['3'] = "No";
				if (isset($arrayQuestionario['2-3']['Details'])) {
					$questionarioAsIs['MinacceGestioneAziendale']['3-details'] = $arrayQuestionario['2-3']['Details'];
				}
			}
			if ($arrayQuestionario['2-4']['Result'] == 'Si') {
				$questionarioAsIs['MinacceGestioneAziendale']['4'] = "Si";
				if (isset($arrayQuestionario['2-4']['Details'])) {
					$questionarioAsIs['MinacceGestioneAziendale']['4-details'] = $arrayQuestionario['2-4']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceGestioneAziendale']['4'] = "No";
				if (isset($arrayQuestionario['2-4']['Details'])) {
					$questionarioAsIs['MinacceGestioneAziendale']['4-details'] = $arrayQuestionario['2-4']['Details'];
				}
			}
			if ($arrayQuestionario['2-5']['Result'] == 'Si') {
				$questionarioAsIs['MinacceGestioneAziendale']['5'] = "Si";
				if (isset($arrayQuestionario['2-5']['Details'])) {
					$questionarioAsIs['MinacceGestioneAziendale']['5-details'] = $arrayQuestionario['2-5']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceGestioneAziendale']['5'] = "No";
				if (isset($arrayQuestionario['2-5']['Details'])) {
					$questionarioAsIs['MinacceGestioneAziendale']['5-details'] = $arrayQuestionario['2-5']['Details'];
				}
			}
			if ($arrayQuestionario['2-6']['Result'] == 'Si') {
				$questionarioAsIs['MinacceGestioneAziendale']['6'] = "Si";
				if (isset($arrayQuestionario['2-6']['Details'])) {
					$questionarioAsIs['MinacceGestioneAziendale']['6-details'] = $arrayQuestionario['2-6']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceGestioneAziendale']['6'] = "No";
				if (isset($arrayQuestionario['2-6']['Details'])) {
					$questionarioAsIs['MinacceGestioneAziendale']['6-details'] = $arrayQuestionario['2-6']['Details'];
				}
			}
			if ($arrayQuestionario['2-7']['Result'] == 'Si') {
				$questionarioAsIs['MinacceGestioneAziendale']['7'] = "Si";
				if (isset($arrayQuestionario['2-7']['Details'])) {
					$questionarioAsIs['MinacceGestioneAziendale']['7-details'] = $arrayQuestionario['2-7']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceGestioneAziendale']['7'] = "No";
				if (isset($arrayQuestionario['2-7']['Details'])) {
					$questionarioAsIs['MinacceGestioneAziendale']['7-details'] = $arrayQuestionario['2-7']['Details'];
				}
			}
			if ($arrayQuestionario['2-8']['Result'] == 'Si') {
				$questionarioAsIs['MinacceGestioneAziendale']['8'] = "Si";
				if (isset($arrayQuestionario['2-8']['Details'])) {
					$questionarioAsIs['MinacceGestioneAziendale']['8-details'] = $arrayQuestionario['2-8']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceGestioneAziendale']['8'] = "No";
				if (isset($arrayQuestionario['2-8']['Details'])) {
					$questionarioAsIs['MinacceGestioneAziendale']['8-details'] = $arrayQuestionario['2-8']['Details'];
				}
			}
			if ($arrayQuestionario['2-9']['Result'] == 'Si') {
				$questionarioAsIs['MinacceGestioneAziendale']['9'] = "Si";
				if (isset($arrayQuestionario['2-9']['Details'])) {
					$questionarioAsIs['MinacceGestioneAziendale']['9-details'] = $arrayQuestionario['2-9']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceGestioneAziendale']['9'] = "No";
				if (isset($arrayQuestionario['2-9']['Details'])) {
					$questionarioAsIs['MinacceGestioneAziendale']['9-details'] = $arrayQuestionario['2-9']['Details'];
				}
			}
			if ($arrayQuestionario['2-10']['Result'] == 'Si') {
				$questionarioAsIs['MinacceGestioneAziendale']['10'] = "Si";
				if (isset($arrayQuestionario['2-10']['Details'])) {
					$questionarioAsIs['MinacceGestioneAziendale']['10-details'] = $arrayQuestionario['2-10']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceGestioneAziendale']['10'] = "No";
				if (isset($arrayQuestionario['2-10']['Details'])) {
					$questionarioAsIs['MinacceGestioneAziendale']['10-details'] = $arrayQuestionario['2-10']['Details'];
				}
			}
			if ($arrayQuestionario['3-1']['Result'] == 'Si') {
				$questionarioAsIs['MinacceErarialiRischiCaratteristici']['1'] = "Si";
				if (isset($arrayQuestionario['3-1']['Details'])) {
					$questionarioAsIs['MinacceErarialiRischiCaratteristici']['1details'] = $arrayQuestionario['3-1']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceErarialiRischiCaratteristici']['1'] = "No";
				if (isset($arrayQuestionario['3-1']['Details'])) {
					$questionarioAsIs['MinacceErarialiRischiCaratteristici']['1details'] = $arrayQuestionario['3-1']['Details'];
				}
			}
			if ($arrayQuestionario['3-2']['Result'] == 'Si') {
				$questionarioAsIs['MinacceErarialiRischiCaratteristici']['2'] = "Si";
				if (isset($arrayQuestionario['3-2']['Details'])) {
					$questionarioAsIs['MinacceErarialiRischiCaratteristici']['2details'] = $arrayQuestionario['3-2']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceErarialiRischiCaratteristici']['2'] = "No";
				if (isset($arrayQuestionario['3-2']['Details'])) {
					$questionarioAsIs['MinacceErarialiRischiCaratteristici']['2details'] = $arrayQuestionario['3-2']['Details'];
				}
			}
			if ($arrayQuestionario['3-3']['Result'] == 'Si') {
				$questionarioAsIs['MinacceErarialiRischiCaratteristici']['3'] = "Si";
				if (isset($arrayQuestionario['3-3']['Details'])) {
					$questionarioAsIs['MinacceErarialiRischiCaratteristici']['3details'] = $arrayQuestionario['3-3']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceErarialiRischiCaratteristici']['3'] = "No";
				if (isset($arrayQuestionario['3-3']['Details'])) {
					$questionarioAsIs['MinacceErarialiRischiCaratteristici']['3details'] = $arrayQuestionario['3-3']['Details'];
				}
			}
			if ($arrayQuestionario['3-4']['Result'] == 'Si') {
				$questionarioAsIs['MinacceErarialiRischiCaratteristici']['4'] = "Si";
				if (isset($arrayQuestionario['3-4']['Details'])) {
					$questionarioAsIs['MinacceErarialiRischiCaratteristici']['4details'] = $arrayQuestionario['3-4']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceErarialiRischiCaratteristici']['4'] = "No";
				if (isset($arrayQuestionario['3-4']['Details'])) {
					$questionarioAsIs['MinacceErarialiRischiCaratteristici']['4details'] = $arrayQuestionario['3-4']['Details'];
				}
			}
			if ($arrayQuestionario['4-1']['Result'] == 'Si') {
				$questionarioAsIs['MinacceEventiPregiudizievoli']['1'] = "Si";
				if (isset($arrayQuestionario['4-1']['Details'])) {
					$questionarioAsIs['MinacceEventiPregiudizievoli']['1details'] = $arrayQuestionario['4-1']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceEventiPregiudizievoli']['1'] = "No";
				if (isset($arrayQuestionario['4-1']['Details'])) {
					$questionarioAsIs['MinacceEventiPregiudizievoli']['1details'] = $arrayQuestionario['4-1']['Details'];
				}
			}
			if ($arrayQuestionario['4-2']['Result'] == 'Si') {
				$questionarioAsIs['MinacceEventiPregiudizievoli']['2'] = "Si";
				if (isset($arrayQuestionario['4-2']['Details'])) {
					$questionarioAsIs['MinacceEventiPregiudizievoli']['2details'] = $arrayQuestionario['4-2']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceEventiPregiudizievoli']['2'] = "No";
				if (isset($arrayQuestionario['4-2']['Details'])) {
					$questionarioAsIs['MinacceEventiPregiudizievoli']['2details'] = $arrayQuestionario['4-2']['Details'];
				}
			}
			if ($arrayQuestionario['4-3']['Result'] == 'Si') {
				$questionarioAsIs['MinacceErarialiRischiCaratteristici']['3'] = "Si";
				if (isset($arrayQuestionario['4-3']['Details'])) {
					$questionarioAsIs['MinacceErarialiRischiCaratteristici']['3details'] = $arrayQuestionario['4-3']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceEventiPregiudizievoli']['3'] = "No";
				if (isset($arrayQuestionario['4-3']['Details'])) {
					$questionarioAsIs['MinacceEventiPregiudizievoli']['3details'] = $arrayQuestionario['4-3']['Details'];
				}
			}
			if ($arrayQuestionario['4-4']['Result'] == 'Si') {
				$questionarioAsIs['MinacceEventiPregiudizievoli']['4'] = "Si";
				if (isset($arrayQuestionario['4-4']['Details'])) {
					$questionarioAsIs['MinacceEventiPregiudizievoli']['4details'] = $arrayQuestionario['4-4']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceEventiPregiudizievoli']['4'] = "No";
				if (isset($arrayQuestionario['4-4']['Details'])) {
					$questionarioAsIs['MinacceEventiPregiudizievoli']['4details'] = $arrayQuestionario['4-4']['Details'];
				}
			}
			if ($arrayQuestionario['4-5']['Result'] == 'Si') {
				$questionarioAsIs['MinacceEventiPregiudizievoli']['5'] = "Si";
				if (isset($arrayQuestionario['4-5']['Details'])) {
					$questionarioAsIs['MinacceEventiPregiudizievoli']['5details'] = $arrayQuestionario['4-5']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceEventiPregiudizievoli']['5'] = "No";
				if (isset($arrayQuestionario['4-5']['Details'])) {
					$questionarioAsIs['MinacceEventiPregiudizievoli']['5details'] = $arrayQuestionario['4-5']['Details'];
				}
			}
			if ($arrayQuestionario['4-6']['Result'] == 'Si') {
				$questionarioAsIs['MinacceEventiPregiudizievoli']['6'] = "Si";
				if (isset($arrayQuestionario['4-6']['Details'])) {
					$questionarioAsIs['MinacceEventiPregiudizievoli']['6details'] = $arrayQuestionario['4-6']['Details'];
				}
			} else {
				$questionarioAsIs['MinacceEventiPregiudizievoli']['6'] = "No";
				if (isset($arrayQuestionario['4-6']['Details'])) {
					$questionarioAsIs['MinacceEventiPregiudizievoli']['6details'] = $arrayQuestionario['4-6']['Details'];
				}
			}
        }

		$forwardLooking = [];

        if (count($arrayForwardLooking) == 12) {
            $scoreFL = $allertaHelper->valutazioneFL($arrayForwardLooking);

				if ($arrayForwardLooking['forwardLooking1'] == 1) {
					$forwardLooking['response1'] = "Costante";
				}
				if ($arrayForwardLooking['forwardLooking1'] == 2) {
					$forwardLooking['response1'] = "In aumento";
				}

				if ($arrayForwardLooking['forwardLooking1'] == 3) {
					$forwardLooking['response1'] = "In diminuzione";
				}
				if ($arrayForwardLooking['forwardLooking2'] == 1) {
					$forwardLooking['response2'] = "Si, per nuovi investimenti";
				}
				if ($arrayForwardLooking['forwardLooking2'] == 2) {
					$forwardLooking['response2'] = "No";
				}

				if ($arrayForwardLooking['forwardLooking2'] == 3) {
					$forwardLooking['response2'] = "Si, perchè serve liquidità";
				}
				if ($arrayForwardLooking['forwardLooking3'] == 1) {
					$forwardLooking['response3'] = "Costante";
				}
				if ($arrayForwardLooking['forwardLooking3'] == 2) {
					$forwardLooking['response3'] = "In aumento";
				}

				if ($arrayForwardLooking['forwardLooking3'] == 3) {
					$forwardLooking['response3'] = "In diminuzione";
				}
				if ($arrayForwardLooking['forwardLooking4'] == 1) {
					$forwardLooking['response4'] = "Fra 10 e 30";
				}
				if ($arrayForwardLooking['forwardLooking4'] == 2) {
					$forwardLooking['response4'] = "Più di 30";
				}

				if ($arrayForwardLooking['forwardLooking4'] == 3) {
					$forwardLooking['response4'] = "Meno di 10";
				}
				if ($arrayForwardLooking['forwardLooking5'] == 1) {
					$forwardLooking['response5'] = "Su base pluriennale";
				}
				if ($arrayForwardLooking['forwardLooking5'] == 2) {
					$forwardLooking['response5'] = "Su base annuale";
				}

				if ($arrayForwardLooking['forwardLooking5'] == 3) {
					$forwardLooking['response5'] = "No";
				}
				if ($arrayForwardLooking['forwardLooking6'] == 1) {
					$forwardLooking['response6'] = "Si, ma non rilevanti";
				}
				if ($arrayForwardLooking['forwardLooking6'] == 2) {
					$forwardLooking['response6'] = "No";
				}

				if ($arrayForwardLooking['forwardLooking6'] == 3) {
					$forwardLooking['response6'] = "Si";
				}
				if ($arrayForwardLooking['forwardLooking7'] == 1) {
					$forwardLooking['response7'] = "Si, per aumento previsto di utilizzi";
				}
				if ($arrayForwardLooking['forwardLooking7'] == 2) {
					$forwardLooking['response7'] = "No";
				}

				if ($arrayForwardLooking['forwardLooking7'] == 3) {
					$forwardLooking['response7'] = "Si, li usiamo sempre al limite";
				}
				if ($arrayForwardLooking['forwardLooking8'] == 1) {
					$forwardLooking['response8'] = "Forse si, ma potrebbero esserci difficoltà";
				}
				if ($arrayForwardLooking['forwardLooking8'] == 2) {
					$forwardLooking['response8'] = "Si";
				}

				if ($arrayForwardLooking['forwardLooking8'] == 3) {
					$forwardLooking['response8'] = "No, serve sicuramente liquidità";
				}
				if ($arrayForwardLooking['forwardLooking9'] == 1) {
					$forwardLooking['response9'] = "Probabilmente si";
				}
				if ($arrayForwardLooking['forwardLooking9'] == 2) {
					$forwardLooking['response9'] = "Si";
				}

				if ($arrayForwardLooking['forwardLooking9'] == 3) {
					$forwardLooking['response9'] = "No";
				}
				if ($arrayForwardLooking['forwardLooking10'] == 1) {
					$forwardLooking['response10'] = "No";
				}

				if ($arrayForwardLooking['forwardLooking10'] == 3) {
					$forwardLooking['response10'] = "Si, i tempi di pagamento ai fornitori sono più corti";
				}
				if ($arrayForwardLooking['forwardLooking11'] == 1) {
					$forwardLooking['response11'] = "Si, ma evitabili";
				}
				if ($arrayForwardLooking['forwardLooking11'] == 2) {
					$forwardLooking['response11'] = "No";
				}

				if ($arrayForwardLooking['forwardLooking11'] == 3) {
					$forwardLooking['response11'] = "Si";
				}
				if ($arrayForwardLooking['forwardLooking12'] == 1) {
					$forwardLooking['response12'] = "No";
				}
				if ($arrayForwardLooking['forwardLooking12'] == 2) {
					$forwardLooking['response12'] = "Si, prevediamo maggior utilizzo";
				}

				if ($arrayForwardLooking['forwardLooking12'] == 3) {
					$forwardLooking['response12'] = "Si, usiamo sempre al limite le disponibilità";
				}

        }



        $bilancioData = $bilancioHelper->getAnalisiBilancio($idBilancio);
		$bilancio = Bilanci::find($idBilancio);

		$annoEsaminato = $bilancio->year;
		$nomeAzienda = json_decode($bilancio->json_data_anag)->DatiAnagraficiDenominazione;

		// $firstPeriodAnalized = $getDate['lowerBoundDate']->format('Y');
		// $lastPeriodAnalized = $getDate['upperBoundDate']->format('Y');

		// dd($firstPeriodAnalized, $lastPeriodAnalized, $annoEsaminato);
		
		$numeroSconfiniTotali = $crHelper->getTotaleSconfini($banks);
        $anomalie = $crHelper->getAnomalie($banks);
		$sconfiniDivisi = $crHelper->divideAnomalie($numeroSconfiniTotali, $banks); 
        $ASISfinalScore = $allertaHelper->getAsIsFinalScore($bilancioData['AnalisiAdvanced'], $scoreCR, $scoreASIS);
        $getScoreHelper = $allertaHelper->getScores($punteggioCR, $bilancioData['AnalisiAdvanced'], $scoreASIS, $ASISfinalScore, $scoreFL);
		$getGeneralScore = $allertaHelper->getGeneralScore($bilancioData['AnalisiAdvanced'], $scoreCR, $scoreASIS, $scoreFL);

				// dd($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['ROS']['Scoring']);
				// dd($bilancioData['AnalisiAdvanced']['Giudizi']['AnalisiAdvanced']['Giudizi']);
				// dd($bilancioData['AnalisiAdvanced']['Giudizi']['AnalisiAdvanced']['Giudizi']['Andamento del fatturato']);

				$dataAllerta = [
					"id" => $idBilancio,
					"nomeAzienda" => $nomeAzienda,
					"annoEsaminato" => [
						'years' => $annoEsaminato,
						'periodoDa' => $inizioPeriodo,
						'periodoA' => $finePeriodo 
					],
					"andamentoDelFatturato" => [
						'Valore' =>  (isset($bilancioData['AnalisiAdvanced']['Indici']['Andamento_del_fatturato']) && $bilancioData['AnalisiAdvanced']['Indici']['Andamento_del_fatturato'] < 1000) ? number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Andamento_del_fatturato'], '2', ',', '.').'%' : ( (float)$bilancioData['AnalisiAdvanced']['Indici']['Andamento_del_fatturato'] >= 1000 ? (number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Andamento_del_fatturato'], '2', ',', '.')."% Valore anomalo") : (null)),
						'Score' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Andamento del fatturato'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Andamento del fatturato']['Scoring'] : null,
						'Giudizio' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Andamento del fatturato'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Andamento del fatturato']['Giudizio'] : null,
					],
					"AndamentoDelMOL" => [
						'Valore' =>  (isset($bilancioData['AnalisiAdvanced']['Indici']['Andamento_del_MOL']['AndamentoMOL']) && $bilancioData['AnalisiAdvanced']['Indici']['Andamento_del_MOL']['AndamentoMOL'] < 1000) ? number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Andamento_del_MOL']['AndamentoMOL'], '2', ',', '.').'%' : ( $bilancioData['AnalisiAdvanced']['Indici']['Andamento_del_MOL']['AndamentoMOL'] >= 1000 ? (number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Andamento_del_MOL']['AndamentoMOL'], '2', ',', '.').'% Valore anomalo') : (null)),
						'Score' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Andamento del MOL'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Andamento del MOL']['Scoring'] : null,
						'Giudizio' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Andamento del MOL'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Andamento del MOL']['Giudizio'] : null,
					],
					"ROI" => [
						'Valore' =>  (isset($bilancioData['AnalisiAdvanced']['Indici']['ROI']) && $bilancioData['AnalisiAdvanced']['Indici']['ROI'] < 1000) ? number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['ROI'], '2', ',', '.').'%' : ($bilancioData['AnalisiAdvanced']['Indici']['ROI'] >= 1000 ? (number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['ROI'], '2', ',', '.')."% Valore anomalo") : (null)),
						'Score' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['ROI'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['ROI']['Scoring'] : null,
						'Giudizio' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['ROI'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['ROI']['Giudizio'] : null,
					],
					"ROS" => [
						'Valore' =>  (isset($bilancioData['AnalisiAdvanced']['Indici']['ROS']) && $bilancioData['AnalisiAdvanced']['Indici']['ROS'] < 1000) ? number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['ROS'], '2', ',', '.').'%' : ($bilancioData['AnalisiAdvanced']['Indici']['ROS'] >= 1000 ? (number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['ROS'], '2', ',', '.')."% Valore anomalo") : (null)),
						'Score' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['ROS'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['ROS']['Scoring'] : null,
						'Giudizio' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['ROS'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['ROS']['Giudizio'] : null,
					],   
					"ROE" => [
						'Valore' =>  (isset($bilancioData['AnalisiAdvanced']['Indici']['ROE']) && $bilancioData['AnalisiAdvanced']['Indici']['ROE'] < 1000) ? number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['ROE'], '2', ',', '.').'%' : ($bilancioData['AnalisiAdvanced']['Indici']['ROE'] >= 1000 ? (number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['ROE'], '2', ',', '.')."% Valore anomalo") : (null)),
						'Score' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['ROE'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['ROE']['Scoring'] : null,
						'Giudizio' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['ROE'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['ROE']['Giudizio'] : null,
					],
					"EBITDAFatturato" => [
						'Valore' =>  (isset($bilancioData['AnalisiAdvanced']['Indici']['EBITDA_Fatturato']) && $bilancioData['AnalisiAdvanced']['Indici']['EBITDA_Fatturato'] < 1000) ? number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['EBITDA_Fatturato'], '2', ',', '.').'%' : ($bilancioData['AnalisiAdvanced']['Indici']['EBITDA_Fatturato'] >= 1000 ? (number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['EBITDA_Fatturato'], '2', ',', '.')."% Valore anomalo") : (null)),
						'Score' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['EBITDA Fatturato'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['EBITDA Fatturato']['Scoring'] : null,
						'Giudizio' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['EBITDA Fatturato'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['EBITDA Fatturato']['Giudizio'] : null,
					],
					"AndamentoDeiMezziPropri" => [
						'Valore' =>  (isset($bilancioData['AnalisiAdvanced']['Indici']['Andamento_dei_mezzi_propri']) && $bilancioData['AnalisiAdvanced']['Indici']['Andamento_dei_mezzi_propri'] < 1000) ? number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Andamento_dei_mezzi_propri'], '2', ',', '.').'%' : ($bilancioData['AnalisiAdvanced']['Indici']['Andamento_dei_mezzi_propri'] >= 1000 ? (number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Andamento_dei_mezzi_propri'], '2', ',', '.')."% Valore anomalo") : (null)),
						'Score' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Andamento dei mezzi propri'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Andamento dei mezzi propri']['Scoring'] : null,
						'Giudizio' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Andamento dei mezzi propri'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Andamento dei mezzi propri']['Giudizio'] : null,
					],
					"MargineStrutturaPrimario" => [
						'Valore' =>  (isset($bilancioData['AnalisiAdvanced']['Indici']['Margine_Struttura_Primario']) && $bilancioData['AnalisiAdvanced']['Indici']['Margine_Struttura_Primario'] < 1000) ? number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Margine_Struttura_Primario'], '2', ',', '.').'%' : ($bilancioData['AnalisiAdvanced']['Indici']['Margine_Struttura_Primario'] >= 1000 ? (number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Margine_Struttura_Primario'], '2', ',', '.')."% Valore anomalo") : (null)),
						'Score' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Margine Struttura Primario'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Margine Struttura Primario']['Scoring'] : null,
						'Giudizio' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Margine Struttura Primario'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Margine Struttura Primario']['Giudizio'] : null,
					],
					"MargineStrutturaSecondario" => [
						'Valore' =>  (isset($bilancioData['AnalisiAdvanced']['Indici']['Margine_Struttura_Secondario']) && $bilancioData['AnalisiAdvanced']['Indici']['Margine_Struttura_Secondario'] < 1000) ? number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Margine_Struttura_Secondario'], '2', ',', '.').'%' : ($bilancioData['AnalisiAdvanced']['Indici']['Margine_Struttura_Secondario'] >= 1000 ? (number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Margine_Struttura_Secondario'], '2', ',', '.')."% Valore anomalo") : (null)),
						'Score' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Margine Struttura Secondario'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Margine Struttura Secondario']['Scoring'] : null,
						'Giudizio' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Margine Struttura Secondario'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Margine Struttura Secondario']['Giudizio'] : null,
					],
					"CurrentRatio" => [
						'Valore' =>  (isset($bilancioData['AnalisiAdvanced']['Indici']['Current_Ratio']) && $bilancioData['AnalisiAdvanced']['Indici']['Current_Ratio'] < 1000) ? number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Current_Ratio'], '2', ',', '.').'%' : ($bilancioData['AnalisiAdvanced']['Indici']['Current_Ratio'] >= 1000 ? (number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Current_Ratio'], '2', ',', '.')."% Valore anomalo") : (null)),
						'Score' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Current Ratio'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Current Ratio']['Scoring'] : null,
						'Giudizio' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Current Ratio'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Current Ratio']['Giudizio'] : null,
					],
					"AcidTest" => [
						'Valore' =>  (isset($bilancioData['AnalisiAdvanced']['Indici']['Acid_Test']) && $bilancioData['AnalisiAdvanced']['Indici']['Acid_Test'] < 1000) ? number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Acid_Test'], '2', ',', '.').'%' : ($bilancioData['AnalisiAdvanced']['Indici']['Acid_Test'] >= 1000 ? (number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Acid_Test'], '2', ',', '.')."% Valore anomalo") : (null)),
						'Score' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Acid Test'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Acid Test']['Scoring'] : null,
						'Giudizio' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Acid Test'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Acid Test']['Giudizio'] : null,
					],
					"AutonomiaFinanziaria" => [
						'Valore' =>  (isset($bilancioData['AnalisiAdvanced']['Indici']['Autonomia_Finanziaria']) && $bilancioData['AnalisiAdvanced']['Indici']['Autonomia_Finanziaria'] < 1000) ? number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Autonomia_Finanziaria'], '2', ',', '.').'%' : ($bilancioData['AnalisiAdvanced']['Indici']['Autonomia_Finanziaria'] >= 1000 ? (number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Autonomia_Finanziaria'], '2', ',', '.')."% Valore anomalo") : (null)),
						'Score' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Autonomia Finanziaria'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Autonomia Finanziaria']['Scoring'] : null,
						'Giudizio' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Autonomia Finanziaria'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Autonomia Finanziaria']['Giudizio'] : null,
					],
					"LivelloInvestimentiAziendali" => [
						'Valore' =>  (isset($bilancioData['AnalisiAdvanced']['Indici']['Livello_investimenti_aziendali']) && $bilancioData['AnalisiAdvanced']['Indici']['Livello_investimenti_aziendali'] < 1000) ? number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Livello_investimenti_aziendali'], '2', ',', '.').'%' : ($bilancioData['AnalisiAdvanced']['Indici']['Livello_investimenti_aziendali'] >= 1000 ? (number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Livello_investimenti_aziendali'], '2', ',', '.')."% Valore anomalo") : (null)),
						'Score' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Livello investimenti aziendali'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Livello investimenti aziendali']['Scoring'] : null,
						'Giudizio' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Livello investimenti aziendali'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Livello investimenti aziendali']['Giudizio'] : null,
					],
					"PFN_EBITDA" => [
						'Valore' =>  (isset($bilancioData['AnalisiAdvanced']['Indici']['PFN_EBITDA']) && $bilancioData['AnalisiAdvanced']['Indici']['PFN_EBITDA'] < 1000) ? number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['PFN_EBITDA'], '2', ',', '.').'%' : ($bilancioData['AnalisiAdvanced']['Indici']['PFN_EBITDA'] >= 1000 ? (number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['PFN_EBITDA'], '2', ',', '.')."% Valore anomalo") : (null)),
						'Score' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['PFN EBITDA'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['PFN EBITDA']['Scoring'] : null,
						'Giudizio' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['PFN EBITDA'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['PFN EBITDA']['Giudizio'] : null,
					],
					"OF_Fatturato" => [
						'Valore' =>  (isset($bilancioData['AnalisiAdvanced']['Indici']['OF_Fatturato']) && $bilancioData['AnalisiAdvanced']['Indici']['OF_Fatturato'] < 1000) ? number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['OF_Fatturato'], '2', ',', '.').'%' : ($bilancioData['AnalisiAdvanced']['Indici']['OF_Fatturato'] >= 1000 ? (number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['OF_Fatturato'], '2', ',', '.')."% Valore anomalo") : (null)),
						'Score' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Copertura Lorda OF'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Copertura Lorda OF']['Scoring'] : null,
						'Giudizio' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Copertura Lorda OF'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Copertura Lorda OF']['Giudizio'] : null,
					],
					"EBIT_OF" => [
						'Valore' =>  (isset($bilancioData['AnalisiAdvanced']['Indici']['EBIT_OF']) && $bilancioData['AnalisiAdvanced']['Indici']['EBIT_OF'] < 1000) ? number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['EBIT_OF'], '2', ',', '.').'%' : ($bilancioData['AnalisiAdvanced']['Indici']['EBIT_OF'] >= 1000 ? (number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['EBIT_OF'], '2', ',', '.')."% Valore anomalo") : (null)),
						'Score' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['EBIT OF'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['EBIT OF']['Scoring'] : null,
						'Giudizio' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['EBIT OF'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['EBIT OF']['Giudizio'] : null,
					],
					"CoperturaLordaOF" => [
						'Valore' =>  (isset($bilancioData['AnalisiAdvanced']['Indici']['Copertura_Lorda_OF']) && $bilancioData['AnalisiAdvanced']['Indici']['Copertura_Lorda_OF'] < 1000) ? number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Copertura_Lorda_OF'], '2', ',', '.').'%' : ($bilancioData['AnalisiAdvanced']['Indici']['Copertura_Lorda_OF'] >= 1000 ? (number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Copertura_Lorda_OF'], '2', ',', '.')."% Valore anomalo") : (null)),
						'Score' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Copertura Lorda OF'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Copertura Lorda OF']['Scoring'] : null,
						'Giudizio' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Copertura Lorda OF'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Copertura Lorda OF']['Giudizio'] : null,
					],
					"CostoDelPersonale" => [
						'Valore' =>  (isset($bilancioData['AnalisiAdvanced']['Indici']['Costo_del_personale']) && $bilancioData['AnalisiAdvanced']['Indici']['Costo_del_personale'] < 1000) ? number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Costo_del_personale'], '2', ',', '.').'%' : ($bilancioData['AnalisiAdvanced']['Indici']['Costo_del_personale'] >= 1000 ? (number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Costo_del_personale'], '2', ',', '.')."% Valore anomalo") : (null)),
						'Score' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Costo del personale'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Costo del personale']['Scoring'] : null,
						'Giudizio' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Costo del personale'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Costo del personale']['Giudizio'] : null,
					],
					"CFAttivo" => [
						'Valore' =>  (isset($bilancioData['AnalisiAdvanced']['Indici']['CF_Attivo']) && $bilancioData['AnalisiAdvanced']['Indici']['CF_Attivo'] < 1000) ? number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['CF_Attivo'], '2', ',', '.').'%' : ($bilancioData['AnalisiAdvanced']['Indici']['CF_Attivo'] >= 1000 ? (number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['CF_Attivo'], '2', ',', '.')."% Valore anomalo") : (null)),
						'Score' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['CF Attivo'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['CF Attivo']['Scoring'] : null,
						'Giudizio' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['CF Attivo'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['CF Attivo']['Giudizio'] : null,
					],
					"IndiceDiIndebitamento" => [
						'Valore' =>  (isset($bilancioData['AnalisiAdvanced']['Indici']['Indice_di_Indebitamento']) && $bilancioData['AnalisiAdvanced']['Indici']['Indice_di_Indebitamento'] < 1000) ? number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Indice_di_Indebitamento'], '2', ',', '.').'%' : ($bilancioData['AnalisiAdvanced']['Indici']['Indice_di_Indebitamento'] >= 1000 ? (number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Indice_di_Indebitamento'], '2', ',', '.')."% Valore anomalo") : (null)),
						'Score' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Indice di Indebitamento'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Indice di Indebitamento']['Scoring'] : null,
						'Giudizio' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Indice di Indebitamento'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Indice di Indebitamento']['Giudizio'] : null,
					],
					"SaldoDeiDebitiVersoIlFisco" => [
						'Valore' =>  (isset($bilancioData['AnalisiAdvanced']['Indici']['Saldo_dei_Debiti_verso_il_Fisco']) && $bilancioData['AnalisiAdvanced']['Indici']['Saldo_dei_Debiti_verso_il_Fisco'] < 1000) ? number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Saldo_dei_Debiti_verso_il_Fisco'], '2', ',', '.').'%' : ($bilancioData['AnalisiAdvanced']['Indici']['Saldo_dei_Debiti_verso_il_Fisco'] >= 1000 ? (number_format((float)$bilancioData['AnalisiAdvanced']['Indici']['Saldo_dei_Debiti_verso_il_Fisco'], '2', ',', '.')."% Valore anomalo") : (null)),
						'Score' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Saldo dei Debiti verso il Fisco'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Saldo dei Debiti verso il Fisco']['Scoring'] : null,
						'Giudizio' => (isset($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Saldo dei Debiti verso il Fisco'])) ? $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Saldo dei Debiti verso il Fisco']['Giudizio'] : null,
					],
					'ResocontoAnomalie' => [
						'ListaSconfiniEntroNovantaGiorni' => $sconfiniDivisi['SconfiniEntro90Giorni'],
						'ListaSconfiniEntroCentoOttantaGiorni' => $sconfiniDivisi['SconfiniOltre90Giorni'],
						'ListaSconfiniOltreCentoOttantaGiorni' => $sconfiniDivisi['SconfiniOltre180Giorni'],
						'ListaAnomalie' => $anomalie,
					],
					"ASISfinalScore" => $ASISfinalScore,
					"bilancioData" => $bilancioData,
					"punteggioCR" => $punteggioCR,
					"alerts" => $alerts,
					"arrayQuestionario" => $questionarioAsIs,
					"arrayForwardLooking" => $forwardLooking,
					"scoreFL" => $scoreFL,
					"scoreASIS" => $scoreASIS,
					"generalScore" => $getGeneralScore['Giudizio'],
					"GiudizioFinale" => $getScoreHelper,
				];

				$printPDF = new printpdf;
				$printPDF->currentPayload = $dataAllerta;
				$documentId = $printPDF->generateDocument('allerta');
				sleep(5);

				return $printPDF->getDocumentData($documentId);

		/*	$printPDF = new printpdf;
	 
	 	$documentId = $printPDF->generateDocument('allerta', $idBilancio);
	 	sleep(5);

	 	return $printPDF->getDocumentData($documentId);  */
	}
}
