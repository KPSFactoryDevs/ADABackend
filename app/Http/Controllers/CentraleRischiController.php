<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\ElaborateLatestCR;
use App\Http\Requests;
use App;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Models\Bilanci;
use App\Models\Account;
use App\Models\cr;
use Symfony\Component\Process\Process;
use App\Helpers\CentraleRischi\CrExtractorHelper;
use App\Helpers\CentraleRischi\newCrExtractor;
use App\Models\Document;
use DateTime;
use Storage;
use Exception;

class CentraleRischiController extends Controller
{


    public function getDocuments(Request $request)
    {


        if ($request->header('currentcompany') || $request->header('currentcompany') === 0) {
            $documentsCr = Document::where('company_id', $request->header('currentcompany'))->orderBy('created_at', 'desc')->get();
        } else {
            $documentsCr = Document::orderBy('created_at', 'desc')->get();
        }


        foreach ($documentsCr as $singleDocument) {
            $textPeriodAvailable = "";
            $periodAvailable = cr::select(['mese','anno'])
                ->Where('document_id', $singleDocument->codice_documento)
                ->groupBy('anno','mese')
                ->get();
            foreach($periodAvailable as $singlePeriod) {
                $textPeriodAvailable .= substr(ucFirst($singlePeriod->mese),0,3)." ".$singlePeriod->anno. ', ';
            }
            $singleDocument['status'] = ucfirst(str_replace('_', ' ', $singleDocument['status']));
            $singleDocument['type'] = ucfirst($singleDocument['type']);
            $singleDocument['availableMonths'] = $textPeriodAvailable;
        }


        return response()->json([
            $documentsCr,
        ]);

    }

    public function getDocumentsById($id)
    {
        $singleDocument = Document::findOrFail($id);

        return response()->json([
            'error' => false,
            'data' => $singleDocument
        ]);
    }


