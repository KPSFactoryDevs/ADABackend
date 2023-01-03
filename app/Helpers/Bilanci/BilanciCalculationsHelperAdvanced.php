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

    public function getElementFromBalance($elementName)
    {
        $elements = $this->_currentInstance->getElements();
        $elements = $elements->getElements();

        return $elements[$elementName];
    }


    public function getTotalePatrimonioNetto()
    {
        $TotalePatrimonioNetto = $this->getDataFromBilancio('TotalePatrimonioNetto');

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetPatrimonioNetto', $TotalePatrimonioNetto);

        return  number_format($TotalePatrimonioNetto, 2, ',', '.');
    }

    public function getPatrimonioNettoNegativo()
    {
        $TotalePatrimonioNetto = $this->getTotalePatrimonioNetto();
        $TotaleCreditiVersoSociVersamentiAncoraDovuti = $this->getDataFromBilancio('TotaleCreditiVersoSociVersamentiAncoraDovuti');

        $TotalePatrimonioNetto = str_replace(',', '.', str_replace('.', '', $TotalePatrimonioNetto));

        $PN_NEGATIVO = $TotalePatrimonioNetto - $TotaleCreditiVersoSociVersamentiAncoraDovuti;
        $calculationPNnegativo = $TotalePatrimonioNetto.' - '.$TotaleCreditiVersoSociVersamentiAncoraDovuti.' = '.$PN_NEGATIVO;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetPatrimonioNettoNegativo', $calculationPNnegativo);

        return  $PN_NEGATIVO;
    }

    public function getOfRicavi()
    {
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari =  $this->getDataFromBilancio('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari');
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getDataFromBilancio('ValoreProduzioneRicaviVenditePrestazioni');
        if ($ValoreProduzioneRicaviVenditePrestazioni != 0) {
            $OF_RICAVI = number_format(($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari / $ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '.');
            $calculation = $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari.' / '.$ValoreProduzioneRicaviVenditePrestazioni.' * '. 100 .' = '.$OF_RICAVI;
        } else {
            $OF_RICAVI = "Non Calcolabile";
            $calculation = $OF_RICAVI;
        }


        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetOF_RICAVI', $calculation);

        return $OF_RICAVI;
    }

    public function getTotaleDebiti()
    {
        $TotaleDebiti = $this->getDataFromBilancio('TotaleDebiti');

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetTotaleDebiti', $TotaleDebiti);

        return $TotaleDebiti;
    }

    public function getAdeguatezzaPatrimoniale()
    {
        $PN_NEGATIVO = $this->getPatrimonioNettoNegativo();
        $TotaleDebiti = $this->getTotaleDebiti();
        $PassivoRateiRisconti = $this->getDataFromBilancio('PassivoRateiRisconti');

        $ADEGUATEZZA_PATRIMONIALE = number_format(($PN_NEGATIVO / ($TotaleDebiti + $PassivoRateiRisconti)) * 100, 2, ',', '.');
        $calculation = $PN_NEGATIVO.' / ('.$TotaleDebiti.' + '.$PassivoRateiRisconti.') * '. 100;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAdeguatezzaPatrimoniale', $calculation);

        return $ADEGUATEZZA_PATRIMONIALE;
    }

    public function getTotaleCreditiEntroDodiciMesi()
    {
        $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo');
        $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo');
        $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo');
        $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo');
        $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo');
        $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo');
        $CreditiImposteAnticipateTotaleImposteAnticipate = $this->getDataFromBilancio('CreditiImposteAnticipateTotaleImposteAnticipate');

        $TotaleDebitiEntroDodiciMesi = (float)$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + (float)$CreditiImposteAnticipateTotaleImposteAnticipate + (float)$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo;
        $calculation = (float)$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo.' + '.(float)$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo.' + '.(float)$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo.' + '.(float)$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo.' + '.(float)$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo.' + '.(float)$CreditiImposteAnticipateTotaleImposteAnticipate.' + '.(float)$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo.' = '.$TotaleDebitiEntroDodiciMesi;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetTotaleCreditiEntroDodiciMesi', $calculation);

        return $TotaleDebitiEntroDodiciMesi;
    }

    public function getTotaleDebitiEntroDodiciMesi()
    {
        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo');
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo');
        $DebitiAccontiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiAccontiEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo');
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo');
        $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo');

        $TotaleDebitiEntroDodiciMesi = (float)$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAccontiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo;

        $calculation = (float)$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiAccontiEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo.' + '.(float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo.' = '.$TotaleDebitiEntroDodiciMesi;

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetTotaleDebitiEntroDodiciMesi', $calculation);

        return $TotaleDebitiEntroDodiciMesi;
    }

    public function getLiquidita()
    {
        $CostiProduzioneAccantonamentiRischi = $this->getDataFromBilancio('CostiProduzioneAccantonamentiRischi');
        $TotaleAttivo = $this->getDataFromBilancio('TotaleAttivo');
        $UtilePerditaEsercizio = $this->getDataFromBilancio('UtilePerditaEsercizio');
        $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni = $this->getDataFromBilancio('CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni');
        $CostiProduzioneAltriAccantonamenti = $this->getDataFromBilancio('CostiProduzioneAltriAccantonamenti');


        if ($TotaleAttivo == 0) {
            $LIQUIDITA = 'Non Calcolabile';
            $calculationLIQUIDITA = $LIQUIDITA;
        } else {
            $LIQUIDITA = number_format((($UtilePerditaEsercizio + $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni + (float)$CostiProduzioneAccantonamentiRischi + (float)$CostiProduzioneAltriAccantonamenti) / (float)$TotaleAttivo) * 100, 2, ',', '.');
            $calculationLIQUIDITA = '(('.$UtilePerditaEsercizio.' + '.$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni.' + '.(float)$CostiProduzioneAccantonamentiRischi.' + '.(float)$CostiProduzioneAltriAccantonamenti.') / '.(float)$TotaleAttivo.') * '. 100;
        }

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetLiquidità', $calculationLIQUIDITA);

        return $LIQUIDITA;
    }

    public function getIndebitamentoPrevidenzialeTributario()
    {
        $DebitiDebitiTributariTotaleDebitiTributari = $this->getDataFromBilancio('DebitiDebitiTributariTotaleDebitiTributari');
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale = $this->getDataFromBilancio('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale');
        $TotaleAttivo = $this->getDataFromBilancio('TotaleAttivo');

        if ($TotaleAttivo == 0) {
            $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO = number_format((($DebitiDebitiTributariTotaleDebitiTributari + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) / 1) * 100, 2, ',', '.');
            $calculation = '(('.$DebitiDebitiTributariTotaleDebitiTributari.' + '.$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale.') / '. 1 .') * '. 100 .' = '.$INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO;
        } else {
            $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO = number_format((($DebitiDebitiTributariTotaleDebitiTributari + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) / $TotaleAttivo) * 100, 2, ',', '.');
            $calculation = '(('.$DebitiDebitiTributariTotaleDebitiTributari.' + '.$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale.') / '. $TotaleAttivo .') * '. 100 .' = '.$INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO;
        }

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetIndebitamentoPrevidenzialeTributario', $calculation);

        return $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO;
    }

    public function getAndamentoDelFatturato()
    {
        $ValoreProduzioneRicaviVenditePrestazioniCurr = $this->getDataFromBilancio('ValoreProduzioneRicaviVenditePrestazioni');
        $ValoreProduzioneRicaviVenditePrestazioniPrev = $this->getDataFromBilancioPrev('ValoreProduzioneRicaviVenditePrestazioni');
        if ($ValoreProduzioneRicaviVenditePrestazioniPrev == 0) {
            $AndamentoDelFatturato = number_format((- (1 - (($ValoreProduzioneRicaviVenditePrestazioniCurr) / (1)))) * 100, 2, ',', '.');
            $calculation = '( - ('. 1 .' - (('.$ValoreProduzioneRicaviVenditePrestazioniCurr.') / ('. 1 .')))) * '. 100 .' = '.$AndamentoDelFatturato;
        } else {
            $AndamentoDelFatturato = number_format((- (1 - (($ValoreProduzioneRicaviVenditePrestazioniCurr) / ($ValoreProduzioneRicaviVenditePrestazioniPrev)))) * 100, 2, ',', '.');
            $calculation = '(- ('. 1 .' - (('.$ValoreProduzioneRicaviVenditePrestazioniCurr.') / ('.$ValoreProduzioneRicaviVenditePrestazioniPrev.')))) * '. 100 .' = '.$AndamentoDelFatturato;
        }

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAndamentoDelFatturato', $calculation);

        return $AndamentoDelFatturato;
    }

    public function getAndamentoDelMol()
    {
        // Curr
        $TotaleValoreProduzione = $this->getDataFromBilancio('TotaleValoreProduzione');
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerci = $this->getDataFromBilancio('CostiProduzioneMateriePrimeSussidiarieConsumoMerci');
        $CostiProduzioneGodimentoBeniTerzi = $this->getDataFromBilancio('CostiProduzioneGodimentoBeniTerzi');
        $CostiProduzioneServizi = $this->getDataFromBilancio('CostiProduzioneServizi');
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getDataFromBilancio('CostiProduzionePersonaleTotaleCostiPersonale');
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci = $this->getDataFromBilancio('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci');
        $CostiProduzioneOneriDiversiGestione = $this->getDataFromBilancio('CostiProduzioneOneriDiversiGestione');

        // Prev
        $TotaleValoreProduzionePrecedente = $this->getDataFromBilancioPrev('TotaleValoreProduzione');
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerciPrecedente = $this->getDataFromBilancioPrev('CostiProduzioneMateriePrimeSussidiarieConsumoMerci');
        $CostiProduzioneGodimentoBeniTerziPrecedente = $this->getDataFromBilancioPrev('CostiProduzioneGodimentoBeniTerzi');
        $CostiProduzioneServiziPrecedente = $this->getDataFromBilancioPrev('CostiProduzioneServizi');
        $CostiProduzionePersonaleTotaleCostiPersonalePrecedente = $this->getDataFromBilancioPrev('CostiProduzionePersonaleTotaleCostiPersonale');
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerciPrecedente = $this->getDataFromBilancioPrev('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci');
        $CostiProduzioneOneriDiversiGestionePrecedente = $this->getDataFromBilancioPrev('CostiProduzioneOneriDiversiGestione');

        $MOLcurr = $TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzioneServizi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione;
        $MOLprev = $TotaleValoreProduzionePrecedente - $CostiProduzioneMateriePrimeSussidiarieConsumoMerciPrecedente - $CostiProduzioneGodimentoBeniTerziPrecedente - $CostiProduzioneServiziPrecedente - $CostiProduzionePersonaleTotaleCostiPersonalePrecedente - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerciPrecedente - $CostiProduzioneOneriDiversiGestionePrecedente;

        if ($MOLprev == 0) {
            $AndamentoMOL = number_format(- (1 - ($MOLcurr / 1)) * 100, 2, ',', '.');
            $calculation = '- ('. 1 .' - ('.$MOLcurr.' / '. 1 .')) * '. 100 .' = '.$AndamentoMOL;
        } else {
            $AndamentoMOL = number_format(- (1 - ($MOLcurr / $MOLprev)) * 100, 2, ',', '.');
            $calculation = '- ('. 1 .' - ('.$MOLcurr.' / '. $MOLprev .')) * '. 100 .' = '.$AndamentoMOL;
        }

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAndamentoDelMol', $calculation);

        $data = [
            'MOLcurr' => number_format($MOLcurr, 2, ',', '.'),
            'AndamentoMOL' => $AndamentoMOL,
        ];

        return $data;
    }

    public function getROI()
    {
        $DifferenzaValoreCostiProduzione = $this->getDataFromBilancio('DifferenzaValoreCostiProduzione');
        $TotaleAttivo = $this->getDataFromBilancio('TotaleAttivo');

        if ($TotaleAttivo == 0) {
            $ROI = number_format(($DifferenzaValoreCostiProduzione / 1) * 100, 2, ',', '.');
            $calculation = '('.$DifferenzaValoreCostiProduzione .' / '. 1 .') * '. 100 .' = '.$ROI;
        } else {
            $ROI = number_format(($DifferenzaValoreCostiProduzione / $TotaleAttivo) * 100, 2, ',', '.');
            $calculation = '('.$DifferenzaValoreCostiProduzione .' / '. $TotaleAttivo .') * '. 100 .' = '.$ROI;
        }

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetROI', $calculation);

        return $ROI;
    }

    public function getROS()
    {
        $DifferenzaValoreCostiProduzione = $this->getDataFromBilancio('DifferenzaValoreCostiProduzione');
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getDataFromBilancio('ValoreProduzioneRicaviVenditePrestazioni');

        if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
            $ROS = number_format(($DifferenzaValoreCostiProduzione / 1) * 100, 2, ',', '.');
            $calculation = '('. $DifferenzaValoreCostiProduzione .' / '. 1 .') * '. 100 .' = '.$ROS;
        } else {
            $ROS = number_format(($DifferenzaValoreCostiProduzione / $ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '.');
            $calculation = '('. $DifferenzaValoreCostiProduzione .' / '. $ValoreProduzioneRicaviVenditePrestazioni .') * '. 100 .' = '.$ROS;
        }

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetROS', $calculation);

        return $ROS;
    }

    public function getROE()
    {
        $TotalePatrimonioNetto = $this->getDataFromBilancio('TotalePatrimonioNetto');
        $UtilePerditaEsercizio = $this->getDataFromBilancio('UtilePerditaEsercizio');

        if ($TotalePatrimonioNetto == 0) {
            $ROE = number_format(($UtilePerditaEsercizio / 1) * 100, 2, ',', '.');
            $calculation = '('. $UtilePerditaEsercizio .' / '. 1 .') * '. 100 .' = '.$ROE;
        } else {
            $ROE = number_format(($UtilePerditaEsercizio / $TotalePatrimonioNetto) * 100, 2, ',', '.');
            $calculation = '('. $UtilePerditaEsercizio .' / '. $TotalePatrimonioNetto .') * '. 100 .' = '.$ROE;
        }

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetROE', $calculation);

        return $ROE;
    }

    public function getEbitdaFatturato()
    {
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getDataFromBilancio('ValoreProduzioneRicaviVenditePrestazioni');
        $TotaleValoreProduzione = $this->getDataFromBilancio('TotaleValoreProduzione');
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerci = $this->getDataFromBilancio('CostiProduzioneMateriePrimeSussidiarieConsumoMerci');
        $CostiProduzioneServizi = $this->getDataFromBilancio('CostiProduzioneServizi');
        $CostiProduzioneGodimentoBeniTerzi = $this->getDataFromBilancio('CostiProduzioneGodimentoBeniTerzi');
        $CostiProduzionePersonaleTotaleCostiPersonale = $this->getDataFromBilancio('CostiProduzionePersonaleTotaleCostiPersonale');
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci = $this->getDataFromBilancio('CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci');
        $CostiProduzioneOneriDiversiGestione = $this->getDataFromBilancio('CostiProduzioneOneriDiversiGestione');

        if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
            $EBITDA_FATTURATO = number_format((($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / 1) * 100, 2, ',', '.');
            $calculation = '(('.$TotaleValoreProduzione.' - '.$CostiProduzioneMateriePrimeSussidiarieConsumoMerci.' - '.$CostiProduzioneServizi.' - '.$CostiProduzioneGodimentoBeniTerzi.' - '.$CostiProduzionePersonaleTotaleCostiPersonale.' - '.$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci .' - '. $CostiProduzioneOneriDiversiGestione.') / '. 1 .') * '. 100 .' = '.$EBITDA_FATTURATO;
        } else {
            $EBITDA_FATTURATO = number_format((($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / $ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '.');
            $calculation = '(('.$TotaleValoreProduzione.' - '.$CostiProduzioneMateriePrimeSussidiarieConsumoMerci.' - '.$CostiProduzioneServizi.' - '.$CostiProduzioneGodimentoBeniTerzi.' - '.$CostiProduzionePersonaleTotaleCostiPersonale.' - '.$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci .' - '. $CostiProduzioneOneriDiversiGestione.') / '. $ValoreProduzioneRicaviVenditePrestazioni .') * '. 100 .' = '.$EBITDA_FATTURATO;
        }

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetEbitdaFatturato', $calculation);

        return $EBITDA_FATTURATO;
    }

    public function getAndamentoDeiMezziPropri()
    {
        $TotalePatrimonioNettoCurr = $this->getDataFromBilancio('TotalePatrimonioNetto');
        $TotalePatrimonioNettoPrev = $this->getDataFromBilancioPrev('TotalePatrimonioNetto');

        if ($TotalePatrimonioNettoPrev == 0) {
            $AndamentoDeiMezziPropri = number_format((($TotalePatrimonioNettoCurr / 1) - 1) * 100, 2, ',', '.');
            $calculation = '(('.$TotalePatrimonioNettoCurr.' / '. 1 .') - '. 1 .') * '. 100 .' = '.$AndamentoDeiMezziPropri;
        } else {
            $AndamentoDeiMezziPropri = number_format((($TotalePatrimonioNettoCurr / $TotalePatrimonioNettoPrev) - 1) * 100, 2, ',', '.');
            $calculation = '(('.$TotalePatrimonioNettoCurr.' / '. 1 .') - '. $TotalePatrimonioNettoPrev .') * '. 100 .' = '.$AndamentoDeiMezziPropri;
        }

        CustomLog::addToLogAnalisiBilancioCalculation('BilanciCalculationsHelper', 'GetAndamentoDeiMezziPropri', $calculation);

        return $AndamentoDeiMezziPropri;
    }

    public function getMargineStrutturaPrimario()
    {
        $TotaleImmobilizzazioni = $this->getDataFromBilancio('TotaleImmobilizzazioni');
        $TotalePatrimonioNetto = $this->getDataFromBilancio('TotalePatrimonioNetto');

        if ($TotaleImmobilizzazioni == 0) {
            $Margine_Struttura_Primario = number_format((float)($TotalePatrimonioNetto / 1) * 100, 2, ',', '.');
            $calculation = '('.(float)$TotalePatrimonioNetto.' / '. 1 .') * '. 100 .' = '. $Margine_Struttura_Primario;
        } else {
            $Margine_Struttura_Primario = number_format((float)($TotalePatrimonioNetto / $TotaleImmobilizzazioni) * 100, 2, ',', '.');
            $calculation = '('.(float)$TotalePatrimonioNetto.' / '. $TotaleImmobilizzazioni .') * '. 100 .' = '. $Margine_Struttura_Primario;
        }

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
