<?php

namespace App\Helpers\Bilanci;

use stdClass;

class BilanciCalculationsHelper
{

    public $_bilancioJSON = [];

    public function setBilancioData($bilancioJSON)
    {
        $this->_bilancioJSON = $bilancioJSON;
        $this->sanitizeData();
    }


    public function sanitizeData()
    {
        $cleanData = new stdClass;

        foreach ($this->_bilancioJSON as $singleKey => $singleValue) {
            $cleanData->$singleKey = (float)$singleValue;
        }

        $this->_bilancioJSON = $cleanData;
    }

    public function getDataFromBilancio($dataExtendedName)
    {

        $data = $this->_bilancioJSON->$dataExtendedName;
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

        $ADEGUATEZZA_PATRIMONIALE = number_format((float)($PN_NEGATIVO / ($TotaleDebiti + (float)$PassivoRateiRisconti)) * 100, 2, ',', '');

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

        if ($TotaleAttivo == 0) {
            $LIQUIDITA = 0;
            $dataAnalisis['LIQUIDITA'] = $LIQUIDITA . '%';
        } else {
            $LIQUIDITA = number_format((($UtilePerditaEsercizio + $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni + $CostiProduzioneAccantonamentiRischi + $CostiProduzioneAltriAccantonamenti) / $TotaleAttivo) * 100, 2, ',', '');
            $dataAnalisis['LIQUIDITA'] = $LIQUIDITA . '%';
        }
    }
}
