<?php

namespace App\Helpers\Bilanci;

use stdClass;

class BilanciCalculationsHelper
{

    public $_bilancioJSON = [];
    public $_bilancioJSONPrev = [];

    public function setBilancioData($bilancioJSON)
    {
        $this->_bilancioJSON = $bilancioJSON;
        $this->sanitizeData();
    }

    public function setBilancioDataPrev($bilancioJSONPrev)
    {
        $this->_bilancioJSONPrev = $bilancioJSONPrev;
        $this->sanitizeDataPrev();
    }


    public function sanitizeData()
    {
        $cleanData = new stdClass;

        foreach ($this->_bilancioJSON as $singleKey => $singleValue) {
            $cleanData->$singleKey = (float)$singleValue;
        }

        $this->_bilancioJSON = $cleanData;
    }

    public function sanitizeDataPrev()
    {
        $cleanData = new stdClass;

        foreach ($this->_bilancioJSONPrev as $singleKey => $singleValue) {
            $cleanData->$singleKey = (float)$singleValue;
        }

        $this->_bilancioJSONPrev = $cleanData;
    }

    public function getDataFromBilancio($dataExtendedName)
    {
        return (float)(property_exists($this->_bilancioJSON, $dataExtendedName) ? $this->_bilancioJSON->$dataExtendedName : 0);
    }

    public function getDataFromBilancioPrev($dataExtendedName)
    {
        return (float)(property_exists($this->_bilancioJSONPrev, $dataExtendedName) ? $this->_bilancioJSONPrev->$dataExtendedName : 0);
    }

    public function getTotalePatrimonioNetto()
    {
        $TotalePatrimonioNetto = $this->getDataFromBilancio('TotalePatrimonioNetto');
        return  $TotalePatrimonioNetto;
    }

    public function getPatrimonioNettoNegativo()
    {
        $TotalePatrimonioNetto = $this->getTotalePatrimonioNetto();
        $TotaleCreditiVersoSociVersamentiAncoraDovuti = $this->getDataFromBilancio('TotaleCreditiVersoSociVersamentiAncoraDovuti');

        return  $PN_NEGATIVO = $TotalePatrimonioNetto - $TotaleCreditiVersoSociVersamentiAncoraDovuti;
    }

