<?php

namespace App\Http\Controllers;

ini_set('max_input_vars', 5000);

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
use App\Models\Document;

class PDFController extends Controller
{
	public function templateReportBasic($id)
	{
		$document = Document::find($id);

		$filePath = base_path() . '/public/bilanci/' . $document->filename;
		$taxonomyName = $document->taxonomy;
		$emptyInstance = false;
		$taxonomyPath = base_path()."/taxonomies/2018-11-04/".$taxonomyName;
		$readXBRL = \XBRL\XBRL_Instance::FromInstanceDocument($filePath, $taxonomyPath, $emptyInstance);
		$bilancioJSON = $readXBRL->toJSON();

	 	$Elements = $readXBRL->getElements();
		$elements = $Elements->getElements();

 		foreach($elements as $key => $singleElements) {
			foreach($singleElements as $singleElement) {
				$element[$key] = $singleElement['value'];
			}
		}

		$bilanciHelper = new BilanciHelper;
		$bilancioAnalisi = $bilanciHelper->getIndexesForBalanceTaxonomy($document->id, $filePath, $readXBRL, $document->codice_documento);
		$renderHTML = $bilanciHelper->generateHTMLRender($filePath, $taxonomyPath);



/* 		$bilancioAnalisi['Questionari'] = str_replace('dscrData', 'Alert DSCR', array_key_first($bilancioAnalisi['Questionari']));

		dd($bilancioAnalisi['Questionari']); */

		$statoPatrimonialeAttivoRange = array_slice($element, 9, 26);

/* 		$statoPatrimonialeAttivo = [
			'Crediti verso soci per versamenti ancora dovuti' => $statoPatrimonialeAttivoRange['TotaleCreditiVersoSociVersamentiAncoraDovuti'],
			'Immobilizzazioni (NON crediti finanziari)' => $statoPatrimonialeAttivoRange['TotaleImmobilizzazioni'],
			'Immobilizzazione - Crediti finanziari' => $statoPatrimonialeAttivoRange['TotaleImmobilizzazioniFinanziarie'],
			'Rimanenze' => $statoPatrimonialeAttivoRange['TotaleRimanenze'],
			'Crediti esigibili entro l\'esercizio successivo (NON finanziari)' => $statoPatrimonialeAttivoRange['CreditiEsigibiliEntroEsercizioSuccessivo'],
			'Crediti esigibili oltre l\'esercizio successivo' => $statoPatrimonialeAttivoRange['CreditiEsigibiliOltreEsercizioSuccessivo'],
			'C III) Attività finanziarie che non costituiscono immobilizzazioni' => $statoPatrimonialeAttivoRange['TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni'],
			'C IV) Disponibilità liquide' => $statoPatrimonialeAttivoRange['TotaleDisponibilitaLiquide'],
			'D) Ratei e risconti attivi' => $statoPatrimonialeAttivoRange['AttivoRateiRisconti'],
		]; */

/* 	 	foreach($statoPatrimonialeAttivo as $key => $singleStatoPatrimonialeAttivo) {
			if($singleStatoPatrimonialeAttivo > "0") {
				dump(number_format(floatval($singleStatoPatrimonialeAttivo), ',', '.', 2));
				$numberFormat = number_format(floatval($singleStatoPatrimonialeAttivo), ',', '.', 2);
				dump($numberFormat);

				//dd($numberFormat);

				//$statoPatrimonialeAttivo[$key] = number_format((int)$singleStatoPatrimonialeAttivo, ',', '.', 2);
			}
		} */
		//dd($statoPatrimonialeAttivo);
	//	$totaleAttivo = array_slice($element, 9, 26)['TotaleAttivo'];

		$nomeAzienda = $document->nome_azienda;
		$formaGiuridica = $document->forma_giuridica;
		$tipoAzienda = $document->tipo_azienda;
		$annoInizio = $document->anno_inizio;
		$annoFine = $document->anno_fine;

		$datiImpresa = [
			'ragione_sociale' => $nomeAzienda,
			'tipologia_impresa' => $formaGiuridica,
			'settore' => $tipoAzienda,
			'data_chiusura' => $annoInizio,
			'data_ultima' => $annoFine,
			'bilancioAnalisi' => $bilancioAnalisi,
			'renderHTML' => $renderHTML,
		];

		$pdf = PDF::loadView('frontend.reportBasic',['datiImpresa' => $datiImpresa])->setPaper('A4');;
		return $pdf->stream('result.pdf', array('Attachment'=>0));
	}
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
                $earlierDate = $earlierDate->modify('-12 months')->modify('first day of this month');
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

