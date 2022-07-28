<?php

namespace App\Http\Controllers;

use App\Models\Analisi;
use Illuminate\Http\Request;

class SistemiAllertaController extends Controller
{

    public function indexBasic()
    {
        $analisis = Analisi::with(['bilanci','account','analisistype'])->paginate(25);

        return view('sistemi.basic.index', compact('analisis'));
    }

    public function indexAdvanced()
    {
        $analisis = Analisi::with(['bilanci','account','analisistype'])->paginate(25);

        return view('sistemi.advanced.index', compact('analisis'));
    }

    public function basic($id){

        $analisi = Analisi::with('bilanci')->with('account')->with('analisisType')->findOrFail($id);

        $bilancioJSON = json_decode($analisi->bilanci['json_data']);
        $bilancioJSONprev = json_decode($analisi->bilanci['json_data_prev']);
        $dataAnalisis = array();


        $TotaleAttivo = (isset($bilancioJSON->TotaleAttivo) ? $bilancioJSON->TotaleAttivo : 0);
        $CostiProduzioneAltriAccantonamenti = (isset($bilancioJSON->CostiProduzioneAltriAccantonamenti) ? $bilancioJSON->CostiProduzioneAltriAccantonamenti : 0);
        $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni = (isset($bilancioJSON->CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni) ? $bilancioJSON->CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni : 0);
        $TotaleCreditiVersoSociVersamentiAncoraDovuti = (isset($bilancioJSON->TotaleCreditiVersoSociVersamentiAncoraDovuti) ? $bilancioJSON->TotaleCreditiVersoSociVersamentiAncoraDovuti : 0);
        $TotalePatrimonioNetto = (isset($bilancioJSON->TotalePatrimonioNetto) ? $bilancioJSON->TotalePatrimonioNetto : 0);
        $PN_NEGATIVO = $TotalePatrimonioNetto-$TotaleCreditiVersoSociVersamentiAncoraDovuti;
        $dataAnalisis['PN_NEGATIVO'] = $PN_NEGATIVO;
        $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo : 0));
        $UtilePerditaEsercizio = (isset($bilancioJSON->UtilePerditaEsercizio) ? $bilancioJSON->UtilePerditaEsercizio : 0);

        // Valori bilancio
        // ### OF_RICAVI ###
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = (isset($bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari) ? $bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari : 0);
        $ValoreProduzioneRicaviVenditePrestazioni = (isset($bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni) ? $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni : 0);;
        $OF_RICAVI = number_format((float)($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari/$ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '.');
        $dataAnalisis['OF_RICAVI'] = $OF_RICAVI.'%';

        // ### ADEGUATEZZA_PATRIMONIALE ###
        $TotaleDebiti = (isset($bilancioJSON->TotaleDebiti) ? $bilancioJSON->TotaleDebiti : 0);
        $PassivoRateiRisconti = (isset($bilancioJSON->PassivoRateiRisconti) ? $bilancioJSON->PassivoRateiRisconti : 0);
        $ADEGUATEZZA_PATRIMONIALE = number_format((float)($PN_NEGATIVO/($TotaleDebiti+$PassivoRateiRisconti)) * 100, 2, ',', '.');
        $dataAnalisis['ADEGUATEZZA_PATRIMONIALE'] = $ADEGUATEZZA_PATRIMONIALE.'%';

        // ### RITORNO_LIQUIDO_ATTIVO ###
        $DebitiEsigibiliEntroEsercizioSuccessivo = isset($bilancioJSON->DebitiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiEsigibiliEntroEsercizioSuccessivo : 0;
        $TotaleDisponibilitaLiquide = (isset($bilancioJSON->TotaleDisponibilitaLiquide) ? $bilancioJSON->TotaleDisponibilitaLiquide : $val = (isset($bilancioJSONprev->TotaleDisponibilitaLiquide) ? $bilancioJSONprev->TotaleDisponibilitaLiquide : 0));
        $AttivoRateiRisconti = (isset($bilancioJSON->AttivoRateiRisconti) ? $bilancioJSON->AttivoRateiRisconti : 0);
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = (isset($bilancioJSON->TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni) ? $bilancioJSON->TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni : 0);
        $TotaleRimanenze = (isset($bilancioJSON->TotaleRimanenze) ? $bilancioJSON->TotaleRimanenze : 0);
        $TotaleCrediti = (isset($bilancioJSON->TotaleCrediti) ? $bilancioJSON->TotaleCrediti : 0);
        $formula = ($TotaleRimanenze+$TotaleCrediti+$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni+$TotaleDisponibilitaLiquide+$AttivoRateiRisconti)/($DebitiEsigibiliEntroEsercizioSuccessivo+$PassivoRateiRisconti);
        $RITORNO_LIQUIDO_ATTIVO = number_format((float)$formula * 100, 2, ',', '.');
        $dataAnalisis['RITORNO_LIQUIDO_ATTIVO'] = $RITORNO_LIQUIDO_ATTIVO.'%';


        // ### LIQUIDITA ###
        $CostiProduzioneAccantonamentiRischi = (isset($bilancioJSON->CostiProduzioneAccantonamentiRischi) ? $bilancioJSON->CostiProduzioneAccantonamentiRischi : 0);
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari;
        $ValoreProduzioneRicaviVenditePrestazioni = $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni;
        $LIQUIDITA = number_format((float)(($UtilePerditaEsercizio+$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni+ $CostiProduzioneAccantonamentiRischi + $CostiProduzioneAltriAccantonamenti)/$TotaleAttivo), 2, ',', '.') ;
        $dataAnalisis['LIQUIDITA'] = $LIQUIDITA.'%';

        // ### INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO ###
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari;
        $ValoreProduzioneRicaviVenditePrestazioni = $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni;
        $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO = number_format((float)$ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari/$ValoreProduzioneRicaviVenditePrestazioni, 2, ',', '.');
        $dataAnalisis['INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO'] = $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO;


        return view('sistemi.basic.basic', compact('dataAnalisis'));
    }

    public function advanced($id){

        $analisi = Analisi::with('bilanci')->with('account')->with('analisisType')->findOrFail($id);

        $bilancioJSON = json_decode($analisi->bilanci['json_data']);
        $bilancioJSONprev = json_decode($analisi->bilanci['json_data_prev']);
        $dataAnalisis = array();

        $TotaleAttivo = (isset($bilancioJSON->TotaleAttivo) ? $bilancioJSON->TotaleAttivo : 0);
        $CostiProduzioneAltriAccantonamenti = (isset($bilancioJSON->CostiProduzioneAltriAccantonamenti) ? $bilancioJSON->CostiProduzioneAltriAccantonamenti : 0);
        $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni = (isset($bilancioJSON->CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni) ? $bilancioJSON->CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni : 0);
        $TotaleCreditiVersoSociVersamentiAncoraDovuti = (isset($bilancioJSON->TotaleCreditiVersoSociVersamentiAncoraDovuti) ? $bilancioJSON->TotaleCreditiVersoSociVersamentiAncoraDovuti : 0);
        $TotalePatrimonioNetto = (isset($bilancioJSON->TotalePatrimonioNetto) ? $bilancioJSON->TotalePatrimonioNetto : 0);
        $PN_NEGATIVO = $TotalePatrimonioNetto-$TotaleCreditiVersoSociVersamentiAncoraDovuti;
        $dataAnalisis['PN_NEGATIVO'] = $PN_NEGATIVO;
        $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo : 0));
        $UtilePerditaEsercizio = (isset($bilancioJSON->UtilePerditaEsercizio) ? $bilancioJSON->UtilePerditaEsercizio : 0);

        // Valori bilancio
        // ### OF_RICAVI ###
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = (isset($bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari) ? $bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari : 0);
        $ValoreProduzioneRicaviVenditePrestazioni = (isset($bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni) ? $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni : 0);;
        $OF_RICAVI = number_format((float)($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari/$ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '.');

        // ### ADEGUATEZZA_PATRIMONIALE ###
        $TotaleDebiti = (isset($bilancioJSON->TotaleDebiti) ? $bilancioJSON->TotaleDebiti : 0);
        $PassivoRateiRisconti = (isset($bilancioJSON->PassivoRateiRisconti) ? $bilancioJSON->PassivoRateiRisconti : 0);
        $ADEGUATEZZA_PATRIMONIALE = number_format((float)($PN_NEGATIVO/($TotaleDebiti+$PassivoRateiRisconti)) * 100, 2, ',', '.');

        // ### RITORNO_LIQUIDO_ATTIVO ###
        $DebitiEsigibiliEntroEsercizioSuccessivo = isset($bilancioJSON->DebitiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiEsigibiliEntroEsercizioSuccessivo : 0;
        $TotaleDisponibilitaLiquide = (isset($bilancioJSON->TotaleDisponibilitaLiquide) ? $bilancioJSON->TotaleDisponibilitaLiquide : $val = (isset($bilancioJSONprev->TotaleDisponibilitaLiquide) ? $bilancioJSONprev->TotaleDisponibilitaLiquide : 0));
        $AttivoRateiRisconti = (isset($bilancioJSON->AttivoRateiRisconti) ? $bilancioJSON->AttivoRateiRisconti : 0);
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = (isset($bilancioJSON->TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni) ? $bilancioJSON->TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni : 0);
        $TotaleRimanenze = (isset($bilancioJSON->TotaleRimanenze) ? $bilancioJSON->TotaleRimanenze : 0);
        $TotaleCrediti = (isset($bilancioJSON->TotaleCrediti) ? $bilancioJSON->TotaleCrediti : 0);
        $formula = ($TotaleRimanenze+$TotaleCrediti+$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni+$TotaleDisponibilitaLiquide+$AttivoRateiRisconti)/($DebitiEsigibiliEntroEsercizioSuccessivo+$PassivoRateiRisconti);
        $RITORNO_LIQUIDO_ATTIVO = number_format((float)$formula * 100, 2, ',', '.');


        // ### LIQUIDITA ###
        $CostiProduzioneAccantonamentiRischi = (isset($bilancioJSON->CostiProduzioneAccantonamentiRischi) ? $bilancioJSON->CostiProduzioneAccantonamentiRischi : 0);
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari;
        $ValoreProduzioneRicaviVenditePrestazioni = $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni;
        $LIQUIDITA = number_format((float)(($UtilePerditaEsercizio+$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni+ $CostiProduzioneAccantonamentiRischi + $CostiProduzioneAltriAccantonamenti)/$TotaleAttivo), 2, ',', '.') ;

        // ### INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO ###
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari;
        $ValoreProduzioneRicaviVenditePrestazioni = $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni;
        $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO = number_format((float)$ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari/$ValoreProduzioneRicaviVenditePrestazioni, 2, ',', '.');

// INDICI ADVANCED

        //### Andamento del fatturato
        $ValoreProduzioneRicaviVenditePrestazioniCurr = (isset($bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni) ? $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni : 0);
        $ValoreProduzioneRicaviVenditePrestazioniPrev = (isset($bilancioJSONprev->ValoreProduzioneRicaviVenditePrestazioni) ? $bilancioJSONprev->ValoreProduzioneRicaviVenditePrestazioni : 0);
        $AndamentoDelFatturato = number_format((float)(-(1-(($ValoreProduzioneRicaviVenditePrestazioniCurr)/($ValoreProduzioneRicaviVenditePrestazioniPrev)))) * 100, 2, ',', '.');
        $dataAnalisis['Andamento_Del_Fatturato'] = $AndamentoDelFatturato.'%';
        //        dd($AndamentoDelFatturato);

        // ANDAMENTO DEL MOL
        $TotaleValoreProduzione = (isset($bilancioJSON->TotaleValoreProduzione) ? $bilancioJSON->TotaleValoreProduzione : 0);
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerci = (isset($bilancioJSON->CostiProduzioneMateriePrimeSussidiarieConsumoMerci) ? $bilancioJSON->CostiProduzioneMateriePrimeSussidiarieConsumoMerci : 0);
        $CostiProduzioneGodimentoBeniTerzi = (isset($bilancioJSON->CostiProduzioneGodimentoBeniTerzi) ? $bilancioJSON->CostiProduzioneGodimentoBeniTerzi : 0);
        $CostiProduzioneServizi = (isset($bilancioJSON->CostiProduzioneServizi) ? $bilancioJSON->CostiProduzioneServizi : 0);
        $CostiProduzionePersonaleTotaleCostiPersonale = (isset($bilancioJSON->CostiProduzionePersonaleTotaleCostiPersonale) ? $bilancioJSON->CostiProduzionePersonaleTotaleCostiPersonale : 0);
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci = (isset($bilancioJSON->CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci) ? $bilancioJSON->CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci : 0);
        $CostiProduzioneOneriDiversiGestione = (isset($bilancioJSON->CostiProduzioneOneriDiversiGestione) ? $bilancioJSON->CostiProduzioneOneriDiversiGestione : 0);

        $TotaleValoreProduzionePrecedente = (isset($bilancioJSONprev->TotaleValoreProduzione) ? $bilancioJSONprev->TotaleValoreProduzione : 0);
        $CostiProduzioneMateriePrimeSussidiarieConsumoMerciPrecedente = (isset($bilancioJSONprev->CostiProduzioneMateriePrimeSussidiarieConsumoMerci) ? $bilancioJSONprev->CostiProduzioneMateriePrimeSussidiarieConsumoMerci : 0);
        $CostiProduzioneGodimentoBeniTerziPrecedente = (isset($bilancioJSONprev->CostiProduzioneGodimentoBeniTerzi) ? $bilancioJSONprev->CostiProduzioneGodimentoBeniTerzi : 0);
        $CostiProduzioneServiziPrecedente = (isset($bilancioJSONprev->CostiProduzioneServizi) ? $bilancioJSONprev->CostiProduzioneServizi : 0);
        $CostiProduzionePersonaleTotaleCostiPersonalePrecedente = (isset($bilancioJSONprev->CostiProduzionePersonaleTotaleCostiPersonale) ? $bilancioJSONprev->CostiProduzionePersonaleTotaleCostiPersonale : 0);
        $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerciPrecedente = (isset($bilancioJSONprev->CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci) ? $bilancioJSONprev->CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci : 0);
        $CostiProduzioneOneriDiversiGestionePrecedente = (isset($bilancioJSONprev->CostiProduzioneOneriDiversiGestione) ? $bilancioJSONprev->CostiProduzioneOneriDiversiGestione : 0);


        $MOLcurr = $TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzioneServizi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione;
        $MOLprev = $TotaleValoreProduzionePrecedente - $CostiProduzioneMateriePrimeSussidiarieConsumoMerciPrecedente - $CostiProduzioneGodimentoBeniTerziPrecedente - $CostiProduzioneServiziPrecedente - $CostiProduzionePersonaleTotaleCostiPersonalePrecedente - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerciPrecedente - $CostiProduzioneOneriDiversiGestionePrecedente;

        $AndamentoMOL = -(1-($MOLcurr/$MOLprev));
        $dataAnalisis['ANDAMENTO_DEL_MOL'] = $AndamentoMOL;

        // ### ROI
        $DifferenzaValoreCostiProduzione = (isset($bilancioJSON->DifferenzaValoreCostiProduzione) ? $bilancioJSON->DifferenzaValoreCostiProduzione : 0);
        $ROI = number_format((float)($DifferenzaValoreCostiProduzione/$TotaleAttivo), 2, ',', '.');
        $dataAnalisis['ROI'] = $ROI.'%';

        // ### ROS
        $ROS = number_format((float)($DifferenzaValoreCostiProduzione/$ValoreProduzioneRicaviVenditePrestazioni), 2, ',', '.');
        $dataAnalisis['ROS'] = $ROS.'%';

        //### ROE
//        dd($UtilePerditaEsercizio);
        $ROE = number_format((float)($UtilePerditaEsercizio/$TotalePatrimonioNetto), 2, ',', '.');
        $dataAnalisis['ROE'] = $ROE.'%';

        //### EBITDA/Fatturato
        $EBITDA_FATTURATO = number_format((float)(($TotaleValoreProduzione-$CostiProduzioneMateriePrimeSussidiarieConsumoMerci-$CostiProduzioneServizi-$CostiProduzioneGodimentoBeniTerzi-$CostiProduzionePersonaleTotaleCostiPersonale-$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci-$CostiProduzioneOneriDiversiGestione)/$ValoreProduzioneRicaviVenditePrestazioni), 2, ',', '.') ;
        $dataAnalisis['EBITDA_FATTURATO'] = $EBITDA_FATTURATO.'%';

        //### Andamento dei mezzi propri

        $TotalePatrimonioNettoCurr = (isset($bilancioJSON->TotalePatrimonioNetto) ? $bilancioJSON->TotalePatrimonioNetto : 0);
        $TotalePatrimonioNettoPrev = (isset($bilancioJSONprev->TotalePatrimonioNetto) ? $bilancioJSONprev->TotalePatrimonioNetto : 0);
        $AndamentoDeiMezziPropri = number_format((float)(($TotalePatrimonioNettoCurr/$TotalePatrimonioNettoPrev)-1), 2, ',', '.') ;
        $dataAnalisis['Andamento_Dei_Mezzi_Propri'] = $AndamentoDeiMezziPropri.'%';
//        dd($AndamentoDeiMezziPropri);

        //### Margine Struttura Primario
        $TotaleImmobilizzazioni = (isset($bilancioJSON->TotaleImmobilizzazioni) ? $bilancioJSON->TotaleImmobilizzazioni : 0);
        $Margine_Struttura_Primario = number_format((float)($TotalePatrimonioNetto/$TotaleImmobilizzazioni), 2, ',', '.') ;
        $dataAnalisis['Margine_Struttura_Primario'] = $Margine_Struttura_Primario.'%';

        //### Margine Struttura Secondario
        $TrattamentoFineRapportoLavoroSubordinato = (isset($bilancioJSON->TrattamentoFineRapportoLavoroSubordinato) ? $bilancioJSON->TrattamentoFineRapportoLavoroSubordinato : 0);
        $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSONprev->DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo : 0));
        $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiAccontiEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiAccontiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiAccontiEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo : 0);
        $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSONprev->DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo : 0));
        $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSONprev->DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo : 0));
        $DebitiEsigibiliOltreEsercizioSuccessivo = isset($bilancioJSON->DebitiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiEsigibiliOltreEsercizioSuccessivo : 0;


        $QuarantaTre = $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo+$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo+$DebitiAccontiEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo+$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo; //(isset($bilancioJSON->DebitiOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiOltreEsercizioSuccessivo : 0);

        $Margine_Struttura_Secondario_Semplificato = number_format((float)(($TotalePatrimonioNetto+$TrattamentoFineRapportoLavoroSubordinato+$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo+$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo+$DebitiAccontiEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo+$DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo)/$TotaleImmobilizzazioni), 2, ',', '.') ;
        $Margine_Struttura_Secondario_Ordinario = number_format((float)(($TotalePatrimonioNetto+$TrattamentoFineRapportoLavoroSubordinato+$QuarantaTre)/$TotaleImmobilizzazioni), 2, ',', '.') ;
        $dataAnalisis['Margine_Struttura_Secondario_Semplificato'] = $Margine_Struttura_Secondario_Semplificato.'%';
        $dataAnalisis['Margine_Struttura_Secondario_Ordinario'] = $Margine_Struttura_Secondario_Ordinario.'%';
        // CURRENT RADIO (VEDI INDICE RITORNO LIQUIDO ATT)


        //### Attivita a breve / Passività a Breve
//        dd($bilancioJSON, $bilancioJSONprev);

        // TRENTACINQUE
        $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiVersoControllantiEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoControllantiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoControllantiEsigibiliOltreEsercizioSuccessivo : 0);
        $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo : 0));
        $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo : 0));
        $TrentaCinque = $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo+$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo+$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo+$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo+$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo;
        $CreditiCreditiTributariTotaleCreditiTributari = (isset($bilancioJSON->CreditiCreditiTributariTotaleCreditiTributari) ? $bilancioJSON->CreditiCreditiTributariTotaleCreditiTributari : $val = (isset($bilancioJSONprev->CreditiCreditiTributariTotaleCreditiTributari) ? $bilancioJSONprev->CreditiCreditiTributariTotaleCreditiTributari : 0));

        // QUARANTANOVE
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo : 0));
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiAccontiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiAccontiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiAccontiEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo : 0));
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo : 0));
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo : 0));
        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo : 0);
        $QuarantaNove = $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo+$DebitiAccontiEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo+$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo+$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo;
        $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo : 0);


        $Attivita_a_breve_Passivita_a_Breve_Semplificato = number_format((float)((($TotaleDisponibilitaLiquide+$TrentaCinque+$TotaleRimanenze+$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni+$AttivoRateiRisconti)/($QuarantaNove+$PassivoRateiRisconti))), 2, ',', '.') ;
        $Attivita_a_breve_Passivita_a_Breve_Ordinario = number_format((float)((($TotaleDisponibilitaLiquide+$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo+$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo+$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo+$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo+$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo+$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo+$TotaleRimanenze+$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni+$AttivoRateiRisconti)/($DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo+$DebitiAccontiEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo+$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo+$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo+$PassivoRateiRisconti))), 2, ',', '.') ;
        $dataAnalisis['Attivita_a_breve_Passività_a_Breve_Semplificato'] = $Attivita_a_breve_Passivita_a_Breve_Semplificato.'%';
        $dataAnalisis['Attivita_a_breve_Passività_a_Breve_Ordinario'] = $Attivita_a_breve_Passivita_a_Breve_Ordinario.'%';

        // ACID TEST

        $AcidTest = number_format((float)(($TotaleCrediti + $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + $TotaleDisponibilitaLiquide + $AttivoRateiRisconti)/($DebitiEsigibiliEntroEsercizioSuccessivo+$PassivoRateiRisconti)), 2, ',', '.') ;
        $ACID_TEST_Semplificato = number_format((float)((($TotaleDisponibilitaLiquide+$TrentaCinque+$TotaleRimanenze+$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni+$AttivoRateiRisconti-$TotaleRimanenze)/($QuarantaNove+$PassivoRateiRisconti))), 2, ',', '.') ;
        $ACID_TEST_Ordinario = number_format((float)((($TotaleDisponibilitaLiquide+$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo+$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo+$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo+$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo+$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo+$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo+$TotaleRimanenze+$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni+$AttivoRateiRisconti-$TotaleRimanenze)/($DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo+$DebitiAccontiEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo+$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo+$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo+$PassivoRateiRisconti))), 2, ',', '.') ;
        $dataAnalisis['ACID_TEST_Semplificato'] = $ACID_TEST_Semplificato.'%';
        $dataAnalisis['ACID_TEST_Ordinario'] = $ACID_TEST_Ordinario.'%';
        $dataAnalisis['AcidTest'] = $AcidTest.'%';



        // AUTONOMIA FINANZIARIA
        $AUTONOMIA_FINANZIARIA = number_format((float)(($TotalePatrimonioNetto/($TotalePatrimonioNetto+$TotaleDebiti))), 2, ',', '.') ;
        $dataAnalisis['AUTONOMIA_FINANZIARIA'] = $AUTONOMIA_FINANZIARIA.'%';

        // LIVELLO INVESTIMENTI AZIENDALI
        $LIVELLO_INVESTIMENTI_AZIENDALI = number_format((float)($TotalePatrimonioNetto/$TotaleAttivo), 2, ',', '.') ;
        $dataAnalisis['LIVELLO_INVESTIMENTI_AZIENDALI'] = $LIVELLO_INVESTIMENTI_AZIENDALI.'%';

        // PFN / EBITDA
        $ImmobilizzazioniFinanziarieCreditiTotaleCrediti = (isset($bilancioJSON->ImmobilizzazioniFinanziarieCreditiTotaleCrediti) ? $bilancioJSON->ImmobilizzazioniFinanziarieCreditiTotaleCrediti : 0);
        $PFN_EBITDA = number_format((float)(($DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo+$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo+$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo+$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo+$TotaleDisponibilitaLiquide+$ImmobilizzazioniFinanziarieCreditiTotaleCrediti)/($TotaleValoreProduzione+$CostiProduzioneMateriePrimeSussidiarieConsumoMerci+$CostiProduzioneServizi+$CostiProduzioneGodimentoBeniTerzi+$CostiProduzionePersonaleTotaleCostiPersonale+$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci+$CostiProduzioneOneriDiversiGestione)), 2, ',', '.') ;
        $dataAnalisis['PFN_EBITDA'] = $PFN_EBITDA.'%';

        // Peso Oneri Finanziari (OF/Fatturato)
        $Peso_Oneri_Finanziari = number_format((float)($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari/$ValoreProduzioneRicaviVenditePrestazioni),  2, ',', '.');
        $dataAnalisis['Peso_Oneri_Finanziari'] = $Peso_Oneri_Finanziari.'%';

        // Copertura Lorda degli Oneri Finanziari
        $Copertura_Lorda_degli_Oneri_Finanziari = number_format((float)(($TotaleValoreProduzione-$CostiProduzioneMateriePrimeSussidiarieConsumoMerci-$CostiProduzioneServizi-$CostiProduzioneGodimentoBeniTerzi-$CostiProduzionePersonaleTotaleCostiPersonale-$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci-$CostiProduzioneOneriDiversiGestione)/$ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari), 2, ',', '.') ;
        $dataAnalisis['Copertura_Lorda_degli_Oneri_Finanziari'] = $Copertura_Lorda_degli_Oneri_Finanziari.'%';

        // EBIT / OF
