<?php

namespace App\Helpers\Bilanci;

use App\Http\Requests;
use App;
use App\Models\Bilanci;
use App\Models\Basic;
use App\Models\Account;
use App\Models\cr;
use App\Models\soglie;
use App\Models\range;
use App\Models\Roe;
use Illuminate\Support\Facades\DB;
use DateTime;
use Exception;
use App\Helpers\Bilanci\BilanciCalculationsHelper;
use Illuminate\Support\Facades\Date;
use App\Models\CustomLog;

class BilanciHelper
{

    public function getRangeValutazioni()
    {
        return array(
            "Rischio Elevato" => 0.1,
            "Situazione Critica" => 0.3,
            "Buono" => 0.6,
            "Ottimo" => 1
        );
    }

    public function getSettori()
    {
        return array(
            "Industria" => array(
                "ESTRAZIONE, MANIFATTURA, PROD. ENERGIA E GAS" => 1,
                "AGRICOLTURA, SIVICOLTURA E PESCA" => 1,
                "FORN. ACQUA E RETI FOGNARIE RIFIUTI, TRASM. ENERGIA/GAS" => 1,
                "COSTRUZIONE DI EDIFICI" => 1,
                "INGEGNERIA CIVILE, COSTRUZIONI SPECIALIZZATE" => 1
            ),
            "Commercio" => array(
                "COMM INGROSSO e DETT AUTOVEICOLI, COMM INGROSSO, DISTRIB. ENERGIA/GAS" => 1,
                "COMM. DETTAGLIO, BAR E RISTORANTI" => 1
            ),
            "Servizi" => array(
                "SERVIZI ALLE PERSONE" => 1,
                "SERVIZI ALLE IMPRESE" => 1,
                "TRASPORTO E MAGAZZINAGGIO, HOTEL" => 1
            )
        );
    }

    public function getTipiAziende()
    {
        return array(
            "AGRICOLTURA, SIVICOLTURA E PESCA",
            "ESTRAZIONE, MANIFATTURA, PROD. ENERGIA E GAS",
            "FORN. ACQUA E RETI FOGNARIE RIFIUTI, TRASM. ENERGIA/GAS",
            "COSTRUZIONE DI EDIFICI",
            "INGEGNERIA CIVILE, COSTRUZIONI SPECIALIZZATE",
            "COMM INGROSSO e DETT AUTOVEICOLI, COMM INGROSSO, DISTRIB. ENERGIA/GAS",
            "COMM. DETTAGLIO, BAR E RISTORANTI",
            "TRASPORTO E MAGAZZINAGGIO, HOTEL",
            "SERVIZI ALLE IMPRESE",
            "SERVIZI ALLE PERSONE"
        );
    }

    public function convertPercentualeToNum($string)
    {
        $explodedString = explode(',', str_replace('%', '', $string));
        if ((int)$explodedString[0] >= 0) {
            return $explodedString[0] + ($explodedString[1] / 100);
        } else {
            return (int)$explodedString[0] - ((int)$explodedString[1] / 100);
        }
    }

