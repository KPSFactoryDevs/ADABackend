<?php

namespace App\Helpers\Bilanci;

use App\Http\Requests;
use App;
use App\Models\Bilanci;
use App\Models\Basic;
use App\Models\Voci;
use App\Models\Account;
use App\Models\cr;
use App\Models\soglie;
use App\Models\range;
use App\Models\Roe;
use App\Models\Document;
use Exception;
use App\Helpers\Bilanci\BilanciCalculationsHelper;
use App\Models\CustomLog;
use XBRL\XBRL_DFR;
use XBRL\XBRL_Global;
use XBRL\XBRL_Types;
use XBRL\XBRL_Instance;
use App\Helpers\Bilanci\BilanciCalculationsHelperAdvanced;

class BilanciHelper
{

    public function getIndexesForBalanceTaxonomy($idBilancio, $filePath = false, $instance = false) {

        $vocis = Voci::pluck('name', 'extended_name')->all();

        $calculationHelper = new BilanciCalculationsHelperAdvanced;
        $calculationHelper->documentId = $idBilancio;

        if($instance) {
            $calculationHelper->setCurrentInstance($instance);
        } else {
            $document = Document::findOrFail($idBilancio);

            $filePath = base_path() . '/public/bilanci/' . $document->filename;
            $taxonomyName = $document->taxonomy;
            $taxonomyPath = base_path()."/taxonomies/2018-11-04/".$taxonomyName;

            $readXBRL = XBRL_Instance::FromInstanceDocument($filePath, $taxonomyPath, $emptyInstance);
            $calculationHelper->setCurrentInstance($readXBRL);
        }

        return array(
            "Indici" => [
                "Basic" => [
                    "Sostenibilità Oneri Finanziari" => $calculationHelper->getSostenibilitaOneriFinanziari(),
                    "Adeguatezza Patrimoniale" => $calculationHelper->getAdeguatezzaPatrimonialeEvaluation(),
                    "Liquidità" => $calculationHelper->getLiquiditaEvaluation(),
                    "Indebitamento Previdenziale Tributario" => $calculationHelper->getIndebitamentoPrevidenziale(),
                    "Ritorno Liquido Attivo" => $calculationHelper->getRitornoLiquidoAttivo(),
                    "IndiceCNDCEC" => $calculationHelper->getIndiceCNDCEC(),
                ],
                "Advanced" => [
                    'OF Ricavi' => $calculationHelper->getOfRicavi(),
                    'Adeguatezza Patrimoniale' => $calculationHelper->getAdeguatezzaPatrimoniale(),
                    'Liqudità' => $calculationHelper->getLiquidita(),
                    'Andamento Del Fatturato' => $calculationHelper->getAndamentoDelFatturato(),
                    'Andamento Del Mol' => ($calculationHelper->getAndamentoDelMol('Andamento Del Mol')) ? $calculationHelper->getAndamentoDelMol('Andamento Del Mol')['AndamentoMOL'] : false,
                    'ROI' => $calculationHelper->getROI(),
                    'ROS' => $calculationHelper->getROS(),
                    'ROE' => $calculationHelper->getROE(),
                    'Ebitda Fatturato' => $calculationHelper->getEbitdaFatturato(),
                    'Andamento Dei Mezzi Propri' => $calculationHelper->getAndamentoDeiMezziPropri(),
                    'Margine Struttura Primario' => $calculationHelper->getMargineStrutturaPrimario(),
                    'Margine Struttura Secondario' => $calculationHelper->getMargineStrutturaSecondario(),
                    'Current Ratio' => $calculationHelper->getCurrentRatio(),
                    'Attivita Passivita A Breve' => ($calculationHelper->getAttivitaPassivitaABreve('Attivita Passivita A Breve')) ? $calculationHelper->getAttivitaPassivitaABreve('Attivita Passivita A Breve')['Attivita_a_breve_Passività_a_Breve_Ordinario'] : false,
                    'Acid Test' => $calculationHelper->getAcidTest(),
                    'Acid Test Ordinario' => $calculationHelper->getAcidTestOrdinario(),
                    'Autonomia Finanziaria' => $calculationHelper->getAutonomiaFinanziaria(),
                    'Livello Investimenti Aziendali' => $calculationHelper->getLivelloInvestimentiAziendali(),
                    'Pfn Ebitda' => $calculationHelper->getPfnEbitda(),
                    'Peso Oneri Finanziari' => $calculationHelper->getPesoOneriFinanziari(),
                    'Copertura Lorda Degli Oneri Finanziari' => $calculationHelper->getCoperturaLordaDegliOneriFinanziari(),
                    'Ebit Of' => $calculationHelper->getEbitOf(),
                    'Costo Del Personale' => $calculationHelper->getCostoDelPersonale(),
                    'Cf Attivo' => $calculationHelper->getCfAttivo(),
                    'Indice Di Indebitamento' => $calculationHelper->getIndiceDiIndebitamento(),
                    'Saldo Debiti Vs Fisco' => $calculationHelper->getSaldoDebitiVsFisco(),
                ]
            ],
            'ValuesFromDb' => $calculationHelper->getMissingVoicesFromDb(),
            'indiceVociMancanti' => $calculationHelper->_missingVoicesArray,
            'labels' => $vocis
        );
    }