//        dd($CostiProduzioneAccantonamentiRischi);
        $EBIT_OF = number_format((float)(($TotaleValoreProduzione-$CostiProduzioneMateriePrimeSussidiarieConsumoMerci-$CostiProduzioneServizi-$CostiProduzioneGodimentoBeniTerzi-$CostiProduzionePersonaleTotaleCostiPersonale-$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci-$CostiProduzioneOneriDiversiGestione-$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni-$CostiProduzioneAccantonamentiRischi-$CostiProduzioneAltriAccantonamenti)/$ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari), 2, ',', '.') ;
        $dataAnalisis['EBIT_OF'] = $EBIT_OF.'%';

        //Costo del personale
        $Costo_del_personale = number_format((float)($CostiProduzionePersonaleTotaleCostiPersonale/$ValoreProduzioneRicaviVenditePrestazioni), 2, ',', '.') ;
        $dataAnalisis['Costo_del_personale'] = $Costo_del_personale.'%';

        // CF / Attivo
        $CreditiImposteAnticipateTotaleImposteAnticipate = (isset($bilancioJSON->CreditiImposteAnticipateTotaleImposteAnticipate) ? $bilancioJSON->CreditiImposteAnticipateTotaleImposteAnticipate : 0);
        $CF_ATTIVO = number_format((float)(($UtilePerditaEsercizio+$CostiProduzioneAccantonamentiRischi+$CostiProduzioneAltriAccantonamenti+$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni+$CreditiImposteAnticipateTotaleImposteAnticipate)/$TotaleAttivo), 2, ',', '.') ;
        $dataAnalisis['CF_ATTIVO'] = $CF_ATTIVO.'%';

        //Indice di Indebitamento (PFN/PN)
        $Indice_di_Indebitamento = number_format((float)(($DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo+$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo+$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo+$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo+$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo+$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo-$TotaleDisponibilitaLiquide-$ImmobilizzazioniFinanziarieCreditiTotaleCrediti)/$TotalePatrimonioNetto), 2, ',', '.') ;
        $dataAnalisis['Indice_di_Indebitamento'] = $Indice_di_Indebitamento.'%';

//        dd($bilancioJSON, $bilancioJSONprev);

        $indiciBilancio = array();

        //SALDO DEBITI VS FISCO

        $DebitiDebitiTributariTotaleDebitiTributariCorrente = (isset($bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari) ? $bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari : 0);
        $FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente = (isset($bilancioJSON->FondiRischiOneriTrattamentoQuiescenzaObblighiSimili) ? $bilancioJSON->FondiRischiOneriTrattamentoQuiescenzaObblighiSimili : 0);
        $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipatePrecedente = isset($bilancioJSONprev->ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate) ? $bilancioJSONprev->ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate : 0;
        $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate= isset($bilancioJSON->ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate) ? $bilancioJSON->ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate : 0;

        $DifferenzaImposteReddito = ($ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate + $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipatePrecedente)/2;
        $SaldoDebitiVSFisco = ($FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente + $DebitiDebitiTributariTotaleDebitiTributariCorrente)/$DifferenzaImposteReddito;
        $dataAnalisis['SALDO_DEBITI_VS_FISCO'] = $SaldoDebitiVSFisco;


        return view('sistemi.advanced.advanced', compact('dataAnalisis'));
    }

}
