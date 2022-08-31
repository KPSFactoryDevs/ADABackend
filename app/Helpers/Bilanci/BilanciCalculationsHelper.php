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
        $data = $this->_bilancioJSON->$dataExtendedName;
        if (!$data) {
            $data = 0;
        }

        return (float)$data;
    }

    public function getDataFromBilancioPrev($dataExtendedName)
    {
        $data = $this->_bilancioJSONPrev->$dataExtendedName;
        if (!$data) {
            $data = 0;
        }

        return (float)$data;
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
            $AndamentoDelFatturato = number_format((float)(- (1 - (($ValoreProduzioneRicaviVenditePrestazioniCurr) / (1)))) * 100, 2, ',', '');
            $dataAnalisis['Andamento_del_fatturato'] = $AndamentoDelFatturato . '%';
        } else {
            $AndamentoDelFatturato = number_format((float)(- (1 - (($ValoreProduzioneRicaviVenditePrestazioniCurr) / ($ValoreProduzioneRicaviVenditePrestazioniPrev)))) * 100, 2, ',', '');
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
}