    public function valutazioneIndici($data, $tipoAzienda, $currentYear)
    {
        CustomLog::addToLogBilanciHelper('BilanciHelper', 'GetParametersData', json_encode($data));
        CustomLog::addToLogBilanciHelper('BilanciHelper', 'GetParametersTipoAzienda', json_encode($tipoAzienda));
        CustomLog::addToLogBilanciHelper('BilanciHelper', 'GetParametersCurrentYear', json_encode($currentYear));

        unset($data['Peso_Oneri_Finanziari']);
        $arraySettori = $this->getSettori();
        $arrayRange = $this->getRangeValutazioni();
        $arrayGiudizi = array();
        $arrayIndici = array();
        $arraySoglie = array();
        $scoringAreaBilancio = 0;
        $giudizi = array("rischio_elevato", "situazione_critica", "buono", "ottimo");

        foreach ($arraySettori as $singleSector => $multipleTypes) {
            if (array_key_exists($tipoAzienda, $multipleTypes) && ($tipoAzienda == 'Industria' || $tipoAzienda == 'Commercio' || $tipoAzienda == 'Servizi')) {
                $tipoAzienda = $singleSector;
            }
        }

        foreach ($data as $label => $value) {
            $giudizio = '';
            if (str_contains((float)$value, '%') && isset($value)) {
                $value = explode('%', (float)$value)[0];
            }

            $label = str_replace('_', ' ', $label);
            $value = (float)str_replace(',', '.', $value);

            if ($label == 'ROE') {
                $tassoInflazione = (float)Roe::where('year', $currentYear)->get()->first()->value / 100;
                $value /= 100;
                if ($value < $tassoInflazione + 0.02) {
                    $scoringAreaBilancio += 0.0426 * 0.1;
                    $arrayGiudizi[$label]['Scoring'] = 0.0426 * 0.1;
                    $arrayGiudizi[$label]['Giudizio'] = 'Rischio Elevato';
                } else if ($value >= $tassoInflazione + 0.02 && $value  < $tassoInflazione + 0.03) {
                    $scoringAreaBilancio += 0.0426 * 0.3;
                    $arrayGiudizi[$label]['Scoring'] = 0.0426 * 0.3;
                    $arrayGiudizi[$label]['Giudizio'] = 'Situazione Critica';
                } else if ($value >= $tassoInflazione + 0.03 && $value  < $tassoInflazione + 0.05) {
                    $scoringAreaBilancio += 0.0426 * 0.6;
                    $arrayGiudizi[$label]['Scoring'] = 0.0426 * 0.6;
                    $arrayGiudizi[$label]['Giudizio'] = 'Buono';
                } else if ($value  >= $tassoInflazione + 0.05) {
                    $scoringAreaBilancio += 0.0426 * 1;
                    $arrayGiudizi[$label]['Scoring'] = 0.0426 * 1;
                    $arrayGiudizi[$label]['Giudizio'] = 'Ottimo';
                }
            } else if ($label == 'OF Fatturato') {
                $label = 'Peso Oneri Finanziari';
            }

            $arrayIndici[$label] = $value / 100;

            $arraySoglie[$label] = range::where([['range_min', '<', $arrayIndici[$label]], ['range_max', '>', $arrayIndici[$label]], ['indice', '=', $label], ['tipo_azienda', '=', $tipoAzienda]])->with('pesi')->get();
            // if ($label == 'Costo del personale') {
            //             dump($dataAnalisis);
            // (range::where([['range_min', '<', $arrayIndici[$label]], ['range_max', '>', $arrayIndici[$label]], ['indice', '=', $label], ['tipo_azienda', '=', $tipoAzienda]])->with('pesi')->getBindings());
            //     dd(range::where([['range_min', '<', $arrayIndici[$label]], ['range_max', '>', $arrayIndici[$label]], ['indice', '=', $label], ['tipo_azienda', '=', $tipoAzienda]])->with('pesi')->get());
            // }

            if (count($arraySoglie[$label]) > 0) {
                $arrayGiudizi[$label]['Scoring'] = ($arraySoglie[$label][0]->pesi->peso) * ($arraySoglie[$label][0]->score);
                $arrayGiudizi[$label]['Giudizio'] = $arraySoglie[$label][0]->giudizio;

                $scoringAreaBilancio += $arrayGiudizi[$label]['Scoring'];
            } else {
                $arraySoglie[$label] = range::where([['range_min', '<', $arrayIndici[$label]], ['range_max', '>', $arrayIndici[$label]], ['indice', '=', $label], ['tipo_azienda', '=', 'Generica']])->with('pesi')->get();
                // dump($arraySoglie);
                // if ($label == 'PFN EBITDA') {
                //     dd($label, $value, $tipoAzienda, count($arraySoglie['PFN EBITDA']));
                // }
                // if ($label == 'PFN EBITDA') {
                //     dd(range::where([['range_min', '<', $arrayIndici[$label]], ['range_max', '>', $arrayIndici[$label]], ['indice', '=', $label], ['tipo_azienda', '=', 'Generica']])->with('pesi')->toSql(), $arrayIndici[$label], $label);
                //     dd($label, $value, $arraySoglie[$label], $tipoAzienda, $arrayIndici[$label]);
                // }
                if (count($arraySoglie[$label]) > 0) {

                    $arrayGiudizi[$label]['Scoring'] = ($arraySoglie[$label][0]->pesi->peso) * ($arraySoglie[$label][0]->score);

                    $arrayGiudizi[$label]['Giudizio'] = $arraySoglie[$label][0]->giudizio;

                    $scoringAreaBilancio += $arrayGiudizi[$label]['Scoring'];
                }

            }

            if(isset($arrayGiudizi[$label]['Scoring'])) {
                $arrayGiudizi[$label]['Scoring'] = number_format($arrayGiudizi[$label]['Scoring'], 2, ',', '.');
            }

            $indiciName = str_replace(' ', '_', $label);

            if(isset($arrayGiudizi[$label])) {
                CustomLog::addToLogBilanciHelper('BilanciHelper', 'Get'.$indiciName.'', 'Scoring => '.json_decode(json_encode($arrayGiudizi[$label]['Scoring'])).' Giudizio => '.json_decode(json_encode($arrayGiudizi[$label]['Giudizio'])));
            }
        }

        CustomLog::addToLogBilanciHelper('BilanciHelper', 'GetGeneralScore', number_format($scoringAreaBilancio, 2, ',', '.'));

        return array("Score" => number_format($scoringAreaBilancio, 2, ',', '.'), "Giudizi" => $arrayGiudizi);
    }

