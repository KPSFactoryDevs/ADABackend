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

        if ($TotaleAttivo == 0) {
            $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO = (($DebitiDebitiTributariTotaleDebitiTributari + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) / 1) * 100;
            $calculation = '(('.$DebitiDebitiTributariTotaleDebitiTributari.' + '.$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale.') / '. 1 .') * '. 100 .' = '.$INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO;
        } else {
            $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO = (($DebitiDebitiTributariTotaleDebitiTributari + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) / $TotaleAttivo) * 100;
            $calculation = '(('.$DebitiDebitiTributariTotaleDebitiTributari.' + '.$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale.') / '. $TotaleAttivo .') * '. 100 .' = '.$INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO;
        }
        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetIndebitamentoPrevidenzialeTributario', $calculation);

        return $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO;
    }

    public function getAndamentoDelFatturato()
    {
        $ValoreProduzioneRicaviVenditePrestazioniCurr = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1);
        $ValoreProduzioneRicaviVenditePrestazioniPrev = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 2);

        if ($ValoreProduzioneRicaviVenditePrestazioniPrev == 0) {
            $AndamentoDelFatturato = (- (1 - (($ValoreProduzioneRicaviVenditePrestazioniCurr) / (1)))) * 100;
            $calculation = '( - ('. 1 .' - (('.$ValoreProduzioneRicaviVenditePrestazioniCurr.') / ('. 1 .')))) * '. 100 .' = '.$AndamentoDelFatturato;
        } else {
            $AndamentoDelFatturato = (- (1 - (($ValoreProduzioneRicaviVenditePrestazioniCurr) / ($ValoreProduzioneRicaviVenditePrestazioniPrev)))) * 100;
            $calculation = '(- ('. 1 .' - (('.$ValoreProduzioneRicaviVenditePrestazioniCurr.') / ('.$ValoreProduzioneRicaviVenditePrestazioniPrev.')))) * '. 100 .' = '.$AndamentoDelFatturato;
        }
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

        if ($MOLprev == 0) {
            $AndamentoMOL = - (1 - ($MOLcurr / 1)) * 100;
            $calculation = '- ('. 1 .' - ('.$MOLcurr.' / '. 1 .')) * '. 100 .' = '.$AndamentoMOL;
        } else {
            $AndamentoMOL = - (1 - ($MOLcurr / $MOLprev)) * 100;
            $calculation = '- ('. 1 .' - ('.$MOLcurr.' / '. $MOLprev .')) * '. 100 .' = '.$AndamentoMOL;         
        }

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

        if ($TotaleAttivo == 0) {
            $ROI = ($DifferenzaValoreCostiProduzione / 1) * 100;
            $calculation = '('.$DifferenzaValoreCostiProduzione .' / '. 1 .') * '. 100 .' = '.$ROI;
        } else {
            $ROI = ($DifferenzaValoreCostiProduzione / $TotaleAttivo) * 100;
            $calculation = '('.$DifferenzaValoreCostiProduzione .' / '. $TotaleAttivo .') * '. 100 .' = '.$ROI;
        }
        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetROI', $calculation);

        return $ROI;
    }

    public function getROS()
    {
        $DifferenzaValoreCostiProduzione = $this->getElementFromBalance('DifferenzaValoreCostiProduzione', 1);
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getElementFromBalance('ValoreProduzioneRicaviVenditePrestazioni', 1);

        if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
            $ROS = ($DifferenzaValoreCostiProduzione / 1) * 100;
            $calculation = '('. $DifferenzaValoreCostiProduzione .' / '. 1 .') * '. 100 .' = '.$ROS;
        } else {
            $ROS = ($DifferenzaValoreCostiProduzione / $ValoreProduzioneRicaviVenditePrestazioni) * 100;
            $calculation = '('. $DifferenzaValoreCostiProduzione .' / '. $ValoreProduzioneRicaviVenditePrestazioni .') * '. 100 .' = '.$ROS;
        }


        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetROS', $calculation);

        return $ROS;
    }

    public function getROE()
    {
        $TotalePatrimonioNetto = $this->getElementFromBalance('TotalePatrimonioNetto', 1);
        $UtilePerditaEsercizio = $this->getElementFromBalance('UtilePerditaEsercizio', 1);

        if ($TotalePatrimonioNetto == 0) {
            $ROE = ($UtilePerditaEsercizio / 1) * 100;
            $calculation = '('. $UtilePerditaEsercizio .' / '. 1 .') * '. 100 .' = '.$ROE;
        } else {
            $ROE = ($UtilePerditaEsercizio / $TotalePatrimonioNetto) * 100;
            $calculation = '('. $UtilePerditaEsercizio .' / '. $TotalePatrimonioNetto .') * '. 100 .' = '.$ROE;
        }        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetROE', $calculation);

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
        $TrattamentoFineRapportoLavoroSubordinato = $this->getElementFromBalance('TrattamentoFineRapportoLavoroSubordinato', 1);
        $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 1);
        $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 1);
        $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 1) != 0 ? $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 1)  : $this->getElementFromBalance('DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 2));
        $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 1);
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 1);
        $DebitiAccontiEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiAccontiEsigibiliOltreEsercizioSuccessivo', 1);
        $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo', 1);
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo', 1);
        $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo', 1);
        $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo', 1);
        $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo', 1);
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo = $this->getElementFromBalance('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo', 1);
        $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo = ($this->getElementFromBalance('DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo', 1) != 0 ? $this->getElementFromBalance('DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo', 1) :  $this->getElementFromBalance('DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo', 2));
        $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo = ($this->getElementFromBalance('DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo', 1) != 0 ? $this->getElementFromBalance('DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo', 1) : $this->getElementFromBalance('DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo', 2));
        $TotalePatrimonioNetto = $this->getElementFromBalance('TotalePatrimonioNetto', 1);
        $TotaleImmobilizzazioni = $this->getElementFromBalance('TotaleImmobilizzazioni', 1);

            $Margine_Struttura_Secondario_Semplificato = (($TotalePatrimonioNetto + $TrattamentoFineRapportoLavoroSubordinato + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo + $DebitiAccontiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) / $TotaleImmobilizzazioni) * 100;
            $calculation = '(('.$TotalePatrimonioNetto.' + '.$TrattamentoFineRapportoLavoroSubordinato.' + '.$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiAccontiEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo.' + '.$DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo.') / '. $TotaleImmobilizzazioni .') * '. 100 .' = '.$Margine_Struttura_Secondario_Semplificato;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetMargineStrutturaSecondario', $calculation);

        return $Margine_Struttura_Secondario_Semplificato;
    }

    public function getCurrentRatio()
    {
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1);
        $AttivoRateiRisconti = $this->getElementFromBalance('AttivoRateiRisconti', 1);
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getElementFromBalance('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 1);
        $TotaleRimanenze = $this->getElementFromBalance('TotaleRimanenze', 1);
        $TotaleCreditiEntroDodiciMesi = $this->getElementFromBalance('TotaleCreditiEntroDodiciMesi', 1);
        $TotaleDebitiEntroDodiciMesi = $this->getElementFromBalance('TotaleDebitiEntroDodiciMesi', 1);
        $PassivoRateiRisconti = $this->getElementFromBalance('PassivoRateiRisconti', 1);

        $denominatoreRitornoLiquidoAttivo = 0;
        if (($TotaleDebitiEntroDodiciMesi + $PassivoRateiRisconti) == 0) {
            $denominatoreRitornoLiquidoAttivo = 1;
        }

        $formula = ($TotaleDisponibilitaLiquide + $AttivoRateiRisconti + $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + $TotaleRimanenze + $TotaleCreditiEntroDodiciMesi) / ($TotaleDebitiEntroDodiciMesi + $PassivoRateiRisconti + $denominatoreRitornoLiquidoAttivo);
        $calculation = '('.$TotaleDisponibilitaLiquide.' + '.$AttivoRateiRisconti.' + '.$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni.' + '.$TotaleRimanenze.' + '.$TotaleCreditiEntroDodiciMesi.') / ('.$TotaleDebitiEntroDodiciMesi.' + '.$PassivoRateiRisconti.' + '.$denominatoreRitornoLiquidoAttivo.') = '.$formula;

        $RITORNO_LIQUIDO_ATTIVO = $formula * 100;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetCurrentRatio', $calculation);

        return $RITORNO_LIQUIDO_ATTIVO;
    }

    public function getAttivitaPassivitaABreve()
    {
        // TRENTACINQUE
        $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 1);
        $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 1);
        $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 1);
        $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 1) ? $this->getElementFromBalance('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 1) : $this->getElementFromBalance('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 2));
        $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = ($this->getElementFromBalance('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 1) ? $this->getElementFromBalance('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 1) : $this->getElementFromBalance('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 2));
        $TrentaCinque = $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo;


        // QUARANTANOVE
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

        $PassivoRateiRisconti = $this->getElementFromBalance('PassivoRateiRisconti', 1);
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1);
        $TotaleRimanenze = $this->getElementFromBalance('TotaleRimanenze', 1);
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getElementFromBalance('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 1);
        $AttivoRateiRisconti = $this->getElementFromBalance('AttivoRateiRisconti', 1);

        $QuarantaNove = $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiAccontiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo;
        $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo = $this->getElementFromBalance('CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo', 1);

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
        $TotalePatrimonioNetto = $this->getTotalePatrimonioNetto();
        $TotaleDebiti = $this->getElementFromBalance('TotaleDebiti', 1);

        $TotalePatrimonioNetto = str_replace(',', '.', str_replace('.', '', $TotalePatrimonioNetto));

        if (($TotalePatrimonioNetto + $TotaleDebiti) == 0) {
            $AUTONOMIA_FINANZIARIA = (((float)$TotalePatrimonioNetto / 1)) * 100;
            $calculation = '(('.(float)$TotalePatrimonioNetto.' / '. 1 .')) * '. 100 .' = '.$AUTONOMIA_FINANZIARIA;
        } else {
            $AUTONOMIA_FINANZIARIA = (float)(($TotalePatrimonioNetto / ($TotalePatrimonioNetto + $TotaleDebiti))) * 100;
            $calculation = '(('.(float)$TotalePatrimonioNetto.' / ('.$TotalePatrimonioNetto.' + '.$TotaleDebiti.'))) * '. 100 .' = '.$AUTONOMIA_FINANZIARIA;
        }

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAutonomiaFinanziaria', $calculation);

        return $AUTONOMIA_FINANZIARIA;
    }

    public function getLivelloInvestimentiAziendali()
    {
        $TotalePatrimonioNetto = $this->getTotalePatrimonioNetto();
        $TotaleAttivo = $this->getElementFromBalance('TotaleAttivo', 1);
        $TotalePatrimonioNetto = str_replace(',', '.', str_replace('.', '', $TotalePatrimonioNetto));

        if ($TotaleAttivo == 0) {
            $LIVELLO_INVESTIMENTI_AZIENDALI = (float)($TotalePatrimonioNetto / 0.1) * 100;
            $calculation = '('.(float)$TotalePatrimonioNetto.' / '. 0.1 .') * '. 100 .' = '.$LIVELLO_INVESTIMENTI_AZIENDALI;
        } else {
            $LIVELLO_INVESTIMENTI_AZIENDALI = (float)($TotalePatrimonioNetto / $TotaleAttivo) * 100;
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
        $TotalePatrimonioNetto = $this->getTotalePatrimonioNetto();
        $TotaleDisponibilitaLiquide = $this->getElementFromBalance('TotaleDisponibilitaLiquide', 1);
        $ImmobilizzazioniFinanziarieCreditiTotaleCrediti = $this->getElementFromBalance('ImmobilizzazioniFinanziarieCreditiTotaleCrediti', 1);
        $TotalePatrimonioNetto = str_replace(',', '.', str_replace('.', '', $TotalePatrimonioNetto));

        if ($TotalePatrimonioNetto == 0) {
            $MOLannoCorrente = $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;
            $Indice_di_Indebitamento = (($MOLannoCorrente - $TotaleDisponibilitaLiquide - $ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / 0.1) * 100;
            $calculation = '(('.$MOLannoCorrente.' - '.$TotaleDisponibilitaLiquide.' - '.$ImmobilizzazioniFinanziarieCreditiTotaleCrediti.') / '. 0.1 .') * '. 100 .' = '.$Indice_di_Indebitamento;
            $dataAnalisis['Indice_di_Indebitamento'] = $Indice_di_Indebitamento;
        } else {
            $MOLannoCorrente = $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;
            $Indice_di_Indebitamento = (($MOLannoCorrente - $TotaleDisponibilitaLiquide - $ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / $TotalePatrimonioNetto) * 100;
            $calculation = '(('.$MOLannoCorrente.' - '.$TotaleDisponibilitaLiquide.' - '.$ImmobilizzazioniFinanziarieCreditiTotaleCrediti.') / '. $TotalePatrimonioNetto .') * '. 100 .' = '.$Indice_di_Indebitamento;
            $dataAnalisis['Indice_di_Indebitamento'] = $Indice_di_Indebitamento;
        }

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
}
