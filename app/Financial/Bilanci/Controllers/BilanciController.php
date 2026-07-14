<?php

namespace App\Financial\Bilanci\Controllers;
use Vtiful\Kernel\Excel;

ini_set('max_input_vars', 5000);

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests;
use App;
use lyquidity\XPath2\XPath2Exception;
use XBRL_Instance;
use App\Models\Bilanci;
use App\Models\Account;
use App\Models\cr;
use function Livewire\str;
use App\Helpers\Bilanci\BilanciHelper;
use App\Helpers\Bilanci\BilanciCalculationsHelperAdvanced;
use App\Models\Voci;
use Storage;
use App\Models\Document;
use App\Models\CustomLog;
use App\Domains\Auth\Models\User;
use Auth;
use Exception;
use App\Models\MissingVoice;
use Laravel\Passport\Token;
use App\Models\Company;
use App\Services\RagService;

class BilanciController extends Controller
{


    public function index(Request $request)
    {
        $bilancis = Bilanci::with('account');

        if ($request->header('currentcompany') || $request->header('currentcompany') === 0) {
            $bilancis = $bilancis->where('company_id', $request->header('currentcompany'));
        }

        $bilancis = $bilancis->paginate(25);

        foreach ($bilancis as $singleBilancio) {
            $year = explode(' ', $singleBilancio->year);
            $year = $year[0];
            $singleBilancio->company_name = json_decode($singleBilancio->json_data_anag)->DatiAnagraficiDenominazione;
            $singleBilancio->annoFormatted = date('Y', strtotime($year));

            if (isset($singleBilancio->year)) {
                $singleBilancio->annoFormatted = $singleBilancio->year;
            }
        }

        return response()->json([
            'error' => false,
            'data' => $bilancis
        ], 200);
    }