    public function valutazioneIndici($data, $tipoAzienda, $currentYear)
    {

        unset($data['Peso_Oneri_Finanziari']);
        $arraySettori = $this->getSettori();
        $arrayRange = $this->getRangeValutazioni();
        $arrayGiudizi = array();
        $arrayIndici = array();
        $arraySoglie = array();
        $scoringAreaBilancio = 0;
        $giudizi = array("rischio_elevato", "situazione_critica", "buono", "ottimo");

        foreach ($arraySettori as $singleSector => $multipleTypes) {
            if (array_key_exists($tipoAzienda, $multipleTypes) && ($tipoAzienda == 'Industria' || $tipoAzienda == 'Commercio' || $tipoAzienda == 'Servizi')) {
                $tipoAzienda = $singleSector;
            }
        }

        foreach ($data as $label => $value) {
            if(!$value) {
                continue;
            }

            $value = (float)str_replace(',', '.', $value);
            $arrayIndici[$label] = $value / 100;

            if ($label == 'ROE') {
                $tassoInflazione = (float)Roe::where('year', $currentYear)->get()->first()->value / 100;
                $value /= 100;
                if ($value < $tassoInflazione + 0.02) {
                    $scoringAreaBilancio += 0.0426 * 0.1;
                    $arrayGiudizi[$label]['Scoring'] = 0.0426 * 0.1;
                    $arrayGiudizi[$label]['Giudizio'] = 'Rischio Elevato';
                } else if ($value >= $tassoInflazione + 0.02 && $value  < $tassoInflazione + 0.03) {
                    $scoringAreaBilancio += 0.0426 * 0.3;
                    $arrayGiudizi[$label]['Scoring'] = 0.0426 * 0.3;
                    $arrayGiudizi[$label]['Giudizio'] = 'Situazione Critica';
                } else if ($value >= $tassoInflazione + 0.03 && $value  < $tassoInflazione + 0.05) {
                    $scoringAreaBilancio += 0.0426 * 0.6;
                    $arrayGiudizi[$label]['Scoring'] = 0.0426 * 0.6;
                    $arrayGiudizi[$label]['Giudizio'] = 'Buono';
                } else if ($value  >= $tassoInflazione + 0.05) {
                    $scoringAreaBilancio += 0.0426 * 1;
                    $arrayGiudizi[$label]['Scoring'] = 0.0426 * 1;
                    $arrayGiudizi[$label]['Giudizio'] = 'Ottimo';
                }
            }



        //  $arraySoglie[$label] = range::where([['range_min', '<', $arrayIndici[$label]], ['range_max', '>', $arrayIndici[$label]], ['indice', '=', $label], ['tipo_azienda', '=', $tipoAzienda]])->with('pesi')->get();
            $arraySoglie[$label] = range::where([['range_min', '<', $arrayIndici[$label]], ['range_max', '>', $arrayIndici[$label]], ['indice', '=', $label], ['tipo_azienda', '=', 'Generica']])->with('pesi')->get();


            if (count($arraySoglie[$label]) > 0) {
                $arrayGiudizi[$label]['Scoring'] = ($arraySoglie[$label][0]->pesi->peso) * ($arraySoglie[$label][0]->score);
                $arrayGiudizi[$label]['Giudizio'] = $arraySoglie[$label][0]->giudizio;
                $scoringAreaBilancio += $arrayGiudizi[$label]['Scoring'];
            }

            $arrayGiudizi[$label] = false;

            if(isset($arrayGiudizi[$label]['Scoring'])) {
                $arrayGiudizi[$label]['Scoring'] = number_format($arrayGiudizi[$label]['Scoring'], 2, ',', '.');
            }

        }

        return array("Score" => number_format($scoringAreaBilancio, 2, ',', '.'), "Giudizi" => $arrayGiudizi);
    }



