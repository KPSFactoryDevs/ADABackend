<?php

namespace App\Http\Controllers;

ini_set('max_input_vars', 5000);

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
	
	public function getDocuments() 
	{
		$documentsCr = Document::all();
		//$documentsBilanci = Document::where('type', 'bilancio')->get();
		
		foreach($documentsCr as $singleDocument) {
			$status = $singleDocument->status;
			$type = $singleDocument->type;
			
			$singleDocument['nome_azienda'] = 'Azienda Test';
			
			if($status == 'da_elaborare') {
				$status = 'Da Elaborare';
				$singleDocument['status'] = $status;
			} else if($status == 'completed') {
				$status = 'Completato';
				$singleDocument['status'] = $status;
			} else {
				$status = 'Elaborato';
				$singleDocument['status'] = $status;
			}
			
			if($type == 'centrale rischi') {
				$type = 'Centrale Rischi';
				
				$singleDocument['type'] = $type;
			}
			
			if($type == 'bilancio') {
				$type = 'Bilancio';

				$singleDocument['type'] = $type;
			}
			
			
			
		/*	foreach($documentsBilanci as $documentBilancio) {
				$status = $documentBilancio->status;
				$type = $documentBilancio->type;
				
				$documentBilancio['nome_azienda'] = 'azienda test';

				if($status == 'da_elaborare') {
					$status = 'Da Elaborare';
					$documentBilancio['status'] = $status;
				} else if($status == 'completed') {
					$status = 'Completato';
					$documntBilancio['status'] = $status;
				} else {
					$status = 'Elaborato';
					$documentBilancio['status'] = $status;
				}

				if($type == 'bilancio') {
					$type = 'Bilancio';

					$documentBilancio['type'] = $type;
				}
			} */
			

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


    public function recap(Request $request)
    {
        // query che recupera 1 sola centrale rischi in status da elaborare
        // passiamo il filepath al python che elabora ed importa i dati.
        $crFileToElaborate = Document::where('status', 'da_elaborare')->first();
        $filepath = $crFileToElaborate->path;

        // $filepath = 'storage/path/to/file/test.pdf';
        $process = new Process(['python3', 'crExtractor.py', $filepath]);

        $process->setTimeout(10000);

        try {
            $process->run();
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        } catch (ProcessFailedException $e) {
            dd($e);
        }

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        } else {


            $jsonData = $process->getOutput();

            $jsonArray = json_decode($jsonData);


            $crData = array();
            $currentMonth = false;
            $currentYear = false;
            $currentIntermediario = 'false';
            $currentCreditType = false;

            $currentGarante = false;
            $isLeggenda = false;
            $isInframensile = false;
            $isSegnalazione = true;
            $cointestazione = false;
            $canStartReading = false;
            //            dd($jsonArray);

            $categoriaSezione = array(
                "RISCHI A SCADENZA" => 'Cassa',
                "RISCHI AUTOLIQUIDANTI" => 'Cassa',
                "RISCHI A REVOCA" => 'Cassa',
                "RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI" => 'Informativa',
                "SOFFERENZE" => 'Sofferenze',
                "GARANZIE CONNESSE CON OPERAZIONI DI NATURA COMMERCIALE" => 'Firma',
                "GARANZIE RICEVUTE" => 'Garanzie',
                'Garanti' => 'Garanti'
            );

            foreach ($jsonArray as $singleJsonArray) {
                foreach ($singleJsonArray as $key => $value) {
                    $cleanRow = (object)array_filter((array)$value, 'strlen');


                    foreach ($cleanRow as $singleRow) {

                        if (strpos($singleRow, 'Cointestazione: ') !== false && strpos($singleRow, 'cod. CR') !== true) {
                            $explodedCointestazione = explode('CR', $singleRow);
                            $cointestazione = trim($explodedCointestazione[1]);
                        }

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
                            }
                            if (!array_key_exists($month, $crData[$currentYear])) {
                                $crData[$currentYear][$currentMonth] = array();
                            }
                        }
                        if ($singleRow == 'Intermediario:') {
                            $intermed = next($cleanRow);
                            if (!$currentIntermediario || $intermed != $currentIntermediario) {
                                $currentIntermediario = $intermed;
                                $isSegnalazione = false;

                                if (!array_key_exists($currentMonth, $crData[$currentYear])) {
                                    $crData[$currentYear][$currentMonth] = array();
                                    if (!array_key_exists($currentIntermediario, $crData[$currentYear][$currentMonth])) {
                                        $crData[$currentYear][$currentMonth][$currentIntermediario] = array();
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

                        if (strpos($singleRow, 'Di seguito si riportano le segnalazioni') !== false) {
                            $isSegnalazione = true;
                        }


                        if (
                            (strpos($singleRow, 'RISCHI A') !== false ||
                                strpos($singleRow, 'GARANZIE CONNESSE') !== false ||
                                strpos($singleRow, 'SOFFERENZE') !== false ||
                                strpos($singleRow, 'CREDITI') !== false ||
                                strpos($singleRow, 'GARANZIE RICEVUTE') !== false)
                        ) {

                            foreach ($currentCategoryArray as $arrKey => $arrValue) {
                                if ($arrKey == 'Categoria') {
                                    $currentCategoryArray['Categoria'] = $singleRow;
                                } else {
                                    $currentCategoryArray[$arrKey] = next($cleanRow);
                                }
                            }


                            if (array_key_exists($currentCategoryArray['Categoria'], $categoriaSezione)) {
                                $sezione = $categoriaSezione[$currentCategoryArray['Categoria']];
                                if ($isSegnalazione) {
                                    $sezione = 'Segnalazioni';
                                }
                                $crData[$currentYear][$currentMonth][$currentIntermediario][$sezione][] = array_merge($currentCategoryArray, array('Cointestazione' => $cointestazione));
                            }
                        }


                        if (strpos($singleRow, 'Garante') !== false) {
                            if (!$currentGarante || $currentGarante != $singleRow) {
                                $currentGarante = $singleRow;

                                $garanteArray = array();
                                $garanteArray['Garante'] = array();
                                for ($i = 2; $i <= count((array)$cleanRow); $i++) {
                                    $garanteArray[next($cleanRow)] = array();
                                }
                                $currentGaranteArray = $garanteArray;
                            }
                        }


                        if (strpos($singleRow, 'codice censito') !== false) {
                            if (isset($currentGaranteArray)) {
                                if (!$currentGarante || $currentGarante != $singleRow) {
                                    foreach ($currentGaranteArray as $arrKey => $arrValue) {
                                        if ($arrKey == 'Garante') {
                                            $currentGaranteArray['Garante'] = $singleRow;
                                        } else {
                                            $currentGaranteArray[$arrKey] = next($cleanRow);
                                        }
                                    }
                                    $crData[$currentYear][$currentMonth][$currentIntermediario]['Garanti'][] = $currentGaranteArray;
                                }
                            }
                        }
                    }
                }
            }


            $tensioniAnnuali = array();
            $crediti_perdita = array();
            $sofferenze = array();
            $soldini = array();
            $sconfini = array();
            $yearlyDivision = array();
            $sconfiniPerAnniBanche = array();
            $tensioniLineCtredito = array();


            foreach ($crData as $anno => $months) {
                foreach ($months as $mese => $data) {
                    cr::where('anno', $anno)->where('mese', $mese)->delete();
                    foreach ($data as $singleBank => $keys) {
                        foreach ($keys as $index => $value) {
                            $mesiList = ["0" => "fuoriMese", "gennaio" => 1, 'febbraio' => 2, 'marzo' => 3, 'aprile' => 4, 'maggio' => 5, 'giugno' => 6, 'luglio' => 07, "agosto" => 8, 'settembre' => 9, 'ottobre' => 10, 'novembre' => 11, 'dicembre' => 12,];

                            $transformedMonth = $mesiList[$mese];
                            $arraytmp = [];



                            if ($index === 'Firma') {

                                $array = $value;
                                foreach ($array as $key => $val) {
                                    foreach ($val as $chiave => $valore) {
                                        $chiave = str_replace(array("\r\n", "\n", "\r"), ' ', $chiave);
                                        $valore = str_replace(array("\r\n", "\n", "\r"), ' ', $valore);
                                        $arraytmp[$chiave] = $valore;
                                    }
                                    $categoria = $arraytmp['Categoria'];
                                    $accordato = str_replace('.', '', $arraytmp['Accordato']);
                                    $accordatoOperativo = str_replace('.', '', $arraytmp['Accordato Operativo']);
                                    $utilizzato = str_replace('.', '', $arraytmp['Utilizzato']);
                                    $durataResidua = isset($arraytmp['Durata Residua']) ? $arraytmp['Durata Residua'] : 'N/A';
                                    $durataOriginaria = isset($arraytmp['Durata Originaria']) ? $arraytmp['Durata Originaria'] : 'N/A';
                                    $localizzazione = isset($arraytmp['Localizzazione']) ? $arraytmp['Localizzazione'] : 'N/A';
                                    $divisa = isset($arraytmp['Divisa']) ? $arraytmp['Divisa'] : 'N/A';
                                    $tipoGaranzia = isset($arraytmp['Tipo Garanzia']) ? $arraytmp['Tipo Garanzia'] : 'N/A';
                                    $statoRapporto = isset($arraytmp['Stato Rapporto']) ? $arraytmp['Stato Rapporto'] : 'N/A';
                                    $tipoAttivita = isset($arraytmp['Tipo Attività']) ? $arraytmp['Tipo Attività'] : 'N/A';
                                    $ruoloAffidato = isset($arraytmp['Ruolo Affidato']) ? $arraytmp['Ruolo Affidato'] : 'N/A';
                                    $importExport = isset($arraytmp['Import Export']) ? $arraytmp['Import Export'] : 'N/A';
                                    $saldo_medio = isset($arraytmp['Saldo Medio']) ? $arraytmp['Saldo Medio'] : 'N/A';
                                    $saldo_medio = str_replace('.', '', $saldo_medio);
                                    $importo_garantito = isset($arraytmp['Importo Garantito']) ? $arraytmp['Importo Garantito'] : 'N/A';
                                    $importo_garantito = str_replace('.', '', $importo_garantito);
                                    $codiceCoint = isset($arraytmp['Cointestazione']) ? $arraytmp['Cointestazione'] : null;

                                    $cr = new cr;
                                    $buildDate = $anno . ' ' . $transformedMonth;


                                    $createdDate  = date_create_from_format("Y n", $buildDate);

                                    $cr->date = $createdDate;
                                    $cr->anno = $anno;
                                    $cr->mese = $mese;
                                    $cr->account_id = 1;
                                    $cr->nome_banca = $singleBank;
                                    $cr->sezione = $index;
                                    $cr->categoria = $categoria;
                                    $cr->accordato = $accordato;
                                    $cr->accordato_operativo = $accordatoOperativo;
                                    $cr->utilizzato = $utilizzato;
                                    $cr->durata_residua = $durataResidua;
                                    $cr->durata_originaria = $durataOriginaria;
                                    $cr->localizzazione = $localizzazione;
                                    $cr->divisa = $divisa;
                                    $cr->tipo_garanzia = $tipoGaranzia;
                                    $cr->stato_rapporto = $statoRapporto;
                                    $cr->tipo_attivita = $tipoAttivita;
                                    $cr->ruolo_affidato = $ruoloAffidato;
                                    $cr->import_export = $importExport;
                                    $cr->saldo_medio = $saldo_medio;
                                    $cr->importo_garantito = $importo_garantito;
                                    $cr->codice_coint = $codiceCoint;
                                    $cr->save();
                                }
                            }





                            if ($index === 'Garanzie') {
                                $array = $value;
                                foreach ($array as $key => $val) {
                                    foreach ($val as $chiave => $valore) {
                                        $chiave = str_replace(array("\r\n", "\n", "\r"), ' ', $chiave);
                                        $valore = str_replace(array("\r\n", "\n", "\r"), ' ', $valore);
                                        $arraytmp[$chiave] = $valore;
                                    }
                                    //                                    dump($arraytmp);
                                    $categoria = $arraytmp['Categoria'];
                                    $accordato = '';
                                    $accordatoOperativo = '';
                                    $utilizzato = '';
                                    $durataResidua = '';
                                    $durataOriginaria = '';
                                    $localizzazione = isset($arraytmp['Localizzazione']) ? $arraytmp['Localizzazione'] : 'N/A';
                                    $divisa = '';
                                    $tipoGaranzia = isset($arraytmp['Tipo Garanzia']) ? $arraytmp['Tipo Garanzia'] : 'N/A';
                                    $statoRapporto = isset($arraytmp['Stato Rapporto']) ? $arraytmp['Stato Rapporto'] : 'N/A';
                                    $tipoAttivita = '';
                                    $ruoloAffidato = '';
                                    $importExport = '';
                                    $saldo_medio = '';
                                    $importo_garantito = isset($arraytmp['Importo Garantito']) ? $arraytmp['Importo Garantito'] : 'N/A';
                                    $garantito = isset($arraytmp['Garantito']) ? $arraytmp['Garantito'] : 'N/A';
                                    $garanzia = isset($arraytmp['Valore Garanzia']) ? $arraytmp['Valore Garanzia'] : 'N/A';
                                    $codiceCoint = isset($arraytmp['Cointestazione']) ? $arraytmp['Cointestazione'] : null;


                                    $cr = new cr;
                                    $buildDate = $anno . ' ' . $transformedMonth;

                                    $createdDate  = date_create_from_format("Y n", $buildDate);

                                    $cr->date = $createdDate;
                                    $cr->anno = $anno;
                                    $cr->mese = $mese;
                                    $cr->account_id = 1;
                                    $cr->nome_banca = $singleBank;
                                    $cr->sezione = $index;
                                    $cr->categoria = $categoria;
                                    $cr->accordato = $accordato;
                                    $cr->accordato_operativo = $accordatoOperativo;
                                    $cr->utilizzato = $utilizzato;
                                    $cr->durata_residua = $durataResidua;
                                    $cr->durata_originaria = $durataOriginaria;
                                    $cr->localizzazione = $localizzazione;
                                    $cr->divisa = $divisa;
                                    $cr->tipo_garanzia = $tipoGaranzia;
                                    $cr->stato_rapporto = $statoRapporto;
                                    $cr->tipo_attivita = $tipoAttivita;
                                    $cr->ruolo_affidato = $ruoloAffidato;
                                    $cr->import_export = $importExport;
                                    $cr->saldo_medio = $saldo_medio;
                                    $cr->importo_garantito = $importo_garantito;
                                    $cr->garantito = $garantito;
                                    $cr->garanzia = $garanzia;
                                    $cr->codice_coint = $codiceCoint;
                                    $cr->save();
                                }
                            }






                            if ($index === 'Sofferenze') {
                                $array = $value;
                                foreach ($array as $key => $val) {
                                    foreach ($val as $chiave => $valore) {
                                        $chiave = str_replace(array("\r\n", "\n", "\r"), ' ', $chiave);
                                        $valore = str_replace(array("\r\n", "\n", "\r"), ' ', $valore);
                                        $arraytmp[$chiave] = $valore;
                                    }
                                    //                                    dump($arraytmp);
                                    $categoria = $arraytmp['Categoria'];
                                    $accordato = isset($arraytmp['Accordato']) ? str_replace('.', '', $arraytmp['Accordato']) : 'N/A';
                                    $accordatoOperativo = isset($arraytmp['Accordato Operativo']) ? str_replace('.', '', $arraytmp['Accordato Operativo']) : 'N/A';
                                    $utilizzato = isset($arraytmp['Utilizzato']) ? str_replace('.', '', $arraytmp['Utilizzato']) : 'N/A';
                                    $durataResidua = isset($arraytmp['Durata Residua']) ? $arraytmp['Durata Residua'] : 'N/A';
                                    $durataOriginaria = isset($arraytmp['Durata Originaria']) ? $arraytmp['Durata Originaria'] : 'N/A';
                                    $localizzazione = isset($arraytmp['Localizzazione']) ? $arraytmp['Localizzazione'] : 'N/A';
                                    $divisa = isset($arraytmp['Divisa']) ? $arraytmp['Divisa'] : 'N/A';
                                    $tipoGaranzia = isset($arraytmp['Tipo Garanzia']) ? $arraytmp['Tipo Garanzia'] : 'N/A';
                                    $statoRapporto = isset($arraytmp['Stato Rapporto']) ? $arraytmp['Stato Rapporto'] : 'N/A';
                                    $tipoAttivita = isset($arraytmp['Tipo Attività']) ? $arraytmp['Tipo Attività'] : 'N/A';
                                    $ruoloAffidato = isset($arraytmp['Ruolo Affidato']) ? $arraytmp['Ruolo Affidato'] : 'N/A';
                                    $importExport = isset($arraytmp['Import Export']) ? $arraytmp['Import Export'] : 'N/A';
                                    $saldo_medio = isset($arraytmp['Saldo Medio']) ? $arraytmp['Saldo Medio'] : 'N/A';
                                    $importo_garantito = isset($arraytmp['Importo Garantito']) ? $arraytmp['Importo Garantito'] : 'N/A';
                                    $codiceCoint = isset($arraytmp['Cointestazione']) ? $arraytmp['Cointestazione'] : null;

                                    $cr = new cr;
                                    $buildDate = $anno . ' ' . $transformedMonth;

                                    $createdDate  = date_create_from_format("Y n", $buildDate);

                                    $cr->date = $createdDate;
                                    $cr->anno = $anno;
                                    $cr->mese = $mese;
                                    $cr->account_id = 1;
                                    $cr->nome_banca = $singleBank;
                                    $cr->sezione = $index;
                                    $cr->categoria = $categoria;
                                    $cr->accordato = $accordato;
                                    $cr->accordato_operativo = $accordatoOperativo;
                                    $cr->utilizzato = $utilizzato;
                                    $cr->durata_residua = $durataResidua;
                                    $cr->durata_originaria = $durataOriginaria;
                                    $cr->localizzazione = $localizzazione;
                                    $cr->divisa = $divisa;
                                    $cr->tipo_garanzia = $tipoGaranzia;
                                    $cr->stato_rapporto = $statoRapporto;
                                    $cr->tipo_attivita = $tipoAttivita;
                                    $cr->ruolo_affidato = $ruoloAffidato;
                                    $cr->import_export = $importExport;
                                    $cr->saldo_medio = $saldo_medio;
                                    $cr->importo_garantito = $importo_garantito;
                                    $cr->codice_coint = $codiceCoint;
                                    $cr->save();
                                }
                            }





                            if ($index === 'Cassa') {
                                $array = $value;
                                foreach ($array as $key => $val) {
                                    foreach ($val as $chiave => $valore) {
                                        $chiave = str_replace(array("\r\n", "\n", "\r"), ' ', $chiave);
                                        $valore = str_replace(array("\r\n", "\n", "\r"), ' ', $valore);
                                        $arraytmp[$chiave] = $valore;
                                    }
                                    if (!isset($arraytmp['Accordato'])) {
                                        continue;
                                    }
                                    $categoria = $arraytmp['Categoria'];
                                    $accordato = str_replace('.', '', $arraytmp['Accordato']);
                                    $accordatoOperativo = str_replace('.', '', $arraytmp['Accordato Operativo']);
                                    $utilizzato = str_replace('.', '', $arraytmp['Utilizzato']);
                                    $durataResidua = isset($arraytmp['Durata Residua']) ? $arraytmp['Durata Residua'] : '';
                                    $durataOriginaria = isset($arraytmp['Durata Originaria']) ? $arraytmp['Durata Originaria'] : '';
                                    $localizzazione = isset($arraytmp['Localizzazione']) ? $arraytmp['Localizzazione'] : '';
                                    $divisa = isset($arraytmp['Divisa']) ? $arraytmp['Divisa'] : '';
                                    $tipoGaranzia = isset($arraytmp['Tipo Garanzia']) ? $arraytmp['Tipo Garanzia'] : '';
                                    $statoRapporto = isset($arraytmp['Stato Rapporto']) ? $arraytmp['Stato Rapporto'] : '';
                                    $tipoAttivita = isset($arraytmp['Tipo Attività']) ? $arraytmp['Tipo Attività'] : '';
                                    $ruoloAffidato = isset($arraytmp['Ruolo Affidato']) ? $arraytmp['Ruolo Affidato'] : '';
                                    $importExport = isset($arraytmp['Import Export']) ? $arraytmp['Import Export'] : '';
                                    $saldo_medio = isset($arraytmp['Saldo Medio']) ? $arraytmp['Saldo Medio'] : 'N/A';
                                    $importo_garantito = isset($arraytmp['Importo Garantito']) ? $arraytmp['Importo Garantito'] : 'N/A';
                                    $codiceCoint = isset($arraytmp['Cointestazione']) ? $arraytmp['Cointestazione'] : null;

                                    if ($arraytmp['Categoria'] != 'LA CENTRALE DEI RISCHI' && !str_contains($arraytmp['Accordato'], 'Assenza')) {
                                        $cr = new cr;
                                        $buildDate = $anno . ' ' . $transformedMonth;

                                        $createdDate  = date_create_from_format("Y n", $buildDate);


                                        $cr->date = $createdDate;
                                        $cr->anno = $anno;
                                        $cr->mese = $mese;
                                        $cr->account_id = 1;
                                        $cr->nome_banca = $singleBank;
                                        $cr->sezione = $index;
                                        $cr->categoria = $categoria;
                                        $cr->accordato = $accordato;
                                        $cr->accordato_operativo = $accordatoOperativo;
                                        $cr->utilizzato = $utilizzato;
                                        $cr->durata_residua = $durataResidua;
                                        $cr->durata_originaria = $durataOriginaria;
                                        $cr->localizzazione = $localizzazione;
                                        $cr->divisa = $divisa;
                                        $cr->tipo_garanzia = $tipoGaranzia;
                                        $cr->stato_rapporto = $statoRapporto;
                                        $cr->tipo_attivita = $tipoAttivita;
                                        $cr->ruolo_affidato = $ruoloAffidato;
                                        $cr->import_export = $importExport;
                                        $cr->saldo_medio = $saldo_medio;
                                        $cr->importo_garantito = $importo_garantito;
                                        $cr->codice_coint = $codiceCoint;
                                        $cr->save();
                                    }
                                }
                            }


                            if ($index === 'Informativa') {

                                $array = $value;
                                foreach ($array as $key => $val) {
                                    foreach ($val as $chiave => $valore) {
                                        $chiave = str_replace(array("\r\n", "\n", "\r"), ' ', $chiave);
                                        $valore = str_replace(array("\r\n", "\n", "\r"), ' ', $valore);
                                        $arraytmp[$chiave] = $valore;
                                    }
                                    if (isset($arraytmp['Categoria']) && $arraytmp['Categoria'] == 'RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI') {
                                        $categoria = $arraytmp['Categoria'];
                                        $accordato = '';
                                        $accordatoOperativo = '';
                                        $utilizzato = '';
                                        $durataResidua = '';
                                        $durataOriginaria = '';
                                        $localizzazione = $arraytmp['Localizzazione'];
                                        $divisa = '';
                                        $tipoGaranzia = '';
                                        $statoRapporto = $arraytmp['Stato Rapporto'];
                                        $tipoAttivita = '';
                                        $ruoloAffidato = '';
                                        $importExport = '';
                                        $saldo_medio = isset($arraytmp['Saldo Medio']) ? $arraytmp['Saldo Medio'] : 'N/A';
                                        $importo_garantito = isset($arraytmp['Importo']) ? $arraytmp['Importo'] : 'N/A';
                                        // if ($statoRapporto == 'Crediti pagati' || $statoRapporto == 'Crediti impagati' || str_contains($categoria, 'A PERDITA')) {
                                        $importo = isset($arraytmp['Importo']) ? $arraytmp['Importo'] : 'N/A';
                                        $importo_garantito = str_replace('.', '', $importo);
                                        $codiceCoint = isset($arraytmp['Cointestazione']) ? $arraytmp['Cointestazione'] : null;
                                        // }

                                        $cr = new cr;
                                        $buildDate = $anno . ' ' . $transformedMonth;

                                        $createdDate  = date_create_from_format("Y n", $buildDate);
                                        $cr->date = $createdDate;
                                        $cr->anno = $anno;
                                        $cr->mese = $mese;
                                        $cr->account_id = 1;
                                        $cr->nome_banca = $singleBank;
                                        $cr->sezione = $index;
                                        $cr->categoria = $categoria;
                                        $cr->accordato = $accordato;
                                        $cr->accordato_operativo = $accordatoOperativo;
                                        $cr->utilizzato = $utilizzato;
                                        $cr->durata_residua = $durataResidua;
                                        $cr->durata_originaria = $durataOriginaria;
                                        $cr->localizzazione = $localizzazione;
                                        $cr->divisa = $divisa;
                                        $cr->tipo_garanzia = $tipoGaranzia;
                                        $cr->stato_rapporto = $statoRapporto;
                                        $cr->tipo_attivita = $tipoAttivita;
                                        $cr->ruolo_affidato = $ruoloAffidato;
                                        $cr->import_export = $importExport;
                                        $cr->saldo_medio = $saldo_medio;
                                        $cr->importo_garantito = $importo_garantito;
                                        $cr->codice_coint = $codiceCoint;
                                        $cr->save();
                                    }
                                }
                            }

                            if ($index === 'Garanti') {

                                $array = $value;
                                foreach ($array as $key => $val) {
                                    foreach ($val as $chiave => $valore) {
                                        $chiave = str_replace(array("\r\n", "\n", "\r"), ' ', $chiave);
                                        $valore = str_replace(array("\r\n", "\n", "\r"), ' ', $valore);
                                        $arraytmp[$chiave] = $valore;
                                    }

                                    if (isset($arraytmp['Garante'])) {
                                        if (isset($arraytmp['Valore Garanzia']) && isset($arraytmp['Importo Garantito'])) {
                                            if (($arraytmp['Valore Garanzia'] !== false && $arraytmp['Importo Garantito'] !== false) && ($arraytmp['Valore Garanzia'] !== "" && $arraytmp['Importo Garantito'] !== "")) {
                                                $nome_garante = $arraytmp['Garante'];
                                                $categoria = '';
                                                $accordato = '';
                                                $accordatoOperativo = '';
                                                $utilizzato = '';
                                                $durataResidua = '';
                                                $durataOriginaria = '';
                                                $localizzazione = '';
                                                $divisa = '';
                                                $tipoGaranzia = '';
                                                $statoRapporto = '';
                                                $tipoAttivita = '';
                                                $ruoloAffidato = '';
                                                $importExport = '';
                                                $saldo_medio = isset($arraytmp['Saldo Medio']) ? $arraytmp['Saldo Medio'] : 'N/A';
                                                $importo_garantito = isset($arraytmp['Importo']) ? $arraytmp['Importo'] : 'N/A';
                                                // if ($statoRapporto == 'Crediti pagati' || $statoRapporto == 'Crediti impagati' || str_contains($categoria, 'A PERDITA')) {
                                                $importo = isset($arraytmp['Importo Garantito']) ? $arraytmp['Importo Garantito'] : 'N/A';

                                                $valoreGaranzia = isset($arraytmp['Valore Garanzia']) ? $arraytmp['Valore Garanzia'] : 'N/A';
                                                $valoreGaranzia = str_replace('.', '', $valoreGaranzia);

                                                $importo_garantito = str_replace('.', '', $importo);
                                                $codiceCoint = isset($arraytmp['Cointestazione']) ? $arraytmp['Cointestazione'] : null;

                                                // }

                                                $cr = new cr;
                                                $buildDate = $anno . ' ' . $transformedMonth;

                                                $createdDate  = date_create_from_format("Y n", $buildDate);
                                                $cr->date = $createdDate;
                                                $cr->nome_garante = $nome_garante;
                                                $cr->anno = $anno;
                                                $cr->mese = $mese;
                                                $cr->account_id = 1;
                                                $cr->nome_banca = $singleBank;
                                                $cr->sezione = $index;
                                                $cr->categoria = $categoria;
                                                $cr->accordato = $accordato;
                                                $cr->accordato_operativo = $accordatoOperativo;
                                                $cr->utilizzato = $utilizzato;
                                                $cr->durata_residua = $durataResidua;
                                                $cr->durata_originaria = $durataOriginaria;
                                                $cr->localizzazione = $localizzazione;
                                                $cr->divisa = $divisa;
                                                $cr->tipo_garanzia = $tipoGaranzia;
                                                $cr->stato_rapporto = $statoRapporto;
                                                $cr->tipo_attivita = $tipoAttivita;
                                                $cr->ruolo_affidato = $ruoloAffidato;
                                                $cr->import_export = $importExport;
                                                $cr->saldo_medio = $saldo_medio;
                                                $cr->codice_coint = $codiceCoint;

                                                $cr->importo_garantito = $valoreGaranzia;
                                                $cr->garanzia = $importo_garantito;

                                                $cr->save();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $crFileToElaborate->status = "completed";
            $crFileToElaborate->save();

            return response()->json([
                'error' => false,
                'data' => 'File Centrale Rischi' . $crFileToElaborate->status
            ]);
            return Redirect::route('admin.cr.centralerischi.datatable');
        }
    }

    /**
     * @return mixed
     */
    public function store(Request $request)
    {
        $importCr = $request->base64;
		
        $fileName = time() . '.pdf';

        Storage::disk('public')->put($fileName, base64_decode($importCr));

        // $importCr->move(public_path('centraleRischi/'), $fileName, base64_decode($importCr));

        $dataCr = [
            'filename' => $fileName,
            'path' => asset('centraleRischi') . '/' . $fileName,
            'type' => 'centrale rischi'
        ];

        Document::create($dataCr);


        $jsonData = array();
        foreach ($request->input() as $singlereq => $singleValue) {
            $jsonData[$singlereq] = $singleValue;
        }

        $jsonDB = json_encode($jsonData);
        $accountId = $request->input('account_id');





        return response()->json([
            'error' => false,
            'jsonData' => $jsonData
        ]);

        return view('bilanci.store', $bilancio)->with(['jsonData' => $jsonData]);
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
            $lastDate = new DateTime(cr::select('date')->orderBy('date', 'desc')->first()->date);

            switch ($crAndamentaleData['period']) {
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
            }

         $latestDate = new DateTime(cr::select('date')->orderBy('date', 'desc')->first()->date);

            $banksScoring = array();
            $singleBankData = array();

            $numeroRapportiContestati = 0;

            $unrefinedPeriods = json_decode(DB::table('crs')
                ->select('anno', 'mese', 'date')
                ->where("date", '>', $earlierDate->modify('first day of this month')->format('Y-m-d'))->where("date", '<', $lastDate->modify('last day of this month')->format('Y-m-d'))
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
                    if (cr::where([['date', '>=', $tempDate->modify('+1 month')->format('Y-m-01')], ['date', '<=', $tempDate->format('Y-m-0t')]])->groupBy('date')->count() == 0) {
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
            $periodsCorrect = $crHelper->buildPeriodArray();

            $banksQuery = DB::table('crs');

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
                'anomalie' => $anomalie,
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
                'intermediari' => $intermediari,
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
                'scoreCR' => $scoreCR
            ]);

            // dd($sconfiniDivisi);
            return view('centralerischi.crandamentale', compact('anomalieStatoRapporto', 'anomalie', 'missingMonths', 'sconfiniDivisi', 'utilizzatoPie', 'accordatoPie', 'earlierDate', 'garanzieRicevute', 'anomalie', 'informazioniGaranti', 'monthsList', 'affidamentiPerMese', 'banksScoring', 'totaleAffidamentiGeneral', 'incidenzaImpagati', 'rischiGaranzie', 'percentualiUtilizzato', 'percentualiAccordato', 'latestMonth', 'latestYear', 'importiSconfini', 'creditiPassatiPerdita', 'sofferenze', 'garanzieEsitoNegativo', 'impagati', 'numeroRapportiContestati', 'intermediari', 'inizioPeriodo', 'finePeriodo', 'intermediari', 'numeroSconfiniTotali', 'mediaAnalisiIndebitamento', 'totaleAffidamentiTable', 'totAffidamentiConPesiPerBanca', 'scoreCR'));
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