    public function getOfRicavi()
    {
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari =  $this->getDataFromBilancio('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari');
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getDataFromBilancio('ValoreProduzioneRicaviVenditePrestazioni');
        if ($ValoreProduzioneRicaviVenditePrestazioni != 0) {
            $OF_RICAVI = number_format(($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari / $ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, '.', '');
        } else {
            $OF_RICAVI = "NON CALCOLABILE";
        }

        return $OF_RICAVI . '%';
    }

    public function getTotaleDebiti()
    {
        $TotaleDebiti = $this->getDataFromBilancio('TotaleDebiti');

        return $TotaleDebiti;
    }

    public function getAdeguatezzaPatrimoniale()
    {
        $PN_NEGATIVO = $this->getPatrimonioNettoNegativo();
        $TotaleDebiti = $this->getTotaleDebiti();
        $PassivoRateiRisconti = $this->getDataFromBilancio('PassivoRateiRisconti');

        $ADEGUATEZZA_PATRIMONIALE = number_format(($PN_NEGATIVO / ($TotaleDebiti + $PassivoRateiRisconti)) * 100, 2, ',', '');

        return $ADEGUATEZZA_PATRIMONIALE . '%';
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

        return (float)$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + (float)$CreditiImposteAnticipateTotaleImposteAnticipate + (float)$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo;
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

        return (float)$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAccontiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo;
    }


    public function getRitornoLiquidoAttivo()
    {
        $DebitiEsigibiliEntroEsercizioSuccessivo = $this->getDataFromBilancio('DebitiEsigibiliEntroEsercizioSuccessivo');
        $TotaleDisponibilitaLiquide = $this->getDataFromBilancio('TotaleDisponibilitaLiquide');
        $AttivoRateiRisconti = $this->getDataFromBilancio('AttivoRateiRisconti');
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $this->getDataFromBilancio('TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni');
        $TotaleRimanenze = $this->getDataFromBilancio('TotaleRimanenze');
        $TotaleCrediti = $this->getDataFromBilancio('TotaleCrediti');

        $PassivoRateiRisconti = $this->getDataFromBilancio('PassivoRateiRisconti');

        $data = [
            'DebitiEsigibiliEntroEsercizioSuccessivo' => $DebitiEsigibiliEntroEsercizioSuccessivo,
            'TotaleDisponibilitaLiquide' => $TotaleDisponibilitaLiquide,
            'AttivoRateiRisconti' => $AttivoRateiRisconti,
            'TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni' => $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni,
            'TotaleRimanenze' => $TotaleRimanenze,
            'TotaleCrediti' => $TotaleCrediti,
            'PassivoRateiRisconti' => $PassivoRateiRisconti,
        ];

        return $data;
    }

    public function getLiquidita()
    {
        $CostiProduzioneAccantonamentiRischi = $this->getDataFromBilancio('CostiProduzioneAccantonamentiRischi');
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $this->getDataFromBilancio('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari');
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getDataFromBilancio('ValoreProduzioneRicaviVenditePrestazioni');
        $TotaleAttivo = $this->getDataFromBilancio('TotaleAttivo');
        $UtilePerditaEsercizio = $this->getDataFromBilancio('UtilePerditaEsercizio');
        $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni = $this->getDataFromBilancio('CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni');
        $CostiProduzioneAltriAccantonamenti = $this->getDataFromBilancio('CostiProduzioneAltriAccantonamenti');


        if ($TotaleAttivo == 0) {
            $LIQUIDITA = 0;
            $dataAnalisis['LIQUIDITA'] = $LIQUIDITA . '%';
        } else {
            $LIQUIDITA = number_format((($UtilePerditaEsercizio + $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni + (float)$CostiProduzioneAccantonamentiRischi + (float)$CostiProduzioneAltriAccantonamenti) / (float)$TotaleAttivo) * 100, 2, ',', '');
            $dataAnalisis['LIQUIDITA'] = $LIQUIDITA . '%';
        }

        $data = [
            'CostiProduzioneAccantonamentiRischi' => $CostiProduzioneAccantonamentiRischi,
            'ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari' => $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari,
            'ValoreProduzioneRicaviVenditePrestazioni' => $ValoreProduzioneRicaviVenditePrestazioni,
            'LIQUIDITA' => $LIQUIDITA . '%',
        ];

        return $data;
    }

    public function getIndebitamentoPrevidenzialeTributario()
    {
        $DebitiDebitiTributariTotaleDebitiTributariCorrente = $this->getDataFromBilancio('DebitiDebitiTributariTotaleDebitiTributari');
        $DebitiDebitiTributariTotaleDebitiTributari = $this->getDataFromBilancio('DebitiDebitiTributariTotaleDebitiTributari');
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale = $this->getDataFromBilancio('DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale');
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $this->getDataFromBilancio('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari');
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getDataFromBilancio('ValoreProduzioneRicaviVenditePrestazioni');
        $TotaleAttivo = $this->getDataFromBilancio('TotaleAttivo');

        if ($TotaleAttivo == 0) {
            $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO = number_format((($DebitiDebitiTributariTotaleDebitiTributari + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) / 1) * 100, 2, ',', '');
            $dataAnalisis['INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO'] = $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO . '%';
        } else {
            $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO = number_format((($DebitiDebitiTributariTotaleDebitiTributari + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) / $TotaleAttivo) * 100, 2, ',', '');
            $dataAnalisis['INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO'] = $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO . '%';
        }

        $data = [
            'DebitiDebitiTributariTotaleDebitiTributariCorrente' => $DebitiDebitiTributariTotaleDebitiTributariCorrente,
            'DebitiDebitiTributariTotaleDebitiTributari' => $DebitiDebitiTributariTotaleDebitiTributari,
            'DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale' => $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale,
            'ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari' => $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari,
            'ValoreProduzioneRicaviVenditePrestazioni' => $ValoreProduzioneRicaviVenditePrestazioni,
            'INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO' => $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO . '%'
        ];

        return $data;
    }

    public function getAndamentoDelFatturato()
    {
        $ValoreProduzioneRicaviVenditePrestazioniCurr = $this->getDataFromBilancio('ValoreProduzioneRicaviVenditePrestazioni');
        $ValoreProduzioneRicaviVenditePrestazioniPrev = $this->getDataFromBilancioPrev('ValoreProduzioneRicaviVenditePrestazioni');
        if ($ValoreProduzioneRicaviVenditePrestazioniPrev == 0) {
            $AndamentoDelFatturato = number_format((- (1 - (($ValoreProduzioneRicaviVenditePrestazioniCurr) / (1)))) * 100, 2, ',', '');
            $dataAnalisis['Andamento_del_fatturato'] = $AndamentoDelFatturato . '%';
        } else {
            $AndamentoDelFatturato = number_format((- (1 - (($ValoreProduzioneRicaviVenditePrestazioniCurr) / ($ValoreProduzioneRicaviVenditePrestazioniPrev)))) * 100, 2, ',', '');
            $dataAnalisis['Andamento_del_fatturato'] = $AndamentoDelFatturato . '%';
        }

        return $AndamentoDelFatturato . '%';
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
            $AndamentoMOL = number_format(- (1 - ($MOLcurr / 1)) * 100, 2, ',', '');
            $dataAnalisis['Andamento_del_MOL'] = $AndamentoMOL . '%';
        } else {
            $AndamentoMOL = number_format(- (1 - ($MOLcurr / $MOLprev)) * 100, 2, ',', '');
            $dataAnalisis['Andamento_del_MOL'] = $AndamentoMOL . '%';
        }

        $data = [
            'TotaleValoreProduzione' => $TotaleValoreProduzione,
            'CostiProduzioneMateriePrimeSussidiarieConsumoMerci' => $CostiProduzioneMateriePrimeSussidiarieConsumoMerci,
            'CostiProduzioneGodimentoBeniTerzi' => $CostiProduzioneGodimentoBeniTerzi,
            'CostiProduzioneServizi' => $CostiProduzioneServizi,
            'CostiProduzionePersonaleTotaleCostiPersonale' => $CostiProduzionePersonaleTotaleCostiPersonale,
            'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci' => $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci,
            'CostiProduzioneOneriDiversiGestione' => $CostiProduzioneOneriDiversiGestione,
            'MOLcurr' => $MOLcurr,
            'AndamentoMOL' => $AndamentoMOL . '%',
        ];

        return $data;
    }