    public function saveAnalisiBasicToDB($allData, $idBilancio)
    {
        if(str_contains($idBilancio, '"')) {
            $idBilancio = str_replace('"', '', $idBilancio);
        }

        $calcoloDSCR = $this->getCalcoloDSCR($allData);
        $dscrData = $this->getAnalisisDataFull($allData);

        if (isset($calcoloDSCR['error'])) {
            $dscrData['alertDSCR'] = "DSCR Non Calcolabile: dati mancanti";
        }

        if ($calcoloDSCR > 1) {
            $dscrData['alertDSCR'] = 'Azienda non a rischio';
        } else {
            $dscrData['alertDSCR'] = 'Azienda a rischio';
        }

        $dscrData['bilancio_id'] = (int)$idBilancio;

        $balance = Basic::where('bilancio_id', $idBilancio)->get();

        if (count($balance) == 0) {
            Basic::create($dscrData);
        } else {
            Basic::where('bilancio_id', $idBilancio)->update($dscrData);
        }

        return [
            'Message' => "Analisi aggiornata correttamente",
            'AlertDSCR' => $dscrData['alertDSCR'],
            'savedData' => $dscrData
        ];
    }



    public function generateHTMLRender($instance, $taxonomy) {

        $cacheLocation = __DIR__ . "/cache";
        $compiledLocation = $taxonomy; // !!! Change this
        $languageCode = 'it';
        global $use_xbrl_functions;
        $use_xbrl_functions = true;

        try
        {
            if ( ! file_exists( "$compiledLocation" ) ) {
                return false;
            }

            global $reportModelStructureRuleViolations;
            $reportModelStructureRuleViolations = false;
            XBRL_Global::reset();
            XBRL_Types::reset();
            new \XBRL_IFRS();
/*
 *
            $context = XBRL_Global::getInstance();
            if ( ! $context->useCache )
            {
                $context->useCache = true;
                $context->cacheLocation = $cacheLocation;
                $context->initializeCache();
            }
            */
            $document = $instance;

            if ( ! file_exists( $document ) )
            {
                 return false;
            }

            $schemaHRef = $this->getInstanceTaxonomyHRef( $document );
            $compiledTaxonomyFilename = base_path()."/taxonomies/2018-11-04/".$schemaHRef;
            $instance = XBRL_Instance::FromInstanceDocumentWithExtensionTaxonomy( $document, $compiledTaxonomyFilename );
            $formulas = null;
            $results = array();
            $instanceTaxonomy = $instance->getInstanceTaxonomy();
            $dfr = new XBRL_DFR( $instanceTaxonomy );
            $presentationNetworks = $dfr->validateDFR( $formulas, true, $languageCode );
            $dfr->includeCheckboxControls = false;
            $dfr->includeComponent = false;
            $dfr->includeSlicers = false;
            $dfr->includeFactsTable = false;
            $dfr->includeWidthcontrols = false;
            $dfr->includeBusinessRules = false;
            $renders = $dfr->renderPresentationNetworks( $presentationNetworks, $instance, $formulas, false, $languageCode, false, $results );

            $indexHTML =
                "<html>\n" .
                "	<head>\n" .
                "		<title>XBRL Rendered Views Index</title>\n" .
               "		<link rel='stylesheet' id='font-awesome_style-css' href='https://www.xbrlquery.com/wp-content/themes/zerif-pro/assets/css/font-awesome.min.css?ver=v1' type='text/css' media='all'>\n" .
              "		<link rel='stylesheet' id='render-report-css' href='https://piratebuy.it/xbrl-render-report.css'>\n" .
                "		<script src='https://kit.fontawesome.com/d5b3603aa0.js'></script>\n" .
                "		<script type='text/javascript' src='https://code.jquery.com/jquery-1.12.4.min.js'></script>\n" .

                "		<style>\n" .
                "			body { margin-left: 20px; margin-right: 20px; }\n" .
                "		</style>\n" .

                "	</head>\n" .
                "	<body>\n" .

                "";

            $count = 0;
            foreach ( $renders as $role => $render )
            {
                $count++;
                if ( isset( $render['hasReport'] ) && ! $render['hasReport'] ) continue;

                foreach ( $render['entities'] as $entity => $networkHTML )
                {
                    $indexHTML .=
                        "		<div id='primary'>\n" . $networkHTML .
                        "		</div>\n" ;
                }
            }
            $indexHTML .=  "</html>";

            return $indexHTML;
        }
        catch( \Exception $ex )
        {
            echo $ex;
            //echo $ex->getMessage();
            return false;
        }
    }



