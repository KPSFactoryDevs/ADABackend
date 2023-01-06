<?php

namespace App\Helpers\Bilanci;

use Illuminate\Support\Facades\DB;
use stdClass;
use App\Models\CustomLog;

class BilanciCalculationsHelperAdvanced
{

    public $_currentInstance = false;

    public $_missingVoicesArray = array();


    public function setCurrentInstance($instanceDocument)
    {
        $this->_currentInstance = $instanceDocument;
    }

    public function getElementFromBalance($elementName, $period, $indexName = "test")
    {

        $value = false;
        $elements = $this->_currentInstance->getElements();
        $elements = $elements->getElements();

// query che cerca elemento nel db
        $elementoFromQuery = false;


        if(isset($elements[$elementName]) || $elementoFromQuery) {
            if($period == 1) {
                $value = array_first($elements[$elementName])['value'];
            } else if($period == 2) {
                $value = array_last($elements[$elementName])['value'];
            }

            return $value;
        } else {
            if(!isset($this->_missingVoicesArray[$indexName])) {
                $this->_missingVoicesArray[$indexName] = array(
                    $period => [],
                );

                array_push($this->_missingVoicesArray[$indexName][$period], $elementName);
            } else {
                if (!isset($this->_missingVoicesArray[$indexName][$period])) {
                    $this->_missingVoicesArray[$indexName][$period] = [];
                    array_push($this->_missingVoicesArray[$indexName][$period], $elementName);
                } else {
                    if (!isset($this->_missingVoicesArray[$indexName][$period][$elementName])) {
                        array_push($this->_missingVoicesArray[$indexName][$period], $elementName);
                    }
                }
            }

            return false;
        }
    }


    public function getTotalePatrimonioNetto($indexName)
    {
        $TotalePatrimonioNetto = $this->getElementFromBalance('TotalePatrimonioNetto', 1, $indexName);

        return $TotalePatrimonioNetto;
    }

    public function getPatrimonioNettoNegativo($indexName)
    {
        $TotalePatrimonioNetto = $this->getTotalePatrimonioNetto($indexName);
        $TotaleCreditiVersoSociVersamentiAncoraDovuti = $this->getElementFromBalance('TotaleCreditiVersoSociVersamentiAncoraDovuti', 1, $indexName);
      //  $TotalePatrimonioNetto = str_replace(',', '.', str_replace('.', '', $TotalePatrimonioNetto));

        if (!$TotalePatrimonioNetto || !$TotaleCreditiVersoSociVersamentiAncoraDovuti)
            return false;

        $PN_NEGATIVO = $TotalePatrimonioNetto - $TotaleCreditiVersoSociVersamentiAncoraDovuti;
        $calculationPNnegativo = $TotalePatrimonioNetto.' - '.$TotaleCreditiVersoSociVersamentiAncoraDovuti.' = '.$PN_NEGATIVO;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetPatrimonioNettoNegativo', $calculationPNnegativo);


        return  $PN_NEGATIVO;
    }

    public function getOfRicavi()
    {
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari =  $this->getElementFromBalance('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 1, 'OF Ricavi');
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1, 'OF Ricavi');

        if (!$ValoreProduzioneRicaviVenditePrestazioni || !$ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari) {
            return false;
        }

        $OF_RICAVI = ($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari / $ValoreProduzioneRicaviVenditePrestazioni) * 100;
        $calculation = $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari.' / '.$ValoreProduzioneRicaviVenditePrestazioni.' * '. 100 .' = '.$OF_RICAVI;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetOF_RICAVI', $calculation);

        return $OF_RICAVI;
    }

    public function getTotaleDebiti($indexName)
    {
        $TotaleDebiti = $this->getElementFromBalance('TotaleDebiti', 1, $indexName);

        if (!$TotaleDebiti)
            return false;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetTotaleDebiti', $TotaleDebiti);

        return $TotaleDebiti;
    }

    public function getAdeguatezzaPatrimoniale()
    {
        $PN_NEGATIVO = $this->getPatrimonioNettoNegativo('Adeguatezza Patrimoniale');
        $TotaleDebiti = $this->getTotaleDebiti('Adeguatezza Patrimoniale');
        $PassivoRateiRisconti = $this->getElementFromBalance('PassivoRateiRisconti', 1, 'Adeguatezza Patrimoniale');

        if(!$PN_NEGATIVO || !$TotaleDebiti || !$PassivoRateiRisconti){
            return false;
        }
        $ADEGUATEZZA_PATRIMONIALE = ($PN_NEGATIVO / ($TotaleDebiti + $PassivoRateiRisconti)) * 100;
        $calculation = $PN_NEGATIVO.' / ('.$TotaleDebiti.' + '.$PassivoRateiRisconti.') * '. 100;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAdeguatezzaPatrimoniale', $calculation);

        return $ADEGUATEZZA_PATRIMONIALE;
    }

