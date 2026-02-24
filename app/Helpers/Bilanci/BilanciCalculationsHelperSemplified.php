<?php

namespace App\Helpers\Bilanci;

use Illuminate\Support\Facades\DB;
use stdClass;
use App\Models\CustomLog;
use App\Models\MissingVoice;
use App\Models\AnalisiAgenziaEntrate;
use App\Models\AnalisiInps;
use App\Models\AnalisiRiscossione;
use App\Models\AnalisiRetribuzioni;
use App\Models\AnalisiFornitori;
use App\Models\Document;
use App\Models\Bilanci;
use App\Models\AnalisiDscr;
use Exception;

class BilanciCalculationsHelperSemplified
{

    public $_currentInstance = false;

    public $_missingVoicesArray = array();

    public $_valuesFromDatabase = array();

    public $documentId = false;

    public $codice_documento = false;

    public function setCurrentInstance($instanceDocument)
    {
        $this->_currentInstance = $instanceDocument;
    }

    public function getElementFromBalance($elementName, $period, $indexName)
    {

        $value = false;
        $elements = $this->_currentInstance->getElements();
        $elements = $elements->getElements();

        // query che cerca elemento nel db
        /*$elementoFromQuery = MissingVoice::where('documentId', $this->documentId)
             ->where('period', $period)
             ->where('voiceFullName', $elementName)
             ->get();
             */

        /*    if($elementName == "CostiProduzioneAccantonamentiRischi") {
                echo $elementoFromQuery;
            }*/
        if (isset($elements[$elementName])) {
            if ($period == 1) {
                $value = array_first($elements[$elementName])['value'];
            } else if ($period == 2) {
                $value = array_last($elements[$elementName])['value'];
            }

            return $value;
        } else {
            return 0;
        }
        /*elseif (count($elementoFromQuery) > 0) {
            $data = array(
                $elementName.'_'.$period => $elementoFromQuery->first()->voiceValue
            );
            if (!isset($this->_valuesFromDatabase[$indexName])) {
                $this->_valuesFromDatabase[$indexName] = array();
                $this->_valuesFromDatabase[$indexName][$elementName.'_'.$period] = $elementoFromQuery->first()->voiceValue;
            } else {
                if(!isset($this->_valuesFromDatabase[$indexName][$elementName.'_'.$period])) {
                    $this->_valuesFromDatabase[$indexName][$elementName.'_'.$period] = $elementoFromQuery->first()->voiceValue;
                }
            }

            return $elementoFromQuery->first()->voiceValue;
        } else {

            if (!isset($this->_missingVoicesArray[$indexName])) {
                $this->_missingVoicesArray[$indexName] = array(
                    $period => [],
                );

                array_push($this->_missingVoicesArray[$indexName][$period], $elementName);
            } else {
                if (!isset($this->_missingVoicesArray[$indexName][$period])) {
                    $this->_missingVoicesArray[$indexName][$period] = [];
                    array_push($this->_missingVoicesArray[$indexName][$period], $elementName);
                } else {
                    if (!in_array($elementName, $this->_missingVoicesArray[$indexName][$period])) {
                        array_push($this->_missingVoicesArray[$indexName][$period], $elementName);
                    }
                }
            }

            return false;

        }*/
    }

    public function getMissingVoicesFromDb()
    {
        return $this->_valuesFromDatabase;
    }

    public function getCalcoloDSCR($allData)
    {

        $dscrData = $this->getDSCRArrayData($allData);

        if ($dscrData['DSCR'] != 1) {
            return "DSCR da non calcolare";
        }

        if (empty($dscrData['uscitaDSCRCFmese6']) || $dscrData['uscitaDSCRCFmese6'] == 0 || empty($dscrData['rimborsoDSCRmese1']) || $dscrData['rimborsoDSCRmese1'] == 0) {
            // CustomLog::addToLogBilanciHelper('BilanciHelper', 'GetEmptyCalculateDSCR', json_encode(["uscitaDSCRCFmese6" => $dscrData['uscitaDSCRCFmese6'], "rimborsoDSCRmese1" => $dscrData['rimborsoDSCRmese1']]));
            return ['error' => true];
        }


        return $this->calculateDSCR($dscrData);
    }

    private function getDSCRArrayData($allData)
    {
        $cleanArray = [
            'DSCRDate' => $allData['DSCRDate'],
            'DSCR' => $allData['DSCR'],
            'DSCRdispLiquida' => $allData['DSCRdispLiquida'],
            'entrataDSCRCFmese1' => (isset($allData['entrataDSCRCFmese1'])) ? $allData['entrataDSCRCFmese1'] : null,
            'entrataDSCRCFmese2' => (isset($allData['entrataDSCRCFmese2'])) ? $allData['entrataDSCRCFmese2'] : null,
            'entrataDSCRCFmese3' => (isset($allData['entrataDSCRCFmese3'])) ? $allData['entrataDSCRCFmese3'] : null,
            'entrataDSCRCFmese4' => (isset($allData['entrataDSCRCFmese4'])) ? $allData['entrataDSCRCFmese4'] : null,
            'entrataDSCRCFmese5' => (isset($allData['entrataDSCRCFmese5'])) ? $allData['entrataDSCRCFmese5'] : null,
            'entrataDSCRCFmese6' => (isset($allData['entrataDSCRCFmese6'])) ? $allData['entrataDSCRCFmese6'] : null,
            'uscitaDSCRCFmese1' => (isset($allData['uscitaDSCRCFmese1'])) ? $allData['uscitaDSCRCFmese1'] : null,
            'uscitaDSCRCFmese2' => (isset($allData['uscitaDSCRCFmese2'])) ? $allData['uscitaDSCRCFmese2'] : null,
            'uscitaDSCRCFmese3' => (isset($allData['uscitaDSCRCFmese3'])) ? $allData['uscitaDSCRCFmese3'] : null,
            'uscitaDSCRCFmese4' => (isset($allData['uscitaDSCRCFmese4'])) ? $allData['uscitaDSCRCFmese4'] : null,
            'uscitaDSCRCFmese5' => (isset($allData['uscitaDSCRCFmese5'])) ? $allData['uscitaDSCRCFmese5'] : null,
            'uscitaDSCRCFmese6' => (isset($allData['uscitaDSCRCFmese6'])) ? $allData['uscitaDSCRCFmese6'] : null,
            'rimborsoDSCRmese1' => (isset($allData['rimborsoDSCRmese1'])) ? $allData['rimborsoDSCRmese1'] : null,
            'rimborsoDSCRmese2' => (isset($allData['rimborsoDSCRmese2'])) ? $allData['rimborsoDSCRmese2'] : null,
            'rimborsoDSCRmese3' => (isset($allData['rimborsoDSCRmese3'])) ? $allData['rimborsoDSCRmese3'] : null,
            'rimborsoDSCRmese4' => (isset($allData['rimborsoDSCRmese4'])) ? $allData['rimborsoDSCRmese4'] : null,
            'rimborsoDSCRmese5' => (isset($allData['rimborsoDSCRmese5'])) ? $allData['rimborsoDSCRmese5'] : null,
            'rimborsoDSCRmese6' => (isset($allData['rimborsoDSCRmese6'])) ? $allData['rimborsoDSCRmese6'] : null,
        ];

        return $cleanArray;
    }


    public function getTotalePatrimonioNetto($indexName = "TotalePatrimonioNetto")
    {
        $TotalePatrimonioNetto = $this->getElementFromBalance('TotalePatrimonioNetto', 1, $indexName);

        if (is_bool($TotalePatrimonioNetto) && $TotalePatrimonioNetto === false)
            return false;

        return $TotalePatrimonioNetto;
    }

    public function getPatrimonioNettoNegativo($indexName = "PatrimonioNettoNegativo")
    {
        $TotalePatrimonioNetto = $this->getTotalePatrimonioNetto($indexName);
        $TotaleCreditiVersoSociVersamentiAncoraDovuti = $this->getElementFromBalance('TotaleCreditiVersoSociVersamentiAncoraDovuti', 1, $indexName);
        //  $TotalePatrimonioNetto = str_replace(',', '.', str_replace('.', '', $TotalePatrimonioNetto));

        if (
            (is_bool($TotalePatrimonioNetto) && $TotalePatrimonioNetto === false) ||
            (is_bool($TotaleCreditiVersoSociVersamentiAncoraDovuti) && $TotaleCreditiVersoSociVersamentiAncoraDovuti === false)
        )
            return false;

        $PN_NEGATIVO = $TotalePatrimonioNetto - $TotaleCreditiVersoSociVersamentiAncoraDovuti;
        $calculationPNnegativo = $TotalePatrimonioNetto . ' - ' . $TotaleCreditiVersoSociVersamentiAncoraDovuti . ' = ' . $PN_NEGATIVO;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetPatrimonioNettoNegativo', $calculationPNnegativo);


        return $PN_NEGATIVO;
    }

    public function getOfRicavi($indexName = "OF Ricavi")
    {
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $this->getElementFromBalance('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 1, $indexName);
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1, $indexName);

        if ($ValoreProduzioneRicaviVenditePrestazioni === false || $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari === false) {
            return false;
        }

        $OF_RICAVI = ($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari / $ValoreProduzioneRicaviVenditePrestazioni) * 100;
        $calculation = $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari . ' / ' . $ValoreProduzioneRicaviVenditePrestazioni . ' * ' . 100 . ' = ' . $OF_RICAVI;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetOF_RICAVI', $calculation);

        return $OF_RICAVI;
    }

    public function getTotaleDebiti($indexName = "TotaleDebiti")
    {
        $TotaleDebiti = $this->getElementFromBalance('TotaleDebiti', 1, $indexName);

        if (is_bool($TotaleDebiti) && $TotaleDebiti === false)
            return false;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetTotaleDebiti', $TotaleDebiti);

        return $TotaleDebiti;
    }

    public function getAdeguatezzaPatrimoniale($indexName = "AdeguatezzaPatrimoniale")
    {
        $PN_NEGATIVO = $this->getPatrimonioNettoNegativo($indexName);
        $TotaleDebiti = $this->getTotaleDebiti($indexName);
        $PassivoRateiRisconti = $this->getElementFromBalance('PassivoRateiRisconti', 1, $indexName);

        if (
            (is_bool($PN_NEGATIVO) && $PN_NEGATIVO === false) ||
            ($TotaleDebiti === false) ||
            ($PassivoRateiRisconti === false)
        ) {
            return false;
        }
        $ADEGUATEZZA_PATRIMONIALE = ($PN_NEGATIVO / ($TotaleDebiti + $PassivoRateiRisconti)) * 100;
        $calculation = $PN_NEGATIVO . ' / (' . $TotaleDebiti . ' + ' . $PassivoRateiRisconti . ') * ' . 100;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAdeguatezzaPatrimoniale', $calculation);

        return $ADEGUATEZZA_PATRIMONIALE;
    }