    public function getInstanceTaxonomyHRef( $filename ) {
        try {
            $dom = new \DOMDocument();

            $dom->load(html_entity_decode($filename, ENT_COMPAT, "UTF-8"));

            $domXPath = new \DOMXPath( $dom );
            $domXPath->registerNamespace( 'xbrli', "http://www.xbrl.org/2003/instance" );
            $domXPath->registerNamespace( 'link', "http://www.w3.org/1999/xlink" );
            $nodes = $domXPath->query("/xbrli:xbrl/link:schemaRef");
            /** @var $domElement DOMElement */
            $domElement = $nodes[0];
            return $domElement->getAttribute('xlink:href');
        } catch(Exception $e) {
            return "itcc-ci-abb-2018-11-04.xsd";
        }
    }

    /**
     * Handler for 'set_error_handler' function
     * @param int $error_level Contains the level of the error raised, as an integer.
     * @param string $error_message Contains the error message, as a string.
     * @param string $error_file Contains the filename that the error was raised in, as a string.
     * @param int $error_line Contains the line number the error was raised at, as an integer.
     * @param array $error_context An array that points to the active symbol table at the point the error occurred
     */
    public function errorHandler( $error_level, $error_message, $error_file, $error_line, $error_context ) {
        $error = array(
            "level" => $error_level,
            "message" => $error_message,
            "file" => $error_file,
            "line" => $error_line,
        );

        switch ( $error_level )
        {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_PARSE:
                $error['class'] = "fatal";
                break;

            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                $error['class'] = "error";
                break;

            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                $error['class'] = "warn";
                break;

            case E_NOTICE:
                return true; // Ignore notices

            case E_USER_NOTICE:
                $error['class'] = "info";
                break;

            case E_STRICT:
                $error['class'] = "debug";
                break;

            default:
                $error['class'] = "warn";
        }

        print_r( $error );
        error_log( print_r( $error, true ) );
    }


    public function getRangeValutazioni()
    {
        return array(
            "Rischio Elevato" => 0.1,
            "Situazione Critica" => 0.3,
            "Buono" => 0.6,
            "Ottimo" => 1
        );
    }

    public function getSettori()
    {
        return array(
            "Industria" => array(
                "ESTRAZIONE, MANIFATTURA, PROD. ENERGIA E GAS" => 1,
                "AGRICOLTURA, SIVICOLTURA E PESCA" => 1,
                "FORN. ACQUA E RETI FOGNARIE RIFIUTI, TRASM. ENERGIA/GAS" => 1,
                "COSTRUZIONE DI EDIFICI" => 1,
                "INGEGNERIA CIVILE, COSTRUZIONI SPECIALIZZATE" => 1
            ),
            "Commercio" => array(
                "COMM INGROSSO e DETT AUTOVEICOLI, COMM INGROSSO, DISTRIB. ENERGIA/GAS" => 1,
                "COMM. DETTAGLIO, BAR E RISTORANTI" => 1
            ),
            "Servizi" => array(
                "SERVIZI ALLE PERSONE" => 1,
                "SERVIZI ALLE IMPRESE" => 1,
                "TRASPORTO E MAGAZZINAGGIO, HOTEL" => 1
            )
        );
    }

    public function getTipiAziende()
    {
        return array(
            "AGRICOLTURA, SIVICOLTURA E PESCA",
            "ESTRAZIONE, MANIFATTURA, PROD. ENERGIA E GAS",
            "FORN. ACQUA E RETI FOGNARIE RIFIUTI, TRASM. ENERGIA/GAS",
            "COSTRUZIONE DI EDIFICI",
            "INGEGNERIA CIVILE, COSTRUZIONI SPECIALIZZATE",
            "COMM INGROSSO e DETT AUTOVEICOLI, COMM INGROSSO, DISTRIB. ENERGIA/GAS",
            "COMM. DETTAGLIO, BAR E RISTORANTI",
            "TRASPORTO E MAGAZZINAGGIO, HOTEL",
            "SERVIZI ALLE IMPRESE",
            "SERVIZI ALLE PERSONE"
        );
    }
}
