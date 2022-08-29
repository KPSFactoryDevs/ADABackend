<?php

namespace App\Http\Controllers;

ini_set('max_input_vars', 5000);

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests;
use App;
use Illuminate\Support\Facades\DB;
use lyquidity\xml\QName;
use lyquidity\XPath2\XPath2Exception;
use XBRL\XBRL_Instance;
use App\Models\Bilanci;
use App\Models\Account;
use App\Models\AnalisisType;
use function Livewire\str;
use App\Helpers\Bilanci\BilanciHelper;
use DateTime;
use App\Models\Voci;

class BilanciController extends Controller
{

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
        return view('bilanci.create', compact('accounts'));
    }

    /**
     * @return mixed
     */
    public function recap(Request $request)
    {
        $tipo_azienda = $request->input('tipo_azienda');
      //  $formaGiuridica = $request->input('forma_giuridica');

        global $use_xbrl_functions;
        $use_xbrl_functions = true;

        $jsonData = array();

        $file = $request->file()['xbrl'];
        $instance = null;

        $result = XBRL_Instance::FromInstanceDocumentWithExtensionTaxonomy($file->getPathName(),  __DIR__ . '/../../../../taxonomies/2018-11-04/itcc-ci-2018-11-04.xsd', 'XBRL', $instance);
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

        $elements = $result->getElements();
        $elements = $elements->getElements();

        foreach ($elements as $key => $elemento) {
           // $chiave = array_keys($elemento);

            if (count($elemento) == 2) {
                $first = array_shift($elemento);

                $valuePrev = 0;
                $valueCurr = 0;

                if ($first['contextRef'] == $currentCntxt_d || $first['contextRef'] == $currentCntxt_i) {
                    if (isset($first['value'])  || $first['value'] == 0) {
                        $valueCurr = $first['value'];
                        $jsonData['current'][$first['taxonomy_element']['name']] = $valueCurr;
                    }
                } else if ($first['contextRef'] == $prevCntxt_d || $first['contextRef'] == $prevCntxt_i) {
                    if (isset($first['value'])  || $first['value'] == 0) {
                        $valuePrev = $first['value'];
                        $jsonData['prev'][$first['taxonomy_element']['name']] = $valuePrev;
                    }
                }

                $second = array_shift($elemento);

                if ($second['contextRef'] == $currentCntxt_d || $second['contextRef'] == $currentCntxt_i) {
                    if (isset($second['value'])  || $second['value'] == 0) {
                        $valueCurr = $second['value'];
                        $jsonData['current'][$second['taxonomy_element']['name']] = $valueCurr;
                    }
                } else {
                    if (isset($second['value']) || $second['value'] == 0) {
                        $valuePrev = $second['value'];
                        $jsonData['prev'][$second['taxonomy_element']['name']] = $valuePrev;
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

        return view('bilanci.recap')->with(['jsonData' => $jsonData, 'tipo_azienda' => $tipo_azienda, 'gradi' => $gradi, 'vociExt' => $vociExt, 'account_id' => $request->input('account_id')]);
    }



    /**
     * @return mixed
     */
    public function store(Request $request)
    {
        dd($request->all());
        $jsonData = array();
        $jsonDataPrev = array();
        $jsonDataAnag = array();

        foreach ($request->input() as $singlereq => $singleValue) {
            if (stripos($singlereq, 'curr')) {
                str_replace('Anagrafici', '', $singlereq);
                $jsonData[str_replace('_curr', '', $singlereq)] = $singleValue;
            } else if (stripos($singlereq, 'prev')) {
                $jsonDataPrev[str_replace('_prev', '', $singlereq)] = $singleValue;
            }
            if (stripos($singlereq, 'anag')) {
                $singlereq = str_replace('_curr', '', $singlereq);
                $jsonDataAnag[str_replace('_anag', '', $singlereq)] = $singleValue;
            }
        }

        $jsonDB = json_encode($jsonData);
        $jsonPrevDB = json_encode($jsonDataPrev);
        $jsonAnagDB = json_encode($jsonDataAnag);

        $accountId = $request->input('account_id');
        $currentYear = $request->input('currYear');
        $prevYear = $request->input('prevYear');

        $bilancio = Bilanci::create([
            'json_data' => $jsonDB,
            'account_id' => $accountId,
            'json_data_prev' => $jsonPrevDB,
            'json_data_anag' => $jsonAnagDB,
            'current_year' => $currentYear,
            'prev_year' => $prevYear,
            'year' => $currentYear,
        ]);



        return view('bilanci.store', $bilancio)->with(['jsonData' => $jsonData, 'jsonPrevDB' => $jsonPrevDB, 'jsonAnagDB' => $jsonAnagDB, 'currentYear' => $currentYear, 'prevYear' => $prevYear]);
    }


    /**
     * @param  User  $user
     *
     * @return mixed
     */
    public function show($id)
    {
        $bilancio = Bilanci::find($id);
        $attributes = $bilancio->getAttributes();

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

        $jsonData['current'] = json_decode($attributes['json_data'], true);
        $jsonData['prev'] = json_decode($attributes['json_data_prev'], true);
        $jsonData['anagrafic'] = json_decode($attributes['json_data_anag'], true);
        $jsonData['currentYear'] = $attributes['current_year'];
        $jsonData['prevYear'] = $attributes['prev_year'];
        return view('bilanci.show')->with(['jsonData' => $jsonData, 'vociExt' => $vociExt, 'gradi' => $gradi]);
    }

    public function findSons($name)
    {
        $leaf = array();
        $sons = Voci::where('voce_padre', $name)->get();
        if ($sons !== null) {
            foreach ($sons as $singleSon) {
                $leaf[$singleSon->name] = $this->findSons($singleSon->name);
            }
            return $leaf;
        } else {
            return $leaf;
        }
    }

    public function provvisorio(Request $request)
    {
        $voci = DB::table('vocis')->get();
        $gradi = array();
        foreach ($voci as $singleVoice) {
            if ($singleVoice->voce_padre == "") {
                $gradi[$singleVoice->name] = $this->findSons($singleVoice->name);
            }
        }

        $vociExt = array();

        foreach ($voci as $voce) {
            $vociExt[$voce->name] = $voce->extended_name;
        }

        $formaGiuridica = array("DITTA INDIVIDUALE", "SOCIETA A RESPONSABILITA LIMITATA SRL", "SOCIETA IN NOME COLLETTIVO SNC", "SOCIETA IN ACCOMANDITA SEMPLICE SAS", "SOCIETA PER AZIONI SPA");

        $bilanciHelper = new BilanciHelper;

        $tipiAziende = $bilanciHelper->getTipiAziende();

        $campiAnagrafici = array(
            "Dati Anagrafici Denominazione" => "DatiAnagraficiDenominazione",
            "Dati Anagrafici Partita Iva" => "DatiAnagraficiPartitaIva"
        );

        $campiAnnoPrecedente = array(
            'Valore della produzione' => 'TotaleValoreProduzione',
            'Acquisti materie prime, sussidiarie, di consumo e di merci' => 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci',
            'Spese per godimento di beni terzi' => 'CostiProduzioneGodimentoBeniTerzi',
            'Spese per prestazione di servizi' => 'CostiProduzioneServizi',
            'Costi del personale' => 'CostiProduzionePersonaleTotaleCostiPersonale',
            'Var. rim. materie prime, sussid., di consumo e merci ' => 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci',
            'Oneri diversi da gestione' => 'CostiProduzioneOneriDiversiGestione',
            'Ricavi delle vendite e delle prestazioni' => 'ValoreProduzioneRicaviVenditePrestazioni'
        );

        $request->session()->put('campiAnnoPrecedente', $campiAnnoPrecedente);
        $request->session()->put('tipiAziende', $tipiAziende);
        $request->session()->put('formaGiuridica', $formaGiuridica);
        $request->session()->put('campiAnagrafici', $campiAnagrafici);
        $request->session()->put('gradi', $gradi);
        $request->session()->put('vociExt', $vociExt);

        $required = Voci::where('required', 1)->get()->toArray();

        return view('bilanci.provvisorio')->with(
            [
                'campiAnnoPrecedente' => $campiAnnoPrecedente,
                'tipiAziende' => $tipiAziende,
                'formaGiuridica' => $formaGiuridica,
                "campiAnagrafici" => $campiAnagrafici,
                'gradi' => $gradi,
                'vociExt' => $vociExt,
                'required' => $required
            ]
        );
    }

    public function storeProvvisorio(Request $request)
    {
        $jsonData = array();
        $jsonDataPrev = array();
        $jsonDataAnag = array();
        $formaGiuridica = $request->input('forma_giuridica');
        $tipoAzienda = $request->input('tipo_azienda');
        $DebitiEsigibiliEntroEsercizioSuccessivo = array('curr' => 0);
        $DebitiEsigibiliOltreEsercizioSuccessivo = array('curr' => 0);

        $vociFiglieDebiti = Voci::where('voce_padre', 'Debiti')->get();
        $alerts = [];

        foreach ($vociFiglieDebiti as $index => $actualVoice) {
            $nipoti = Voci::where('voce_padre', $actualVoice->name)->get();
            foreach ($nipoti as $label => $singleNipote) {
                if (str_contains($singleNipote->name, 'entro 12 mesi')) {
                    $DebitiEsigibiliEntroEsercizioSuccessivo['curr'] += $request->all()[($singleNipote->extended_name . '_curr')];
                } else if (str_contains($singleNipote->name, 'oltre 12 mesi')) {
                    $DebitiEsigibiliOltreEsercizioSuccessivo['curr'] += $request->all()[($singleNipote->extended_name . '_curr')];
                }
            }
        }

        if ((float)$request->input('ignoreAlert') != 1) {
            if ($request->input('TotaleAttivo_curr' != 'TotalePassivo_curr')) {
                $alerts['Totale Attivo'] = 'TotaleAttivo';
                $alerts['Totale Passivo'] = 'TotalePassivo';
            }
            if ($request->input('PatrimonioNettoUtilePerditaEsercizio_curr') != $request->input('UtilePerditaEsercizio_curr')) {
                $alerts['Utile (perdita) dell esercizio (conto economico)'] = 'UtilePerditaEsercizio';
                $alerts['Utile (perdita) dell esercizio'] = 'PatrimonioNettoUtilePerditaEsercizio';
            }
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
            if (!empty($alerts)) {
                return 
                    view('bilanci.provvisorio')
                    ->with([
                        'fields' => $request->all(),
                        'alerts' => $alerts,
                        'sessionData' => $request->session()->all()
                    ]);
            }
        }

        foreach ($request->input() as $singlereq => $singleValue) {
            if (stripos($singlereq, 'curr')) {
                str_replace('Anagrafici', '', $singlereq);
                $jsonData[str_replace('_curr', '', $singlereq)] = $singleValue;
            } else if (stripos($singlereq, 'prev') && !(stripos($singlereq, 'Ateco'))) {
                $jsonDataPrev[str_replace('_prev', '', $singlereq)] = $singleValue;
            }
            if (stripos($singlereq, 'anag')) {
                $singlereq = str_replace('_curr', '', $singlereq);
                $jsonDataAnag[str_replace('_anag', '', $singlereq)] = $singleValue;
            }
        }

        $jsonData['DebitiEsigibiliEntroEsercizioSuccessivo'] = $DebitiEsigibiliEntroEsercizioSuccessivo['curr'];
        $jsonData['DebitiEsigibiliOltreEsercizioSuccessivo'] = $DebitiEsigibiliOltreEsercizioSuccessivo['curr'];


        $jsonDB = json_encode($jsonData);
        $jsonPrevDB = json_encode($jsonDataPrev);
        $jsonAnagDB = json_encode($jsonDataAnag);

        $accountId = $request->input('account_id');
        $period = $request->input('period_start') . ' ' . $request->input('period_end');

        $bilancio = Bilanci::create([
            'json_data' => $jsonDB,
            'account_id' => $accountId,
            'json_data_prev' => $jsonPrevDB,
            'json_data_anag' => $jsonAnagDB,
            'year' => $period,
            'provvisorio' => 1,
            'forma_giuridica' => $formaGiuridica,
            'tipo_azienda' => $tipoAzienda
        ]);

        return redirect()->route('admin.bilanci.bilancio.datatable');
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

        return redirect()->route('bilanci.index')->withFlashSuccess(__('The Bilancio was successfully deleted.'));
    }


    public function datatable()
    {
        $bilancis = Bilanci::where('provvisorio', null)->paginate(25);
        
        $bilanciProvvisori = Bilanci::where('provvisorio', 1)->paginate(25);

        $bilanciHelper = new BilanciHelper;

        $tipiAziende = $bilanciHelper->getTipiAziende();

        $formaGiuridica = array("DITTA INDIVIDUALE", "SOCIETA A RESPONSABILITA LIMITATA SRL", "SOCIETA IN NOME COLLETTIVO SNC", "SOCIETA IN ACCOMANDITA SEMPLICE SAS", "SOCIETA PER AZIONI SPA");

        return view('bilanci.datatable', compact('formaGiuridica', 'bilancis', 'tipiAziende', 'bilanciProvvisori'));
    }

    public function fileHome()
    {
        $bilancis = Bilanci::where('provvisorio', null)->paginate(25);
        
        $bilanciProvvisori = Bilanci::where('provvisorio', 1)->paginate(25);

        $bilanciHelper = new BilanciHelper;

        $tipiAziende = $bilanciHelper->getTipiAziende();

        $formaGiuridica = array("DITTA INDIVIDUALE", "SOCIETA A RESPONSABILITA LIMITATA SRL", "SOCIETA IN NOME COLLETTIVO SNC", "SOCIETA IN ACCOMANDITA SEMPLICE SAS", "SOCIETA PER AZIONI SPA");

        return view('cortesia.Home', compact('formaGiuridica', 'bilancis', 'tipiAziende', 'bilanciProvvisori'));
    }

    public function delete($id)
    {
        $bilancio = Bilanci::find($id);

        $bilancio->delete();
        return back();
    }

    public function showCurrent(Request $request)
    {
        $bilancioId = $request->input('bilancioId');
        $bilancio = Bilanci::where('id', $bilancioId)->get();
        $jsonData = $bilancio->jsonData;

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

        return view('bilanci.show')->with(['jsonData' => $jsonData, 'account_id' => 0]);
    }
}