    public function getVociTree()
    {
        $voci = DB::table('vocis')->where('required', 1)->get();
        $gradi = array();

        foreach ($voci as $voce) {
            if ($voce->voce_padre == null || $voce->voce_padre == "") {
                $h1[$voce->name] = $voce->extended_name;
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

        return $gradi;
    }




    public function getAnalisiBilancio($idBilancio)
    {
        $bilancio = Bilanci::findOrFail($idBilancio);

        $bilancioCalculationHelper = new BilanciCalculationsHelper;

        $tipoAzienda = $bilancio->tipo_azienda;

        $attributes = $bilancio->getAttributes();
        $jsonData['current'] = json_decode($attributes['json_data'], true);
        $jsonData['prev'] = json_decode($attributes['json_data_prev'], true);
        $jsonData['anagrafic'] = json_decode($attributes['json_data_anag'], true);
        $jsonData['currentYear'] = $attributes['current_year'];
        $jsonData['prevYear'] = $attributes['prev_year'];
        $tipoAzienda = $attributes['tipo_azienda'];

        $bilancioJSON = json_decode($bilancio['json_data']);
        $bilancioCalculationHelper->setBilancioData($bilancioJSON);
        $bilancioJSONprev = json_decode($bilancio['json_data_prev']);
        $bilancioCalculationHelper->setBilancioDataPrev($bilancioJSONprev);
        $dataAnalisis = array();

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

            $daysToYear = $days / 365;

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

        $setBilancioData = $bilancioCalculationHelper->setBilancioData($bilancioJSON);

        // ### PATRIMONIO_NETTO ###
        $TotalePatrimonioNetto = $bilancioCalculationHelper->getTotalePatrimonioNetto();
        $dataAnalisis['PATRIMONIO_NETTO'] = $TotalePatrimonioNetto;
        $arrayConVoci['PATRIMONIO_NETTO'] = array('TotalePatrimonioNetto', 'TotaleCreditiVersoSociVersamentiAncoraDovuti');

        // Valori bilancio
        // ### OF_RICAVI ###
        $OF_RICAVI = $bilancioCalculationHelper->getOfRicavi();
        $dataAnalisis['OF_Fatturato'] = $OF_RICAVI;
        $arrayConVoci['OF_Fatturato'] = array('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 'ValoreProduzioneRicaviVenditePrestazioni');

        // ### ADEGUATEZZA_PATRIMONIALE ###
        $ADEGUATEZZA_PATRIMONIALE = $bilancioCalculationHelper->getAdeguatezzaPatrimoniale();
        $dataAnalisis['ADEGUATEZZA_PATRIMONIALE'] = $ADEGUATEZZA_PATRIMONIALE;
        $arrayConVoci['ADEGUATEZZA_PATRIMONIALE'] = array('TotaleDebiti', 'PassivoRateiRisconti');
        $arrayConVoci['ADEGUATEZZA_PATRIMONIALE'] = array_merge($arrayConVoci['ADEGUATEZZA_PATRIMONIALE'], $arrayConVoci['PATRIMONIO_NETTO']);

        // ### RITORNO_LIQUIDO_ATTIVO ###
        $TotaleDisponibilitaLiquide = $bilancioCalculationHelper->getDataFromBilancio('TotaleDisponibilitaLiquide');

        // ### LIQUIDITA ###
        $getLiquidita = $bilancioCalculationHelper->getLiquidita();

        $dataAnalisis['LIQUIDITA'] = $getLiquidita;
        $arrayConVoci['LIQUIDITA'] = array('UtilePerditaEsercizio', 'CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 'CostiProduzioneAccantonamentiRischi', 'CostiProduzioneAltriAccantonamenti', 'TotaleAttivo');

        // ### INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO ###
        $getIndebitamentoPrevidenzialeTributario = $bilancioCalculationHelper->getIndebitamentoPrevidenzialeTributario();

        $dataAnalisis['INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO'] = $getIndebitamentoPrevidenzialeTributario;
        $arrayConVoci['INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO'] = array('DebitiDebitiTributariTotaleDebitiTributari', 'DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale', 'TotaleAttivo');

        // INDICI ADVANCED

        //### Andamento del fatturato
        $getAndamentoDelFatturato = $bilancioCalculationHelper->getAndamentoDelFatturato();

        $dataAnalisis['Andamento_del_fatturato'] = $getAndamentoDelFatturato;
        $arrayConVoci['Andamento_del_fatturato'] = array('ValoreProduzioneRicaviVenditePrestazioniCurr', 'ValoreProduzioneRicaviVenditePrestazioniPrev');


        // ANDAMENTO DEL MOL
        $getAndamentoDelMol = $bilancioCalculationHelper->getAndamentoDelMol();

        $dataAnalisis['Andamento_del_MOL'] = $getAndamentoDelMol;
        $arrayConVoci['Andamento_del_MOL'] = array('TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzioneServizi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione');

        // ### ROI
        $getROI = $bilancioCalculationHelper->getROI();

        $dataAnalisis['ROI'] = $getROI;
        $arrayConVoci['ROI'] = array('DifferenzaValoreCostiProduzione', 'TotaleAttivo');

        // ### ROS
        $getROS = $bilancioCalculationHelper->getROS();

        $dataAnalisis['ROS'] = $getROS;
        $arrayConVoci['ROS'] = array('DifferenzaValoreCostiProduzione', 'ValoreProduzioneRicaviVenditePrestazioni');

        //### ROE
        $getROE = $bilancioCalculationHelper->getROE();

        $dataAnalisis['ROE'] = $getROE;
        $arrayConVoci['ROE'] = array('UtilePerditaEsercizio', 'TotalePatrimonioNetto');

        //### EBITDA/Fatturato
        $getEbitdaFatturato = $bilancioCalculationHelper->getEbitdaFatturato();

        $dataAnalisis['EBITDA_Fatturato'] = $getEbitdaFatturato;
        $arrayConVoci['EBITDA_Fatturato'] = array('TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneServizi', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione', 'ValoreProduzioneRicaviVenditePrestazioni');

        //### Andamento dei mezzi propri
        $getAndamentoDeiMezziPropri = $bilancioCalculationHelper->getAndamentoDeiMezziPropri();

        $dataAnalisis['Andamento_dei_mezzi_propri'] = $getAndamentoDeiMezziPropri;
        $arrayConVoci['Andamento_dei_mezzi_propri'] = array('TotalePatrimonioNetto');

        //### Margine Struttura Primario
        $getMargineStrutturaPrimario = $bilancioCalculationHelper->getMargineStrutturaPrimario();

        $dataAnalisis['Margine_Struttura_Primario'] = $getMargineStrutturaPrimario;
        $arrayConVoci['Margine_Struttura_Primario'] = array('TotalePatrimonioNetto', 'TotaleImmobilizzazioni');

        //### Margine Struttura Secondario
        $getMargineStrutturaSecondario = $bilancioCalculationHelper->getMargineStrutturaSecondario();

        $dataAnalisis['Margine_Struttura_Secondario'] = $getMargineStrutturaSecondario;
        $arrayConVoci['Margine_Struttura_Secondario'] = array('TotalePatrimonioNetto', 'TrattamentoFineRapportoLavoroSubordinat', 'DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 'DebitiAccontiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo', 'DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo', 'TotaleImmobilizzazioni');

        // CURRENT RADIO (VEDI INDICE RITORNO LIQUIDO ATT)
        $getCurrentRatio = $bilancioCalculationHelper->getCurrentRatio();

        $dataAnalisis['Current_Ratio'] = $getCurrentRatio;
        $arrayConVoci['Current_Ratio'] = array('TotaleDisponibilitaLiquide', 'AttivoRateiRisconti', 'TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 'TotaleRimanenze', 'TotaleCreditiEntroDodiciMesi', 'TotaleDebitiEntroDodiciMesi', 'PassivoRateiRisconti');

        //### Attivita a breve / Passività a Breve
        $getAttivitaPassivitaABreve = $bilancioCalculationHelper->getAttivitaPassivitaABreve();

        $dataAnalisis['Attivita_a_breve_Passività_a_Breve_Ordinario'] = $getAttivitaPassivitaABreve['Attivita_a_breve_Passività_a_Breve_Ordinario'];
        $arrayConVoci['Attivita_a_breve_Passività_a_Breve_Ordinario'] = array();

        // ACID TEST
        $getAcidTestOrdinario = $bilancioCalculationHelper->getAcidTestOrdinario();

        $dataAnalisis['Acid_Test'] = $getAcidTestOrdinario;
        $arrayConVoci['Acid_Test'] = array('TotaleDisponibilitaLiquide', 'CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo', 'CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 'CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 'CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 'CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 'CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 'TotaleRimanenze', 'TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 'AttivoRateiRisconti', 'TotaleRimanenze', 'DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 'DebitiAccontiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 'DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 'PassivoRateiRisconti');

        // AUTONOMIA FINANZIARIA
        $getAutonomiaFinanziaria = $bilancioCalculationHelper->getAutonomiaFinanziaria();

        $dataAnalisis['Autonomia_Finanziaria'] = $getAutonomiaFinanziaria;
        $arrayConVoci['Autonomia_Finanziaria'] = array('TotalePatrimonioNetto', 'TotalePatrimonioNetto', 'TotaleDebiti');

        // LIVELLO INVESTIMENTI AZIENDALI
        $getLivelloInvestimentiAziendali = $bilancioCalculationHelper->getLivelloInvestimentiAziendali();

        $dataAnalisis['Livello_investimenti_aziendali'] = $getLivelloInvestimentiAziendali;
        $arrayConVoci['Livello_investimenti_aziendali'] = array('TotalePatrimonioNetto', 'TotaleAttivo');


        // PFN / EBITDA
        $getPfnEbitda = $bilancioCalculationHelper->getPfnEbitda();

        $dataAnalisis['PFN_EBITDA'] = $getPfnEbitda;
        $arrayConVoci['PFN_EBITDA'] = array('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 'TotaleDisponibilitaLiquide', 'ImmobilizzazioniFinanziarieCreditiTotaleCrediti', 'TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzioneServizi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione');

        // Peso Oneri Finanziari (OF/Fatturato)
        $getPesoOneriFinanziari = $bilancioCalculationHelper->getPesoOneriFinanziari();

        $dataAnalisis['Peso_Oneri_Finanziari'] = $getPesoOneriFinanziari;
        $arrayConVoci['Peso_Oneri_Finanziari'] = array('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 'ValoreProduzioneRicaviVenditePrestazioni');

        // Copertura Lorda degli Oneri Finanziari
        $getCoperturaLordaDegliOneriFinanziari = $bilancioCalculationHelper->getCoperturaLordaDegliOneriFinanziari();

        $dataAnalisis['Copertura_Lorda_OF'] = $getCoperturaLordaDegliOneriFinanziari;
        $arrayConVoci['Copertura_Lorda_OF'] = array('TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneServizi', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione', 'ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari');

        // EBIT / OF
        $getEbitOf = $bilancioCalculationHelper->getEbitOf();

        $dataAnalisis['EBIT_OF'] = $getEbitOf;
        $arrayConVoci['EBIT_OF'] = array('TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneServizi', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione', 'CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 'CostiProduzioneAccantonamentiRischi', 'CostiProduzioneAltriAccantonamenti', 'ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari');

        //Costo del personale
        $getCostoDelPersonale = $bilancioCalculationHelper->getCostoDelPersonale();

        $dataAnalisis['Costo_del_personale'] = $getCostoDelPersonale;
        $arrayConVoci['Costo_del_personale'] = array('CostiProduzionePersonaleTotaleCostiPersonale', 'ValoreProduzioneRicaviVenditePrestazioni');

        // CF / Attivo
        $getCfAttivo = $bilancioCalculationHelper->getCfAttivo();

        $dataAnalisis['CF_Attivo'] = $getCfAttivo;
        $arrayConVoci['CF_Attivo'] = array('UtilePerditaEsercizio', 'CostiProduzioneAccantonamentiRischi', 'CostiProduzioneAltriAccantonamenti', 'CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 'CreditiImposteAnticipateTotaleImposteAnticipate', 'TotaleAttivo');

        //Indice di Indebitamento (PFN/PN)
        $getIndiceDiIndebitamento = $bilancioCalculationHelper->getIndiceDiIndebitamento();

        $dataAnalisis['Indice_di_Indebitamento'] = $getIndiceDiIndebitamento;
        $arrayConVoci['Indice_di_Indebitamento'] = array('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 'TotaleDisponibilitaLiquide', 'ImmobilizzazioniFinanziarieCreditiTotaleCrediti', 'TotalePatrimonioNetto');

        //SALDO DEBITI VS FISCO
        $getSaldoDebitiVsFisco = $bilancioCalculationHelper->getSaldoDebitiVsFisco();

        $dataAnalisis['Saldo_dei_Debiti_verso_il_Fisco'] = $getSaldoDebitiVsFisco;
        $arrayConVoci['Saldo_dei_Debiti_verso_il_Fisco'] = array('FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente', 'DebitiDebitiTributariTotaleDebitiTributariCorrente', 'DifferenzaImposteReddito');

        $explodedDate = explode('-', $bilancio->year)[0];

        $valutazioneBilancio = $this->valutazioneIndici($dataAnalisis, $tipoAzienda, $explodedDate);

        $dataAnalisis["PFN_EBITDA"] = number_format($dataAnalisis["PFN_EBITDA"] / 100, 2, ',', '.');
        $dataAnalisis["Copertura_Lorda_OF"] = number_format($dataAnalisis["Copertura_Lorda_OF"] / 100, 2, ',', '.');
        $dataAnalisis["EBIT_OF"] = number_format($dataAnalisis["EBIT_OF"] / 100, 2, ',', '.');
        $dataAnalisis["Saldo_dei_Debiti_verso_il_Fisco"] = number_format($dataAnalisis["Saldo_dei_Debiti_verso_il_Fisco"] / 100, 2, ',', '.');
        $dataAnalisis["Margine_Struttura_Primario"] =  number_format((float)str_replace('.', '', $dataAnalisis["Margine_Struttura_Primario"]) / 100, 2, ',', '.');
        $dataAnalisis["Margine_Struttura_Secondario"] =  number_format((float)str_replace('.', '', $dataAnalisis["Margine_Struttura_Secondario"]) / 100, 2, ',', '.');

        $response = array(
            'AnalisiBasic' => $this->getBasicAnalisi($tipoAzienda, $dataAnalisis, $dataAnalisis, $idBilancio),
            'AnalisiAdvanced' => [
                'Indici' => $dataAnalisis,
                'Giudizi' => $valutazioneBilancio
            ]
        );

        return $response;
    }


    public function getBasicAnalisi($tipoAzienda, $dataAnalisisBasic, $dataAnalisis, $idBilancio)
    {
        $basicData = array();

        $dataAnalisisBasic['OF_Fatturato'] = $dataAnalisisBasic['OF_Fatturato'];
        if (isset($dataAnalisisBasic['Adeguatezza_Patrimoniale'])) {
            $dataAnalisisBasic['Adeguatezza_Patrimoniale'] = str_replace(',', '.', $dataAnalisisBasic['Adeguatezza_Patrimoniale']);
        } else {
            $dataAnalisisBasic['Adeguatezza_Patrimoniale'] = 0;
        }

        if (isset($dataAnalisisBasic['Liquidità'])) {
            $dataAnalisisBasic['Liquidità'] = str_replace(',', '.', $dataAnalisisBasic['Liquidità']);
        } else {
            $dataAnalisisBasic['Liquidità'] = 0;
        }

        if (isset($dataAnalisisBasic['Indebitamento_Previdenziale_Tributario'])) {
            $dataAnalisisBasic['Indebitamento_Previdenziale_Tributario'] = str_replace(',', '.', $dataAnalisisBasic['Indebitamento_Previdenziale_Tributario']);
        } else {
            $dataAnalisisBasic['Indebitamento_Previdenziale_Tributario'] = 0;
        }

        if (isset($dataAnalisisBasic['Current_Ratio'])) {
            $dataAnalisis['Current_Ratio'] = str_replace(',', '.', $dataAnalisis['Current_Ratio']);
        } else {
            $dataAnalisis['Current_Ratio'] = 0;
        }


        if (DB::table('rangesBasic')->where('tipo_azienda', '=', $tipoAzienda)->where('indice', '=', 'Sostenibilità Oneri Finanziari')->where('soglia', '>', floatval($dataAnalisisBasic['OF_Fatturato']))->count()) {
            $soglieBasic['Sostenibilità Oneri Finanziari'] = true;
            $basicData['Sostenibilità Oneri Finanziari'] = floatval($dataAnalisisBasic['OF_Fatturato']);
            $basicData['Sostenibilità Oneri Finanziari'] = number_format($basicData['Sostenibilità Oneri Finanziari'], 2, ',', '.');
        } else {
            $soglieBasic['Sostenibilità Oneri Finanziari'] = false;
            $basicData['Sostenibilità Oneri Finanziari'] = floatval($dataAnalisisBasic['OF_Fatturato']);
            $basicData['Sostenibilità Oneri Finanziari'] = number_format($basicData['Sostenibilità Oneri Finanziari'], 2, ',', '.');
        }
        if (DB::table('rangesBasic')->where('tipo_azienda', '=', $tipoAzienda)->where('indice', '=', 'Adeguatezza Patrimoniale')->where('soglia', '<', floatval($dataAnalisisBasic['Adeguatezza_Patrimoniale']))->count()) {
            $soglieBasic['Adeguatezza Patrimoniale'] = true;
            $basicData['Adeguatezza Patrimoniale'] = floatval($dataAnalisisBasic['Adeguatezza_Patrimoniale']);
            $basicData['Adeguatezza Patrimoniale'] = number_format($basicData['Adeguatezza Patrimoniale'], 2, ',', '.');
        } else {
            $soglieBasic['Adeguatezza Patrimoniale'] = false;
            $basicData['Adeguatezza Patrimoniale'] = floatval($dataAnalisisBasic['Adeguatezza_Patrimoniale']);
            $basicData['Adeguatezza Patrimoniale'] = number_format($basicData['Adeguatezza Patrimoniale'], 2, ',', '.');
        }

        if (DB::table('rangesBasic')->where('tipo_azienda', '=', $tipoAzienda)->where('indice', '=', 'Liquidità')->where('soglia', '<', floatval($dataAnalisisBasic['Liquidità']))->count()) {
            $soglieBasic['Liquidità'] = true;
            $basicData['Liquidità'] = floatval($dataAnalisisBasic['Liquidità']);
            $basicData['Liquidità'] = number_format($basicData['Liquidità'], 2, ',', '.');
        } else {
            $soglieBasic['Liquidità'] = false;
            $basicData['Liquidità'] = floatval($dataAnalisisBasic['Liquidità']);
            $basicData['Liquidità'] = number_format($basicData['Liquidità'], 2, ',', '.');
        }
        if (DB::table('rangesBasic')->where('tipo_azienda', '=', $tipoAzienda)->where('indice', '=', 'Indebitamento Previdenziale Tributario')->where('soglia', '>', floatval($dataAnalisisBasic['Indebitamento_Previdenziale_Tributario']))->count()) {
            $soglieBasic['Indebitamento Previdenziale Tributario'] = true;
            $basicData['Indebitamento Previdenziale Tributario'] = floatval($dataAnalisisBasic['Indebitamento_Previdenziale_Tributario']);
            $basicData['Indebitamento Previdenziale Tributario'] = number_format($basicData['Indebitamento Previdenziale Tributario'], 2, ',', '.');
        } else {
            $soglieBasic['Indebitamento Previdenziale Tributario'] = false;
            $basicData['Indebitamento Previdenziale Tributario'] = floatval($dataAnalisisBasic['Indebitamento_Previdenziale_Tributario']);
            $basicData['Indebitamento Previdenziale Tributario'] = number_format($basicData['Indebitamento Previdenziale Tributario'], 2, ',', '.');
        }
        if (DB::table('rangesBasic')->where('tipo_azienda', '=', $tipoAzienda)->where('indice', '=', 'Ritorno Liquido Attivo')->where('soglia', '<', floatval($dataAnalisis['Current_Ratio']))->count()) {
            $soglieBasic['Ritorno Liquido Attivo'] = true;
            $basicData['Ritorno Liquido Attivo'] = floatval($dataAnalisis['Current_Ratio']);
            $basicData['Ritorno Liquido Attivo'] = number_format($basicData['Ritorno Liquido Attivo'], 2, ',', '.');
        } else {
            $soglieBasic['Ritorno Liquido Attivo'] = false;
            $basicData['Ritorno Liquido Attivo'] = floatval($dataAnalisis['Current_Ratio']);
            $basicData['Ritorno Liquido Attivo'] = number_format($basicData['Ritorno Liquido Attivo'], 2, ',', '.');
        }
        // dd(floatval($dataAnalisis['Current_Ratio']), floatval($dataAnalisisBasic['OF_Fatturato']), floatval($dataAnalisisBasic['Adeguatezza_Patrimoniale']), floatval($dataAnalisisBasic['Liquidità']), floatval($dataAnalisisBasic['Indebitamento_Previdenziale_Tributario']));

        $valutazioneAllertaBasic = true;

        foreach ($soglieBasic as $label => $alert) {
            if ($alert) {
                $valutazioneAllertaBasic = false;
            }
        }


        $sistemaBasic = DB::table('basic')->where('bilancio_id', '=', $idBilancio)->get()->toArray();

        

        if(!empty($sistemaBasic)) {
            $sistemaBasic = $this->getAnalisiBasicWithNumberFormat($sistemaBasic);
        }


        if ($valutazioneAllertaBasic) {
            $soglieBasic['indiceCNDCEC'] = "Azienda a Rischio";
        } else {
            $soglieBasic['indiceCNDCEC'] = "Azienda non a Rischio";
        }

        return array(
            'Soglie' => $soglieBasic,
            'Valori' => $basicData,
            'InputData' => $sistemaBasic,
        );
    }

    public function getAnalisiBasicWithNumberFormat($sistemaBasic) 
    {

        foreach($sistemaBasic as $singleDataBasic) {
            $sistemaBasic[0]->DSCRdispLiquida = number_format($singleDataBasic->DSCRdispLiquida, 2, ',', '.');
            $sistemaBasic[0]->entrataDSCRCFmese1 = number_format($singleDataBasic->entrataDSCRCFmese1, 2, ',', '.');
            $sistemaBasic[0]->entrataDSCRCFmese2 = number_format($singleDataBasic->entrataDSCRCFmese2, 2, ',', '.');
            $sistemaBasic[0]->entrataDSCRCFmese3 = number_format($singleDataBasic->entrataDSCRCFmese3, 2, ',', '.');
            $sistemaBasic[0]->entrataDSCRCFmese4 = number_format($singleDataBasic->entrataDSCRCFmese4, 2, ',', '.');
            $sistemaBasic[0]->entrataDSCRCFmese5 = number_format($singleDataBasic->entrataDSCRCFmese5, 2, ',', '.');
            $sistemaBasic[0]->entrataDSCRCFmese6 = number_format($singleDataBasic->entrataDSCRCFmese6, 2, ',', '.');
            $sistemaBasic[0]->uscitaDSCRCFmese1 = number_format($singleDataBasic->uscitaDSCRCFmese1, 2, ',', '.');
            $sistemaBasic[0]->uscitaDSCRCFmese2 = number_format($singleDataBasic->uscitaDSCRCFmese2, 2, ',', '.');
            $sistemaBasic[0]->uscitaDSCRCFmese3 = number_format($singleDataBasic->uscitaDSCRCFmese3, 2, ',', '.');
            $sistemaBasic[0]->uscitaDSCRCFmese4 = number_format($singleDataBasic->uscitaDSCRCFmese4, 2, ',', '.');
            $sistemaBasic[0]->uscitaDSCRCFmese5 = number_format($singleDataBasic->uscitaDSCRCFmese5, 2, ',', '.');
            $sistemaBasic[0]->uscitaDSCRCFmese6 = number_format($singleDataBasic->uscitaDSCRCFmese6, 2, ',', '.');
            $sistemaBasic[0]->rimborsoDSCRmese1 = number_format($singleDataBasic->rimborsoDSCRmese1, 2, ',', '.');
            $sistemaBasic[0]->rimborsoDSCRmese2 = number_format($singleDataBasic->rimborsoDSCRmese2, 2, ',', '.');
            $sistemaBasic[0]->rimborsoDSCRmese3 = number_format($singleDataBasic->rimborsoDSCRmese3, 2, ',', '.');
            $sistemaBasic[0]->rimborsoDSCRmese4 = number_format($singleDataBasic->rimborsoDSCRmese4, 2, ',', '.');
            $sistemaBasic[0]->rimborsoDSCRmese5 = number_format($singleDataBasic->rimborsoDSCRmese5, 2, ',', '.');
            $sistemaBasic[0]->rimborsoDSCRmese6 = number_format($singleDataBasic->rimborsoDSCRmese6, 2, ',', '.');
            $sistemaBasic[0]->agenziaEntrate1 = number_format($singleDataBasic->agenziaEntrate1, 2, ',', '.');
            $sistemaBasic[0]->agenziaEntrate3 = number_format($singleDataBasic->agenziaEntrate3, 2, ',', '.');
            $sistemaBasic[0]->agenziaEntrate2 = number_format($singleDataBasic->agenziaEntrate2, 2, ',', '.');
            $sistemaBasic[0]->agenziaEntrate4 = number_format($singleDataBasic->agenziaEntrate4, 2, ',', '.');
            $sistemaBasic[0]->INPS1 = number_format($singleDataBasic->INPS1, 2, ',', '.');
            $sistemaBasic[0]->INPS2 = number_format($singleDataBasic->INPS2, 2, ',', '.');
            $sistemaBasic[0]->INPS3 = number_format($singleDataBasic->INPS3, 2, ',', '.');
            $sistemaBasic[0]->riscossione = number_format($singleDataBasic->riscossione, 2, ',', '.');
            $sistemaBasic[0]->retribuzioni1 = number_format($singleDataBasic->retribuzioni1, 2, ',', '.');
            $sistemaBasic[0]->retribuzioni2 = number_format($singleDataBasic->retribuzioni2, 2, ',', '.');
            $sistemaBasic[0]->retribuzioni3 = number_format($singleDataBasic->retribuzioni3, 2, ',', '.');
            $sistemaBasic[0]->fornitori1 = number_format($singleDataBasic->fornitori1, 2, ',', '.');
            $sistemaBasic[0]->fornitori2 = number_format($singleDataBasic->fornitori2, 2, ',', '.');
        }

        return $sistemaBasic;
    }

    public function getNameCompany($id)
    {

        $bilancio = Bilanci::findOrFail($id);
        $bilancioJsonAnag = json_decode($bilancio['json_data_anag']);

        return $bilancioJsonAnag->DatiAnagraficiDenominazione;
    }

    private function getAnalisisDataFull($allData)
    {
        $agenziaEntrate = $this->calculateAgenziaEntrate($allData);
        $dataINPS = $this->calcoloINPS($allData);
        $riscossioneAlert = $this->calculateRiscossione($allData);
        $alertRetribuzioni = $this->calculateRetribuzione($allData);
        $alertFornitori = $this->calculateFornitori($allData);
        $alertDSCR = $this->getCalcoloDSCR($allData);

        $cleanArray = [
            'DSCRDate' => (isset($allData['DSCRdispLiquida'])) ? $allData['DSCRDate'] : "1970-01-01",
            'DSCR' => $allData['DSCR'],
            'DSCRdispLiquida' => (empty($allData['DSCRdispLiquida']) || $allData['DSCRdispLiquida'] == 0) ? null : $allData['DSCRdispLiquida'],
            'entrataDSCRCFmese1' => (isset($allData['entrataDSCRCFmese1'])) ? $allData['entrataDSCRCFmese1'] : null,
            'entrataDSCRCFmese2' => (isset($allData['entrataDSCRCFmese2'])) ? $allData['entrataDSCRCFmese2'] : null,
            'entrataDSCRCFmese3' => (isset($allData['entrataDSCRCFmese3'])) ? $allData['entrataDSCRCFmese3'] : null,
            'entrataDSCRCFmese4' => (isset($allData['entrataDSCRCFmese4'])) ? $allData['entrataDSCRCFmese4'] : null,
            'entrataDSCRCFmese5' => (isset($allData['entrataDSCRCFmese5'])) ? $allData['entrataDSCRCFmese5'] : null,
            'entrataDSCRCFmese6' => (isset($allData['entrataDSCRCFmese6'])) ? $allData['entrataDSCRCFmese6'] : null,
            'uscitaDSCRCFmese1' => (isset($allData['uscitaDSCRCFmese1'])) ? $allData['uscitaDSCRCFmese1'] : null,
            'uscitaDSCRCFmese2' => (isset($allData['uscitaDSCRCFmese2'])) ? $allData['uscitaDSCRCFmese2'] : null,
            'uscitaDSCRCFmese3' => (isset($allData['uscitaDSCRCFmese3'])) ? $allData['uscitaDSCRCFmese3'] : null,
            'uscitaDSCRCFmese4' => (isset($allData['uscitaDSCRCFmese4'])) ? $allData['uscitaDSCRCFmese4'] : null,
            'uscitaDSCRCFmese5' => (isset($allData['uscitaDSCRCFmese5'])) ? $allData['uscitaDSCRCFmese5'] : null,
            'uscitaDSCRCFmese6' => (isset($allData['uscitaDSCRCFmese6'])) ? $allData['uscitaDSCRCFmese6'] : null,
            'rimborsoDSCRmese1' => (isset($allData['rimborsoDSCRmese1'])) ? $allData['rimborsoDSCRmese1'] : null,
            'rimborsoDSCRmese2' => (isset($allData['rimborsoDSCRmese2'])) ? $allData['rimborsoDSCRmese2'] : null,
            'rimborsoDSCRmese3' => (isset($allData['rimborsoDSCRmese3'])) ? $allData['rimborsoDSCRmese3'] : null,
            'rimborsoDSCRmese4' => (isset($allData['rimborsoDSCRmese4'])) ? $allData['rimborsoDSCRmese4'] : null,
            'rimborsoDSCRmese5' => (isset($allData['rimborsoDSCRmese5'])) ? $allData['rimborsoDSCRmese5'] : null,
            'rimborsoDSCRmese6' => (isset($allData['rimborsoDSCRmese6'])) ? $allData['rimborsoDSCRmese6'] : null,
            'agenziaEntrate1' => (isset($allData['agenziaEntrate1'])) ? floatval(str_replace('"', '', $allData['agenziaEntrate1'])) : null,
            'agenziaEntrate2' => (isset($allData['agenziaEntrate2'])) ? floatval(str_replace('"', '', $allData['agenziaEntrate2'])) : null,
            'agenziaEntrate3' => (isset($allData['agenziaEntrate3'])) ? floatval(str_replace('"', '', $allData['agenziaEntrate3'])) : null,
            'agenziaEntrate4' => (isset($agenziaEntrate['agenziaEntrate4'])) ? floatval(str_replace('"', '', $agenziaEntrate['agenziaEntrate4']['agenziaEntrate4']))  : null,
            'INPS1' => ($allData["INPS1"] != null) ? floatval(str_replace('"', '', $allData['INPS1'])) : null,
            'INPS2' => ($allData["INPS2"] != null) ? floatval(str_replace('"', '', $allData['INPS2'])) : null,
            'INPS3' => ($dataINPS['inps3'] != null) ? (float)$dataINPS['inps3'] : null,
            'riscossione' => ($allData["riscossione"] != null) ? floatval(str_replace('"', '', $allData['riscossione'])) : null,
            'retribuzioni1' => ($allData["retribuzioni1"] != null) ? floatval(str_replace('"', '', $allData['retribuzioni1'])) : null,
            'retribuzioni2' => ($allData["retribuzioni2"] != null) ? floatval(str_replace('"', '', $allData['retribuzioni2'])) : null,
            'retribuzioni3' => ($alertRetribuzioni["retribuzioni3"] != null) ? (float)$alertRetribuzioni["retribuzioni3"] : null,
            'fornitori1' => ($allData["fornitori1"] != null) ? floatval(str_replace('"', '', $allData['fornitori1'])) : null,
            'fornitori2' => ($allData["fornitori2"] != null) ? floatval(str_replace('"', '', $allData['fornitori2'])) : null,
            'resultDSCR' => $alertDSCR,
            'alertAgenziaEntrate' => (isset($agenziaEntrate['alert'])) ? $agenziaEntrate['alert'] : "Dati Mancanti",
            'alertINPS' => $dataINPS['alert'],
            'alertRiscossione' => $riscossioneAlert,
            'alertRetribuzioni' => $alertRetribuzioni['alert'],
            'alertFornitori' => $alertFornitori,
        ];

        return $cleanArray;
    }

    private function getDSCRArrayData($allData)
    {
        $cleanArray = [
            'DSCRDate' => $allData['DSCRDate'],
            'DSCR' => $allData['DSCR'],
            'DSCRdispLiquida' => $allData['DSCRdispLiquida'],
            'entrataDSCRCFmese1' => (isset($allData['entrataDSCRCFmese1'])) ? $allData['entrataDSCRCFmese1'] : null,
            'entrataDSCRCFmese2' => (isset($allData['entrataDSCRCFmese2'])) ? $allData['entrataDSCRCFmese2'] : null,
            'entrataDSCRCFmese3' => (isset($allData['entrataDSCRCFmese3'])) ? $allData['entrataDSCRCFmese3'] : null,
            'entrataDSCRCFmese4' => (isset($allData['entrataDSCRCFmese4'])) ? $allData['entrataDSCRCFmese4'] : null,
            'entrataDSCRCFmese5' => (isset($allData['entrataDSCRCFmese5'])) ? $allData['entrataDSCRCFmese5'] : null,
            'entrataDSCRCFmese6' => (isset($allData['entrataDSCRCFmese6'])) ? $allData['entrataDSCRCFmese6'] : null,
            'uscitaDSCRCFmese1' => (isset($allData['uscitaDSCRCFmese1'])) ? $allData['uscitaDSCRCFmese1'] : null,
            'uscitaDSCRCFmese2' => (isset($allData['uscitaDSCRCFmese2'])) ? $allData['uscitaDSCRCFmese2'] : null,
            'uscitaDSCRCFmese3' => (isset($allData['uscitaDSCRCFmese3'])) ? $allData['uscitaDSCRCFmese3'] : null,
            'uscitaDSCRCFmese4' => (isset($allData['uscitaDSCRCFmese4'])) ? $allData['uscitaDSCRCFmese4'] : null,
            'uscitaDSCRCFmese5' => (isset($allData['uscitaDSCRCFmese5'])) ? $allData['uscitaDSCRCFmese5'] : null,
            'uscitaDSCRCFmese6' => (isset($allData['uscitaDSCRCFmese6'])) ? $allData['uscitaDSCRCFmese6'] : null,
            'rimborsoDSCRmese1' => (isset($allData['rimborsoDSCRmese1'])) ? $allData['rimborsoDSCRmese1'] : null,
            'rimborsoDSCRmese2' => (isset($allData['rimborsoDSCRmese2'])) ? $allData['rimborsoDSCRmese2'] : null,
            'rimborsoDSCRmese3' => (isset($allData['rimborsoDSCRmese3'])) ? $allData['rimborsoDSCRmese3'] : null,
            'rimborsoDSCRmese4' => (isset($allData['rimborsoDSCRmese4'])) ? $allData['rimborsoDSCRmese4'] : null,
            'rimborsoDSCRmese5' => (isset($allData['rimborsoDSCRmese5'])) ? $allData['rimborsoDSCRmese5'] : null,
            'rimborsoDSCRmese6' => (isset($allData['rimborsoDSCRmese6'])) ? $allData['rimborsoDSCRmese6'] : null,
        ];

        return $cleanArray;
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

            return number_format($result, 2, ",", ".");
        } catch (Exception $e) {
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
            return ['error' => true];
        }


        return $this->calculateDSCR($dscrData);
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
            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }


    public function saveAnalisiBasicToDB($allData, $idBilancio)
    { 
        if(str_contains($idBilancio, '"')) {
            $idBilancio = str_replace('"', '', $idBilancio);
        } 

        $calcoloDSCR = $this->getCalcoloDSCR($allData);
        $dscrData = $this->getAnalisisDataFull($allData);

        if (isset($calcoloDSCR['error'])) {
            $dscrData['alertDSCR'] = "DSCR Non Calcolabile: dati mancanti";
        }

        if ($calcoloDSCR > 1) {
            $dscrData['alertDSCR'] = 'Azienda non a rischio';
        } else {
            $dscrData['alertDSCR'] = 'Azienda a rischio';
        }

        $dscrData['bilancio_id'] = (int)$idBilancio;

        $balance = Basic::where('bilancio_id', $idBilancio)->get();

        if (count($balance) == 0) {
            Basic::create($dscrData);
        } else {
            Basic::where('bilancio_id', $idBilancio)->update($dscrData);
        }

        return [
            'Message' => "Analisi aggiornata correttamente",
            'AlertDSCR' => $dscrData['alertDSCR'],
            'savedData' => $dscrData
        ];
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
                $inps3 = ((str_replace(',', '.', $allData['INPS1']) / str_replace(',', '.', $allData['INPS2'])) * 100);
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
                if ((str_replace(',', '.', $allData['retribuzioni1']) / str_replace(',', '.', $allData['retribuzioni2'])) * 100 >= 50) {
                    $alert = "Si";
                } else {
                    $alert = "No";
                }
                if (is_finite(str_replace(',', '.', $allData['retribuzioni1']) / str_replace(',', '.', $allData['retribuzioni2']))) {
                    $cleanData['retribuzioni3'] = number_format(((str_replace(',', '.', $allData['retribuzioni1']) / str_replace(',', '.', $allData['retribuzioni2'])) * 100), 2, ".", ",");
                } else {
                    $cleanData['retribuzioni3'] = "NON CALCOLABILE";
                }
            } else {
                if (($allData['retribuzioni1'] / $allData['retribuzioni2']) * 100 >= 50) {
                    $alert = "Si";
                } else {
                    $alert = "No";
                }
                if (is_finite($allData['retribuzioni1'] / $allData['retribuzioni2'])) {
                    $cleanData['retribuzioni3'] = number_format((($allData['retribuzioni1'] / $allData['retribuzioni2']) * 100), 2, ".", ",");
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
}
