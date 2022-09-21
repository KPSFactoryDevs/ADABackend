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
		$id = $idBilancio;
		$ASISfinalScore = false;
		$generalScore = false;

		if (cr::where('document_id', $idCr)->count() == 0) {
			return response()->json([
				'error' => true,
				'message' => "Non è stata caricata nessuna Centrale Rischi"
			]);
		} else {


			$bilancioHelper = new BilanciHelper;
			
			$allertaHelper = new AllertaHelper;
			$setDocumentId = $allertaHelper->setDocumentId($idCr);
			$getDocumentId = $allertaHelper->getDocumentId();

			$crs = cr::select('anno', 'mese', 'date')->where('document_id', $getDocumentId)->distinct()->orderBy('date', 'asc')->get();

			for ($i = count($crs) - 12; $i < count($crs); $i++) {
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

			$upperBoundDate = new DateTime((cr::select('date')->where('anno', $earliestYear)->where('mese', $earliestMonth)->where('document_id', $getDocumentId)->get()->first())->date);
			$lowerBoundDate = new DateTime($upperBoundDate->format('Y-m-d'));
			$lowerBoundDate = $lowerBoundDate->modify('-11 months');

			$banks = array();

			foreach (cr::select('nome_banca')->where('date', '>=', $lowerBoundDate->format('Y-m-d'))->where('date', '<=', $upperBoundDate->format('Y-m-d'))->where('document_id', $getDocumentId)->distinct()->get()->toArray() as $label => $nomeBanca) {
				$banks[] = $nomeBanca["nome_banca"];
			}

			$crHelper = new CrExtractorHelper;
			$crHelper->setPeriod($periods);
			// $crHelper->setDocumentId($idCr);
			$cleanCR = $crHelper->getAllDataToArray($banks);
			$intermediari = $crHelper->getCountBanks($banks);

			$crExtractorHelper = new CrExtractorHelper;

			$allertaHelper->setCrExtractor($crExtractorHelper);

			$trimestrePeriod = $allertaHelper->getTrimestrePeriod($periods);
			$lastYearPeriod = $periods;

			$triennioPeriod = $allertaHelper->getTriennioPeriod($periods, $banks);

			$numeroSconfiniTotali = $crHelper->getTotaleSconfini($banks);
			$sofferenze = $crHelper->getSofferenze($banks);
			$creditiPassatiPerdita = $crHelper->getCreditiPassatiPerdita($banks);
			$crExtractorHelper->setPeriod($lastYearPeriod);
			$scoreCR = $crExtractorHelper->getScoring($banks, $intermediari, $numeroSconfiniTotali, $sofferenze, $creditiPassatiPerdita);
			$alerts = array();

			$alerts['1'] = $allertaHelper->getAnalisiCRUno($banks);

			$alerts['2'] = $allertaHelper->getAnalisiCRDue($banks);

			$alerts['3'] = $allertaHelper->getAnalisiCRTre($banks);

			$alerts['4'] = $allertaHelper->getAnalisiCRQuattro($triennioPeriod, $trimestrePeriod, $latestYear, $latestMonth, $categories);

			$alerts['5'] = $allertaHelper->getAnalisiCRCinque($periods);

			$alerts['6'] = $allertaHelper->getAnalisiCRSei($lastYearPeriod, $categories, $banks);

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

			$arrayQuestionario = array();

			$questionario = DB::table('questionario')->where('document_id', $getDocumentId)->get();
			foreach ($questionario as $item => $data) {
				$arrayQuestionario[$data->parameter]['Result'] = $data->result;
				$arrayQuestionario[$data->parameter]['Details'] = $data->details == null ? '' : $data->details;
			}

			// dd($questionario);

			$arrayForwardLooking = array();

			$forwardLooking = DB::table('forwardlooking')->where('document_id', $getDocumentId)->get();
			foreach ($forwardLooking as $item => $data) {
				$arrayForwardLooking[$data->question] = $data->answer;
			}

			if (count($arrayQuestionario) > 0) {
				$scoreASIS = $allertaHelper->valutazioneQuestionarioQualitativo($arrayQuestionario);
			} else {
				$scoreASIS = array('3' => 0, '4' => 0, '5' => 0, '6' => 0);
			}
			if (count($arrayForwardLooking) == 12) {
				$scoreFL = $allertaHelper->valutazioneFL($arrayForwardLooking);
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

				$bilancioData = $bilancioHelper->getAnalisiBilancio($id);

				// $bilancioData = $this->basic($id);

				// dd($bilancioData['Giudizi']['Score']);
				if (DB::table('questionario')->where('document_id', $getDocumentId)->count() != 0) {
					// dd($bilancioData);
					$ASISfinalScore = array("Score" => ($bilancioData['AnalisiAdvanced']['Giudizi']['Score'] * 0.25) + ($scoreCR * 0.25) + ($scoreASIS['1'] * 0.1) + ($scoreASIS['2'] * 0.1) + ($scoreASIS['3'] * 0.15) + ($scoreASIS['4'] * 0.15));

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

					if ($scoreASIS['4'] < 0.75) {
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

				$analisiBilancioGiudizio = null;

				if ($bilancioData['AnalisiAdvanced']['Giudizi']['Score'] >= 0 && $bilancioData['AnalisiAdvanced']['Giudizi']['Score'] < 0.14) {
					$analisiBilancioGiudizio = "Default";
				} else if ($bilancioData['AnalisiAdvanced']['Giudizi']['Score'] >= 0.14 && $bilancioData['AnalisiAdvanced']['Giudizi']['Score'] < 0.28) {
					$analisiBilancioGiudizio = "Situazione Grave";
				} else if ($bilancioData['AnalisiAdvanced']['Giudizi']['Score'] >= 0.28 && $bilancioData['AnalisiAdvanced']['Giudizi']['Score'] < 0.42) {
					$analisiBilancioGiudizio = "Alert";
				} else if ($bilancioData['AnalisiAdvanced']['Giudizi']['Score'] >= 0.42 && $bilancioData['AnalisiAdvanced']['Giudizi']['Score'] < 0.56) {
					$analisiBilancioGiudizio = "Rischio alert";
				} else if ($bilancioData['AnalisiAdvanced']['Giudizi']['Score'] >= 0.56 && $bilancioData['AnalisiAdvanced']['Giudizi']['Score'] < 0.70) {
					$analisiBilancioGiudizio = "Fragilità elevata";
				} else if ($bilancioData['AnalisiAdvanced']['Giudizi']['Score'] >= 0.70 && $bilancioData['AnalisiAdvanced']['Giudizi']['Score'] < 0.85) {
					$analisiBilancioGiudizio = "Fragilità";
				} else if ($bilancioData['AnalisiAdvanced']['Giudizi']['Score'] >= 0.85 && $bilancioData['AnalisiAdvanced']['Giudizi']['Score'] <= 1) {
					$analisiBilancioGiudizio = "Solidità";
				}
				$analisiCR = null;
				if ($punteggioCR >= 0 && $punteggioCR < 0.14) {
					$analisiCR = "Default";
				} else if ($punteggioCR >= 0.14 && $punteggioCR < 0.28) {
					$analisiCR = "Situazione Grave";
				} else if ($punteggioCR >= 0.28 && $punteggioCR < 0.42) {
					$analisiCR = "Alert";
				} else if ($punteggioCR >= 0.42 && $punteggioCR < 0.56) {
					$analisiCR = "Rischio alert";
				} else if ($punteggioCR >= 0.56 && $punteggioCR < 0.70) {
					$analisiCR = "Fragilità elevata";
				} else if ($punteggioCR >= 0.70 && $punteggioCR < 0.85) {
					$analisiCR = "Fragilità";
				} else if ($punteggioCR >= 0.85 && $punteggioCR <= 1) {
					$analisiCR = "Solidità";
				}
				$minacceRapportiComerciali = null;
				if ($scoreASIS['1'] >= 0 && $scoreASIS['1'] < 0.14) {
					$minacceRapportiComerciali = "Default";
				} else if ($scoreASIS['1'] >= 0.14 && $scoreASIS['1'] < 0.28) {
					$minacceRapportiComerciali = "Situazione Grave";
				} else if ($scoreASIS['1'] >= 0.28 && $scoreASIS['1'] < 0.42) {
					$minacceRapportiComerciali = "Alert";
				} else if ($scoreASIS['1'] >= 0.42 && $scoreASIS['1'] < 0.56) {
					$minacceRapportiComerciali = "Rischio alert";
				} else if ($scoreASIS['1'] >= 0.56 && $scoreASIS['1'] < 0.70) {
					$minacceRapportiComerciali = "Fragilità elevata";
				} else if ($scoreASIS['1'] >= 0.70 && $scoreASIS['1'] < 0.85) {
					$minacceRapportiComerciali = "Fragilità";
				} else if ($scoreASIS['1'] >= 0.85 && $scoreASIS['1'] <= 1) {
					$minacceRapportiComerciali = "Solidità";
				}

				$MinacceGestioneAziendale = null;
				if ($scoreASIS['2'] >= 0 && $scoreASIS['2'] < 0.14) {
					$MinacceGestioneAziendale = "Default";
				} else if ($scoreASIS['2'] >= 0.14 && $scoreASIS['2'] < 0.28) {
					$MinacceGestioneAziendale = "Situazione Grave";
				} else if ($scoreASIS['2'] >= 0.28 && $scoreASIS['2'] < 0.42) {
					$MinacceGestioneAziendale = "Alert";
				} else if ($scoreASIS['2'] >= 0.42 && $scoreASIS['2'] < 0.56) {
					$MinacceGestioneAziendale = "Rischio alert";
				} else if ($scoreASIS['2'] >= 0.56 && $scoreASIS['2'] < 0.70) {
					$MinacceGestioneAziendale = "Fragilità elevata";
				} else if ($scoreASIS['2'] >= 0.70 && $scoreASIS['2'] < 0.85) {
					$MinacceGestioneAziendale = "Fragilità";
				} else if ($scoreASIS['2'] >= 0.85 && $scoreASIS['2'] <= 1) {
					$MinacceGestioneAziendale = "Solidità";
				}
				$minacceERischiCaratteristici = null;
				if ($scoreASIS['4'] >= 0 && $scoreASIS['4'] < 0.14) {
					$minacceERischiCaratteristici = "Default";
				} else if ($scoreASIS['4'] >= 0.14 && $scoreASIS['4'] < 0.28) {
					$minacceERischiCaratteristici = "Situazione Grave";
				} else if ($scoreASIS['4'] >= 0.28 && $scoreASIS['4'] < 0.42) {
					$minacceERischiCaratteristici = "Alert";
				} else if ($scoreASIS['4'] >= 0.42 && $scoreASIS['4'] < 0.56) {
					$minacceERischiCaratteristici = "Rischio alert";
				} else if ($scoreASIS['4'] >= 0.56 && $scoreASIS['4'] < 0.70) {
					$minacceERischiCaratteristici = "Fragilità elevata";
				} else if ($scoreASIS['4'] >= 0.70 && $scoreASIS['4'] < 0.85) {
					$minacceERischiCaratteristici = "Fragilità";
				} else if ($scoreASIS['4'] >= 0.85 && $scoreASIS['4'] <= 1) {
					$minacceERischiCaratteristici = "Solidità";
				}
				$MinacceEventiPregiudizievoli = null;
				if ($scoreASIS['3'] >= 0 && $scoreASIS['3'] < 0.14) {
					$MinacceEventiPregiudizievoli = "Default";
				} else if ($scoreASIS['3'] >= 0.14 && $scoreASIS['3'] < 0.28) {
					$MinacceEventiPregiudizievoli = "Situazione Grave";
				} else if ($scoreASIS['3'] >= 0.28 && $scoreASIS['3'] < 0.42) {
					$MinacceEventiPregiudizievoli = "Alert";
				} else if ($scoreASIS['3'] >= 0.42 && $scoreASIS['3'] < 0.56) {
					$MinacceEventiPregiudizievoli = "Rischio alert";
				} else if ($scoreASIS['3'] >= 0.56 && $scoreASIS['3'] < 0.70) {
					$MinacceEventiPregiudizievoli = "Fragilità elevata";
				} else if ($scoreASIS['3'] >= 0.70 && $scoreASIS['3'] < 0.85) {
					$MinacceEventiPregiudizievoli = "Fragilità";
				} else if ($scoreASIS['3'] >= 0.85 && $scoreASIS['3'] <= 1) {
					$MinacceEventiPregiudizievoli = "Solidità";
				}
				$ProfiloRischioASIS = null;
				if ($ASISfinalScore) {
					$ProfiloRischioASIS = $ASISfinalScore['Giudizio'];
				}
				$questionarioToBe = null;
				if ($ASISfinalScore) {
					$questionarioToBe = $scoreFL['Giudizio'];
				}
				$ProfiloRischioComplessivo = null;
				if (isset($generalScore['Giudizio'])) {
					$ProfiloRischioComplessivo = $generalScore['Giudizio'];
				}


				$giudiziFinali = [
					'AnalisiBilancio' => $analisiBilancioGiudizio,
					'AnalisiCentraleRischi' => $analisiCR,
					'MinacceRapportiCommerciali' => $minacceRapportiComerciali,
					'MinacceGestioneAziendali' => $MinacceGestioneAziendale,
					'MinacceErarialiRischiCaratteristici' => $minacceERischiCaratteristici,
					'MinacceEventiPregiudizievoli' => $MinacceEventiPregiudizievoli,
					'ProfiloRischioASIS' => $ProfiloRischioASIS,
					'QuestionarioToBe' => $questionarioToBe,
					'ProfiloRischioComplessivo' => $ProfiloRischioComplessivo,
				];

				$questionarioAsIs = [];

				if (count($arrayQuestionario) > 0) {
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

				$acidTestValue = 0;
				$acidTestScoring = null;
				$acidTestGiudizio = "Ottimo";
				if (isset($bilancioData['Indici']['Acid_Test'])) {
					$acidTestValue = $bilancioData['Indici']['Acid_Test'];
					$acidTestScoring = $bilancioData['AnalisiAdvanced']['Giudizi']['AnalisiAdvanced']['Giudizi']['Acid Test']['Scoring'];
					$acidTestGiudizio = $bilancioData['AnalisiAdvanced']['Giudizi']['AnalisiAdvanced']['Giudizi']['Acid Test']['Giudizio'];
				}
				// dd($bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['ROS']['Scoring']);
				// dd($bilancioData['AnalisiAdvanced']['Giudizi']['AnalisiAdvanced']['Giudizi']);
				// dd($bilancioData['AnalisiAdvanced']['Giudizi']['AnalisiAdvanced']['Giudizi']['Andamento del fatturato']);

				$dataAllerta = [
					"id" => $id,
					"andamentoDelFatturato" => [
						'Valore' =>  $bilancioData['AnalisiAdvanced']['Indici']['Andamento_del_fatturato'],
						'Score' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Andamento del fatturato']['Scoring'],
						'Giudizio' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Andamento del fatturato']['Giudizio'],
					],
					"AndamentoDelMOL" => [
						'Valore' =>  $bilancioData['AnalisiAdvanced']['Indici']['Andamento_del_MOL'],
						'Score' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Andamento del MOL']['Scoring'],
						'Giudizio' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Andamento del MOL']['Giudizio'],
					],
					"ROI" => [
						'Valore' =>  $bilancioData['AnalisiAdvanced']['Indici']['ROI'],
						'Score' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['ROI']['Scoring'],
						'Giudizio' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['ROI']['Giudizio'],
					],
					"ROS" => [
						'Valore' =>  $bilancioData['AnalisiAdvanced']['Indici']['ROS'],
						'Score' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['ROS']['Scoring'],
						'Giudizio' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['ROS']['Giudizio'],
					],   
					"ROE" => [
						'Valore' =>  $bilancioData['AnalisiAdvanced']['Indici']['ROE'],
						'Score' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['ROE']['Scoring'],
						'Giudizio' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['ROE']['Giudizio'],
					],
					"EBITDAFatturato" => [
						'Valore' =>  $bilancioData['AnalisiAdvanced']['Indici']['EBITDA_Fatturato'],
						'Score' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['EBITDA Fatturato']['Scoring'],
						'Giudizio' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['EBITDA Fatturato']['Giudizio'],
					],
					"AndamentoDeiMezziPropri" => [
						'Valore' =>  $bilancioData['AnalisiAdvanced']['Indici']['Andamento_dei_mezzi_propri'],
						'Score' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Andamento dei mezzi propri']['Scoring'],
						'Giudizio' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Andamento dei mezzi propri']['Giudizio'],
					],
					"MargineStrutturaPrimario" => [
						'Valore' =>  $bilancioData['AnalisiAdvanced']['Indici']['Margine_Struttura_Primario'],
						'Score' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Margine Struttura Primario']['Scoring'],
						'Giudizio' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Margine Struttura Primario']['Giudizio'],
					],
					"MargineStrutturaSecondario" => [
						'Valore' =>  $bilancioData['AnalisiAdvanced']['Indici']['Margine_Struttura_Secondario'],
						'Score' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Margine Struttura Secondario']['Scoring'],
						'Giudizio' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Margine Struttura Secondario']['Giudizio'],
					],
					"CurrentRatio" => [
						'Valore' =>  $bilancioData['AnalisiAdvanced']['Indici']['Current_Ratio'],
						'Score' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Current Ratio']['Scoring'],
						'Giudizio' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Current Ratio']['Giudizio'],
					],
					"AcidTest" => [
						'Valore' =>  $acidTestValue,
						'Score' => $acidTestScoring,
						'Giudizio' => $acidTestGiudizio,
					],
					"AutonomiaFinanziaria" => [
						'Valore' =>  $bilancioData['AnalisiAdvanced']['Indici']['Autonomia_Finanziaria'],
						'Score' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Autonomia Finanziaria']['Scoring'],
						'Giudizio' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Autonomia Finanziaria']['Giudizio'],
					],
					"LivelloInvestimentiAziendali" => [
						'Valore' =>  $bilancioData['AnalisiAdvanced']['Indici']['Livello_investimenti_aziendali'],
						'Score' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Livello investimenti aziendali']['Scoring'],
						'Giudizio' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Livello investimenti aziendali']['Giudizio'],
					],
					"PFN_EBITDA" => [
						'Valore' =>  $bilancioData['AnalisiAdvanced']['Indici']['PFN_EBITDA'],
						'Score' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['PFN EBITDA']['Scoring'],
						'Giudizio' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['PFN EBITDA']['Giudizio'],
					],
					"OF_Fatturato" => [
						'Valore' =>  $bilancioData['AnalisiAdvanced']['Indici']['OF_Fatturato'],
						'Score' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Copertura Lorda OF']['Scoring'],
						'Giudizio' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Copertura Lorda OF']['Giudizio'],
					],
					"EBIT_OF" => [
						'Valore' =>  $bilancioData['AnalisiAdvanced']['Indici']['EBIT_OF'],
						'Score' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['EBIT OF']['Scoring'],
						'Giudizio' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['EBIT OF']['Giudizio'],
					],
					"CoperturaLordaOF" => [
						'Valore' =>  $bilancioData['AnalisiAdvanced']['Indici']['Copertura_Lorda_OF'],
						'Score' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Copertura Lorda OF']['Scoring'],
						'Giudizio' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Copertura Lorda OF']['Giudizio'],
					],
					"CostoDelPersonale" => [
						'Valore' =>  $bilancioData['AnalisiAdvanced']['Indici']['Costo_del_personale'],
						'Score' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Costo del personale']['Scoring'],
						'Giudizio' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Costo del personale']['Giudizio'],
					],
					"CFAttivo" => [
						'Valore' =>  $bilancioData['AnalisiAdvanced']['Indici']['CF_Attivo'],
						'Score' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['CF Attivo']['Scoring'],
						'Giudizio' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['CF Attivo']['Giudizio'],
					],
					"IndiceDiIndebitamento" => [
						'Valore' =>  $bilancioData['AnalisiAdvanced']['Indici']['Indice_di_Indebitamento'],
						'Score' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Indice di Indebitamento']['Scoring'],
						'Giudizio' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Indice di Indebitamento']['Giudizio'],
					],
					"SaldoDeiDebitiVersoIlFisco" => [
						'Valore' =>  $bilancioData['AnalisiAdvanced']['Indici']['Saldo_dei_Debiti_verso_il_Fisco'],
						'Score' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Saldo dei Debiti verso il Fisco']['Scoring'],
						'Giudizio' => $bilancioData['AnalisiAdvanced']['Giudizi']['Giudizi']['Saldo dei Debiti verso il Fisco']['Giudizio'],
					],
					"ASISfinalScore" => $ASISfinalScore,
					"generalScore" => $generalScore,
					"bilancioData" => $bilancioData,
					"punteggioCR" => $punteggioCR,
					"alerts" => $alerts,
					"arrayQuestionario" => $questionarioAsIs,
					"arrayForwardLooking" => $forwardLooking,
					"scoreFL" => $scoreFL,
					"scoreASIS" => $scoreASIS,
					"GiudizioFinale" => $giudiziFinali,
				];


				$printPDF = new printpdf;
				$printPDF->currentPayload = $dataAllerta;
				$documentId = $printPDF->generateDocument('allerta');
				sleep(5);

				return $printPDF->getDocumentData($documentId);
			} else {
				$msg = "Non è stata caricata nessun Bilancio";
				return view('allerta.empty', compact(['msg']));
			}
		}

		/*	$printPDF = new printpdf;
	 
	 	$documentId = $printPDF->generateDocument('allerta', $idBilancio);
	 	sleep(5);

	 	return $printPDF->getDocumentData($documentId);  */
	}
}
