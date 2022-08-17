<?php

namespace App\Http\Controllers;

ini_set('max_input_vars', 5000);

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Jobs\ElaborateLatestCR;
use App\Http\Requests;
use App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use lyquidity\xml\QName;
use lyquidity\XPath2\XPath2Exception;
use PhpParser\Node\Stmt\Foreach_;
use Symfony\Component\Process\Exception\ProcessFailedException;
use XBRL\XBRL_Instance;
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

    public function truncateCR()
    {
        cr::truncate();


        return back();
    }

    /**
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $bilancis = Bilanci::paginate(25);

        return view('bilanci.index', compact('bilancis'));
    }

    /**
     * @return mixed
     */
    public function create()
    {
        $accounts = Account::pluck('name', 'id')->all();

        return view('centralerischi.create', compact('accounts'));
    }

    public function getDocuments(Request $request)
    {


        if ($request->header('currentcompany') || $request->header('currentcompany') === 0) {
            $documentsCr = Document::where('company_id', $request->header('currentcompany'))->orderBy('created_at', 'desc')->get();
        } else {
            $documentsCr = Document::orderBy('created_at', 'desc')->get();
        }

        //$documentsBilanci = Document::where('type', 'bilancio')->get();

        foreach ($documentsCr as $singleDocument) {
            $textPeriodAvailable = "";
            $periodAvailable = cr::select(['mese','anno'])->Where('document_id', $singleDocument->codice_documento)->get();
            foreach($periodAvailable as $singlePeriod) {
                $textPeriodAvailable .= substr(ucFirst($singlePeriod->mese),0,3)." ".$singlePeriod->anno." - ";
            }
            $singleDocument['status'] = ucfirst(str_replace('_', ' ', $singleDocument['status']));
            $singleDocument['type'] = ucfirst($singleDocument['type']);
            $singleDocument['availableMonths'] = $textPeriodAvailable;
        }


        return response()->json([
            $documentsCr,
        ]);

        /*	$documentsBilanci = Document::where('type', 'bilancio')->get();

            return response()->json([
                'error' => false,
                'cr' => $documentsCr,
                'bilanci' => $documentsBilanci
            ]);  */
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


        // $filepath = 'storage/path/to/file/test.pdf';
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
            'filename' => $base64CentraleRischi, time().'_'.$base64CentraleRischi->getClientOriginalName(),
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


        //$fileToRead = file_get_contents($storeFullPath);
      //  $totalPages = preg_match_all("/\/Page\W/", $fileToRead, $dummy);

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




    /**
     * @param  DeleteUserRequest  $request
     * @param  User  $user
     *
     * @return mixed
     * @throws \App\Exceptions\GeneralException
     */
    public function destroy(Bilanci $bilancio)
    {
        $attributes = $bilancio->getAttributes();
        $idDel = $attributes['id'];
        $bilanciSing = Bilanci::find($idDel);
        $bilanciSing->delete(); //delete the client

        return redirect()->route('admin.bilanci.bilancio.index')->withFlashSuccess(__('The Bilancio was successfully deleted.'));
    }


    public function datatable()
    {

        $accounts = Account::All();
        $crs = cr::select('id', 'anno', 'mese', 'date')->get();

        $crData = array();

        $mesiList = [0 => 'gennaio', 1 => 'febbraio', 2 => 'marzo', 3 => 'aprile', 4 => 'maggio', 5 => 'giugno', 6 => 'luglio', 7 => 'agosto', 8 => 'settembre', 9 => 'ottobre', 10 => 'novembre', 11 => 'dicembre'];
        $formattedMonths = ['gennaio' => '01', 'febbraio' => '02', 'marzo' => '03', 'aprile' => '04', 'maggio' => '05', 'giugno' => '06', 'luglio' => '07', 'agosto' => '08', 'settembre' => '09', 'ottobre' => '10', 'novembre' => '11', 'dicembre' => '12'];

        foreach ($crs as $tmpLabel => $toFixData) {
            $tmpDate = new DateTime($toFixData->date);

            if ($mesiList[((int)$tmpDate->format('m') - 1)] != $toFixData->mese) {
                cr::where('id', $toFixData->id)->update(['date' => $tmpDate->format('Y') . '-' . $formattedMonths[$toFixData->mese] . '-' . $tmpDate->format('d')]);
            }
        }

        $banks = cr::select('nome_banca')->distinct()->get()->toArray();

        if (count($crs) > 0) {

            $periods = array();

            foreach ($crs as $data => $value) {
                $periods[$value->anno][$value->mese] = null;
                $periods[$value->anno][$value->mese] = array_search($value->mese, $mesiList);
                asort($periods[$value->anno]);
            }

            ksort($periods);

            $latestYear = array_key_last($periods);
            $latestMonth = array_key_last($periods[$latestYear]);

            $lastDate = new DateTime((cr::select('date')->where('anno', $latestYear)->where('mese', $latestMonth)->first())->date);

            $earliestDate = new DateTime((cr::select('date')->orderBy('date', 'asc')->first())->date);

            $lastYear = (new DateTime($lastDate->format('d-m-Y')))->modify('-11 months');

            if (cr::select('date')->where('date', $lastYear)->count() == 0) {
                $lastYear = new DateTime($earliestDate->format('d-m-Y'));
            }

            $lastTwoYears = (new DateTime($lastDate->format('d-m-Y')))->modify('-23 months');

            if (cr::select('date')->where('date', $lastTwoYears)->count() == 0) {
                $lastTwoYears = new DateTime($earliestDate->format('d-m-Y'));
            }

            $lastThreeYears = (new DateTime($lastDate->format('d-m-Y')))->modify('-35 months');

            if (cr::select('date')->where('date', $lastThreeYears)->count() == 0) {
                $lastThreeYears = new DateTime($earliestDate->format('d-m-Y'));
            }

            // dd($lastYear, $lastTwoYears, $lastThreeYears, $earliestDate, $lastDate);

            return view('centralerischi.allcr', compact('banks', 'lastYear', 'lastTwoYears', 'lastThreeYears', 'earliestDate', 'lastDate', 'periods', 'accounts', 'crData'));
        } else {
            return view('centralerischi.allcr');
        }
    }

    public function crAndamentale(Request $request)
    {
        $mesiCheckList = [0 => "fuoriMese", 1 => "gennaio", 2 => 'febbraio', 3 => 'marzo',   4 => 'aprile',   5 => 'maggio',   6 => 'giugno',   7 => 'luglio',   8 => "agosto",   9 => 'settembre',   10 => 'ottobre',   11 => 'novembre',   12 => 'dicembre'];

        $crAndamentaleData = $request->all();
        $crAndamentaleData['period'] = $request->period;
        unset($crAndamentaleData['_token']);


        if (!isset($crAndamentaleData['period'])) {
            $msg = "Non è stato selezionato nessun periodo";
            return view('allerta.empty', compact(['msg']));
        } else {
            $lastDate = new DateTime(cr::select('date')->where('document_id', $crAndamentaleData['period'])->orderBy('date', 'desc')->first()->date);

		if(!isset($crAndamentaleData['newDates'])) {
            $earlierDate = (new DateTime(cr::select('date')->where('document_id', $crAndamentaleData['period'])->orderBy('date', 'desc')->first()->date))->modify('-23 months');
        } else {
           /* switch ($crAndamentaleData['period']) {
                case 1:
                    $earlierDate = (new DateTime(cr::select('date')->orderBy('date', 'desc')->first()->date))->modify('-11 months');
                    break;
                case 2:
                    $earlierDate = (new DateTime(cr::select('date')->orderBy('date', 'desc')->first()->date))->modify('-23 months');
                    break;
                case 3:
                    $earlierDate = (new DateTime(cr::select('date')->orderBy('date', 'desc')->first()->date))->modify('-35 months');
                    break;
                case 0:
                    $earlierDate = new DateTime(cr::select('date')->orderBy('date', 'asc')->first()->date);
            }*/
            $earlierDate = new DateTime(cr::select('date')->orderBy('date', 'desc')->get()->last()->date);
        }

         $latestDate = new DateTime(cr::select('date')->where('document_id', $crAndamentaleData['period'])->orderBy('date', 'desc')->first()->date);

            $banksScoring = array();
            $singleBankData = array();

            $numeroRapportiContestati = 0;

            $unrefinedPeriods = json_decode(DB::table('crs')
                ->select('anno', 'mese', 'date')
               // ->where("date", '>', $earlierDate->modify('first day of this month')->format('Y-m-d'))->where("date", '<', $lastDate->modify('last day of this month')->format('Y-m-d'))
				->where('document_id', $crAndamentaleData['period'])
                ->groupBy('date', 'anno', 'mese')
                ->orderBy('date')
                ->get(), true);

            $counter = 0;

            $missingMonths = array();

            foreach ($unrefinedPeriods as $label => $data) {
                $periods[$data['anno']][$data['mese']] = 1;
                $counter++;

                if ($counter < count($unrefinedPeriods)) {
                    $tempDate = new DateTime($data['date']);
                    if (cr::where([['date', '>=', $tempDate->modify('+1 month')->format('Y-m-01')], ['date', '<=', $tempDate->format('Y-m-0t')]])->where('document_id', $crAndamentaleData['period'])->groupBy('date')->count() == 0) {
                        $missingMonths[] = $mesiCheckList[(float)$tempDate->format('m')] . ' ' . $tempDate->format('Y');
                    }
                }
            }

            $latestYear = array_key_last($periods);
            $latestMonth = array_key_last($periods[$latestYear]);
            $earliestYear = array_key_first($periods);
            $earliestMonth = array_key_first($periods[$earliestYear]);
            $finePeriodo = $latestMonth . ' ' . $latestYear;
            $inizioPeriodo = $earliestMonth . ' ' . $earliestYear;

            $categories = array(
                'RISCHI A SCADENZA',
                'RISCHI AUTOLIQUIDANTI',
                'RISCHI A REVOCA',
                'SOFFERENZE'
            );

            $crHelper = new CrExtractorHelper;
            $crHelper->setPeriod($periods);
            $crHelper->setDocumentId($crAndamentaleData['period']);
            $periodsCorrect = $crHelper->buildPeriodArray();

            $banksQuery = DB::table('crs')->where('document_id', $crAndamentaleData['period']);

            foreach ($periodsCorrect as $queryPeriodArray) {
                $banksQuery->orWhere(function ($query) use ($queryPeriodArray, $categories) {
                    $query->where($queryPeriodArray);
                   // $query->whereIn('categoria', $categories);
                });
            }
            if ($request->input('banks') !== null) {
                $banks = $request->input('banks');
            } else {

                $banksData = $banksQuery
                    ->get()
                    ->groupBy('nome_banca')
                    ->toArray();

                foreach ($banksData as $singleBankName => $arrayData) {
                    $banks[] = $singleBankName;
                }
            }

            $cleanCR = $crHelper->getAllDataToArray($banks);
            $intermediari = $crHelper->getCountBanks($banks);
            $mediaAnalisiIndebitamento = $crHelper->getMediaIndebitamento($banks);

            $numeroSconfiniTotali = $crHelper->getTotaleSconfini($banks);

            $rischiGaranzie = $crHelper->getRischiGaranzie($banks);

            $sofferenzeTotali = $crHelper->getSofferenze($banks);

            $totaleAffidamentiTable = $crHelper->getTotaleAffidamenti($categories, $latestYear, $latestMonth, $banks);
            $totaleAffidamentiGeneral = $crHelper->getTotaleAffidamentiGeneral($categories, $latestYear, $latestMonth, $banks);

            $totAffidamentiConPesiPerBanca = $crHelper->getPesiAffidamentiPerBanca($categories, $latestYear, $latestMonth, $banks);
            $scoreCR = $crHelper->getScoring($banks);
            $creditiContestati = $crHelper->getCreditiContestati($banks);
            $impagati = $crHelper->getAlertImpagati($banks);
            $garanzieEsitoNegativo = $crHelper->getGaranzieEsitoNegativo($banks);
            $sofferenze = $crHelper->getSofferenze($banks);
            $creditiPassatiPerdita = $crHelper->getCreditiPassatiPerdita($banks);

            $anomalie = $crHelper->getAnomalie($banks);

            $incidenzaImpagati = $crHelper->getPercentualeMediaImpagati($banks);
            $informazioniGaranti = $crHelper->getInformazioniGaranti($banks);
            $garanzieRicevute = $crHelper->getGaranzieRicevute($banks);

            $importiSconfini = $crHelper->getImportiSconfini($banks);

            foreach ($creditiContestati as $singleLineArray) {
                $numeroRapportiContestati += count($singleLineArray);
            }

            $affidamentiPerMese = $crHelper->getTotaleAffidamentiPerMese($periods, $categories, $banks);




            // dd($periods);

            // Per singola banca
            foreach ($banks as $label => $nameData) {
                $newCrExtractor = new newCrExtractor;
                $newCrExtractor->setPeriod($periods);
                $singleBankData[$nameData]['totaleSconfini'] = $newCrExtractor->getTotaleSconfini(array(0 => $nameData));
                $singleBankData[$nameData]['Impagati'] = $newCrExtractor->getImpagati(array(0 => $nameData));
                $newCrExtractor->getAlertImpagati(array(0 => $nameData));
                $singleBankData[$nameData]['Sofferenze'] = $newCrExtractor->getSofferenze(array(0 => $nameData));
                $singleBankData[$nameData]['Crediti a perdita'] = $newCrExtractor->getCreditiPassatiPerdita(array(0 => $nameData));
                $banksScoring[$nameData] = $newCrExtractor->getScoring(array(0 => $nameData));
                if (!empty($singleBankData['totaleSconfini']['Tensioni'])) {
                    foreach ($singleBankData[$nameData]['totaleSconfini']['Tensioni'] as $creditLine => $presence) {
                        $presenzaTensione[$creditLine] = true;
                    }
                }
                unset($newCrExtractor);
            }


            $monthsList = array_keys($affidamentiPerMese);



            foreach ($totaleAffidamentiTable as $indice => $oggetto) {
                if ($oggetto["categoria"] == "RISCHI A SCADENZA") {
                    $totaleAffidamentiTable[$indice]["style"] = "background-color: rgb(236, 91, 91)";
                } else if ($oggetto["categoria"] == "RISCHI A REVOCA") {
                    $totaleAffidamentiTable[$indice]["style"] = "background-color: rgb(125, 236, 91)";
                } else {
                    $totaleAffidamentiTable[$indice]["style"] = "background-color: rgb(91, 171, 236)";
                }
            }

            $percentualiAccordato = array();
            $percentualiUtilizzato = array();

            foreach ($totAffidamentiConPesiPerBanca as $label => $data) {
                if (isset($data['PesoAccordatoOperativo'])) {
                    $percentualiAccordato[] = array("label" => $data['nome_banca'], "y" => $data['PesoAccordatoOperativo']);
                } else {
                    $percentualiAccordato[] = array("label" => $data['nome_banca'], "y" => 0);
                }
                if (isset($data['PesoUtilizzato'])) {
                    $percentualiUtilizzato[] = array("label" => $data['nome_banca'], "y" => $data['PesoUtilizzato']);
                } else {
                    $percentualiUtilizzato[] = array("label" => $data['nome_banca'], "y" => 0);
                }
            }

            // dd($informazioniGaranti);

            $earlierDate = $earlierDate->format('Y-m-d');

            $accordatoPie = array(array('Banca', 'Accordato'));
            $utilizzatoPie = array(array('Banca', 'Utilizzato'));

            foreach ($totAffidamentiConPesiPerBanca as $labelAffidamenti => $affidamentiData) {
                $accordatoPie[] = array($affidamentiData['nome_banca'], $affidamentiData['totAccordatoOperativo']);
                $utilizzatoPie[] = array($affidamentiData['nome_banca'], $affidamentiData['totUtilizzato']);
            }

            // dd($numeroSconfiniTotali);

            // dd($utilizzatoPie, $accordatoPie);

            // dd($anomalie, $informazioniGaranti);

            // dd($informazioniGaranti);

            $anomalieStatoRapporto = $crHelper->mancateSegnalazioniStatoRapporto($banks);

            $testSconfini = $crHelper->testSconfini($banks);
            // dd($testSconfini, $numeroSconfiniTotali);
            $sconfiniDivisi = $crHelper->divideAnomalie($numeroSconfiniTotali, $banks);

			$informazioniGarantiAnomalie = [];

					foreach($informazioniGaranti['Anomalie'] as $nomeBanca=>$multipleDates) {
						foreach($multipleDates as $singleDate=>$multipleTypes) {
							foreach($multipleTypes as $singleType=>$multipleAnomalie) {
								$multipleAnomalie = array_unique($multipleAnomalie);
								foreach($multipleAnomalie as $singleAnomalia=>$tmp) {
									$informazioniGarantiAnomalie[] = [
										'data' => $singleDate,
										'nome_banca' => $nomeBanca,
										'type' => $singleType,
										'tmp' => $tmp
									];
								}
							}
						}
					}

	foreach($totAffidamentiConPesiPerBanca as $singleBank) {
				$totaleAccordatoGeneral = $totAffidamentiConPesiPerBanca[0]['totAccordatoOperativo'] + $singleBank['totAccordatoOperativo'];
				$totaleUtilizzatoGeneral = $totAffidamentiConPesiPerBanca[0]['totUtilizzato'] + $singleBank['totUtilizzato'];
			}

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
                    'ListaScoringBanche' => $banksScoring
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
                'utilizzatoPie' => $utilizzatoPie,
                'accordatoPie' => $accordatoPie,
                'earlierDate' => $earlierDate,
                'garanzieRicevute' => $garanzieRicevute,
                'informazioniGaranti' => $informazioniGaranti,
                'monthsList' => $monthsList,
                'affidamentiPerMese' => $affidamentiPerMese,
                'banksScoring' => $banksScoring,
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
				'totaleAccordatoOperativoPerBancaGeneral' => $totaleAccordatoGeneral,
				'totaleUtilizzatoPerBancaGeneral' => $totaleUtilizzatoGeneral,
				'informazioniGarantiAnomalie' => $informazioniGarantiAnomalie,
                'scoreCR' => $scoreCR,
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

        $request->session()->put($data);

        // dd($affidamenti);
        return response()->json([
            'error' => false,
            'data' => $data
        ]);

        return view('centralerischi.trimestrale', compact(['styles', 'informazioniSuiGaranti', 'creditiScadutiBreveTermine', 'creditiRischi', 'analisiSconfini', 'affidamenti', 'verificaGaranzie', 'segnalazioniGravi', 'pesoDebitiBreveTermine', 'disponibilitaInutilizzata', 'presenzaCreditiScaduti', 'presenzaSconfini', 'affidamentiPerCategoria', 'lastTwelveMonths', 'trimestri']));
    }
}
