<?php

namespace App\Financial\Bilanci\Controllers;

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

        $file = $request->base64;
        $filePath = $file->getPathName();
        $taxonomyName = $bilanciHelper->getInstanceTaxonomyHRef($filePath);
        $taxonomyPath = base_path()."/taxonomies/2018-11-04/".$taxonomyName;

        try {

            $emptyInstance = false;
            $readXBRL = XBRL_Instance::FromInstanceDocument($filePath, $taxonomyPath, $emptyInstance);
            $bilancioJSON = $readXBRL->toJSON();
            $renderHTML = $bilanciHelper->generateHTMLRender($filePath, $taxonomyPath);

            $bilancioCalculationHelper = new BilanciCalculationsHelperAdvanced();
            $bilancioCalculationHelper->setCurrentInstance($readXBRL);
            $debitiTest = $bilancioCalculationHelper->getElementFromBalance('DebitiEsigibiliEntroEsercizioSuccessivo');

dd($debitiTest);
            $fileName = "Bilancio_" . time() . '.xbrl';
            Storage::disk('bilanci')->put($fileName, base64_decode($file));
            $document = Document::create([
                'filename' => $fileName,
                'path' => asset('bilanci') . '/' . $fileName,
                'type' => 'bilancio',
                'taxonomy' => $taxonomyName,
            ]);


            return response()->json([
                'exception' => false,
                'renderHTML' => $renderHTML,
                'bilancioJSON' => $bilancioJSON,
                'bilancioAnalisi' => $bilanciHelper->getIndexesForBalanceTaxonomy($document->id),
            ], 200);

        } catch(Exception $e) {

            return response()->json([
                'exception' => true,
                'message' => $e->getMessage()
            ], 500);
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
