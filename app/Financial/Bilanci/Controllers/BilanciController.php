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
use App\Models\Bilanci;
use App\Models\Account;
use function Livewire\str;
use Illuminate\Support\Facades\DB;
use App\Helpers\Bilanci\BilanciHelper;
use App\Models\Voci;
use Storage;
use App\Models\Document;
use App\Models\CustomLog;
use Carbon\Carbon;
use Auth;
use Exception;

class BilanciController extends Controller
{

    /**
     * @return \Illuminate\View\View
     */
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
    public function create()
    {
        $accounts = Account::pluck('name', 'id')->all();
        return view('bilanci.create', compact('accounts'));
    }

    public function copiaBilancio(Request $request)
    {
        if ($request->id) {
            $bilancioReplicated = Bilanci::find($request->id);
            $newBilancioCopy = $bilancioReplicated->replicate();
            $newBilancioCopy->created_at = Carbon::now();
            $newBilancioCopy->save();

            return response()->json([
                'error' => false,
                'data' => "Il Bilancio è stato copiato correttamente"
            ]);
        } else {
            return response()->json([
                'error' => true,
                'data' => "ID Bilancio non trovato"
            ], 404);
        }
    }




    /**
     * @return mixed
     */
    public function recap(Request $request)
    {
        $tipo_azienda = $request->input('tipo_azienda');
        $formaGiuridica = $request->input('forma_giuridica');

        global $use_xbrl_functions;
        $use_xbrl_functions = true;

        $jsonData = array();

        $file = $request->base64;
        $instance = false;

        try {
            $result = XBRL_Instance::FromInstanceDocumentWithExtensionTaxonomy($file->getPathName(), base_path() . "/taxonomies/2018-11-04/itcc-ci-2018-11-04.xsd", 'XBRL', $instance);
        } catch(Exception $e) {

            return response()->json([
                'exception' => true,
                'message' => $e->getMessage()
            ], 400);
        }

        $contexts = ($result->getContexts()->getContexts());
        $years = array();

        foreach ($contexts as $cont) {
            $years[] = $cont['period']['startDate'];
            $years[] = $cont['period']['endDate'];
        }

        usort($years, function ($a, $b) {
            return strtotime($a) - strtotime($b);
        });

        $years = array_values(array_unique($years));


        ksort($contexts);

        foreach ($contexts as $data => $value) {
            if ($value['period']['type'] == 'duration') {
                if ($value['period']['startDate'] == $years[0] && $value['period']['endDate'] == $years[1]) {
                    $prevCntxt_d = $data;
                } else if ($value['period']['startDate'] == $years[2] && $value['period']['endDate'] == $years[3]) {
                    $currentCntxt_d = $data;
                }
            } else if ($value['period']['type'] == 'instant') {
                if ($value['period']['startDate'] == $years[1] && $value['period']['endDate'] == $years[1]) {
                    $prevCntxt_i = $data;
                } else if ($value['period']['startDate'] == $years[3] && $value['period']['endDate'] == $years[3]) {
                    $currentCntxt_i = $data;
                }
            }
        }

        $date = array();

        foreach ($contexts as $data => $val) {
            $date[] = $val['period']['startDate'];
            $date[] = $val['period']['endDate'];
        }

        $ordDate = array_reverse(array_unique($date, SORT_STRING));
        sort($ordDate);

        $jsonData['prevYear'] = $years[0] . ' ' . $years[1];
        $jsonData['currentYear'] = $years[2] . ' ' . $years[3];
        $jsonData['years'] = explode('-', $years[0])[0].'-'.explode('-', $years[2])[0];

       // CustomLog::addToLogBilanci('Bilanci Recap', 'Sono stati estratti gli anni '.$jsonData['years'].'.');

        $elements = $result->getElements();
        $elements = $elements->getElements();


        foreach ($elements as $key => $elemento) {

            $chiave = array_keys($elemento);

            if (count($elemento) == 2) {
                $first = array_shift($elemento);

                // $valuePrev = 0;
                // $valueCurr = 0;

                if (!isset($first['tuple_elements'])) {
                    if ($first['contextRef'] == $currentCntxt_d || $first['contextRef'] == $currentCntxt_i) {
                        $value = 0;
                        if (isset($first['value'])) {
                            $value = $first['value'];
                        }
                        if (str_contains($first['taxonomy_element']['type'], 'monetaryItemType')) {
                            $jsonData['current'][$first['taxonomy_element']['name']] = $value;
                        } else if (str_contains($first['taxonomy_element']['name'], 'Anagrafic')) {
                            $jsonData['anagrafic'][$first['taxonomy_element']['name']] = $value;
                        }
                    }
                    if ($first['contextRef'] == $prevCntxt_d || $first['contextRef'] == $prevCntxt_i) {
                        $value = 0;
                        if (isset($first['value'])) {
                            $value = $first['value'];
                        }
                        if (str_contains($first['taxonomy_element']['type'], 'monetaryItemType')) {
                            $jsonData['prev'][$first['taxonomy_element']['name']] = $value;
                        } else if (str_contains($first['taxonomy_element']['name'], 'Anagrafic')) {
                            $jsonData['anagrafic'][$first['taxonomy_element']['name']] = $value;
                        }
                    }
                } else {
                    $tmp = $first['tuple_elements'];
                    foreach ($tmp as $valueCode => $values) {

                        $internalData = array_shift($values);
                        if (str_contains($internalData['contextRef'], $currentCntxt_d) || str_contains($internalData['contextRef'], $currentCntxt_i) || $internalData['contextRef'] == $currentCntxt_i || $internalData['contextRef'] === $currentCntxt_d) {
                            $value = 0;

                            if (isset($internalData['value'])) {
                                $value = $internalData['value'];
                            }
                            if (is_numeric($value) && !(str_contains($internalData['taxonomy_element']['name'], 'Anagrafic'))) {
                                $jsonData['current'][$internalData['taxonomy_element']['name']] = $value;
                            } else if (str_contains($internalData['taxonomy_element']['name'], 'Anagrafic')) {
                                $jsonData['anagrafic'][$internalData['taxonomy_element']['name']] = $value;
                            } else if ($internalData['taxonomy_element']['type'] == 'xbrli:stringItemType') {
                                $jsonData['anagrafic'][$internalData['taxonomy_element']['name']] = $value;
                            } else if ($internalData['taxonomy_element']['type'] == 'xbrli:monetaryItemType') {
                                $jsonData['current'][$internalData['taxonomy_element']['name']] = $value;
                            }
                        }
                        if (str_contains($internalData['contextRef'], $prevCntxt_d) || str_contains($internalData['contextRef'], $prevCntxt_i) || $internalData['contextRef'] === $prevCntxt_i || $internalData['contextRef'] === $prevCntxt_d) {
                            $value = 0;
                            if (isset($internalData['value'])) {
                                $value = $internalData['value'];
                            }
                            if (is_numeric($value) && !(str_contains($internalData['taxonomy_element']['name'], 'Anagrafic'))) {
                                $jsonData['prev'][$internalData['taxonomy_element']['name']] = $value;
                            } else if (str_contains($internalData['taxonomy_element']['name'], 'Anagrafic')) {
                                $jsonData['anagrafic'][$internalData['taxonomy_element']['name']] = $value;
                            } else if ($internalData['taxonomy_element']['type'] == 'xbrli:stringItemType') {
                                $jsonData['anagrafic'][$internalData['taxonomy_element']['name']] = $value;
                            } else if ($internalData['taxonomy_element']['type'] == 'xbrli:monetaryItemType') {
                                $jsonData['prev'][$internalData['taxonomy_element']['name']] = $value;
                            }
                        }
                    }
                }

                $second = array_shift($elemento);

                if (!isset($second['tuple_elements'])) {
                    if ($second['contextRef'] == $currentCntxt_d || $second['contextRef'] == $currentCntxt_i) {
                        $value = 0;
                        if (isset($second['value'])) {
                            $value = $second['value'];
                        }
                        if (str_contains($second['taxonomy_element']['type'], 'monetaryItemType')) {
                            $jsonData['current'][$second['taxonomy_element']['name']] = $value;
                        } else if (str_contains($second['taxonomy_element']['name'], 'Anagrafic')) {
                            $jsonData['anagrafic'][$second['taxonomy_element']['name']] = $value;
                        }
                    }
                    if ($second['contextRef'] == $prevCntxt_d || $second['contextRef'] == $prevCntxt_i) {
                        $value = 0;
                        if (isset($second['value'])) {
                            $value = $second['value'];
                        }
                        if (str_contains($second['taxonomy_element']['type'], 'monetaryItemType')) {
                            $jsonData['prev'][$second['taxonomy_element']['name']] = $value;
                        } else if (str_contains($second['taxonomy_element']['name'], 'Anagrafic')) {
                            $jsonData['anagrafic'][$second['taxonomy_element']['name']] = $value;
                        }
                    }
                } else {
                    $tmp = $second['tuple_elements'];
                    foreach ($tmp as $valueCode => $values) {
                        $internalData = array_shift($values);
                        if (str_contains($internalData['contextRef'], $currentCntxt_d) || str_contains($internalData['contextRef'], $currentCntxt_i) || $internalData['contextRef'] == $currentCntxt_i || $internalData['contextRef'] === $currentCntxt_d) {
                            $value = 0;

                            if (isset($internalData['value'])) {
                                $value = $internalData['value'];
                            }
                            if (is_numeric($value) && !(str_contains($internalData['taxonomy_element']['name'], 'Anagrafic'))) {
                                $jsonData['singleCurrent'][$internalData['taxonomy_element']['name']] = $value;
                            } else if (str_contains($internalData['taxonomy_element']['name'], 'Anagrafic')) {
                                $jsonData['anagrafic'][$internalData['taxonomy_element']['name']] = $value;
                            } else if ($internalData['taxonomy_element']['type'] == 'xbrli:stringItemType') {
                                $jsonData['anagrafic'][$internalData['taxonomy_element']['name']] = $value;
                            } else if ($internalData['taxonomy_element']['type'] == 'xbrli:monetaryItemType') {
                                $jsonData['singleCurrent'][$internalData['taxonomy_element']['name']] = $value;
                            }
                        }
                        if (str_contains($internalData['contextRef'], $prevCntxt_d) || str_contains($internalData['contextRef'], $prevCntxt_i) || $internalData['contextRef'] === $prevCntxt_i || $internalData['contextRef'] === $prevCntxt_d) {
                            $value = 0;
                            if (isset($internalData['value'])) {
                                $value = $internalData['value'];
                            }
                            if (is_numeric($value) && !(str_contains($internalData['taxonomy_element']['name'], 'Anagrafic'))) {
                                $jsonData['singlePrev'][$internalData['taxonomy_element']['name']] = $value;
                            } else if (str_contains($internalData['taxonomy_element']['name'], 'Anagrafic')) {
                                $jsonData['anagrafic'][$internalData['taxonomy_element']['name']] = $value;
                            } else if ($internalData['taxonomy_element']['type'] == 'xbrli:stringItemType') {
                                $jsonData['anagrafic'][$internalData['taxonomy_element']['name']] = $value;
                            } else if ($internalData['taxonomy_element']['type'] == 'xbrli:monetaryItemType') {
                                $jsonData['singlePrev'][$internalData['taxonomy_element']['name']] = $value;
                            }
                        }
                    }
                }
            }

            if (count($elemento) == 1) {
                $data = array_shift($elemento);
                if (!isset($data['tuple_elements'])) {
                    if ($data['contextRef'] == $currentCntxt_d || $data['contextRef'] == $currentCntxt_i) {
                        $value = 0;
                        if (isset($data['value'])) {
                            $value = $data['value'];
                        }
                        if (str_contains($data['taxonomy_element']['type'], 'monetaryItemType')) {
                            $jsonData['current'][$data['taxonomy_element']['name']] = $value;
                            $jsonData['prev'][$data['taxonomy_element']['name']] = 0;
                        } else if (str_contains($data['taxonomy_element']['name'], 'Anagrafic')) {
                            $jsonData['anagrafic'][$data['taxonomy_element']['name']] = $value;
                        }
                    }
                    if ($data['contextRef'] == $prevCntxt_d || $data['contextRef'] == $prevCntxt_i) {
                        $value = 0;
                        if (isset($data['value'])) {
                            $value = $data['value'];
                        }
                        if (str_contains($data['taxonomy_element']['type'], 'monetaryItemType')) {
                            $jsonData['prev'][$data['taxonomy_element']['name']] = $value;
                            $jsonData['current'][$data['taxonomy_element']['name']] = 0;
                        } else if (str_contains($data['taxonomy_element']['name'], 'Anagrafic')) {
                            $jsonData['anagrafic'][$data['taxonomy_element']['name']] = $value;
                        }
                    }
                } else {
                    $tmp = $data['tuple_elements'];
                    foreach ($tmp as $valueCode => $values) {
                        $internalData = array_shift($values);
                        if (str_contains($internalData['contextRef'], $currentCntxt_d) || str_contains($internalData['contextRef'], $currentCntxt_i) || $internalData['contextRef'] == $currentCntxt_i || $internalData['contextRef'] === $currentCntxt_d) {
                            $value = 0;

                            if (isset($internalData['value'])) {
                                $value = $internalData['value'];
                            }
                            if (is_numeric($value) && !(str_contains($internalData['taxonomy_element']['name'], 'Anagrafic'))) {
                                $jsonData['singleCurrent'][$internalData['taxonomy_element']['name']] = $value;
                            } else if (str_contains($internalData['taxonomy_element']['name'], 'Anagrafic')) {
                                $jsonData['anagrafic'][$internalData['taxonomy_element']['name']] = $value;
                            } else if ($internalData['taxonomy_element']['type'] == 'xbrli:stringItemType') {
                                $jsonData['anagrafic'][$internalData['taxonomy_element']['name']] = $value;
                            } else if ($internalData['taxonomy_element']['type'] == 'xbrli:monetaryItemType') {
                                $jsonData['singleCurrent'][$internalData['taxonomy_element']['name']] = $value;
                            }
                        }
                        if (str_contains($internalData['contextRef'], $prevCntxt_d) || str_contains($internalData['contextRef'], $prevCntxt_i) || $internalData['contextRef'] === $prevCntxt_i || $internalData['contextRef'] === $prevCntxt_d) {
                            $value = 0;
                            if (isset($internalData['value'])) {
                                $value = $internalData['value'];
                            }
                            if (is_numeric($value) && !(str_contains($internalData['taxonomy_element']['name'], 'Anagrafic'))) {
                                $jsonData['singlePrev'][$internalData['taxonomy_element']['name']] = $value;
                            } else if (str_contains($internalData['taxonomy_element']['name'], 'Anagrafic')) {
                                $jsonData['anagrafic'][$internalData['taxonomy_element']['name']] = $value;
                            } else if ($internalData['taxonomy_element']['type'] == 'xbrli:stringItemType') {
                                $jsonData['anagrafic'][$internalData['taxonomy_element']['name']] = $value;
                            } else if ($internalData['taxonomy_element']['type'] == 'xbrli:monetaryItemType') {
                                $jsonData['singlePrev'][$internalData['taxonomy_element']['name']] = $value;
                            }
                        }
                    }
                }
            }
        }

        $voci = DB::table('vocis')->get();
        $gradi = array();

        foreach ($voci as $voce) {
            if ($voce->voce_padre == null || $voce->voce_padre == "") {
                $h1[$voce->name] = $voce->extended_name;
                $gradi[] = array();
            } else {
                foreach ($voci as $voci1) {
                    if ($voce->voce_padre == $voci1->name) {
                        if ($voci1->voce_padre == null || $voci1->voce_padre == "") {
                            $gradi[$voci1->name][$voce->name] = array();
                        } else {
                            foreach ($voci as $voci2) {
                                if ($voci1->voce_padre == $voci2->name) {
                                    if ($voci2->voce_padre == null || $voci2->voce_padre == "") {
                                        $gradi[$voci2->name][$voci1->name][$voce->name] = array();
                                    } else {
                                        foreach ($voci as $voci3) {
                                            if ($voci2->voce_padre == $voci3->name) {
                                                if ($voci3->voce_padre == null || $voci3->voce_padre == "") {
                                                    $gradi[$voci3->name][$voci2->name][$voci1->name][$voce->name] = array();
                                                } else {
                                                    foreach ($voci as $voci4) {
                                                        if ($voci3->voce_padre == $voci4->name) {
                                                            if ($voci4->voce_padre == null || $voci4->voce_padre == "") {
                                                                $gradi[$voci4->name][$voci3->name][$voci2->name][$voci1->name][$voce->name] = array();
                                                            } else {
                                                                foreach ($voci as $voci5) {
                                                                    if ($voci4->voce_padre == $voci5->name) {
                                                                        if ($voci5->voce_padre == null || $voci5->voce_padre == "") {
                                                                            $gradi[$voci5->name][$voci4->name][$voci3->name][$voci2->name][$voci1->name][$voce->name] = array();
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        unset($gradi[0], $gradi[1], $gradi[2]);

        $vociExt = array();

        foreach ($voci as $voce) {
            $vociExt[$voce->name] = $voce->extended_name;
        }

        $currentYear = $jsonData['currentYear'];
        $prevYear = $jsonData['prevYear'];
        $support1 = $jsonData['current'];
        $support2 = $jsonData['prev'];
        $support3 = array();
        ksort($support1);
        ksort($support2);


        foreach ($support1 as $sup1 => $val1) {
            foreach ($support2 as $sup2 => $val2) {
                if ($sup1 == $sup2) {
                    $support3[$sup1]['current'] = $val1;
                    $support3[$sup1]['prev'] = $val2;
                }
            }
        }

        foreach ($vociExt as $name => $extName) {
            foreach (array('current', 'prev') as $index => $period) {
                if (isset($support3[$extName][$period])) {
                    if ($support3[$extName][$period] == 0) {
                        $sons = DB::table('vocis')->where('voce_padre', $name)->get()->toArray();
                        $sum = 0;
                        foreach ($sons as $label => $item) {
                            if (isset($support3[$item->extended_name][$period])) {
                                $currentSum = $sum + $support3[$item->extended_name][$period];
                            }
                        }
                        $support3[$extName][$period] = $sum;
                    }
                }
            }
        }

        $indiciImportanti = Voci::select('name', 'extended_name', 'voce_padre')->where('required', 1)->orderBy('name')->get()->toArray();

        $vociBilancioMancanti = array();

        foreach ($indiciImportanti as $index => $values) {
            if (!in_array($values['extended_name'], array_keys($support3))) {
                $vociBilancioMancanti[$values['extended_name']] = $values['name'];
                unset($support3[$values['extended_name']]);
            }
        }
        $completeBranch = array();
        $extNames = array();

        foreach ($vociBilancioMancanti as $extNome => $nome) {
            $extNames[$nome] = $extNome;
        }
        foreach ($vociBilancioMancanti as $ext => $name) {
            $completeBranch[$name] = $this->getSonsFromFather($name, $extNames);
        }

        if($vociBilancioMancanti) {
            $encodeVociMancanti = json_encode($vociBilancioMancanti);

          //  CustomLog::addToLogBilanci('Bilanci Recap', 'Le voci di bilancio mancanti sono le seguenti: '.$encodeVociMancanti.'');
        }

        $request->session()->put('extNames', $extNames);
        $request->session()->put('mascheraOrdinata', $this->mascheraOrdinata());
        $request->session()->put('formaGiuridica', $formaGiuridica);
        $request->session()->put('vociBilancioMancanti', $vociBilancioMancanti);
        $request->session()->put('currentYear', $currentYear);
        $request->session()->put('prevYear', $prevYear);
        $request->session()->put('support3', $support3);
        $request->session()->put('jsonData', $jsonData);
        $request->session()->put('tipo_azienda', $tipo_azienda);
        $request->session()->put('gradi', $gradi);
        $request->session()->put('vociExt', $vociExt);
        $request->session()->put('account_id', $request->input('account_id'));

      //  CustomLog::addToLogBilanci('Bilanci Recap', 'Tutti i dati sono stati estratti');

        return response()->json([
            'vociBilancioMancanti' => $vociBilancioMancanti,
            'extNames' => $extNames,
            'mascheraOrdinata' => $this->mascheraOrdinata(),
            'formaGiuridica' => $formaGiuridica,
            'currentYear' => $currentYear,
            'prevYear' => $prevYear,
            'years' => $jsonData['years'],
            'support3' => $support3,
            'jsonData' => $jsonData,
            'tipo_azienda' => $tipo_azienda,
            'gradi' => $gradi,
            'vociExt' => $vociExt,
            'account_id' => $request->input('account_id')
        ]);
    }

    public function getSonsFromFather($father, $extNames)
    {
        $singleBranch = array();
        $sons = Voci::where('voce_padre', $father)->where('required', 1)->get();
        if (count($sons) == 0) {
            return $singleBranch;
        }
        foreach ($sons as $index => $son) {
            if (!in_array($son->name, $extNames)) {
                $singleBranch[$son->name] = $this->getSonsFromFather($son->name, $extNames);
            }
        }
        return $singleBranch;
    }

    /**
     * @return mixed
     */
    public function store(Request $request)
    {
        if (isset($request->base64)) {
            $importBilancio = $request->base64;

            $fileName = time() . '.xbrl';

            Storage::disk('bilanci')->put($fileName, base64_decode($importBilancio));

            //$importBilancio->move(asset('bilanci/'), $fileName, base64_decode($importBilancio));

            $dataBilancio = [
                'filename' => $fileName,
                'path' => asset('bilanci') . '/' . $fileName,
                'type' => 'bilancio'
            ];

            $document = Document::create($dataBilancio);

            CustomLog::addToLogBilanci('Bilanci Store', 'File importato');

            return response()->json([
                'error' => false,
                'data' => $dataBilancio,
            ]);
        }

        $jsonData = array();
        $jsonDataPrev = array();
        $jsonDataAnag = array();
        $alerts = array();
        $DebitiEsigibiliEntroEsercizioSuccessivo = array('curr' => 0, 'prev' => 0);
        $DebitiEsigibiliOltreEsercizioSuccessivo = array('curr' => 0, 'prev' => 0);

        $vociFiglieDebiti = Voci::where('voce_padre', 'Debiti')->get();

        foreach ($vociFiglieDebiti as $index => $actualVoice) {
            $nipoti = Voci::where('voce_padre', $actualVoice->name)->get();
            foreach ($nipoti as $label => $singleNipote) {
                if (str_contains($singleNipote->name, 'entro 12 mesi')) {
                    $DebitiEsigibiliEntroEsercizioSuccessivo['curr'] += $request->all()[($singleNipote->extended_name . '_curr')];
                    $DebitiEsigibiliEntroEsercizioSuccessivo['prev'] += $request->all()[($singleNipote->extended_name . '_prev')];
                } else if (str_contains($singleNipote->name, 'oltre 12 mesi')) {
                    $DebitiEsigibiliOltreEsercizioSuccessivo['curr'] += $request->all()[($singleNipote->extended_name . '_curr')];
                    $DebitiEsigibiliOltreEsercizioSuccessivo['prev'] += $request->all()[($singleNipote->extended_name . '_prev')];
                }
            }
        }

        if ((float)$request->input('ignoreAlert') != 1) {
            foreach ($request->all() as $singleInput => $singleValue) {
                if ((str_contains($singleInput, 'curr') !== false || str_contains($singleInput, 'prev') !== false) && !str_contains($singleInput, 'Year')) {
                    $explodedInput = explode('_', $singleInput);
                    $formattedSingleValue = (float)str_replace('.', '', $singleValue);
                    $voce = Voci::where('extended_name', $explodedInput[0])->get()->first();
                    if ($voce->required == 1) {
                        $vociFiglie = Voci::where('voce_padre', $voce->name)->get()->toArray();
                        if (Voci::where('voce_padre', $voce->name)->count() !== 0) {
                            $sommaVociFiglie = 0;
                            foreach ($vociFiglie as $index => $singleVoice) {
                                foreach ($request->all() as $innerInput => $innerValue) {
                                    $explodedInner = explode('_', $innerInput);
                                    if (($explodedInner[0] == $singleVoice['extended_name']) && str_contains($innerInput, $explodedInput[1])) {
                                        $formattedInnerValue = (float)str_replace('.', '', $innerValue);
                                        $sommaVociFiglie += $formattedInnerValue;
                                    }
                                }
                            }
                            if ($formattedSingleValue != 0 && $sommaVociFiglie != $formattedSingleValue) {
                                $voceIncorretta = Voci::where('extended_name', $explodedInput[0])->get()->first();
                                $alerts[$voceIncorretta->name] = $voceIncorretta->extended_name;
                            }
                        }
                    }
                }
            }
        }

        foreach ($request->input() as $singlereq => $singleValue) {
            $singleValue = str_replace('.', '', $singleValue);
            if (stripos($singlereq, 'curr')) {
                str_replace('Anagrafici', '', $singlereq);
                $jsonData[str_replace('_curr', '', $singlereq)] = $singleValue;
            } else if (stripos($singlereq, 'prev') && !(stripos($singlereq, 'Anagrafic'))) {
                $jsonDataPrev[str_replace('_prev', '', $singlereq)] = $singleValue;
            }
            if (stripos($singlereq, 'anag') || stripos($singlereq, 'Anagrafic')) {
                $jsonDataAnag[str_replace('_anag', '', $singlereq)] = $singleValue;
            }
        }

        $tipoAzienda = $request->tipo_azienda;
        $formaGiuridica = $request->formaGiuridica;

        $jsonData['DebitiEsigibiliEntroEsercizioSuccessivo'] = str_replace('.', '', number_format($DebitiEsigibiliEntroEsercizioSuccessivo['curr'], 3, '.', ','));
        $jsonDataPrev['DebitiEsigibiliEntroEsercizioSuccessivo'] = str_replace('.', '', number_format($DebitiEsigibiliEntroEsercizioSuccessivo['prev'], 3, '.', ','));
        $jsonData['DebitiEsigibiliOltreEsercizioSuccessivo'] = str_replace('.', '', number_format($DebitiEsigibiliOltreEsercizioSuccessivo['curr'], 3, '.', ','));
        $jsonDataPrev['DebitiEsigibiliOltreEsercizioSuccessivo'] = str_replace('.', '', number_format($DebitiEsigibiliOltreEsercizioSuccessivo['prev'], 3, '.', ','));

        $jsonDB = json_encode($jsonData);
        $jsonPrevDB = json_encode($jsonDataPrev);
        $jsonAnagDB = json_encode($jsonDataAnag);

        $accountId = $request->input('account_id');
        $currentYear = $request->input('currYear');
        $prevYear = $request->input('prevYear');
        $years = $request->input('years');

        $companyId = 0;
        if ($request->header('currentcompany') || $request->header('currentcompany') === 0) {
            $companyId = $request->header('currentcompany');
        }

        $bilancio = Bilanci::create([
            'json_data' => $jsonDB,
            'account_id' => $accountId,
            'json_data_prev' => $jsonPrevDB,
            'json_data_anag' => $jsonAnagDB,
            'current_year' => $currentYear,
            'prev_year' => $prevYear,
            'year' => $years,
            'tipo_azienda' => $tipoAzienda,
            'forma_giuridica' => $formaGiuridica,
            'company_id' => $companyId
        ]);

        CustomLog::addToLogBilanci('Bilanci Store', 'Inserimento a Database');

        return response()->json([
            'bilancioImported' => true,
            'tipo_azienda' => $tipoAzienda,
            'forma_giuridica' => $formaGiuridica,
            'idBilancio' => $bilancio->id
        ]);
    }

    /**
     * @param  User  $user
     *
     * @return mixed
     */
    public function show($id)
    {
        $bilanciHelper = new BilanciHelper();
        $gradi = $bilanciHelper->getVociTree();

        $bilancio = Bilanci::findOrFail($id);

        $attributes = $bilancio->getAttributes();
        $jsonData['current'] = json_decode($attributes['json_data'], true);
        $jsonData['prev'] = json_decode($attributes['json_data_prev'], true);
        $jsonData['anagrafic'] = json_decode($attributes['json_data_anag'], true);
        $jsonData['currentYear'] = $attributes['current_year'];
        $jsonData['prevYear'] = $attributes['prev_year'];
        $jsonData['alberatura'] = $gradi;

        $voci = voci::select('name', 'extended_name')->where('required', 1)->get()->pluck('extended_name', 'name')->toArray();
        $vociWithValuesCurrent = array();
        $vociWithValuesPrevious = array();
        foreach ($voci as $singleKey => $singleValye) {
            if (isset($jsonData['current'][$singleValye])) {
                $vociWithValuesCurrent[$singleKey] = $jsonData['current'][$singleValye];
            } else {
                $vociWithValuesCurrent[$singleKey] = 0;
            }

            if (isset($jsonData['prev'][$singleValye])) {
                $vociWithValuesPrevious[$singleKey] = $jsonData['prev'][$singleValye];
            } else {
                $vociWithValuesPrevious[$singleKey] = 0;
            }
        }
        $jsonData['vociDiBilancioConValoriCurrent']  = $vociWithValuesCurrent;
        $jsonData['vociDiBilancioConValoriPrevious']  = $vociWithValuesPrevious;

        CustomLog::addToLogBilanci('Bilanci Show', 'Visualizzato');

        return response()->json([
            'error' => false,
            'jsonData' => $jsonData,
        ]);
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


    public function mascheraOrdinata()
    {
        return array(
            0 => 'Crediti Immobilizzazioni Finanziarie',
            1 => 'Crediti',
            2 => 'Totale Crediti verso Clienti',
            3 => 'Totale Crediti verso Clienti entro 12 mesi',
            4 => 'Crediti verso clienti oltre 12 mesi',
            5 => 'Totale Crediti verso imprese controllate entro 12 mesi',
            6 => 'Totale Crediti verso imprese collegate entro 12 mesi',
            7 => 'Totale Crediti verso imprese controllanti entro 12 mesi',
            8 => 'Crediti tributari entro 12 mesi',
            9 => 'Crediti - Imposte anticipate',
            10 => 'Totale Crediti verso altri entro 12 mesi',
            11 => 'Debiti per obbligazioni non convertibili entro 12 mesi',
            12 => 'Debiti per obbligazioni non convertibili oltre 12 mesi',
            13 => 'Obbligazioni convertibili entro 12 mesi',
            14 => 'Obbligazioni convertibili oltre 12 mesi',
            15 => 'Debiti verso soci per finanziamenti entro 12 mesi',
            16 => 'Debiti verso soci per finanziamenti oltre 12 mesi',
            17 => 'Debiti verso banche entro 12 mesi',
            18 => 'Debiti verso banche oltre 12 mesi',
            19 => 'Debiti verso altri finanziatori entro 12 mesi',
            20 => 'Debiti verso altri finanziatori entro 12 mesi',
            21 => 'Acconti entro 12 mesi',
            22 => 'Acconti oltre 12 mesi',
            23 => 'Debiti verso fornitori entro 12 mesi',
            24 => 'Debiti verso fornitori oltre 12 mesi',
            25 => 'Debiti rappresentati da titoli di credito',
            26 => 'Debiti rappresentati da titoli di credito entro 12 mesi',
            27 => 'Debiti rappresentati da titoli di credito oltre 12 mesi',
            28 => 'Debiti verso imprese controllate',
            29 => 'Debiti verso imprese controllate entro 12 mesi',
            30 => 'Debiti verso imprese controllate oltre 12 mesi',
            31 => 'Debiti verso imprese collegate',
            32 => 'Debiti verso imprese collegate entro 12 mesi',
            33 => 'Debiti verso imprese collegate oltre 12 mesi',
            34 => 'Debiti verso controllanti',
            35 => 'Debiti verso controllanti entro 12 mesi',
            36 => 'Debiti verso controllanti oltre 12 mesi',
            37 => 'Debiti tributari',
            38 => 'Debiti tributari entro 12 mesi',
            39 => 'Debiti tributari oltre 12 mesi',
            40 => 'Debiti verso istituti di previdenza e sicurezza sociale',
            41 => 'Debiti verso istituti di previdenza e sicurezza sociale entro 12 mesi',
            42 => 'Debiti verso istituti di previdenza e sicurezza sociale oltre 12 mesi',
            43 => 'Altri debiti entro 12 mesi',
            44 => 'Altri debiti oltre 12 mesi',
            45 => 'Imposte anticipate (conto economico)'
        );
    }
}