    /**
     * Import bilancio from PDF: convert to XBRL via Python script, then process normally.
     */
    public function importFromPdf(Request $request)
    {
        $bilanciHelper = new BilanciHelper();

        // Validate
        if (!$request->hasFile('file') && !$request->hasFile('base64')) {
            return response()->json([
                'exception' => true,
                'message' => 'Nessun file PDF caricato'
            ], 422);
        }

        $file = $request->file('file') ?? $request->file('base64');

        // Verify it's a PDF
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();
        if ($extension !== 'pdf' && $mimeType !== 'application/pdf') {
            return response()->json([
                'exception' => true,
                'message' => 'Il file deve essere in formato PDF'
            ], 422);
        }

        $currentUserId = $bilanciHelper->getCurrentUserIdFromToken($request);
        if ($currentUserId == 'Unauthorized') {
            return response()->json([
                'exception' => true,
                'message' => 'Unauthorized'
            ], 401);
        }

        try {
            // Save the PDF temporarily
            $pdfPath = $file->getPathName();
            $outputDir = base_path() . '/public/bilanci';

            // Ensure output directory exists
            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // Get OpenAI config from .env
            $openaiKey = env('OPENAI_API_KEY', '');
            $openaiModel = env('OPENAI_MODEL', 'gpt-4o-mini');

            if (empty($openaiKey)) {
                return response()->json([
                    'exception' => true,
                    'message' => 'OPENAI_API_KEY non configurata nel server'
                ], 500);
            }

            // Invoke the Python script
            $scriptPath = base_path() . '/scripts/pdf_to_xbrl.py';
            $command = sprintf(
                'OPENAI_API_KEY=%s OPENAI_MODEL=%s python3 %s %s %s 2>&1',
                escapeshellarg($openaiKey),
                escapeshellarg($openaiModel),
                escapeshellarg($scriptPath),
                escapeshellarg($pdfPath),
                escapeshellarg($outputDir)
            );

            $output = shell_exec($command);

            // Parse the JSON output from the script
            $result = json_decode($output, true);

            if (!$result || !isset($result['success']) || !$result['success']) {
                $errorMsg = $result['error'] ?? 'Conversione PDF fallita: ' . substr($output ?? '', 0, 500);
                return response()->json([
                    'exception' => true,
                    'message' => $errorMsg
                ], 500);
            }

            // Create the Document record
            $taxonomyName = $result['taxonomy'] ?? 'itcc-ci-abb-2018-11-04.xsd';
            $xbrlFilename = $result['xbrl_filename'];

            $document = Document::create([
                'filename' => $xbrlFilename,
                'path' => asset('bilanci') . '/' . $xbrlFilename,
                'type' => 'bilancio',
                'taxonomy' => $taxonomyName,
                'codice_documento' => rand(1, 999999999),
                'company_id' => $request->header('currentcompany'),
                'forma_giuridica' => $request->forma_giuridica,
                'tipo_azienda' => $request->tipo_azienda,
                'user_id' => $currentUserId,
                'nome_azienda' => $result['company_name'] ?? null,
                'source' => 'pdf_import',
            ]);

            // Now delegate to the existing recap flow using the documentId
            $recapRequest = new Request();
            $recapRequest->merge([
                'documentId' => $document->id,
                'userID' => $request->userID,
                'forma_giuridica' => $request->forma_giuridica,
                'tipo_azienda' => $request->tipo_azienda,
            ]);
            $recapRequest->headers->set('currentcompany', $request->header('currentcompany'));
            $recapRequest->headers->set('Authorization', $request->header('Authorization'));

            return $this->recap($recapRequest);

        } catch (Exception $e) {
            return response()->json([
                'exception' => true,
                'message' => 'Errore durante la conversione PDF: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * @return mixed
     */
    public function recap(Request $request)
    {
        global $use_xbrl_functions;
        $use_xbrl_functions = true;

        $bilanciHelper = new BilanciHelper();
        if ($request->documentId) {


            $document = Document::findOrFail($request->documentId);

            $filePath = base_path() . '/public/bilanci/' . $document->filename;
            $taxonomyName = $document->taxonomy;
        } else {
            $file = $request->base64;
            $filePath = $file->getPathName();
            $taxonomyName = $bilanciHelper->getInstanceTaxonomyHRef($filePath);
            $fileName = "Bilancio_" . time() . '.xbrl';
            $storeFile = Storage::disk('bilanci')->putFileAs('', $file, $fileName);

            $currentUserId = $bilanciHelper->getCurrentUserIdFromToken($request);

            if ($currentUserId == 'Unauthorized')
                return response()->json([
                    'exception' => true,
                    'message' => 'Unauthorized'
                ], 401);

            $document = Document::create([
                'filename' => $fileName,
                'path' => asset('bilanci') . '/' . $fileName,
                'type' => 'bilancio',
                'taxonomy' => $taxonomyName,
                'codice_documento' => rand(1, 999999999),
                'company_id' => $request->header('currentcompany'),
                'forma_giuridica' => $request->forma_giuridica,
                'tipo_azienda' => $request->tipo_azienda,
                'user_id' => $currentUserId
            ]);
        }

        $taxonomyPath = base_path() . "/taxonomies/2018-11-04/" . $taxonomyName;


        try {

            // Clean up XBRL instance document before parsing to prevent xml errors
            if (file_exists($filePath)) {
                $data = file_get_contents($filePath);
                $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
                $data = str_replace(['&euro;', '&nbsp;', '&agrave;', '&egrave;', '&igrave;', '&ograve;', '&ugrave;', '&Agrave;', '&Egrave;', '&Igrave;', '&Ograve;', '&Ugrave;', '&deg;', '&apos;'], ['€', ' ', 'à', 'è', 'ì', 'ò', 'ù', 'À', 'È', 'Ì', 'Ò', 'Ù', '°', '\''], $data);
                file_put_contents($filePath, $data);
            }

            $emptyInstance = false;
            $readXBRL = XBRL_Instance::FromInstanceDocument($filePath, $taxonomyPath, $emptyInstance);

            if ($readXBRL) {
                $userID = $request->userID;
                $bilancioJSON = $readXBRL->toJSON();


                $renderHTML = $bilanciHelper->generateHTMLRender($filePath, $taxonomyPath);
                $period = $bilanciHelper->getPeriodFromContext(json_decode($bilancioJSON)->contexts);
                $nomeAzienda = $bilanciHelper->getNomeAziendaFromElements(json_decode($bilancioJSON)->elements->DatiAnagraficiDenominazione);

                if ($nomeAzienda && $period) {
                    $document = Document::find($document->id);
                    $document->update([
                        'nome_azienda' => $nomeAzienda,
                        'anno_inizio' => $period['anno_inizio'],
                        'anno_fine' => $period['anno_fine']
                    ]);
                }

                $bilancioAnalisi = $bilanciHelper->getIndexesForBalanceTaxonomy($document->id, $filePath, $readXBRL, $document->codice_documento, $userID);

                // Calcola lo score complessivo (Basic 45% + Advanced 25% + Questionari 20% + Completezza 10%)
                $valutazioneAdv = $bilanciHelper->valutazioneComplessivaBilancio($bilancioAnalisi, 'Commercio', date('Y'));
                $rawScore = (float) str_replace(',', '.', $valutazioneAdv['Score']);
                $bilancioAnalisi['AdvancedScore'] = number_format($rawScore * 100, 2, ',', '.');
                $bilancioAnalisi['AdvancedGiudizio'] = $valutazioneAdv['Giudizio'];
                $bilancioAnalisi['AdvancedGiudizi'] = $valutazioneAdv['Giudizi'];
                $bilancioAnalisi['AdvancedDettaglio'] = $valutazioneAdv['Dettaglio'] ?? null;

                // --- RAG: indicizza il bilancio strutturato nel vector store ---
                try {
                    $companyId = $document->company_id ?? $request->header('currentcompany');
                    if ($companyId) {
                        $rag = new RagService();
                        $cleanedVoci = $this->cleanBilancioData($bilancioJSON);

                        // Estrai nota integrativa dal render HTML (se disponibile)
                        $notaIntegrativa = '';
                        if ($renderHTML) {
                            $notaText = strip_tags($renderHTML);
                            // Prendi solo i primi 10000 chars della nota
                            $notaIntegrativa = mb_substr($notaText, 0, 10000);
                        }

                        $rag->ingestBilancio(
                            'bilancio_' . $document->id,
                            $companyId,
                            $nomeAzienda ?? '',
                            $period['anno_fine'] ?? '',
                            $cleanedVoci,
                            $bilancioAnalisi,
                            $notaIntegrativa
                        );
                    }
                } catch (\Throwable $e) {
                    Log::warning('RAG ingest bilancio failed', ['doc' => $document->id, 'err' => $e->getMessage()]);
                }
                // --- fine RAG ---

                return response()->json([
                    'exception' => false,
                    'nome_azienda' => $nomeAzienda,
                    'period' => $period,
                    'codice_documento' => $document->codice_documento,
                    'idDocumento' => $document->id,
                    'renderHTML' => $renderHTML,
                    'bilancioJSON' => $bilancioJSON,
                    'bilancioAnalisi' => $bilancioAnalisi,
                ], 200);
            } else {
                return response()->json([
                    'exception' => true,
                    'message' => 'Il file è danneggiato'
                ], 202);
            }


        } catch (Exception $e) {

            return response()->json([
                'exception' => true,
                'message' => $e->getMessage()
            ], 500);
        }

    }




    /**
     * @return mixed
     */
    public function recapForAi(Request $request, $documentId)
    {
        global $use_xbrl_functions;
        $use_xbrl_functions = true;

        $bilanciHelper = new BilanciHelper();
        if ($documentId) {

            $document = Document::findOrFail($documentId);

            $filePath = base_path() . '/public/bilanci/' . $document->filename;
            $taxonomyName = $document->taxonomy;
        } else {
            $file = $request->base64;
            $filePath = $file->getPathName();
            $taxonomyName = $bilanciHelper->getInstanceTaxonomyHRef($filePath);
            $fileName = "Bilancio_" . time() . '.xbrl';
            $storeFile = Storage::disk('bilanci')->putFileAs('', $file, $fileName);

            $currentUserId = $bilanciHelper->getCurrentUserIdFromToken($request);

            if ($currentUserId == 'Unauthorized')
                return response()->json([
                    'exception' => true,
                    'message' => 'Unauthorized'
                ], 401);

            $document = Document::create([
                'filename' => $fileName,
                'path' => asset('bilanci') . '/' . $fileName,
                'type' => 'bilancio',
                'taxonomy' => $taxonomyName,
                'codice_documento' => rand(1, 999999999),
                'company_id' => $request->header('currentcompany'),
                'forma_giuridica' => $request->forma_giuridica,
                'tipo_azienda' => $request->tipo_azienda,
                'user_id' => $currentUserId
            ]);
        }

        $taxonomyPath = base_path() . "/taxonomies/2018-11-04/" . $taxonomyName;


        try {

            // Clean up XBRL instance document before parsing to prevent xml errors
            if (file_exists($filePath)) {
                $data = file_get_contents($filePath);
                $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
                $data = str_replace(['&euro;', '&nbsp;', '&agrave;', '&egrave;', '&igrave;', '&ograve;', '&ugrave;', '&Agrave;', '&Egrave;', '&Igrave;', '&Ograve;', '&Ugrave;', '&deg;', '&apos;'], ['€', ' ', 'à', 'è', 'ì', 'ò', 'ù', 'À', 'È', 'Ì', 'Ò', 'Ù', '°', '\''], $data);
                file_put_contents($filePath, $data);
            }

            $emptyInstance = false;
            $readXBRL = XBRL_Instance::FromInstanceDocument($filePath, $taxonomyPath, $emptyInstance);

            if ($readXBRL) {
                $userID = $request->userID;
                $bilancioJSON = $readXBRL->toJSON();


                //  $renderHTML = $bilanciHelper->generateHTMLRender($filePath, $taxonomyPath);
                $period = $bilanciHelper->getPeriodFromContext(json_decode($bilancioJSON)->contexts);
                $nomeAzienda = $bilanciHelper->getNomeAziendaFromElements(json_decode($bilancioJSON)->elements->DatiAnagraficiDenominazione);

                if ($nomeAzienda && $period) {
                    $document = Document::find($document->id);
                    $document->update([
                        'nome_azienda' => $nomeAzienda,
                        'anno_inizio' => $period['anno_inizio'],
                        'anno_fine' => $period['anno_fine']
                    ]);
                }

                /*  return response()->json([
                      'exception' => false,
                      'nome_azienda' => $nomeAzienda,
                      'period' => $period,   
                 //     'bilancioJSON' => $bilancioJSON,
                      'bilancioAnalisi' => $bilanciHelper->getIndexesForBalanceTaxonomyForAi($document->id, $filePath, $readXBRL, $document->codice_documento, $userID),
                  ], 200);*/


                $bilancioAnalisi = json_encode($bilanciHelper->getIndexesForBalanceTaxonomyForAi($document->id, $filePath, $readXBRL, $document->codice_documento, $userID));
                $vocidibilanciocomplete = $this->cleanBilancioData($bilancioJSON);

                $responseString = "Sei un analista finanziario esperto, con una profonda conoscenza delle dinamiche economiche e finanziarie. Specializzato nell'analisi dei bilanci aziendali e nella valutazione delle performance economico-finanziarie, il tuo lavoro si concentra su imprese di ogni dimensione e appartenenti a diversi settori. Utilizzi un linguaggio tecnico ma accessibile, chiaro e diretto, con un tono di voce autorevole ma empatico, in grado di trasmettere fiducia e professionalità.

Il tuo obiettivo principale è supportare le aziende nella gestione ottimale delle loro risorse finanziarie, contribuendo a una solida pianificazione strategica e alla riduzione dei rischi. Ti occupi di analisi approfondite dei flussi di cassa, interpretazione accurata dei bilanci, redazione di piani di investimento e ottimizzazione fiscale. Inoltre, fornisci consulenze personalizzate e strategie mirate per migliorare la solidità economica, la redditività e l’efficienza operativa delle aziende.

Attraverso report dettagliati e approfonditi, offri suggerimenti concreti e applicabili, sempre basati su dati verificati e analisi rigorose. Il tuo approccio prevede un focus sulla comunicazione efficace: semplifichi concetti complessi senza sacrificare la precisione, rendendo le informazioni accessibili sia agli imprenditori esperti che a quelli meno abituati a trattare con la finanza.

Utilizzi un italiano formale ma non rigido, arricchito da termini tecnici spiegati con chiarezza per garantire una comprensione completa. Il tuo tono riflette la tua competenza e il tuo impegno nel costruire relazioni professionali solide e di fiducia con i tuoi clienti. L'obiettivo finale è offrire un valore tangibile, contribuendo al successo e alla crescita sostenibile delle imprese con cui collabori.
                
                Nome Azienda: " . $nomeAzienda . "\n" .
                    "Periodo Analizzato preso in considerazione: " . json_encode($period) . "\n" .
                    "Bilancio Analisi: " . $bilancioAnalisi . "\n" .
                    "Bilancio Tassonomia Italiana (Dati estratti): " . json_encode($vocidibilanciocomplete, JSON_PRETTY_PRINT);

                // Restituzione della risposta come stringa
                return response($responseString, 200)
                    ->header('Content-Type', 'text/plain');

            } else {
                return response()->json([
                    'exception' => true,
                    'message' => 'Il file è danneggiato'
                ], 202);
            }


        } catch (Exception $e) {
            dd($e);
            return response()->json([
                'exception' => true,
                'message' => $e->getMessage()
            ], 500);
        }

    }


    public function cleanBilancioData($bilancioJSON)
    {
        // Decodifica il JSON
        $decodedData = json_decode($bilancioJSON, true);

        // Assicurati che esista la proprietà "elements"
        if (!isset($decodedData['elements'])) {
            return [];
        }

        // Funzione ricorsiva per attraversare la struttura e pulire i valori
        $cleanedData = [];

        // Iteriamo sugli elementi
        //   dd($decodedData['elements']);
        foreach ($decodedData['elements'] as $key => $value) {
            // Se l'elemento è un array (nel tuo caso sembra esserlo), attraversiamo ricorsivamente
            if (is_array($value)) {

                foreach ($value as $innerArray) {

                    if (array_key_exists('value', $innerArray)) {

                        $result[$key] = empty($innerArray['value']) ? 0 : $innerArray['value'];
                    } else {
                        // Unisci i risultati interni nel risultato principale
                        $result[$key] = 0;
                    }
                }
                // Se troviamo un 'value', lo salviamo

            }
        }
        return $result;
    }
    public function missingVoices(Request $request)
    {
        $documentId = $request->documentId;

        if (!$documentId)
            return response()->json([
                'error' => true,
                'data' => "Id Bilancio Errato"
            ]);


        try {

            foreach ($request->voci as $key => $singleVoice) {
                if (preg_match('/^(.*)_(\d+)$/', $key, $matches)) {
                    $voiceFullName = $matches[1];
                    $period = $matches[2];
                } else {
                    $voiceFullName = $key;
                    $period = 1;
                }

                MissingVoice::updateOrCreate(
                    [
                        'documentId' => $documentId,
                        'voiceFullName' => $voiceFullName,
                        'period' => $period
                    ],
                    [
                        'voiceLabel' => false,
                        'voiceValue' => $singleVoice,
                    ]
                );
            }



            return response()->json([
                'error' => false,
                'data' => "Voci mancanti salvate"
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'data' => $e->getMessage()
            ]);
        }
    }


    public function updateMissingVoices(Request $request)
    {
        $documentId = $request->documentId;

        if (!$documentId)
            return response()->json([
                'error' => true,
                'data' => "Id Bilancio Errato"
            ]);


        try {

            foreach ($request->voci as $key => $singleVoice) {
                if (preg_match('/^(.*)_(\d+)$/', $key, $matches)) {
                    $voiceFullName = $matches[1];
                    $period = $matches[2];
                } else {
                    $voiceFullName = $key;
                    $period = 1;
                }

                MissingVoice::updateOrCreate(
                    [
                        'documentId' => $documentId,
                        'voiceFullName' => $voiceFullName,
                        'period' => $period
                    ],
                    [
                        'voiceValue' => $singleVoice
                    ]
                );
            }

            return response()->json([
                'error' => false,
                'data' => "Voci aggiornate correttamente"
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'data' => $e->getMessage()
            ]);
        }
    }


    public function destroy($idBilancio)
    {
        if (!$idBilancio) {

            // CustomLog::addToLogBilanci('Bilanci destroy', 'ID non specificato');

            return response()->json([
                'error' => true,
                'message' => 'Specifica l\'Id del bilancio',
            ]);
        }
        try {
            $bilancio = Document::findOrFail($idBilancio);
            $bilancio->delete();

            // CustomLog::addToLogBilanci('Bilanci destroy', 'Eliminato');

            return response()->json([
                'error' => false,
                'message' => 'Bilancio eliminato correttamente',
            ]);
        } catch (Exception $e) {

            // CustomLog::addToLogBilanci('Bilanci destroy', 'Exception: '.$e.'.');

            return response()->json([
                'error' => false,
                'type' => 'Eccezione',
                'message' => $e,
            ]);
        }
    }


    public function getDocuments(Request $request)
    {
        try {
            $bilanciHelper = new BilanciHelper;
            $currentUserId = $bilanciHelper->getCurrentUserIdFromToken($request);

            if ($currentUserId == 'Unauthorized')
                return response()->json([
                    'exception' => true,
                    'message' => 'Unauthorized'
                ], 401);

            if ($request->header('currentcompany') || $request->header('currentcompany') === 0) {
                $documentsCr = Document::where('type', 'bilancio')->where('company_id', $request->header('currentcompany'))->where('user_id', $currentUserId)->orderBy('created_at', 'desc')->get();
            } else {
                $documentsCr = Document::where('type', 'bilancio')->where('user_id', $currentUserId)->orderBy('created_at', 'desc')->get();
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
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function modalitySetting(Request $request)
    {
        try {
            $userID = $request->userID;
            $modality = $request->mod;

            $currentUser = User::findOrFail($userID);
            $currentUser->update(['modAnalisi' => $modality]);

            return response()->json([
                'Message' => 'Modalità analisi cambiata correttamente.',
                'mod' => $modality
            ]);

        } catch (Exception $e) {
            return response()->json($e->getMessage());
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
                ->where('type', 'bilancio')
                ->where('user_id', $currentUserId)
                ->firstOrFail();

            if ($currentCompany) {
                Document::where('type', 'bilancio')
                    ->where('company_id', $currentCompany)
                    ->where('user_id', $currentUserId)
                    ->update(['predefinito' => false]);
            } else {
                Document::where('type', 'bilancio')
                    ->where('user_id', $currentUserId)
                    ->update(['predefinito' => false]);
            }

            $document->predefinito = true;
            $document->save();

            return response()->json([
                'error' => false,
                'message' => 'Bilancio impostato come predefinito.'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Classifica lo score Advanced su scala 0-1 con le stesse soglie dell'allerta.
     */
    private function classifyAdvScore($scoreRaw)
    {
        $score = (float) str_replace(',', '.', $scoreRaw);
        if ($score >= 0.85) return 'Solidità';
        if ($score >= 0.70) return 'Fragilità';
        if ($score >= 0.56) return 'Fragilità elevata';
        if ($score >= 0.42) return 'Rischio alert';
        if ($score >= 0.28) return 'Alert';
        if ($score >= 0.14) return 'Situazione Grave';
        return 'Default';
    }
}