    public function getTotaleCreditiEntroDodiciMesi($indexName = "Totale Crediti Entro Esercizio Successivo")
    {
        $TotaleCreditiEntroDodiciMesi = $this->getElementFromBalance('CreditiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);

        if (!is_bool($TotaleCreditiEntroDodiciMesi)) {
            return $TotaleCreditiEntroDodiciMesi;
        } else {

            $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
            $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
            $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
            $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
            $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
            $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
            $CreditiImposteAnticipateTotaleImposteAnticipate = $this->getElementFromBalance('CreditiImposteAnticipateTotaleImposteAnticipate', 1, $indexName);

            if (
                (is_bool($CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo) && $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo === false) ||
                (is_bool($CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo) && $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo === false) ||
                (is_bool($CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo) && $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo === false) ||
                (is_bool($CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo) && $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo === false) ||
                (is_bool($CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo) && $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo === false) ||
                (is_bool($CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo) && $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo === false) ||
                (is_bool($CreditiImposteAnticipateTotaleImposteAnticipate) && $CreditiImposteAnticipateTotaleImposteAnticipate === false)
            ) {
                return false;
            }


            if (count($this->_missingVoicesArray['Totale Crediti Entro Esercizio Successivo']) > 1) {
                foreach ($this->_missingVoicesArray['Totale Crediti Entro Esercizio Successivo'] as $singleMissingVoices) {
                    if (in_array('CreditiEsigibiliEntroEsercizioSuccessivo', $singleMissingVoices)) {
                        unset($singleMissingVoices);
                    }
                }
            } else {
                unset($this->_missingVoicesArray['Totale Crediti Entro Esercizio Successivo']);
            }

        }


        $TotaleDebitiEntroDodiciMesi = (float) $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + (float) $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float) $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float) $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float) $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + (float) $CreditiImposteAnticipateTotaleImposteAnticipate + (float) $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo;
        $calculation = (float) $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo . ' + ' . (float) $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo . ' + ' . (float) $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo . ' + ' . (float) $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo . ' + ' . (float) $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo . ' + ' . (float) $CreditiImposteAnticipateTotaleImposteAnticipate . ' + ' . (float) $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo . ' = ' . $TotaleDebitiEntroDodiciMesi;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetTotaleCreditiEntroDodiciMesi', $calculation);

        return $TotaleDebitiEntroDodiciMesi;

    }

    public function getTotaleDebitiEntroDodiciMesi($indexName = "Totale Debiti Entro Esercizio Successivo")
    {

        $TotaleDebitiEntroDodiciMesi = $this->getElementFromBalance('DebitiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);

        if (!is_bool($TotaleDebitiEntroDodiciMesi)) {
            return $TotaleDebitiEntroDodiciMesi;
        } else {

            $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
            $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
            $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
            $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
            $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
            $DebitiAccontiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiAccontiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
            $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
            $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
            $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
            $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
            $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
            $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
            $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
            $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);

            if (
                (is_bool($DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo) && $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo === false) ||
                (is_bool($DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo) && $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo === false) ||
                (is_bool($DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo) && $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo === false) ||
                (is_bool($DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo) && $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo === false) ||
                (is_bool($DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo) && $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo === false) ||
                (is_bool($DebitiAccontiEsigibiliEntroEsercizioSuccessivo) && $DebitiAccontiEsigibiliEntroEsercizioSuccessivo === false) ||
                (is_bool($DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo) && $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo === false) ||
                (is_bool($DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo) && $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo === false) ||
                (is_bool($DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo) && $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo === false) ||
                (is_bool($DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo) && $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo === false) ||
                (is_bool($DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo) && $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo === false) ||
                (is_bool($DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo) && $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo === false) ||
                (is_bool($DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo) && $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo === false) ||
                (is_bool($DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo) && $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo === false)
            ) {
                return false;
            }

            if (count($this->_missingVoicesArray['Totale Debiti Entro Esercizio Successivo']) > 1) {
                foreach ($this->_missingVoicesArray['Totale Debiti Entro Esercizio Successivo'] as $singleMissingVoices) {
                    if (in_array('DebitiEsigibiliEntroEsercizioSuccessivo', $singleMissingVoices)) {
                        unset($singleMissingVoices);
                    }
                }
            } else {
                unset($this->_missingVoicesArray['Totale Debiti Entro Esercizio Successivo']);
            }

        }

        $TotaleDebitiEntroDodiciMesi = (float) $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + (float) $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + (float) $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + (float) $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float) $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float) $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float) $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + (float) $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + (float) $DebitiAccontiEsigibiliEntroEsercizioSuccessivo + (float) $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + (float) $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + (float) $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + (float) $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + (float) $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo;

        $calculation = (float) $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo . ' + ' . (float) $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo . ' + ' . (float) $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo . ' + ' . (float) $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo . ' + ' . (float) $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo . ' + ' . (float) $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo . ' + ' . (float) $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo . ' + ' . (float) $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo . ' + ' . (float) $DebitiAccontiEsigibiliEntroEsercizioSuccessivo . ' + ' . (float) $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo . ' + ' . (float) $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo . ' + ' . (float) $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo . ' + ' . (float) $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo . ' + ' . (float) $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo . ' = ' . $TotaleDebitiEntroDodiciMesi;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetTotaleDebitiEntroDodiciMesi', $calculation);

        return $TotaleDebitiEntroDodiciMesi;
    }

    public function getLiquidita($indexName = "Liquidità")
    {
        $CostiProduzioneAccantonamentiRischi = $this->getElementFromBalance('CostiProduzioneAccantonamentiRischi', 1, $indexName);
        $TotaleAttivo = $this->getElementFromBalance('TotaleAttivo', 1, $indexName);
        $UtilePerditaEsercizio = $this->getElementFromBalance('UtilePerditaEsercizio', 1, $indexName);
        $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni = $this->getElementFromBalance('CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 1, $indexName);
        $CostiProduzioneAltriAccantonamenti = $this->getElementFromBalance('CostiProduzioneAltriAccantonamenti', 1, $indexName);

        if (
            (is_bool($CostiProduzioneAccantonamentiRischi) && $CostiProduzioneAccantonamentiRischi === false) ||
            $TotaleAttivo === false ||
            (is_bool($UtilePerditaEsercizio) && $UtilePerditaEsercizio === false) ||
            (is_bool($CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni) && $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni === false) ||
            (is_bool($CostiProduzioneAltriAccantonamenti) && $CostiProduzioneAltriAccantonamenti === false)
        ) {
            return false;
        }


        $LIQUIDITA = (($UtilePerditaEsercizio + $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni + (float) $CostiProduzioneAccantonamentiRischi + (float) $CostiProduzioneAltriAccantonamenti) / (float) $TotaleAttivo) * 100;
        $calculationLIQUIDITA = '((' . $UtilePerditaEsercizio . ' + ' . $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni . ' + ' . (float) $CostiProduzioneAccantonamentiRischi . ' + ' . (float) $CostiProduzioneAltriAccantonamenti . ') / ' . (float) $TotaleAttivo . ') * ' . 100;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetLiquiditÃ ', $calculationLIQUIDITA);

        return $LIQUIDITA;
    }

    public function getIndebitamentoPrevidenzialeTributario($indexName = "Indebitamento Previdenziale Tributario")
    {
        $DebitiDebitiTributariTotaleDebitiTributari = $this->getElementFromBalance('DebitiDebitiTributariTotaleDebitiTributari', 1, $indexName);
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale = $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale', 1, $indexName);
        $TotaleAttivo = $this->getElementFromBalance('TotaleAttivo', 1, $indexName);

        if (
            (is_bool($DebitiDebitiTributariTotaleDebitiTributari) && $DebitiDebitiTributariTotaleDebitiTributari === false) ||
            (is_bool($DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) && $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale === false) ||
            $TotaleAttivo === false
        )
            return false;

        $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO = (($DebitiDebitiTributariTotaleDebitiTributari + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) / $TotaleAttivo) * 100;
        $calculation = '((' . $DebitiDebitiTributariTotaleDebitiTributari . ' + ' . $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale . ') / ' . $TotaleAttivo . ') * ' . 100 . ' = ' . $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetIndebitamentoPrevidenzialeTributario', $calculation);

        return $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO;
    }

    public function getAndamentoDelFatturato($indexName = "Andamento Del Fatturato")
    {
        $ValoreProduzioneRicaviVenditePrestazioniCurr = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1, $indexName);
        $ValoreProduzioneRicaviVenditePrestazioniPrev = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 2, $indexName);

        if ($ValoreProduzioneRicaviVenditePrestazioniCurr === false || $ValoreProduzioneRicaviVenditePrestazioniPrev === false)
            return false;

        $AndamentoDelFatturato = (-(1 - (($ValoreProduzioneRicaviVenditePrestazioniCurr) / ($ValoreProduzioneRicaviVenditePrestazioniPrev)))) * 100;
        $calculation = '(- (' . 1 . ' - ((' . $ValoreProduzioneRicaviVenditePrestazioniCurr . ') / (' . $ValoreProduzioneRicaviVenditePrestazioniPrev . ')))) * ' . 100 . ' = ' . $AndamentoDelFatturato;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAndamentoDelFatturato', $calculation);

        return $AndamentoDelFatturato;
    }

    public function getAndamentoDelMol($indexName = "Andamento Del Mol")
    {
        // Curr
        $TotaleValoreProduzione = $this->getElementFromBalance('TotaleValoreProduzione', 1, $indexName);
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 1, $indexName);
        $CostiProduzioneGodimentoBeniTerzi = $this->getElementFromBalance('CostiProduzioneGodimentoBeniTerzi', 1, $indexName);
        $CostiProduzioneServizi = $this->getElementFromBalance('CostiProduzioneServizi', 1, $indexName);
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getElementFromBalance('CostiProduzionePersonaleTotaleCostiPersonale', 1, $indexName);
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 1, $indexName);
        $CostiProduzioneOneriDiversiGestione = $this->getElementFromBalance('CostiProduzioneOneriDiversiGestione', 1, $indexName);

        // Prev
        $TotaleValoreProduzionePrecedente = $this->getElementFromBalance('TotaleValoreProduzione', 2, $indexName);
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerciPrecedente = $this->getElementFromBalance('CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 2, $indexName);
        $CostiProduzioneGodimentoBeniTerziPrecedente = $this->getElementFromBalance('CostiProduzioneGodimentoBeniTerzi', 2, $indexName);
        $CostiProduzioneServiziPrecedente = $this->getElementFromBalance('CostiProduzioneServizi', 2, $indexName);
        $CostiProduzionePersonaleTotaleCostiPersonalePrecedente = $this->getElementFromBalance('CostiProduzionePersonaleTotaleCostiPersonale', 2, $indexName);
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerciPrecedente = $this->getElementFromBalance('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 2, $indexName);
        $CostiProduzioneOneriDiversiGestionePrecedente = $this->getElementFromBalance('CostiProduzioneOneriDiversiGestione', 2, $indexName);

        if ($TotaleValoreProduzione === false || $CostiProduzioneMateriePrimeSussidiarieConsumoMerci === false || $CostiProduzioneGodimentoBeniTerzi === false || $CostiProduzioneServizi === false || $CostiProduzionePersonaleTotaleCostiPersonale === false || $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci === false || $CostiProduzioneOneriDiversiGestione === false || $TotaleValoreProduzionePrecedente === false || $CostiProduzioneMateriePrimeSussidiarieConsumoMerciPrecedente === false || $CostiProduzioneGodimentoBeniTerziPrecedente === false || $CostiProduzioneServiziPrecedente === false || $CostiProduzionePersonaleTotaleCostiPersonalePrecedente === false || $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerciPrecedente === false || $CostiProduzioneOneriDiversiGestionePrecedente === false) {
            return false;
        }

        $MOLcurr = $TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzioneServizi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione;
        $MOLprev = $TotaleValoreProduzionePrecedente - $CostiProduzioneMateriePrimeSussidiarieConsumoMerciPrecedente - $CostiProduzioneGodimentoBeniTerziPrecedente - $CostiProduzioneServiziPrecedente - $CostiProduzionePersonaleTotaleCostiPersonalePrecedente - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerciPrecedente - $CostiProduzioneOneriDiversiGestionePrecedente;

        $AndamentoMOL = -(1 - ($MOLcurr / $MOLprev)) * 100;
        $calculation = '- (' . 1 . ' - (' . $MOLcurr . ' / ' . $MOLprev . ')) * ' . 100 . ' = ' . $AndamentoMOL;


        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAndamentoDelMol', $calculation);

        $data = [
            'MOLcurr' => $MOLcurr,
            'AndamentoMOL' => $AndamentoMOL,
        ];

        return $data;
    }

    public function getROI($indexName = "ROI")
    {
        $DifferenzaValoreCostiProduzione = $this->getElementFromBalance('DifferenzaValoreCostiProduzione', 1, $indexName);
        $TotaleAttivo = $this->getElementFromBalance('TotaleAttivo', 1, $indexName);

        if ($DifferenzaValoreCostiProduzione === false || $TotaleAttivo === false)
            return false;

        $ROI = ($DifferenzaValoreCostiProduzione / $TotaleAttivo) * 100;
        $calculation = '(' . $DifferenzaValoreCostiProduzione . ' / ' . $TotaleAttivo . ') * ' . 100 . ' = ' . $ROI;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetROI', $calculation);

        return $ROI;
    }

    public function getROS($indexName = "ROS")
    {
        $DifferenzaValoreCostiProduzione = $this->getElementFromBalance('DifferenzaValoreCostiProduzione', 1, $indexName);
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1, $indexName);

        if ($DifferenzaValoreCostiProduzione === false || $ValoreProduzioneRicaviVenditePrestazioni === false)
            return false;

        $ROS = ($DifferenzaValoreCostiProduzione / $ValoreProduzioneRicaviVenditePrestazioni) * 100;
        $calculation = '(' . $DifferenzaValoreCostiProduzione . ' / ' . $ValoreProduzioneRicaviVenditePrestazioni . ') * ' . 100 . ' = ' . $ROS;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetROS', $calculation);

        return $ROS;
    }

    public function getROE($indexName = "ROE")
    {
        $TotalePatrimonioNetto = $this->getElementFromBalance('TotalePatrimonioNetto', 1, $indexName);
        $UtilePerditaEsercizio = $this->getElementFromBalance('UtilePerditaEsercizio', 1, $indexName);

        if ($TotalePatrimonioNetto === false || $UtilePerditaEsercizio === false)
            return false;

        $ROE = ($UtilePerditaEsercizio / $TotalePatrimonioNetto) * 100;
        $calculation = '(' . $UtilePerditaEsercizio . ' / ' . $TotalePatrimonioNetto . ') * ' . 100 . ' = ' . $ROE;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetROE', $calculation);

        return $ROE;
    }

    public function getEbitdaFatturato($indexName = "Ebitda Fatturato")
    {
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1, $indexName);
        $TotaleValoreProduzione = $this->getElementFromBalance('TotaleValoreProduzione', 1, 'Ebitda Fatturato');
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 1, $indexName);
        $CostiProduzioneServizi = $this->getElementFromBalance('CostiProduzioneServizi', 1, $indexName);
        $CostiProduzioneGodimentoBeniTerzi = $this->getElementFromBalance('CostiProduzioneGodimentoBeniTerzi', 1, $indexName);
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getElementFromBalance('CostiProduzionePersonaleTotaleCostiPersonale', 1, $indexName);
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 1, $indexName);
        $CostiProduzioneOneriDiversiGestione = $this->getElementFromBalance('CostiProduzioneOneriDiversiGestione', 1, $indexName);

        if ($ValoreProduzioneRicaviVenditePrestazioni === false || $TotaleValoreProduzione === false || $CostiProduzioneMateriePrimeSussidiarieConsumoMerci === false || $CostiProduzioneServizi === false || $CostiProduzioneGodimentoBeniTerzi === false || $CostiProduzionePersonaleTotaleCostiPersonale === false || $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci === false || $CostiProduzioneOneriDiversiGestione === false)
            return false;

        $EBITDA_FATTURATO = (($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / $ValoreProduzioneRicaviVenditePrestazioni) * 100;
        $calculation = '((' . $TotaleValoreProduzione . ' - ' . $CostiProduzioneMateriePrimeSussidiarieConsumoMerci . ' - ' . $CostiProduzioneServizi . ' - ' . $CostiProduzioneGodimentoBeniTerzi . ' - ' . $CostiProduzionePersonaleTotaleCostiPersonale . ' - ' . $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci . ' - ' . $CostiProduzioneOneriDiversiGestione . ') / ' . $ValoreProduzioneRicaviVenditePrestazioni . ') * ' . 100 . ' = ' . $EBITDA_FATTURATO;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetEbitdaFatturato', $calculation);

        return $EBITDA_FATTURATO;
    }

    public function getAndamentoDeiMezziPropri($indexName = "Andamento Dei Mezzi Propri")
    {
        $TotalePatrimonioNettoCurr = $this->getElementFromBalance('TotalePatrimonioNetto', 1, $indexName);
        $TotalePatrimonioNettoPrev = $this->getElementFromBalance('TotalePatrimonioNetto', 2, $indexName);

        if ($TotalePatrimonioNettoCurr === false || $TotalePatrimonioNettoPrev === false)
            return false;

        $AndamentoDeiMezziPropri = (($TotalePatrimonioNettoCurr / $TotalePatrimonioNettoPrev) - 1) * 100;
        $calculation = '((' . $TotalePatrimonioNettoCurr . ' / ' . 1 . ') - ' . $TotalePatrimonioNettoPrev . ') * ' . 100 . ' = ' . $AndamentoDeiMezziPropri;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAndamentoDeiMezziPropri', $calculation);

        return $AndamentoDeiMezziPropri;
    }

    public function getMargineStrutturaPrimario($indexName = "Margine Struttura Primario")
    {
        $TotaleImmobilizzazioni = $this->getElementFromBalance('TotaleImmobilizzazioni', 1, $indexName);
        $TotalePatrimonioNetto = $this->getElementFromBalance('TotalePatrimonioNetto', 1, $indexName);

        if ($TotaleImmobilizzazioni === false || $TotalePatrimonioNetto === false)
            return false;

        $Margine_Struttura_Primario = (float) ($TotalePatrimonioNetto / $TotaleImmobilizzazioni) * 100;
        $calculation = '(' . (float) $TotalePatrimonioNetto . ' / ' . $TotaleImmobilizzazioni . ') * ' . 100 . ' = ' . $Margine_Struttura_Primario;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetMargineStrutturaPrimario', $calculation);

        return $Margine_Struttura_Primario;
    }

    public function getMargineStrutturaSecondario($indexName = "Margine Struttura Secondario")
    {
        $TrattamentoFineRapportoLavoroSubordinato = $this->getElementFromBalance('TrattamentoFineRapportoLavoroSubordinato', 1, $indexName);
        $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 1, $indexName);
        $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 1, $indexName) != 0 ? $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 1, $indexName) : $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 2, $indexName));
        $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 1, $indexName);
        $DebitiAccontiEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiAccontiEsigibiliOltreEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo', 1, $indexName);
        $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo = ($this->getElementFromBalance('DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo', 1, $indexName) != 0 ? $this->getElementFromBalance('DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo', 1, $indexName) : $this->getElementFromBalance('DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo', 2, $indexName));
        $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo', 1, $indexName) != 0 ? $this->getElementFromBalance('DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo', 1, $indexName) : $this->getElementFromBalance('DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo', 2, $indexName));
        $TotalePatrimonioNetto = $this->getElementFromBalance('TotalePatrimonioNetto', 1, $indexName);
        $TotaleImmobilizzazioni = $this->getElementFromBalance('TotaleImmobilizzazioni', 1, $indexName);

        if ($TrattamentoFineRapportoLavoroSubordinato === false || $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo === false || $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo === false || $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo === false || $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo === false || $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo === false || $DebitiAccontiEsigibiliOltreEsercizioSuccessivo === false || $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo === false || $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo === false || $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo === false || $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo === false || $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo === false || $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo === false || $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo === false || $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo === false || $TotalePatrimonioNetto === false || $TotaleImmobilizzazioni === false)
            return false;

        $Margine_Struttura_Secondario_Semplificato = (($TotalePatrimonioNetto + $TrattamentoFineRapportoLavoroSubordinato + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo + $DebitiAccontiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) / $TotaleImmobilizzazioni) * 100;
        $calculation = '((' . $TotalePatrimonioNetto . ' + ' . $TrattamentoFineRapportoLavoroSubordinato . ' + ' . $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiAccontiEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo . ') / ' . $TotaleImmobilizzazioni . ') * ' . 100 . ' = ' . $Margine_Struttura_Secondario_Semplificato;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetMargineStrutturaSecondario', $calculation);

        return $Margine_Struttura_Secondario_Semplificato;
    }

    public function getCurrentRatio($indexName = "Current Ratio")
    {

        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1, $indexName);
        $AttivoRateiRisconti = $this->getElementFromBalance('AttivoRateiRisconti', 1, $indexName);
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getElementFromBalance('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 1, $indexName);
        $TotaleRimanenze = $this->getElementFromBalance('TotaleRimanenze', 1, $indexName);

        $TotaleCreditiEntroDodiciMesi = $this->getTotaleCreditiEntroDodiciMesi();
        $TotaleDebitiEntroDodiciMesi = $this->getTotaleDebitiEntroDodiciMesi();
        $PassivoRateiRisconti = $this->getElementFromBalance('PassivoRateiRisconti', 1, $indexName);

        if (
            (is_bool($TotaleDisponibilitaLiquide) && $TotaleDisponibilitaLiquide === false) ||
            (is_bool($AttivoRateiRisconti) && $AttivoRateiRisconti === false) ||
            (is_bool($TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni) && $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni === false) ||
            (is_bool($TotaleRimanenze) && $TotaleRimanenze === false) ||
            (is_bool($TotaleCreditiEntroDodiciMesi) && $TotaleCreditiEntroDodiciMesi === false) ||
            $TotaleDebitiEntroDodiciMesi === false &&
            $PassivoRateiRisconti === false
        )
            return false;

        $formula = ($TotaleDisponibilitaLiquide + $AttivoRateiRisconti + $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + $TotaleRimanenze + $TotaleCreditiEntroDodiciMesi) / ($TotaleDebitiEntroDodiciMesi + $PassivoRateiRisconti);
        $calculation = '(' . $TotaleDisponibilitaLiquide . ' + ' . $AttivoRateiRisconti . ' + ' . $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni . ' + ' . $TotaleRimanenze . ' + ' . $TotaleCreditiEntroDodiciMesi . ') / (' . $TotaleDebitiEntroDodiciMesi . ' + ' . $PassivoRateiRisconti . ') = ' . $formula;

        $RITORNO_LIQUIDO_ATTIVO = $formula * 100;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetCurrentRatio', $calculation);

        return $RITORNO_LIQUIDO_ATTIVO;
    }

    public function getAttivitaPassivitaABreve($indexName = "Attivita Passivita a Breve")
    {
        // TRENTACINQUE
        $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 1, $indexName) ? $this->getElementFromBalance('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 1, $indexName) : $this->getElementFromBalance('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 2, $indexName));
        $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 1, $indexName) ? $this->getElementFromBalance('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 1, $indexName) : $this->getElementFromBalance('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 2, $indexName));
        $TrentaCinque = $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo;


        // QUARANTANOVE
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 1, $indexName) ? $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 1, $indexName) : $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 2, $indexName));
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiAccontiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiAccontiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 1, $indexName) ? $this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 1, $indexName) : $this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 2, $indexName));
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 1, $indexName) ? $this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 1, $indexName) : $this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 2, $indexName));
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 1, $indexName) ? $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 1, $indexName) : $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 2, $indexName));
        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);

        $PassivoRateiRisconti = $this->getElementFromBalance('PassivoRateiRisconti', 1, $indexName);
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1, $indexName);
        $TotaleRimanenze = $this->getElementFromBalance('TotaleRimanenze', 1, $indexName);
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getElementFromBalance('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 1, $indexName);
        $AttivoRateiRisconti = $this->getElementFromBalance('AttivoRateiRisconti', 1, $indexName);

        $QuarantaNove = $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiAccontiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo;
        $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);

        if ($CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo === false || $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo === false || $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo === false || $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo === false || $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo === false || $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo === false || $DebitiAccontiEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo === false || $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo === false || $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo === false || $PassivoRateiRisconti === false || $TotaleDisponibilitaLiquide === false || $TotaleRimanenze === false || $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni === false || $AttivoRateiRisconti === false || $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo === false)
            return false;

        $Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore = $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiAccontiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $PassivoRateiRisconti;

        $Attivita_a_breve_Passivita_a_Breve_Ordinario = ((($TotaleDisponibilitaLiquide + $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo + $TotaleRimanenze + $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + $AttivoRateiRisconti) / ($Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore))) * 100;
        $calculation = '(((' . $TotaleDisponibilitaLiquide . ' + ' . $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo . ' + ' . $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo . ' + ' . $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo . ' + ' . $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo . ' + ' . $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo . ' + ' . $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo . ' + ' . $TotaleRimanenze . ' + ' . $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni . ' + ' . $AttivoRateiRisconti . ') / (' . $Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore . '))) * ' . 100 . ' = ' . $Attivita_a_breve_Passivita_a_Breve_Ordinario;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAttivitaPassivitaABreve', $calculation);

        $data = [
            'Attivita_a_breve_Passività_a_Breve_Ordinario' => $Attivita_a_breve_Passivita_a_Breve_Ordinario,
            'QuarantaNove' => $QuarantaNove,
            'TrentaCinque' => $TrentaCinque
        ];

        return $data;
    }

    public function getAcidTest($indexName = "Acid Test")
    {
        $DebitiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $PassivoRateiRisconti = $this->getElementFromBalance('PassivoRateiRisconti', 1, $indexName);
        $TotaleCrediti = $this->getElementFromBalance('TotaleCrediti', 1, $indexName);
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getElementFromBalance('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 1, $indexName);
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1, $indexName);
        $AttivoRateiRisconti = $this->getElementFromBalance('AttivoRateiRisconti', 1, $indexName);

        if ($DebitiEsigibiliEntroEsercizioSuccessivo === false || $PassivoRateiRisconti === false || $TotaleCrediti === false || $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni === false || $TotaleDisponibilitaLiquide === false || $AttivoRateiRisconti === false)
            return false;

        $AcidTest = (((float) $TotaleCrediti + (float) $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float) $TotaleDisponibilitaLiquide + (float) $AttivoRateiRisconti) / ((float) $DebitiEsigibiliEntroEsercizioSuccessivo + (float) $PassivoRateiRisconti));
        $calculation = '((' . (float) $TotaleCrediti . ' + ' . (float) $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni . ' + ' . (float) $TotaleDisponibilitaLiquide . ' + ' . (float) $AttivoRateiRisconti . ') / (' . (float) $DebitiEsigibiliEntroEsercizioSuccessivo . ' + ' . (float) $PassivoRateiRisconti . ')) = ' . $AcidTest;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAcidTest', $calculation);

        return $AcidTest;
    }

    public function getAcidTestSemplificato($indexName = "Acid Test Semplificato")
    {
        $getAttivitaPassivitaABreve = $this->getAttivitaPassivitaABreve($indexName);

        $TotaleRimanenze = $this->getElementFromBalance('TotaleRimanenze', 1, $indexName);
        $PassivoRateiRisconti = $this->getElementFromBalance('PassivoRateiRisconti', 1, $indexName);
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1, $indexName);
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getElementFromBalance('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 1, $indexName);
        $AttivoRateiRisconti = $this->getElementFromBalance('AttivoRateiRisconti', 1, $indexName);

        if ($getAttivitaPassivitaABreve === false || $TotaleRimanenze === false || $PassivoRateiRisconti === false || $TotaleDisponibilitaLiquide === false || $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni === false || $AttivoRateiRisconti === false)
            return false;

        $QuarantaNove = $getAttivitaPassivitaABreve['QuarantaNove'];
        $TrentaCinque = $getAttivitaPassivitaABreve['TrentaCinque'];

        $ACID_TEST_Semplificato = ((((float) $TotaleDisponibilitaLiquide + (float) $TrentaCinque + (float) $TotaleRimanenze + (float) $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float) $AttivoRateiRisconti - (float) $TotaleRimanenze) / ((float) $QuarantaNove + (float) $PassivoRateiRisconti)));
        $calculation = '(((' . (float) $TotaleDisponibilitaLiquide . ' + ' . (float) $TrentaCinque . ' + ' . (float) $TotaleRimanenze . ' + ' . (float) $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni . ' + ' . (float) $AttivoRateiRisconti . ' - ' . (float) $TotaleRimanenze . ') / (' . (float) $QuarantaNove . ' + ' . (float) $PassivoRateiRisconti . '))) = ' . $ACID_TEST_Semplificato;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAcidTestSemplificato', $calculation);

        return $ACID_TEST_Semplificato;
    }

    public function getAcidTestOrdinario($indexName = "Acid Test Ordinario")
    {
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 1, $indexName) ? $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 1, $indexName) : $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 2, $indexName));
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiAccontiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiAccontiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 1, $indexName) ? $this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 1, $indexName) : $this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 2, $indexName));
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 1, $indexName) ? $this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 1, $indexName) : $this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 2, $indexName));
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 1, $indexName) ? $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 1, $indexName) : $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 2, $indexName));
        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 1, $indexName);

        $PassivoRateiRisconti = $this->getElementFromBalance('PassivoRateiRisconti', 1, $indexName);
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1, $indexName);
        $TotaleRimanenze = $this->getElementFromBalance('TotaleRimanenze', 1, $indexName);
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getElementFromBalance('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 1, $indexName);
        $AttivoRateiRisconti = $this->getElementFromBalance('AttivoRateiRisconti', 1, $indexName);

        if ($DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo === false || $DebitiAccontiEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo === false || $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo === false || $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo === false || $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo === false || $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo === false || $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo === false || $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo === false || $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo === false || $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo === false || $PassivoRateiRisconti === false || $TotaleDisponibilitaLiquide === false || $TotaleRimanenze === false || $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni === false || $AttivoRateiRisconti === false)
            return false;

        $ACID_TEST_Ordinario_divisore = $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiAccontiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $PassivoRateiRisconti;

        $ACID_TEST_Ordinario = ((($TotaleDisponibilitaLiquide + $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo + $TotaleRimanenze + $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + $AttivoRateiRisconti - $TotaleRimanenze) / ($ACID_TEST_Ordinario_divisore))) * 100;
        $calculation = '(((' . $TotaleDisponibilitaLiquide . ' + ' . $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo . ' + ' . $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo . ' + ' . $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo . ' + ' . $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo . ' + ' . $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo . ' + ' . $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo . ' + ' . $TotaleRimanenze . ' + ' . $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni . ' + ' . $AttivoRateiRisconti . ' - ' . $TotaleRimanenze . ') / (' . $ACID_TEST_Ordinario_divisore . '))) * ' . 100 . ' = ' . $ACID_TEST_Ordinario;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAcidTestOrdinario', $calculation);

        return $ACID_TEST_Ordinario;
    }

    public function getAutonomiaFinanziaria($indexName = "Autonomia Finanziari")
    {
        $TotalePatrimonioNetto = $this->getTotalePatrimonioNetto($indexName);
        $TotaleDebiti = $this->getElementFromBalance('TotaleDebiti', 1, $indexName);

        if ($TotalePatrimonioNetto === false || $TotaleDebiti === false)
            return false;

        $AUTONOMIA_FINANZIARIA = (float) (($TotalePatrimonioNetto / ($TotalePatrimonioNetto + $TotaleDebiti))) * 100;
        $calculation = '((' . (float) $TotalePatrimonioNetto . ' / (' . $TotalePatrimonioNetto . ' + ' . $TotaleDebiti . '))) * ' . 100 . ' = ' . $AUTONOMIA_FINANZIARIA;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAutonomiaFinanziaria', $calculation);

        return $AUTONOMIA_FINANZIARIA;
    }

    public function getLivelloInvestimentiAziendali($indexName = "Livello Investimenti Aziendali")
    {
        $TotalePatrimonioNetto = $this->getTotalePatrimonioNetto($indexName);
        $TotaleAttivo = $this->getElementFromBalance('TotaleAttivo', 1, $indexName);

        if ($TotalePatrimonioNetto === false || $TotaleAttivo === false)
            return false;

        $LIVELLO_INVESTIMENTI_AZIENDALI = (float) ($TotalePatrimonioNetto / $TotaleAttivo) * 100;
        $calculation = '(' . (float) $TotalePatrimonioNetto . ' / ' . $TotaleAttivo . ') * ' . 100 . ' = ' . $LIVELLO_INVESTIMENTI_AZIENDALI;


        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetLivelloInvestimentiAziendali', $calculation);

        return $LIVELLO_INVESTIMENTI_AZIENDALI;
    }

    public function getPfnEbitda($indexName = "Pfn Ebitda")
    {
        $getAndamentoDelMol = $this->getAndamentoDelMol($indexName);
        // $MOLcurr = number_format($getAndamentoDelMol['MOLcurr'], '.', ',');

        $ImmobilizzazioniFinanziarieCreditiTotaleCrediti = $this->getElementFromBalance('ImmobilizzazioniFinanziarieCreditiTotaleCrediti', 1, $indexName);
        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 1, $indexName);
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 1, $indexName);
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1, $indexName);

        if ($ImmobilizzazioniFinanziarieCreditiTotaleCrediti === false || $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo === false || $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo === false || $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo === false || $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo === false || $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo === false || $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo === false || $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo === false || $TotaleDisponibilitaLiquide === false)
            return false;

        $MOLcurr = $getAndamentoDelMol['MOLcurr'];


        $debitiFinanziariCurr = $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;

        $PFN_EBITDA = (($debitiFinanziariCurr - $TotaleDisponibilitaLiquide - $ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / $MOLcurr);
        $calculation = '((' . $debitiFinanziariCurr . ' - ' . $TotaleDisponibilitaLiquide . ' - ' . $ImmobilizzazioniFinanziarieCreditiTotaleCrediti . ') / ' . $MOLcurr . ') = ' . $PFN_EBITDA . ' * ' . 100;


        $PFN_EBITDA = $PFN_EBITDA * 100;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetPfnEbitda', $calculation);

        return $PFN_EBITDA;
    }

    public function getPesoOneriFinanziari($indexName = "Peso Oneri Finanziari")
    {
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1, $indexName);
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $this->getElementFromBalance('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 1, $indexName);


        if ($ValoreProduzioneRicaviVenditePrestazioni === false || $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari === false)
            return false;


        $Peso_Oneri_Finanziari = ($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari / $ValoreProduzioneRicaviVenditePrestazioni) * 100;
        $calculation = '(' . $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari . ' / ' . $ValoreProduzioneRicaviVenditePrestazioni . ') * ' . 100 . ' = ' . $Peso_Oneri_Finanziari;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetPesoOneriFinanziari', $calculation);

        return $Peso_Oneri_Finanziari;
    }

    public function getCoperturaLordaDegliOneriFinanziari($indexName = 'Copertura Lorda Degli Oneri Finanziari')
    {
        $TotaleValoreProduzione = $this->getElementFromBalance('TotaleValoreProduzione', 1, $indexName);
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 1, $indexName);
        $CostiProduzioneGodimentoBeniTerzi = $this->getElementFromBalance('CostiProduzioneGodimentoBeniTerzi', 1, $indexName);
        $CostiProduzioneServizi = $this->getElementFromBalance('CostiProduzioneServizi', 1, $indexName);
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getElementFromBalance('CostiProduzionePersonaleTotaleCostiPersonale', 1, $indexName);
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 1, $indexName);
        $CostiProduzioneOneriDiversiGestione = $this->getElementFromBalance('CostiProduzioneOneriDiversiGestione', 1, $indexName);
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $this->getElementFromBalance('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 1, $indexName);

        if ($TotaleValoreProduzione === false || $CostiProduzioneMateriePrimeSussidiarieConsumoMerci === false || $CostiProduzioneGodimentoBeniTerzi === false || $CostiProduzioneServizi === false || $CostiProduzionePersonaleTotaleCostiPersonale === false || $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci === false || $CostiProduzioneOneriDiversiGestione === false || $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari === false)
            return false;

        $Copertura_Lorda_degli_Oneri_Finanziari = (($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari);
        $calculation = '((' . $TotaleValoreProduzione . ' - ' . $CostiProduzioneMateriePrimeSussidiarieConsumoMerci . ' - ' . $CostiProduzioneServizi . ' - ' . $CostiProduzioneGodimentoBeniTerzi . ' - ' . $CostiProduzionePersonaleTotaleCostiPersonale . ' - ' . $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci . ' - ' . $CostiProduzioneOneriDiversiGestione . ') / ' . $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari . ') = ' . (float) $Copertura_Lorda_degli_Oneri_Finanziari . ' * ' . 100;

        $Copertura_Lorda_degli_Oneri_Finanziari = (float) $Copertura_Lorda_degli_Oneri_Finanziari * 100;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetCoperturaLordaDegliOneriFinanziari', $calculation);

        return $Copertura_Lorda_degli_Oneri_Finanziari;
    }

    public function getEbitOf($indexName = 'Ebit Of')
    {
        $TotaleValoreProduzione = $this->getElementFromBalance('TotaleValoreProduzione', 1, $indexName);
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 1, $indexName);
        $CostiProduzioneGodimentoBeniTerzi = $this->getElementFromBalance('CostiProduzioneGodimentoBeniTerzi', 1, $indexName);
        $CostiProduzioneServizi = $this->getElementFromBalance('CostiProduzioneServizi', 1, $indexName);
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getElementFromBalance('CostiProduzionePersonaleTotaleCostiPersonale', 1, $indexName);
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 1, $indexName);
        $CostiProduzioneOneriDiversiGestione = $this->getElementFromBalance('CostiProduzioneOneriDiversiGestione', 1, $indexName);
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $this->getElementFromBalance('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 1, $indexName);
        $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni = $this->getElementFromBalance('CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 1, $indexName);
        $CostiProduzioneAccantonamentiRischi = $this->getElementFromBalance('CostiProduzioneAccantonamentiRischi', 1, $indexName);
        $CostiProduzioneAltriAccantonamenti = $this->getElementFromBalance('CostiProduzioneAltriAccantonamenti', 1, $indexName);

        if ($TotaleValoreProduzione === false || $CostiProduzioneMateriePrimeSussidiarieConsumoMerci === false || $CostiProduzioneGodimentoBeniTerzi === false || $CostiProduzioneServizi === false || $CostiProduzionePersonaleTotaleCostiPersonale === false || $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci === false || $CostiProduzioneOneriDiversiGestione === false || $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari === false || $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni === false || $CostiProduzioneAccantonamentiRischi === false || $CostiProduzioneAltriAccantonamenti === false)
            return false;

        $EBIT_OF = (($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione - $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - $CostiProduzioneAccantonamentiRischi - $CostiProduzioneAltriAccantonamenti) / $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari);
        $calculation = '((' . $TotaleValoreProduzione . ' - ' . $CostiProduzioneMateriePrimeSussidiarieConsumoMerci . ' - ' . $CostiProduzioneServizi . ' - ' . $CostiProduzioneGodimentoBeniTerzi . ' - ' . $CostiProduzionePersonaleTotaleCostiPersonale . ' - ' . $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci . ' - ' . $CostiProduzioneOneriDiversiGestione . ' - ' . $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni . ' - ' . $CostiProduzioneAccantonamentiRischi . ' - ' . $CostiProduzioneAltriAccantonamenti . ') / ' . $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari . ')  ' . (float) $EBIT_OF . ' * ' . 100 . ' = ' . (float) $EBIT_OF * 100;

        $EBIT_OF = (float) $EBIT_OF * 100;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetEbitOf', $calculation);

        return $EBIT_OF;
    }

    public function getCostoDelPersonale($indexName = 'Costo Del Personale')
    {
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getElementFromBalance('CostiProduzionePersonaleTotaleCostiPersonale', 1, $indexName);
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1, $indexName);

        if ($CostiProduzionePersonaleTotaleCostiPersonale === false || $ValoreProduzioneRicaviVenditePrestazioni === false)
            return false;

        $Costo_del_personale = (float) ($CostiProduzionePersonaleTotaleCostiPersonale / $ValoreProduzioneRicaviVenditePrestazioni) * 100;
        $calculation = '(' . (float) $CostiProduzionePersonaleTotaleCostiPersonale . ' / ' . $ValoreProduzioneRicaviVenditePrestazioni . ') * ' . 100 . ' = ' . $Costo_del_personale;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetCostoDelPersonale', $calculation);

        return $Costo_del_personale;
    }

    public function getCfAttivo($indexName = 'CF Attivo')
    {
        $TotaleAttivo = $this->getElementFromBalance('TotaleAttivo', 1, $indexName);
        $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni = $this->getElementFromBalance('CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 1, $indexName);
        $CostiProduzioneAccantonamentiRischi = $this->getElementFromBalance('CostiProduzioneAccantonamentiRischi', 1, $indexName);
        $CostiProduzioneAltriAccantonamenti = $this->getElementFromBalance('CostiProduzioneAltriAccantonamenti', 1, $indexName);
        $UtilePerditaEsercizio = $this->getElementFromBalance('UtilePerditaEsercizio', 1, $indexName);
        $imposteRedditoEsercizioImposteAnticipate = $this->getElementFromBalance('ImposteRedditoEsercizioCorrentiDifferiteAnticipateImposteDifferiteAnticipate', 1, $indexName);

        if ($TotaleAttivo === false || $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni === false || $CostiProduzioneAccantonamentiRischi === false || $CostiProduzioneAltriAccantonamenti === false || $UtilePerditaEsercizio === false || $imposteRedditoEsercizioImposteAnticipate === false)
            return false;

        $CF_ATTIVO = (($UtilePerditaEsercizio + $CostiProduzioneAccantonamentiRischi + $CostiProduzioneAltriAccantonamenti + $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - $imposteRedditoEsercizioImposteAnticipate) / $TotaleAttivo) * 100;
        $calculation = '((' . $UtilePerditaEsercizio . ' + ' . $CostiProduzioneAccantonamentiRischi . ' + ' . $CostiProduzioneAltriAccantonamenti . ' + ' . $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni . ' - ' . $imposteRedditoEsercizioImposteAnticipate . ') / ' . $TotaleAttivo . ') * ' . 100;


        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetCfAttivo', $calculation);

        return $CF_ATTIVO;
    }

    public function getIndiceDiIndebitamento($indexName = 'Indice di Indebitamento')
    {
        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 1, $indexName);
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 1, $indexName);
        $TotalePatrimonioNetto = $this->getTotalePatrimonioNetto($indexName);
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1, $indexName);
        $ImmobilizzazioniFinanziarieCreditiTotaleCrediti = $this->getElementFromBalance('ImmobilizzazioniFinanziarieCreditiTotaleCrediti', 1, $indexName);

        if ($DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo === false || $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo === false || $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo === false || $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo === false || $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo === false || $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo === false || $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo === false || $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo === false || $TotalePatrimonioNetto === false || $TotaleDisponibilitaLiquide === false || $ImmobilizzazioniFinanziarieCreditiTotaleCrediti === false)
            return false;

        $MOLannoCorrente = $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;
        $Indice_di_Indebitamento = (($MOLannoCorrente - $TotaleDisponibilitaLiquide - $ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / $TotalePatrimonioNetto) * 100;
        $calculation = '((' . $MOLannoCorrente . ' - ' . $TotaleDisponibilitaLiquide . ' - ' . $ImmobilizzazioniFinanziarieCreditiTotaleCrediti . ') / ' . $TotalePatrimonioNetto . ') * ' . 100 . ' = ' . $Indice_di_Indebitamento;
        $dataAnalisis['Indice_di_Indebitamento'] = $Indice_di_Indebitamento;


        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetIndiceDiIndebitamento', $calculation);

        return $Indice_di_Indebitamento;
    }

    public function getSaldoDebitiVsFisco($indexName = 'Saldo Debiti Vs Fisco')
    {
        $DebitiDebitiTributariTotaleDebitiTributariCorrente = $this->getElementFromBalance('DebitiDebitiTributariTotaleDebitiTributari', 1, $indexName);
        $FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente = $this->getElementFromBalance('FondiRischiOneriTrattamentoQuiescenzaObblighiSimili', 1, $indexName);
        $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipatePrecedente = $this->getElementFromBalance('ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate', 2, $indexName);
        $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate = $this->getElementFromBalance('ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate', 1, $indexName);

        if ($DebitiDebitiTributariTotaleDebitiTributariCorrente === false || $FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente === false || $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipatePrecedente === false || $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate === false)
            return false;

        $DifferenzaImposteReddito = ($ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate + $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipatePrecedente) / 2;

        $SaldoDebitiVSFisco = ($FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente + $DebitiDebitiTributariTotaleDebitiTributariCorrente) / $DifferenzaImposteReddito;
        $calculation = '(' . $FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente . ' + ' . $DebitiDebitiTributariTotaleDebitiTributariCorrente . ') / ' . $DifferenzaImposteReddito . ' = ' . $SaldoDebitiVSFisco . ' * ' . 100 . ' = ' . $SaldoDebitiVSFisco * 100;


        $SaldoDebitiVSFisco = $SaldoDebitiVSFisco * 100;

        //  CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetSaldoDebitiVsFisco', $calculation);

        return $SaldoDebitiVSFisco;
    }




    public function getTipoAzienda()
    {
        return "FORN. ACQUA E RETI FOGNARIE RIFIUTI, TRASM. ENERGIA/GAS";
    }







    public function getSostenibilitaOneriFinanziari()
    {

        $returnData = array(
            'fuoriSoglia' => false,
            'value' => false
        );
        $tipoAzienda = $this->getTipoAzienda();
        $of_fatturato = $this->getOfRicavi('SostenibilitaOneriFinanziari');

        if ($of_fatturato === false)
            return false;

        $returnData['value'] = $of_fatturato;

        $sostenibilitaOneriFinanziari = DB::table('rangesBasic')
            ->where('tipo_azienda', '=', $tipoAzienda)
            ->where('indice', '=', 'Sostenibilità Oneri Finanziari')
            ->where('soglia', '>', $of_fatturato)
            ->count();

        if ($sostenibilitaOneriFinanziari === false) {
            $returnData['fuoriSoglia'] = true;
        }

        return $returnData;
    }

    public function getAdeguatezzaPatrimonialeEvaluation()
    {

        $returnData = array(
            'fuoriSoglia' => false,
            'value' => false
        );
        $tipoAzienda = $this->getTipoAzienda();
        $adeguatezza_patrimoniale = $this->getAdeguatezzaPatrimoniale('AdeguatezzaPatrimoniale');

        if ($adeguatezza_patrimoniale === false)
            return false;

        $returnData['value'] = $adeguatezza_patrimoniale;

        $evaluation = DB::table('rangesBasic')
            ->where('tipo_azienda', '=', $tipoAzienda)
            ->where('indice', '=', 'Adeguatezza Patrimoniale')
            ->where('soglia', '>', $adeguatezza_patrimoniale)
            ->count();

        if ($evaluation) {
            $returnData['fuoriSoglia'] = true;
        }

        return $returnData;
    }

    public function getLiquiditaEvaluation()
    {

        $returnData = array(
            'fuoriSoglia' => false,
            'value' => false
        );
        $tipoAzienda = $this->getTipoAzienda();
        $liquidita = $this->getLiquidita();

        if ($liquidita === false)
            return false;

        $returnData['value'] = $liquidita;

        $evaluation = DB::table('rangesBasic')
            ->where('tipo_azienda', '=', $tipoAzienda)
            ->where('indice', '=', 'Liquidità')
            ->where('soglia', '>', $liquidita)
            ->count();

        if ($evaluation) {
            $returnData['fuoriSoglia'] = true;
        }

        return $returnData;
    }

    public function getIndebitamentoPrevidenziale()
    {

        $returnData = array(
            'fuoriSoglia' => false,
            'value' => false
        );
        $tipoAzienda = $this->getTipoAzienda();
        $indebitamentoTributario = $this->getIndebitamentoPrevidenzialeTributario('Indebitamento Previdenziale Tributario');

        if ($indebitamentoTributario === false)
            return false;

        $returnData['value'] = $indebitamentoTributario;

        $evaluation = DB::table('rangesBasic')
            ->where('tipo_azienda', '=', $tipoAzienda)
            ->where('indice', '=', 'Liquidità')
            ->where('soglia', '>', $indebitamentoTributario)
            ->count();

        if ($evaluation === false) {
            $returnData['fuoriSoglia'] = true;
        }

        return $returnData;
    }

    public function getRitornoLiquidoAttivo()
    {

        $returnData = array(
            'fuoriSoglia' => false,
            'value' => false
        );
        $tipoAzienda = $this->getTipoAzienda();
        $currentRatio = $this->getCurrentRatio('Ritorno Liquido Attivo');

        if ($currentRatio === false)
            return false;

        $returnData['value'] = $currentRatio;

        $evaluation = DB::table('rangesBasic')
            ->where('tipo_azienda', '=', $tipoAzienda)
            ->where('indice', '=', 'Ritorno Liquido Attivo')
            ->where('soglia', '>', $currentRatio)
            ->count();

        if ($evaluation) {
            $returnData['fuoriSoglia'] = true;
        }

        return $returnData;
    }

    public function getIndiceCNDCECEvaluation()
    {
        if ($getSostenibilitaOneriFinanziari = $this->getSostenibilitaOneriFinanziari()) {
            if ($getSostenibilitaOneriFinanziari['fuoriSoglia'])
                return true;
        }
        if ($getAdeguatezzaPatrimonialeEvaluation = $this->getAdeguatezzaPatrimonialeEvaluation()) {
            if ($getAdeguatezzaPatrimonialeEvaluation['fuoriSoglia'])
                return true;
        }
        if ($getLiquiditaEvaluation = $this->getLiquiditaEvaluation()) {
            if ($getLiquiditaEvaluation['fuoriSoglia'])
                return true;
        }
        if ($getIndebitamentoPrevidenziale = $this->getIndebitamentoPrevidenziale()) {
            if ($getIndebitamentoPrevidenziale['fuoriSoglia'])
                return true;
        }
        if ($getRitornoLiquidoAttivo = $this->getRitornoLiquidoAttivo()) {
            if ($getRitornoLiquidoAttivo['fuoriSoglia'])
                return true;
        }

        return false;
    }



    public function getIndiceCNDCEC()
    {
        if ($this->getIndiceCNDCECEvaluation())
            return "Azienda a Rischio";

        return "Azienda NON a Rischio";
    }

    public function getInpsData()
    {
        $inpsData = AnalisiInps::where('document_id', $this->codice_documento)->latest()->first();

        if ($inpsData != null) {
            $inpsData['alert'] = $inpsData['alertINPS'];

            return $inpsData;
        } else {
            return false;
        }
    }

    public function saveInps($dataInps)
    {
        if (!isset($dataInps["document_id"])) {
            return [
                'error' => true,
                'message' => "document_id mancante"
            ];
        } else {
            $idDocument = Document::where('codice_documento', $dataInps["document_id"])->first();
            if ($idDocument == null) {
                return [
                    'error' => true,
                    'message' => "document_id non trovato"
                ];
            }
        }

        $response = null;
        if (!isset($dataInps["INPS1"])) {
            $response = [
                'error' => true,
                'message' => "INPS1 mancante"
            ];
        } elseif (!isset($dataInps["INPS2"])) {
            $response = [
                'error' => true,
                'message' => "INPS2 mancante"
            ];
        } else {
            $response = $dataInps;
        }

        if (!isset($response['error'])) {

            $calcoloInps = $this->calcoloINPS($dataInps);
            if ($calcoloInps) {
                $response['INPS3'] = $dataInps['INPS3']; //$calcoloInps['inps3'];
                $response['alertINPS'] = $calcoloInps['alert'];

                $inpsToDb = AnalisiInps::where('document_id', $dataInps['document_id'])->first();

                if (isset($inpsToDb)) {
                    $inpsToDb->delete();
                    AnalisiInps::create($response);
                } else {
                    AnalisiInps::create($response);
                }

                return "Dati Inps salvati correttamente";
            } else {
                return [
                    "error" => true,
                    "message" => "INPS1 o INPS2 settato a 0, impossibile calcolare"
                ];
            }
        } else {
            return $response;
        }
    }

    public function calcoloINPS($allData)
    {
        if (!isset($allData['INPS1']) || !isset($allData['INPS2'])) {
            $alert = "Dati Mancanti";
        } elseif ($allData['INPS1'] == 0 || $allData['INPS2'] == 0) {
            return false;
        } else {
            $alert = "No";
        }

        if ($alert != "Dati Mancanti") {
            /*             if(str_contains($allData['INPS1'], ',') && str_contains($allData['INPS2'], ',')) {
                            $inps3 = ((str_replace(',', '', str_replace('.', '', $allData['INPS1'])) / str_replace(',', '', str_replace('.', '', $allData['INPS2']))) * 100);
                        } else {
                            $inps3 = (($allData['INPS1'] / $allData['INPS2']) * 100);
                        } */

            if ($allData['INPS1'] > 50000) {
                if ($allData['INPS3'] > 50.50) {
                    $alert = "Si";
                }
            }
        } /* else {
          $inps3 = "NON CALCOLABILE";
      } */

        $data = [
            'alert' => $alert,
            'inps3' => $allData['INPS3']
        ];

        return $data;
    }

    public function getRiscossioneData()
    {
        $riscossioneData = AnalisiRiscossione::where('document_id', $this->codice_documento)->latest()->first();

        if ($riscossioneData != null) {
            $riscossioneData['alert'] = $riscossioneData['alertRiscossione'];

            return $riscossioneData;
        } else {
            return false;
        }
    }

    public function saveRiscossione($dataRiscossione)
    {
        /*         if(!isset($dataRiscossione["idBilancio"])) {
                    return [
                        'error' => true,
                        'message' => "idBilancio mancante"
                    ];
                } else {
                    $idBilancio = Bilanci::where('id', $dataRiscossione["idBilancio"])->first();
                    if ($idBilancio == null) {
                        return  [
                            'error' => true,
                            'message' => "idBilancio non trovato"
                        ];
                    }
                } */

        if (!isset($dataRiscossione["document_id"])) {
            return [
                'error' => true,
                'message' => "document_id mancante"
            ];
        } else {
            $idDocument = Document::where('codice_documento', $dataRiscossione["document_id"])->first();
            if ($idDocument == null) {
                return [
                    'error' => true,
                    'message' => "document_id non trovato"
                ];
            }
        }

        $response = null;

        if (!isset($dataRiscossione["riscossione"])) {
            $response = [
                'error' => true,
                'message' => "riscossione mancante"
            ];
        } else {
            $response = $dataRiscossione;
        }

        if (!isset($response['error'])) {
            $calcoloRiscossione = $this->calculateRiscossione($response);

            $response['alertRiscossione'] = $calcoloRiscossione;

            $riscossioneToDb = AnalisiRiscossione::where('document_id', $response['document_id'])->first();

            if (isset($riscossioneToDb)) {
                $riscossioneToDb->delete();
                AnalisiRiscossione::create($response);
            } else {
                AnalisiRiscossione::create($response);
            }
            return "Dati riscossione salvati correttamente";
        } else {
            return $response;
        }
    }

    public function calculateRiscossione($allData)
    {
        /*         if(str_contains($allData['idBilancio'], '"')) {
                    $allData['idBilancio'] = str_replace('"', '', $allData['idBilancio']);
                }

                $balance = Bilanci::findOrFail($allData['idBilancio']);

                $formaGiuridica = $balance->forma_giuridica; */

        if (empty($allData['riscossione']) || $allData['riscossione'] == 0) {
            $alert = "Dati Mancanti";
        } else {
            $alert = "No";
        }

        /*         if ($formaGiuridica == "DITTA INDIVIDUALE") {
                    if ($allData['riscossione'] > 500000) {
                        $alert = "Si";
                    } else {
                        $alert = "No";
                    }
                } else { */
        if ($allData['riscossione'] > 1000000) {
            $alert = "Si";
        } else {
            $alert = "No";
        }
        /*  } */

        return $alert;
    }

    public function getRetribuzioniData()
    {
        $retribuzioniData = AnalisiRetribuzioni::where('document_id', $this->codice_documento)->latest()->first();

        if ($retribuzioniData != null) {
            $retribuzioniData['alert'] = $retribuzioniData['alertRetribuzioni'];

            return $retribuzioniData;
        } else {
            return false;
        }
    }

    public function saveRetribuzione($dataRetribuzione)
    {

        if (!isset($dataRetribuzione["document_id"])) {
            return [
                'error' => true,
                'message' => "document_id mancante"
            ];
        } else {
            $idDocument = Document::where('codice_documento', $dataRetribuzione["document_id"])->first();
            if ($idDocument == null) {
                return [
                    'error' => true,
                    'message' => "document_id non trovato"
                ];
            }
        }

        $response = null;
        if (!isset($dataRetribuzione["retribuzioni1"])) {
            $response = [
                'error' => true,
                'message' => "retribuzioni1 mancante"
            ];
        } elseif (!isset($dataRetribuzione["retribuzioni2"])) {
            $response = [
                'error' => true,
                'message' => "retribuzioni2 mancante"
            ];
        } else {
            $response = $dataRetribuzione;
        }

        if (!isset($response['error'])) {
            $calcoloRetribuzioni = $this->calculateRetribuzione($response);
            if ($calcoloRetribuzioni) {
                $response['retribuzioni3'] = $dataRetribuzione['retribuzioni3']; //$calcoloRetribuzioni['retribuzioni3'];
                $response['alertRetribuzioni'] = $calcoloRetribuzioni['alert'];

                $retribuzioniToDb = AnalisiRetribuzioni::where('document_id', $response['document_id'])->first();

                if (isset($retribuzioniToDb)) {
                    $retribuzioniToDb->delete();
                    AnalisiRetribuzioni::create($response);
                } else {
                    AnalisiRetribuzioni::create($response);
                }
                return "Dati retribuzioni salvati correttamente";
            } else {
                return [
                    "error" => true,
                    "message" => "retribuzioni1 o retribuzioni2 settato a 0, impossibile calcolare"
                ];
            }

        } else {
            return $response;
        }

    }

    public function calculateRetribuzione($allData)
    {
        if (!isset($allData['retribuzioni1']) || !isset($allData['retribuzioni2'])) {
            $alert = "Dati Mancanti";
        } elseif ($allData['retribuzioni1'] == 0 || $allData['retribuzioni2'] == 0) {
            return false;
        } else {
            $alert = "No";
        }

        if ($alert != "Dati Mancanti" && $alert != false) {
            if (str_contains($allData['retribuzioni1'], ',') || str_contains($allData['retribuzioni2'], ',')) {
                if ((str_replace(',', '.', str_replace('.', '', $allData['retribuzioni1'])) / str_replace(',', '.', str_replace('.', '', $allData['retribuzioni2']))) * 100 >= 50) {
                    $alert = "Si";
                } else {
                    $alert = "No";
                }
                /*               if (is_finite(str_replace(',', '.', str_replace('.', '', $allData['retribuzioni1'])) / str_replace(',', '.', str_replace('.', '', $allData['retribuzioni2'])))) {
                                  $cleanData['retribuzioni3'] = number_format(((str_replace(',', '.', str_replace('.', '', $allData['retribuzioni1'])) / str_replace(',', '.', str_replace('.', '', $allData['retribuzioni2']))) * 100), 2, ".", ",");
                              } else {
                                  $cleanData['retribuzioni3'] = "NON CALCOLABILE";
                              } */
            } else {
                if ((str_replace(',', '.', str_replace('.', '', $allData['retribuzioni1'])) / str_replace(',', '.', str_replace('.', '', $allData['retribuzioni2']))) * 100 >= 50) {
                    $alert = "Si";
                } else {
                    $alert = "No";
                }
                /*                 if (is_finite(str_replace(',', '.', str_replace('.', '', $allData['retribuzioni1'])) / str_replace(',', '.', str_replace('.', '', $allData['retribuzioni2'])))) {
                                    $cleanData['retribuzioni3'] = number_format(((str_replace(',', '.', str_replace('.', '', $allData['retribuzioni1'])) / str_replace(',', '.', str_replace('.', '', $allData['retribuzioni2']))) * 100), 2, ".", ",");
                                } else {
                                    $cleanData['retribuzioni3'] = "NON CALCOLABILE";
                                } */
            }
        }

        $data = [
            'alert' => $alert,
            'retribuzioni3' => $allData['retribuzioni3']
        ];

        return $data;
    }

    public function getFornitoriData()
    {
        $fornitoriData = AnalisiFornitori::where('document_id', $this->codice_documento)->latest()->first();

        if ($fornitoriData != null) {
            $fornitoriData['alert'] = $fornitoriData['alertFornitori'];

            return $fornitoriData;
        } else {
            return false;
        }
    }

    public function saveFornitori($dataFornitori)
    {

        if (!isset($dataFornitori["document_id"])) {
            return [
                'error' => true,
                'message' => "document_id mancante"
            ];
        } else {
            $idDocument = Document::where('codice_documento', $dataFornitori["document_id"])->first();
            if ($idDocument == null) {
                return [
                    'error' => true,
                    'message' => "document_id non trovato"
                ];
            }
        }

        $response = null;
        if (!isset($dataFornitori["fornitori1"])) {
            $response = [
                'error' => true,
                'message' => "fornitori1 mancante"
            ];
        } elseif (!isset($dataFornitori["fornitori2"])) {
            $response = [
                'error' => true,
                'message' => "fornitori2 mancante"
            ];
        } else {
            $response = $dataFornitori;
        }

        if (!isset($response['error'])) {
            $calcoloFornitori = $this->calculateFornitori($response);

            $response['alertFornitori'] = $calcoloFornitori;

            $fornitoriToDb = AnalisiFornitori::where('document_id', $response['document_id'])->first();

            if (isset($fornitoriToDb)) {
                $fornitoriToDb->delete();
                AnalisiFornitori::create($response);
            } else {
                AnalisiFornitori::create($response);
            }
            return "Dati fornitori salvati correttamente";
        } else {
            return $response;
        }
    }

    public function calculateFornitori($allData)
    {
        if (!isset($allData['fornitori1']) || !isset($allData['fornitori2'])) {
            $alert = "Dati Mancanti";
        } else {
            $alert = "No";
        }

        if ($allData['fornitori1'] > $allData['fornitori2']) {
            $alert = "Si";
        }

        return $alert;
    }

    public function getAgenziaEntrateData()
    {
        $agenziaEntrateData = AnalisiAgenziaEntrate::where('document_id', $this->codice_documento)->latest()->first();

        if ($agenziaEntrateData != null) {
            $agenziaEntrateData['alert'] = $agenziaEntrateData['alertAgenziaEntrate'];

            return $agenziaEntrateData;
        } else {
            return false;
        }
    }

    public function saveAgenziaEntrate($agenziaEntrateData)
    {

        if (!isset($agenziaEntrateData["document_id"])) {
            return [
                'error' => true,
                'message' => "document_id mancante"
            ];
        } else {
            $idDocument = Document::where('codice_documento', $agenziaEntrateData["document_id"])->first();
            if ($idDocument == null) {
                return [
                    'error' => true,
                    'message' => "document_id non trovato"
                ];
            }
        }

        $response = null;
        if (!isset($agenziaEntrateData["agenziaEntrate1"])) {
            $response = [
                'error' => true,
                'message' => "agenziaEntrate1 mancante"
            ];
        } elseif (!isset($agenziaEntrateData["agenziaEntrate2"])) {
            $response = [
                'error' => true,
                'message' => "agenziaEntrate2 mancante"
            ];
        } elseif (!isset($agenziaEntrateData["agenziaEntrate3"])) {
            $response = [
                'error' => true,
                'message' => "agenziaEntrate3 mancante"
            ];
        } elseif (!isset($agenziaEntrateData["agenziaEntrate1"]) && !isset($agenziaEntrateData["agenziaEntrate2"])) {
            $response = [
                'error' => true,
                'message' => "agenziaEntrate1 e agenziaEntrate2 mancanti"
            ];
        } else {
            $response = $agenziaEntrateData;
        }

        if (!isset($response['error'])) {
            $dataFloat = [
                'document_id' => $response['document_id'],
                'agenziaEntrate1' => (float) $response['agenziaEntrate1'],
                'agenziaEntrate2' => (float) $response['agenziaEntrate2'],
                'agenziaEntrate3' => (float) $response['agenziaEntrate3'],
                'agenziaEntrate4' => $agenziaEntrateData['agenziaEntrate4']
            ];

            $calcoloAgenziaEntrate = $this->calculateAgenziaEntrate($dataFloat);

            if ($calcoloAgenziaEntrate) {
                $dataFloat['alertAgenziaEntrate'] = $calcoloAgenziaEntrate['alert'];
                $dataFloat['agenziaEntrate4'] = $agenziaEntrateData['agenziaEntrate4'];//(float)$calcoloAgenziaEntrate['agenziaEntrate4']['agenziaEntrate4'];

                $agenziaEntrateToDb = AnalisiAgenziaEntrate::where('document_id', $dataFloat['document_id'])->first();

                if (isset($agenziaEntrateToDb)) {
                    $agenziaEntrateToDb->delete();
                    AnalisiAgenziaEntrate::create($dataFloat);
                } else {
                    AnalisiAgenziaEntrate::create($dataFloat);
                }

                return "Dati AgenziaEntrate salvati correttamente";
            } else {
                return [
                    "error" => true,
                    "message" => "agenziaEntrate1 o agenziaEntrate2 settato a 0, impossibile calcolare"
                ];
            }

        } else {
            return $response;
        }
    }

    public function calculateAgenziaEntrate($allData)
    {

        if (!isset($allData['agenziaEntrate1']) || !isset($allData['agenziaEntrate2'])) {
            $alert = "Dati Mancanti";
        } elseif ($allData['agenziaEntrate1'] == 0 || $allData['agenziaEntrate2'] == 0) {
            return false;
        } else {
            $alert = "No";
        }

        try {
            if ($alert != "Dati Mancanti") {
                if ($allData['agenziaEntrate1'] >= (($allData['agenziaEntrate2'] / 100) * 30)) {
                    if ($allData['agenziaEntrate3'] >= 0 && $allData['agenziaEntrate3'] < 2000000) {
                        if ($allData['agenziaEntrate1'] >= 25000) {
                            $alert = "Si";
                        }
                    } else if ($allData['agenziaEntrate3'] >= 2000000 && $allData['agenziaEntrate3'] < 10000000) {
                        if ($allData['agenziaEntrate1'] >= 50000) {
                            $alert = "Si";
                        }
                    } else if ($allData['agenziaEntrate3'] > 10000000) {
                        if ($allData['agenziaEntrate1'] > 100000) {
                            $alert = "Si";
                        }
                    }
                }

                /*           if (is_finite($allData['agenziaEntrate1'] / $allData['agenziaEntrate2'])) {
                              // dd((float)number_format((((float)$allData['agenziaEntrate1'] / (float)$allData['agenziaEntrate2'])), 2, ",", "."));
                              $cleanData['agenziaEntrate4'] = number_format((($allData['agenziaEntrate1'] / $allData['agenziaEntrate2'])), 2, ",", ".");
                          } else {
                              $cleanData['agenziaEntrate4'] = "NON CALCOLABILE";
                          } */
            }

            $data = [
                'alert' => $alert,
                'agenziaEntrate4' => $allData['agenziaEntrate4']
            ];

            return $data;
        } catch (Exception $e) {
            CustomLog::addToLogBilanciHelper('BilanciHelper', 'GetExceptionAgenziaEntrate', json_encode($e->getMessage()));
            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }


    public function saveDscrAnalisi($dscrData)
    {
        if ($dscrData['DSCR'] != 1) {
            return [
                'error' => true,
                'message' => "DSCR da non calcolare"
            ];
        }

        if (!isset($dscrData["document_id"])) {
            return [
                'error' => true,
                'message' => "document_id mancante"
            ];
        } else {
            $idDocument = Document::where('codice_documento', $dscrData["document_id"])->first();
            if ($idDocument == null) {
                return [
                    'error' => true,
                    'message' => "document_id non trovato"
                ];
            }
        }

        $response = null;
        foreach ($dscrData as $key => $singleDscrData) {
            if (!isset($dscrData[$key])) {
                return $response = [
                    'error' => true,
                    'message' => $key . " mancante"
                ];
            } else {
                $response = $dscrData;
            }
        }

        if (!isset($response['error'])) {
            $calcoloDSCR = $this->calculateDSCR($dscrData);

            if ($calcoloDSCR > 1) {
                $response['alertDSCR'] = 'Azienda non a rischio';
            } else {
                $response['alertDSCR'] = 'Azienda a rischio';
            }

            $response['resultDSCR'] = $calcoloDSCR;

            $dscrToDb = AnalisiDscr::where('document_id', $response['document_id'])->first();

            if (isset($dscrToDb)) {
                $dscrToDb->delete();
                AnalisiDscr::create($response);
            } else {
                AnalisiDscr::create($response);
            }

            return "Dati DSCR salvati correttamente";
        } else {
            return $response;
        }
    }

    public function calculateDSCR($inboundData)
    {


        $sum = ($inboundData['DSCRdispLiquida'] +
            $inboundData['entrataDSCRCFmese1'] +
            $inboundData['entrataDSCRCFmese2'] +
            $inboundData['entrataDSCRCFmese3'] +
            $inboundData['entrataDSCRCFmese4'] +
            $inboundData['entrataDSCRCFmese5'] +
            $inboundData['entrataDSCRCFmese6'] +
            $inboundData['uscitaDSCRCFmese1'] -
            $inboundData['uscitaDSCRCFmese2'] -
            $inboundData['uscitaDSCRCFmese3'] -
            $inboundData['uscitaDSCRCFmese4'] -
            $inboundData['uscitaDSCRCFmese5'] -
            $inboundData['uscitaDSCRCFmese6']
        );

        $divisore = ($inboundData['rimborsoDSCRmese1'] +
            $inboundData['rimborsoDSCRmese2'] +
            $inboundData['rimborsoDSCRmese3'] +
            $inboundData['rimborsoDSCRmese4'] +
            $inboundData['rimborsoDSCRmese5'] +
            $inboundData['rimborsoDSCRmese6']
        );

        try {
            $result = $sum / $divisore;
            CustomLog::addToLogBilanciHelper('BilanciHelper', 'GetResultCalculateDSCR', $sum . ' / ' . $divisore . ' = ' . $result);

            return number_format($result, 2, ",", ".");
        } catch (Exception $e) {
            CustomLog::addToLogBilanciHelper('BilanciHelper', 'getExceptionCalculateDSCR', json_encode($e->getMessage()));

            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getDSCRData()
    {
        $dscrData = AnalisiDscr::where('document_id', $this->codice_documento)->latest()->first();
        //dd($dscrData);

        if ($dscrData != null) {
            $dscrData['alert'] = $dscrData['alertDSCR'];

            return $dscrData;
        } else {
            return false;
        }
    }

}
