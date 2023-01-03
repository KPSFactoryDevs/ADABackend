<?php

namespace App\Helpers\Bilanci;

use stdClass;
use App\Models\CustomLog;

class BilanciCalculationsHelperAdvanced
{

    public $_currentInstance = false;


    public function setCurrentInstance($instanceDocument)
    {
        $this->_currentInstance = $instanceDocument;
    }

    public function getElementFromBalance($elementName, $period)
    {
        $value = false;
        $elements = $this->_currentInstance->getElements();
        $elements = $elements->getElements();

        if(isset($elements[$elementName])) {
            if($period == 1) {
                $value = array_first($elements[$elementName])['value'];
            } else if($period == 2) {
                $value = array_last($elements[$elementName])['value'];
            }
        } else {
            $value = 0;
        }

        return $value;
    }


    public function getTotalePatrimonioNetto()
    {
        $TotalePatrimonioNetto = $this->getElementFromBalance('TotalePatrimonioNetto', 1);

        return $TotalePatrimonioNetto;
    }

    public function getPatrimonioNettoNegativo()
    {
        $TotalePatrimonioNetto = $this->getTotalePatrimonioNetto();
        $TotaleCreditiVersoSociVersamentiAncoraDovuti = $this->getElementFromBalance('TotaleCreditiVersoSociVersamentiAncoraDovuti', 1);
        $TotalePatrimonioNetto = str_replace(',', '.', str_replace('.', '', $TotalePatrimonioNetto));

        $PN_NEGATIVO = $TotalePatrimonioNetto - $TotaleCreditiVersoSociVersamentiAncoraDovuti;
        $calculationPNnegativo = $TotalePatrimonioNetto.' - '.$TotaleCreditiVersoSociVersamentiAncoraDovuti.' = '.$PN_NEGATIVO;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetPatrimonioNettoNegativo', $calculationPNnegativo);

        if($PN_NEGATIVO > 0) {
            $PN_NEGATIVO = "No";
        } else {
            $PN_NEGATIVO = "Si";
        }

        return  $PN_NEGATIVO;
    }

    public function getOfRicavi()
    {
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari =  $this->getElementFromBalance('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 1);
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1);

        $OF_RICAVI = ($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari / $ValoreProduzioneRicaviVenditePrestazioni) * 100;
        $calculation = $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari.' / '.$ValoreProduzioneRicaviVenditePrestazioni.' * '. 100 .' = '.$OF_RICAVI;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetOF_RICAVI', $calculation);

        return $OF_RICAVI;
    }

    public function getTotaleDebiti()
    {
        $TotaleDebiti = $this->getElementFromBalance('TotaleDebiti', 1);

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetTotaleDebiti', $TotaleDebiti);

        return $TotaleDebiti;
    }

    public function getAdeguatezzaPatrimoniale()
    {
        $PN_NEGATIVO = $this->getPatrimonioNettoNegativo();
        $TotaleDebiti = $this->getTotaleDebiti();
        $PassivoRateiRisconti = $this->getElementFromBalance('PassivoRateiRisconti', 1);

        $ADEGUATEZZA_PATRIMONIALE = ($PN_NEGATIVO / ($TotaleDebiti + $PassivoRateiRisconti)) * 100;
        $calculation = $PN_NEGATIVO.' / ('.$TotaleDebiti.' + '.$PassivoRateiRisconti.') * '. 100;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAdeguatezzaPatrimoniale', $calculation);

        return $ADEGUATEZZA_PATRIMONIALE;
    }