    public function getROI()
    {
        $DifferenzaValoreCostiProduzione = $this->getDataFromBilancio('DifferenzaValoreCostiProduzione');
        $TotaleAttivo = $this->getDataFromBilancio('TotaleAttivo');

        if ($TotaleAttivo == 0) {
            $ROI = number_format(($DifferenzaValoreCostiProduzione / 1) * 100, 2, ',', '');
            $dataAnalisis['ROI'] = $ROI . '%';
        } else {
            $ROI = number_format(($DifferenzaValoreCostiProduzione / $TotaleAttivo) * 100, 2, ',', '');
            $dataAnalisis['ROI'] = $ROI . '%';
        }

        $data = [
            'DifferenzaValoreCostiProduzione' => $DifferenzaValoreCostiProduzione,
            'ROI' => $ROI . '%'
        ];

        return $data;
    }

    public function getROS()
    {
        $getROI = $this->getROI();
        $DifferenzaValoreCostiProduzione = $getROI['DifferenzaValoreCostiProduzione'];
        $ValoreProduzioneRicaviVenditePrestazioni = $this->getDataFromBilancio('ValoreProduzioneRicaviVenditePrestazioni');

        if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
            $ROS = number_format(($DifferenzaValoreCostiProduzione / 1) * 100, 2, ',', '');
            $dataAnalisis['ROS'] = $ROS . '%';
        } else {
            $ROS = number_format(($DifferenzaValoreCostiProduzione / $ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '');
            $dataAnalisis['ROS'] = $ROS . '%';
        }

        return $ROS . '%';
    }

