<?php


namespace App\Helpers\CentraleRischi;

use App\Http\Requests;
use App;
use App\Models\Bilanci;
use App\Models\Account;
use App\Models\cr;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer;
use phpDocumentor\Reflection\Types\This;

class CentraleRischiStoreDataHelper
{
    public $_data = false;

    public function saveFirma($value, $anno, $mese, $transformedMonth, $singleBank, $index, $codiceDocumento, $companyId)
    {

        $array = $value;
        $arraytmp = [];
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


            $createdDate = date_create_from_format("Y n", $buildDate);

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

            //new data
            $cr->document_id = $codiceDocumento;
            $cr->company_id = $companyId;

            $cr->save();
        }
    }


    public function saveGaranzie($value, $anno, $mese, $transformedMonth, $singleBank, $index, $codiceDocumento, $companyId)
    {
        $array = $value;
        $arraytmp = [];
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

            $createdDate = date_create_from_format("Y n", $buildDate);

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
            //new data
            $cr->document_id = $codiceDocumento;
            $cr->company_id = $companyId;
            $cr->save();
        }
    }

    public function saveSofferenze($value, $anno, $mese, $transformedMonth, $singleBank, $index, $codiceDocumento, $companyId)
    {
        $array = $value;
        $arraytmp = [];
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

            $createdDate = date_create_from_format("Y n", $buildDate);

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
            //new data
            $cr->document_id = $codiceDocumento;
            $cr->company_id = $companyId;
            $cr->save();
        }
    }

    public function saveCassa($value, $anno, $mese, $transformedMonth, $singleBank, $index, $codiceDocumento, $companyId)
    {
     
        $array = $value;
        $arraytmp = [];
        
        foreach ($array as $key => $val) {
            foreach ($val as $chiave => $valore) {
                $chiave = str_replace(array("\r\n", "\n", "\r"), ' ', $chiave);
                $valore = str_replace(array("\r\n", "\n", "\r"), ' ', $valore);
                $arraytmp[$chiave] = $valore;
            }
    
            if (!isset($arraytmp['Accordato'])) {
                
                continue;
            }

            $accordatoOperativo = null;

if (isset($arraytmp['Accordato Operativo']) && $arraytmp['Accordato Operativo'] !== '') {
    $accordatoOperativo = $arraytmp['Accordato Operativo'];
} elseif (isset($arraytmp['Operativo']) && $arraytmp['Operativo'] !== '') {
    $accordatoOperativo = $arraytmp['Operativo'];
}

 
     
            $categoria = $arraytmp['Categoria'];
            $accordato = str_replace('.', '', $arraytmp['Accordato']);
            $accordatoOperativo = $accordatoOperativo !== null
            ? str_replace('.', '', $accordatoOperativo)
            : '';
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

                $createdDate = date_create_from_format("Y n", $buildDate);


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
                //new data
                $cr->document_id = $codiceDocumento;
                $cr->company_id = $companyId;
                try {
               
                    $cr->save();                     // <‑‑ qui il salvataggio
              
            
                } catch (QueryException $e) {  
                  
                    Log::error('Errore SQL nel salvataggio della credit request', [
                        'sql_state' => $e->getSqlState(),
                        'code'      => $e->getCode(),
                        'message'   => $e->getMessage(),
                    ]);
                    return response()->json([
                        'message' => 'Errore durante il salvataggio (DB)',
                    ], 500);
            
                } catch (\Throwable $e) {   
                    dd('salnon vato');           // qualsiasi altra eccezione
                    Log::critical('Errore generico nel salvataggio credit request', [
                        'message' => $e->getMessage(),
                        'trace'   => $e->getTraceAsString(),
                    ]);
                    return response()->json([
                        'message' => 'Si è verificato un errore imprevisto',
                    ], 500);
                }
            }
        }
    }

    public function saveInformativa(
        array $value,
        $anno, $mese, $transformedMonth,
        $singleBank, $index, $codiceDocumento, $companyId
    ) {
        // categorie da salvare come "Informativa"
        $categorieInformativa = [
            'RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI',
            'SOFFERENZE - CREDITI PASSATI A PERDITA',
            'CREDITI ACQUISITI DA CLIENTELA DIVERSA DA INTERMEDIARI - DEBITORI CEDUTI',
        ];
    
        foreach ($value as $riga) {
    
            // 1) azzera buffer
            $arraytmp = [];
    
            // 2) normalizza chiavi/valori
            foreach ($riga as $k => $v) {
                $k              = str_replace(["\r", "\n"], ' ', $k);
                $arraytmp[$k]   = str_replace(["\r", "\n"], ' ', $v);
            }
    
            // 3) se la categoria non è tra quelle che ci interessano → salta
            if (
                empty($arraytmp['Categoria']) ||
                !in_array($arraytmp['Categoria'], $categorieInformativa, true)
            ) {
                continue;
            }
    
            /* --------- mapping campi ----------------------------------------------------------- */
            $categoria          = $arraytmp['Categoria'];
            $localizzazione     = $arraytmp['Localizzazione']  ?? '';
            $statoRapporto      = $arraytmp['Stato Rapporto']  ?? '';
            $saldo_medio        = $arraytmp['Saldo Medio']     ?? 'N/A';
            $importo            = $arraytmp['Importo']         ?? 'N/A';
            $importo_garantito  = str_replace('.', '', $importo);
            $codiceCoint        = $arraytmp['Cointestazione']  ?? null;
    
            // altri campi non presenti in questa sezione
            $accordato = $accordatoOperativo = $utilizzato = '';
            $durataResidua = $durataOriginaria = $divisa = $tipoGaranzia = '';
            $tipoAttivita  = $ruoloAffidato = $importExport = '';
    
            /* --------- salva ------------------------------------------------------------------- */
            $cr = new cr;
            $buildDate   = $anno.' '.$transformedMonth;
            $cr->date    = date_create_from_format('Y n', $buildDate);
            $cr->anno    = $anno;
            $cr->mese    = $mese;
            $cr->account_id        = 1;
            $cr->nome_banca        = $singleBank;
            $cr->sezione           = $index;        // "Informativa"
            $cr->categoria         = $categoria;
            $cr->accordato         = $accordato;
            $cr->accordato_operativo = $accordatoOperativo;
            $cr->utilizzato        = $utilizzato;
            $cr->durata_residua    = $durataResidua;
            $cr->durata_originaria = $durataOriginaria;
            $cr->localizzazione    = $localizzazione;
            $cr->divisa            = $divisa;
            $cr->tipo_garanzia     = $tipoGaranzia;
            $cr->stato_rapporto    = $statoRapporto;
            $cr->tipo_attivita     = $tipoAttivita;
            $cr->ruolo_affidato    = $ruoloAffidato;
            $cr->import_export     = $importExport;
            $cr->saldo_medio       = $saldo_medio;
            $cr->importo_garantito = $importo_garantito;
            $cr->codice_coint      = $codiceCoint;
            $cr->document_id       = $codiceDocumento;
            $cr->company_id        = $companyId;
            $cr->save();
        }
    }

    public function saveGaranti($value, $anno, $mese, $transformedMonth, $singleBank, $index, $codiceDocumento, $companyId)
    {

        $array = $value;
        $arraytmp = [];
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

                        $createdDate = date_create_from_format("Y n", $buildDate);
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
                        //new data
                        $cr->document_id = $codiceDocumento;
                        $cr->company_id = $companyId;
                        $cr->save();
                    }
                }
            }
        }
    }
}