    public function recap(Request $request, $documentId, $liveStatus, $page = "all")
    {
        // query che recupera 1 sola centrale rischi in status da elaborare
        // passiamo il filepath al python che elabora ed importa i dati.
        $crFileToElaborate = Document::where('codice_documento', $documentId)->first();
        $filepath = $crFileToElaborate->path;


        $process = new Process(['python3', base_path() . '/crExtractor.py', $filepath, $page]);

        $process->setTimeout(10000);

        try {
            $process->run();
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
            $crFileToElaborate->status = $liveStatus;
            $crFileToElaborate->save();
        } catch (ProcessFailedException $e) {
            $crFileToElaborate->status = $liveStatus;
            $crFileToElaborate->save();
            dd($e);
        }

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        } else {

            $jsonData = $process->getOutput();
            $jsonArray = json_decode($jsonData);
            $CentraleRischiAggregateData = new App\Helpers\CentraleRischi\CentraleRischiAggregateData();
            $dataToSave = $CentraleRischiAggregateData->crJsonToArray($jsonArray);


            $codiceDocumento = $crFileToElaborate->codice_documento;
            $companyId = $crFileToElaborate->company_id;
            $CentraleRischiStoreDataHelper = new App\Helpers\CentraleRischi\CentraleRischiStoreDataHelper();


            foreach ($dataToSave as $anno => $months) {
                foreach ($months as $mese => $data) {
                    foreach ($data as $singleBank => $keys) {
                        foreach ($keys as $index => $value) {
                            $mesiList = ["0" => "fuoriMese", "gennaio" => 1, 'febbraio' => 2, 'marzo' => 3, 'aprile' => 4, 'maggio' => 5, 'giugno' => 6, 'luglio' => 07, "agosto" => 8, 'settembre' => 9, 'ottobre' => 10, 'novembre' => 11, 'dicembre' => 12,];
                            $transformedMonth = $mesiList[$mese];

                            if ($index === 'Firma') {
                                $CentraleRischiStoreDataHelper->saveFirma($value, $anno, $mese, $transformedMonth, $singleBank, $index, $codiceDocumento, $companyId);
                            }

                            if ($index === 'Garanzie') {
                                $CentraleRischiStoreDataHelper->saveGaranzie($value, $anno, $mese, $transformedMonth, $singleBank, $index, $codiceDocumento, $companyId);
                            }

                            if ($index === 'Sofferenze') {
                                $CentraleRischiStoreDataHelper->saveSofferenze($value, $anno, $mese, $transformedMonth, $singleBank, $index, $codiceDocumento, $companyId);
                            }

                            if ($index === 'Cassa') {
                                $CentraleRischiStoreDataHelper->saveCassa($value, $anno, $mese, $transformedMonth, $singleBank, $index, $codiceDocumento, $companyId);
                            }

                            if ($index === 'Informativa') {
                                $CentraleRischiStoreDataHelper->saveInformativa($value, $anno, $mese, $transformedMonth, $singleBank, $index, $codiceDocumento, $companyId);
                            }

                            if ($index === 'Garanti') {
                                $CentraleRischiStoreDataHelper->saveGaranti($value, $anno, $mese, $transformedMonth, $singleBank, $index, $codiceDocumento, $companyId);
                            }
                        }
                    }
                }
            }


            return response()->json([
                'error' => false,
                'data' => 'File Centrale Rischi' . $crFileToElaborate->status
            ]);
        }
    }

    /**
     * @return mixed
     */
    public function store(Request $request)
    {

        $base64CentraleRischi = $request->base64;

        $storedFile = Storage::disk('public')->putFile('', $base64CentraleRischi);
        $storeFullPath = asset('centraleRischi') . '/' . $storedFile;

        $newDocumentData = [
            'filename' => time().'_'.$base64CentraleRischi->getClientOriginalName(),
            'path' => $storeFullPath,
            'type' => 'centrale rischi',
            'codice_documento' => rand(1, 999999999),
            'status' => 'Da Elaborare',
            'company_id' => null
        ];

        if ($request->header('currentcompany') || $request->header('currentcompany') == 0) {
            $newDocumentData["company_id"] = $request->header('currentcompany');
        }

        try {
            $documentCreated = Document::create($newDocumentData);
        } catch (Exception $e) {
            return response()->json([
                'exception' => $e,
            ], 500);
        }


        $processGetPages = new Process(['qpdf','--show-npages','/var/www/html/staging/public/centraleRischi/'.$storedFile]);

        $processGetPages->setTimeout(120);

        try {
            $processGetPages->run();
            if (!$processGetPages->isSuccessful()) {
                throw new ProcessFailedException($processGetPages);
            }
        } catch (ProcessFailedException $e) {
            dd($e);
        }

        $totalPages = $processGetPages->getOutput();
        $totalPages = str_replace('/n','',$totalPages);
        $totalPages = (int)$totalPages;

        if($totalPages > 0 && is_int($totalPages)) {
            for ($pageToExtract = 1; $pageToExtract <= $totalPages; $pageToExtract++) {
                $liveStatus = round( (($pageToExtract / $totalPages) * 100), 1 ). "% processato";
                if($pageToExtract == $totalPages) {
                    $liveStatus = "Completato";
                }
                ElaborateLatestCR::dispatch($pageToExtract, $documentCreated["codice_documento"], $liveStatus);
            }
        } else {
            return response()->json([
                'error' => true,
                'message' => "No pages detected on file"
            ], 500);
        }

        return response()->json([
            'newDocumentCreated' => $documentCreated,
        ], 200);

    }


    public function crAndamentale(Request $request)
    {

        $crAndamentaleData = $request->all();
        $crAndamentaleData['period'] = $request->period;
        unset($crAndamentaleData['_token']);


        if (!isset($crAndamentaleData['period'])) {
            $msg = "Non è stato selezionato nessun documento";
            return view('allerta.empty', compact(['msg']));
        } else {

            $categories = array(
                'RISCHI A SCADENZA',
                'RISCHI AUTOLIQUIDANTI',
                'RISCHI A REVOCA',
                'SOFFERENZE'
            );

            $crHelper = new CrExtractorHelper;

                if(!isset($crAndamentaleData['newDates'])) {
                    $lastDate = new DateTime(cr::select('date')->where('document_id', $crAndamentaleData['period'])->orderBy('date', 'desc')->first()->date);
                    $earlierDate = new DateTime(cr::select('date')->where('document_id', $crAndamentaleData['period'])->orderBy('date', 'desc')->first()->date);
                    $earlierDate = $earlierDate->modify('-23 months');
                } else {
                    $earlierDate = $crAndamentaleData['newDates']['data_inizio'];
                    $lastDate = $crAndamentaleData['newDates']['data_fine'];

                    $earlierDate = new DateTime($earlierDate);
                    $lastDate = new DateTime($lastDate);
                }



            $unrefinedPeriods = json_decode(DB::table('crs')
                ->select('anno', 'mese', 'date')
                ->where("date", '>', $earlierDate->modify('first day of this month')->format('Y-m-d'))->where("date", '<', $lastDate->modify('last day of this month')->format('Y-m-d'))
				->where('document_id', $crAndamentaleData['period'])
                ->groupBy('date', 'anno', 'mese')
                ->orderBy('date')
                ->get(), true);


            $periods = $crHelper->getCleanPeriods($unrefinedPeriods);
            $crHelper->setPeriod($periods);
            $periodsCorrect = $crHelper->buildPeriodArray();
            $crHelper->setDocumentId($crAndamentaleData['period']);
            $banks = $crHelper->getGeneratedbanks($request, $crAndamentaleData, $periodsCorrect, $categories);

            $latestYear = array_key_last($periods);
            $latestMonth = array_key_last($periods[$latestYear]);
            $earliestYear = array_key_first($periods);
            $earliestMonth = array_key_first($periods[$earliestYear]);
            $finePeriodo = $latestMonth . ' ' . $latestYear;
            $inizioPeriodo = $earliestMonth . ' ' . $earliestYear;

            $missingMonths = $crHelper->missingMonths($unrefinedPeriods);
            $intermediari = $crHelper->getCountBanks($banks);
            $mediaAnalisiIndebitamento = $crHelper->getMediaIndebitamento($banks);
            $numeroSconfiniTotali = $crHelper->getTotaleSconfini($banks);
            $rischiGaranzie = $crHelper->getRischiGaranzie($banks);
            $totaleAffidamentiTable = $crHelper->getTotaleAffidamenti($categories, $latestYear, $latestMonth, $banks);
            $totaleAffidamentiGeneral = $crHelper->getTotaleAffidamentiGeneral($categories, $latestYear, $latestMonth, $banks);
            $totAffidamentiConPesiPerBanca = $crHelper->getPesiAffidamentiPerBanca($categories, $latestYear, $latestMonth, $banks);
        //    $scoreCR = $crHelper->getScoring($banks);
            $creditiContestati = $crHelper->getCreditiContestati($banks);
            $numeroRapportiContestati = count($creditiContestati);
            $impagati = $crHelper->getAlertImpagati($banks);
            $garanzieEsitoNegativo = $crHelper->getGaranzieEsitoNegativo($banks);
            $sofferenze = $crHelper->getSofferenze($banks);
            $creditiPassatiPerdita = $crHelper->getCreditiPassatiPerdita($banks);
            $anomalie = $crHelper->getAnomalie($banks);
            $incidenzaImpagati = $crHelper->getPercentualeMediaImpagati($banks);
            $informazioniGaranti = $crHelper->getInformazioniGaranti($banks);
            $garanzieRicevute = $crHelper->getGaranzieRicevute($banks);
            $importiSconfini = $crHelper->getImportiSconfini($banks);
            $affidamentiPerMese = $crHelper->getTotaleAffidamentiPerMese($periods, $categories, $banks);
            $anomalieStatoRapporto = $crHelper->mancateSegnalazioniStatoRapporto($banks);
            $sconfiniDivisi = $crHelper->divideAnomalie($numeroSconfiniTotali, $banks);
         //   $banksScoring = $crHelper->singleBankData($banks, $periods);
            $informazioniGarantiAnomalie = $crHelper->informazioniSuiGaranti($informazioniGaranti);
            $percentualiAccordato = $crHelper->percentualiAccordato($totAffidamentiConPesiPerBanca);
            $percentualiUtilizzato = $crHelper->percentualiUtilizzato($totAffidamentiConPesiPerBanca);
            $totaleUtilizzatoGeneral = $crHelper->totAffidamentiConPesiPerBanca($totAffidamentiConPesiPerBanca);
            $monthsList = array_keys($affidamentiPerMese);

            $response = [
                'Scoring' => [
                    'Panoramica' => [
                        'PeriodoRiferimento' => [
                            'Inizio' =>  ucFirst($inizioPeriodo),
                            'Fine' => ucFirst($finePeriodo),
                            ],
                        'NumeroIntermediari' => $intermediari,
                        'NumeroPosizioniContestate' => $numeroRapportiContestati,
                //        'FinalScore' => $scoreCR
                    ],
                    'AnomalieUtilizzi' => [
                        'TensioneAutoliquidanti' => $numeroSconfiniTotali['Tensioni']['RISCHI AUTOLIQUIDANTI'],
                        'TensioneRevoca' => $numeroSconfiniTotali['Tensioni']['RISCHI A REVOCA'],
                        'TensioneScadenza' => $numeroSconfiniTotali['Tensioni']['RISCHI A SCADENZA'],
                    ],
                    'AnomalieLievi' => [
                        'Impagati' => $impagati,
                        'Sconfini' => $numeroSconfiniTotali['PresenzaSconfini'],
                    ],
                    'AnomalieQuasiPregiudizievoli' => [
                        'SconfiniEntroNovantaGiorni' => (!empty($numeroSconfiniTotali['SconfiniEntro90Giorni'])),
                        'SconfiniEntroCentoOttantaGiorni' => (!empty($numeroSconfiniTotali['SconfiniOltre90Giorni'])),
                        'SconfiniOltreCentoOttantaGiorni' => (!empty($numeroSconfiniTotali['SconfiniOltre180Giorni'])),
                    ],
                    'AnomaliePregiudizievoli' => [
                        'GaranzieAttivateEsitoNegativo' => ($garanzieEsitoNegativo>0),
                        'Sofferenze' => (!empty($sofferenze)),
                        'CreditiPassatiPerdita' => (!empty($creditiPassatiPerdita)),
                    ],
                ],
                'ResocontoAnomalie' => [
                    'ListaSconfiniEntroNovantaGiorni' => $numeroSconfiniTotali['SconfiniEntro90Giorni'],
                    'ListaSconfiniEntroCentoOttantaGiorni' => $numeroSconfiniTotali['SconfiniOltre90Giorni'],
                    'ListaSconfiniOltreCentoOttantaGiorni' => $numeroSconfiniTotali['SconfiniOltre180Giorni'],
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
                            'TotaleCreditiScadutiImpoagati' => $rischiGaranzie['CreditiScadutiImpagati'],
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

            ];
            return response()->json([
                'error' => false,
                'anomalieStatoRapporto' => $anomalieStatoRapporto,
                'anomalie' => $anomalie,
                'missingMonths' => $missingMonths,
                'sconfiniDivisi' => $sconfiniDivisi,
                'garanzieRicevute' => $garanzieRicevute,
                'informazioniGaranti' => $informazioniGaranti,
                'monthsList' => $monthsList,
                'affidamentiPerMese' => $affidamentiPerMese,
          //      'banksScoring' => $banksScoring,
                'totaleAffidamentiGeneral' => $totaleAffidamentiGeneral,
                'incidenzaImpagati' => $incidenzaImpagati,
                'rischiGaranzie' => $rischiGaranzie,
                'percentualiUtilizzato' => $percentualiUtilizzato,
                'percentualiAccordato' => $percentualiAccordato,
                'latestMonth' => $latestMonth,
                'latestYear' => $latestYear,
                'importiSconfini' => $importiSconfini,
                'creditiPassatiPerdita' => $creditiPassatiPerdita,
                'sofferenze' => $sofferenze,
                'garanzieEsitoNegativo' => $garanzieEsitoNegativo,
                'impagati' => $impagati,
                'numeroRapportiContestati' => $numeroRapportiContestati,
                'inizioPeriodo' => $inizioPeriodo,
                'finePeriodo' => $finePeriodo,
                'intermediari' => $intermediari,
                'numeroSconfiniTotali' => $numeroSconfiniTotali,
                'mediaAnalisiIndebitamento' => $mediaAnalisiIndebitamento,
                'totaleAffidamentiTable' => $totaleAffidamentiTable,
                'totAffidamentiConPesiPerBanca' => $totAffidamentiConPesiPerBanca,
                'totaleAccordatoUtilizzatoPerBancaGeneral' => $totaleUtilizzatoGeneral,
                'informazioniGarantiAnomalie' => $informazioniGarantiAnomalie,
               // 'scoreCR' => $scoreCR,

                'newFutureArray' => $response
            ]);
        }
    }

    public function dettagliata(Request $request)
    {
        $lastTwelveMonths = cr::select('anno', 'mese', 'date')->orderBy('date', 'desc')->take(12)->distinct()->get()->toArray();
        $trimestri = array();
        for ($i = 1; $i <= 12; $i++) {
            if ($i % 3 == 0) {
                for ($j = ($i - 3); $j < $i; $j++) {
                    $trimestri[$i / 3][] = array('anno' => $lastTwelveMonths[$j]['anno'], 'mese' => $lastTwelveMonths[$j]['mese']);
                }
            }
        }
        $trimestri = array_reverse($trimestri);
        foreach ($trimestri as $index => $singleTrimestre) {
            $trimestri[$index] = array_reverse($singleTrimestre);
        }

        $crHelper = new CrExtractorHelper;
        $trimestriData = array(1 => array(), 2 => array(), 3 => array(), 4 => array());
        // dd($trimestri);
        foreach ($trimestri as $trimestre => $singlePeriod) {
            //Pagina 1 - Composizione delle linee di credito, Composizione delle linee di credito per banca e modalità utilizzo linee di credito
            $affidamentiPerCategoria[$trimestre] = $crHelper->getAffidamentiGeneralTrimestrale($singlePeriod);
            //Pagina 1 - Verifica presenza di sconfini
            $presenzaSconfini[$trimestre] = $crHelper->getPresenzaSconfini($singlePeriod);
            //Pagina 1 - Verifica sui crediti scaduti
            $presenzaCreditiScaduti[$trimestre] = $crHelper->verificaImpagati($singlePeriod);
            //Pagina 1 - verifica disponibilità inutilizzata
            $disponibilitaInutilizzata[$trimestre] = $crHelper->verificaDisponibilita($singlePeriod);
            //Pagina 1 - peso debiti a breve termine
            $pesoDebitiBreveTermine[$trimestre] = $crHelper->pesoDebitiBreveTermine($singlePeriod);
            //Pagina 1 - Segnalazioni gravi
            $segnalazioniGravi[$trimestre] = $crHelper->verificaSegnalazioniGravi($singlePeriod);
            //Pagina 1 - Verifica delle garanzie
            $verificaGaranzie[$trimestre] = $crHelper->verificaGaranzie($singlePeriod);
            //Pagina 2 - Uso degli affidamenti
            $affidamenti[$trimestre] = $crHelper->usoAffidamenti($singlePeriod);
            //Pagina 3 - Analisi sconfini
            $analisiSconfini[$trimestre] = $crHelper->analisiSconfini($singlePeriod);
            //Pagina 4 - Analisi crediti e situazioni a rischio
            $creditiRischi[$trimestre] = $crHelper->creditiRischi($singlePeriod);
            //Pagina 5 - Peso debiti breve termine
            $creditiScadutiBreveTermine[$trimestre] = $crHelper->getCreditiScadutiBreveTermine($singlePeriod);
            //Pagina 6 - Informazioni sui garanti
            $informazioniSuiGaranti[$trimestre] = $crHelper->getInformazioniGarantiTrimestrale($singlePeriod);
        }

        $styles = array(
            'green' => "background-color: lime; color: black; border-radius: 50%; width: 20px",
            'yellow' => "background-color: yellow; color: black; border-radius: 50%; width: 20px",
            'orange' => "background-color: orange; color: black; border-radius: 50%; width: 20px",
            'red' => "background-color: red; color: black; border-radius: 50%; width: 20px"
        );

        $data = [
            'styles' => $styles,
            'informazioniSuiGaranti' => $informazioniSuiGaranti,
            'creditiScadutiBreveTermine' => $creditiScadutiBreveTermine,
            'creditiRischi' => $creditiRischi,
            'analisiSconfini' => $analisiSconfini,
            'affidamenti' => $affidamenti,
            'verificaGaranzie' => $verificaGaranzie,
            'segnalazioniGravi' => $segnalazioniGravi,
            'pesoDebitiBreveTermine' => $pesoDebitiBreveTermine,
            'disponibilitaInutilizzata' => $disponibilitaInutilizzata,
            'presenzaCreditiScaduti' => $presenzaCreditiScaduti,
            'presenzaSconfini' => $presenzaSconfini,
            'affidamentiPerCategoria' => $affidamentiPerCategoria,
            'lastTwelveMonths' => $lastTwelveMonths,
            'trimestri' => $trimestri
        ];

        // dd($affidamenti);
        return response()->json([
            'error' => false,
            'data' => $data
        ]);

    }

    public function destroy($idDocument)
    {
        if(!$idDocument) {
            return response()->json([
                'error' => true,
                'message' => 'Specifica l\'Id del bilancio',
            ]);
        }
        try {
            $document = Document::findOrFail($idDocument);
            $crRows = cr::where('document_id', $document->codice_documento);

            $crRows->delete();
            $document->delete();

            return response()->json([
                'error' => false,
                'message' => 'Bilancio eliminato correttamente',
            ]);
        } catch (Excepton $e) {
            return response()->json([
                'error' => false,
                'type' => 'Eccezione',
                'message' => $e,
            ]);
        }
    }


}