    public function getTotaleCreditiEntroDodiciMesi()
    {
        $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo', 1, 'Totale Crediti Entro Dodici Mesi');
        $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 1, 'Totale Crediti Entro Dodici Mesi');
        $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1, 'Totale Crediti Entro Dodici Mesi');
        $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 1, 'Totale Crediti Entro Dodici Mesi');
        $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 1, 'Totale Crediti Entro Dodici Mesi');
        $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 1, 'Totale Crediti Entro Dodici Mesi');
        $CreditiImposteAnticipateTotaleImposteAnticipate = $this->getElementFromBalance('CreditiImposteAnticipateTotaleImposteAnticipate', 1, 'Totale Crediti Entro Dodici Mesi');

        if(!$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo || !$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo || !$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo || !$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo || !$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo || !$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo || !$CreditiImposteAnticipateTotaleImposteAnticipate ) {
            return false;
        }

        $TotaleDebitiEntroDodiciMesi = (float)$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + (float)$CreditiImposteAnticipateTotaleImposteAnticipate + (float)$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo;
        $calculation = (float)$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo.' + '.(float)$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo.' + '.(float)$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo.' + '.(float)$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo.' + '.(float)$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo.' + '.(float)$CreditiImposteAnticipateTotaleImposteAnticipate.' + '.(float)$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo.' = '.$TotaleDebitiEntroDodiciMesi;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetTotaleCreditiEntroDodiciMesi', $calculation);

        return $TotaleDebitiEntroDodiciMesi;
    }

    public function getTotaleDebitiEntroDodiciMesi()
    {
        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 1, 'Totale Debiti Entro Dodici Mesi');
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 1, 'Totale Debiti Entro Dodici Mesi');
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 1, 'Totale Debiti Entro Dodici Mesi');
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 1, 'Totale Debiti Entro Dodici Mesi');
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 1, 'Totale Debiti Entro Dodici Mesi');
        $DebitiAccontiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiAccontiEsigibiliEntroEsercizioSuccessivo', 1, 'Totale Debiti Entro Dodici Mesi');
        $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 1, 'Totale Debiti Entro Dodici Mesi');
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo', 1, 'Totale Debiti Entro Dodici Mesi');
        $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 1, 'Totale Debiti Entro Dodici Mesi');
        $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1, 'Totale Debiti Entro Dodici Mesi');
        $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 1, 'Totale Debiti Entro Dodici Mesi');
        $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 1, 'Totale Debiti Entro Dodici Mesi');
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 1, 'Totale Debiti Entro Dodici Mesi');
        $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo', 1, 'Totale Debiti Entro Dodici Mesi');

        if(!$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo || !$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo || !$DebitiAccontiEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo || !$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo || !$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo) {
            return false;
        }

        $TotaleDebitiEntroDodiciMesi = (float)$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAccontiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo;

        $calculation = (float)$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiAccontiEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo.' = '.$TotaleDebitiEntroDodiciMesi;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetTotaleDebitiEntroDodiciMesi', $calculation);

        return $TotaleDebitiEntroDodiciMesi;
    }

    public function getLiquidita()
    {
        $CostiProduzioneAccantonamentiRischi = $this->getElementFromBalance('CostiProduzioneAccantonamentiRischi', 1, 'Liquidità');
        $TotaleAttivo = $this->getElementFromBalance('TotaleAttivo', 1, 'Liquidità');
        $UtilePerditaEsercizio = $this->getElementFromBalance('UtilePerditaEsercizio', 1, 'Liquidità');
        $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni = $this->getElementFromBalance('CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 1, 'Liquidità');
        $CostiProduzioneAltriAccantonamenti = $this->getElementFromBalance('CostiProduzioneAltriAccantonamenti', 1, 'Liquidità');

            $LIQUIDITA = (($UtilePerditaEsercizio + $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni + (float)$CostiProduzioneAccantonamentiRischi + (float)$CostiProduzioneAltriAccantonamenti) / (float)$TotaleAttivo) * 100;
            $calculationLIQUIDITA = '(('.$UtilePerditaEsercizio.' + '.$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni.' + '.(float)$CostiProduzioneAccantonamentiRischi.' + '.(float)$CostiProduzioneAltriAccantonamenti.') / '.(float)$TotaleAttivo.') * '. 100;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetLiquidità', $calculationLIQUIDITA);

        return $LIQUIDITA;
    }

    public function getIndebitamentoPrevidenzialeTributario()
    {
        $DebitiDebitiTributariTotaleDebitiTributari = $this->getElementFromBalance('DebitiDebitiTributariTotaleDebitiTributari', 1, 'Indebitamento Previdenziale Tributario');
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale = $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale', 1, 'Indebitamento Previdenziale Tributario');
        $TotaleAttivo = $this->getElementFromBalance('TotaleAttivo', 1, 'Indebitamento Previdenziale Tributario');

        if (!$DebitiDebitiTributariTotaleDebitiTributari || !$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale || !$TotaleAttivo)
            return false;

            $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO = (($DebitiDebitiTributariTotaleDebitiTributari + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) / $TotaleAttivo) * 100;
            $calculation = '(('.$DebitiDebitiTributariTotaleDebitiTributari.' + '.$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale.') / '. $TotaleAttivo .') * '. 100 .' = '.$INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetIndebitamentoPrevidenzialeTributario', $calculation);

        return $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO;
    }

    public function getAndamentoDelFatturato()
    {
        $ValoreProduzioneRicaviVenditePrestazioniCurr = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1, 'Andamento Del Fatturato');
        $ValoreProduzioneRicaviVenditePrestazioniPrev = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 2, 'Andamento Del Fatturato');

            $AndamentoDelFatturato = (- (1 - (($ValoreProduzioneRicaviVenditePrestazioniCurr) / ($ValoreProduzioneRicaviVenditePrestazioniPrev)))) * 100;
            $calculation = '(- ('. 1 .' - (('.$ValoreProduzioneRicaviVenditePrestazioniCurr.') / ('.$ValoreProduzioneRicaviVenditePrestazioniPrev.')))) * '. 100 .' = '.$AndamentoDelFatturato;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAndamentoDelFatturato', $calculation);

        return $AndamentoDelFatturato;
    }

    public function getAndamentoDelMol()
    {
        // Curr
        $TotaleValoreProduzione = $this->getElementFromBalance('TotaleValoreProduzione', 1, 'Andamento Del Mol');
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 1, 'Andamento Del Mol');
        $CostiProduzioneGodimentoBeniTerzi = $this->getElementFromBalance('CostiProduzioneGodimentoBeniTerzi', 1, 'Andamento Del Mol');
        $CostiProduzioneServizi = $this->getElementFromBalance('CostiProduzioneServizi', 1, 'Andamento Del Mol');
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getElementFromBalance('CostiProduzionePersonaleTotaleCostiPersonale', 1, 'Andamento Del Mol');
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 1, 'Andamento Del Mol');
        $CostiProduzioneOneriDiversiGestione = $this->getElementFromBalance('CostiProduzioneOneriDiversiGestione', 1, 'Andamento Del Mol');

        // Prev
        $TotaleValoreProduzionePrecedente = $this->getElementFromBalance('TotaleValoreProduzione', 2, 'Andamento Del Mol');
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerciPrecedente = $this->getElementFromBalance('CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 2, 'Andamento Del Mol');
        $CostiProduzioneGodimentoBeniTerziPrecedente = $this->getElementFromBalance('CostiProduzioneGodimentoBeniTerzi', 2, 'Andamento Del Mol');
        $CostiProduzioneServiziPrecedente = $this->getElementFromBalance('CostiProduzioneServizi', 2, 'Andamento Del Mol');
        $CostiProduzionePersonaleTotaleCostiPersonalePrecedente = $this->getElementFromBalance('CostiProduzionePersonaleTotaleCostiPersonale', 2, 'Andamento Del Mol');
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerciPrecedente = $this->getElementFromBalance('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 2, 'Andamento Del Mol');
        $CostiProduzioneOneriDiversiGestionePrecedente = $this->getElementFromBalance('CostiProduzioneOneriDiversiGestione', 2, 'Andamento Del Mol');

 /*        if (!$TotaleValoreProduzione || !$CostiProduzioneMateriePrimeSussidiarieConsumoMerci || !$CostiProduzioneGodimentoBeniTerzi || !$CostiProduzioneServizi || !$CostiProduzionePersonaleTotaleCostiPersonale || !$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci || !$CostiProduzioneOneriDiversiGestione || !$TotaleValoreProduzionePrecedente || !$CostiProduzioneMateriePrimeSussidiarieConsumoMerciPrecedente || !$CostiProduzioneGodimentoBeniTerziPrecedente || !$CostiProduzioneServiziPrecedente || !$CostiProduzionePersonaleTotaleCostiPersonalePrecedente || !$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerciPrecedente || !$CostiProduzioneOneriDiversiGestionePrecedente) {
            return false;
        } */

        $MOLcurr = $TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzioneServizi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione;
        $MOLprev = $TotaleValoreProduzionePrecedente - $CostiProduzioneMateriePrimeSussidiarieConsumoMerciPrecedente - $CostiProduzioneGodimentoBeniTerziPrecedente - $CostiProduzioneServiziPrecedente - $CostiProduzionePersonaleTotaleCostiPersonalePrecedente - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerciPrecedente - $CostiProduzioneOneriDiversiGestionePrecedente;

            $AndamentoMOL = - (1 - ($MOLcurr / $MOLprev)) * 100;
            $calculation = '- ('. 1 .' - ('.$MOLcurr.' / '. $MOLprev .')) * '. 100 .' = '.$AndamentoMOL;


        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAndamentoDelMol', $calculation);

        $data = [
            'MOLcurr' => $MOLcurr,
            'AndamentoMOL' => $AndamentoMOL,
        ];

        return $data;
    }

    public function getROI()
    {
        $DifferenzaValoreCostiProduzione = $this->getElementFromBalance('DifferenzaValoreCostiProduzione', 1, 'ROI');
        $TotaleAttivo = $this->getElementFromBalance('TotaleAttivo', 1, 'ROI');

        if (!$DifferenzaValoreCostiProduzione || !$TotaleAttivo)
            return false;

            $ROI = ($DifferenzaValoreCostiProduzione / $TotaleAttivo) * 100;
            $calculation = '('.$DifferenzaValoreCostiProduzione .' / '. $TotaleAttivo .') * '. 100 .' = '.$ROI;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetROI', $calculation);

        return $ROI;
    }

    public function getROS()
    {
        $DifferenzaValoreCostiProduzione = $this->getElementFromBalance('DifferenzaValoreCostiProduzione', 1, 'ROS');
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1, 'ROS');

        if (!$DifferenzaValoreCostiProduzione || !$ValoreProduzioneRicaviVenditePrestazioni)
            return false;

            $ROS = ($DifferenzaValoreCostiProduzione / $ValoreProduzioneRicaviVenditePrestazioni) * 100;
            $calculation = '('. $DifferenzaValoreCostiProduzione .' / '. $ValoreProduzioneRicaviVenditePrestazioni .') * '. 100 .' = '.$ROS;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetROS', $calculation);

        return $ROS;
    }

    public function getROE()
    {
        $TotalePatrimonioNetto = $this->getElementFromBalance('TotalePatrimonioNetto', 1, 'ROE');
        $UtilePerditaEsercizio = $this->getElementFromBalance('UtilePerditaEsercizio', 1, 'ROE');

        if(!$TotalePatrimonioNetto || !$UtilePerditaEsercizio)
            return false;

            $ROE = ($UtilePerditaEsercizio / $TotalePatrimonioNetto) * 100;
            $calculation = '('. $UtilePerditaEsercizio .' / '. $TotalePatrimonioNetto .') * '. 100 .' = '.$ROE;

            CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetROE', $calculation);

        return $ROE;
    }

    public function getEbitdaFatturato()
    {
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1, 'Ebitda Fatturato');
        $TotaleValoreProduzione = $this->getElementFromBalance('TotaleValoreProduzione', 1, 'Ebitda Fatturato');
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 1, 'Ebitda Fatturato');
        $CostiProduzioneServizi = $this->getElementFromBalance('CostiProduzioneServizi', 1, 'Ebitda Fatturato');
        $CostiProduzioneGodimentoBeniTerzi = $this->getElementFromBalance('CostiProduzioneGodimentoBeniTerzi', 1, 'Ebitda Fatturato');
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getElementFromBalance('CostiProduzionePersonaleTotaleCostiPersonale', 1, 'Ebitda Fatturato');
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 1, 'Ebitda Fatturato');
        $CostiProduzioneOneriDiversiGestione = $this->getElementFromBalance('CostiProduzioneOneriDiversiGestione', 1, 'Ebitda Fatturato');

        if (!$ValoreProduzioneRicaviVenditePrestazioni || !$TotaleValoreProduzione || !$CostiProduzioneMateriePrimeSussidiarieConsumoMerci || !$CostiProduzioneServizi || !$CostiProduzioneGodimentoBeniTerzi || !$CostiProduzionePersonaleTotaleCostiPersonale || !$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci || !$CostiProduzioneOneriDiversiGestione)
            return false;

            $EBITDA_FATTURATO = (($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / $ValoreProduzioneRicaviVenditePrestazioni) * 100;
            $calculation = '(('.$TotaleValoreProduzione.' - '.$CostiProduzioneMateriePrimeSussidiarieConsumoMerci.' - '.$CostiProduzioneServizi.' - '.$CostiProduzioneGodimentoBeniTerzi.' - '.$CostiProduzionePersonaleTotaleCostiPersonale.' - '.$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci .' - '. $CostiProduzioneOneriDiversiGestione.') / '. $ValoreProduzioneRicaviVenditePrestazioni .') * '. 100 .' = '.$EBITDA_FATTURATO;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetEbitdaFatturato', $calculation);

        return $EBITDA_FATTURATO;
    }

    public function getAndamentoDeiMezziPropri()
    {
        $TotalePatrimonioNettoCurr = $this->getElementFromBalance('TotalePatrimonioNetto', 1, 'Andamento Dei Mezzi Propri');
        $TotalePatrimonioNettoPrev = $this->getElementFromBalance('TotalePatrimonioNetto', 2, 'Andamento Dei Mezzi Propri');

        if (!$TotalePatrimonioNettoCurr || !$TotalePatrimonioNettoPrev)
            return false;

            $AndamentoDeiMezziPropri = (($TotalePatrimonioNettoCurr / $TotalePatrimonioNettoPrev) - 1) * 100;
            $calculation = '(('.$TotalePatrimonioNettoCurr.' / '. 1 .') - '. $TotalePatrimonioNettoPrev .') * '. 100 .' = '.$AndamentoDeiMezziPropri;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAndamentoDeiMezziPropri', $calculation);

        return $AndamentoDeiMezziPropri;
    }

    public function getMargineStrutturaPrimario()
    {
        $TotaleImmobilizzazioni = $this->getElementFromBalance('TotaleImmobilizzazioni', 1, 'Margine Struttura Primario');
        $TotalePatrimonioNetto = $this->getElementFromBalance('TotalePatrimonioNetto', 1, 'Margine Struttura Primario');

        if (!$TotaleImmobilizzazioni || !$TotalePatrimonioNetto)
            return false;

            $Margine_Struttura_Primario = (float)($TotalePatrimonioNetto / $TotaleImmobilizzazioni) * 100;
            $calculation = '('.(float)$TotalePatrimonioNetto.' / '. $TotaleImmobilizzazioni .') * '. 100 .' = '. $Margine_Struttura_Primario;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetMargineStrutturaPrimario', $calculation);

        return $Margine_Struttura_Primario;
    }

    public function getMargineStrutturaSecondario()
    {
        $TrattamentoFineRapportoLavoroSubordinato = $this->getElementFromBalance('TrattamentoFineRapportoLavoroSubordinato', 1, 'Margine Struttura Secondario');
        $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 1, 'Margine Struttura Secondario');
        $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 1, 'Margine Struttura Secondario');
        $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 1, 'Margine Struttura Secondario') != 0 ? $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 1, 'Margine Struttura Secondario')  : $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 2, 'Margine Struttura Secondario'));
        $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 1, 'Margine Struttura Secondario');
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 1, 'Margine Struttura Secondario');
        $DebitiAccontiEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiAccontiEsigibiliOltreEsercizioSuccessivo', 1, 'Margine Struttura Secondario');
        $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo', 1, 'Margine Struttura Secondario');
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo', 1, 'Margine Struttura Secondario');
        $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo', 1, 'Margine Struttura Secondario');
        $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo', 1, 'Margine Struttura Secondario');
        $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo', 1, 'Margine Struttura Secondario');
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo', 1, 'Margine Struttura Secondario');
        $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo = ($this->getElementFromBalance('DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo', 1, 'Margine Struttura Secondario') != 0 ? $this->getElementFromBalance('DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo', 1, 'Margine Struttura Secondario') :  $this->getElementFromBalance('DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo', 2, 'Margine Struttura Secondario'));
        $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo', 1, 'Margine Struttura Secondario') != 0 ? $this->getElementFromBalance('DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo', 1, 'Margine Struttura Secondario') : $this->getElementFromBalance('DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo', 2, 'Margine Struttura Secondario'));
        $TotalePatrimonioNetto = $this->getElementFromBalance('TotalePatrimonioNetto', 1, 'Margine Struttura Secondario');
        $TotaleImmobilizzazioni = $this->getElementFromBalance('TotaleImmobilizzazioni', 1, 'Margine Struttura Secondario');

        if (!$TrattamentoFineRapportoLavoroSubordinato || !$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo || !$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo || !$DebitiAccontiEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo || !$DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo || !$DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo || !$TotalePatrimonioNetto || !$TotaleImmobilizzazioni)
            return false;

            $Margine_Struttura_Secondario_Semplificato = (($TotalePatrimonioNetto + $TrattamentoFineRapportoLavoroSubordinato + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo + $DebitiAccontiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) / $TotaleImmobilizzazioni) * 100;
            $calculation = '(('.$TotalePatrimonioNetto.' + '.$TrattamentoFineRapportoLavoroSubordinato.' + '.$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiAccontiEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo.') / '. $TotaleImmobilizzazioni .') * '. 100 .' = '.$Margine_Struttura_Secondario_Semplificato;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetMargineStrutturaSecondario', $calculation);

        return $Margine_Struttura_Secondario_Semplificato;
    }

    public function getCurrentRatio()
    {
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1, 'Current Ratio');
        $AttivoRateiRisconti = $this->getElementFromBalance('AttivoRateiRisconti', 1, 'Current Ratio');
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getElementFromBalance('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 1, 'Current Ratio');
        $TotaleRimanenze = $this->getElementFromBalance('TotaleRimanenze', 1, 'Current Ratio');
        $TotaleCreditiEntroDodiciMesi = $this->getElementFromBalance('TotaleCreditiEntroDodiciMesi', 1, 'Current Ratio');
        $TotaleDebitiEntroDodiciMesi = $this->getElementFromBalance('TotaleDebitiEntroDodiciMesi', 1, 'Current Ratio');
        $PassivoRateiRisconti = $this->getElementFromBalance('PassivoRateiRisconti', 1, 'Current Ratio');

        if (!$TotaleDisponibilitaLiquide || !$AttivoRateiRisconti || !$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni || !$TotaleRimanenze || !$TotaleCreditiEntroDodiciMesi || !$TotaleDebitiEntroDodiciMesi || !$PassivoRateiRisconti)
            return false;

        $formula = ($TotaleDisponibilitaLiquide + $AttivoRateiRisconti + $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + $TotaleRimanenze + $TotaleCreditiEntroDodiciMesi) / ($TotaleDebitiEntroDodiciMesi + $PassivoRateiRisconti);
        $calculation = '('.$TotaleDisponibilitaLiquide.' + '.$AttivoRateiRisconti.' + '.$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni.' + '.$TotaleRimanenze.' + '.$TotaleCreditiEntroDodiciMesi.') / ('.$TotaleDebitiEntroDodiciMesi.' + '.$PassivoRateiRisconti.') = '.$formula;

        $RITORNO_LIQUIDO_ATTIVO = $formula * 100;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetCurrentRatio', $calculation);

        return $RITORNO_LIQUIDO_ATTIVO;
    }

    public function getAttivitaPassivitaABreve()
    {
        // TRENTACINQUE
        $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve');
        $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve');
        $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve');
        $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve') ? $this->getElementFromBalance('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve') : $this->getElementFromBalance('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 2, 'Attivita Passivita a Breve'));
        $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve') ? $this->getElementFromBalance('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve') : $this->getElementFromBalance('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 2, 'Attivita Passivita a Breve'));
        $TrentaCinque = $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo;


        // QUARANTANOVE
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve');
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve');
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve') ? $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve') : $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 2, 'Attivita Passivita a Breve'));
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve');
        $DebitiAccontiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiAccontiEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve');
        $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve') ? $this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve') : $this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 2, 'Attivita Passivita a Breve'));
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve');
        $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve');
        $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve');
        $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve');
        $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve') ? $this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve') : $this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 2, 'Attivita Passivita a Breve'));
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve') ? $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve') : $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 2, 'Attivita Passivita a Breve'));
        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve');
        $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve');

        $PassivoRateiRisconti = $this->getElementFromBalance('PassivoRateiRisconti', 1, 'Attivita Passivita a Breve');
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1, 'Attivita Passivita a Breve');
        $TotaleRimanenze = $this->getElementFromBalance('TotaleRimanenze', 1, 'Attivita Passivita a Breve');
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getElementFromBalance('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 1, 'Attivita Passivita a Breve');
        $AttivoRateiRisconti = $this->getElementFromBalance('AttivoRateiRisconti', 1, 'Attivita Passivita a Breve');

        $QuarantaNove = $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiAccontiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo;
        $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo', 1, 'Attivita Passivita a Breve');



        $Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore = $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiAccontiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $PassivoRateiRisconti;

            $Attivita_a_breve_Passivita_a_Breve_Ordinario = ((($TotaleDisponibilitaLiquide + $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo + $TotaleRimanenze + $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + $AttivoRateiRisconti) / ($Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore))) * 100;
            $calculation = '((('.$TotaleDisponibilitaLiquide.' + '.$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo.' + '.$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo.' + '.$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo.' + '.$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo.' + '.$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo.' + '.$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo.' + '.$TotaleRimanenze.' + '.$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni.' + '.$AttivoRateiRisconti.') / ('.$Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore.'))) * '. 100 .' = '.$Attivita_a_breve_Passivita_a_Breve_Ordinario;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAttivitaPassivitaABreve', $calculation);

        $data = [
            'Attivita_a_breve_Passività_a_Breve_Ordinario' => $Attivita_a_breve_Passivita_a_Breve_Ordinario,
            'QuarantaNove' => $QuarantaNove,
            'TrentaCinque' => $TrentaCinque
        ];

        return $data;
    }

    public function getAcidTest()
    {
        $DebitiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiEsigibiliEntroEsercizioSuccessivo', 1);
        $PassivoRateiRisconti = $this->getElementFromBalance('PassivoRateiRisconti', 1);
        $TotaleCrediti = $this->getElementFromBalance('TotaleCrediti', 1);
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getElementFromBalance('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 1);
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1);
        $AttivoRateiRisconti = $this->getElementFromBalance('AttivoRateiRisconti', 1);

            $AcidTest = (((float)$TotaleCrediti + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$TotaleDisponibilitaLiquide + (float)$AttivoRateiRisconti) / ((float)$DebitiEsigibiliEntroEsercizioSuccessivo + (float)$PassivoRateiRisconti));
            $calculation = '(('.(float)$TotaleCrediti.' + '.(float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni.' + '.(float)$TotaleDisponibilitaLiquide.' + '.(float)$AttivoRateiRisconti.') / ('.(float)$DebitiEsigibiliEntroEsercizioSuccessivo.' + '.(float)$PassivoRateiRisconti.')) = '.$AcidTest;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAcidTest', $calculation);

        return $AcidTest;
    }

    public function getAcidTestSemplificato()
    {
        $getAttivitaPassivitaABreve = $this->getAttivitaPassivitaABreve();
        $QuarantaNove = $getAttivitaPassivitaABreve['QuarantaNove'];
        $TrentaCinque = $getAttivitaPassivitaABreve['TrentaCinque'];

        $TotaleRimanenze = $this->getElementFromBalance('TotaleRimanenze', 1);
        $PassivoRateiRisconti = $this->getElementFromBalance('PassivoRateiRisconti', 1);
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1);
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getElementFromBalance('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 1);
        $AttivoRateiRisconti = $this->getElementFromBalance('AttivoRateiRisconti', 1);

            $ACID_TEST_Semplificato = ((((float)$TotaleDisponibilitaLiquide + (float)$TrentaCinque + (float)$TotaleRimanenze + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$AttivoRateiRisconti - (float)$TotaleRimanenze) / ((float)$QuarantaNove + (float)$PassivoRateiRisconti)));
            $calculation = '((('.(float)$TotaleDisponibilitaLiquide.' + '.(float)$TrentaCinque.' + '.(float)$TotaleRimanenze.' + '.(float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni.' + '.(float)$AttivoRateiRisconti.' - '.(float)$TotaleRimanenze.') / ('.(float)$QuarantaNove.' + '.(float)$PassivoRateiRisconti.'))) = '.$ACID_TEST_Semplificato;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAcidTestSemplificato', $calculation);

        return $ACID_TEST_Semplificato;
    }

    public function getAcidTestOrdinario()
    {
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 1) ? $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 1) : $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 2));
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiAccontiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiAccontiEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 1) ? $this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 1) : $this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 2));
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 1) ? $this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 1) : $this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 2));
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 1) ? $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 1) : $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 2));
        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo', 1);
        $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo', 1);
        $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 1);
        $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1);
        $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 1);
        $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 1);
        $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 1);

        $PassivoRateiRisconti = $this->getElementFromBalance('PassivoRateiRisconti', 1);
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1);
        $TotaleRimanenze = $this->getElementFromBalance('TotaleRimanenze', 1);
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getElementFromBalance('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 1);
        $AttivoRateiRisconti = $this->getElementFromBalance('AttivoRateiRisconti', 1);


        $ACID_TEST_Ordinario_divisore = $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiAccontiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $PassivoRateiRisconti;

            $ACID_TEST_Ordinario = ((($TotaleDisponibilitaLiquide + $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo + $TotaleRimanenze + $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + $AttivoRateiRisconti - $TotaleRimanenze) / ($ACID_TEST_Ordinario_divisore))) * 100;
            $calculation = '((('.$TotaleDisponibilitaLiquide.' + '.$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo.' + '.$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo.' + '.$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo.' + '.$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo.' + '.$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo.' + '.$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo.' + '.$TotaleRimanenze.' + '.$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni.' + '.$AttivoRateiRisconti.' - '.$TotaleRimanenze.') / ('.$ACID_TEST_Ordinario_divisore.'))) * '. 100 .' = '.$ACID_TEST_Ordinario;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAcidTestOrdinario', $calculation);

        return $ACID_TEST_Ordinario;
    }

    public function getAutonomiaFinanziaria()
    {
        $TotalePatrimonioNetto = $this->getTotalePatrimonioNetto('Autonomia Finanziaria');
        $TotaleDebiti = $this->getElementFromBalance('TotaleDebiti', 1, 'Autonomia Finanziaria');




            $AUTONOMIA_FINANZIARIA = (float)(($TotalePatrimonioNetto / ($TotalePatrimonioNetto + $TotaleDebiti))) * 100;
            $calculation = '(('.(float)$TotalePatrimonioNetto.' / ('.$TotalePatrimonioNetto.' + '.$TotaleDebiti.'))) * '. 100 .' = '.$AUTONOMIA_FINANZIARIA;


        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAutonomiaFinanziaria', $calculation);

        return $AUTONOMIA_FINANZIARIA;
    }

    public function getLivelloInvestimentiAziendali()
    {
        $TotalePatrimonioNetto = $this->getTotalePatrimonioNetto('Livello Investimenti Aziendali');
        $TotaleAttivo = $this->getElementFromBalance('TotaleAttivo', 1, 'Livello Investimenti Aziendali');


            $LIVELLO_INVESTIMENTI_AZIENDALI = (float)($TotalePatrimonioNetto / $TotaleAttivo) * 100;
            $calculation = '('.(float)$TotalePatrimonioNetto.' / '. $TotaleAttivo .') * '. 100 .' = '.$LIVELLO_INVESTIMENTI_AZIENDALI;


        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetLivelloInvestimentiAziendali', $calculation);

        return $LIVELLO_INVESTIMENTI_AZIENDALI;
    }

    public function getPfnEbitda()
    {
        $getAndamentoDelMol = $this->getAndamentoDelMol();
        $MOLcurr = str_replace(',', '.', (str_replace('.', '',$getAndamentoDelMol['MOLcurr'])));
        // $MOLcurr = number_format($getAndamentoDelMol['MOLcurr'], '.', ',');

        $ImmobilizzazioniFinanziarieCreditiTotaleCrediti = $this->getElementFromBalance('ImmobilizzazioniFinanziarieCreditiTotaleCrediti', 1);
        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 1);
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 1);
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 1);
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 1);
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 1);
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1);

        $debitiFinanziariCurr = $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;
        if ($MOLcurr == 0) {
            $PFN_EBITDA = (($debitiFinanziariCurr - $TotaleDisponibilitaLiquide - $ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / 1);
            $calculation = '(('.$debitiFinanziariCurr.' - '.$TotaleDisponibilitaLiquide.' - '.$ImmobilizzazioniFinanziarieCreditiTotaleCrediti.') / '. 1 .') = '.$PFN_EBITDA.' * '. 100;
        } else {
            $PFN_EBITDA = (($debitiFinanziariCurr - $TotaleDisponibilitaLiquide - $ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / $MOLcurr);
            $calculation = '(('.$debitiFinanziariCurr.' - '.$TotaleDisponibilitaLiquide.' - '.$ImmobilizzazioniFinanziarieCreditiTotaleCrediti.') / '. $MOLcurr .') = '.$PFN_EBITDA.' * '. 100;
        }

        $PFN_EBITDA = $PFN_EBITDA * 100;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetPfnEbitda', $calculation);

        return $PFN_EBITDA;
    }

    public function getPesoOneriFinanziari()
    {
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1);
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $this->getElementFromBalance('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 1);

        if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
            $ValoreProduzioneRicaviVenditePrestazioni = 1;
        }
        $Peso_Oneri_Finanziari = ($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari / $ValoreProduzioneRicaviVenditePrestazioni) * 100;
        $calculation = '('.$ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari.' / '.$ValoreProduzioneRicaviVenditePrestazioni.') * '. 100 .' = '.$Peso_Oneri_Finanziari;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetPesoOneriFinanziari', $calculation);

        return $Peso_Oneri_Finanziari;
    }

    public function getCoperturaLordaDegliOneriFinanziari()
    {
        $TotaleValoreProduzione = $this->getElementFromBalance('TotaleValoreProduzione', 1);
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 1);
        $CostiProduzioneGodimentoBeniTerzi = $this->getElementFromBalance('CostiProduzioneGodimentoBeniTerzi', 1);
        $CostiProduzioneServizi = $this->getElementFromBalance('CostiProduzioneServizi', 1);
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getElementFromBalance('CostiProduzionePersonaleTotaleCostiPersonale', 1);
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 1);
        $CostiProduzioneOneriDiversiGestione = $this->getElementFromBalance('CostiProduzioneOneriDiversiGestione', 1);
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari =  $this->getElementFromBalance('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 1);

        if ($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari == 0) {
            $Copertura_Lorda_degli_Oneri_Finanziari = (($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / 1);
            $calculation = '(('.$TotaleValoreProduzione.' - '.$CostiProduzioneMateriePrimeSussidiarieConsumoMerci.' - '.$CostiProduzioneServizi.' - '.$CostiProduzioneGodimentoBeniTerzi.' - '.$CostiProduzionePersonaleTotaleCostiPersonale.' - '.$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci.' - '.$CostiProduzioneOneriDiversiGestione.') / '. 1 .') = '.(float)$Copertura_Lorda_degli_Oneri_Finanziari.' * '. 100;
        } else {
            $Copertura_Lorda_degli_Oneri_Finanziari = (($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari);
            $calculation = '(('.$TotaleValoreProduzione.' - '.$CostiProduzioneMateriePrimeSussidiarieConsumoMerci.' - '.$CostiProduzioneServizi.' - '.$CostiProduzioneGodimentoBeniTerzi.' - '.$CostiProduzionePersonaleTotaleCostiPersonale.' - '.$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci.' - '.$CostiProduzioneOneriDiversiGestione.') / '. $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari .') = '.(float)$Copertura_Lorda_degli_Oneri_Finanziari.' * '. 100;
        }

        $Copertura_Lorda_degli_Oneri_Finanziari = (float)$Copertura_Lorda_degli_Oneri_Finanziari * 100;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetCoperturaLordaDegliOneriFinanziari', $calculation);

        return $Copertura_Lorda_degli_Oneri_Finanziari;
    }

    public function getEbitOf()
    {
        $TotaleValoreProduzione = $this->getElementFromBalance('TotaleValoreProduzione', 1);
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 1);
        $CostiProduzioneGodimentoBeniTerzi = $this->getElementFromBalance('CostiProduzioneGodimentoBeniTerzi', 1);
        $CostiProduzioneServizi = $this->getElementFromBalance('CostiProduzioneServizi', 1);
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getElementFromBalance('CostiProduzionePersonaleTotaleCostiPersonale', 1);
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 1);
        $CostiProduzioneOneriDiversiGestione = $this->getElementFromBalance('CostiProduzioneOneriDiversiGestione', 1);
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari =  $this->getElementFromBalance('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 1);
        $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni = $this->getElementFromBalance('CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 1);
        $CostiProduzioneAccantonamentiRischi = $this->getElementFromBalance('CostiProduzioneAccantonamentiRischi', 1);
        $CostiProduzioneAltriAccantonamenti =  $this->getElementFromBalance('CostiProduzioneAltriAccantonamenti', 1);


        if ($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari == 0) {
            $EBIT_OF = (($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione - $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - $CostiProduzioneAccantonamentiRischi - $CostiProduzioneAltriAccantonamenti) / 1);
            $calculation = '(('.$TotaleValoreProduzione.' - '.$CostiProduzioneMateriePrimeSussidiarieConsumoMerci.' - '.$CostiProduzioneServizi.' - '.$CostiProduzioneGodimentoBeniTerzi.' - '.$CostiProduzionePersonaleTotaleCostiPersonale.' - '.$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci.' - '.$CostiProduzioneOneriDiversiGestione.' - '.$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni.' - '.$CostiProduzioneAccantonamentiRischi.' - '.$CostiProduzioneAltriAccantonamenti.') / '. 1 .') '.(float)$EBIT_OF.' * '. 100 .' = '.(float)$EBIT_OF*100;
        } else {
            $EBIT_OF = (($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione - $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - $CostiProduzioneAccantonamentiRischi - $CostiProduzioneAltriAccantonamenti) / $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari);
            $calculation = '(('.$TotaleValoreProduzione.' - '.$CostiProduzioneMateriePrimeSussidiarieConsumoMerci.' - '.$CostiProduzioneServizi.' - '.$CostiProduzioneGodimentoBeniTerzi.' - '.$CostiProduzionePersonaleTotaleCostiPersonale.' - '.$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci.' - '.$CostiProduzioneOneriDiversiGestione.' - '.$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni.' - '.$CostiProduzioneAccantonamentiRischi.' - '.$CostiProduzioneAltriAccantonamenti.') / '.$ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari.')  '.(float)$EBIT_OF.' * '. 100 .' = '.(float)$EBIT_OF*100;
        }

        $EBIT_OF = (float)$EBIT_OF * 100;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetEbitOf', $calculation);

        return $EBIT_OF;
    }

    public function getCostoDelPersonale()
    {
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getElementFromBalance('CostiProduzionePersonaleTotaleCostiPersonale', 1);
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1);


        if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
            $Costo_del_personale = (float)($CostiProduzionePersonaleTotaleCostiPersonale / 1) * 100;
            $calculation = '('.(float)$CostiProduzionePersonaleTotaleCostiPersonale.' / '. 1 .') * '. 100 .' = '.$Costo_del_personale;
        } else {
            $Costo_del_personale = (float)($CostiProduzionePersonaleTotaleCostiPersonale / $ValoreProduzioneRicaviVenditePrestazioni) * 100;
            $calculation = '('.(float)$CostiProduzionePersonaleTotaleCostiPersonale.' / '. $ValoreProduzioneRicaviVenditePrestazioni .') * '. 100 .' = '.$Costo_del_personale;
        }

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetCostoDelPersonale', $calculation);

        return $Costo_del_personale;
    }

    public function getCfAttivo()
    {
        $TotaleAttivo = $this->getElementFromBalance('TotaleAttivo', 1);
        $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni = $this->getElementFromBalance('CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 1);
        $CostiProduzioneAccantonamentiRischi = $this->getElementFromBalance('CostiProduzioneAccantonamentiRischi', 1);
        $CostiProduzioneAltriAccantonamenti =  $this->getElementFromBalance('CostiProduzioneAltriAccantonamenti', 1);
        $UtilePerditaEsercizio = $this->getElementFromBalance('UtilePerditaEsercizio', 1);
        $imposteRedditoEsercizioImposteAnticipate = $this->getElementFromBalance('ImposteRedditoEsercizioCorrentiDifferiteAnticipateImposteDifferiteAnticipate', 1);

        if ($TotaleAttivo == 0) {
            $CF_ATTIVO = (($UtilePerditaEsercizio + $CostiProduzioneAccantonamentiRischi + $CostiProduzioneAltriAccantonamenti + $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - $imposteRedditoEsercizioImposteAnticipate) / 0.1) * 100;
            $calculation = '(('.$UtilePerditaEsercizio.' + '.$CostiProduzioneAccantonamentiRischi.' + '.$CostiProduzioneAltriAccantonamenti.' + '.$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni.' - '.$imposteRedditoEsercizioImposteAnticipate.') / '. 0.1 .') * '. 100;
        } else {
            $CF_ATTIVO = (($UtilePerditaEsercizio + $CostiProduzioneAccantonamentiRischi + $CostiProduzioneAltriAccantonamenti + $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - $imposteRedditoEsercizioImposteAnticipate) / $TotaleAttivo) * 100;
            $calculation = '(('.$UtilePerditaEsercizio.' + '.$CostiProduzioneAccantonamentiRischi.' + '.$CostiProduzioneAltriAccantonamenti.' + '.$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni.' - '.$imposteRedditoEsercizioImposteAnticipate.') / '. $TotaleAttivo .') * '. 100;
        }

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
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 1 ,'Indice di Indebitamento');
        $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 1, 'Indice di Indebitamento');
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 1, 'Indice di Indebitamento');
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 1, 'Indice di Indebitamento');
        $TotalePatrimonioNetto = $this->getTotalePatrimonioNetto('Indice di Indebitamento');
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1, 'Indice di Indebitamento');
        $ImmobilizzazioniFinanziarieCreditiTotaleCrediti = $this->getElementFromBalance('ImmobilizzazioniFinanziarieCreditiTotaleCrediti', 1, 'Indice di Indebitamento');


            $MOLannoCorrente = $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;
            $Indice_di_Indebitamento = (($MOLannoCorrente - $TotaleDisponibilitaLiquide - $ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / $TotalePatrimonioNetto) * 100;
            $calculation = '(('.$MOLannoCorrente.' - '.$TotaleDisponibilitaLiquide.' - '.$ImmobilizzazioniFinanziarieCreditiTotaleCrediti.') / '. $TotalePatrimonioNetto .') * '. 100 .' = '.$Indice_di_Indebitamento;
            $dataAnalisis['Indice_di_Indebitamento'] = $Indice_di_Indebitamento;


        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetIndiceDiIndebitamento', $calculation);

        return $Indice_di_Indebitamento;
    }

    public function getSaldoDebitiVsFisco()
    {
        $DebitiDebitiTributariTotaleDebitiTributariCorrente = $this->getElementFromBalance('DebitiDebitiTributariTotaleDebitiTributari', 1);
        $FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente = $this->getElementFromBalance('FondiRischiOneriTrattamentoQuiescenzaObblighiSimili', 1);
        $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipatePrecedente = $this->getElementFromBalance('ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate', 2);
        $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate = $this->getElementFromBalance('ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate', 1);

        $DifferenzaImposteReddito = ($ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate + $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipatePrecedente) / 2;
        if ($DifferenzaImposteReddito == 0) {
            $SaldoDebitiVSFisco = ($FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente + $DebitiDebitiTributariTotaleDebitiTributariCorrente) / 1;
            $calculation = '('.$FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente.' + '.$DebitiDebitiTributariTotaleDebitiTributariCorrente.') / '. 1 .' = '.$SaldoDebitiVSFisco.' * '. 100 .' = '.$SaldoDebitiVSFisco*100;
        } else {
            $SaldoDebitiVSFisco = ($FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente + $DebitiDebitiTributariTotaleDebitiTributariCorrente) / $DifferenzaImposteReddito;
            $calculation = '('.$FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente.' + '.$DebitiDebitiTributariTotaleDebitiTributariCorrente.') / '. $DifferenzaImposteReddito .' = '.$SaldoDebitiVSFisco.' * '. 100 .' = '.$SaldoDebitiVSFisco*100;
        }

        $SaldoDebitiVSFisco = $SaldoDebitiVSFisco * 100;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetSaldoDebitiVsFisco', $calculation);

        return $SaldoDebitiVSFisco;
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

}