    public function getROE()
    {
        $TotalePatrimonioNetto = $this->getDataFromBilancio('TotalePatrimonioNetto');
        $UtilePerditaEsercizio = $this->getDataFromBilancio('UtilePerditaEsercizio');

        if ($TotalePatrimonioNetto == 0) {
            $ROE = number_format(($UtilePerditaEsercizio / 1) * 100, 2, ',', '');
            $dataAnalisis['ROE'] = $ROE . '%';
        } else {
            $ROE = number_format(($UtilePerditaEsercizio / $TotalePatrimonioNetto) * 100, 2, ',', '');
            $dataAnalisis['ROE'] = $ROE . '%';
        }

        return $ROE . '%';
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
            $EBITDA_FATTURATO = number_format((($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / 1) * 100, 2, ',', '');
            $dataAnalisis['EBITDA_Fatturato'] = $EBITDA_FATTURATO . '%';
        } else {
            $EBITDA_FATTURATO = number_format((($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / $ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '');
            $dataAnalisis['EBITDA_Fatturato'] = $EBITDA_FATTURATO . '%';
        }

        return $EBITDA_FATTURATO . '%';
    }

    public function getAndamentoDeiMezziPropri()
    {
        $TotalePatrimonioNettoCurr = $this->getDataFromBilancio('TotalePatrimonioNetto');
        $TotalePatrimonioNettoPrev = $this->getDataFromBilancioPrev('TotalePatrimonioNetto');

        if ($TotalePatrimonioNettoPrev == 0) {
            $AndamentoDeiMezziPropri = number_format((($TotalePatrimonioNettoCurr / 1) - 1) * 100, 2, ',', '');
            $dataAnalisis['Andamento_dei_mezzi_propri'] = $AndamentoDeiMezziPropri . '%';
        } else {
            $AndamentoDeiMezziPropri = number_format((($TotalePatrimonioNettoCurr / $TotalePatrimonioNettoPrev) - 1) * 100, 2, ',', '');
            $dataAnalisis['Andamento_dei_mezzi_propri'] = $AndamentoDeiMezziPropri . '%';
        }

        return $AndamentoDeiMezziPropri . '%';
    }

    public function getMargineStrutturaPrimario()
    {
        $TotaleImmobilizzazioni = $this->getDataFromBilancio('TotaleImmobilizzazioni');
        $TotalePatrimonioNetto = $this->getDataFromBilancio('TotalePatrimonioNetto');

        if ($TotaleImmobilizzazioni == 0) {
            $Margine_Struttura_Primario = number_format((float)($TotalePatrimonioNetto / 1) * 100, 2, ',', '');
            $dataAnalisis['Margine_Struttura_Primario'] = $Margine_Struttura_Primario;
        } else {
            $Margine_Struttura_Primario = number_format((float)($TotalePatrimonioNetto / $TotaleImmobilizzazioni) * 100, 2, ',', '');
            $dataAnalisis['Margine_Struttura_Primario'] = $Margine_Struttura_Primario;
        }

        $data = [
            'TotaleImmobilizzazioni' => $TotaleImmobilizzazioni,
            'Margine_Struttura_Primario' => $Margine_Struttura_Primario
        ];

        return $data;
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
            $Margine_Struttura_Secondario_Semplificato = number_format((($TotalePatrimonioNetto + $TrattamentoFineRapportoLavoroSubordinato + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo + $DebitiAccontiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) / 1) * 100, 2, ',', '');
            $dataAnalisis['Margine_Struttura_Secondario'] = $Margine_Struttura_Secondario_Semplificato;
        } else {
            $Margine_Struttura_Secondario_Semplificato = number_format((($TotalePatrimonioNetto + $TrattamentoFineRapportoLavoroSubordinato + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo + $DebitiAccontiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) / $TotaleImmobilizzazioni) * 100, 2, ',', '');
            $dataAnalisis['Margine_Struttura_Secondario'] = $Margine_Struttura_Secondario_Semplificato;
        }

        $data = [
            'TrattamentoFineRapportoLavoroSubordinato' => $TrattamentoFineRapportoLavoroSubordinato,
            'DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo' => $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo,
            'DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo' => $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo,
            'DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo' => $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo,
            'DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo' => $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo,
            'DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo' => $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo,
            'DebitiAccontiEsigibiliOltreEsercizioSuccessivo' => $DebitiAccontiEsigibiliOltreEsercizioSuccessivo,
            'DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo' => $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo,
            'DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo' => $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo,
            'DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo' => $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo,
            'DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo' => $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo,
            'DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo' => $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo,
            'DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo' => $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo,
            'DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo' => $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo,
            'DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo' => $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo,
            'Margine_Struttura_Secondario_Semplificato' => $Margine_Struttura_Secondario_Semplificato
        ];

        return $data;
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

        $RITORNO_LIQUIDO_ATTIVO = number_format((float)$formula * 100, 2, ',', '');

        return $RITORNO_LIQUIDO_ATTIVO . '%';
    }

}
