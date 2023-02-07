<?php

namespace App\Financial\Bilanci\Controllers;
use Vtiful\Kernel\Excel;

ini_set('max_input_vars', 5000);

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests;
use App;
use lyquidity\XPath2\XPath2Exception;
use XBRL\XBRL_Instance;
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

            if(isset($singleBilancio->year)) {
                $singleBilancio->annoFormatted = $singleBilancio->year;
            }
        }

        return response()->json([
            'error' => false,
            'data' => $bilancis
        ], 200);
    }



    /**
     * @return mixed
     */
    public function recap(Request $request)
    {
        global $use_xbrl_functions;
        $use_xbrl_functions = true;

        $bilanciHelper = new BilanciHelper();
        if($request->documentId) {
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

            $taxonomyPath = base_path()."/taxonomies/2018-11-04/".$taxonomyName;


        try {

            $emptyInstance = false;
            $readXBRL = XBRL_Instance::FromInstanceDocument($filePath, $taxonomyPath, $emptyInstance);

            if($readXBRL) {
                $bilancioJSON = $readXBRL->toJSON();
                $renderHTML = $bilanciHelper->generateHTMLRender($filePath, $taxonomyPath);
                $period = $bilanciHelper->getPeriodFromContext(json_decode($bilancioJSON)->contexts);
                $nomeAzienda = $bilanciHelper->getNomeAziendaFromElements(json_decode($bilancioJSON)->elements->DatiAnagraficiDenominazione);

                if($nomeAzienda && $period) {
                    $document = Document::find($document->id);
                    $document->update([
                        'nome_azienda' => $nomeAzienda,
                        'anno_inizio' => $period['anno_inizio'],
                        'anno_fine' => $period['anno_fine']
                    ]);
                }

                return response()->json([
                    'exception' => false,
                    'nome_azienda' => $nomeAzienda,
                    'period' => $period,
                    'codice_documento' => $document->codice_documento,
                    'idDocumento' => $document->id,
                    'renderHTML' => $renderHTML,
                    'bilancioJSON' => $bilancioJSON,
                    'bilancioAnalisi' => $bilanciHelper->getIndexesForBalanceTaxonomy($document->id, $filePath, $readXBRL, $document->codice_documento),
                ], 200);
            } else {
                return response()->json([
                    'exception' => true,
                    'message' => 'Il file è danneggiato'
                ], 202);
            }


        } catch(Exception $e) {
            dd($e);
            return response()->json([
                'exception' => true,
                'message' => $e->getMessage()
            ], 500);
        }

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

            foreach($request->voci as $key => $singleVoice) {

                MissingVoice::create([
                    'documentId' => $documentId,
                    'voiceFullName' => explode('_', $key)[0],
                    'voiceLabel' => false,
                    'voiceValue' => $singleVoice,
                    'period' => explode('_', $key)[1]
                ]);
            }



            return response()->json([
                'error' => false,
                'data' => "Voci mancanti salvate"
            ]);

        } catch(Exception $e) {
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

            foreach($request->voci as $key => $singleVoice) {
                MissingVoice::where('documentId', $documentId)->where('voiceFullName', explode('_', $key)[0])->update(['voiceValue' => $singleVoice]);
            }

            return response()->json([
                'error' => false,
                'data' => "Voci aggiornate correttamente"
            ]); 

        } catch(Exception $e) {
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
        } catch (Excepton $e) {

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
            }

            return response()->json([
                $documentsCr,
            ]);
        } catch(Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
