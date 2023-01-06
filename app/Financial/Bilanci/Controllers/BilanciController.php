<?php

namespace App\Financial\Bilanci\Controllers;
use Vtiful\Kernel\Excel;

ini_set('max_input_vars', 5000);

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests;
use App;
use lyquidity\xml\QName;
use lyquidity\XPath2\XPath2Exception;
use XBRL\XBRL_Instance;
use XBRL\XBRL_Report;
use XBRL\XBRL_DFR;
use App\Models\Bilanci;
use App\Models\Account;
use function Livewire\str;
use Illuminate\Support\Facades\DB;
use App\Helpers\Bilanci\BilanciHelper;
use App\Helpers\Bilanci\BilanciCalculationsHelperAdvanced;
use App\Models\Voci;
use Storage;
use App\Models\Document;
use App\Models\CustomLog;
use Carbon\Carbon;
use Auth;
use Exception;
use Illuminate\Support\Facades\Http;
use App\Models\MissingVoice;

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

            $document = Document::create([
                'filename' => $fileName,
                'path' => asset('bilanci') . '/' . $fileName,
                'type' => 'bilancio',
                'taxonomy' => $taxonomyName,
                'codice_documento' => rand(1, 999999999)
            ]);
        }

            $taxonomyPath = base_path()."/taxonomies/2018-11-04/".$taxonomyName;


        try {

            $emptyInstance = false;
            $readXBRL = XBRL_Instance::FromInstanceDocument($filePath, $taxonomyPath, $emptyInstance);

            if($readXBRL) {
                $bilancioJSON = $readXBRL->toJSON();
                $renderHTML = $bilanciHelper->generateHTMLRender($filePath, $taxonomyPath);
        

                return response()->json([
                    'exception' => false,
                    'idDocumento' => $document->id,
                    'renderHTML' => $renderHTML,
                    'bilancioJSON' => $bilancioJSON,
                    'bilancioAnalisi' => $bilanciHelper->getIndexesForBalanceTaxonomy($document->id, $filePath, $readXBRL),
                ], 200);
            } else {
                return response()->json([
                    'exception' => true,
                    'message' => 'Il file è danneggiato'
                ], 202);
            }
            

        } catch(Exception $e) {

            return response()->json([
                'exception' => true,
                'message' => $e->getMessage()
            ], 500);
        }

    }

    public function missingVoices(Request $request) 
    {
        try {

            foreach($request->missingVoices as $key => $singleVoice) {
                $voci = Voci::where('extended_name', $key)->get()->first();

                MissingVoice::create([
                    'documentId' => $request->idDocumento,
                    'voiceFullName' => $key,
                    'voiceLabel' => $voci->name,
                    'voiceValue' => $singleVoice['value'],
                    'period' => $singleVoice['period']
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



    public function destroy($idBilancio)
    {
        if (!$idBilancio) {

            CustomLog::addToLogBilanci('Bilanci destroy', 'ID non specificato');

            return response()->json([
                'error' => true,
                'message' => 'Specifica l\'Id del bilancio',
            ]);
        }
        try {
            $bilancio = Bilanci::findOrFail($idBilancio);
            $bilancio->delete();

            CustomLog::addToLogBilanci('Bilanci destroy', 'Eliminato');

            return response()->json([
                'error' => false,
                'message' => 'Bilancio eliminato correttamente',
            ]);
        } catch (Excepton $e) {

            CustomLog::addToLogBilanci('Bilanci destroy', 'Exception: '.$e.'.');

            return response()->json([
                'error' => false,
                'type' => 'Eccezione',
                'message' => $e,
            ]);
        }
    }
}
