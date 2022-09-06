<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Bilanci;
use App\Models\cr;
use App\Helpers\CentraleRischi\CrExtractorHelper;
use App\Helpers\CentraleRischi\newCrExtractor;
use Illuminate\Support\Facades\DB;
use App\Helpers\Bilanci\BilanciHelper;
use App\Helpers\Allerta\AllertaHelper;
use DateTime;
use PDF;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Response;
use App\Helpers\printpdf;
use HTTP_Request2;

class PDFController extends Controller
{   
    public function reportBasicPdf($idBilancio)
	{
        $bilancio = Bilanci::findOrFail($idBilancio);

        $tipoAzienda = $bilancio->tipo_azienda;

        $attributes = $bilancio->getAttributes();
        $jsonData['current'] = json_decode($attributes['json_data'], true);
        $jsonData['prev'] = json_decode($attributes['json_data_prev'], true);
        $jsonData['anagrafic'] = json_decode($attributes['json_data_anag'], true);
        $jsonData['currentYear'] = $attributes['current_year'];
        $jsonData['prevYear'] = $attributes['prev_year'];
        $tipoAzienda = $attributes['tipo_azienda'];

        if (DB::table('basic')->where('bilancio_id', '=', $idBilancio)->count() == 0) {
            return response()->json([
				'error' => false,
				'Message' => 'Non è stata salvata alcuna analisi per questo bilancio'
			]);
        }   

        $voci = DB::table('vocis')->get();
        $gradi = array();

        foreach ($voci as $voce) {
            if ($voce->voce_padre == null || $voce->voce_padre == "") {
                $h1[$voce->name] = $voce->extended_name;
                $gradi[] = array();
            } else {
                foreach ($voci as $voci1) {
                    if ($voce->voce_padre == $voci1->name) {
                        if ($voci1->voce_padre == null || $voci1->voce_padre == "") {
                            $gradi[$voci1->name][$voce->name] = array();
                        } else {
                            foreach ($voci as $voci2) {
                                if ($voci1->voce_padre == $voci2->name) {
                                    if ($voci2->voce_padre == null || $voci2->voce_padre == "") {
                                        $gradi[$voci2->name][$voci1->name][$voce->name] = array();
                                    } else {
                                        foreach ($voci as $voci3) {
                                            if ($voci2->voce_padre == $voci3->name) {
                                                if ($voci3->voce_padre == null || $voci3->voce_padre == "") {
                                                    $gradi[$voci3->name][$voci2->name][$voci1->name][$voce->name] = array();
                                                } else {
                                                    foreach ($voci as $voci4) {
                                                        if ($voci3->voce_padre == $voci4->name) {
                                                            if ($voci4->voce_padre == null || $voci4->voce_padre == "") {
                                                                $gradi[$voci4->name][$voci3->name][$voci2->name][$voci1->name][$voce->name] = array();
                                                            } else {
                                                                foreach ($voci as $voci5) {
                                                                    if ($voci4->voce_padre == $voci5->name) {
                                                                        if ($voci5->voce_padre == null || $voci5->voce_padre == "") {
                                                                            $gradi[$voci5->name][$voci4->name][$voci3->name][$voci2->name][$voci1->name][$voce->name] = array();
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        unset($gradi[0], $gradi[1], $gradi[2]);

        $vociExt = array();

        $dataAnalisiBasic = array();

        foreach ($voci as $voce) {
            $vociExt[$voce->name] = $voce->extended_name;
        }

        // $analisi = Analisi::with('bilanci')->with('account')->with('analisisType')->findOrFail($id);

        $bilancioJSON = json_decode($bilancio['json_data']);
        $bilancioJSONprev = json_decode($bilancio['json_data_prev']);
        $dataAnalisis = array();
        $righeUtilizzate = array();

        if ($bilancio->provvisorio == 1) {
            $vociContoEconomico = array(
                "ValoreProduzioneRicaviVenditePrestazioni",
                "ValoreProduzioneVariazioniRimanenzeProdottiCorsoLavorazioneSemilavoratiFiniti",
                "ValoreProduzioneVariazioniLavoriCorsoOrdinazione",
                "ValoreProduzioneIncrementiImmobilizzazioniLavoriInterni",
                "ValoreProduzioneAltriRicaviProventiTotaleAltriRicaviProventi",
                "CostiProduzioneMateriePrimeSussidiarieConsumoMerci",
                "CostiProduzioneServizi",
                "CostiProduzioneGodimentoBeniTerzi",
                "CostiProduzionePersonaleTotaleCostiPersonale",
                "CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni",
                "CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci",
                "CostiProduzioneAccantonamentiRischi",
                "CostiProduzioneAltriAccantonamenti",
                "CostiProduzioneOneriDiversiGestione",
                "ProventiOneriFinanziariProventiPartecipazioniTotaleProventiPartecipazioni",
                "ProventiOneriFinanziariAltriProventiFinanziariTotaleAltriProventiFinanziari",
                "ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari",
                "ProventiOneriStraordinariProventiTotaleProventi",
                "ProventiOneriStraordinariOneriTotaleOneri",
            );

            $tmpPeriod = explode(' ', $bilancio->year);

            $periodStart = new DateTime($tmpPeriod[0]);

            $periodEnd = new DateTime($tmpPeriod[1]);

            $days = $periodEnd->diff($periodStart)->format("%a");

            $daysToYear = 365 / $days;

            $bilancioJSON = (array)$bilancioJSON;

            $bilancioJSON['ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate'] = (float)$bilancioJSON['RisultatoPrimaImposte'] * 0.28;

            $bilancioJSON['UtilePerditaEsercizio'] = (float)$bilancioJSON['RisultatoPrimaImposte'] - (float)$bilancioJSON['ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate'];

            foreach ($vociContoEconomico as $tmp => $singolaVoce) {
                if (isset($bilancioJSON[$singolaVoce])) {
                    $bilancioJSON[$singolaVoce] = ($bilancioJSON[$singolaVoce]) * $daysToYear;
                }
            }

            $bilancioJSON = (object)$bilancioJSON;
        }

        $bilancioJSONanag = json_decode($bilancio['json_data_anag']); // DatiAnagraficiDenominazione DatiAnagraficiFormaGiuridica

        $formaGiuridica = $bilancio->forma_giuridica;

        $nomeAzienda = $bilancioJSONanag->DatiAnagraficiDenominazione;

        $salariStipendi = $bilancioJSON->CostiProduzionePersonaleSalariStipendi;

        $TotaleAttivo = (isset($bilancioJSON->TotaleAttivo) ? $bilancioJSON->TotaleAttivo : 0);
        $CostiProduzioneAltriAccantonamenti = (isset($bilancioJSON->CostiProduzioneAltriAccantonamenti) ? $bilancioJSON->CostiProduzioneAltriAccantonamenti : 0);
        $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni = (isset($bilancioJSON->CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni) ? $bilancioJSON->CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni : 0);
        $TotaleCreditiVersoSociVersamentiAncoraDovuti = (isset($bilancioJSON->TotaleCreditiVersoSociVersamentiAncoraDovuti) ? $bilancioJSON->TotaleCreditiVersoSociVersamentiAncoraDovuti : 0);
        $TotalePatrimonioNetto = (isset($bilancioJSON->TotalePatrimonioNetto) ? $bilancioJSON->TotalePatrimonioNetto : 0);
        $PN_NEGATIVO = $TotalePatrimonioNetto - $TotaleCreditiVersoSociVersamentiAncoraDovuti;
        $dataAnalisisBasic['Patrimonio_Netto'] = $PN_NEGATIVO;
        $arrayConVoci['Patrimonio_Netto'] = array('TotalePatrimonioNetto', 'TotaleCreditiVersoSociVersamentiAncoraDovuti');
        $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo : 0));
        $UtilePerditaEsercizio = (isset($bilancioJSON->UtilePerditaEsercizio) ? $bilancioJSON->UtilePerditaEsercizio : 0);
        $PatrimonioNettoUtilePerditaEsercizio = isset($bilancioJSON->PatrimonioNettoUtilePerditaEsercizio) ? $bilancioJSON->PatrimonioNettoUtilePerditaEsercizio : 0;
        // Valori bilancio
        // ### OF_RICAVI ###
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = (isset($bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari) ? $bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari : 0);
        $ValoreProduzioneRicaviVenditePrestazioni = (isset($bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni) ? $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni : 0);
        if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
            $ValoreProduzioneRicaviVenditePrestazioni = 0.00001;
        }
        $OF_RICAVI = number_format((float)($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari / $ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '.');
        $dataAnalisisBasic['OF_Fatturato'] = $OF_RICAVI . '%';
        $arrayConVoci['OF_Fatturato'] = array('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 'ValoreProduzioneRicaviVenditePrestazioni');

        // ### ADEGUATEZZA_PATRIMONIALE ###
        $TotaleDebiti = (isset($bilancioJSON->TotaleDebiti) ? $bilancioJSON->TotaleDebiti : 0);
        $PassivoRateiRisconti = (isset($bilancioJSON->PassivoRateiRisconti) ? $bilancioJSON->PassivoRateiRisconti : 0);

        $ADEGUATEZZA_PATRIMONIALE = number_format((float)($PN_NEGATIVO / ($TotaleDebiti + $PassivoRateiRisconti)) * 100, 2, ',', '.');
        $dataAnalisisBasic['Adeguatezza_Patrimoniale'] = $ADEGUATEZZA_PATRIMONIALE . '%';
        $arrayConVoci['Adeguatezza_Patrimoniale'] = array('TotaleDebiti', 'PassivoRateiRisconti');

        $arrayConVoci['Adeguatezza_Patrimoniale'] = array_merge($arrayConVoci['Adeguatezza_Patrimoniale'], $arrayConVoci['Patrimonio_Netto']);

        // ### RITORNO_LIQUIDO_ATTIVO ###
        $DebitiEsigibiliEntroEsercizioSuccessivo = isset($bilancioJSON->DebitiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiEsigibiliEntroEsercizioSuccessivo : 0;
        $TotaleDisponibilitaLiquide = (isset($bilancioJSON->TotaleDisponibilitaLiquide) ? $bilancioJSON->TotaleDisponibilitaLiquide : $val = (isset($bilancioJSONprev->TotaleDisponibilitaLiquide) ? $bilancioJSONprev->TotaleDisponibilitaLiquide : 0));
        $AttivoRateiRisconti = (isset($bilancioJSON->AttivoRateiRisconti) ? $bilancioJSON->AttivoRateiRisconti : 0);
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = (isset($bilancioJSON->TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni) ? $bilancioJSON->TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni : 0);
        $TotaleRimanenze = (isset($bilancioJSON->TotaleRimanenze) ? $bilancioJSON->TotaleRimanenze : 0);
        $TotaleCrediti = (isset($bilancioJSON->TotaleCrediti) ? $bilancioJSON->TotaleCrediti : 0);
        $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiImposteAnticipateTotaleImposteAnticipate = (isset($bilancioJSON->CreditiImposteAnticipateTotaleImposteAnticipate) ? $bilancioJSON->CreditiImposteAnticipateTotaleImposteAnticipate : 0);

        $TotaleCreditiEntroDodiciMesi = $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + $CreditiImposteAnticipateTotaleImposteAnticipate + $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo;

        $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiAccontiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiAccontiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiAccontiEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo : 0);
        $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo : 0);
        $PassivoRateiRisconti = (isset($bilancioJSON->PassivoRateiRisconti) ? $bilancioJSON->PassivoRateiRisconti : 0);

        $TotaleDebitiEntroDodiciMesi = $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + $DebitiAccontiEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo;

        // ### LIQUIDITA ###
        $CostiProduzioneAccantonamentiRischi = (isset($bilancioJSON->CostiProduzioneAccantonamentiRischi) ? $bilancioJSON->CostiProduzioneAccantonamentiRischi : 0);
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari;
        $ValoreProduzioneRicaviVenditePrestazioni = $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni;
        if ($TotaleAttivo == 0) {
            $LIQUIDITA = 0;
            $dataAnalisisBasic['Liquidità'] = $LIQUIDITA . '%';
        } else {
            $LIQUIDITA = number_format((float)(($UtilePerditaEsercizio + $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni + $CostiProduzioneAccantonamentiRischi + $CostiProduzioneAltriAccantonamenti) / $TotaleAttivo) * 100, 2, ',', '.');
            $dataAnalisisBasic['Liquidità'] = $LIQUIDITA . '%';
        }
        $arrayConVoci['Liquidità'] = array('UtilePerditaEsercizio', 'CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 'CostiProduzioneAccantonamentiRischi', 'CostiProduzioneAltriAccantonamenti', 'TotaleAttivo');

        // ### INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO ###
        $DebitiDebitiTributariTotaleDebitiTributariCorrente = (isset($bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari) ? $bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari : 0);
        $DebitiDebitiTributariTotaleDebitiTributari = (isset($bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari) ? $bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari : 0);
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale = (isset($bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) ? $bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale : 0);
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari;
        $ValoreProduzioneRicaviVenditePrestazioni = $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni;
        if ($TotaleAttivo == 0) {
            $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO = number_format((($DebitiDebitiTributariTotaleDebitiTributari + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) / 1) * 100, 2, ',', '.');
            $dataAnalisisBasic['Indebitamento_Previdenziale_Tributario'] = $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO . '%';
        } else {
            $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO = number_format((($DebitiDebitiTributariTotaleDebitiTributari + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) / $TotaleAttivo) * 100, 2, ',', '.');
            $dataAnalisisBasic['Indebitamento_Previdenziale_Tributario'] = $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO . '%';
        }

        $arrayConVoci['Indebitamento_Previdenziale_Tributario'] = array('DebitiDebitiTributariTotaleDebitiTributari', 'DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale', 'TotaleAttivo');
        // INDICI ADVANCED

        //### Andamento del fatturato
        $ValoreProduzioneRicaviVenditePrestazioniCurr = (isset($bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni) ? $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni : 0);
        $ValoreProduzioneRicaviVenditePrestazioniPrev = (isset($bilancioJSONprev->ValoreProduzioneRicaviVenditePrestazioni) ? $bilancioJSONprev->ValoreProduzioneRicaviVenditePrestazioni : 0);
        if ($ValoreProduzioneRicaviVenditePrestazioniPrev == 0) {
            $AndamentoDelFatturato = number_format((float)(- (1 - (($ValoreProduzioneRicaviVenditePrestazioniCurr) / (1)))) * 100, 2, ',', '.');
            $dataAnalisis['Andamento_del_fatturato'] = $AndamentoDelFatturato . '%';
        } else {
            $AndamentoDelFatturato = number_format((float)(- (1 - (($ValoreProduzioneRicaviVenditePrestazioniCurr) / ($ValoreProduzioneRicaviVenditePrestazioniPrev)))) * 100, 2, ',', '.');
            $dataAnalisis['Andamento_del_fatturato'] = $AndamentoDelFatturato . '%';
        }
        $arrayConVoci['Andamento_del_fatturato'] = array('ValoreProduzioneRicaviVenditePrestazioniCurr', 'ValoreProduzioneRicaviVenditePrestazioniPrev');

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

        if ($MOLprev == 0) {
            $AndamentoMOL = number_format(- (1 - ($MOLcurr / 1)) * 100, 2, ',', '.');
            $dataAnalisis['Andamento_del_MOL'] = $AndamentoMOL . '%';
        } else {
            $AndamentoMOL = number_format(- (1 - ($MOLcurr / $MOLprev)) * 100, 2, ',', '.');
            $dataAnalisis['Andamento_del_MOL'] = $AndamentoMOL . '%';
        }

        $arrayConVoci['Andamento_del_MOL'] = array('TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzioneServizi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione');

        // ### ROI

        $DifferenzaValoreCostiProduzione = (isset($bilancioJSON->DifferenzaValoreCostiProduzione) ? $bilancioJSON->DifferenzaValoreCostiProduzione : 0);
        if ($TotaleAttivo == 0) {
            $ROI = number_format((float)($DifferenzaValoreCostiProduzione / 1) * 100, 2, ',', '.');
            $dataAnalisis['ROI'] = $ROI . '%';
        } else {
            $ROI = number_format((float)($DifferenzaValoreCostiProduzione / $TotaleAttivo) * 100, 2, ',', '.');
            $dataAnalisis['ROI'] = $ROI . '%';
        }

        $arrayConVoci['ROI'] = array('DifferenzaValoreCostiProduzione', 'TotaleAttivo');

        // ### ROS

        if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
            $ROS = number_format((float)($DifferenzaValoreCostiProduzione / 1) * 100, 2, ',', '.');
            $dataAnalisis['ROS'] = $ROS . '%';
        } else {
            $ROS = number_format((float)($DifferenzaValoreCostiProduzione / $ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '.');
            $dataAnalisis['ROS'] = $ROS . '%';
        }

        $arrayConVoci['ROS'] = array('DifferenzaValoreCostiProduzione', 'ValoreProduzioneRicaviVenditePrestazioni');

        //### ROE

        if ($TotalePatrimonioNetto == 0) {
            $ROE = number_format((float)($UtilePerditaEsercizio / 1) * 100, 2, ',', '.');
            $dataAnalisis['ROE'] = $ROE . '%';
        } else {
            $ROE = number_format((float)($UtilePerditaEsercizio / $TotalePatrimonioNetto) * 100, 2, ',', '.');
            $dataAnalisis['ROE'] = $ROE . '%';
        }

        $arrayConVoci['ROE'] = array('UtilePerditaEsercizio', 'TotalePatrimonioNetto');

        //### EBITDA/Fatturato

        if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
            $EBITDA_FATTURATO = number_format((float)(($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / 1) * 100, 2, ',', '.');
            $dataAnalisis['EBITDA_Fatturato'] = $EBITDA_FATTURATO . '%';
        } else {
            $EBITDA_FATTURATO = number_format((float)(($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / $ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '.');
            $dataAnalisis['EBITDA_Fatturato'] = $EBITDA_FATTURATO . '%';
        }

        $arrayConVoci['EBITDA_Fatturato'] = array('TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneServizi', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione', 'ValoreProduzioneRicaviVenditePrestazioni');

        //### Andamento dei mezzi propri

        $TotalePatrimonioNettoCurr = (isset($bilancioJSON->TotalePatrimonioNetto) ? $bilancioJSON->TotalePatrimonioNetto : 0);
        $TotalePatrimonioNettoPrev = (isset($bilancioJSONprev->TotalePatrimonioNetto) ? $bilancioJSONprev->TotalePatrimonioNetto : 0);
        if ($TotalePatrimonioNettoPrev == 0) {
            $AndamentoDeiMezziPropri = number_format((float)(($TotalePatrimonioNettoCurr / 1) - 1) * 100, 2, ',', '.');
            $dataAnalisis['Andamento_dei_mezzi_propri'] = $AndamentoDeiMezziPropri . '%';
        } else {
            $AndamentoDeiMezziPropri = number_format((float)(($TotalePatrimonioNettoCurr / $TotalePatrimonioNettoPrev) - 1) * 100, 2, ',', '.');
            $dataAnalisis['Andamento_dei_mezzi_propri'] = $AndamentoDeiMezziPropri . '%';
        }

        $arrayConVoci['Andamento_dei_mezzi_propri'] = array('TotalePatrimonioNetto');

        //### Margine Struttura Primario
        $TotaleImmobilizzazioni = (isset($bilancioJSON->TotaleImmobilizzazioni) ? $bilancioJSON->TotaleImmobilizzazioni : 0);
        if ($TotaleImmobilizzazioni == 0) {
            $Margine_Struttura_Primario = number_format((float)($TotalePatrimonioNetto / 1) * 100, 2, ',', '.');
            $dataAnalisis['Margine_Struttura_Primario'] = $Margine_Struttura_Primario . '%';
        } else {
            $Margine_Struttura_Primario = number_format((float)($TotalePatrimonioNetto / $TotaleImmobilizzazioni) * 100, 2, ',', '.');
            $dataAnalisis['Margine_Struttura_Primario'] = $Margine_Struttura_Primario . '%';
        }

        $arrayConVoci['Margine_Struttura_Primario'] = array('TotalePatrimonioNetto', 'TotaleImmobilizzazioni');

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


        $QuarantaTre = $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo + $DebitiAccontiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo; //(isset($bilancioJSON->DebitiOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiOltreEsercizioSuccessivo : 0);

        if ($TotaleImmobilizzazioni == 0) {
            $Margine_Struttura_Secondario_Semplificato = number_format((float)(($TotalePatrimonioNetto + $TrattamentoFineRapportoLavoroSubordinato + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo + $DebitiAccontiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) / 1) * 100, 2, ',', '.');
            $Margine_Struttura_Secondario_Ordinario = number_format((float)(($TotalePatrimonioNetto + $TrattamentoFineRapportoLavoroSubordinato + $QuarantaTre) / 1) * 100, 2, ',', '.');
            $dataAnalisis['Margine_Struttura_Secondario'] = $Margine_Struttura_Secondario_Semplificato . '%';
        } else {
            $Margine_Struttura_Secondario_Semplificato = number_format((float)(($TotalePatrimonioNetto + $TrattamentoFineRapportoLavoroSubordinato + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo + $DebitiAccontiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) / $TotaleImmobilizzazioni) * 100, 2, ',', '.');
            $Margine_Struttura_Secondario_Ordinario = number_format((float)(($TotalePatrimonioNetto + $TrattamentoFineRapportoLavoroSubordinato + $QuarantaTre) / $TotaleImmobilizzazioni) * 100, 2, ',', '.');
            $dataAnalisis['Margine_Struttura_Secondario'] = $Margine_Struttura_Secondario_Semplificato . '%';
        }

        $arrayConVoci['Margine_Struttura_Secondario'] = array('TotalePatrimonioNetto', 'TrattamentoFineRapportoLavoroSubordinat', 'DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 'DebitiAccontiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo', 'DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo', 'TotaleImmobilizzazioni');

        // CURRENT RATIO (VEDI INDICE RITORNO LIQUIDO ATT)

        $denominatoreRitornoLiquidoAttivo = 0;
        if (($TotaleDebitiEntroDodiciMesi + $PassivoRateiRisconti) == 0) {
            $denominatoreRitornoLiquidoAttivo = 1;
        }

        $formula = ($TotaleDisponibilitaLiquide + $AttivoRateiRisconti + $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + $TotaleRimanenze + $TotaleCreditiEntroDodiciMesi) / ($TotaleDebitiEntroDodiciMesi + $PassivoRateiRisconti + $denominatoreRitornoLiquidoAttivo);

        $RITORNO_LIQUIDO_ATTIVO = number_format((float)$formula * 100, 2, ',', '.');
        $dataAnalisis['Current_Ratio'] = $RITORNO_LIQUIDO_ATTIVO . '%';
        $arrayConVoci['Current_Ratio'] = array('TotaleDisponibilitaLiquide', 'AttivoRateiRisconti', 'TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 'TotaleRimanenze', 'TotaleCreditiEntroDodiciMesi', 'TotaleDebitiEntroDodiciMesi', 'PassivoRateiRisconti');

        //### Attivita a breve / Passività a Breve

        // TRENTACINQUE
        $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiVersoControllantiEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoControllantiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoControllantiEsigibiliOltreEsercizioSuccessivo : 0);
        $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo : 0));
        $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo : 0));
        $TrentaCinque = $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo;
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
        $QuarantaNove = $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiAccontiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo;
        $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo : 0);

        if ($QuarantaNove > 0 || $PassivoRateiRisconti > 0) {
            $Attivita_a_breve_Passivita_a_Breve_Semplificato = number_format((float)((($TotaleDisponibilitaLiquide + $TrentaCinque + $TotaleRimanenze + $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + $AttivoRateiRisconti) / ($QuarantaNove + $PassivoRateiRisconti))), 2, ',', '.');
            //$dataAnalisis['Attivita_a_breve_Passività_a_Breve_Semplificato'] = $Attivita_a_breve_Passivita_a_Breve_Semplificato.'%';
        }
        $Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore = $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiAccontiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $PassivoRateiRisconti;

        if ($Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore > 0) {
            $Attivita_a_breve_Passivita_a_Breve_Ordinario = number_format((float)((($TotaleDisponibilitaLiquide + $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo + $TotaleRimanenze + $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + $AttivoRateiRisconti) / ($Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore))) * 100, 2, ',', '.');
            $dataAnalisis['Attivita_a_breve_Passività_a_Breve_Ordinario'] = $Attivita_a_breve_Passivita_a_Breve_Ordinario . '%';
        }

        $arrayConVoci['Attivita_a_breve_Passività_a_Breve_Ordinario'] = array();

        // ACID TEST
        if ($DebitiEsigibiliEntroEsercizioSuccessivo > 0 || $PassivoRateiRisconti > 0) {
            $AcidTest = number_format((float)(($TotaleCrediti + $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + $TotaleDisponibilitaLiquide + $AttivoRateiRisconti) / ((float)$DebitiEsigibiliEntroEsercizioSuccessivo + $PassivoRateiRisconti)), 2, ',', '.');
            //            $dataAnalisis['AcidTest'] = $AcidTest.'%';
        }
        if ($QuarantaNove > 0 || $PassivoRateiRisconti > 0) {
            $ACID_TEST_Semplificato = number_format((float)((($TotaleDisponibilitaLiquide + $TrentaCinque + $TotaleRimanenze + $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + $AttivoRateiRisconti - $TotaleRimanenze) / ($QuarantaNove + $PassivoRateiRisconti))), 2, ',', '.');
            // $dataAnalisis['ACID_TEST_Semplificato'] = $ACID_TEST_Semplificato.'%';
        }
        $ACID_TEST_Ordinario_divisore = $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiAccontiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $PassivoRateiRisconti;

        if ($ACID_TEST_Ordinario_divisore > 0) {
            $ACID_TEST_Ordinario = number_format((float)((($TotaleDisponibilitaLiquide + $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo + $TotaleRimanenze + $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + $AttivoRateiRisconti - $TotaleRimanenze) / ($ACID_TEST_Ordinario_divisore))) * 100, 2, ',', '.');
            $dataAnalisis['Acid_Test'] = $ACID_TEST_Ordinario . '%';
        }

        $arrayConVoci['Acid_Test'] = array('TotaleDisponibilitaLiquide', 'CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo', 'CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 'CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 'CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 'CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 'CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 'TotaleRimanenze', 'TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 'AttivoRateiRisconti', 'TotaleRimanenze', 'DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 'DebitiAccontiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 'DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 'PassivoRateiRisconti');

        // AUTONOMIA FINANZIARIA
        if (($TotalePatrimonioNetto + $TotaleDebiti) == 0) {
            $AUTONOMIA_FINANZIARIA = number_format((float)(($TotalePatrimonioNetto / 1)) * 100, 2, ',', '.');
            $dataAnalisis['Autonomia_Finanziaria'] = $AUTONOMIA_FINANZIARIA . '%';
        } else {
            $AUTONOMIA_FINANZIARIA = number_format((float)(($TotalePatrimonioNetto / ($TotalePatrimonioNetto + $TotaleDebiti))) * 100, 2, ',', '.');
            $dataAnalisis['Autonomia_Finanziaria'] = $AUTONOMIA_FINANZIARIA . '%';
        }

        $arrayConVoci['Autonomia_Finanziaria'] = array('TotalePatrimonioNetto', 'TotalePatrimonioNetto', 'TotaleDebiti');

        // LIVELLO INVESTIMENTI AZIENDALI
        if ($TotaleAttivo == 0) {
            $LIVELLO_INVESTIMENTI_AZIENDALI = number_format((float)($TotalePatrimonioNetto / 0.1) * 100, 2, ',', '.');
            $dataAnalisis['Livello_investimenti_aziendali'] = $LIVELLO_INVESTIMENTI_AZIENDALI . '%';
        } else {
            $LIVELLO_INVESTIMENTI_AZIENDALI = number_format((float)($TotalePatrimonioNetto / $TotaleAttivo) * 100, 2, ',', '.');
            $dataAnalisis['Livello_investimenti_aziendali'] = $LIVELLO_INVESTIMENTI_AZIENDALI . '%';
        }

        $arrayConVoci['Livello_investimenti_aziendali'] = array('TotalePatrimonioNetto', 'TotaleAttivo');

        // PFN / EBITDA
        $ImmobilizzazioniFinanziarieCreditiTotaleCrediti = (isset($bilancioJSON->ImmobilizzazioniFinanziarieCreditiTotaleCrediti) ? $bilancioJSON->ImmobilizzazioniFinanziarieCreditiTotaleCrediti : 0);
        $debitiFinanziariCurr = $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;
        if ($MOLcurr == 0) {
            $PFN_EBITDA = number_format((float)(($debitiFinanziariCurr - $TotaleDisponibilitaLiquide - $ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / 1), 2, ',', '.');
            $dataAnalisis['PFN_EBITDA'] = $PFN_EBITDA;
        } else {
            $PFN_EBITDA = number_format((float)(($debitiFinanziariCurr - $TotaleDisponibilitaLiquide - $ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / $MOLcurr), 2, ',', '.');
            $dataAnalisis['PFN_EBITDA'] = $PFN_EBITDA;
        }

        $arrayConVoci['PFN_EBITDA'] = array('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 'TotaleDisponibilitaLiquide', 'ImmobilizzazioniFinanziarieCreditiTotaleCrediti', 'TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzioneServizi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione');

        // Peso Oneri Finanziari (OF/Fatturato)
        $denominatoreOFfatturato = $ValoreProduzioneRicaviVenditePrestazioni;
        if ($denominatoreOFfatturato == 0) {
            $denominatoreOFfatturato = 1;
        }
        $Peso_Oneri_Finanziari = number_format((float)($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari / $denominatoreOFfatturato) * 100,  2, ',', '.');
        $dataAnalisis['Peso_Oneri_Finanziari'] = $Peso_Oneri_Finanziari . '%';
        $arrayConVoci['Peso_Oneri_Finanziari'] = array('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 'ValoreProduzioneRicaviVenditePrestazioni');

        // Copertura Lorda degli Oneri Finanziari
        if ($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari == 0) {
            $Copertura_Lorda_degli_Oneri_Finanziari = number_format((float)(($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / 1), 2, ',', '.');
            $dataAnalisis['Copertura_Lorda_OF'] = $Copertura_Lorda_degli_Oneri_Finanziari;
        } else {
            $Copertura_Lorda_degli_Oneri_Finanziari = number_format((float)(($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione) / $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari), 2, ',', '.');
            $dataAnalisis['Copertura_Lorda_OF'] = $Copertura_Lorda_degli_Oneri_Finanziari;
        }

        $arrayConVoci['Copertura_Lorda_OF'] = array('TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneServizi', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione', 'ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari');

        // EBIT / OF
        if ($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari == 0) {
            $EBIT_OF = number_format((float)(($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione - $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - $CostiProduzioneAccantonamentiRischi - $CostiProduzioneAltriAccantonamenti) / 1), 2, ',', '.');
            $dataAnalisis['EBIT_OF'] = $EBIT_OF;
        } else {
            $EBIT_OF = number_format((float)(($TotaleValoreProduzione - $CostiProduzioneMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneServizi - $CostiProduzioneGodimentoBeniTerzi - $CostiProduzionePersonaleTotaleCostiPersonale - $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - $CostiProduzioneOneriDiversiGestione - $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - $CostiProduzioneAccantonamentiRischi - $CostiProduzioneAltriAccantonamenti) / $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari), 2, ',', '.');
            $dataAnalisis['EBIT_OF'] = $EBIT_OF;
        }

        $arrayConVoci['EBIT_OF'] = array('TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneServizi', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione', 'CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 'CostiProduzioneAccantonamentiRischi', 'CostiProduzioneAltriAccantonamenti', 'ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari');

        //Costo del personale
        if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
            $Costo_del_personale = number_format((float)($CostiProduzionePersonaleTotaleCostiPersonale / 1) * 100, 2, ',', '.');
            $dataAnalisis['Costo_del_personale'] = $Costo_del_personale . '%';
        } else {
            $Costo_del_personale = number_format((float)($CostiProduzionePersonaleTotaleCostiPersonale / $ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '.');
            $dataAnalisis['Costo_del_personale'] = $Costo_del_personale . '%';
        }

        $arrayConVoci['Costo_del_personale'] = array('CostiProduzionePersonaleTotaleCostiPersonale', 'ValoreProduzioneRicaviVenditePrestazioni');
        $CreditiImposteAnticipateTotaleImposteAnticipate = (isset($bilancioJSON->ImposteRedditoEsercizioCorrentiDifferiteAnticipateImposteDifferiteAnticipate) ? $bilancioJSON->ImposteRedditoEsercizioCorrentiDifferiteAnticipateImposteDifferiteAnticipate : 0);

        // CF / Attivo
        if ($TotaleAttivo == 0) {
            $CF_ATTIVO = number_format((float)(($PatrimonioNettoUtilePerditaEsercizio + $CostiProduzioneAccantonamentiRischi + $CostiProduzioneAltriAccantonamenti + $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - $CreditiImposteAnticipateTotaleImposteAnticipate) / 0.1) * 100, 2, ',', '.');
            $dataAnalisis['CF_Attivo'] = $CF_ATTIVO . '%';
        } else {
            $CF_ATTIVO = number_format((float)(($PatrimonioNettoUtilePerditaEsercizio + $CostiProduzioneAccantonamentiRischi + $CostiProduzioneAltriAccantonamenti + $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - $CreditiImposteAnticipateTotaleImposteAnticipate) / $TotaleAttivo) * 100, 2, ',', '.');
            $dataAnalisis['CF_Attivo'] = $CF_ATTIVO . '%';
        }

        $arrayConVoci['CF_Attivo'] = array('PatrimonioNettoUtilePerditaEsercizio', 'CostiProduzioneAccantonamentiRischi', 'CostiProduzioneAltriAccantonamenti', 'CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 'CreditiImposteAnticipateTotaleImposteAnticipate', 'TotaleAttivo');


        //Indice di Indebitamento (PFN/PN)
        if ($TotalePatrimonioNetto == 0) {
            $MOLannoCorrente = $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;
            $Indice_di_Indebitamento = number_format((float)(($MOLannoCorrente - $TotaleDisponibilitaLiquide - $ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / 0.1) * 100, 2, ',', '.');
            $dataAnalisis['Indice_di_Indebitamento'] = $Indice_di_Indebitamento . '%';
        } else {
            $MOLannoCorrente = $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;
            $Indice_di_Indebitamento = number_format((float)(($MOLannoCorrente - $TotaleDisponibilitaLiquide - $ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / $TotalePatrimonioNetto) * 100, 2, ',', '.');
            $dataAnalisis['Indice_di_Indebitamento'] = $Indice_di_Indebitamento . '%';
        }

        $arrayConVoci['Indice_di_Indebitamento'] = array('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 'TotaleDisponibilitaLiquide', 'ImmobilizzazioniFinanziarieCreditiTotaleCrediti', 'TotalePatrimonioNetto');

        $indiciBilancio = array();

        //SALDO DEBITI VS FISCO

        $DebitiDebitiTributariTotaleDebitiTributariCorrente = (isset($bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari) ? $bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari : 0);
        $FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente = (isset($bilancioJSON->FondiRischiOneriTrattamentoQuiescenzaObblighiSimili) ? $bilancioJSON->FondiRischiOneriTrattamentoQuiescenzaObblighiSimili : 0);
        $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipatePrecedente = isset($bilancioJSONprev->ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate) ? $bilancioJSONprev->ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate : 0;
        $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate = isset($bilancioJSON->ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate) ? $bilancioJSON->ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate : 0;

        $DifferenzaImposteReddito = ($ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate + $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipatePrecedente) / 2;
        if ($DifferenzaImposteReddito == 0) {
            $SaldoDebitiVSFisco = number_format(($FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente + $DebitiDebitiTributariTotaleDebitiTributariCorrente) / 1, 3, ',', '.');
            $dataAnalisis['Saldo_dei_Debiti_verso_il_Fisco'] = $SaldoDebitiVSFisco;
        } else {
            $SaldoDebitiVSFisco = number_format(($FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente + $DebitiDebitiTributariTotaleDebitiTributariCorrente) / $DifferenzaImposteReddito, 3, ',', '.');
            $dataAnalisis['Saldo_dei_Debiti_verso_il_Fisco'] = $SaldoDebitiVSFisco;
        }

        $arrayConVoci['Saldo_dei_Debiti_verso_il_Fisco'] = array('FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente', 'DebitiDebitiTributariTotaleDebitiTributariCorrente', 'DifferenzaImposteReddito');

        $bilanciHelper = new BilanciHelper;

        $explodedDate = explode('-', $bilancio->year)[0];

        $valutazioneBilancio = $bilanciHelper->valutazioneIndici($dataAnalisis, $tipoAzienda, $explodedDate);

        $indiciImportanti = array();

        foreach ($arrayConVoci as $indice => $formulaIndici) {
            $indiciImportanti = array_merge($indiciImportanti, $formulaIndici);
        }

        $indiciImportanti = array_unique($indiciImportanti);

        $soglieBasicAlert = array();

        $dataAnalisisBasic['OF_Fatturato'] = str_replace(',', '.', $dataAnalisisBasic['OF_Fatturato']);
        $dataAnalisisBasic['Adeguatezza_Patrimoniale'] = str_replace(',', '.', $dataAnalisisBasic['Adeguatezza_Patrimoniale']);
        $dataAnalisisBasic['Liquidità'] = str_replace(',', '.', $dataAnalisisBasic['Liquidità']);
        $dataAnalisisBasic['Indebitamento_Previdenziale_Tributario'] = str_replace(',', '.', $dataAnalisisBasic['Indebitamento_Previdenziale_Tributario']);
        $dataAnalisis['Current_Ratio'] = str_replace(',', '.', $dataAnalisis['Current_Ratio']);

        if (DB::table('rangesBasic')->where('tipo_azienda', '=', $tipoAzienda)->where('indice', '=', 'Sostenibilità Oneri Finanziari')->where('soglia', '>', floatval($dataAnalisisBasic['OF_Fatturato']))->count()) {
            $soglieBasic['Sostenibilità Oneri Finanziari'] = true;
        } else {
            $soglieBasic['Sostenibilità Oneri Finanziari'] = false;
        }
        if (DB::table('rangesBasic')->where('tipo_azienda', '=', $tipoAzienda)->where('indice', '=', 'Adeguatezza Patrimoniale')->where('soglia', '<', floatval($dataAnalisisBasic['Adeguatezza_Patrimoniale']))->count()) {
            $soglieBasic['Adeguatezza Patrimoniale'] = true;
        } else {
            $soglieBasic['Adeguatezza Patrimoniale'] = false;
        }

        if (DB::table('rangesBasic')->where('tipo_azienda', '=', $tipoAzienda)->where('indice', '=', 'Liquidità')->where('soglia', '<', floatval($dataAnalisisBasic['Liquidità']))->count()) {
            $soglieBasic['Liquidità'] = true;
        } else {
            $soglieBasic['Liquidità'] = false;
        }
        if (DB::table('rangesBasic')->where('tipo_azienda', '=', $tipoAzienda)->where('indice', '=', 'Indebitamento Previdenziale Tributario')->where('soglia', '>', floatval($dataAnalisisBasic['Indebitamento_Previdenziale_Tributario']))->count()) {
            $soglieBasic['Indebitamento Previdenziale Tributario'] = true;
        } else {
            $soglieBasic['Indebitamento Previdenziale Tributario'] = false;
        }
        if (DB::table('rangesBasic')->where('tipo_azienda', '=', $tipoAzienda)->where('indice', '=', 'Ritorno Liquido Attivo')->where('soglia', '<', floatval($dataAnalisis['Current_Ratio']))->count()) {
            $soglieBasic['Ritorno Liquido Attivo'] = true;
        } else {
            $soglieBasic['Ritorno Liquido Attivo'] = false;
        }

        $valutazioneAllertaBasic = true;

        foreach ($soglieBasic as $label => $alert) {
            if ($alert) {
                $valutazioneAllertaBasic = false;
            }
        }

        $sistemaBasic = DB::table('basic')->where('bilancio_id', '=', $idBilancio)->get()->first();
		
		if($valutazioneAllertaBasic) {
			$rischioAzienda = "Azienda a rischio";
		} else {
			$rischioAzienda = "Azienda non a Rischio";
		}
		
		$patrimonioNettoSiNo = false;
		$DSCR6Mesi = "No";
		if($dataAnalisisBasic['Patrimonio_Netto'] < 0) {
			$patrimonioNettoSiNo = "Si";
			if(isset($sistemaBasic) && $sistemaBasic->DSCR == "no") {
				$DSCR6Mesi = "No";
			} else {
				$DSCR6Mesi = "Si";
			}
		} else {
			$patrimonioNettoSiNo = "No";
		}
	 
			$agenziaEntrate1 = 0;
	 		$agenziaEntrate2 = 0;
	 	 	$agenziaEntrate3 = 0;
	 		$agenziaEntrate4 = 0;
			if(isset($sistemaBasic->rimborsoDSCRmese1)) {
				$agenziaEntrate1 = number_format($sistemaBasic->agenziaEntrate1,0,',','.');
			}
	 		if(isset($sistemaBasic->rimborsoDSCRmese2)) {
				$agenziaEntrate2 = number_format($sistemaBasic->agenziaEntrate2,0,',','.');
			}
	 		if(isset($sistemaBasic->rimborsoDSCRmese3)) {
				$agenziaEntrate3 = number_format($sistemaBasic->agenziaEntrate3,0,',','.');
			} else {
				$agenziaEntrate3 = $ValoreProduzioneRicaviVenditePrestazioni;
			}
	 	 	if(isset($sistemaBasic->rimborsoDSCRmese4)) {
				$agenziaEntrate4 = number_format($sistemaBasic->agenziaEntrate4,2,',','.');
			}
	 
			$inps1 = 0;
	 		$inps2 = 0;
	 		$inps3 = 0;
	 		if(isset($sistemaBasic->INPS1)) {
				$inps1 = number_format($sistemaBasic->INPS1,0,',','.');
			}
	 		if(isset($sistemaBasic->INPS2)) {
				$inps2 = number_format($sistemaBasic->INPS2,0,',','.');
			}
	 		if(isset($sistemaBasic->INPS3)) {
				$inps3 = number_format($sistemaBasic->INPS3,0,',','.');
			}
	 	
	 		$riscossione = 0;
			if(isset($sistemaBasic->riscossione)) {
				$riscossione = $sistemaBasic->riscossione;
			}
	 
	 		$retribuzioni1 = 0;
	 		$retribuzioni2 = 0;
	 		$retribuzioni3 = 0;
	 		$retribuzioni2Percent = 0;
			
	 		if(isset($sistemaBasic->retribuzioni1)) {
				$retribuzioni1 = number_format($sistemaBasic->retribuzioni1,0,',','.');
			}
	 	 	if(isset($sistemaBasic->retribuzioni2)) {
				$retribuzioni2 = number_format($sistemaBasic->retribuzioni2,0,',','.');
			} else {
				$retribuzioni2 = $salariStipendi;
			}
	 	 	if(isset($sistemaBasic->retribuzioni2)) {
				$retribuzioni2Percent = $sistemaBasic->retribuzioni2;
			}
	 
			$fornitori1 = 0;
			$fornitori2 = 0;
			
	 		if(isset($sistemaBasic->fornitori1)) {
				$fornitori1 = $sistemaBasic->fornitori1;
			}
	 		if(isset($sistemaBasic->fornitori2)) {
				$fornitori2 = $sistemaBasic->fornitori2;
			}

			$bilancioHelper = new BilanciHelper;
			$analisiResult = $bilancioHelper->getAnalisiBilancio($idBilancio);

		//	dd($analisiResult['AnalisiBasic']['Soglie']);
          
        // $response = Http::get('http://127.0.0.1:8000/api/analisiBilancioGeneral/'.$idBilancio);

	

		$pdfBilancioData = [
			'currentDate' => date('d/m/y'),
			'nomeAzienda' => $nomeAzienda,
			'DSCR' => $sistemaBasic->DSCR,
			'DSCR6MesiAttendibile' => $DSCR6Mesi,
			'DSCRDate' => $sistemaBasic->DSCRDate,
			'DSCRdispLiquida' => $sistemaBasic->DSCRdispLiquida,
			'riscossione' => $riscossione,
			'alertRiscossione' => $sistemaBasic->alertRiscossione,
			'rischioAzienda' => $rischioAzienda,
			'patrimonioNetto' => $patrimonioNettoSiNo,
			'soglie' => [
				"soglieBool" => $soglieBasic,
				"basicData" => $analisiResult['AnalisiBasic']['Soglie'],
			],
			'entrateDSCRCF' => [
				'DSCRCFmese1' => $sistemaBasic->entrataDSCRCFmese1,
				'DSCRCFmese2' => $sistemaBasic->entrataDSCRCFmese2,
				'DSCRCFmese3' => $sistemaBasic->entrataDSCRCFmese3,
				'DSCRCFmese4' => $sistemaBasic->entrataDSCRCFmese4,
				'DSCRCFmese5' => $sistemaBasic->entrataDSCRCFmese5,
				'DSCRCFmese6' => $sistemaBasic->entrataDSCRCFmese6,
			],
			'uscitaDSCRCF' => [
				'DSCRCFmese1' => $sistemaBasic->uscitaDSCRCFmese1,
				'DSCRCFmese2' => $sistemaBasic->uscitaDSCRCFmese2,
				'DSCRCFmese3' => $sistemaBasic->uscitaDSCRCFmese3,
				'DSCRCFmese4' => $sistemaBasic->uscitaDSCRCFmese4,
				'DSCRCFmese5' => $sistemaBasic->uscitaDSCRCFmese5,
				'DSCRCFmese6' => $sistemaBasic->uscitaDSCRCFmese6,
			],
			'rimborsoDSCR' => [
				'DSCRmese1' => $sistemaBasic->rimborsoDSCRmese1,
				'DSCRmese2' => $sistemaBasic->rimborsoDSCRmese2,
				'DSCRmese3' => $sistemaBasic->rimborsoDSCRmese3,
				'DSCRmese4' => $sistemaBasic->rimborsoDSCRmese4,
				'DSCRmese5' => $sistemaBasic->rimborsoDSCRmese5,
				'DSCRmese6' => $sistemaBasic->rimborsoDSCRmese6,
			],
			'agenziaEntrate' => [
				'agenziaEntrate1' => $agenziaEntrate1,
				'agenziaEntrate2' => $agenziaEntrate2,
				'agenziaEntrate3' => $agenziaEntrate3,
				'agenziaEntrate4' => $agenziaEntrate4,
				'alertAgenziaEntrate' => $sistemaBasic->alertAgenziaEntrate,
			],
			'INPS' => [
				'INPS1' => $inps1,
				'INPS2' => $inps2,
				'INPS3' => $inps3,
				'alertINPS' => $sistemaBasic->alertINPS,
			],
			'retribuzioni' => [
				'retribuzioni1' => $retribuzioni1,
				'retribuzioni2' => $retribuzioni2,
				'retribuzioniPercent' => $retribuzioni2Percent,
				'alertRetribuzioni' => $sistemaBasic->alertRetribuzioni,
			],
			'fornitori' => [
				'fornitori1' => $fornitori1,
				'fornitori2' => $fornitori2,
				'alertFornitori' => $sistemaBasic->alertFornitori,
			],
		];

		$printPDF = new printpdf;
	 	$printPDF->currentPayload = $pdfBilancioData;
	 	$documentId = $printPDF->generateDocument('bilancio');
	 	sleep(5);

	 	return $printPDF->getDocumentData($documentId); 
	}  

    public function reportCrAndamentale($years, Request $request)
	{
		$mesiCheckList = [0 => "fuoriMese", 1 => "gennaio", 2 => 'febbraio', 3 => 'marzo',   4 => 'aprile',   5 => 'maggio',   6 => 'giugno',   7 => 'luglio',   8 => "agosto",   9 => 'settembre',   10 => 'ottobre',   11 => 'novembre',   12 => 'dicembre'];

        $crAndamentaleData = $request->all();
        unset($crAndamentaleData['_token']);

        if (!isset($years)) {
            $msg = "Non è stato selezionato nessun periodo";

            return view('allerta.empty', compact(['msg']));
        } else {
            $lastDate = new DateTime(cr::select('date')->orderBy('date', 'desc')->first()->date);
            switch ($years) {
                case 1:
                    $earlierDate = (new DateTime(cr::select('date')->orderBy('date', 'desc')->first()->date))->modify('-11 months');
                    break;
                case 2:
                    $earlierDate = (new DateTime(cr::select('date')->orderBy('date', 'desc')->first()->date))->modify('-23 months');
                    break;
                case 3:
                    $earlierDate = (new DateTime(cr::select('date')->orderBy('date', 'desc')->first()->date))->modify('-35 months');
                    break;
                case 0:
                    $earlierDate = new DateTime(cr::select('date')->orderBy('date', 'asc')->first()->date);
            }

            $latestDate = new DateTime(cr::select('date')->orderBy('date', 'desc')->first()->date);

            $banksScoring = array();
            $singleBankData = array();

            $numeroRapportiContestati = 0;

            $unrefinedPeriods = json_decode(DB::table('crs')
                ->select('anno', 'mese', 'date')
                ->where("date", '>', $earlierDate->modify('first day of this month')->format('Y-m-d'))->where("date", '<', $lastDate->modify('last day of this month')->format('Y-m-d'))
                ->groupBy('date', 'anno', 'mese')
                ->orderBy('date')
                ->get(), true);

            $counter = 0;

            $missingMonths = array();

            foreach ($unrefinedPeriods as $label => $data) {
                $periods[$data['anno']][$data['mese']] = 1;
                $counter++;

                if ($counter < count($unrefinedPeriods)) {
                    $tempDate = new DateTime($data['date']);
                    if (cr::where([['date', '>=', $tempDate->modify('+1 month')->format('Y-m-01')], ['date', '<=', $tempDate->format('Y-m-0t')]])->groupBy('date')->count() == 0) {
                        $missingMonths[] = $mesiCheckList[(float)$tempDate->format('m')] . ' ' . $tempDate->format('Y');
                    }
                }
            }

            $latestYear = array_key_last($periods);
            $latestMonth = array_key_last($periods[$latestYear]);
            $earliestYear = array_key_first($periods);
            $earliestMonth = array_key_first($periods[$earliestYear]);
            $finePeriodo = $latestMonth . ' ' . $latestYear;
            $inizioPeriodo = $earliestMonth . ' ' . $earliestYear;

            $categories = array(
                'RISCHI A SCADENZA',
                'RISCHI AUTOLIQUIDANTI',
                'RISCHI A REVOCA',
                'SOFFERENZE'
            );

            $crHelper = new CrExtractorHelper;
            $crHelper->setPeriod($periods);
            $periodsCorrect = $crHelper->buildPeriodArray();

            $banksQuery = DB::table('crs');

            foreach ($periodsCorrect as $queryPeriodArray) {
                $banksQuery->orWhere(function ($query) use ($queryPeriodArray, $categories) {
                    $query->where($queryPeriodArray);
                   // $query->whereIn('categoria', $categories);
                });
            }
            if ($request->input('banks') !== null) {
                $banks = $request->input('banks');
            } else {

                $banksData = $banksQuery
                    ->get()
                    ->groupBy('nome_banca')
                    ->toArray();

                foreach ($banksData as $singleBankName => $arrayData) {
                    $banks[] = $singleBankName;
                }
            }
            $cleanCR = $crHelper->getAllDataToArray($banks);
            $intermediari = $crHelper->getCountBanks($banks);
            $mediaAnalisiIndebitamento = $crHelper->getMediaIndebitamento($banks);

            $numeroSconfiniTotali = $crHelper->getTotaleSconfini($banks);

            $rischiGaranzie = $crHelper->getRischiGaranzie($banks);

            $sofferenzeTotali = $crHelper->getSofferenze($banks);

            $totaleAffidamentiTable = $crHelper->getTotaleAffidamenti($categories, $latestYear, $latestMonth, $banks);
            $totaleAffidamentiGeneral = $crHelper->getTotaleAffidamentiGeneral($categories, $latestYear, $latestMonth, $banks);

            $totAffidamentiConPesiPerBanca = $crHelper->getPesiAffidamentiPerBanca($categories, $latestYear, $latestMonth, $banks);
            $scoreCR = $crHelper->getScoring($banks);
            $creditiContestati = $crHelper->getCreditiContestati($banks);
            $impagati = $crHelper->getAlertImpagati($banks);
            $garanzieEsitoNegativo = $crHelper->getGaranzieEsitoNegativo($banks);
            $sofferenze = $crHelper->getSofferenze($banks);
            $creditiPassatiPerdita = $crHelper->getCreditiPassatiPerdita($banks);

            $anomalie = $crHelper->getAnomalie($banks);

            $incidenzaImpagati = $crHelper->getPercentualeMediaImpagati($banks);
            $informazioniGaranti = $crHelper->getInformazioniGaranti($banks);
            $garanzieRicevute = $crHelper->getGaranzieRicevute($banks);

            $importiSconfini = $crHelper->getImportiSconfini($banks);

            foreach ($creditiContestati as $singleLineArray) {
                $numeroRapportiContestati += count($singleLineArray);
            }

            $affidamentiPerMese = $crHelper->getTotaleAffidamentiPerMese($periods, $categories, $banks);

            // Per singola banca
            foreach ($banks as $label => $nameData) {
                $newCrExtractor = new newCrExtractor;
                $newCrExtractor->setPeriod($periods);
                $singleBankData[$nameData]['totaleSconfini'] = $newCrExtractor->getTotaleSconfini(array(0 => $nameData));
                $singleBankData[$nameData]['Impagati'] = $newCrExtractor->getImpagati(array(0 => $nameData));
                $newCrExtractor->getAlertImpagati(array(0 => $nameData));
                $singleBankData[$nameData]['Sofferenze'] = $newCrExtractor->getSofferenze(array(0 => $nameData));
                $singleBankData[$nameData]['Crediti a perdita'] = $newCrExtractor->getCreditiPassatiPerdita(array(0 => $nameData));
                $banksScoring[$nameData] = $newCrExtractor->getScoring(array(0 => $nameData));
                if (!empty($singleBankData['totaleSconfini']['Tensioni'])) {
                    foreach ($singleBankData[$nameData]['totaleSconfini']['Tensioni'] as $creditLine => $presence) {
                        $presenzaTensione[$creditLine] = true;
                    }
                }
                unset($newCrExtractor);
            }

            $monthsList = array_keys($affidamentiPerMese);

            foreach ($totaleAffidamentiTable as $indice => $oggetto) {
                if ($oggetto["categoria"] == "RISCHI A SCADENZA") {
                    $totaleAffidamentiTable[$indice]["style"] = "background-color: rgb(236, 91, 91)";
                } else if ($oggetto["categoria"] == "RISCHI A REVOCA") {
                    $totaleAffidamentiTable[$indice]["style"] = "background-color: rgb(125, 236, 91)";
                } else {
                    $totaleAffidamentiTable[$indice]["style"] = "background-color: rgb(91, 171, 236)";
                }
            }

            $percentualiAccordato = array();
            $percentualiUtilizzato = array();

            foreach ($totAffidamentiConPesiPerBanca as $label => $data) {
                if (isset($data['PesoAccordatoOperativo'])) {
                    $percentualiAccordato[] = array("label" => $data['nome_banca'], "y" => $data['PesoAccordatoOperativo']);
                } else {
                    $percentualiAccordato[] = array("label" => $data['nome_banca'], "y" => 0);
                }
                if (isset($data['PesoUtilizzato'])) {
                    $percentualiUtilizzato[] = array("label" => $data['nome_banca'], "y" => $data['PesoUtilizzato']);
                } else {
                    $percentualiUtilizzato[] = array("label" => $data['nome_banca'], "y" => 0);
                }
            }

            $earlierDate = $earlierDate->format('Y-m-d');

            $accordatoPie = array(array('Banca', 'Accordato'));
            $utilizzatoPie = array(array('Banca', 'Utilizzato'));

            foreach ($totAffidamentiConPesiPerBanca as $labelAffidamenti => $affidamentiData) {
                $accordatoPie[] = array($affidamentiData['nome_banca'], $affidamentiData['totAccordatoOperativo']);
                $utilizzatoPie[] = array($affidamentiData['nome_banca'], $affidamentiData['totUtilizzato']);
            }

            $anomalieStatoRapporto = $crHelper->mancateSegnalazioniStatoRapporto($banks);

            $testSconfini = $crHelper->testSconfini($banks);

            $sconfiniDivisi = $crHelper->divideAnomalie($numeroSconfiniTotali, $banks);
         
            $pdfDataAndamentale = [
				"anomalieStatoRapporto" => $anomalieStatoRapporto,
				 "anomalie" => $anomalie,
				"mesiMancanti" => $missingMonths,
				"sconfiniDivisi" => $sconfiniDivisi,
				"utilizzatoPie" => $utilizzatoPie,
				"accordatoPie" => $accordatoPie,
				"prevDate" => $earlierDate,
				"garanzieRicevute" => $garanzieRicevute,
				"informazioniGaranti" => $informazioniGaranti,
				"elencoMesi" => $monthsList,
				"affidamentiPerMese" => $affidamentiPerMese,
				"bankScoring" => $banksScoring,
				"totaleAffidamentiGeneral" => $totaleAffidamentiGeneral,
				"incidenzaImpagati" => $incidenzaImpagati,
				"rischiGaranzie" => $rischiGaranzie,
				"percentualiUtilizzato" => $percentualiUtilizzato,
				"percentualiAccordato" => $percentualiAccordato,
				"latestMonth" => $latestMonth,
				"latestYear" => $latestYear,
				"importiSconfini" => $importiSconfini,
				"creditiPassatiPerdita" => $creditiPassatiPerdita,
				"sofferenze" => $sofferenze,
				"garanzieEsitoNegativo" => $garanzieEsitoNegativo,
				"impagati" => $impagati,
				"numeroRapportiContestati" => $numeroRapportiContestati,
				"intermediari" => $intermediari,
				"inizioPeriodo" => $inizioPeriodo,
				"finePeriodo" => $finePeriodo,
				"numeroSconfiniTotali" => $numeroSconfiniTotali,
				"mediaAnalisiIndebitamento" => $mediaAnalisiIndebitamento,
				"totaleAffidamentiTable" => $totaleAffidamentiTable,
				"totAffidamentiConPesiPerBanca" => $totAffidamentiConPesiPerBanca,
				"scoreCR" => $scoreCR,
				"sofferenzeTotali" => $sofferenzeTotali,
			];
			
		$printPDF = new printpdf;
	 
		$printPDF->currentPayload = $pdfDataAndamentale;
	
	 	$documentId = $printPDF->generateDocument('crAndamentale');
	 	sleep(5);

	 	return $printPDF->getDocumentData($documentId); 
	}  
	 }


	 public function reportAllerta($idBilancio, $idCr)
	{
	
        $id = $idBilancio;
        $ASISfinalScore = false;
        $generalScore = false;

        if (cr::All()->count() == 0) {
            $msg = "Non è stata caricata nessuna Centrale Rischi";
            return view('allerta.empty', compact(['msg']));
        } else {

            $crs = cr::select('anno', 'mese', 'date')->distinct()->orderBy('date', 'asc')->get();

            for ($i = count($crs) - 12; $i < count($crs); $i++) {
                $periods[$crs[$i]->anno][$crs[$i]->mese] = null;
            }


            $latestYear = array_key_last($periods);
            $latestMonth = array_key_last($periods[$latestYear]);

            $crData = array();

            $categories = array(
                'RISCHI A SCADENZA',
                'RISCHI AUTOLIQUIDANTI',
                'RISCHI A REVOCA',
            );

            $earliestYear = array_key_first($periods);
            $earliestMonth = array_key_first($periods[$earliestYear]);

            $upperBoundDate = new DateTime((cr::select('date')->where('anno', $earliestYear)->where('mese', $earliestMonth)->get()->first())->date);
            $lowerBoundDate = new DateTime($upperBoundDate->format('Y-m-d'));
            $lowerBoundDate = $lowerBoundDate->modify('-11 months');

            $banks = array();

            foreach (cr::select('nome_banca')->where('date', '>=', $lowerBoundDate->format('Y-m-d'))->where('date', '<=', $upperBoundDate->format('Y-m-d'))->distinct()->get()->toArray() as $label => $nomeBanca) {
                $banks[] = $nomeBanca["nome_banca"];
            }

            $crHelper = new CrExtractorHelper;
            $crHelper->setPeriod($periods);
            $crHelper->setDocumentId($idCr);
            $cleanCR = $crHelper->getAllDataToArray($banks);
            $intermediari = $crHelper->getCountBanks($banks);

            $allertaHelper = new AllertaHelper;
            $crExtractorHelper = new CrExtractorHelper;

            $allertaHelper->setCrExtractor($crExtractorHelper);

            $trimestrePeriod = $allertaHelper->getTrimestrePeriod($periods);
            $lastYearPeriod = $periods;
            

            $setDocumentId = $allertaHelper->setDocumentId($idCr);
            $getDocumentId = $allertaHelper->getDocumentId();

            $triennioPeriod = $allertaHelper->getTriennioPeriod($periods, $banks);

			$numeroSconfiniTotali = $crHelper->getTotaleSconfini($banks);
			$sofferenze = $crHelper->getSofferenze($banks);
            $creditiPassatiPerdita = $crHelper->getCreditiPassatiPerdita($banks);
            $crExtractorHelper->setPeriod($lastYearPeriod);
            $scoreCR = $crExtractorHelper->getScoring($banks, $intermediari, $numeroSconfiniTotali, $sofferenze, $creditiPassatiPerdita);
            $alerts = array();

            $alerts['1'] = $allertaHelper->getAnalisiCRUno($banks);

            $alerts['2'] = $allertaHelper->getAnalisiCRDue($banks);

            $alerts['3'] = $allertaHelper->getAnalisiCRTre($banks);

            $alerts['4'] = $allertaHelper->getAnalisiCRQuattro($triennioPeriod, $trimestrePeriod, $latestYear, $latestMonth, $categories);

            $alerts['5'] = $allertaHelper->getAnalisiCRCinque($periods);

            $alerts['6'] = $allertaHelper->getAnalisiCRSei($lastYearPeriod, $categories, $banks);

            $alerts['7'] = $allertaHelper->getAnalisiCRSette($lastYearPeriod);

            $alerts['8'] = $allertaHelper->getAnalisiCROtto($lastYearPeriod, $latestYear, $latestMonth, $trimestrePeriod, $triennioPeriod);

            $alerts['9'] = $allertaHelper->getAnalisiCRNove($lastYearPeriod, $latestYear, $latestMonth);

            $alerts['10'] = $allertaHelper->getAnalisiCRDieci($triennioPeriod, $latestYear, $latestMonth);

            $alerts['11'] = $allertaHelper->getAnalisiCRUndici($triennioPeriod, $trimestrePeriod, $lastYearPeriod, $latestYear, $latestMonth, array('RISCHI A REVOCA'));

            $alerts['12'] = $allertaHelper->getAnalisiCRDodici($triennioPeriod, $trimestrePeriod, $lastYearPeriod, $latestYear, $latestMonth, array('RISCHI AUTOLIQUIDANTI', 'RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI'), $banks);

            $alerts['13'] = $allertaHelper->getAnalisiCRTredici($triennioPeriod, $trimestrePeriod, $lastYearPeriod, $latestYear, $latestMonth, $categories, $banks);

            $alerts['14'] = $allertaHelper->getAnalisiCRQuattordici($banks);
            $alerts['15'] = $allertaHelper->getAnalisiCRQuindici($banks);
            $alerts['16'] = $allertaHelper->getAnalisiCRSedici($banks);
            $punteggioCR = $allertaHelper->getPunteggioCR($alerts);

            $arrayQuestionario = array();

            $questionario = DB::table('questionario')->get();
            foreach ($questionario as $item => $data) {
                $arrayQuestionario[$data->parameter]['Result'] = $data->result;
                $arrayQuestionario[$data->parameter]['Details'] = $data->details == null ? '' : $data->details;
            }

            // dd($questionario);

            $arrayForwardLooking = array();

            $forwardLooking = DB::table('forwardLooking')->get();
            foreach ($forwardLooking as $item => $data) {
                $arrayForwardLooking[$data->question] = $data->answer;
            }

            if (count($arrayQuestionario) > 0) {
                $scoreASIS = $this->valutazioneQuestionarioQualitativo($arrayQuestionario);
            } else {
                $scoreASIS = array('3' => 0, '4' => 0, '5' => 0, '6' => 0);
            }
            if (count($arrayForwardLooking) == 12) {
                $scoreFL = $this->valutazioneFL($arrayForwardLooking);
            } else {
                $arrayForwardLooking = array(
                    "forwardLooking1" => 0,
                    "forwardLooking2" => 0,
                    "forwardLooking3" => 0,
                    "forwardLooking4" => 0,
                    "forwardLooking5" => 0,
                    "forwardLooking6" => 0,
                    "forwardLooking7" => 0,
                    "forwardLooking8" => 0,
                    "forwardLooking9" => 0,
                    "forwardLooking10" => 0,
                    "forwardLooking11" => 0,
                    "forwardLooking12" => 0,
                );
                $scoreFL = array('Giudizio' => '', 'Valore' => '0');
            }

            if (Bilanci::count() != 0) {

                $bilancioData = $this->basic($id);

                if (DB::table('questionario')->count() != 0) {
                    $ASISfinalScore = array("Score" => ($bilancioData['Giudizi']['Score'] * 0.25) + ($scoreCR * 0.25) + ($scoreASIS['3'] * 0.1) + ($scoreASIS['4'] * 0.1) + ($scoreASIS['5'] * 0.15) + ($scoreASIS['6'] * 0.15));

                    $rangeGiudizi = array(
                        0 => array("Min" => 0, "Max" => 0.14, "Giudizio" => "Default"),
                        1 => array("Min" => 0.14, "Max" => 0.28, "Giudizio" => "Situazione Grave"),
                        2 => array("Min" => 0.28, "Max" => 0.42, "Giudizio" => "Alert"),
                        3 => array("Min" => 0.42, "Max" => 0.56, "Giudizio" => "Rischio alert"),
                        4 => array("Min" => 0.56, "Max" => 0.70, "Giudizio" => "Fragilità elevata"),
                        5 => array("Min" => 0.70, "Max" => 0.85, "Giudizio" => "Fragilità"),
                        6 => array("Min" => 0.85, "Max" => 1, "Giudizio" => "Solidità")
                    );

                    foreach ($rangeGiudizi as $index => $ranges) {
                        if ($ASISfinalScore["Score"] >= $ranges["Min"] && $ASISfinalScore["Score"] < $ranges["Max"]) {
                            $ASISfinalScore["Giudizio"] = $ranges['Giudizio'];
                            $ASISfinalScore["Index"] = $index;
                        }
                    }

                    $generalScore = array();

                    if ($scoreASIS['6'] < 0.75) {
                        $generalScore["Giudizio"] = $rangeGiudizi[$ASISfinalScore["Index"] - 1]['Giudizio'];
                        $generalScore["Index"] = $ASISfinalScore["Index"] - 1;
                    } else {
                        $generalScore = $ASISfinalScore;
                    }


                    if ($scoreFL['Giudizio'] == 'Miglioramento') {
                        if ($generalScore["Index"] != 6) {
                            $generalScore["Giudizio"] = $rangeGiudizi[$ASISfinalScore["Index"] + 1]['Giudizio'];
                            $generalScore["Index"] = $generalScore["Index"] + 1;
                        }
                    } else if ($scoreFL['Giudizio'] == 'Peggioramento') {
                        if ($generalScore["Index"] != 0) {
                            $generalScore["Giudizio"] = $rangeGiudizi[$ASISfinalScore["Index"] - 1]['Giudizio'];
                            $generalScore["Index"] = $generalScore["Index"] - 1;
                        }
                    }
                }
				
				$analisiBilancioGiudizio = null;
				
				if($bilancioData['Giudizi']['Score'] >= 0 && $bilancioData['Giudizi']['Score'] < 0.14){
					$analisiBilancioGiudizio = "Default";
				}
				else if($bilancioData['Giudizi']['Score'] >= 0.14 && $bilancioData['Giudizi']['Score'] < 0.28){
					 $analisiBilancioGiudizio = "Situazione Grave";
				}
				else if($bilancioData['Giudizi']['Score'] >= 0.28 && $bilancioData['Giudizi']['Score'] < 0.42){
					$analisiBilancioGiudizio = "Alert";
				}
				else if($bilancioData['Giudizi']['Score'] >= 0.42 && $bilancioData['Giudizi']['Score'] < 0.56){
					$analisiBilancioGiudizio = "Rischio alert";
				}
				else if($bilancioData['Giudizi']['Score'] >= 0.56 && $bilancioData['Giudizi']['Score'] < 0.70){
					$analisiBilancioGiudizio = "Fragilità elevata";
				}
				else if($bilancioData['Giudizi']['Score'] >= 0.70 && $bilancioData['Giudizi']['Score'] < 0.85){
					$analisiBilancioGiudizio = "Fragilità";
				}
				else if($bilancioData['Giudizi']['Score'] >= 0.85 && $bilancioData['Giudizi']['Score'] <= 1){
					$analisiBilancioGiudizio = "Solidità";
				}
				$analisiCR = null;
				if($punteggioCR >= 0 && $punteggioCR < 0.14){
					$analisiCR = "Default";
				}
				else if($punteggioCR >= 0.14 && $punteggioCR < 0.28){
					$analisiCR = "Situazione Grave";
				}
				else if($punteggioCR >= 0.28 && $punteggioCR < 0.42){
					$analisiCR = "Alert";
				}
				else if($punteggioCR >= 0.42 && $punteggioCR < 0.56){
					$analisiCR = "Rischio alert";
				}
				else if($punteggioCR >= 0.56 && $punteggioCR < 0.70){
					$analisiCR = "Fragilità elevata";
				}
				else if($punteggioCR >= 0.70 && $punteggioCR < 0.85){
					$analisiCR = "Fragilità";
				}
				else if($punteggioCR >= 0.85 && $punteggioCR <= 1){
					$analisiCR = "Solidità";
				}
				$minacceRapportiComerciali = null;
				if($scoreASIS['3'] >= 0 && $scoreASIS['3'] < 0.14){
					$minacceRapportiComerciali = "Default";
				}
				else if($scoreASIS['3'] >= 0.14 && $scoreASIS['3'] < 0.28){
				    $minacceRapportiComerciali = "Situazione Grave";
				}
				else if($scoreASIS['3'] >= 0.28 && $scoreASIS['3'] < 0.42){
					$minacceRapportiComerciali = "Alert";
				}
				else if($scoreASIS['3'] >= 0.42 && $scoreASIS['3'] < 0.56){
					$minacceRapportiComerciali = "Rischio alert";
				}
				else if($scoreASIS['3'] >= 0.56 && $scoreASIS['3'] < 0.70){
					$minacceRapportiComerciali = "Fragilità elevata";
				}
				else if($scoreASIS['3'] >= 0.70 && $scoreASIS['3'] < 0.85){
					$minacceRapportiComerciali = "Fragilità";
				}
				else if($scoreASIS['3'] >= 0.85 && $scoreASIS['3'] <= 1){
					$minacceRapportiComerciali = "Solidità";
				}
				
				$MinacceGestioneAziendale = null;
				if($scoreASIS['4'] >= 0 && $scoreASIS['4'] < 0.14){
					$MinacceGestioneAziendale = "Default";
				}
				else if($scoreASIS['4'] >= 0.14 && $scoreASIS['4'] < 0.28){
					$MinacceGestioneAziendale = "Situazione Grave";
				}
				else if($scoreASIS['4'] >= 0.28 && $scoreASIS['4'] < 0.42){
					$MinacceGestioneAziendale = "Alert";
				}
				else if($scoreASIS['4'] >= 0.42 && $scoreASIS['4'] < 0.56){
					$MinacceGestioneAziendale = "Rischio alert";
				}
				else if($scoreASIS['4'] >= 0.56 && $scoreASIS['4'] < 0.70){
					$MinacceGestioneAziendale = "Fragilità elevata";
				}
				else if($scoreASIS['4'] >= 0.70 && $scoreASIS['4'] < 0.85){
					$MinacceGestioneAziendale = "Fragilità";
				}
				else if($scoreASIS['4'] >= 0.85 && $scoreASIS['4'] <= 1){
					$MinacceGestioneAziendale = "Solidità";
				}
				$minacceERischiCaratteristici = null;
				if($scoreASIS['6'] >= 0 && $scoreASIS['6'] < 0.14){
					$minacceERischiCaratteristici = "Default";
				}
				else if($scoreASIS['6'] >= 0.14 && $scoreASIS['6'] < 0.28){
					$minacceERischiCaratteristici = "Situazione Grave";
				}
				else if($scoreASIS['6'] >= 0.28 && $scoreASIS['6'] < 0.42){
					$minacceERischiCaratteristici = "Alert";
				}
				else if($scoreASIS['6'] >= 0.42 && $scoreASIS['6'] < 0.56){
					$minacceERischiCaratteristici = "Rischio alert";
				}
				else if($scoreASIS['6'] >= 0.56 && $scoreASIS['6'] < 0.70){
					$minacceERischiCaratteristici = "Fragilità elevata";
				}
				else if($scoreASIS['6'] >= 0.70 && $scoreASIS['6'] < 0.85){
					$minacceERischiCaratteristici = "Fragilità";
				}
				else if($scoreASIS['6'] >= 0.85 && $scoreASIS['6'] <= 1){
					$minacceERischiCaratteristici = "Solidità";
				}
				$MinacceEventiPregiudizievoli = null;
				if($scoreASIS['5'] >= 0 && $scoreASIS['5'] < 0.14){
					$MinacceEventiPregiudizievoli = "Default";
				}
				else if($scoreASIS['5'] >= 0.14 && $scoreASIS['5'] < 0.28){
					$MinacceEventiPregiudizievoli = "Situazione Grave";
				}
				else if($scoreASIS['5'] >= 0.28 && $scoreASIS['5'] < 0.42){
					$MinacceEventiPregiudizievoli = "Alert";
				}
				else if($scoreASIS['5'] >= 0.42 && $scoreASIS['5'] < 0.56){
					$MinacceEventiPregiudizievoli = "Rischio alert";
				}
				else if($scoreASIS['5'] >= 0.56 && $scoreASIS['5'] < 0.70){
					$MinacceEventiPregiudizievoli = "Fragilità elevata";
				}
				else if($scoreASIS['5'] >= 0.70 && $scoreASIS['5'] < 0.85){
					$MinacceEventiPregiudizievoli = "Fragilità";
				}
				else if($scoreASIS['5'] >= 0.85 && $scoreASIS['5'] <= 1){
					$MinacceEventiPregiudizievoli = "Solidità";
				}
				$ProfiloRischioASIS = null;
				if($ASISfinalScore) {
					$ProfiloRischioASIS = $ASISfinalScore['Giudizio'];
				}
				$questionarioToBe = null;
				if($ASISfinalScore) {
					$questionarioToBe = $scoreFL['Giudizio'];
				}
				$ProfiloRischioComplessivo = null;
				if(isset($generalScore['Giudizio'])) {
					$ProfiloRischioComplessivo = $generalScore['Giudizio'];
				}  

	
				$giudiziFinali = [
					'AnalisiBilancio' => $analisiBilancioGiudizio,
					'AnalisiCentraleRischi' => $analisiCR,
					'MinacceRapportiCommerciali' => $minacceRapportiComerciali,
					'MinacceGestioneAziendali' => $MinacceGestioneAziendale,
					'MinacceErarialiRischiCaratteristici' => $minacceERischiCaratteristici,
					'MinacceEventiPregiudizievoli' => $MinacceEventiPregiudizievoli,
					'ProfiloRischioASIS' => $ProfiloRischioASIS,
					'QuestionarioToBe' => $questionarioToBe,
					'ProfiloRischioComplessivo' => $ProfiloRischioComplessivo,
				];
				
				$questionarioAsIs = [];
				
					if(count($arrayQuestionario) > 0) {
						if($arrayQuestionario['3-1']['Result'] == 'Si') {
							$questionarioAsIs['MinacceRapportiCommerciali']['1'] = "Si";
							if(isset($arrayQuestionario['3-1']['Details'])) {
								$questionarioAsIs['MinacceRapportiCommerciali']['1-details'] = $arrayQuestionario['3-1']['Details'];
							}
						} else {
							$questionarioAsIs['MinacceRapportiCommerciali']['1'] = "No";
							if(isset($arrayQuestionario['3-1']['Details'])) {
								$questionarioAsIs['MinacceRapportiCommerciali']['1-details'] = $arrayQuestionario['3-1']['Details'];
							}
						}
						if($arrayQuestionario['3-2']['Result'] == 'Si') {
							$questionarioAsIs['MinacceRapportiCommerciali']['2'] = "Si";
							if(isset($arrayQuestionario['3-2']['Details'])) {
								$questionarioAsIs['MinacceRapportiCommerciali']['2-details'] = $arrayQuestionario['3-2']['Details'];
							}
						} else {
							$questionarioAsIs['MinacceRapportiCommerciali']['2'] = "No";
							if(isset($arrayQuestionario['3-2']['Details'])) {
								$questionarioAsIs['MinacceRapportiCommerciali']['2-details'] = $arrayQuestionario['3-2']['Details'];
							}							
						}
						if($arrayQuestionario['3-3']['Result'] == 'Si') {
							$questionarioAsIs['MinacceRapportiCommerciali']['3'] = "Si";
							if(isset($arrayQuestionario['3-3']['Details'])) {
								$questionarioAsIs['MinacceRapportiCommerciali']['3-details'] = $arrayQuestionario['3-3']['Details'];
							}							
						} else {
							$questionarioAsIs['MinacceRapportiCommerciali']['3'] = "No";
							if(isset($arrayQuestionario['3-3']['Details'])) {
								$questionarioAsIs['MinacceRapportiCommerciali']['3-details'] = $arrayQuestionario['3-3']['Details'];
							}							
						}
						if($arrayQuestionario['3-4']['Result'] == 'Si') {
							$questionarioAsIs['MinacceRapportiCommerciali']['4'] = "Si";
							if(isset($arrayQuestionario['3-4']['Details'])) {
								$questionarioAsIs['MinacceRapportiCommerciali']['4-details'] = $arrayQuestionario['3-4']['Details'];
							}							
						} else {
							$questionarioAsIs['MinacceRapportiCommerciali']['4'] = "No";
							if(isset($arrayQuestionario['3-4']['Details'])) {
								$questionarioAsIs['MinacceRapportiCommerciali']['4-details'] = $arrayQuestionario['3-4']['Details'];
							}							
						}
						if($arrayQuestionario['3-5']['Result'] == 'Si') {
							$questionarioAsIs['MinacceRapportiCommerciali']['5'] = "Si";
							if(isset($arrayQuestionario['3-5']['Details'])) {
								$questionarioAsIs['MinacceRapportiCommerciali']['5-details'] = $arrayQuestionario['3-5']['Details'];
							}							
						} else {
							$questionarioAsIs['MinacceRapportiCommerciali']['5'] = "No";
							if(isset($arrayQuestionario['3-5']['Details'])) {
								$questionarioAsIs['MinacceRapportiCommerciali']['5-details'] = $arrayQuestionario['3-5']['Details'];
							}							
						}
						if($arrayQuestionario['3-6']['Result'] == 'Si') {
							$questionarioAsIs['MinacceRapportiCommerciali']['6'] = "Si";
							if(isset($arrayQuestionario['3-6']['Details'])) {
								$questionarioAsIs['MinacceRapportiCommerciali']['6-details'] = $arrayQuestionario['3-6']['Details'];
							}								
						} else {
							$questionarioAsIs['MinacceRapportiCommerciali']['6'] = "No";
							if(isset($arrayQuestionario['3-6']['Details'])) {
								$questionarioAsIs['MinacceRapportiCommerciali']['6-details'] = $arrayQuestionario['3-6']['Details'];
							}								
						}
						if($arrayQuestionario['3-7']['Result'] == 'Si') {
							$questionarioAsIs['MinacceRapportiCommerciali']['7'] = "Si";
							if(isset($arrayQuestionario['3-7']['Details'])) {
								$questionarioAsIs['MinacceRapportiCommerciali']['7-details'] = $arrayQuestionario['3-7']['Details'];
							}
						} else {
							$questionarioAsIs['MinacceRapportiCommerciali']['7'] = "No";
							if(isset($arrayQuestionario['3-7']['Details'])) {
								$questionarioAsIs['MinacceRapportiCommerciali']['7-details'] = $arrayQuestionario['3-7']['Details'];
							}
						}
						if($arrayQuestionario['3-8']['Result'] == 'Si') {
							$questionarioAsIs['MinacceRapportiCommerciali']['8'] = "Si";
							if(isset($arrayQuestionario['3-8']['Details'])) {
								$questionarioAsIs['MinacceRapportiCommerciali']['8-details'] = $arrayQuestionario['3-8']['Details'];
							}
						} else {
							$questionarioAsIs['MinacceRapportiCommerciali']['8'] = "No";
							if(isset($arrayQuestionario['3-8']['Details'])) {
								$questionarioAsIs['MinacceRapportiCommerciali']['8-details'] = $arrayQuestionario['3-8']['Details'];
							}
						}
						if($arrayQuestionario['4-1']['Result'] == 'Si') {
							$questionarioAsIs['MinacceGestioneAziendale']['1'] = "Si";
							if(isset($arrayQuestionario['4-1']['Details'])) {
								$questionarioAsIs['MinacceGestioneAziendale']['1-details'] = $arrayQuestionario['4-1']['Details'];
							}
						} else {
							$questionarioAsIs['MinacceGestioneAziendale']['1'] = "No";
							if(isset($arrayQuestionario['4-1']['Details'])) {
								$questionarioAsIs['MinacceGestioneAziendale']['1-details'] = $arrayQuestionario['4-1']['Details'];
							}
						}
						if($arrayQuestionario['4-2']['Result'] == 'Si') {
							$questionarioAsIs['MinacceGestioneAziendale']['2'] = "Si";
							if(isset($arrayQuestionario['4-2']['Details'])) {
								$questionarioAsIs['MinacceGestioneAziendale']['2-details'] = $arrayQuestionario['4-2']['Details'];
							}
						} else {
							$questionarioAsIs['MinacceGestioneAziendale']['2'] = "No";
							if(isset($arrayQuestionario['4-2']['Details'])) {
								$questionarioAsIs['MinacceGestioneAziendale']['2-details'] = $arrayQuestionario['4-2']['Details'];
							}							
						}
						if($arrayQuestionario['4-3']['Result'] == 'Si') {
							$questionarioAsIs['MinacceGestioneAziendale']['3'] = "Si";
							if(isset($arrayQuestionario['4-3']['Details'])) {
								$questionarioAsIs['MinacceGestioneAziendale']['3-details'] = $arrayQuestionario['4-3']['Details'];
							}							
						} else {
							$questionarioAsIs['MinacceGestioneAziendale']['3'] = "No";
							if(isset($arrayQuestionario['4-3']['Details'])) {
								$questionarioAsIs['MinacceGestioneAziendale']['3-details'] = $arrayQuestionario['4-3']['Details'];
							}							
						}
						if($arrayQuestionario['4-4']['Result'] == 'Si') {
							$questionarioAsIs['MinacceGestioneAziendale']['4'] = "Si";
							if(isset($arrayQuestionario['4-4']['Details'])) {
								$questionarioAsIs['MinacceGestioneAziendale']['4-details'] = $arrayQuestionario['4-4']['Details'];
							}							
						} else {
							$questionarioAsIs['MinacceGestioneAziendale']['4'] = "No";
							if(isset($arrayQuestionario['4-4']['Details'])) {
								$questionarioAsIs['MinacceGestioneAziendale']['4-details'] = $arrayQuestionario['4-4']['Details'];
							}							
						}
						if($arrayQuestionario['4-5']['Result'] == 'Si') {
							$questionarioAsIs['MinacceGestioneAziendale']['5'] = "Si";
							if(isset($arrayQuestionario['4-5']['Details'])) {
								$questionarioAsIs['MinacceGestioneAziendale']['5-details'] = $arrayQuestionario['4-5']['Details'];
							}							
						} else {
							$questionarioAsIs['MinacceGestioneAziendale']['5'] = "No";
							if(isset($arrayQuestionario['4-5']['Details'])) {
								$questionarioAsIs['MinacceGestioneAziendale']['5-details'] = $arrayQuestionario['4-5']['Details'];
							}							
						}
						if($arrayQuestionario['4-6']['Result'] == 'Si') {
							$questionarioAsIs['MinacceGestioneAziendale']['6'] = "Si";
							if(isset($arrayQuestionario['4-6']['Details'])) {
								$questionarioAsIs['MinacceGestioneAziendale']['6-details'] = $arrayQuestionario['4-6']['Details'];
							}								
						} else {
							$questionarioAsIs['MinacceGestioneAziendale']['6'] = "No";
							if(isset($arrayQuestionario['4-6']['Details'])) {
								$questionarioAsIs['MinacceGestioneAziendale']['6-details'] = $arrayQuestionario['4-6']['Details'];
							}								
						}
						if($arrayQuestionario['4-7']['Result'] == 'Si') {
							$questionarioAsIs['MinacceGestioneAziendale']['7'] = "Si";
							if(isset($arrayQuestionario['4-7']['Details'])) {
								$questionarioAsIs['MinacceGestioneAziendale']['7-details'] = $arrayQuestionario['4-7']['Details'];
							}
						} else {
							$questionarioAsIs['MinacceGestioneAziendale']['7'] = "No";
							if(isset($arrayQuestionario['4-7']['Details'])) {
								$questionarioAsIs['MinacceGestioneAziendale']['7-details'] = $arrayQuestionario['4-7']['Details'];
							}
						}
						if($arrayQuestionario['4-8']['Result'] == 'Si') {
							$questionarioAsIs['MinacceGestioneAziendale']['8'] = "Si";
							if(isset($arrayQuestionario['4-8']['Details'])) {
								$questionarioAsIs['MinacceGestioneAziendale']['8-details'] = $arrayQuestionario['4-8']['Details'];
							}
						} else {
							$questionarioAsIs['MinacceGestioneAziendale']['8'] = "No";
							if(isset($arrayQuestionario['4-8']['Details'])) {
								$questionarioAsIs['MinacceGestioneAziendale']['8-details'] = $arrayQuestionario['4-8']['Details'];
							}
						}	
						if($arrayQuestionario['4-9']['Result'] == 'Si') {
							$questionarioAsIs['MinacceGestioneAziendale']['9'] = "Si";
							if(isset($arrayQuestionario['4-9']['Details'])) {
								$questionarioAsIs['MinacceGestioneAziendale']['9-details'] = $arrayQuestionario['4-9']['Details'];
							}
						} else {
							$questionarioAsIs['MinacceGestioneAziendale']['9'] = "No";
							if(isset($arrayQuestionario['4-9']['Details'])) {
								$questionarioAsIs['MinacceGestioneAziendale']['9-details'] = $arrayQuestionario['4-9']['Details'];
							}
						}	
						if($arrayQuestionario['4-10']['Result'] == 'Si') {
							$questionarioAsIs['MinacceGestioneAziendale']['10'] = "Si";
							if(isset($arrayQuestionario['4-10']['Details'])) {
								$questionarioAsIs['MinacceGestioneAziendale']['10-details'] = $arrayQuestionario['4-10']['Details'];
							}
						} else {
							$questionarioAsIs['MinacceGestioneAziendale']['10'] = "No";
							if(isset($arrayQuestionario['4-10']['Details'])) {
								$questionarioAsIs['MinacceGestioneAziendale']['10-details'] = $arrayQuestionario['4-10']['Details'];
							}
						}
						if($arrayQuestionario['5-1']['Result'] == 'Si') {
							$questionarioAsIs['MinacceErarialiRischiCaratteristici']['1'] = "Si";
							if(isset($arrayQuestionario['5-1']['Details'])) {
								$questionarioAsIs['MinacceErarialiRischiCaratteristici']['1details'] = $arrayQuestionario['5-1']['Details'];
							}
						} else {
							$questionarioAsIs['MinacceErarialiRischiCaratteristici']['1'] = "No";
							if(isset($arrayQuestionario['5-1']['Details'])) {
								$questionarioAsIs['MinacceErarialiRischiCaratteristici']['1details'] = $arrayQuestionario['5-1']['Details'];
							}
						}	
						if($arrayQuestionario['5-2']['Result'] == 'Si') {
							$questionarioAsIs['MinacceErarialiRischiCaratteristici']['2'] = "Si";
							if(isset($arrayQuestionario['5-2']['Details'])) {
								$questionarioAsIs['MinacceErarialiRischiCaratteristici']['2details'] = $arrayQuestionario['5-2']['Details'];
							}
						} else {
							$questionarioAsIs['MinacceErarialiRischiCaratteristici']['2'] = "No";
							if(isset($arrayQuestionario['5-2']['Details'])) {
								$questionarioAsIs['MinacceErarialiRischiCaratteristici']['2details'] = $arrayQuestionario['5-2']['Details'];
							}
						}	
						if($arrayQuestionario['5-3']['Result'] == 'Si') {
							$questionarioAsIs['MinacceErarialiRischiCaratteristici']['3'] = "Si";
							if(isset($arrayQuestionario['5-3']['Details'])) {
								$questionarioAsIs['MinacceErarialiRischiCaratteristici']['3details'] = $arrayQuestionario['5-3']['Details'];
							}
						} else {
							$questionarioAsIs['MinacceErarialiRischiCaratteristici']['3'] = "No";
							if(isset($arrayQuestionario['5-3']['Details'])) {
								$questionarioAsIs['MinacceErarialiRischiCaratteristici']['3details'] = $arrayQuestionario['5-3']['Details'];
							}
						}	
						if($arrayQuestionario['5-4']['Result'] == 'Si') {
							$questionarioAsIs['MinacceErarialiRischiCaratteristici']['4'] = "Si";
							if(isset($arrayQuestionario['5-4']['Details'])) {
								$questionarioAsIs['MinacceErarialiRischiCaratteristici']['4details'] = $arrayQuestionario['5-4']['Details'];
							}
						} else {
							$questionarioAsIs['MinacceErarialiRischiCaratteristici']['4'] = "No";
							if(isset($arrayQuestionario['5-4']['Details'])) {
								$questionarioAsIs['MinacceErarialiRischiCaratteristici']['4details'] = $arrayQuestionario['5-4']['Details'];
							}
						}
						if($arrayQuestionario['6-1']['Result'] == 'Si') {
							$questionarioAsIs['MinacceEventiPregiudizievoli']['1'] = "Si";
							if(isset($arrayQuestionario['6-1']['Details'])) {
								$questionarioAsIs['MinacceEventiPregiudizievoli']['1details'] = $arrayQuestionario['6-1']['Details'];
							}
						} else {
							$questionarioAsIs['MinacceEventiPregiudizievoli']['1'] = "No";
							if(isset($arrayQuestionario['6-1']['Details'])) {
								$questionarioAsIs['MinacceEventiPregiudizievoli']['1details'] = $arrayQuestionario['6-1']['Details'];
							}
						}
						if($arrayQuestionario['6-2']['Result'] == 'Si') {
							$questionarioAsIs['MinacceEventiPregiudizievoli']['2'] = "Si";
							if(isset($arrayQuestionario['6-2']['Details'])) {
								$questionarioAsIs['MinacceEventiPregiudizievoli']['2details'] = $arrayQuestionario['6-2']['Details'];
							}
						} else {
							$questionarioAsIs['MinacceEventiPregiudizievoli']['2'] = "No";
							if(isset($arrayQuestionario['6-2']['Details'])) {
								$questionarioAsIs['MinacceEventiPregiudizievoli']['2details'] = $arrayQuestionario['6-2']['Details'];
							}
						}	
						if($arrayQuestionario['6-3']['Result'] == 'Si') {
							$questionarioAsIs['MinacceErarialiRischiCaratteristici']['3'] = "Si";
							if(isset($arrayQuestionario['6-3']['Details'])) {
								$questionarioAsIs['MinacceErarialiRischiCaratteristici']['3details'] = $arrayQuestionario['6-3']['Details'];
							}
						} else {
							$questionarioAsIs['MinacceEventiPregiudizievoli']['3'] = "No";
							if(isset($arrayQuestionario['6-3']['Details'])) {
								$questionarioAsIs['MinacceEventiPregiudizievoli']['3details'] = $arrayQuestionario['6-3']['Details'];
							}
						}	
						if($arrayQuestionario['6-4']['Result'] == 'Si') {
							$questionarioAsIs['MinacceEventiPregiudizievoli']['4'] = "Si";
							if(isset($arrayQuestionario['6-4']['Details'])) {
								$questionarioAsIs['MinacceEventiPregiudizievoli']['4details'] = $arrayQuestionario['6-4']['Details'];
							}
						} else {
							$questionarioAsIs['MinacceEventiPregiudizievoli']['4'] = "No";
							if(isset($arrayQuestionario['6-4']['Details'])) {
								$questionarioAsIs['MinacceEventiPregiudizievoli']['4details'] = $arrayQuestionario['6-4']['Details'];
							}
						}	
						if($arrayQuestionario['6-5']['Result'] == 'Si') {
							$questionarioAsIs['MinacceEventiPregiudizievoli']['5'] = "Si";
							if(isset($arrayQuestionario['6-5']['Details'])) {
								$questionarioAsIs['MinacceEventiPregiudizievoli']['5details'] = $arrayQuestionario['6-5']['Details'];
							}
						} else {
							$questionarioAsIs['MinacceEventiPregiudizievoli']['5'] = "No";
							if(isset($arrayQuestionario['6-5']['Details'])) {
								$questionarioAsIs['MinacceEventiPregiudizievoli']['5details'] = $arrayQuestionario['6-5']['Details'];
							}
						}	
						if($arrayQuestionario['6-6']['Result'] == 'Si') {
							$questionarioAsIs['MinacceEventiPregiudizievoli']['6'] = "Si";
							if(isset($arrayQuestionario['6-6']['Details'])) {
								$questionarioAsIs['MinacceEventiPregiudizievoli']['6details'] = $arrayQuestionario['6-6']['Details'];
							}
						} else {
							$questionarioAsIs['MinacceEventiPregiudizievoli']['6'] = "No";
							if(isset($arrayQuestionario['6-6']['Details'])) {
								$questionarioAsIs['MinacceEventiPregiudizievoli']['6details'] = $arrayQuestionario['6-6']['Details'];
							}
						}
					}
				
				$forwardLooking = [];
				
					if($arrayForwardLooking['forwardLooking1'] == 1) { 
						$forwardLooking['response1'] = "Costante";
					} 
                    if($arrayForwardLooking['forwardLooking1'] == 2) { 
						$forwardLooking['response1'] = "In aumento";
					}  	

  					if($arrayForwardLooking['forwardLooking1'] == 3) { 
						$forwardLooking['response1'] = "In diminuzione";
					}
					if($arrayForwardLooking['forwardLooking2'] == 1) { 
						$forwardLooking['response2'] = "Si, per nuovi investimenti";
					} 
                    if($arrayForwardLooking['forwardLooking2'] == 2) { 
						$forwardLooking['response2'] = "No";
					}  	

  					if($arrayForwardLooking['forwardLooking2'] == 3) { 
						$forwardLooking['response2'] = "Si, perchè serve liquidità";
					}
					if($arrayForwardLooking['forwardLooking3'] == 1) { 
						$forwardLooking['response3'] = "Costante";
					} 
                    if($arrayForwardLooking['forwardLooking3'] == 2) { 
						$forwardLooking['response3'] = "In aumento";
					}  	

  					if($arrayForwardLooking['forwardLooking3'] == 3) { 
						$forwardLooking['response3'] = "In diminuzione";
					}
					if($arrayForwardLooking['forwardLooking4'] == 1) { 
						$forwardLooking['response4'] = "Fra 10 e 30";
					} 
                    if($arrayForwardLooking['forwardLooking4'] == 2) { 
						$forwardLooking['response4'] = "Più di 30";
					}  	

  					if($arrayForwardLooking['forwardLooking4'] == 3) { 
						$forwardLooking['response4'] = "Meno di 10";
					}
					if($arrayForwardLooking['forwardLooking5'] == 1) { 
						$forwardLooking['response5'] = "Su base pluriennale";
					} 
                    if($arrayForwardLooking['forwardLooking5'] == 2) { 
						$forwardLooking['response5'] = "Su base annuale";
					}  	

  					if($arrayForwardLooking['forwardLooking5'] == 3) { 
						$forwardLooking['response5'] = "No";
					}
					if($arrayForwardLooking['forwardLooking6'] == 1) { 
						$forwardLooking['response6'] = "Si, ma non rilevanti";
					} 
                    if($arrayForwardLooking['forwardLooking6'] == 2) { 
						$forwardLooking['response6'] = "No";
					}  	

  					if($arrayForwardLooking['forwardLooking6'] == 3) { 
						$forwardLooking['response6'] = "Si";
					}	
					if($arrayForwardLooking['forwardLooking7'] == 1) { 
						$forwardLooking['response7'] = "Si, per aumento previsto di utilizzi";
					} 
                    if($arrayForwardLooking['forwardLooking7'] == 2) { 
						$forwardLooking['response7'] = "No";
					}  	

  					if($arrayForwardLooking['forwardLooking7'] == 3) { 
						$forwardLooking['response7'] = "Si, li usiamo sempre al limite";
					}	
					if($arrayForwardLooking['forwardLooking8'] == 1) { 
						$forwardLooking['response8'] = "Forse si, ma potrebbero esserci difficoltà";
					} 
                    if($arrayForwardLooking['forwardLooking8'] == 2) { 
						$forwardLooking['response8'] = "Si";
					}  	

  					if($arrayForwardLooking['forwardLooking8'] == 3) { 
						$forwardLooking['response8'] = "No, serve sicuramente liquidità";
					}	
					if($arrayForwardLooking['forwardLooking9'] == 1) { 
						$forwardLooking['response9'] = "Probabilmente si";
					} 
                    if($arrayForwardLooking['forwardLooking9'] == 2) { 
						$forwardLooking['response9'] = "Si";
					}  	

  					if($arrayForwardLooking['forwardLooking9'] == 3) { 
						$forwardLooking['response9'] = "No";
					}		
					if($arrayForwardLooking['forwardLooking10'] == 1) { 
						$forwardLooking['response10'] = "No";
					} 

  					if($arrayForwardLooking['forwardLooking10'] == 3) { 
						$forwardLooking['response10'] = "Si, i tempi di pagamento ai fornitori sono più corti";
					}
					if($arrayForwardLooking['forwardLooking11'] == 1) { 
						$forwardLooking['response11'] = "Si, ma evitabili";
					} 
                    if($arrayForwardLooking['forwardLooking11'] == 2) { 
						$forwardLooking['response11'] = "No";
					}  	

  					if($arrayForwardLooking['forwardLooking11'] == 3) { 
						$forwardLooking['response11'] = "Si";
					}	
					if($arrayForwardLooking['forwardLooking12'] == 1) { 
						$forwardLooking['response12'] = "No";
					} 
                    if($arrayForwardLooking['forwardLooking12'] == 2) { 
						$forwardLooking['response12'] = "Si, prevediamo maggior utilizzo";
					}  	

  					if($arrayForwardLooking['forwardLooking12'] == 3) { 
						$forwardLooking['response12'] = "Si, usiamo sempre al limite le disponibilità";
					}					
		
				$acidTestValue = 0;
				$acidTestScoring = null;
				$acidTestGiudizio = "Ottimo";
				if(isset($bilancioData['Indici']['Acid_Test'])) { 
					$acidTestValue = $bilancioData['Indici']['Acid_Test'];
					$acidTestScoring = $bilancioData['Giudizi']['Giudizi']['Acid Test']['Scoring'];
					$acidTestGiudizio = $bilancioData['Giudizi']['Giudizi']['Acid Test']['Giudizio'];
				}
		
				// dd($bilancioData['Giudizi']['Giudizi']['Andamento del fatturato']);
					$dataAllerta = [
						"id" => $id,
						"andamentoDelFatturato" => [
							'Valore' =>  $bilancioData['Indici']['Andamento_del_fatturato'],
							'Score' => $bilancioData['Giudizi']['Giudizi']['Andamento del fatturato']['Scoring'],
							'Giudizio' => $bilancioData['Giudizi']['Giudizi']['Andamento del fatturato']['Giudizio'],
						],
						"AndamentoDelMOL" => [
							'Valore' =>  $bilancioData['Indici']['Andamento_del_MOL'],
							'Score' => $bilancioData['Giudizi']['Giudizi']['Andamento del MOL']['Scoring'],
							'Giudizio' => $bilancioData['Giudizi']['Giudizi']['Andamento del MOL']['Giudizio'],						
						],
						"ROI" => [
							'Valore' =>  $bilancioData['Indici']['ROI'],
							'Score' => $bilancioData['Giudizi']['Giudizi']['ROI']['Scoring'],
							'Giudizio' => $bilancioData['Giudizi']['Giudizi']['ROI']['Giudizio'],						
						],
						"ROS" => [
							'Valore' =>  $bilancioData['Indici']['ROS'],
							'Score' => $bilancioData['Giudizi']['Giudizi']['ROS']['Scoring'],
							'Giudizio' => $bilancioData['Giudizi']['Giudizi']['ROS']['Giudizio'],						
						],	
						"ROE" => [
							'Valore' =>  $bilancioData['Indici']['ROE'],
							'Score' => $bilancioData['Giudizi']['Giudizi']['ROE']['Scoring'],
							'Giudizio' => $bilancioData['Giudizi']['Giudizi']['ROE']['Giudizio'],						
						],		
						"EBITDAFatturato" => [
							'Valore' =>  $bilancioData['Indici']['EBITDA_Fatturato'],
							'Score' => $bilancioData['Giudizi']['Giudizi']['EBITDA Fatturato']['Scoring'],
							'Giudizio' => $bilancioData['Giudizi']['Giudizi']['EBITDA Fatturato']['Giudizio'],						
						],	
						"AndamentoDeiMezziPropri" => [
							'Valore' =>  $bilancioData['Indici']['Andamento_dei_mezzi_propri'],
							'Score' => $bilancioData['Giudizi']['Giudizi']['Andamento dei mezzi propri']['Scoring'],
							'Giudizio' => $bilancioData['Giudizi']['Giudizi']['Andamento dei mezzi propri']['Giudizio'],					
						],	
						"MargineStrutturaPrimario" => [
							'Valore' =>  $bilancioData['Indici']['Margine_Struttura_Primario'],
							'Score' => $bilancioData['Giudizi']['Giudizi']['Margine Struttura Primario']['Scoring'],
							'Giudizio' => $bilancioData['Giudizi']['Giudizi']['Margine Struttura Primario']['Giudizio'],					
						],				
						"MargineStrutturaSecondario" => [
							'Valore' =>  $bilancioData['Indici']['Margine_Struttura_Secondario'],
							'Score' => $bilancioData['Giudizi']['Giudizi']['Margine Struttura Secondario']['Scoring'],
							'Giudizio' => $bilancioData['Giudizi']['Giudizi']['Margine Struttura Secondario']['Giudizio'],					
						],		
						"CurrentRatio" => [
							'Valore' =>  $bilancioData['Indici']['Current_Ratio'],
							'Score' => $bilancioData['Giudizi']['Giudizi']['Current Ratio']['Scoring'],
							'Giudizio' => $bilancioData['Giudizi']['Giudizi']['Current Ratio']['Giudizio'],					
						],	
						"AcidTest" => [
							'Valore' =>  $acidTestValue,
							'Score' => $acidTestScoring,
							'Giudizio' => $acidTestGiudizio,					
						],		
						"AutonomiaFinanziaria" => [
							'Valore' =>  $bilancioData['Indici']['Autonomia_Finanziaria'],
							'Score' => $bilancioData['Giudizi']['Giudizi']['Autonomia Finanziaria']['Scoring'],
							'Giudizio' => $bilancioData['Giudizi']['Giudizi']['Autonomia Finanziaria']['Giudizio'],					
						],			
						"LivelloInvestimentiAziendali" => [
							'Valore' =>  $bilancioData['Indici']['Livello_investimenti_aziendali'],
							'Score' => $bilancioData['Giudizi']['Giudizi']['Livello investimenti aziendali']['Scoring'],
							'Giudizio' => $bilancioData['Giudizi']['Giudizi']['Livello investimenti aziendali']['Giudizio'],				
						],			
						"PFN_EBITDA" => [
							'Valore' =>  $bilancioData['Indici']['PFN_EBITDA'],
							'Score' => $bilancioData['Giudizi']['Giudizi']['PFN EBITDA']['Scoring'],
							'Giudizio' => $bilancioData['Giudizi']['Giudizi']['PFN EBITDA']['Giudizio'],				
						],
						"OF_Fatturato" => [
							'Valore' =>  $bilancioData['Indici']['OF_Fatturato'],
							'Score' => $bilancioData['Giudizi']['Giudizi']['Copertura Lorda OF']['Scoring'],
							'Giudizio' => $bilancioData['Giudizi']['Giudizi']['Copertura Lorda OF']['Giudizio'],				
						],	
						"EBIT_OF" => [
							'Valore' =>  $bilancioData['Indici']['EBIT_OF'],
							'Score' => $bilancioData['Giudizi']['Giudizi']['EBIT OF']['Scoring'],
							'Giudizio' => $bilancioData['Giudizi']['Giudizi']['EBIT OF']['Giudizio'],				
						],
						"CoperturaLordaOF" => [
							'Valore' =>  $bilancioData['Indici']['Copertura_Lorda_OF'],
							'Score' => $bilancioData['Giudizi']['Giudizi']['Copertura Lorda OF']['Scoring'],
							'Giudizio' => $bilancioData['Giudizi']['Giudizi']['Copertura Lorda OF']['Giudizio'],				
						],							
						"CostoDelPersonale" => [
							'Valore' =>  $bilancioData['Indici']['Costo_del_personale'],
							'Score' => $bilancioData['Giudizi']['Giudizi']['Costo del personale']['Scoring'],
							'Giudizio' => $bilancioData['Giudizi']['Giudizi']['Costo del personale']['Giudizio'],				
						],
						"CFAttivo" => [
							'Valore' =>  $bilancioData['Indici']['CF_Attivo'],
							'Score' => $bilancioData['Giudizi']['Giudizi']['CF Attivo']['Scoring'],
							'Giudizio' => $bilancioData['Giudizi']['Giudizi']['CF Attivo']['Giudizio'],				
						],	
						"IndiceDiIndebitamento" => [
							'Valore' =>  $bilancioData['Indici']['Indice_di_Indebitamento'],
							'Score' => $bilancioData['Giudizi']['Giudizi']['Indice di Indebitamento']['Scoring'],
							'Giudizio' => $bilancioData['Giudizi']['Giudizi']['Indice di Indebitamento']['Giudizio'],				
						],	
						"SaldoDeiDebitiVersoIlFisco" => [
							'Valore' =>  $bilancioData['Indici']['Saldo_dei_Debiti_verso_il_Fisco'],
							'Score' => $bilancioData['Giudizi']['Giudizi']['Saldo dei Debiti verso il Fisco']['Scoring'],
							'Giudizio' => $bilancioData['Giudizi']['Giudizi']['Saldo dei Debiti verso il Fisco']['Giudizio'],				
						],
						"ASISfinalScore" => $ASISfinalScore,
						"generalScore" => $generalScore,
						"bilancioData" => $bilancioData,
						"punteggioCR" => $punteggioCR,
						"alerts" => $alerts,
						"arrayQuestionario" => $questionarioAsIs,
						"arrayForwardLooking" => $forwardLooking,
						"scoreFL" => $scoreFL,
						"scoreASIS" => $scoreASIS,
						"GiudizioFinale" => $giudiziFinali,
					];

				
		$printPDF = new printpdf;
	 	$printPDF->currentPayload = $dataAllerta;
	 	$documentId = $printPDF->generateDocument('allerta');
	 	sleep(5);

	 	return $printPDF->getDocumentData($documentId);  
				
            } else {
                $msg = "Non è stata caricata nessun Bilancio";
                return view('allerta.empty', compact(['msg']));
            }
        }
	
	/*	$printPDF = new printpdf;
	 
	 	$documentId = $printPDF->generateDocument('allerta', $idBilancio);
	 	sleep(5);

	 	return $printPDF->getDocumentData($documentId);  */
    }
}