    public function getTotaleCreditiEntroDodiciMesi()
    {
        $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo', 1);
        $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 1);
        $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1);
        $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 1);
        $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 1);
        $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 1);
        $CreditiImposteAnticipateTotaleImposteAnticipate = $this->getElementFromBalance('CreditiImposteAnticipateTotaleImposteAnticipate', 1);

        $TotaleDebitiEntroDodiciMesi = (float)$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + (float)$CreditiImposteAnticipateTotaleImposteAnticipate + (float)$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo;
        $calculation = (float)$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo.' + '.(float)$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo.' + '.(float)$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo.' + '.(float)$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo.' + '.(float)$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo.' + '.(float)$CreditiImposteAnticipateTotaleImposteAnticipate.' + '.(float)$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo.' = '.$TotaleDebitiEntroDodiciMesi;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetTotaleCreditiEntroDodiciMesi', $calculation);

        return $TotaleDebitiEntroDodiciMesi;
    }

    public function getTotaleDebitiEntroDodiciMesi()
    {
        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiAccontiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiAccontiEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 1);
        $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo', 1);

        $TotaleDebitiEntroDodiciMesi = (float)$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAccontiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo;

        $calculation = (float)$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiAccontiEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo.' = '.$TotaleDebitiEntroDodiciMesi;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetTotaleDebitiEntroDodiciMesi', $calculation);

        return $TotaleDebitiEntroDodiciMesi;
    }

    public function getLiquidita()
    {
        $CostiProduzioneAccantonamentiRischi = $this->getElementFromBalance('CostiProduzioneAccantonamentiRischi', 1);
        $TotaleAttivo = $this->getElementFromBalance('TotaleAttivo', 1);
        $UtilePerditaEsercizio = $this->getElementFromBalance('UtilePerditaEsercizio', 1);
        $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni = $this->getElementFromBalance('CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 1);
        $CostiProduzioneAltriAccantonamenti = $this->getElementFromBalance('CostiProduzioneAltriAccantonamenti', 1);

            $LIQUIDITA = (($UtilePerditaEsercizio + $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni + (float)$CostiProduzioneAccantonamentiRischi + (float)$CostiProduzioneAltriAccantonamenti) / (float)$TotaleAttivo) * 100;
            $calculationLIQUIDITA = '(('.$UtilePerditaEsercizio.' + '.$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni.' + '.(float)$CostiProduzioneAccantonamentiRischi.' + '.(float)$CostiProduzioneAltriAccantonamenti.') / '.(float)$TotaleAttivo.') * '. 100;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetLiquidità', $calculationLIQUIDITA);

        return $LIQUIDITA;
    }

    public function getIndebitamentoPrevidenzialeTributario()
    {
        $DebitiDebitiTributariTotaleDebitiTributari = $this->getElementFromBalance('DebitiDebitiTributariTotaleDebitiTributari', 1);
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale = $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale', 1);
        $TotaleAttivo = $this->getElementFromBalance('TotaleAttivo', 1);

            $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO = (($DebitiDebitiTributariTotaleDebitiTributari + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) / $TotaleAttivo) * 100;
            $calculation = '(('.$DebitiDebitiTributariTotaleDebitiTributari.' + '.$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale.') / '. $TotaleAttivo .') * '. 100 .' = '.$INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetIndebitamentoPrevidenzialeTributario', $calculation);

        return $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO;
    }

    public function getAndamentoDelFatturato()
    {
        $ValoreProduzioneRicaviVenditePrestazioniCurr = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1);
        $ValoreProduzioneRicaviVenditePrestazioniPrev = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 2);

            $AndamentoDelFatturato = (- (1 - (($ValoreProduzioneRicaviVenditePrestazioniCurr) / ($ValoreProduzioneRicaviVenditePrestazioniPrev)))) * 100;
            $calculation = '(- ('. 1 .' - (('.$ValoreProduzioneRicaviVenditePrestazioniCurr.') / ('.$ValoreProduzioneRicaviVenditePrestazioniPrev.')))) * '. 100 .' = '.$AndamentoDelFatturato;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAndamentoDelFatturato', $calculation);

        return $AndamentoDelFatturato;
    }

    public function getAndamentoDelMol()
    {
        // Curr
        $TotaleValoreProduzione = $this->getElementFromBalance('TotaleValoreProduzione', 1);
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 1);
        $CostiProduzioneGodimentoBeniTerzi = $this->getElementFromBalance('CostiProduzioneGodimentoBeniTerzi', 1);
        $CostiProduzioneServizi = $this->getElementFromBalance('CostiProduzioneServizi', 1);
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getElementFromBalance('CostiProduzionePersonaleTotaleCostiPersonale', 1);
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 1);
        $CostiProduzioneOneriDiversiGestione = $this->getElementFromBalance('CostiProduzioneOneriDiversiGestione', 1);

        // Prev
        $TotaleValoreProduzionePrecedente = $this->getElementFromBalance('TotaleValoreProduzione', 2);
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerciPrecedente = $this->getElementFromBalance('CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 2);
        $CostiProduzioneGodimentoBeniTerziPrecedente = $this->getElementFromBalance('CostiProduzioneGodimentoBeniTerzi', 2);
        $CostiProduzioneServiziPrecedente = $this->getElementFromBalance('CostiProduzioneServizi', 2);
        $CostiProduzionePersonaleTotaleCostiPersonalePrecedente = $this->getElementFromBalance('CostiProduzionePersonaleTotaleCostiPersonale', 2);
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerciPrecedente = $this->getElementFromBalance('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 2);
        $CostiProduzioneOneriDiversiGestionePrecedente = $this->getElementFromBalance('CostiProduzioneOneriDiversiGestione', 2);

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
        $DifferenzaValoreCostiProduzione = $this->getElementFromBalance('DifferenzaValoreCostiProduzione', 1);
        $TotaleAttivo = $this->getElementFromBalance('TotaleAttivo', 1);

            $ROI = ($DifferenzaValoreCostiProduzione / $TotaleAttivo) * 100;
            $calculation = '('.$DifferenzaValoreCostiProduzione .' / '. $TotaleAttivo .') * '. 100 .' = '.$ROI;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetROI', $calculation);

        return $ROI;
    }

    public function getROS()
    {
        $DifferenzaValoreCostiProduzione = $this->getElementFromBalance('DifferenzaValoreCostiProduzione', 1);
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1);

            $ROS = ($DifferenzaValoreCostiProduzione / $ValoreProduzioneRicaviVenditePrestazioni) * 100;
            $calculation = '('. $DifferenzaValoreCostiProduzione .' / '. $ValoreProduzioneRicaviVenditePrestazioni .') * '. 100 .' = '.$ROS;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetROS', $calculation);

        return $ROS;
    }

    public function getROE()
    {
        $TotalePatrimonioNetto = $this->getElementFromBalance('TotalePatrimonioNetto', 1);
        $UtilePerditaEsercizio = $this->getElementFromBalance('UtilePerditaEsercizio', 1);

            $ROE = ($UtilePerditaEsercizio / $TotalePatrimonioNetto) * 100;
            $calculation = '('. $UtilePerditaEsercizio .' / '. $TotalePatrimonioNetto .') * '. 100 .' = '.$ROE;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetROE', $calculation);

        return $ROE;
    }

    public function getEbitdaFatturato()
    {
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1);
        $TotaleValoreProduzione = $this->getElementFromBalance('TotaleValoreProduzione', 1);
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 1);
        $CostiProduzioneServizi = $this->getElementFromBalance('CostiProduzioneServizi', 1);
        $CostiProduzioneGodimentoBeniTerzi = $this->getElementFromBalance('CostiProduzioneGodimentoBeniTerzi', 1);
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getElementFromBalance('CostiProduzionePersonaleTotaleCostiPersonale', 1);
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci = $this->getElementFromBalance('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 1);
        $CostiProduzioneOneriDiversiGestione = $this->getElementFromBalance('CostiProduzioneOneriDiversiGestione', 1);

            $EBITDA_FATTURATO = (($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / $ValoreProduzioneRicaviVenditePrestazioni) * 100;
            $calculation = '(('.$TotaleValoreProduzione.' - '.$CostiProduzioneMateriePrimeSussidiarieConsumoMerci.' - '.$CostiProduzioneServizi.' - '.$CostiProduzioneGodimentoBeniTerzi.' - '.$CostiProduzionePersonaleTotaleCostiPersonale.' - '.$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci .' - '. $CostiProduzioneOneriDiversiGestione.') / '. $ValoreProduzioneRicaviVenditePrestazioni .') * '. 100 .' = '.$EBITDA_FATTURATO;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetEbitdaFatturato', $calculation);

        return $EBITDA_FATTURATO;
    }

    public function getAndamentoDeiMezziPropri()
    {
        $TotalePatrimonioNettoCurr = $this->getElementFromBalance('TotalePatrimonioNetto', 1);
        $TotalePatrimonioNettoPrev = $this->getElementFromBalance('TotalePatrimonioNetto', 2);

            $AndamentoDeiMezziPropri = (($TotalePatrimonioNettoCurr / $TotalePatrimonioNettoPrev) - 1) * 100;
            $calculation = '(('.$TotalePatrimonioNettoCurr.' / '. 1 .') - '. $TotalePatrimonioNettoPrev .') * '. 100 .' = '.$AndamentoDeiMezziPropri;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAndamentoDeiMezziPropri', $calculation);

        return $AndamentoDeiMezziPropri;
    }

    public function getMargineStrutturaPrimario()
    {
        $TotaleImmobilizzazioni = $this->getElementFromBalance('TotaleImmobilizzazioni', 1);
        $TotalePatrimonioNetto = $this->getElementFromBalance('TotalePatrimonioNetto', 1);

            $Margine_Struttura_Primario = (float)($TotalePatrimonioNetto / $TotaleImmobilizzazioni) * 100;
            $calculation = '('.(float)$TotalePatrimonioNetto.' / '. $TotaleImmobilizzazioni .') * '. 100 .' = '. $Margine_Struttura_Primario;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetMargineStrutturaPrimario', $calculation);

        return $Margine_Struttura_Primario;
    }

    public function getMargineStrutturaSecondario()
    {
        $TrattamentoFineRapportoLavoroSubordinato = $this->getDataFromBilancio('TrattamentoFineRapportoLavoroSubordinato');
        $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo = $this->getDataFromBilancio('DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo');
        $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo = $this->getDataFromBilancio('DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo');
        $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo = ($this->getDataFromBilancio('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo') != 0 ? $this->getDataFromBilancio('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo')  : $this->getDataFromBilancioPrev('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo'));
        $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo');
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo');
        $DebitiAccontiEsigibiliOltreEsercizioSuccessivo = $this->getDataFromBilancio('DebitiAccontiEsigibiliOltreEsercizioSuccessivo');
        $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo');
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo');
        $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo');
        $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo');
        $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo');
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo');
        $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo = ($this->getDataFromBilancio('DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo') != 0 ? $this->getDataFromBilancio('DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo') :  $this->getDataFromBilancioPrev('DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo'));
        $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo = ($this->getDataFromBilancio('DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo') != 0 ? $this->getDataFromBilancio('DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo') : $this->getDataFromBilancioPrev('DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo'));
        $TotalePatrimonioNetto = $this->getDataFromBilancio('TotalePatrimonioNetto');
        $TotaleImmobilizzazioni = $this->getDataFromBilancio('TotaleImmobilizzazioni');

        if ($TotaleImmobilizzazioni == 0) {
            $Margine_Struttura_Secondario_Semplificato = number_format((($TotalePatrimonioNetto + $TrattamentoFineRapportoLavoroSubordinato + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo + $DebitiAccontiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) / 1) * 100, 2, ',', '.');
            $calculation = '(('.$TotalePatrimonioNetto.' + '.$TrattamentoFineRapportoLavoroSubordinato.' + '.$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiAccontiEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo.') / '. 1 .') * '. 100 .' = '.$Margine_Struttura_Secondario_Semplificato;
        } else {
            $Margine_Struttura_Secondario_Semplificato = number_format((($TotalePatrimonioNetto + $TrattamentoFineRapportoLavoroSubordinato + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo + $DebitiAccontiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) / $TotaleImmobilizzazioni) * 100, 2, ',', '.');
            $calculation = '(('.$TotalePatrimonioNetto.' + '.$TrattamentoFineRapportoLavoroSubordinato.' + '.$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiAccontiEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo.') / '. $TotaleImmobilizzazioni .') * '. 100 .' = '.$Margine_Struttura_Secondario_Semplificato;
        }

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetMargineStrutturaSecondario', $calculation);

        return $Margine_Struttura_Secondario_Semplificato;
    }

    public function getCurrentRatio()
    {
        $TotaleDisponibilitaLiquide = $this->getDataFromBilancio('TotaleDisponibilitaLiquide');
        $AttivoRateiRisconti = $this->getDataFromBilancio('AttivoRateiRisconti');
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getDataFromBilancio('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni');
        $TotaleRimanenze = $this->getDataFromBilancio('TotaleRimanenze');
        $TotaleCreditiEntroDodiciMesi = $this->getDataFromBilancio('TotaleCreditiEntroDodiciMesi');
        $TotaleDebitiEntroDodiciMesi = $this->getDataFromBilancio('TotaleDebitiEntroDodiciMesi');
        $PassivoRateiRisconti = $this->getDataFromBilancio('PassivoRateiRisconti');

        $denominatoreRitornoLiquidoAttivo = 0;
        if (($TotaleDebitiEntroDodiciMesi + $PassivoRateiRisconti) == 0) {
            $denominatoreRitornoLiquidoAttivo = 1;
        }

        $formula = ($TotaleDisponibilitaLiquide + $AttivoRateiRisconti + $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + $TotaleRimanenze + $TotaleCreditiEntroDodiciMesi) / ($TotaleDebitiEntroDodiciMesi + $PassivoRateiRisconti + $denominatoreRitornoLiquidoAttivo);
        $calculation = '('.$TotaleDisponibilitaLiquide.' + '.$AttivoRateiRisconti.' + '.$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni.' + '.$TotaleRimanenze.' + '.$TotaleCreditiEntroDodiciMesi.') / ('.$TotaleDebitiEntroDodiciMesi.' + '.$PassivoRateiRisconti.' + '.$denominatoreRitornoLiquidoAttivo.') = '.$formula;

        $RITORNO_LIQUIDO_ATTIVO = number_format($formula * 100, 2, ',', '.');

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetCurrentRatio', $calculation);

        return $RITORNO_LIQUIDO_ATTIVO;
    }

    public function getAttivitaPassivitaABreve()
    {
        // TRENTACINQUE
        $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo');
        $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo');
        $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo');
        $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = ($this->getDataFromBilancio('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo') ? $this->getDataFromBilancio('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo') : $this->getDataFromBilancioPrev('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo'));
        $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = ($this->getDataFromBilancio('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo') ? $this->getDataFromBilancio('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo') : $this->getDataFromBilancioPrev('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo'));
        $TrentaCinque = $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo;


        // QUARANTANOVE
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = ($this->getDataFromBilancio('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo') ? $this->getDataFromBilancio('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo') : $this->getDataFromBilancioPrev('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo'));
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo');
        $DebitiAccontiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiAccontiEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo = ($this->getDataFromBilancio('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo') ? $this->getDataFromBilancio('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo') : $this->getDataFromBilancioPrev('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo'));
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo = ($this->getDataFromBilancio('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo') ? $this->getDataFromBilancio('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo') : $this->getDataFromBilancioPrev('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo'));
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo = ($this->getDataFromBilancio('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo') ? $this->getDataFromBilancio('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo') : $this->getDataFromBilancioPrev('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo'));
        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo');
        $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo');

        $PassivoRateiRisconti = $this->getDataFromBilancio('PassivoRateiRisconti');
        $TotaleDisponibilitaLiquide = $this->getDataFromBilancio('TotaleDisponibilitaLiquide');
        $TotaleRimanenze = $this->getDataFromBilancio('TotaleRimanenze');
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getDataFromBilancio('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni');
        $AttivoRateiRisconti = $this->getDataFromBilancio('AttivoRateiRisconti');

        $QuarantaNove = $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiAccontiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo;
        $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo');

        $Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore = $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiAccontiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $PassivoRateiRisconti;

        if ($Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore > 0) {
            $Attivita_a_breve_Passivita_a_Breve_Ordinario = number_format(((($TotaleDisponibilitaLiquide + $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo + $TotaleRimanenze + $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + $AttivoRateiRisconti) / ($Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore))) * 100, 2, ',', '.');
            $calculation = '((('.$TotaleDisponibilitaLiquide.' + '.$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo.' + '.$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo.' + '.$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo.' + '.$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo.' + '.$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo.' + '.$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo.' + '.$TotaleRimanenze.' + '.$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni.' + '.$AttivoRateiRisconti.') / ('.$Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore.'))) * '. 100 .' = '.$Attivita_a_breve_Passivita_a_Breve_Ordinario;
        } else {
            $Attivita_a_breve_Passivita_a_Breve_Ordinario = "Non Calcolabile";
            $calculation = $Attivita_a_breve_Passivita_a_Breve_Ordinario;
        }

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
        $DebitiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiEsigibiliEntroEsercizioSuccessivo');
        $PassivoRateiRisconti = $this->getDataFromBilancio('PassivoRateiRisconti');
        $TotaleCrediti = $this->getDataFromBilancio('TotaleCrediti');
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getDataFromBilancio('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni');
        $TotaleDisponibilitaLiquide = $this->getDataFromBilancio('TotaleDisponibilitaLiquide');
        $AttivoRateiRisconti = $this->getDataFromBilancio('AttivoRateiRisconti');

        if ($DebitiEsigibiliEntroEsercizioSuccessivo > 0 || $PassivoRateiRisconti > 0) {
            $AcidTest = number_format((((float)$TotaleCrediti + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$TotaleDisponibilitaLiquide + (float)$AttivoRateiRisconti) / ((float)$DebitiEsigibiliEntroEsercizioSuccessivo + (float)$PassivoRateiRisconti)), 2, ',', '.');
            $calculation = '(('.(float)$TotaleCrediti.' + '.(float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni.' + '.(float)$TotaleDisponibilitaLiquide.' + '.(float)$AttivoRateiRisconti.') / ('.(float)$DebitiEsigibiliEntroEsercizioSuccessivo.' + '.(float)$PassivoRateiRisconti.')) = '.$AcidTest;
        } else {
            $AcidTest = 'Non Calcolabile';
            $calculation = $AcidTest;
        }

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAcidTest', $calculation);

        return $AcidTest;
    }

    public function getAcidTestSemplificato()
    {
        $getAttivitaPassivitaABreve = $this->getAttivitaPassivitaABreve();
        $QuarantaNove = $getAttivitaPassivitaABreve['QuarantaNove'];
        $TrentaCinque = $getAttivitaPassivitaABreve['TrentaCinque'];

        $TotaleRimanenze = $this->getDataFromBilancio('TotaleRimanenze');
        $PassivoRateiRisconti = $this->getDataFromBilancio('PassivoRateiRisconti');
        $TotaleDisponibilitaLiquide = $this->getDataFromBilancio('TotaleDisponibilitaLiquide');
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getDataFromBilancio('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni');
        $AttivoRateiRisconti = $this->getDataFromBilancio('AttivoRateiRisconti');

        if ($QuarantaNove > 0 || $PassivoRateiRisconti > 0) {
            $ACID_TEST_Semplificato = number_format(((((float)$TotaleDisponibilitaLiquide + (float)$TrentaCinque + (float)$TotaleRimanenze + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$AttivoRateiRisconti - (float)$TotaleRimanenze) / ((float)$QuarantaNove + (float)$PassivoRateiRisconti))), 2, ',', '.');
            $calculation = '((('.(float)$TotaleDisponibilitaLiquide.' + '.(float)$TrentaCinque.' + '.(float)$TotaleRimanenze.' + '.(float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni.' + '.(float)$AttivoRateiRisconti.' - '.(float)$TotaleRimanenze.') / ('.(float)$QuarantaNove.' + '.(float)$PassivoRateiRisconti.'))) = '.$ACID_TEST_Semplificato;
        } else {
            $ACID_TEST_Semplificato = 'Non Calcolabile';
            $calculation = $ACID_TEST_Semplificato;
        }

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAcidTestSemplificato', $calculation);

        return $ACID_TEST_Semplificato;
    }

    public function getAcidTestOrdinario()
    {
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = ($this->getDataFromBilancio('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo') ? $this->getDataFromBilancio('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo') : $this->getDataFromBilancioPrev('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo'));
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo');
        $DebitiAccontiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiAccontiEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo = ($this->getDataFromBilancio('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo') ? $this->getDataFromBilancio('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo') : $this->getDataFromBilancioPrev('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo'));
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo = ($this->getDataFromBilancio('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo') ? $this->getDataFromBilancio('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo') : $this->getDataFromBilancioPrev('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo'));
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo = ($this->getDataFromBilancio('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo') ? $this->getDataFromBilancio('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo') : $this->getDataFromBilancioPrev('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo'));
        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo');
        $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo');
        $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo');
        $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo');
        $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo');
        $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo');
        $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo');
        $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo');

        $PassivoRateiRisconti = $this->getDataFromBilancio('PassivoRateiRisconti');
        $TotaleDisponibilitaLiquide = $this->getDataFromBilancio('TotaleDisponibilitaLiquide');
        $TotaleRimanenze = $this->getDataFromBilancio('TotaleRimanenze');
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getDataFromBilancio('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni');
        $AttivoRateiRisconti = $this->getDataFromBilancio('AttivoRateiRisconti');


        $ACID_TEST_Ordinario_divisore = $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiAccontiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $PassivoRateiRisconti;

        if ($ACID_TEST_Ordinario_divisore > 0) {
            $ACID_TEST_Ordinario = number_format(((($TotaleDisponibilitaLiquide + $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo + $TotaleRimanenze + $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + $AttivoRateiRisconti - $TotaleRimanenze) / ($ACID_TEST_Ordinario_divisore))) * 100, 2, ',', '.');
            $calculation = '((('.$TotaleDisponibilitaLiquide.' + '.$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo.' + '.$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo.' + '.$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo.' + '.$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo.' + '.$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo.' + '.$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo.' + '.$TotaleRimanenze.' + '.$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni.' + '.$AttivoRateiRisconti.' - '.$TotaleRimanenze.') / ('.$ACID_TEST_Ordinario_divisore.'))) * '. 100 .' = '.$ACID_TEST_Ordinario;
        } else {
            $ACID_TEST_Ordinario = "Non Calcolabile";
            $calculation = $ACID_TEST_Ordinario;
        }

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAcidTestOrdinario', $calculation);

        return $ACID_TEST_Ordinario;
    }

    public function getAutonomiaFinanziaria()
    {
        $TotalePatrimonioNetto = $this->getTotalePatrimonioNetto();
        $TotaleDebiti = $this->getDataFromBilancio('TotaleDebiti');

        $TotalePatrimonioNetto = str_replace(',', '.', str_replace('.', '', $TotalePatrimonioNetto));

        if (($TotalePatrimonioNetto + $TotaleDebiti) == 0) {
            $AUTONOMIA_FINANZIARIA = number_format((((float)$TotalePatrimonioNetto / 1)) * 100, 2, ',', '.');
            $calculation = '(('.(float)$TotalePatrimonioNetto.' / '. 1 .')) * '. 100 .' = '.$AUTONOMIA_FINANZIARIA;
        } else {
            $AUTONOMIA_FINANZIARIA = number_format((float)(($TotalePatrimonioNetto / ($TotalePatrimonioNetto + $TotaleDebiti))) * 100, 2, ',', '.');
            $calculation = '(('.(float)$TotalePatrimonioNetto.' / ('.$TotalePatrimonioNetto.' + '.$TotaleDebiti.'))) * '. 100 .' = '.$AUTONOMIA_FINANZIARIA;
        }

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAutonomiaFinanziaria', $calculation);

        return $AUTONOMIA_FINANZIARIA;
    }

    public function getLivelloInvestimentiAziendali()
    {
        $TotalePatrimonioNetto = $this->getTotalePatrimonioNetto();
        $TotaleAttivo = $this->getDataFromBilancio('TotaleAttivo');
        $TotalePatrimonioNetto = str_replace(',', '.', str_replace('.', '', $TotalePatrimonioNetto));

        if ($TotaleAttivo == 0) {
            $LIVELLO_INVESTIMENTI_AZIENDALI = number_format((float)($TotalePatrimonioNetto / 0.1) * 100, 2, ',', '.');
            $calculation = '('.(float)$TotalePatrimonioNetto.' / '. 0.1 .') * '. 100 .' = '.$LIVELLO_INVESTIMENTI_AZIENDALI;
        } else {
            $LIVELLO_INVESTIMENTI_AZIENDALI = number_format((float)($TotalePatrimonioNetto / $TotaleAttivo) * 100, 2, ',', '.');
            $calculation = '('.(float)$TotalePatrimonioNetto.' / '. $TotaleAttivo .') * '. 100 .' = '.$LIVELLO_INVESTIMENTI_AZIENDALI;
        }

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetLivelloInvestimentiAziendali', $calculation);

        return $LIVELLO_INVESTIMENTI_AZIENDALI;
    }

    public function getPfnEbitda()
    {
        $getAndamentoDelMol = $this->getAndamentoDelMol();
        $MOLcurr = str_replace(',', '.', (str_replace('.', '',$getAndamentoDelMol['MOLcurr'])));
        // $MOLcurr = number_format($getAndamentoDelMol['MOLcurr'], '.', ',');

        $ImmobilizzazioniFinanziarieCreditiTotaleCrediti = $this->getDataFromBilancio('ImmobilizzazioniFinanziarieCreditiTotaleCrediti');
        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo');
        $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo = $this->getDataFromBilancio('DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo');
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo');
        $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo = $this->getDataFromBilancio('DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo');
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo');
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo');
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo');
        $TotaleDisponibilitaLiquide = $this->getDataFromBilancio('TotaleDisponibilitaLiquide');

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
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getDataFromBilancio('ValoreProduzioneRicaviVenditePrestazioni');
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $this->getDataFromBilancio('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari');

        if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
            $ValoreProduzioneRicaviVenditePrestazioni = 1;
        }
        $Peso_Oneri_Finanziari = number_format(($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari / $ValoreProduzioneRicaviVenditePrestazioni) * 100,  2, ',', '');
        $calculation = '('.$ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari.' / '.$ValoreProduzioneRicaviVenditePrestazioni.') * '. 100 .' = '.$Peso_Oneri_Finanziari;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetPesoOneriFinanziari', $calculation);

        return $Peso_Oneri_Finanziari;
    }

    public function getCoperturaLordaDegliOneriFinanziari()
    {
        $TotaleValoreProduzione = $this->getDataFromBilancio('TotaleValoreProduzione');
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerci = $this->getDataFromBilancio('CostiProduzioneMateriePrimeSussidiarieConsumoMerci');
        $CostiProduzioneGodimentoBeniTerzi = $this->getDataFromBilancio('CostiProduzioneGodimentoBeniTerzi');
        $CostiProduzioneServizi = $this->getDataFromBilancio('CostiProduzioneServizi');
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getDataFromBilancio('CostiProduzionePersonaleTotaleCostiPersonale');
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci = $this->getDataFromBilancio('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci');
        $CostiProduzioneOneriDiversiGestione = $this->getDataFromBilancio('CostiProduzioneOneriDiversiGestione');
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari =  $this->getDataFromBilancio('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari');

        if ($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari == 0) {
            $Copertura_Lorda_degli_Oneri_Finanziari = number_format((($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / 1), 2, ',', '.');
            $calculation = '(('.$TotaleValoreProduzione.' - '.$CostiProduzioneMateriePrimeSussidiarieConsumoMerci.' - '.$CostiProduzioneServizi.' - '.$CostiProduzioneGodimentoBeniTerzi.' - '.$CostiProduzionePersonaleTotaleCostiPersonale.' - '.$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci.' - '.$CostiProduzioneOneriDiversiGestione.') / '. 1 .') = '.(float)$Copertura_Lorda_degli_Oneri_Finanziari.' * '. 100;
        } else {
            $Copertura_Lorda_degli_Oneri_Finanziari = number_format((($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari), 2, ',', '.');
            $calculation = '(('.$TotaleValoreProduzione.' - '.$CostiProduzioneMateriePrimeSussidiarieConsumoMerci.' - '.$CostiProduzioneServizi.' - '.$CostiProduzioneGodimentoBeniTerzi.' - '.$CostiProduzionePersonaleTotaleCostiPersonale.' - '.$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci.' - '.$CostiProduzioneOneriDiversiGestione.') / '. $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari .') = '.(float)$Copertura_Lorda_degli_Oneri_Finanziari.' * '. 100;
        }

        $Copertura_Lorda_degli_Oneri_Finanziari = (float)$Copertura_Lorda_degli_Oneri_Finanziari * 100;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetCoperturaLordaDegliOneriFinanziari', $calculation);

        return $Copertura_Lorda_degli_Oneri_Finanziari;
    }

    public function getEbitOf()
    {
        $TotaleValoreProduzione = $this->getDataFromBilancio('TotaleValoreProduzione');
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerci = $this->getDataFromBilancio('CostiProduzioneMateriePrimeSussidiarieConsumoMerci');
        $CostiProduzioneGodimentoBeniTerzi = $this->getDataFromBilancio('CostiProduzioneGodimentoBeniTerzi');
        $CostiProduzioneServizi = $this->getDataFromBilancio('CostiProduzioneServizi');
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getDataFromBilancio('CostiProduzionePersonaleTotaleCostiPersonale');
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci = $this->getDataFromBilancio('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci');
        $CostiProduzioneOneriDiversiGestione = $this->getDataFromBilancio('CostiProduzioneOneriDiversiGestione');
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari =  $this->getDataFromBilancio('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari');
        $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni = $this->getDataFromBilancio('CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni');
        $CostiProduzioneAccantonamentiRischi = $this->getDataFromBilancio('CostiProduzioneAccantonamentiRischi');
        $CostiProduzioneAltriAccantonamenti =  $this->getDataFromBilancio('CostiProduzioneAltriAccantonamenti');


        if ($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari == 0) {
            $EBIT_OF = number_format((($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione - $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - $CostiProduzioneAccantonamentiRischi - $CostiProduzioneAltriAccantonamenti) / 1), 2, ',', '.');
            $calculation = '(('.$TotaleValoreProduzione.' - '.$CostiProduzioneMateriePrimeSussidiarieConsumoMerci.' - '.$CostiProduzioneServizi.' - '.$CostiProduzioneGodimentoBeniTerzi.' - '.$CostiProduzionePersonaleTotaleCostiPersonale.' - '.$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci.' - '.$CostiProduzioneOneriDiversiGestione.' - '.$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni.' - '.$CostiProduzioneAccantonamentiRischi.' - '.$CostiProduzioneAltriAccantonamenti.') / '. 1 .') '.(float)$EBIT_OF.' * '. 100 .' = '.(float)$EBIT_OF*100;
        } else {
            $EBIT_OF = number_format((($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione - $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - $CostiProduzioneAccantonamentiRischi - $CostiProduzioneAltriAccantonamenti) / $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari), 2, ',', '.');
            $calculation = '(('.$TotaleValoreProduzione.' - '.$CostiProduzioneMateriePrimeSussidiarieConsumoMerci.' - '.$CostiProduzioneServizi.' - '.$CostiProduzioneGodimentoBeniTerzi.' - '.$CostiProduzionePersonaleTotaleCostiPersonale.' - '.$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci.' - '.$CostiProduzioneOneriDiversiGestione.' - '.$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni.' - '.$CostiProduzioneAccantonamentiRischi.' - '.$CostiProduzioneAltriAccantonamenti.') / '.$ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari.')  '.(float)$EBIT_OF.' * '. 100 .' = '.(float)$EBIT_OF*100;
        }

        $EBIT_OF = (float)$EBIT_OF * 100;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetEbitOf', $calculation);

        return $EBIT_OF;
    }

    public function getCostoDelPersonale()
    {
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getDataFromBilancio('CostiProduzionePersonaleTotaleCostiPersonale');
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getDataFromBilancio('ValoreProduzioneRicaviVenditePrestazioni');


        if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
            $Costo_del_personale = number_format((float)($CostiProduzionePersonaleTotaleCostiPersonale / 1) * 100, 2, ',', '.');
            $calculation = '('.(float)$CostiProduzionePersonaleTotaleCostiPersonale.' / '. 1 .') * '. 100 .' = '.$Costo_del_personale;
        } else {
            $Costo_del_personale = number_format((float)($CostiProduzionePersonaleTotaleCostiPersonale / $ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '.');
            $calculation = '('.(float)$CostiProduzionePersonaleTotaleCostiPersonale.' / '. $ValoreProduzioneRicaviVenditePrestazioni .') * '. 100 .' = '.$Costo_del_personale;
        }

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetCostoDelPersonale', $calculation);

        return $Costo_del_personale;
    }

    public function getCfAttivo()
    {
        $TotaleAttivo = $this->getDataFromBilancio('TotaleAttivo');
        $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni = $this->getDataFromBilancio('CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni');
        $CostiProduzioneAccantonamentiRischi = $this->getDataFromBilancio('CostiProduzioneAccantonamentiRischi');
        $CostiProduzioneAltriAccantonamenti =  $this->getDataFromBilancio('CostiProduzioneAltriAccantonamenti');
        $UtilePerditaEsercizio = $this->getDataFromBilancio('UtilePerditaEsercizio');
        $imposteRedditoEsercizioImposteAnticipate = $this->getDataFromBilancio('ImposteRedditoEsercizioCorrentiDifferiteAnticipateImposteDifferiteAnticipate');

        if ($TotaleAttivo == 0) {
            $CF_ATTIVO = number_format((($UtilePerditaEsercizio + $CostiProduzioneAccantonamentiRischi + $CostiProduzioneAltriAccantonamenti + $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - $imposteRedditoEsercizioImposteAnticipate) / 0.1) * 100, 2, ',', '.');
            $calculation = '(('.$UtilePerditaEsercizio.' + '.$CostiProduzioneAccantonamentiRischi.' + '.$CostiProduzioneAltriAccantonamenti.' + '.$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni.' - '.$imposteRedditoEsercizioImposteAnticipate.') / '. 0.1 .') * '. 100;
        } else {
            $CF_ATTIVO = number_format((($UtilePerditaEsercizio + $CostiProduzioneAccantonamentiRischi + $CostiProduzioneAltriAccantonamenti + $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - $imposteRedditoEsercizioImposteAnticipate) / $TotaleAttivo) * 100, 2, ',', '.');
            $calculation = '(('.$UtilePerditaEsercizio.' + '.$CostiProduzioneAccantonamentiRischi.' + '.$CostiProduzioneAltriAccantonamenti.' + '.$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni.' - '.$imposteRedditoEsercizioImposteAnticipate.') / '. $TotaleAttivo .') * '. 100;
        }

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetCfAttivo', $calculation);

        return $CF_ATTIVO;
    }

    public function getIndiceDiIndebitamento()
    {
        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo');
        $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo = $this->getDataFromBilancio('DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo');
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo');
        $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo = $this->getDataFromBilancio('DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo');
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo');
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo');
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo');
        $TotalePatrimonioNetto = $this->getTotalePatrimonioNetto();
        $TotaleDisponibilitaLiquide = $this->getDataFromBilancio('TotaleDisponibilitaLiquide');
        $ImmobilizzazioniFinanziarieCreditiTotaleCrediti = $this->getDataFromBilancio('ImmobilizzazioniFinanziarieCreditiTotaleCrediti');
        $TotalePatrimonioNetto = str_replace(',', '.', str_replace('.', '', $TotalePatrimonioNetto));

        if ($TotalePatrimonioNetto == 0) {
            $MOLannoCorrente = $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;
            $Indice_di_Indebitamento = number_format((($MOLannoCorrente - $TotaleDisponibilitaLiquide - $ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / 0.1) * 100, 2, ',', '.');
            $calculation = '(('.$MOLannoCorrente.' - '.$TotaleDisponibilitaLiquide.' - '.$ImmobilizzazioniFinanziarieCreditiTotaleCrediti.') / '. 0.1 .') * '. 100 .' = '.$Indice_di_Indebitamento;
            $dataAnalisis['Indice_di_Indebitamento'] = $Indice_di_Indebitamento;
        } else {
            $MOLannoCorrente = $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;
            $Indice_di_Indebitamento = number_format((($MOLannoCorrente - $TotaleDisponibilitaLiquide - $ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / $TotalePatrimonioNetto) * 100, 2, ',', '.');
            $calculation = '(('.$MOLannoCorrente.' - '.$TotaleDisponibilitaLiquide.' - '.$ImmobilizzazioniFinanziarieCreditiTotaleCrediti.') / '. $TotalePatrimonioNetto .') * '. 100 .' = '.$Indice_di_Indebitamento;
            $dataAnalisis['Indice_di_Indebitamento'] = $Indice_di_Indebitamento;
        }

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetIndiceDiIndebitamento', $calculation);

        return $Indice_di_Indebitamento;
    }

    public function getSaldoDebitiVsFisco()
    {
        $DebitiDebitiTributariTotaleDebitiTributariCorrente = $this->getDataFromBilancio('DebitiDebitiTributariTotaleDebitiTributari');
        $FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente = $this->getDataFromBilancio('FondiRischiOneriTrattamentoQuiescenzaObblighiSimili');
        $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipatePrecedente = $this->getDataFromBilancioPrev('ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate');
        $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate = $this->getDataFromBilancio('ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate');

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
}
