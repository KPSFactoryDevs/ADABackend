<?php

namespace App\Helpers\Bilanci;

use Illuminate\Support\Facades\DB;
use stdClass;
use App\Models\CustomLog;
use App\Models\MissingVoice;


class BilanciCalculationsHelperAdvanced
{

    public $_currentInstance = false;

    public $_missingVoicesArray = array();

    public $documentId = false;

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
        $elementoFromQuery = MissingVoice::where('documentId', $this->documentId)
            ->where('period', $period)
            ->where('voiceFullName', $elementName)
            ->get();

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
        } elseif (count($elementoFromQuery) > 0) {
       /*     if($elementName == "CostiProduzioneAccantonamentiRischi") {
                echo $elementoFromQuery->first()->value;
            }*/

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
        }
    }

    public function getMissingVoicesFromDb()
    {
        $elementoFromQuery = MissingVoice::select('voiceFullName', 'period', 'voiceValue')
            ->where('documentId', $this->documentId)
            ->get()
            ->groupBy(['voiceFullName', 'period', 'voiceValue'])
            ->toArray();

        return $elementoFromQuery;
    }


    public function getTotalePatrimonioNetto($indexName = "TotalePatrimonioNetto")
    {
        $TotalePatrimonioNetto = $this->getElementFromBalance('TotalePatrimonioNetto', 1, $indexName);

        if (!$TotalePatrimonioNetto)
            return false;

        return $TotalePatrimonioNetto;
    }

    public function getPatrimonioNettoNegativo($indexName = "PatrimonioNettoNegativo")
    {
        $TotalePatrimonioNetto = $this->getTotalePatrimonioNetto($indexName);
        $TotaleCreditiVersoSociVersamentiAncoraDovuti = $this->getElementFromBalance('TotaleCreditiVersoSociVersamentiAncoraDovuti', 1, $indexName);
        //  $TotalePatrimonioNetto = str_replace(',', '.', str_replace('.', '', $TotalePatrimonioNetto));

        if (!$TotalePatrimonioNetto || !$TotaleCreditiVersoSociVersamentiAncoraDovuti)
            return false;

        $PN_NEGATIVO = $TotalePatrimonioNetto - $TotaleCreditiVersoSociVersamentiAncoraDovuti;
        $calculationPNnegativo = $TotalePatrimonioNetto . ' - ' . $TotaleCreditiVersoSociVersamentiAncoraDovuti . ' = ' . $PN_NEGATIVO;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetPatrimonioNettoNegativo', $calculationPNnegativo);


        return $PN_NEGATIVO;
    }

    public function getOfRicavi($indexName = "OF Ricavi")
    {
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $this->getElementFromBalance('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 1, $indexName);
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1, $indexName);

        if (!$ValoreProduzioneRicaviVenditePrestazioni || !$ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari) {
            return false;
        }

        $OF_RICAVI = ($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari / $ValoreProduzioneRicaviVenditePrestazioni) * 100;
        $calculation = $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari . ' / ' . $ValoreProduzioneRicaviVenditePrestazioni . ' * ' . 100 . ' = ' . $OF_RICAVI;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetOF_RICAVI', $calculation);

        return $OF_RICAVI;
    }

    public function getTotaleDebiti($indexName = "TotaleDebiti")
    {
        $TotaleDebiti = $this->getElementFromBalance('TotaleDebiti', 1, $indexName);

        if (!$TotaleDebiti)
            return false;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetTotaleDebiti', $TotaleDebiti);

        return $TotaleDebiti;
    }

    public function getAdeguatezzaPatrimoniale($indexName = "Adeguatezza Patrimoniale")
    {
        $PN_NEGATIVO = $this->getPatrimonioNettoNegativo($indexName);
        $TotaleDebiti = $this->getTotaleDebiti($indexName);
        $PassivoRateiRisconti = $this->getElementFromBalance('PassivoRateiRisconti', 1, $indexName);

        if (!$PN_NEGATIVO || !$TotaleDebiti || !$PassivoRateiRisconti) {
            return false;
        }
        $ADEGUATEZZA_PATRIMONIALE = ($PN_NEGATIVO / ($TotaleDebiti + $PassivoRateiRisconti)) * 100;
        $calculation = $PN_NEGATIVO . ' / (' . $TotaleDebiti . ' + ' . $PassivoRateiRisconti . ') * ' . 100;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAdeguatezzaPatrimoniale', $calculation);

        return $ADEGUATEZZA_PATRIMONIALE;
    }

    public function getTotaleCreditiEntroDodiciMesi($indexName = "Totale Crediti Entro Dodici Mesi")
    {
        $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $CreditiImposteAnticipateTotaleImposteAnticipate = $this->getElementFromBalance('CreditiImposteAnticipateTotaleImposteAnticipate', 1, $indexName);

        if (!$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo || !$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo || !$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo || !$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo || !$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo || !$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo || !$CreditiImposteAnticipateTotaleImposteAnticipate) {
            return false;
        }

        $TotaleDebitiEntroDodiciMesi = (float)$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + (float)$CreditiImposteAnticipateTotaleImposteAnticipate + (float)$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo;
        $calculation = (float)$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo . ' + ' . (float)$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo . ' + ' . (float)$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo . ' + ' . (float)$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo . ' + ' . (float)$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo . ' + ' . (float)$CreditiImposteAnticipateTotaleImposteAnticipate . ' + ' . (float)$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo . ' = ' . $TotaleDebitiEntroDodiciMesi;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetTotaleCreditiEntroDodiciMesi', $calculation);

        return $TotaleDebitiEntroDodiciMesi;
    }

    public function getTotaleDebitiEntroDodiciMesi($indexName = "Totale Debiti Entro Dodici Mesi")
    {
        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiAccontiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiAccontiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1,$indexName);
        $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);

        if (!$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo || !$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo || !$DebitiAccontiEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo || !$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo) {
            return false;
        }

        $TotaleDebitiEntroDodiciMesi = (float)$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAccontiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo;

        $calculation = (float)$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo . ' + ' . (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo . ' + ' . (float)$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo . ' + ' . (float)$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo . ' + ' . (float)$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo . ' + ' . (float)$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo . ' + ' . (float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo . ' + ' . (float)$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo . ' + ' . (float)$DebitiAccontiEsigibiliEntroEsercizioSuccessivo . ' + ' . (float)$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo . ' + ' . (float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo . ' + ' . (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo . ' + ' . (float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo . ' + ' . (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo . ' = ' . $TotaleDebitiEntroDodiciMesi;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetTotaleDebitiEntroDodiciMesi', $calculation);

        return $TotaleDebitiEntroDodiciMesi;
    }

    public function getLiquidita($indexName = "Liquidità")
    {
        $CostiProduzioneAccantonamentiRischi = $this->getElementFromBalance('CostiProduzioneAccantonamentiRischi', 1, $indexName);
        $TotaleAttivo = $this->getElementFromBalance('TotaleAttivo', 1, $indexName);
        $UtilePerditaEsercizio = $this->getElementFromBalance('UtilePerditaEsercizio', 1, $indexName);
        $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni = $this->getElementFromBalance('CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 1, $indexName);
        $CostiProduzioneAltriAccantonamenti = $this->getElementFromBalance('CostiProduzioneAltriAccantonamenti', 1, $indexName);

        if (!$CostiProduzioneAccantonamentiRischi || !$TotaleAttivo || !$UtilePerditaEsercizio || !$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni || !$CostiProduzioneAltriAccantonamenti) {
        /*   return array($CostiProduzioneAccantonamentiRischi,
               $TotaleAttivo,
               $UtilePerditaEsercizio,
               $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni,
               $CostiProduzioneAltriAccantonamenti
           );
*/
            return false;
        }


        $LIQUIDITA = (($UtilePerditaEsercizio + $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni + (float)$CostiProduzioneAccantonamentiRischi + (float)$CostiProduzioneAltriAccantonamenti) / (float)$TotaleAttivo) * 100;
        $calculationLIQUIDITA = '((' . $UtilePerditaEsercizio . ' + ' . $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni . ' + ' . (float)$CostiProduzioneAccantonamentiRischi . ' + ' . (float)$CostiProduzioneAltriAccantonamenti . ') / ' . (float)$TotaleAttivo . ') * ' . 100;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetLiquiditÃ ', $calculationLIQUIDITA);

        return $LIQUIDITA;
    }

    public function getIndebitamentoPrevidenzialeTributario($indexName = "Indebitamento Previdenziale Tributario")
    {
        $DebitiDebitiTributariTotaleDebitiTributari = $this->getElementFromBalance('DebitiDebitiTributariTotaleDebitiTributari', 1, $indexName);
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale = $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale', 1, $indexName);
        $TotaleAttivo = $this->getElementFromBalance('TotaleAttivo', 1, $indexName);

        if (!$DebitiDebitiTributariTotaleDebitiTributari || !$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale || !$TotaleAttivo)
            return false;

        $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO = (($DebitiDebitiTributariTotaleDebitiTributari + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) / $TotaleAttivo) * 100;
        $calculation = '((' . $DebitiDebitiTributariTotaleDebitiTributari . ' + ' . $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale . ') / ' . $TotaleAttivo . ') * ' . 100 . ' = ' . $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetIndebitamentoPrevidenzialeTributario', $calculation);

        return $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO;
    }

    public function getAndamentoDelFatturato($indexName = "Andamento Del Fatturato")
    {
        $ValoreProduzioneRicaviVenditePrestazioniCurr = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1,  $indexName);
        $ValoreProduzioneRicaviVenditePrestazioniPrev = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 2,  $indexName);

        if (!$ValoreProduzioneRicaviVenditePrestazioniCurr || !$ValoreProduzioneRicaviVenditePrestazioniPrev)
            return false;

        $AndamentoDelFatturato = (-(1 - (($ValoreProduzioneRicaviVenditePrestazioniCurr) / ($ValoreProduzioneRicaviVenditePrestazioniPrev)))) * 100;
        $calculation = '(- (' . 1 . ' - ((' . $ValoreProduzioneRicaviVenditePrestazioniCurr . ') / (' . $ValoreProduzioneRicaviVenditePrestazioniPrev . ')))) * ' . 100 . ' = ' . $AndamentoDelFatturato;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAndamentoDelFatturato', $calculation);

        return $AndamentoDelFatturato;
    }

    public function getAndamentoDelMol($indexName = "Andamento Del Mol")
    {
        // Curr
        $TotaleValoreProduzione = $this->getElementFromBalance('TotaleValoreProduzione', 1,   $indexName);
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 1,   $indexName);
        $CostiProduzioneGodimentoBeniTerzi = $this->getElementFromBalance('CostiProduzioneGodimentoBeniTerzi', 1,   $indexName);
        $CostiProduzioneServizi = $this->getElementFromBalance('CostiProduzioneServizi', 1,   $indexName);
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getElementFromBalance('CostiProduzionePersonaleTotaleCostiPersonale', 1,   $indexName);
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 1,   $indexName);
        $CostiProduzioneOneriDiversiGestione = $this->getElementFromBalance('CostiProduzioneOneriDiversiGestione', 1,   $indexName);

        // Prev
        $TotaleValoreProduzionePrecedente = $this->getElementFromBalance('TotaleValoreProduzione', 2,   $indexName);
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerciPrecedente = $this->getElementFromBalance('CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 2,   $indexName);
        $CostiProduzioneGodimentoBeniTerziPrecedente = $this->getElementFromBalance('CostiProduzioneGodimentoBeniTerzi', 2,   $indexName);
        $CostiProduzioneServiziPrecedente = $this->getElementFromBalance('CostiProduzioneServizi', 2,   $indexName);
        $CostiProduzionePersonaleTotaleCostiPersonalePrecedente = $this->getElementFromBalance('CostiProduzionePersonaleTotaleCostiPersonale', 2,   $indexName);
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerciPrecedente = $this->getElementFromBalance('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 2,   $indexName);
        $CostiProduzioneOneriDiversiGestionePrecedente = $this->getElementFromBalance('CostiProduzioneOneriDiversiGestione', 2,   $indexName);

        if (!$TotaleValoreProduzione || !$CostiProduzioneMateriePrimeSussidiarieConsumoMerci || !$CostiProduzioneGodimentoBeniTerzi || !$CostiProduzioneServizi || !$CostiProduzionePersonaleTotaleCostiPersonale || !$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci || !$CostiProduzioneOneriDiversiGestione || !$TotaleValoreProduzionePrecedente || !$CostiProduzioneMateriePrimeSussidiarieConsumoMerciPrecedente || !$CostiProduzioneGodimentoBeniTerziPrecedente || !$CostiProduzioneServiziPrecedente || !$CostiProduzionePersonaleTotaleCostiPersonalePrecedente || !$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerciPrecedente || !$CostiProduzioneOneriDiversiGestionePrecedente) {
            return false;
        }

        $MOLcurr = $TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzioneServizi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione;
        $MOLprev = $TotaleValoreProduzionePrecedente - $CostiProduzioneMateriePrimeSussidiarieConsumoMerciPrecedente - $CostiProduzioneGodimentoBeniTerziPrecedente - $CostiProduzioneServiziPrecedente - $CostiProduzionePersonaleTotaleCostiPersonalePrecedente - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerciPrecedente - $CostiProduzioneOneriDiversiGestionePrecedente;

        $AndamentoMOL = -(1 - ($MOLcurr / $MOLprev)) * 100;
        $calculation = '- (' . 1 . ' - (' . $MOLcurr . ' / ' . $MOLprev . ')) * ' . 100 . ' = ' . $AndamentoMOL;


        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAndamentoDelMol', $calculation);

        $data = [
            'MOLcurr' => $MOLcurr,
            'AndamentoMOL' => $AndamentoMOL,
        ];

        return $data;
    }

    public function getROI($indexName = "ROI")
    {
        $DifferenzaValoreCostiProduzione = $this->getElementFromBalance('DifferenzaValoreCostiProduzione', 1,  $indexName);
        $TotaleAttivo = $this->getElementFromBalance('TotaleAttivo', 1,  $indexName);

        if (!$DifferenzaValoreCostiProduzione || !$TotaleAttivo)
            return false;

        $ROI = ($DifferenzaValoreCostiProduzione / $TotaleAttivo) * 100;
        $calculation = '(' . $DifferenzaValoreCostiProduzione . ' / ' . $TotaleAttivo . ') * ' . 100 . ' = ' . $ROI;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetROI', $calculation);

        return $ROI;
    }

    public function getROS($indexName = "ROS")
    {
        $DifferenzaValoreCostiProduzione = $this->getElementFromBalance('DifferenzaValoreCostiProduzione', 1,  $indexName);
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1,  $indexName);

        if (!$DifferenzaValoreCostiProduzione || !$ValoreProduzioneRicaviVenditePrestazioni)
            return false;

        $ROS = ($DifferenzaValoreCostiProduzione / $ValoreProduzioneRicaviVenditePrestazioni) * 100;
        $calculation = '(' . $DifferenzaValoreCostiProduzione . ' / ' . $ValoreProduzioneRicaviVenditePrestazioni . ') * ' . 100 . ' = ' . $ROS;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetROS', $calculation);

        return $ROS;
    }

    public function getROE($indexName = "ROE")
    {
        $TotalePatrimonioNetto = $this->getElementFromBalance('TotalePatrimonioNetto', 1, $indexName);
        $UtilePerditaEsercizio = $this->getElementFromBalance('UtilePerditaEsercizio', 1, $indexName);

        if (!$TotalePatrimonioNetto || !$UtilePerditaEsercizio)
            return false;

        $ROE = ($UtilePerditaEsercizio / $TotalePatrimonioNetto) * 100;
        $calculation = '(' . $UtilePerditaEsercizio . ' / ' . $TotalePatrimonioNetto . ') * ' . 100 . ' = ' . $ROE;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetROE', $calculation);

        return $ROE;
    }

    public function getEbitdaFatturato($indexName = "Ebitda Fatturato")
    {
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1,  $indexName);
        $TotaleValoreProduzione = $this->getElementFromBalance('TotaleValoreProduzione', 1, 'Ebitda Fatturato');
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 1,  $indexName);
        $CostiProduzioneServizi = $this->getElementFromBalance('CostiProduzioneServizi', 1,  $indexName);
        $CostiProduzioneGodimentoBeniTerzi = $this->getElementFromBalance('CostiProduzioneGodimentoBeniTerzi', 1,  $indexName);
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getElementFromBalance('CostiProduzionePersonaleTotaleCostiPersonale', 1,  $indexName);
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 1,  $indexName);
        $CostiProduzioneOneriDiversiGestione = $this->getElementFromBalance('CostiProduzioneOneriDiversiGestione', 1,  $indexName);

        if (!$ValoreProduzioneRicaviVenditePrestazioni || !$TotaleValoreProduzione || !$CostiProduzioneMateriePrimeSussidiarieConsumoMerci || !$CostiProduzioneServizi || !$CostiProduzioneGodimentoBeniTerzi || !$CostiProduzionePersonaleTotaleCostiPersonale || !$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci || !$CostiProduzioneOneriDiversiGestione)
            return false;

        $EBITDA_FATTURATO = (($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / $ValoreProduzioneRicaviVenditePrestazioni) * 100;
        $calculation = '((' . $TotaleValoreProduzione . ' - ' . $CostiProduzioneMateriePrimeSussidiarieConsumoMerci . ' - ' . $CostiProduzioneServizi . ' - ' . $CostiProduzioneGodimentoBeniTerzi . ' - ' . $CostiProduzionePersonaleTotaleCostiPersonale . ' - ' . $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci . ' - ' . $CostiProduzioneOneriDiversiGestione . ') / ' . $ValoreProduzioneRicaviVenditePrestazioni . ') * ' . 100 . ' = ' . $EBITDA_FATTURATO;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetEbitdaFatturato', $calculation);

        return $EBITDA_FATTURATO;
    }

    public function getAndamentoDeiMezziPropri($indexName = "Andamento Dei Mezzi Propri")
    {
        $TotalePatrimonioNettoCurr = $this->getElementFromBalance('TotalePatrimonioNetto', 1, $indexName);
        $TotalePatrimonioNettoPrev = $this->getElementFromBalance('TotalePatrimonioNetto', 2, $indexName);

        if (!$TotalePatrimonioNettoCurr || !$TotalePatrimonioNettoPrev)
            return false;

        $AndamentoDeiMezziPropri = (($TotalePatrimonioNettoCurr / $TotalePatrimonioNettoPrev) - 1) * 100;
        $calculation = '((' . $TotalePatrimonioNettoCurr . ' / ' . 1 . ') - ' . $TotalePatrimonioNettoPrev . ') * ' . 100 . ' = ' . $AndamentoDeiMezziPropri;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAndamentoDeiMezziPropri', $calculation);

        return $AndamentoDeiMezziPropri;
    }

    public function getMargineStrutturaPrimario($indexName = "Margine Struttura Primario")
    {
        $TotaleImmobilizzazioni = $this->getElementFromBalance('TotaleImmobilizzazioni', 1, $indexName);
        $TotalePatrimonioNetto = $this->getElementFromBalance('TotalePatrimonioNetto', 1, $indexName);

        if (!$TotaleImmobilizzazioni || !$TotalePatrimonioNetto)
            return false;

        $Margine_Struttura_Primario = (float)($TotalePatrimonioNetto / $TotaleImmobilizzazioni) * 100;
        $calculation = '(' . (float)$TotalePatrimonioNetto . ' / ' . $TotaleImmobilizzazioni . ') * ' . 100 . ' = ' . $Margine_Struttura_Primario;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetMargineStrutturaPrimario', $calculation);

        return $Margine_Struttura_Primario;
    }

    public function getMargineStrutturaSecondario($indexName = "Margine Struttura Secondario")
    {
        $TrattamentoFineRapportoLavoroSubordinato = $this->getElementFromBalance('TrattamentoFineRapportoLavoroSubordinato', 1,  $indexName);
        $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 1,  $indexName);
        $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 1,  $indexName);
        $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 1,  $indexName) != 0 ? $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 1,  $indexName) : $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 2,  $indexName));
        $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 1,  $indexName);
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 1,  $indexName);
        $DebitiAccontiEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiAccontiEsigibiliOltreEsercizioSuccessivo', 1,  $indexName);
        $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo', 1,  $indexName);
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo', 1,  $indexName);
        $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo', 1,  $indexName);
        $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo', 1,  $indexName);
        $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo', 1,  $indexName);
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo', 1,  $indexName);
        $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo = ($this->getElementFromBalance('DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo', 1,  $indexName) != 0 ? $this->getElementFromBalance('DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo', 1,  $indexName) : $this->getElementFromBalance('DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo', 2,  $indexName));
        $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo', 1,  $indexName) != 0 ? $this->getElementFromBalance('DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo', 1,  $indexName) : $this->getElementFromBalance('DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo', 2,  $indexName));
        $TotalePatrimonioNetto = $this->getElementFromBalance('TotalePatrimonioNetto', 1,  $indexName);
        $TotaleImmobilizzazioni = $this->getElementFromBalance('TotaleImmobilizzazioni', 1,  $indexName);

        if (!$TrattamentoFineRapportoLavoroSubordinato || !$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo || !$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo || !$DebitiAccontiEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo || !$DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo || !$TotalePatrimonioNetto || !$TotaleImmobilizzazioni)
            return false;

        $Margine_Struttura_Secondario_Semplificato = (($TotalePatrimonioNetto + $TrattamentoFineRapportoLavoroSubordinato + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo + $DebitiAccontiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) / $TotaleImmobilizzazioni) * 100;
        $calculation = '((' . $TotalePatrimonioNetto . ' + ' . $TrattamentoFineRapportoLavoroSubordinato . ' + ' . $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiAccontiEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo . ' + ' . $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo . ') / ' . $TotaleImmobilizzazioni . ') * ' . 100 . ' = ' . $Margine_Struttura_Secondario_Semplificato;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetMargineStrutturaSecondario', $calculation);

        return $Margine_Struttura_Secondario_Semplificato;
    }

    public function getCurrentRatio($indexName = "Current Ratio")
    {
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1, $indexName);
        $AttivoRateiRisconti = $this->getElementFromBalance('AttivoRateiRisconti', 1, $indexName);
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getElementFromBalance('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 1, $indexName);
        $TotaleRimanenze = $this->getElementFromBalance('TotaleRimanenze', 1, $indexName);
        $TotaleCreditiEntroDodiciMesi = $this->getElementFromBalance('TotaleCreditiEntroDodiciMesi', 1, $indexName);
        $TotaleDebitiEntroDodiciMesi = $this->getElementFromBalance('TotaleDebitiEntroDodiciMesi', 1, $indexName);
        $PassivoRateiRisconti = $this->getElementFromBalance('PassivoRateiRisconti', 1, $indexName);

        if (!$TotaleDisponibilitaLiquide || !$AttivoRateiRisconti || !$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni || !$TotaleRimanenze || !$TotaleCreditiEntroDodiciMesi || !$TotaleDebitiEntroDodiciMesi || !$PassivoRateiRisconti)
            return false;

        $formula = ($TotaleDisponibilitaLiquide + $AttivoRateiRisconti + $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + $TotaleRimanenze + $TotaleCreditiEntroDodiciMesi) / ($TotaleDebitiEntroDodiciMesi + $PassivoRateiRisconti);
        $calculation = '(' . $TotaleDisponibilitaLiquide . ' + ' . $AttivoRateiRisconti . ' + ' . $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni . ' + ' . $TotaleRimanenze . ' + ' . $TotaleCreditiEntroDodiciMesi . ') / (' . $TotaleDebitiEntroDodiciMesi . ' + ' . $PassivoRateiRisconti . ') = ' . $formula;

        $RITORNO_LIQUIDO_ATTIVO = $formula * 100;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetCurrentRatio', $calculation);

        return $RITORNO_LIQUIDO_ATTIVO;
    }

    public function getAttivitaPassivitaABreve($indexName = "Attivita Passivita a Breve")
    {
        // TRENTACINQUE
        $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 1, $indexName);
        $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 1, $indexName) ? $this->getElementFromBalance('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 1, $indexName) : $this->getElementFromBalance('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 2, $indexName));
        $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 1, $indexName) ? $this->getElementFromBalance('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 1, $indexName): $this->getElementFromBalance('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 2, $indexName));
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
        $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 1, $indexName) ? $this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 1, $indexName): $this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 2, $indexName));
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

        if (!$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo || !$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo || !$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo || !$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo || !$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo || !$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo || !$DebitiAccontiEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo || !$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo || !$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo || !$PassivoRateiRisconti || !$TotaleDisponibilitaLiquide || !$TotaleRimanenze || !$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni || !$AttivoRateiRisconti || !$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo)
            return false;

        $Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore = $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiAccontiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $PassivoRateiRisconti;

        $Attivita_a_breve_Passivita_a_Breve_Ordinario = ((($TotaleDisponibilitaLiquide + $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo + $TotaleRimanenze + $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + $AttivoRateiRisconti) / ($Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore))) * 100;
        $calculation = '(((' . $TotaleDisponibilitaLiquide . ' + ' . $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo . ' + ' . $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo . ' + ' . $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo . ' + ' . $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo . ' + ' . $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo . ' + ' . $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo . ' + ' . $TotaleRimanenze . ' + ' . $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni . ' + ' . $AttivoRateiRisconti . ') / (' . $Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore . '))) * ' . 100 . ' = ' . $Attivita_a_breve_Passivita_a_Breve_Ordinario;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAttivitaPassivitaABreve', $calculation);

        $data = [
            'Attivita_a_breve_PassivitÃ _a_Breve_Ordinario' => $Attivita_a_breve_Passivita_a_Breve_Ordinario,
            'QuarantaNove' => $QuarantaNove,
            'TrentaCinque' => $TrentaCinque
        ];

        return $data;
    }

    public function getAcidTest()
    {
        $DebitiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test');
        $PassivoRateiRisconti = $this->getElementFromBalance('PassivoRateiRisconti', 1, 'Acid Test');
        $TotaleCrediti = $this->getElementFromBalance('TotaleCrediti', 1, 'Acid Test');
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getElementFromBalance('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 1, 'Acid Test');
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1, 'Acid Test');
        $AttivoRateiRisconti = $this->getElementFromBalance('AttivoRateiRisconti', 1, 'Acid Test');

        /*     if (!$DebitiEsigibiliEntroEsercizioSuccessivo || !$PassivoRateiRisconti || !$TotaleCrediti || !$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni || !$TotaleDisponibilitaLiquide || !$AttivoRateiRisconti)
                return false; */

        $AcidTest = (((float)$TotaleCrediti + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$TotaleDisponibilitaLiquide + (float)$AttivoRateiRisconti) / ((float)$DebitiEsigibiliEntroEsercizioSuccessivo + (float)$PassivoRateiRisconti));
        $calculation = '((' . (float)$TotaleCrediti . ' + ' . (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni . ' + ' . (float)$TotaleDisponibilitaLiquide . ' + ' . (float)$AttivoRateiRisconti . ') / (' . (float)$DebitiEsigibiliEntroEsercizioSuccessivo . ' + ' . (float)$PassivoRateiRisconti . ')) = ' . $AcidTest;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAcidTest', $calculation);

        return $AcidTest;
    }

    public function getAcidTestSemplificato()
    {
        $getAttivitaPassivitaABreve = $this->getAttivitaPassivitaABreve('Acid Test Semplificato');

        $TotaleRimanenze = $this->getElementFromBalance('TotaleRimanenze', 1, 'Acid Test Semplificato');
        $PassivoRateiRisconti = $this->getElementFromBalance('PassivoRateiRisconti', 1, 'Acid Test Semplificato');
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1, 'Acid Test Semplificato');
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getElementFromBalance('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 1, 'Acid Test Semplificato');
        $AttivoRateiRisconti = $this->getElementFromBalance('AttivoRateiRisconti', 1, 'Acid Test Semplificato');

        if (!$getAttivitaPassivitaABreve || !$TotaleRimanenze || !$PassivoRateiRisconti || !$TotaleDisponibilitaLiquide || !$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni || !$AttivoRateiRisconti)
            return false;

        $QuarantaNove = $getAttivitaPassivitaABreve['QuarantaNove'];
        $TrentaCinque = $getAttivitaPassivitaABreve['TrentaCinque'];

        $ACID_TEST_Semplificato = ((((float)$TotaleDisponibilitaLiquide + (float)$TrentaCinque + (float)$TotaleRimanenze + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$AttivoRateiRisconti - (float)$TotaleRimanenze) / ((float)$QuarantaNove + (float)$PassivoRateiRisconti)));
        $calculation = '(((' . (float)$TotaleDisponibilitaLiquide . ' + ' . (float)$TrentaCinque . ' + ' . (float)$TotaleRimanenze . ' + ' . (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni . ' + ' . (float)$AttivoRateiRisconti . ' - ' . (float)$TotaleRimanenze . ') / (' . (float)$QuarantaNove . ' + ' . (float)$PassivoRateiRisconti . '))) = ' . $ACID_TEST_Semplificato;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAcidTestSemplificato', $calculation);

        return $ACID_TEST_Semplificato;
    }

    public function getAcidTestOrdinario()
    {
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario');
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario');
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario') ? $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario') : $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 2, 'Acid Test Ordinario'));
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario');
        $DebitiAccontiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiAccontiEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario');
        $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario') ? $this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario') : $this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 2, 'Acid Test Ordinario'));
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario');
        $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario');
        $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario');
        $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario');
        $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario') ? $this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario') : $this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 2, 'Acid Test Ordinario'));
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario') ? $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario') : $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 2, 'Acid Test Ordinario'));
        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario');
        $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario');
        $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario');
        $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario');
        $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario');
        $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario');
        $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario');
        $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 1, 'Acid Test Ordinario');

        $PassivoRateiRisconti = $this->getElementFromBalance('PassivoRateiRisconti', 1, 'Acid Test Ordinario');
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1, 'Acid Test Ordinario');
        $TotaleRimanenze = $this->getElementFromBalance('TotaleRimanenze', 1, 'Acid Test Ordinario');
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getElementFromBalance('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 1, 'Acid Test Ordinario');
        $AttivoRateiRisconti = $this->getElementFromBalance('AttivoRateiRisconti', 1, 'Acid Test Ordinario');

        if (!$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo || !$DebitiAccontiEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo || !$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo || !$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo || !$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo || !$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo || !$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo || !$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo || !$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo || !$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo || !$PassivoRateiRisconti || !$TotaleDisponibilitaLiquide || !$TotaleRimanenze || !$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni || !$AttivoRateiRisconti)
            return false;

        $ACID_TEST_Ordinario_divisore = $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiAccontiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $PassivoRateiRisconti;

        $ACID_TEST_Ordinario = ((($TotaleDisponibilitaLiquide + $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo + $TotaleRimanenze + $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + $AttivoRateiRisconti - $TotaleRimanenze) / ($ACID_TEST_Ordinario_divisore))) * 100;
        $calculation = '(((' . $TotaleDisponibilitaLiquide . ' + ' . $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo . ' + ' . $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo . ' + ' . $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo . ' + ' . $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo . ' + ' . $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo . ' + ' . $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo . ' + ' . $TotaleRimanenze . ' + ' . $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni . ' + ' . $AttivoRateiRisconti . ' - ' . $TotaleRimanenze . ') / (' . $ACID_TEST_Ordinario_divisore . '))) * ' . 100 . ' = ' . $ACID_TEST_Ordinario;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAcidTestOrdinario', $calculation);

        return $ACID_TEST_Ordinario;
    }

    public function getAutonomiaFinanziaria()
    {
        $TotalePatrimonioNetto = $this->getTotalePatrimonioNetto('Autonomia Finanziaria');
        $TotaleDebiti = $this->getElementFromBalance('TotaleDebiti', 1, 'Autonomia Finanziaria');

        if (!$TotalePatrimonioNetto || !$TotaleDebiti)
            return false;

        $AUTONOMIA_FINANZIARIA = (float)(($TotalePatrimonioNetto / ($TotalePatrimonioNetto + $TotaleDebiti))) * 100;
        $calculation = '((' . (float)$TotalePatrimonioNetto . ' / (' . $TotalePatrimonioNetto . ' + ' . $TotaleDebiti . '))) * ' . 100 . ' = ' . $AUTONOMIA_FINANZIARIA;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAutonomiaFinanziaria', $calculation);

        return $AUTONOMIA_FINANZIARIA;
    }

    public function getLivelloInvestimentiAziendali()
    {
        $TotalePatrimonioNetto = $this->getTotalePatrimonioNetto('Livello Investimenti Aziendali');
        $TotaleAttivo = $this->getElementFromBalance('TotaleAttivo', 1, 'Livello Investimenti Aziendali');

        if (!$TotalePatrimonioNetto || !$TotaleAttivo)
            return false;

        $LIVELLO_INVESTIMENTI_AZIENDALI = (float)($TotalePatrimonioNetto / $TotaleAttivo) * 100;
        $calculation = '(' . (float)$TotalePatrimonioNetto . ' / ' . $TotaleAttivo . ') * ' . 100 . ' = ' . $LIVELLO_INVESTIMENTI_AZIENDALI;


        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetLivelloInvestimentiAziendali', $calculation);

        return $LIVELLO_INVESTIMENTI_AZIENDALI;
    }

    public function getPfnEbitda()
    {
        $getAndamentoDelMol = $this->getAndamentoDelMol('Pfn Ebitda');
        // $MOLcurr = number_format($getAndamentoDelMol['MOLcurr'], '.', ',');

        $ImmobilizzazioniFinanziarieCreditiTotaleCrediti = $this->getElementFromBalance('ImmobilizzazioniFinanziarieCreditiTotaleCrediti', 1, 'Pfn Ebitda');
        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 1, 'Pfn Ebitda');
        $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 1, 'Pfn Ebitda');
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 1, 'Pfn Ebitda');
        $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 1, 'Pfn Ebitda');
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 1, 'Pfn Ebitda');
        $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 1, 'Pfn Ebitda');
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 1, 'Pfn Ebitda');
        $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 1, 'Pfn Ebitda');
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 1, 'Pfn Ebitda');
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 1, 'Pfn Ebitda');
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1, 'Pfn Ebitda');

        if (!$ImmobilizzazioniFinanziarieCreditiTotaleCrediti || !$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo || !$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo || !$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo || !$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo || !$TotaleDisponibilitaLiquide)
            return false;

        $MOLcurr = $getAndamentoDelMol['MOLcurr'];


        $debitiFinanziariCurr = $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;

        $PFN_EBITDA = (($debitiFinanziariCurr - $TotaleDisponibilitaLiquide - $ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / $MOLcurr);
        $calculation = '((' . $debitiFinanziariCurr . ' - ' . $TotaleDisponibilitaLiquide . ' - ' . $ImmobilizzazioniFinanziarieCreditiTotaleCrediti . ') / ' . $MOLcurr . ') = ' . $PFN_EBITDA . ' * ' . 100;


        $PFN_EBITDA = $PFN_EBITDA * 100;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetPfnEbitda', $calculation);

        return $PFN_EBITDA;
    }

    public function getPesoOneriFinanziari()
    {
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1, 'Peso Oneri Finanziari');
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $this->getElementFromBalance('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 1, 'Peso Oneri Finanziari');


        if (!$ValoreProduzioneRicaviVenditePrestazioni || !$ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari)
            return false;


        $Peso_Oneri_Finanziari = ($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari / $ValoreProduzioneRicaviVenditePrestazioni) * 100;
        $calculation = '(' . $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari . ' / ' . $ValoreProduzioneRicaviVenditePrestazioni . ') * ' . 100 . ' = ' . $Peso_Oneri_Finanziari;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetPesoOneriFinanziari', $calculation);

        return $Peso_Oneri_Finanziari;
    }

    public function getCoperturaLordaDegliOneriFinanziari()
    {
        $TotaleValoreProduzione = $this->getElementFromBalance('TotaleValoreProduzione', 1, 'Copertura Lorda Degli Oneri Finanziari');
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 1, 'Copertura Lorda Degli Oneri Finanziari');
        $CostiProduzioneGodimentoBeniTerzi = $this->getElementFromBalance('CostiProduzioneGodimentoBeniTerzi', 1, 'Copertura Lorda Degli Oneri Finanziari');
        $CostiProduzioneServizi = $this->getElementFromBalance('CostiProduzioneServizi', 1, 'Copertura Lorda Degli Oneri Finanziari');
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getElementFromBalance('CostiProduzionePersonaleTotaleCostiPersonale', 1, 'Copertura Lorda Degli Oneri Finanziari');
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 1, 'Copertura Lorda Degli Oneri Finanziari');
        $CostiProduzioneOneriDiversiGestione = $this->getElementFromBalance('CostiProduzioneOneriDiversiGestione', 1, 'Copertura Lorda Degli Oneri Finanziari');
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $this->getElementFromBalance('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 1, 'Copertura Lorda Degli Oneri Finanziari');

        if (!$TotaleValoreProduzione || !$CostiProduzioneMateriePrimeSussidiarieConsumoMerci || !$CostiProduzioneGodimentoBeniTerzi || !$CostiProduzioneServizi || !$CostiProduzionePersonaleTotaleCostiPersonale || !$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci || !$CostiProduzioneOneriDiversiGestione || !$ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari)
            return false;

        $Copertura_Lorda_degli_Oneri_Finanziari = (($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari);
        $calculation = '((' . $TotaleValoreProduzione . ' - ' . $CostiProduzioneMateriePrimeSussidiarieConsumoMerci . ' - ' . $CostiProduzioneServizi . ' - ' . $CostiProduzioneGodimentoBeniTerzi . ' - ' . $CostiProduzionePersonaleTotaleCostiPersonale . ' - ' . $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci . ' - ' . $CostiProduzioneOneriDiversiGestione . ') / ' . $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari . ') = ' . (float)$Copertura_Lorda_degli_Oneri_Finanziari . ' * ' . 100;

        $Copertura_Lorda_degli_Oneri_Finanziari = (float)$Copertura_Lorda_degli_Oneri_Finanziari * 100;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetCoperturaLordaDegliOneriFinanziari', $calculation);

        return $Copertura_Lorda_degli_Oneri_Finanziari;
    }

    public function getEbitOf()
    {
        $TotaleValoreProduzione = $this->getElementFromBalance('TotaleValoreProduzione', 1, 'Ebit Of');
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 1, 'Ebit Of');
        $CostiProduzioneGodimentoBeniTerzi = $this->getElementFromBalance('CostiProduzioneGodimentoBeniTerzi', 1, 'Ebit Of');
        $CostiProduzioneServizi = $this->getElementFromBalance('CostiProduzioneServizi', 1, 'Ebit Of');
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getElementFromBalance('CostiProduzionePersonaleTotaleCostiPersonale', 1, 'Ebit Of');
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 1, 'Ebit Of');
        $CostiProduzioneOneriDiversiGestione = $this->getElementFromBalance('CostiProduzioneOneriDiversiGestione', 1, 'Ebit Of');
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $this->getElementFromBalance('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 1, 'Ebit Of');
        $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni = $this->getElementFromBalance('CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 1, 'Ebit Of');
        $CostiProduzioneAccantonamentiRischi = $this->getElementFromBalance('CostiProduzioneAccantonamentiRischi', 1, 'Ebit Of');
        $CostiProduzioneAltriAccantonamenti = $this->getElementFromBalance('CostiProduzioneAltriAccantonamenti', 1, 'Ebit Of');

        if (!$TotaleValoreProduzione || !$CostiProduzioneMateriePrimeSussidiarieConsumoMerci || !$CostiProduzioneGodimentoBeniTerzi || !$CostiProduzioneServizi || !$CostiProduzionePersonaleTotaleCostiPersonale || !$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci || !$CostiProduzioneOneriDiversiGestione || !$ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari || !$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni || !$CostiProduzioneAccantonamentiRischi || !$CostiProduzioneAltriAccantonamenti)
            return false;

        $EBIT_OF = (($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione - $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - $CostiProduzioneAccantonamentiRischi - $CostiProduzioneAltriAccantonamenti) / $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari);
        $calculation = '((' . $TotaleValoreProduzione . ' - ' . $CostiProduzioneMateriePrimeSussidiarieConsumoMerci . ' - ' . $CostiProduzioneServizi . ' - ' . $CostiProduzioneGodimentoBeniTerzi . ' - ' . $CostiProduzionePersonaleTotaleCostiPersonale . ' - ' . $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci . ' - ' . $CostiProduzioneOneriDiversiGestione . ' - ' . $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni . ' - ' . $CostiProduzioneAccantonamentiRischi . ' - ' . $CostiProduzioneAltriAccantonamenti . ') / ' . $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari . ')  ' . (float)$EBIT_OF . ' * ' . 100 . ' = ' . (float)$EBIT_OF * 100;

        $EBIT_OF = (float)$EBIT_OF * 100;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetEbitOf', $calculation);

        return $EBIT_OF;
    }

    public function getCostoDelPersonale()
    {
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getElementFromBalance('CostiProduzionePersonaleTotaleCostiPersonale', 1, 'Costo Del Personale');
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1, 'Costo Del Personale');

        if (!$CostiProduzionePersonaleTotaleCostiPersonale || !$ValoreProduzioneRicaviVenditePrestazioni)
            return false;

        $Costo_del_personale = (float)($CostiProduzionePersonaleTotaleCostiPersonale / $ValoreProduzioneRicaviVenditePrestazioni) * 100;
        $calculation = '(' . (float)$CostiProduzionePersonaleTotaleCostiPersonale . ' / ' . $ValoreProduzioneRicaviVenditePrestazioni . ') * ' . 100 . ' = ' . $Costo_del_personale;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetCostoDelPersonale', $calculation);

        return $Costo_del_personale;
    }

    public function getCfAttivo()
    {
        $TotaleAttivo = $this->getElementFromBalance('TotaleAttivo', 1, 'CF Attivo');
        $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni = $this->getElementFromBalance('CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 1, 'CF Attivo');
        $CostiProduzioneAccantonamentiRischi = $this->getElementFromBalance('CostiProduzioneAccantonamentiRischi', 1, 'CF Attivo');
        $CostiProduzioneAltriAccantonamenti = $this->getElementFromBalance('CostiProduzioneAltriAccantonamenti', 1, 'CF Attivo');
        $UtilePerditaEsercizio = $this->getElementFromBalance('UtilePerditaEsercizio', 1, 'CF Attivo');
        $imposteRedditoEsercizioImposteAnticipate = $this->getElementFromBalance('ImposteRedditoEsercizioCorrentiDifferiteAnticipateImposteDifferiteAnticipate', 1, 'CF Attivo');

        if (!$TotaleAttivo || !$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni || !$CostiProduzioneAccantonamentiRischi || !$CostiProduzioneAltriAccantonamenti || !$UtilePerditaEsercizio || !$imposteRedditoEsercizioImposteAnticipate)
            return false;

        $CF_ATTIVO = (($UtilePerditaEsercizio + $CostiProduzioneAccantonamentiRischi + $CostiProduzioneAltriAccantonamenti + $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - $imposteRedditoEsercizioImposteAnticipate) / $TotaleAttivo) * 100;
        $calculation = '((' . $UtilePerditaEsercizio . ' + ' . $CostiProduzioneAccantonamentiRischi . ' + ' . $CostiProduzioneAltriAccantonamenti . ' + ' . $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni . ' - ' . $imposteRedditoEsercizioImposteAnticipate . ') / ' . $TotaleAttivo . ') * ' . 100;


        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetCfAttivo', $calculation);

        return $CF_ATTIVO;
    }

    public function getIndiceDiIndebitamento()
    {
        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 1, 'Indice di Indebitamento');
        $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 1, 'Indice di Indebitamento');
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 1, 'Indice di Indebitamento');
        $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 1, 'Indice di Indebitamento');
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 1, 'Indice di Indebitamento');
        $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 1, 'Indice di Indebitamento');
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 1, 'Indice di Indebitamento');
        $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 1, 'Indice di Indebitamento');
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 1, 'Indice di Indebitamento');
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 1, 'Indice di Indebitamento');
        $TotalePatrimonioNetto = $this->getTotalePatrimonioNetto('Indice di Indebitamento');
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1, 'Indice di Indebitamento');
        $ImmobilizzazioniFinanziarieCreditiTotaleCrediti = $this->getElementFromBalance('ImmobilizzazioniFinanziarieCreditiTotaleCrediti', 1, 'Indice di Indebitamento');

        if (!$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo || !$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo || !$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo || !$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo || !$TotalePatrimonioNetto || !$TotaleDisponibilitaLiquide || !$ImmobilizzazioniFinanziarieCreditiTotaleCrediti)
            return false;

        $MOLannoCorrente = $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;
        $Indice_di_Indebitamento = (($MOLannoCorrente - $TotaleDisponibilitaLiquide - $ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / $TotalePatrimonioNetto) * 100;
        $calculation = '((' . $MOLannoCorrente . ' - ' . $TotaleDisponibilitaLiquide . ' - ' . $ImmobilizzazioniFinanziarieCreditiTotaleCrediti . ') / ' . $TotalePatrimonioNetto . ') * ' . 100 . ' = ' . $Indice_di_Indebitamento;
        $dataAnalisis['Indice_di_Indebitamento'] = $Indice_di_Indebitamento;


        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetIndiceDiIndebitamento', $calculation);

        return $Indice_di_Indebitamento;
    }

    public function getSaldoDebitiVsFisco()
    {
        $DebitiDebitiTributariTotaleDebitiTributariCorrente = $this->getElementFromBalance('DebitiDebitiTributariTotaleDebitiTributari', 1, 'Saldo Debiti Vs Fisco');
        $FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente = $this->getElementFromBalance('FondiRischiOneriTrattamentoQuiescenzaObblighiSimili', 1, 'Saldo Debiti Vs Fisco');
        $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipatePrecedente = $this->getElementFromBalance('ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate', 2, 'Saldo Debiti Vs Fisco');
        $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate = $this->getElementFromBalance('ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate', 1, 'Saldo Debiti Vs Fisco');

        if (!$DebitiDebitiTributariTotaleDebitiTributariCorrente || !$FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente || !$ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipatePrecedente || !$ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate)
            return false;

        $DifferenzaImposteReddito = ($ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate + $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipatePrecedente) / 2;

        $SaldoDebitiVSFisco = ($FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente + $DebitiDebitiTributariTotaleDebitiTributariCorrente) / $DifferenzaImposteReddito;
        $calculation = '(' . $FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente . ' + ' . $DebitiDebitiTributariTotaleDebitiTributariCorrente . ') / ' . $DifferenzaImposteReddito . ' = ' . $SaldoDebitiVSFisco . ' * ' . 100 . ' = ' . $SaldoDebitiVSFisco * 100;


        $SaldoDebitiVSFisco = $SaldoDebitiVSFisco * 100;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetSaldoDebitiVsFisco', $calculation);

        return $SaldoDebitiVSFisco;
    }

    public function getTipoAzienda() {
            return "FORN. ACQUA E RETI FOGNARIE RIFIUTI, TRASM. ENERGIA/GAS";
    }

    public function getSostenibilitaOneriFinanziari() {

        $returnData = array(
            'fuoriSoglia' => false,
            'value' => false
        );
        $tipoAzienda =  $this->getTipoAzienda();
        $of_fatturato = $this->getOfRicavi('SostenibilitaOneriFinanziari');

        if(!$of_fatturato)
            return false;

        $returnData['value'] = $of_fatturato;

        $sostenibilitaOneriFinanziari = DB::table('rangesBasic')
            ->where('tipo_azienda', '=', $tipoAzienda)
            ->where('indice', '=', 'Sostenibilità Oneri Finanziari')
            ->where('soglia', '>', $of_fatturato)
            ->count();

        if($sostenibilitaOneriFinanziari) {
            $returnData['fuoriSoglia'] = true;
        }

        return $returnData;
    }

    public function getAdeguatezzaPatrimonialeEvaluation() {

        $returnData = array(
            'fuoriSoglia' => false,
            'value' => false
        );
        $tipoAzienda =  $this->getTipoAzienda();
        $adeguatezza_patrimoniale = $this->getAdeguatezzaPatrimoniale('AdeguatezzaPatrimoniale');

        if(!$adeguatezza_patrimoniale)
            return false;

        $returnData['value'] = $adeguatezza_patrimoniale;

        $evaluation = DB::table('rangesBasic')
            ->where('tipo_azienda', '=', $tipoAzienda)
            ->where('indice', '=', 'Adeguatezza Patrimoniale')
            ->where('soglia', '>', $adeguatezza_patrimoniale)
            ->count();

        if($evaluation) {
            $returnData['fuoriSoglia'] = true;
        }

        return $returnData;
    }

    public function getLiquiditaEvaluation() {

        $returnData = array(
            'fuoriSoglia' => false,
            'value' => false
        );
        $tipoAzienda =  $this->getTipoAzienda();
        $liquidita = $this->getLiquidita();

        if(!$liquidita)
            return false;

        $returnData['value'] = $liquidita;

        $evaluation = DB::table('rangesBasic')
            ->where('tipo_azienda', '=', $tipoAzienda)
            ->where('indice', '=', 'Liquidità')
            ->where('soglia', '>', $liquidita)
            ->count();

        if($evaluation) {
            $returnData['fuoriSoglia'] = true;
        }

        return $returnData;
    }

    public function getIndebitamentoPrevidenziale() {

        $returnData = array(
            'fuoriSoglia' => false,
            'value' => false
        );
        $tipoAzienda =  $this->getTipoAzienda();
        $indebitamentoTributario = $this->getIndebitamentoPrevidenzialeTributario();

        if(!$indebitamentoTributario)
            return false;

        $returnData['value'] = $indebitamentoTributario;

        $evaluation = DB::table('rangesBasic')
            ->where('tipo_azienda', '=', $tipoAzienda)
            ->where('indice', '=', 'Liquidità')
            ->where('soglia', '>', $indebitamentoTributario)
            ->count();

        if($evaluation) {
            $returnData['fuoriSoglia'] = true;
        }

        return $returnData;
    }

    public function getRitornoLiquidoAttivo() {

        $returnData = array(
            'fuoriSoglia' => false,
            'value' => false
        );
        $tipoAzienda =  $this->getTipoAzienda();
        $currentRatio = $this->getCurrentRatio();

        if(!$currentRatio)
            return false;

        $returnData['value'] = $currentRatio;

        $evaluation = DB::table('rangesBasic')
            ->where('tipo_azienda', '=', $tipoAzienda)
            ->where('indice', '=', 'Ritorno Liquido Attivo')
            ->where('soglia', '>', $currentRatio)
            ->count();

        if($evaluation) {
            $returnData['fuoriSoglia'] = true;
        }

        return $returnData;
    }

    public function getIndiceCNDCECEvaluation() {
        if($getSostenibilitaOneriFinanziari = $this->getSostenibilitaOneriFinanziari()) {
            if($getSostenibilitaOneriFinanziari['fuoriSoglia']) return true;
        }
        if($getAdeguatezzaPatrimonialeEvaluation = $this->getAdeguatezzaPatrimonialeEvaluation()) {
            if($getAdeguatezzaPatrimonialeEvaluation['fuoriSoglia']) return true;
        }
        if($getLiquiditaEvaluation = $this->getLiquiditaEvaluation()) {
            if($getLiquiditaEvaluation['fuoriSoglia']) return true;
        }
        if($getIndebitamentoPrevidenziale = $this->getIndebitamentoPrevidenziale()) {
            if($getIndebitamentoPrevidenziale['fuoriSoglia']) return true;
        }
        if($getRitornoLiquidoAttivo = $this->getRitornoLiquidoAttivo()) {
            if($getRitornoLiquidoAttivo['fuoriSoglia']) return true;
        }

        return false;
    }

    public function getIndiceCNDCEC() {
        if($this->getIndiceCNDCECEvaluation()) return "Azienda a Rischio";

        return "Azienda NON a Rischio";
    }


    public function calcoloINPS($allData)
    {
        if (empty($allData['INPS1']) || $allData['INPS1'] == 0 || empty($allData['INPS2']) || $allData['INPS2'] == 0) {
            $alert = "Dati Mancanti";
        } else {
            $alert = "No";
        }

        if ($alert != "Dati Mancanti") {
            if(str_contains($allData['INPS1'], ',') && str_contains($allData['INPS2'], ',')) {
                $inps3 = ((str_replace(',', '', str_replace('.', '', $allData['INPS1'])) / str_replace(',', '', str_replace('.', '', $allData['INPS2']))) * 100);
            } else {
                $inps3 = (($allData['INPS1'] / $allData['INPS2']) * 100);
            }

            if ($allData['INPS1'] > 50000) {
                if ($inps3 > 50.50) {
                    $alert = "Si";
                }
            }
        } else {
            $inps3 = "NON CALCOLABILE";
        }

        $data = [
            'alert' => $alert,
            'inps3' => $inps3
        ];

        return $data;
    }

    public function calculateRiscossione($allData)
    {
        if(str_contains($allData['idBilancio'], '"')) {
            $allData['idBilancio'] = str_replace('"', '', $allData['idBilancio']);
        }

        $balance = Bilanci::findOrFail($allData['idBilancio']);

        $formaGiuridica = $balance->forma_giuridica;

        if (empty($allData['riscossione']) || $allData['riscossione'] == 0) {
            $alert = "Dati Mancanti";
        } else {
            $alert = "No";
        }

        if ($formaGiuridica == "DITTA INDIVIDUALE") {
            if ($allData['riscossione'] > 500000) {
                $alert = "Si";
            } else {
                $alert = "No";
            }
        } else {
            if ($allData['riscossione'] > 1000000) {
                $alert = "Si";
            } else {
                $alert = "No";
            }
        }

        return $alert;
    }

    public function calculateRetribuzione($allData)
    {

        if (empty($allData['retribuzioni1']) || $allData['retribuzioni1'] == 0 || empty($allData['retribuzioni2']) || $allData['retribuzioni2'] == 0) {
            $alert = "Dati Mancanti";
        } else {
            $alert = "No";
        }

        if ($alert != "Dati Mancanti") {
            if(str_contains($allData['retribuzioni1'], ',') || str_contains($allData['retribuzioni2'], ',')) {
                if ((str_replace(',', '.', str_replace('.', '', $allData['retribuzioni1'])) / str_replace(',', '.', str_replace('.', '', $allData['retribuzioni2']))) * 100 >= 50) {
                    $alert = "Si";
                } else {
                    $alert = "No";
                }
                if (is_finite(str_replace(',', '.', str_replace('.', '', $allData['retribuzioni1'])) / str_replace(',', '.', str_replace('.', '', $allData['retribuzioni2'])))) {
                    $cleanData['retribuzioni3'] = number_format(((str_replace(',', '.', str_replace('.', '', $allData['retribuzioni1'])) / str_replace(',', '.', str_replace('.', '', $allData['retribuzioni2']))) * 100), 2, ".", ",");
                } else {
                    $cleanData['retribuzioni3'] = "NON CALCOLABILE";
                }
            } else {
                if ((str_replace(',', '.', str_replace('.', '', $allData['retribuzioni1'])) / str_replace(',', '.', str_replace('.', '', $allData['retribuzioni2']))) * 100 >= 50) {
                    $alert = "Si";
                } else {
                    $alert = "No";
                }
                if (is_finite(str_replace(',', '.', str_replace('.', '', $allData['retribuzioni1'])) / str_replace(',', '.', str_replace('.', '', $allData['retribuzioni2'])))) {
                    $cleanData['retribuzioni3'] = number_format(((str_replace(',', '.', str_replace('.', '', $allData['retribuzioni1'])) / str_replace(',', '.', str_replace('.', '', $allData['retribuzioni2']))) * 100), 2, ".", ",");
                } else {
                    $cleanData['retribuzioni3'] = "NON CALCOLABILE";
                }
            }
        }

        $data = [
            'alert' => $alert,
            'retribuzioni3' => (isset($cleanData)) ? $cleanData['retribuzioni3'] : "NON CALCOLABILE"
        ];

        return $data;
    }

    public function calculateFornitori($allData)
    {
        if (empty($allData['fornitori1']) || $allData['fornitori1'] == 0 || empty($allData['fornitori2']) || $allData['fornitori2'] == 0) {
            $alert = "Dati Mancanti";
        } else {
            $alert = "No";
        }

        if ($allData['fornitori1'] > $allData['fornitori2']) {
            $alert = "Si";
        }

        return $alert;
    }

    public function calculateAgenziaEntrate($allData)
    {

        if (empty($allData['agenziaEntrate1']) || $allData['agenziaEntrate1'] == 0 || empty($allData['agenziaEntrate2']) || $allData['agenziaEntrate2'] == 0) {
            $alert = "Dati Mancanti";
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

                if (is_finite($allData['agenziaEntrate1'] / $allData['agenziaEntrate2'])) {
                    // dd((float)number_format((((float)$allData['agenziaEntrate1'] / (float)$allData['agenziaEntrate2'])), 2, ",", "."));
                    $cleanData['agenziaEntrate4'] = number_format((($allData['agenziaEntrate1'] / $allData['agenziaEntrate2'])), 2, ",", ".");
                } else {
                    $cleanData['agenziaEntrate4'] = "NON CALCOLABILE";
                }
            }

            $data = [
                'alert' => $alert,
                'agenziaEntrate4' => $cleanData
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

    private function calculateDSCR($inboundData)
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
            CustomLog::addToLogBilanciHelper('BilanciHelper', 'GetResultCalculateDSCR', $sum.' / '.$divisore.' = '.$result);

            return number_format($result, 2, ",", ".");
        } catch (Exception $e) {
            CustomLog::addToLogBilanciHelper('BilanciHelper', 'getExceptionCalculateDSCR', json_encode($e->getMessage()));

            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getCalcoloDSCR($allData)
    {

        $dscrData = $this->getDSCRArrayData($allData);

        if ($dscrData['DSCR'] != 1) {
            return "DSCR da non calcolare";
        }

        if (empty($dscrData['uscitaDSCRCFmese6']) || $dscrData['uscitaDSCRCFmese6'] == 0 || empty($dscrData['rimborsoDSCRmese1']) || $dscrData['rimborsoDSCRmese1'] == 0) {
            CustomLog::addToLogBilanciHelper('BilanciHelper', 'GetEmptyCalculateDSCR', json_encode(["uscitaDSCRCFmese6" => $dscrData['uscitaDSCRCFmese6'], "rimborsoDSCRmese1" => $dscrData['rimborsoDSCRmese1']]));
            return ['error' => true];
        }


        return $this->calculateDSCR($dscrData);
    }

}
