<?php

namespace App\Http\Controllers;

ini_set('max_input_vars', 5000);

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Bilanci;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use lyquidity\XPath2\Value\DateValue;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use XBRL\XBRL_Instance;
use App\Http\Requests;
use App;
use lyquidity\xml\QName;
use lyquidity\XPath2\XPath2Exception;
use function Livewire\str;

class AccountsController extends Controller
{

    public function bilancio($id){
        $account = Account::findOrFail($id);
        return view('accounts.importbilancio', compact('id', 'account'));
    }

    public function recapBilancio(Request $request)
    {
        global $use_xbrl_functions;
        $use_xbrl_functions = true;

        $jsonData = array();

        $file = $request->file()['xbrl'];
        $instance = null;

        $result = XBRL_Instance:: FromInstanceDocumentWithExtensionTaxonomy( $file->getPathName(),  __DIR__ . '/../../../taxonomies/2018-11-04/itcc-ci-2018-11-04.xsd', 'XBRL', $instance );

        $contexts = ($result->getContexts()->getContexts());
        $years = array();

        foreach ($contexts as $cont){
            $years[] = $cont['period']['startDate'];
            $years[] = $cont['period']['endDate'];
        }


        usort($years, function($a, $b) {
            return strtotime($a) - strtotime($b);
        });

        $years = array_values(array_unique($years));

        ksort($contexts);

        foreach ($contexts as $data=>$value){
            if ($value['period']['type'] == 'duration'){
                if($value['period']['startDate'] == $years[0] && $value['period']['endDate'] == $years[1]){
                    $prevCntxt_d = $data;
                }
                else if($value['period']['startDate'] == $years[2] && $value['period']['endDate'] == $years[3]){
                    $currentCntxt_d = $data;
                }
            }
            else if ($value['period']['type'] == 'instant'){
                if($value['period']['startDate'] == $years[1] && $value['period']['endDate'] == $years[1]){
                    $prevCntxt_i = $data;
                }
                else if($value['period']['startDate'] == $years[3] && $value['period']['endDate'] == $years[3]){
                    $currentCntxt_i = $data;
                }
            }
        }

        $date = array();

        foreach ($contexts as $data=>$val){
            $date[] = $val['period']['startDate'];
            $date[] = $val['period']['endDate'];
        }
        $ordDate = array_reverse(array_unique($date, SORT_STRING));
        sort($ordDate);

        $jsonData['prevYear'] = $years[0].' '.$years[1];
        $jsonData['currentYear'] = $years[2].' '.$years[3];

        $elements = $result->getElements();
        $elements = $elements->getElements();
        foreach($elements as $key=>$elemento) {

            $chiave = array_keys($elemento);

            if (count($elemento)==2){
                $current = array_shift($elemento);
                $prev = array_shift($elemento);

                $valuePrev = 0;
                $valueCurr = 0;

                if(isset($current['value'])) {
                    $valueCurr = $current['value'];
                }

                if(isset($prev['value'])) {
                    $valuePrev = $prev['value'];
                }

                $jsonData['current'][$current['taxonomy_element']['name']] = $valueCurr;
                $jsonData['prev'][$prev['taxonomy_element']['name']] = $valuePrev;
            }

            if (count($elemento)==1){
                $data = array_shift($elemento);
                if (str_contains($data['contextRef'], $currentCntxt_d) || str_contains($data['contextRef'], $currentCntxt_i) || $data['contextRef'] === $currentCntxt_i || $data['contextRef'] === $currentCntxt_d){
                    $value = 0;
                    if(isset($data['value'])) {
                        $value = $data['value'];
                    }
                    if (is_numeric($value) && !(str_contains($data['taxonomy_element']['name'], 'Anagrafic'))){
                        $jsonData['singleCurrent'][$data['taxonomy_element']['name']] = $value;
                    }
                    else if(str_contains($data['taxonomy_element']['name'], 'Anagrafic')){
                        $jsonData['anagrafic'][$data['taxonomy_element']['name']] = $value;
                    }
                }

                if (str_contains($data['contextRef'], $prevCntxt_d) || str_contains($data['contextRef'], $prevCntxt_i) || $data['contextRef'] === $prevCntxt_i || $data['contextRef'] === $prevCntxt_d){

                    $value = 0;
                    if(isset($data['value'])) {
                        $value = $data['value'];
                    }
                    if (is_numeric($value) && !(str_contains($data['taxonomy_element']['name'], 'Anagrafic'))){
                        $jsonData['singlePrev'][$data['taxonomy_element']['name']] = $value;
                    }
                    else if(str_contains($data['taxonomy_element']['name'], 'Anagrafic')){
                        $jsonData['anagrafic'][$data['taxonomy_element']['name']] = $value;
                    }
                }
            }
        }

        $voci = DB::table('vocis')->get();
        $gradi = array();

        foreach ($voci as $voce){
            if ($voce->voce_padre == null || $voce->voce_padre == ""){
                $h1[$voce->name] = $voce->extended_name;
                $gradi[$voce->name] = [];
            }
            else {
                foreach ($voci as $voci1) {
                    if ($voce->voce_padre == $voci1->name) {
                        if ($voci1->voce_padre == null || $voci1->voce_padre == ""){
                            $gradi[$voci1->name][$voce->name] = [];
                        }
                        else{
                            foreach ($voci as $voci2) {
                                if ($voci1->voce_padre == $voci2->name) {
                                    if ($voci2->voce_padre == null || $voci2->voce_padre == "") {
                                        $gradi[$voci2->name][$voci1->name][$voce->name] = [];
                                    }
                                    else{
                                        foreach ($voci as $voci3) {
                                            if ($voci2->voce_padre == $voci3->name) {
                                                if ($voci3->voce_padre == null || $voci3->voce_padre == "") {
                                                    $gradi[$voci3->name][$voci2->name][$voci1->name][$voce->name] = [];
                                                }
                                                else{
                                                    foreach ($voci as $voci4) {
                                                        if ($voci3->voce_padre == $voci4->name) {
                                                            if ($voci4->voce_padre == null || $voci4->voce_padre == "") {
                                                                $gradi[$voci4->name][$voci3->name][$voci2->name][$voci1->name][$voce->name] = [];
                                                            }
                                                            else{
                                                                foreach ($voci as $voci5) {
                                                                    if ($voci4->voce_padre == $voci5->name) {
                                                                        if ($voci5->voce_padre == null || $voci5->voce_padre == "") {
                                                                            $gradi[$voci4->name][$voci4->name][$voci3->name][$voci2->name][$voci1->name][$voce->name] = [];
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

        $vociExt = array();

        foreach ($voci as $voce){
            $vociExt[$voce->name] = $voce->extended_name;
        }

        $jsonData['Tipo bilancio'] = $request->input('tipobilancio');

        return view('accounts.recapbilancio')->with(['jsonData' => $jsonData, 'account_id' => $request->input('account_id'), 'gradi'=>$gradi, 'vociExt'=>$vociExt]);
    }

    public function storebilancio(Request $request){
        $jsonData = array();
        $jsonDataPrev = array();
        $jsonDataAnag = array();

        foreach($request->input() as $singlereq => $singleValue) {
            if (stripos($singlereq, 'curr')){
                str_replace('Anagrafici', '', $singlereq);
                $jsonData[str_replace('_curr', '', $singlereq)] = $singleValue;
            }
            else if(stripos($singlereq, 'prev') && !(stripos($singlereq, 'Anagrafic'))){
                $jsonDataPrev[str_replace('_prev', '', $singlereq)] = $singleValue;
            }
            if (stripos($singlereq, 'anag') || stripos($singlereq, 'Anagrafic')){
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
            'prev_year' => $prevYear
        ]);

        return $this->index();
    }

    public function cr($id){
        $account = Account::findOrFail($id);
        return view('accounts.importcr', compact('id', 'account'));
    }

    public function recapCentraleRischi(Request $request){
        $accountId = $request->input('account_id');
        $recordId = false;
        $extension = 'pdf';
        $filename = rand(11111111, 99999999). '.' . $extension;
        $request->file('centrale_rischi')->move(
            base_path() . '/public/pdfcr/', $filename
        );
        $filePath = base_path() . '/public/pdfcr/'.$filename;
        $process = new Process(['python', 'crExtractor.py', $filePath, $accountId]);

        $process->setTimeout(20000);

        try {
            $process->run();
            if(!$process->isSuccessful()){
                throw new ProcessFailedException($process);
            }
        }
        catch(ProcessFailedException $e) {
            dd($e);
        }

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        } else {
            $recordId = $process->getOutput();
        }

        if($recordId) {
            return Redirect::route('admin.accounts.account.crshow', array('recordId' => $recordId, 'accountId'=>$accountId));

        }
    }

    public function showCR(){
        $id = $_GET['id'];
        $cr = DB::table('centralerischi')->where('id', $id)->get();
        $anno = $cr[0]->anno;
        $mese = $cr[0]->mese;

    $crData = array();
    $currentMonth = false;
    $currentYear = false;
    $currentIntermediario = 'false';
    $currentCreditType = false;
    $currentCategoryArray = false;
    $currentGarante = false;

    $jsonArray[$anno][$mese] = json_decode($cr[0]->jsonData, true);

//    dd($jsonArray);

    $cleanCR = $jsonArray;
    $tensioniAnnuali = array();
    $sofferenze = array();
    $soldini = array();
    $sconfini = array();
    $yearlyDivision = array();
    $sconfiniPerAnniBanche= array();
    $tensioniLineCtredito = array();
    foreach($cleanCR as $singleYearKey => $months) {
        foreach($months as $singleMonthKey => $banks) {
            foreach($banks as $bankKey => $creditRow) {
                foreach($creditRow as $singleCreditRowTypeKey => $singleCreditValue) {
                    if($singleCreditRowTypeKey == 'Cassa') {
                        foreach ($singleCreditValue as $credit) {
                            $TipoGaranzia = $credit['Tipo Garanzia'];
                            $TipoAttività = false;
                            $Categoria = $credit['Categoria'];
                            $Accordato = $credit['Accordato'];
                            $Utilizzato = $credit['Utilizzato'];
                            $AccordatoOperativo = $credit['Accordato
Operativo'];
                            $SaldoMedio = $credit['Saldo Medio'];
                            $ImportoGarantito = $credit['Importo
Garantito'];
                            $TipoGaranzia = preg_replace("/\n/", " ", $TipoGaranzia);

                            if (str_contains($TipoGaranzia, 'Assenza') && str_contains($TipoGaranzia, 'garanzie') && str_contains($TipoGaranzia, 'e/o') ){
                                $TipoGaranzia = "Assenza di garanzie reali e/o privilegi" ;
                            }

                            if (str_contains($Categoria, 'SCADENZA')){
                                if(isset($credit['Tipo Attività'])) {
                                    $TipoAttività = $credit['Tipo Attività'];
                                }

                            }

                            $currentRow = $Categoria.' '.$Accordato.' '.$AccordatoOperativo.' '.$TipoGaranzia;

                            $currentRow = str_contains($Categoria, 'SCADENZA') ? $currentRow.' '.$TipoAttività : $currentRow ;

                            $isSameRow = false;

                            if (isset($prevRow)){
                                if ($currentRow == $prevRow){
                                    $isSameRow = true;
                                }
                            }

                            if ($Accordato != '') {

                                if (!array_key_exists($singleYearKey, $sconfiniPerAnniBanche)) {
                                    $sconfiniPerAnniBanche[$singleYearKey] = array();
                                }

                                if (!array_key_exists($bankKey, $sconfiniPerAnniBanche[$singleYearKey])) {
                                    $sconfiniPerAnniBanche[$singleYearKey][$bankKey] = array();
                                }
                                if (!array_key_exists($Categoria, $sconfiniPerAnniBanche[$singleYearKey][$bankKey])) {
                                    $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria] = array();
                                }

                                if (!array_key_exists($currentRow, $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria])) {
                                    $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow] = array();
                                }

                            }

                            if ($Utilizzato > $AccordatoOperativo) {
                                // PER BANCHE ANNI

                                if (!array_key_exists('TotaleSconfini', $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow])) {

                                    $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TotaleSconfini'] = 1;
                                    $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TensioneLineaCredito'] = false;

                                }

                                else {

//                                      //SE NON è SETTATA LA SETTO A 1, SE è SETTATA LA INCREMENTO DI 1

                                    if ($sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TotaleSconfini'] < 3) {

                                        //SE NON SIAMO IN SITUAZIONE DI TENSIONE

                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TensioneLineaCredito'] = false;

                                    }

                                    else {

                                        //SE SIAMO IN TENSIONE, DICIAMO PER QUALE ANNO E LINEA DI CREDITO

                                        if (!array_key_exists($singleYearKey, $tensioniLineCtredito)) {

                                            $tensioniLineCtredito[$singleYearKey] = array();

                                        }

                                        $tensioniLineCtredito[$singleYearKey][$Categoria][$currentRow] = true;


                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TensioneLineaCredito'] = true;

                                    }

                                    $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TotaleSconfini'] = ($sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TotaleSconfini'] + 1);
                                    if($sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TotaleSconfini'] >= 3){
                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TensioneLineaCredito'] = true;
                                    }
                                }

                            }

                            else{

                                if (!array_key_exists('TotaleSconfini', $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow])) {

                                    $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TotaleSconfini'] = 0;
                                    $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TensioneLineaCredito'] = false;

                                }

                                if (!($sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TotaleSconfini'] >= 3)) {

                                    //SE NON SIAMO IN SITUAZIONE DI TENSIONE

                                    $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TensioneLineaCredito'] = false;

                                }

                                else{

                                    if (!array_key_exists($singleYearKey, $tensioniLineCtredito)) {

                                        $tensioniLineCtredito[$singleYearKey] = array();

                                    }

                                    $tensioniLineCtredito[$singleYearKey][$Categoria][$currentRow] = true;


                                    $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TensioneLineaCredito'] = true;

                                }

                            }

                            //SE NON HO SCONFINATO
                            if (str_contains($Categoria, 'SCADENZA')){
                                if(isset($credit['Tipo Attività'])) {
                                    $TipoAttività = $credit['Tipo Attività'];
                                }
                            }
                            $prevRow = $Categoria.' '.$Accordato.' '.$AccordatoOperativo.' '.$TipoGaranzia;

                            $prevRow = str_contains($Categoria, 'SCADENZA') ? $currentRow.' '.$TipoAttività : $currentRow ;

                            if (!$isSameRow){
                                $soldini[$singleYearKey][$bankKey][$currentRow]['Accordato Operativo'] = str_replace('.', '',$AccordatoOperativo);
                                $soldini[$singleYearKey][$bankKey][$currentRow]['Utilizzato'] = str_replace('.', '',$Utilizzato);
                            }
                        }
                    }
                }
            }
        }
    }

    foreach ($soldini as $anno => $banca){
        foreach ($banca as $nomeBanca=>$val){
            foreach ($val as $data=>$value){
                if (!isset($soldini[$anno][$nomeBanca]['Totale Accordato Operativo'])){
                    $soldini[$anno][$nomeBanca]['Totale Accordato Operativo'] = 0;
                    $soldini[$anno][$nomeBanca]['Totale Utilizzato'] = 0;
                }
                $soldini[$anno][$nomeBanca]['Totale Accordato Operativo'] += $value['Accordato Operativo'];
                $soldini[$anno][$nomeBanca]['Totale Utilizzato'] += $value['Utilizzato'];
            }
        }
    }

    $sconfinoAnnuale = false;
    $arraySconfiniAnnuali = array();
    $conteggioSconfini = 0;

    foreach ($sconfiniPerAnniBanche as $anno => $arrayBanche){
        foreach ($arrayBanche as $nomeBanca=>$arrayRischi){
            foreach ($arrayRischi as $risk=> $righe) {
                foreach ($righe as $row=>$tensione){
                    if ($tensione['TotaleSconfini'] > 0){
                        $sconfinoAnnuale = true;
                    }
                }
                if ($sconfinoAnnuale){
                    $arraySconfiniAnnuali[$anno][$risk] = true;
                    $sconfinoAnnuale = false;
                }
            }
        }
    }

    $sconfiniGeneral = array();
    foreach($sconfiniPerAnniBanche as $singleYear => $banks) {
        foreach ($banks as $singleBank => $riskType) {
            foreach ($riskType as $singleRisk => $riskRow) {
                foreach ($riskRow as $singleRiskRow => $riskData) {
                    $sconfiniGeneral[$singleYear][$singleBank][$singleRisk]['TotaleSconfini'] = 0;

                }
            }
        }
    }
    $tensioniLine = array();
    foreach($sconfiniPerAnniBanche as $singleYear => $banks) {
        foreach ($banks as $singleBank => $riskType) {
            foreach ($riskType as $singleRisk => $riskRow) {
                foreach ($riskRow as $singleRiskRow => $riskData) {
                    $sconfiniGeneral[$singleYear][$singleBank][$singleRisk]['TotaleSconfini'] = $sconfiniGeneral[$singleYear][$singleBank][$singleRisk]['TotaleSconfini']+$riskData['TotaleSconfini'];
                    if($riskData['TensioneLineaCredito']) {
                        $tensioniLine[$singleYear][$singleRisk] = true;
                    }


                }
            }
        }
    }

    return view('accounts.showCR', compact('sconfiniGeneral', 'cleanCR', 'soldini', 'tensioniLineCtredito','arraySconfiniAnnuali', 'tensioniLine'));
}



    /**
     * Display a listing of the accounts.
     *
     * @return Illuminate\View\View
     */
    public function index()
    {
        $accounts = Account::paginate(25);

        return view('accounts.index', compact('accounts'));
    }

    /**
     * Show the form for creating a new account.
     *
     * @return Illuminate\View\View
     */
    public function create()
    {


        return view('accounts.create');
    }

    /**
     * Store a new account in the storage.
     *
     * @param Illuminate\Http\Request $request
     *
     * @return Illuminate\Http\RedirectResponse | Illuminate\Routing\Redirector
     */
    public function store(Request $request)
    {
        try {

            $data = $this->getData($request);

            Account::create($data);

            return redirect()->route('admin.accounts.account.index')
                ->with('success_message', 'Account was successfully added.');
        } catch (Exception $exception) {

            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }
    }

    public function showAzienda($id){

        $account = Account::findOrFail($id)->get();
        $bilanci = Bilanci::where('account_id' , '=' , $id)->get();
        $centralirischi = DB::table('centralerischi')
            ->where('account_id','=',$id)
            ->get();

        foreach ($centralirischi as $centrale=>$rischi){
            $cleanCR[$rischi->anno][$rischi->mese] = $rischi->id;
        }

        return view('accounts.show', compact('bilanci', 'cleanCR', 'account'));

    }

    /**
     * Display the specified account.
     *
     * @param int $id
     *
     * @return Illuminate\View\View
     */
    public function show($id)
    {
        $account = Account::findOrFail($id);

        $bilanci = DB::table('bilanci')
            ->where('account_id','=',$id)
            ->get();

        dd($bilanci);

        $crData = array();
        $currentMonth = false;
        $currentYear = false;
        $currentIntermediario = 'false';
        $currentCreditType = false;
        $currentCategoryArray = false;
        $currentGarante = false;
        $recordId = $id;
        $jsonData = DB::table('cr_json_export')->where('jobNumber','=',356750)->get(['jsonData']);
        $jsonData2 = DB::table('centralerischi')->where('account_id','=',$id)->get(['jsonData','anno','mese']);

        foreach ($jsonData2 as $data=>$value){
            $jsonArray[$value->anno][$value->mese] = json_decode($value->jsonData);
        }

        $support = [];

        foreach ($jsonArray as $anno => $arrayMese){
            foreach ($arrayMese as $mese => $arrayInterni){
                foreach ($arrayInterni as $data => $value){
                    $support[$anno][$mese][] = (array)$value;
                }
            }
        }

//        dd($support);
//
//        foreach ($support as $anno => $arrayMese){
//            foreach ($arrayMese as $mese => $arrayInterni){
//                foreach ($arrayInterni as $data => $indexArray){
//                    foreach ($indexArray as $key=>$value){
//                        if (str_contains($value, 'Intermediario')){
////                            foreach ($indexArray as $chiave=>$valore){
////                                if (!(str_contains($valore, 'Intermediario'))  && $valore != ""){
////                                    $CRpulita[$anno][$mese]['Intermediario'] = $valore;
////                                }
//                            }
//                        }
//                    }
//                }
//            }
//        }
//        dd($CRpulita);

        foreach($jsonArray as $singleJsonArray) {

            foreach ($singleJsonArray as $key => $value) {
                $cleanRow = (object)array_filter((array)$value, 'strlen');


                foreach ($cleanRow as $singleRow) {

                    if (strpos($singleRow, 'DATA DI RIFERIMENTO:') !== false) {
                        $date = explode(' ', next($cleanRow));
                        $month = $date[0];
                        $year = $date[1];
                        if (!$currentYear || $year != $currentYear) {
                            $currentYear = $year;
                            if (!array_key_exists($year, $crData)) {
                                $crData[$currentYear] = array();
                            }
                        }

                        if (!$currentMonth || $month != $currentMonth) {
                            $currentMonth = $month;
                            if (!array_key_exists($month, $crData[$currentYear])) {
                                $crData[$currentYear][$currentMonth] = array();

                            }
                        }

                    }

                    if ($singleRow == 'Intermediario:') {
                        $intermed = next($cleanRow);
                        if (!$currentIntermediario || $intermed != $currentIntermediario) {
                            $currentIntermediario = $intermed;
                            if(!array_key_exists($currentMonth, $crData[$currentYear])) {
                                $crData[$currentYear][$currentMonth] = array();
                                if (!array_key_exists($currentIntermediario, $crData[$currentYear][$currentMonth])) {
                                    $crData[$currentYear][$currentMonth][$currentIntermediario] = array();
                                }
                            }
                        }
                    }


                    if (strpos($singleRow, 'Crediti per cassa') !== false) {
                        if (!$currentCreditType || $currentCreditType != 'Cassa') {
                            $currentCreditType = 'Cassa';
                            if (!array_key_exists($currentIntermediario, $crData[$currentYear][$currentMonth])) {
                                $crData[$currentYear][$currentMonth][$currentIntermediario] = array();
                                if (!array_key_exists($currentCreditType, $crData[$currentYear][$currentMonth][$currentIntermediario])) {
                                    $crData[$currentYear][$currentMonth][$currentIntermediario][$currentCreditType] = array();
                                }
                            }
                        }
                    }
                    if (strpos($singleRow, 'Crediti di firma') !== false) {
                        if (!$currentCreditType || $currentCreditType != 'Firma') {
                            $currentCreditType = 'Firma';
                            if (!array_key_exists($currentIntermediario, $crData[$currentYear][$currentMonth])) {
                                $crData[$currentYear][$currentMonth][$currentIntermediario] = array();
                                if (!array_key_exists($currentCreditType, $crData[$currentYear][$currentMonth][$currentIntermediario])) {
                                    $crData[$currentYear][$currentMonth][$currentIntermediario][$currentCreditType] = array();
                                }
                            }
                        }
                    }

                    if (strpos($singleRow, 'Sezione informativa') !== false) {
                        if (!$currentCreditType || $currentCreditType != 'Informativa') {
                            $currentCreditType = 'Informativa';
                            if (!array_key_exists($currentIntermediario, $crData[$currentYear][$currentMonth])) {
                                $crData[$currentYear][$currentMonth][$currentIntermediario] = array();
                                if (!array_key_exists($currentCreditType, $crData[$currentYear][$currentMonth][$currentIntermediario])) {
                                    $crData[$currentYear][$currentMonth][$currentIntermediario][$currentCreditType] = array();
                                }
                            }
                        }
                    }
                    if (strpos($singleRow, 'Sofferenze') !== false) {
                        if (!$currentCreditType || $currentCreditType != 'Sofferenze') {
                            $currentCreditType = 'Sofferenze';
                            if (!array_key_exists($currentIntermediario, $crData[$currentYear][$currentMonth])) {
                                $crData[$currentYear][$currentMonth][$currentIntermediario] = array();
                                if (!array_key_exists($currentCreditType, $crData[$currentYear][$currentMonth][$currentIntermediario])) {
                                    $crData[$currentYear][$currentMonth][$currentIntermediario][$currentCreditType] = array();
                                }
                            }
                        }
                    }


                    if (strpos($singleRow, 'Categoria') !== false) {
                        $categoriaArray = array();
                        for ($i = 0; $i <= count((array)$cleanRow); $i++) {

                            $categoriaArray[next($cleanRow)] = array();
                        }
                        $currentCategoryArray = $categoriaArray;

                    }

                    if ((strpos($singleRow, 'RISCHI A') !== false && strlen($singleRow) < 20) || strpos($singleRow, 'GARANZIE CONNESSE') !== false || strpos($singleRow, 'SOFFERENZE') !== false || strpos($singleRow, 'CREDITI') !== false) {
                        foreach ($currentCategoryArray as $arrKey => $arrValue) {
                            if ($arrKey == 'Categoria') {
                                $currentCategoryArray['Categoria'] = $singleRow;
                            } else {
                                $currentCategoryArray[$arrKey] = next($cleanRow);
                            }
                        }
                        $crData[$currentYear][$currentMonth][$currentIntermediario][$currentCreditType][] = $currentCategoryArray;
                    }


                    if (strpos($singleRow, 'codice censito') !== false) {
                        if (!$currentGarante || $currentGarante != $singleRow) {
                            $currentGarante = $singleRow;
                        }
                    }


                    if (strpos($singleRow, 'Valore Garanzia') !== false && strlen($singleRow) < 50) {
                        $valoreGaranzia = str_replace('Valore Garanzia', '', $singleRow);
                        $crData[$currentYear][$currentMonth][$currentIntermediario]['Garanti'][$currentGarante]['Valore Garanzie'] = trim($valoreGaranzia);
                    }


                    if (strpos($singleRow, 'Importo Garantito') !== false) {
                        $valoreGaranzia = str_replace('Importo Garantito', '', $singleRow);
                        $crData[$currentYear][$currentMonth][$currentIntermediario]['Garanti'][$currentGarante]['Importo Garantito'] = trim($valoreGaranzia);
                    }


                }



            }
        }

        $cleanCR = $crData;
//        dd($cleanCR);
        $tensioniAnnuali = array();
        $sofferenze = array();
        $soldini = array();
        $sconfini = array();
        $yearlyDivision = array();
        $sconfiniPerAnniBanche= array();
        $tensioniLineCtredito = array();
        foreach($cleanCR as $singleYearKey => $months) {
            foreach($months as $singleMonthKey => $banks) {
                foreach($banks as $bankKey => $creditRow) {
                    foreach($creditRow as $singleCreditRowTypeKey => $singleCreditValue) {
                        if($singleCreditRowTypeKey == 'Cassa') {
                            foreach ($singleCreditValue as $credit) {
//                                dd($singleYearKey);

//                                dd($credit);
                                $TipoGaranzia = $credit['Tipo Garanzia'];
                                $TipoAttività = false;
                                $Categoria = $credit['Categoria'];
                                $Accordato = $credit['Accordato'];
                                $Utilizzato = $credit['Utilizzato'];
                                $AccordatoOperativo = $credit['Accordato
Operativo'];
                                $SaldoMedio = $credit['Saldo Medio'];
                                $ImportoGarantito = $credit['Importo
Garantito'];
                                $TipoGaranzia = preg_replace("/\n/", " ", $TipoGaranzia);

                                if (str_contains($TipoGaranzia, 'Assenza') && str_contains($TipoGaranzia, 'garanzie') && str_contains($TipoGaranzia, 'e/o') ){
                                    $TipoGaranzia = "Assenza di garanzie reali e/o privilegi" ;
                                }
//                                dd(preg_replace("/\n/", " ", $TipoGaranzia));

                                if (str_contains($Categoria, 'SCADENZA')){
                                    if(isset($credit['Tipo Attività'])) {
                                        $TipoAttività = $credit['Tipo Attività'];
                                    }

                                }

                                $currentRow = $Categoria.' '.$Accordato.' '.$AccordatoOperativo.' '.$TipoGaranzia;

                                $currentRow = str_contains($Categoria, 'SCADENZA') ? $currentRow.' '.$TipoAttività : $currentRow ;

                                $isSameRow = false;

                                if (isset($prevRow)){
                                    if ($currentRow == $prevRow){
                                        $isSameRow = true;
                                    }
                                }

                                if ($Accordato != '') {

                                    if (!array_key_exists($singleYearKey, $sconfiniPerAnniBanche)) {
                                        $sconfiniPerAnniBanche[$singleYearKey] = array();
                                    }

                                    if (!array_key_exists($bankKey, $sconfiniPerAnniBanche[$singleYearKey])) {
                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey] = array();
                                    }
                                    if (!array_key_exists($Categoria, $sconfiniPerAnniBanche[$singleYearKey][$bankKey])) {
                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria] = array();
                                    }

                                    if (!array_key_exists($currentRow, $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria])) {
                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow] = array();
                                    }

                                }

                                if ($Utilizzato > $AccordatoOperativo) {
                                    // PER BANCHE ANNI

                                    if (!array_key_exists('TotaleSconfini', $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow])) {

                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TotaleSconfini'] = 1;
                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TensioneLineaCredito'] = false;

                                    }

                                    else {

//                                      //SE NON è SETTATA LA SETTO A 1, SE è SETTATA LA INCREMENTO DI 1

                                        if ($sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TotaleSconfini'] < 3) {

                                            //SE NON SIAMO IN SITUAZIONE DI TENSIONE

                                            $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TensioneLineaCredito'] = false;

                                        }

                                        else {

                                            //SE SIAMO IN TENSIONE, DICIAMO PER QUALE ANNO E LINEA DI CREDITO

                                            if (!array_key_exists($singleYearKey, $tensioniLineCtredito)) {

                                                $tensioniLineCtredito[$singleYearKey] = array();

                                            }

                                            $tensioniLineCtredito[$singleYearKey][$Categoria][$currentRow] = true;


                                            $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TensioneLineaCredito'] = true;

                                        }

                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TotaleSconfini'] = ($sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TotaleSconfini'] + 1);
                                        if($sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TotaleSconfini'] >= 3){
                                            $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TensioneLineaCredito'] = true;
                                        }
                                    }

                                }

                                else{

                                    if (!array_key_exists('TotaleSconfini', $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow])) {

                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TotaleSconfini'] = 0;
                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TensioneLineaCredito'] = false;

                                    }

                                    if (!($sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TotaleSconfini'] >= 3)) {

                                        //SE NON SIAMO IN SITUAZIONE DI TENSIONE

                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TensioneLineaCredito'] = false;

                                    }

                                    else{

                                        if (!array_key_exists($singleYearKey, $tensioniLineCtredito)) {

                                            $tensioniLineCtredito[$singleYearKey] = array();

                                        }

                                        $tensioniLineCtredito[$singleYearKey][$Categoria][$currentRow] = true;


                                        $sconfiniPerAnniBanche[$singleYearKey][$bankKey][$Categoria][$currentRow]['TensioneLineaCredito'] = true;

                                    }

                                }

                                //SE NON HO SCONFINATO
                                if (str_contains($Categoria, 'SCADENZA')){
                                    if(isset($credit['Tipo Attività'])) {
                                        $TipoAttività = $credit['Tipo Attività'];
                                    }
                                }
                                $prevRow = $Categoria.' '.$Accordato.' '.$AccordatoOperativo.' '.$TipoGaranzia;

                                $prevRow = str_contains($Categoria, 'SCADENZA') ? $currentRow.' '.$TipoAttività : $currentRow ;

                                if (!$isSameRow){
                                    $soldini[$singleYearKey][$bankKey][$currentRow]['Accordato Operativo'] = str_replace('.', '',$AccordatoOperativo);
                                    $soldini[$singleYearKey][$bankKey][$currentRow]['Utilizzato'] = str_replace('.', '',$Utilizzato);
                                }
                            }
                        }
                    }
                }
            }
        }


        foreach ($soldini as $anno => $banca){
            foreach ($banca as $nomeBanca=>$val){
                foreach ($val as $data=>$value){
                    if (!isset($soldini[$anno][$nomeBanca]['Totale Accordato Operativo'])){
                        $soldini[$anno][$nomeBanca]['Totale Accordato Operativo'] = 0;
                        $soldini[$anno][$nomeBanca]['Totale Utilizzato'] = 0;
                    }
                    $soldini[$anno][$nomeBanca]['Totale Accordato Operativo'] += $value['Accordato Operativo'];
                    $soldini[$anno][$nomeBanca]['Totale Utilizzato'] += $value['Utilizzato'];
                }
            }
        }


        $sconfinoAnnuale = false;
        $arraySconfiniAnnuali = array();
        $conteggioSconfini = 0;

        foreach ($sconfiniPerAnniBanche as $anno => $arrayBanche){
            foreach ($arrayBanche as $nomeBanca=>$arrayRischi){
                foreach ($arrayRischi as $risk=> $righe) {
                    foreach ($righe as $row=>$tensione){
                        if ($tensione['TotaleSconfini'] > 0){
                            $sconfinoAnnuale = true;
                        }
                    }
                    if ($sconfinoAnnuale){
                        $arraySconfiniAnnuali[$anno][$risk] = true;
                        $sconfinoAnnuale = false;
                    }
                }
            }
        }

        $sconfiniGeneral = array();
        foreach($sconfiniPerAnniBanche as $singleYear => $banks) {
            foreach ($banks as $singleBank => $riskType) {
                foreach ($riskType as $singleRisk => $riskRow) {
                    foreach ($riskRow as $singleRiskRow => $riskData) {
                        $sconfiniGeneral[$singleYear][$singleBank][$singleRisk]['TotaleSconfini'] = 0;

                    }
                }
            }
        }
        $tensioniLine = array();
        foreach($sconfiniPerAnniBanche as $singleYear => $banks) {
            foreach ($banks as $singleBank => $riskType) {
                foreach ($riskType as $singleRisk => $riskRow) {
                    foreach ($riskRow as $singleRiskRow => $riskData) {
                        $sconfiniGeneral[$singleYear][$singleBank][$singleRisk]['TotaleSconfini'] = $sconfiniGeneral[$singleYear][$singleBank][$singleRisk]['TotaleSconfini']+$riskData['TotaleSconfini'];
                        if($riskData['TensioneLineaCredito']) {
                            $tensioniLine[$singleYear][$singleRisk] = true;
                        }


                    }
                }
            }
        }

        dd($cleanCR);
        return view('accounts.show', compact('sconfiniGeneral', 'cleanCR', 'soldini', 'tensioniLineCtredito','arraySconfiniAnnuali', 'tensioniLine', 'bilanci'));

    }

    /**
     * Show the form for editing the specified account.
     *
     * @param int $id
     *
     * @return Illuminate\View\View
     */
    public function edit($id)
    {
        $account = Account::findOrFail($id);


        return view('accounts.edit', compact('account'));
    }

    /**
     * Update the specified account in the storage.
     *
     * @param int $id
     * @param Illuminate\Http\Request $request
     *
     * @return Illuminate\Http\RedirectResponse | Illuminate\Routing\Redirector
     */
    public function update($id, Request $request)
    {
        try {

            $data = $this->getData($request);

            $account = Account::findOrFail($id);
            $account->update($data);

            return redirect()->route('admin.accounts.account.index')
                ->with('success_message', 'Account was successfully updated.');
        } catch (Exception $exception) {

            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }
    }

    /**
     * Remove the specified account from the storage.
     *
     * @param int $id
     *
     * @return Illuminate\Http\RedirectResponse | Illuminate\Routing\Redirector
     */
    public function destroy($id)
    {
        try {
            $account = Account::findOrFail($id);
            $account->delete();

            return redirect()->route('admin.accounts.account.index')
                ->with('success_message', 'Account was successfully deleted.');
        } catch (Exception $exception) {

            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }
    }


    /**
     * Get the request's data from the request.
     *
     * @param Illuminate\Http\Request\Request $request
     * @return array
     */
    protected function getData(Request $request)
    {
        $rules = [
                'Name' => 'required|string|min:1|max:255',
        ];


        $data = $request->validate($rules);




        return $data;
    }

}