			// dd($totaleAffidamentiTable);
			foreach($totaleAffidamentiTable as $key => $singleAffidamento) {
					$totaleAffidamentiTable[$key]['totAccordatoOperativo'] = number_format($singleAffidamento['totAccordatoOperativo'], 2, ',', '.');
					$totaleAffidamentiTable[$key]['totUtilizzato'] = number_format($singleAffidamento['totUtilizzato'], 2, ',', '.');
					$totaleAffidamentiTable[$key]['PesoAccordatoOperativo'] = number_format($singleAffidamento['PesoAccordatoOperativo'], 2, ',', '.');
					$totaleAffidamentiTable[$key]['PesoUtilizzato'] = number_format($singleAffidamento['PesoUtilizzato'], 2, ',', '.');
			}
			$scoreCR = number_format($scoreCR, 2, ',', '.');
			$finalScoreMoltiplied = (float)str_replace(',', '.', $scoreCR)*10;

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
                        'FinalScore' => number_format($finalScoreMoltiplied, 2, ',', '.')
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
                'generalDates' => $generalDates
            ];


            $pdf = PDF::loadView('frontend.reportAndamentale',['dati' => $response])->setPaper('A4');;
            return $pdf->stream('result.pdf', array('Attachment'=>0));

		}
	}


	public function reportAllerta($idBilancio, $idCr)
	{
        if (cr::where('document_id', $idCr)->get()->count() == 0 || !isset($idCr)) {
            return response()->json([
                'error' => true,
                'message' => 'Non è stata trovata nessuna Centrale Rischi'
            ], 400);
        }

        if (Document::where('id', $idBilancio)->where('type', 'bilancio')->get()->count() == 0 || !isset($idBilancio)) {
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
        $bilancioData = $bilancioHelper->getIndexesForBalanceTaxonomy($idBilancio, false, false);

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

        $arrayQuestionario = $allertaHelper->getArrayQuestionarioAsIs($idBilancio, $idCr);
        $arrayForwardLooking = $allertaHelper->getArrayQuestionarioToBe($idBilancio, $idCr);



        if (count($arrayQuestionario) > 0) {
            $scoreASIS = $allertaHelper->valutazioneQuestionarioQualitativo($arrayQuestionario);
        }

        if (count($arrayForwardLooking) == 12) {
            $scoreFL = $allertaHelper->valutazioneFL($arrayForwardLooking);
        }



        $ASISfinalScore = $allertaHelper->getAsIsFinalScore($bilancioData['ValutazioneGenerale'], $scoreCR, $scoreASIS);
        $getScoreHelper = $allertaHelper->getScores($punteggioCR, $bilancioData['ValutazioneGenerale'], $scoreASIS, $ASISfinalScore, $scoreFL);

        $dataAllerta = [
            'error' => false,
            'pageData' => [
                'scoreCR' => number_format($scoreCR, 2, ',', '.'),
                'crAlerts' => $alerts,
                'arrayQuestionarioAsIs' => $arrayQuestionario,  // questionario per document id e bilancio id
                'arrayForwardLookingToBe' => $arrayForwardLooking,
                'bilancioData' => $bilancioData,
                'ValutazioneGeneraleBilancio' => $bilancioData['ValutazioneGenerale'],
                'FinalScore' => $getScoreHelper['FinalScore']
            ],
            'GeneralScore' => [
                'Giudizio_CR' => $getScoreHelper['resultCentraleRischi'],
                'Giudizio_Bilancio' => $getScoreHelper['resultAnalisiBilancio'],
                'Minacce rapporti commerciali' => $getScoreHelper['resultMinacceRapportiCommerciali'],
                'Minacce gestione aziendale' => $getScoreHelper['resultMinacceGestioneAziendale'],
                'Minacce da eventi pregiudizievoli' => $getScoreHelper['resultMinacceEventiPregiudizievoli'],
                'Minacce erariali e rischi caratteristici' =>  $getScoreHelper['resultMinacceRischiCaratteristici'],
                'Profilo rischio AS IS' => $getScoreHelper['ASISScore'],
                'Questionario TO BE' => $getScoreHelper['scoreGiudizioFL'],
            ],
        ];
				$printPDF = new printpdf;
				$printPDF->currentPayload = $dataAllerta;

        $pdf = PDF::loadView('frontend.reportAllerta',['dati' => $dataAllerta])->setPaper('A4');;
        return $pdf->stream('result.pdf', array('Attachment'=>0));


	}
}
