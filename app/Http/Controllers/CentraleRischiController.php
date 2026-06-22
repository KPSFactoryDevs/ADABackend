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
use Carbon\Carbon;
use DateTime;
use Storage;
use Exception;
use App\Helpers\Bilanci\BilanciHelper;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class CentraleRischiController extends Controller
{


    public function getDocuments(Request $request)
    {
        $bilanciHelper = new BilanciHelper;
        $currentUserId = $bilanciHelper->getCurrentUserIdFromToken($request);

        if ($currentUserId == 'Unauthorized')
            return response()->json([
                'exception' => true,
                'message' => 'Unauthorized'
            ], 401);

        if ($request->header('currentcompany') || $request->header('currentcompany') === 0) {
            $documentsCr = Document::where('type', 'centrale rischi')->where('company_id', $request->header('currentcompany'))->where('user_id', $currentUserId)->orderBy('created_at', 'desc')->get();
        } else {
            $documentsCr = Document::where('type', 'centrale rischi')->where('user_id', $currentUserId)->orderBy('created_at', 'desc')->get();
        }

        foreach ($documentsCr as $singleDocument) {
            $textPeriodAvailable = "";
            $periodAvailable = cr::select(['mese', 'anno'])
                ->Where('document_id', $singleDocument->codice_documento)
                ->groupBy('anno', 'mese')
                ->get();
            foreach ($periodAvailable as $singlePeriod) {
                $textPeriodAvailable .= substr(ucFirst($singlePeriod->mese), 0, 3) . " " . $singlePeriod->anno . ', ';
            }
            $singleDocument['status'] = ucfirst(str_replace('_', ' ', $singleDocument['status']));
            $singleDocument['type'] = ucfirst($singleDocument['type']);
            $singleDocument['availableMonths'] = $textPeriodAvailable;
            $singleDocument['predefinito'] = (bool) $singleDocument['predefinito'];
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
        $crFileToElaborate = Document::where('codice_documento', $documentId)->first();
        if (!$crFileToElaborate) {
            return response()->json(['error' => true, 'message' => 'Document not found'], 404);
        }

        // 1. Convert page to image
        $pageIndex = ((int) $page) - 1;
        if ($pageIndex < 0) {
            $pageIndex = 0;
        }

        $filepathWithPage = $crFileToElaborate->path . '[' . $pageIndex . ']';
        $escapedInput = escapeshellarg($filepathWithPage);

        $tmpImagePattern = '/tmp/cr_page_' . Str::uuid() . '.jpg';
        $cmd = "convert -density 150 {$escapedInput} -quality 90 " . escapeshellarg($tmpImagePattern) . " 2>&1";
        $out = shell_exec($cmd);

        $files = glob(str_replace('.jpg', '*.jpg', $tmpImagePattern));
        if (empty($files) || !file_exists($files[0])) {
            // Se fallisce il glob cerco ignorando il -0
            $fallbackImagePattern = str_replace('.jpg', '-0.jpg', $tmpImagePattern);
            if (file_exists($fallbackImagePattern)) {
                $files = [$fallbackImagePattern];
            } else {
                Log::error("Image conversion failed", ['cmd' => $cmd, 'out' => $out]);
                $crFileToElaborate->status = "Errore conversione image page {$page}";
                $crFileToElaborate->save();
                return response()->json(['error' => true, 'message' => 'Image conversion failed']);
            }
        }

        $imagePath = $files[0];
        $base64Image = base64_encode(file_get_contents($imagePath));
        @unlink($imagePath);

        // 2. Prepare JSON Schema per OpenAI
        $rowSchema = [
            "type" => "array",
            "items" => [
                "type" => "object",
                "properties" => [
                    "Categoria" => ["type" => "string", "description" => "Usa '' se vuoto. Es: RISCHI A SCADENZA"],
                    "Accordato" => ["type" => "string", "description" => "Usa '' se vuoto o assente. Scrivi '0' se c'è uno zero. Attento a non saltare il dato."],
                    "Accordato Operativo" => ["type" => "string", "description" => "Usa '' se vuoto o assente. Scrivi '0' se c'è uno zero."],
                    "Utilizzato" => ["type" => "string", "description" => "Usa '' se vuoto o assente. Scrivi '0' se c'è uno zero. Attento a non saltare il dato."],
                    "Durata Residua" => ["type" => "string", "description" => "Usa '' se vuoto"],
                    "Durata Originaria" => ["type" => "string", "description" => "Usa '' se vuoto"],
                    "Localizzazione" => ["type" => "string", "description" => "Usa '' se vuoto"],
                    "Divisa" => ["type" => "string", "description" => "Usa '' se vuoto"],
                    "Tipo Garanzia" => ["type" => "string", "description" => "Usa '' se vuoto"],
                    "Stato Rapporto" => ["type" => "string", "description" => "Usa '' se vuoto"],
                    "Tipo Attività" => ["type" => "string", "description" => "Usa '' se vuoto"],
                    "Ruolo Affidato" => ["type" => "string", "description" => "Usa '' se vuoto"],
                    "Import Export" => ["type" => "string", "description" => "Usa '' se vuoto"],
                    "Saldo Medio" => ["type" => "string", "description" => "Usa '' se vuoto"],
                    "Importo Garantito" => ["type" => "string", "description" => "Usa '' se vuoto o assente. Scrivi '0' se c'è uno zero. Da estrarre se presente in 'Informazioni sui garanti'."],
                    "Cointestazione" => ["type" => "string", "description" => "Usa '' se vuoto"],
                    "Garantito" => ["type" => "string", "description" => "Usa '' se vuoto"],
                    "Valore Garanzia" => ["type" => "string", "description" => "Usa '' se vuoto. Da estrarre scrupolosamente se presente in 'Informazioni sui garanti'."],
                    "Importo" => ["type" => "string", "description" => "Usa '' se vuoto"],
                    "Garante" => ["type" => "string", "description" => "Usa '' se vuoto. Se presente la sezione 'Informazioni sui garanti' estrai l'esatta stringa, es. 'PEREGO VINCENZO' o 'Cointestazione formata da...'."]
                ],
                "required" => ["Categoria", "Accordato", "Accordato Operativo", "Utilizzato", "Durata Residua", "Durata Originaria", "Localizzazione", "Divisa", "Tipo Garanzia", "Stato Rapporto", "Tipo Attività", "Ruolo Affidato", "Import Export", "Saldo Medio", "Importo Garantito", "Cointestazione", "Garantito", "Valore Garanzia", "Importo", "Garante"],
                "additionalProperties" => false
            ]
        ];

        $schema = [
            "name" => "estrazione_centrale_rischi",
            "strict" => true,
            "schema" => [
                "type" => "object",
                "properties" => [
                    "inizio_legenda" => [
                        "type" => "boolean",
                        "description" => "true se in questa pagina inizia o è presente la sezione o intestino 'LEGENDA' della Centrale Rischi, false altrimenti. Se la pagina contiene prevalentemente la legenda scartarla attivando questo flag."
                    ],
                    "dati_anagrafici_presenti" => [
                        "type" => "boolean",
                        "description" => "true se in questa pagina sono presenti i DATI ANAGRAFICI DELL'INTESTATARIO"
                    ],
                    "ragione_sociale_intestatario" => [
                        "type" => "string",
                        "description" => "Ragione Sociale o Nome esatto dell'intestatario (se presenti, altrimenti stringa vuota '')"
                    ],
                    "codice_fiscale_intestatario" => [
                        "type" => "string",
                        "description" => "Codice Fiscale o Partita IVA dell'intestatario (se presenti, altrimenti stringa vuota '')"
                    ],
                    "dati" => [
                        "type" => "array",
                        "description" => "Banche evinte dalla tabella nella pagina. Attenzione a non unire banche diverse.",
                        "items" => [
                            "type" => "object",
                            "properties" => [
                                "anno" => ["type" => "string", "description" => "Es. '2024'"],
                                "mese" => ["type" => "string", "description" => "Es. 'settembre'"],
                                "intermediario" => ["type" => "string", "description" => "Nome Intermediario/Banca, es. 'SOCIETE GENERALE'"],
                                "sezioni" => [
                                    "type" => "object",
                                    "properties" => [
                                        "Cassa" => $rowSchema,
                                        "Firma" => $rowSchema,
                                        "Garanzie" => $rowSchema,
                                        "Sofferenze" => $rowSchema,
                                        "Informativa" => $rowSchema,
                                        "Garanti" => $rowSchema,
                                    ],
                                    "required" => ["Cassa", "Firma", "Garanzie", "Sofferenze", "Informativa", "Garanti"],
                                    "additionalProperties" => false
                                ]
                            ],
                            "required" => ["anno", "mese", "intermediario", "sezioni"],
                            "additionalProperties" => false
                        ]
                    ]
                ],
                "required" => ["inizio_legenda", "dati_anagrafici_presenti", "ragione_sociale_intestatario", "codice_fiscale_intestatario", "dati"],
                "additionalProperties" => false
            ]
        ];

        // 3. Eseguo chiamata OpenAI con retry per rate limit
        $maxRetries = 5;
        $retryDelay = 6; // secondi iniziali

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $response = Http::withToken(config('services.openai.key'))
                ->withOptions(['timeout' => 120])
                ->post(config('services.openai.base_url') . '/chat/completions', [
                    'model' => config('services.openai.model'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Sei un precisissimo estrattore di Dati. Leggi la tabella della Centrale Rischi d\'Italia e restituisci rigorosamente tutti i valori richiesti. Lascia i valori vuoti/assenti con "". Se un valore numerico è 0, scrivi "0". Fai massima attenzione a non omettere MAI i valori delle colonne "Accordato" e "Utilizzato" se presenti! Identifica attentamente anche le "Informazioni sui garanti", popolando i campi Garante, Valore Garanzia e Importo Garantito.'
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Estrai minuziosamente i dati. Fai attenzione ai DATI ANAGRAFICI DELL\'INTESTATARIO. "RISCHI A SCADENZA", "RISCHI A REVOCA", "AUTOLIQUIDANTI" vanno in "Cassa". Le righe in "Informazioni sui garanti" vanno in array "Garanti" con "Garante" (nome per intero o "Cointestazione..."), "Valore Garanzia" e "Importo Garantito". NON saltare valori di "Accordato" e "Utilizzato" per pigrizia! Ignora la zona LEGENDA attivando il flag apposito.'
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => ['url' => 'data:image/jpeg;base64,' . $base64Image]
                                ]
                            ]
                        ]
                    ],
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => $schema
                    ]
                ]);

            if ($response->successful()) {
                break; // Usciamo dal loop se la chiamata ha successo
            }

            if ($response->status() == 429 && $attempt < $maxRetries) {
                // Rate limit raggiunto, aspettiamo un po' prima di riprovare
                sleep($retryDelay);
                $retryDelay *= 2; // exponential backoff
                continue;
            }

            // Se arriviamo qui sono finiti i tentativi o è un errore fatale
            Log::error("OpenAI Extractor Failed on attempt $attempt", ['response' => $response->body()]);
            $crFileToElaborate->status = "Errore API OpenAI";
            $crFileToElaborate->save();
            return response()->json(['error' => true, 'message' => 'API Error'], 500);
        }

        $content = $response->json('choices.0.message.content');
        $result = json_decode($content, true);

        // Even if inizio_legenda is true, if ChatGPT extracted valid dati we keep them!
        $dati = $result['dati'] ?? [];
        if (($result['inizio_legenda'] ?? false) === true && empty($dati)) {
            $dati = [];
        }

        if (($result['dati_anagrafici_presenti'] ?? false) === true) {
            $otherData = json_decode($crFileToElaborate->other_data_json, true) ?: [];
            $otherData['anagrafica_cr'] = [
                'ragione_sociale' => $result['ragione_sociale_intestatario'] ?? '',
                'codice_fiscale' => $result['codice_fiscale_intestatario'] ?? ''
            ];
            $crFileToElaborate->other_data_json = json_encode($otherData);
            $crFileToElaborate->save();
        }

        $dataToSave = [];

        foreach ($dati as $bancaObj) {
            $anno = $bancaObj['anno'] ?? '';
            $mese = strtolower($bancaObj['mese'] ?? '');
            $banca = $bancaObj['intermediario'] ?? '';
            if (!$anno || !$mese || !$banca)
                continue;

            if (!isset($dataToSave[$anno]))
                $dataToSave[$anno] = [];
            if (!isset($dataToSave[$anno][$mese]))
                $dataToSave[$anno][$mese] = [];
            if (!isset($dataToSave[$anno][$mese][$banca]))
                $dataToSave[$anno][$mese][$banca] = [];

            if (isset($bancaObj['sezioni']) && is_array($bancaObj['sezioni'])) {
                foreach ($bancaObj['sezioni'] as $sezione => $rows) {
                    if (is_array($rows) && count($rows) > 0) {
                        $dataToSave[$anno][$mese][$banca][$sezione] = $rows;
                    }
                }
            }
        }

        // 4. Salvataggio tramite Helpers
        $codiceDocumento = $crFileToElaborate->codice_documento;
        $companyId = $crFileToElaborate->company_id;
        if (is_null($companyId)) {
            $company = \App\Models\Company::where('user_id', $crFileToElaborate->user_id)->first();
            if ($company) {
                $companyId = $company->id;
                $crFileToElaborate->company_id = $companyId;
                $crFileToElaborate->save();
            } else {
                $companyId = 0;
            }
        }
        $CentraleRischiStoreDataHelper = new App\Helpers\CentraleRischi\CentraleRischiStoreDataHelper();

        foreach ($dataToSave as $anno => $months) {
            foreach ($months as $mese => $data) {
                foreach ($data as $singleBank => $keys) {
                    foreach ($keys as $index => $value) {
                        $mesiList = ["0" => "fuoriMese", "gennaio" => 1, 'febbraio' => 2, 'marzo' => 3, 'aprile' => 4, 'maggio' => 5, 'giugno' => 6, 'luglio' => 07, "agosto" => 8, 'settembre' => 9, 'ottobre' => 10, 'novembre' => 11, 'dicembre' => 12];
                        $transformedMonth = $mesiList[$mese] ?? 1;

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

        $crFileToElaborate->status = $liveStatus;
        $crFileToElaborate->save();

        return response()->json([
            'error' => false,
            'data' => 'File Centrale Rischi ' . $crFileToElaborate->status
        ]);
    }





    public function store(Request $request)
    {
        $request->validate([
            'base64' => 'required|file|mimes:pdf|max:51200',
        ]);

        $bilanciHelper = new BilanciHelper;

        $userId = $bilanciHelper->getCurrentUserIdFromToken($request);
        if ($userId === 'Unauthorized') {
            return response()->json(['exception' => true, 'message' => 'Unauthorized'], 401);
        }

        $file = $request->file('base64');
        if (!$file || !$file->isValid()) {
            return response()->json(['error' => true, 'message' => 'Upload non valido'], 422);
        }

        $safeOriginalName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $file->getClientOriginalName());
        $finalName = time() . '_' . Str::uuid() . '_' . $safeOriginalName;

        $storedFile = $file->storeAs('centraleRischi', $finalName, 'public');

        $localPathFs = Storage::disk('public')->path($storedFile);
        $publicUrl = asset('storage/' . $storedFile);

        $newDocumentData = [
            'filename' => $finalName,
            'path' => $localPathFs,
            'type' => 'centrale rischi',
            'codice_documento' => random_int(1, 999999999),
            'status' => 'Da Elaborare',
            'company_id' => $request->header('currentcompany'),
            'user_id' => $userId
        ];

        try {
            $documentCreated = Document::create($newDocumentData);
        } catch (\Exception $e) {
            return response()->json(['exception' => true, 'message' => $e->getMessage()], 500);
        }

        $cmd = 'qpdf --show-npages ' . escapeshellarg($localPathFs) . ' 2>&1';
        $out = trim((string) shell_exec($cmd));

        if ($out === '' || !ctype_digit($out)) {
            return response()->json([
                'error' => true,
                'message' => 'qpdf error / output non valido',
                'qpdf_output' => $out,
                'file' => $storedFile,
            ], 500);
        }

        $totalPages = (int) $out;

        for ($pageToExtract = 1; $pageToExtract <= $totalPages; $pageToExtract++) {
            $liveStatus = round((($pageToExtract / $totalPages) * 100), 1) . "% processato";
            if ($pageToExtract === $totalPages)
                $liveStatus = "Completato";

            ElaborateLatestCR::dispatch($pageToExtract, $documentCreated["codice_documento"], $liveStatus);
        }

        return response()->json([
            'newDocumentCreated' => $documentCreated,
            'storedFile' => $storedFile,
            'fileUrl' => $publicUrl,
            'pages' => $totalPages,
        ], 200);
    }





    public function crAndamentale($period, $data_inizio = false, $data_fine = false, $inputBanks = null)
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

            if (cr::where('document_id', $crAndamentaleData['period'])->count() == 0) {
                return response()->json([
                    'error' => true,
                    'message' => 'Nessun dato Centrale Rischi disponibile per questo periodo/documento.'
                ], 404);
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
                //   ->where("date", '>', $earlierDate)->where("date", '<', $lastDate)
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
            $totaleAffidamentiGeneral = $crHelper->getTotaleAffidamentiGeneral($categories, $latestYear, $latestMonth, $banks);
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
            $affidamentiPerMese = $crHelper->getTotaleAffidamentiPerMese($periods, $categories, $banks);
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

            $doc = Document::where('codice_documento', $crAndamentaleData['period'])->first();
            $anagraficaRaw = null;
            if ($doc && $doc->other_data_json) {
                $od = json_decode($doc->other_data_json, true) ?: [];
                $anagraficaRaw = $od['anagrafica_cr'] ?? null;
            }

            $righeGrezze = cr::where('document_id', $crAndamentaleData['period'])->orderBy('id', 'asc')->get()->toArray();

            $response = [
                'Scoring' => [
                    'Panoramica' => [
                        'PeriodoRiferimento' => [
                            'Inizio' => ucFirst($inizioPeriodo),
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
                    'ListaAffidamentiConPesiPerBanca' => $totaleAffidamentiTable,
                    'ListaAffidamentiGeneral' => $totaleAffidamentiGeneral
                ],
                'AnalisiIndebitamento' => $affidamentiPerMese,
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
                'generalDates' => $generalDates,
                'RigheGrezze' => $righeGrezze,
                'Anagrafica' => $anagraficaRaw
            ];
            return response()->json([
                'error' => false,
                /* 'anomalieStatoRapporto' => $anomalieStatoRapporto,
                'anomalie' => $anomalie,
                'missingMonths' => $missingMonths,
                'sconfiniDivisi' => $sconfiniDivisi,
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
                'totaleAccordatoUtilizzatoPerBancaGeneral' => $totaleUtilizzatoGeneral,
                'informazioniGarantiAnomalie' => $informazioniGarantiAnomalie,
                'scoreCR' => $scoreCR,*/

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
        // $trimestriData = array(1 => array(), 2 => array(), 3 => array(), 4 => array());
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

        return response()->json([
            'error' => false,
            'data' => $data
        ]);
    }

    public function destroy($idDocument)
    {
        if (!$idDocument) {
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
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'type' => 'Eccezione',
                'message' => $e,
            ]);
        }
    }

    public function setPredefinito(Request $request, $id)
    {
        try {
            $bilanciHelper = new BilanciHelper;
            $currentUserId = $bilanciHelper->getCurrentUserIdFromToken($request);

            if ($currentUserId == 'Unauthorized') {
                return response()->json([
                    'exception' => true,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $currentCompany = $request->header('currentcompany') ?: 0;

            $document = Document::where('id', $id)
                ->where('type', 'centrale rischi')
                ->where('user_id', $currentUserId)
                ->firstOrFail();

            if ($currentCompany) {
                Document::where('type', 'centrale rischi')
                    ->where('company_id', $currentCompany)
                    ->where('user_id', $currentUserId)
                    ->update(['predefinito' => false]);
            } else {
                Document::where('type', 'centrale rischi')
                    ->where('user_id', $currentUserId)
                    ->update(['predefinito' => false]);
            }

            $document->predefinito = true;
            $document->save();

            return response()->json([
                'error' => false,
                'message' => 'Centrale Rischi impostata come predefinita.'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
