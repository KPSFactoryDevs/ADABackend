<?php

namespace App\Helpers\Bilanci;

use App\Http\Requests;
use App;
use App\Models\Bilanci;
use App\Models\Account;
use App\Models\cr;
use App\Models\soglie;
use App\Models\range;
use App\Models\Roe;
use Illuminate\Support\Facades\DB;
use DateTime;
use App\Helpers\Bilanci\BilanciCalculationsHelper;

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

                // dd($label, $value);
            }
            // dump($label, $value);
            $arrayIndici[$label] = $value / 100;

            // dd($tipoAzienda);


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
        }
        // dd('');
        return array("Score" => $scoringAreaBilancio, "Giudizi" => $arrayGiudizi);
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

        $dataAnalisis["PFN_EBITDA"] = $dataAnalisis["PFN_EBITDA"] / 100;
        $dataAnalisis["Copertura_Lorda_OF"] = $dataAnalisis["Copertura_Lorda_OF"] / 100;
        $dataAnalisis["EBIT_OF"] = $dataAnalisis["EBIT_OF"] / 100;
        $dataAnalisis["Saldo_dei_Debiti_verso_il_Fisco"] = $dataAnalisis["Saldo_dei_Debiti_verso_il_Fisco"] / 100;
        $dataAnalisis["Margine_Struttura_Primario"] = (float)str_replace('.', '', $dataAnalisis["Margine_Struttura_Primario"]) / 100;
        $dataAnalisis["Margine_Struttura_Secondario"] = (float)str_replace('.', '', $dataAnalisis["Margine_Struttura_Secondario"]) / 100;


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

        $dataAnalisisBasic['OF_Fatturato'] = str_replace(',', '.', $dataAnalisisBasic['OF_Fatturato']);
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
        } else {
            $soglieBasic['Sostenibilità Oneri Finanziari'] = false;
            $basicData['Sostenibilità Oneri Finanziari'] = floatval($dataAnalisisBasic['OF_Fatturato']);
        }
        if (DB::table('rangesBasic')->where('tipo_azienda', '=', $tipoAzienda)->where('indice', '=', 'Adeguatezza Patrimoniale')->where('soglia', '<', floatval($dataAnalisisBasic['Adeguatezza_Patrimoniale']))->count()) {
            $soglieBasic['Adeguatezza Patrimoniale'] = true;
            $basicData['Adeguatezza Patrimoniale'] = floatval($dataAnalisisBasic['Adeguatezza_Patrimoniale']);
        } else {
            $soglieBasic['Adeguatezza Patrimoniale'] = false;
            $basicData['Adeguatezza Patrimoniale'] = floatval($dataAnalisisBasic['Adeguatezza_Patrimoniale']);
        }

        if (DB::table('rangesBasic')->where('tipo_azienda', '=', $tipoAzienda)->where('indice', '=', 'Liquidità')->where('soglia', '<', floatval($dataAnalisisBasic['Liquidità']))->count()) {
            $soglieBasic['Liquidità'] = true;
            $basicData['Liquidità'] = floatval($dataAnalisisBasic['Liquidità']);
        } else {
            $soglieBasic['Liquidità'] = false;
            $basicData['Liquidità'] = floatval($dataAnalisisBasic['Liquidità']);
        }
        if (DB::table('rangesBasic')->where('tipo_azienda', '=', $tipoAzienda)->where('indice', '=', 'Indebitamento Previdenziale Tributario')->where('soglia', '>', floatval($dataAnalisisBasic['Indebitamento_Previdenziale_Tributario']))->count()) {
            $soglieBasic['Indebitamento Previdenziale Tributario'] = true;
            $basicData['Indebitamento Previdenziale Tributario'] = floatval($dataAnalisisBasic['Indebitamento_Previdenziale_Tributario']);
        } else {
            $soglieBasic['Indebitamento Previdenziale Tributario'] = false;
            $basicData['Indebitamento Previdenziale Tributario'] = floatval($dataAnalisisBasic['Indebitamento_Previdenziale_Tributario']);
        }
        if (DB::table('rangesBasic')->where('tipo_azienda', '=', $tipoAzienda)->where('indice', '=', 'Ritorno Liquido Attivo')->where('soglia', '<', floatval($dataAnalisis['Current_Ratio']))->count()) {
            $soglieBasic['Ritorno Liquido Attivo'] = true;
            $basicData['Ritorno Liquido Attivo'] = floatval($dataAnalisis['Current_Ratio']);
        } else {
            $soglieBasic['Ritorno Liquido Attivo'] = false;
            $basicData['Ritorno Liquido Attivo'] = floatval($dataAnalisis['Current_Ratio']);
        }


        // dd(floatval($dataAnalisis['Current_Ratio']), floatval($dataAnalisisBasic['OF_Fatturato']), floatval($dataAnalisisBasic['Adeguatezza_Patrimoniale']), floatval($dataAnalisisBasic['Liquidità']), floatval($dataAnalisisBasic['Indebitamento_Previdenziale_Tributario']));

        $valutazioneAllertaBasic = true;

        foreach ($soglieBasic as $label => $alert) {
            if ($alert) {
                $valutazioneAllertaBasic = false;
            }
        }

        $sistemaBasic = DB::table('basic')->where('bilancio_id', '=', $idBilancio)->get()->toArray();

        return array(
            'Soglie' => $soglieBasic,
            'Valori' => $basicData,
            'InputData' => $sistemaBasic,
        );
    }

    public function getNameCompany($id)
    {

        $bilancio = Bilanci::findOrFail($id);
        $bilancioJsonAnag = json_decode($bilancio['json_data_anag']);

        return $bilancioJsonAnag->DatiAnagraficiDenominazione;
    }

    public function getCalcoloDSCR($allData)
    {

        $data = [
            'DSCRdispLiquida' => $allData['DSCRdispLiquida'],
            'entrataDSCRCFmese1' => $allData['entrataDSCRCFmese1'],
            'entrataDSCRCFmese2' => $allData['entrataDSCRCFmese2'],
            'entrataDSCRCFmese3' => $allData['entrataDSCRCFmese3'],
            'entrataDSCRCFmese4' => $allData['entrataDSCRCFmese4'],
            'entrataDSCRCFmese5' => $allData['entrataDSCRCFmese5'],
            'entrataDSCRCFmese6' => $allData['entrataDSCRCFmese6'],
            'uscitaDSCRCFmese1' => $allData['uscitaDSCRCFmese1'],
            'uscitaDSCRCFmese2' => $allData['uscitaDSCRCFmese2'],
            'uscitaDSCRCFmese3' => $allData['uscitaDSCRCFmese3'],
            'uscitaDSCRCFmese4' => $allData['uscitaDSCRCFmese4'],
            'uscitaDSCRCFmese5' => $allData['uscitaDSCRCFmese5'],
            'uscitaDSCRCFmese6' => $allData['uscitaDSCRCFmese6'],
            'rimborsoDSCRmese1' => $allData['rimborsoDSCRmese1'],
            'rimborsoDSCRmese2' => $allData['rimborsoDSCRmese2'],
            'rimborsoDSCRmese3' => $allData['rimborsoDSCRmese3'],
            'rimborsoDSCRmese4' => $allData['rimborsoDSCRmese4'],
            'rimborsoDSCRmese5' => $allData['rimborsoDSCRmese5'],
            'rimborsoDSCRmese6' => $allData['rimborsoDSCRmese6']
        ];


        $emptyMessage = null;

        foreach ($data as $singleData) {
            if (empty($singleData)) {
                $emptyMessage = "Attenzione, alcuni campi sono vuoti, compila tutti i campi.";
            }
        }

        if (!$emptyMessage) {
            $calcoloDSCR = (
                $allData['DSCRdispLiquida'] +
                $allData['entrataDSCRCFmese1'] +
                $allData['entrataDSCRCFmese2'] +
                $allData['entrataDSCRCFmese3'] +
                $allData['entrataDSCRCFmese4'] +
                $allData['entrataDSCRCFmese5'] +
                $allData['entrataDSCRCFmese6'] +
                $allData['uscitaDSCRCFmese1'] -
                $allData['uscitaDSCRCFmese2'] -
                $allData['uscitaDSCRCFmese3'] -
                $allData['uscitaDSCRCFmese4'] -
                $allData['uscitaDSCRCFmese5'] -
                $allData['uscitaDSCRCFmese6']
                )
                /
                (
                $allData['rimborsoDSCRmese1'] +
                $allData['rimborsoDSCRmese2'] +
                $allData['rimborsoDSCRmese3'] +
                $allData['rimborsoDSCRmese4'] +
                $allData['rimborsoDSCRmese5'] +
                $allData['rimborsoDSCRmese6']
                );

            return number_format($calcoloDSCR, 2, ",", ".");
        } 
    }

    public function saveAnalisiBasicToDB($allData, $idBilancio)
    {
        
        $calcoloDSCR = $this->getCalcoloDSCR($allData);

            $now = new DateTime();

            if ((bool)$allData['DSCR']) {
                $allData['alertDSCR'] = "Azienda non a rischio";
            } else {
                $allData['alertDSCR'] = "Azienda a rischio";
            }

            if (DB::table('basic')->where('bilancio_id', '=', $idBilancio)->count() == 0) {
                DB::table('basic')->insert([
                    'bilancio_id' => $allData["idBilancio"],
                    'DSCR' => $allData["DSCR"],
                    'alertDSCR' => ($allData['alertDSCR'] != null) ? $allData["alertDSCR"] : false,
                    'resultDSCR' => $calcoloDSCR,
                    'DSCRDate' => ($allData["DSCRDate"] != null) ? $allData["DSCRDate"] : 0,
                    'DSCRdispLiquida' => ($allData["DSCRdispLiquida"] != null) ? $allData["DSCRdispLiquida"] : 0,
                    'entrataDSCRCFmese1' => ($allData["entrataDSCRCFmese1"] != null) ? $allData["entrataDSCRCFmese1"] : 0,
                    'entrataDSCRCFmese2' => ($allData["entrataDSCRCFmese2"] != null) ? $allData["entrataDSCRCFmese2"] : 0,
                    'entrataDSCRCFmese3' => ($allData["entrataDSCRCFmese3"] != null) ? $allData["entrataDSCRCFmese3"] : 0,
                    'entrataDSCRCFmese4' => ($allData["entrataDSCRCFmese4"] != null) ? $allData["entrataDSCRCFmese4"] : 0,
                    'entrataDSCRCFmese5' => ($allData["entrataDSCRCFmese5"] != null) ? $allData["entrataDSCRCFmese5"] : 0,
                    'entrataDSCRCFmese6' => ($allData["entrataDSCRCFmese6"] != null) ? $allData["entrataDSCRCFmese6"] : 0,
                    'uscitaDSCRCFmese1' => ($allData["uscitaDSCRCFmese1"] != null) ? $allData["uscitaDSCRCFmese1"] : 0,
                    'uscitaDSCRCFmese2' => ($allData["uscitaDSCRCFmese2"] != null) ? $allData["uscitaDSCRCFmese2"] : 0,
                    'uscitaDSCRCFmese3' => ($allData["uscitaDSCRCFmese3"] != null) ? $allData["uscitaDSCRCFmese3"] : 0,
                    'uscitaDSCRCFmese4' => ($allData["uscitaDSCRCFmese4"] != null) ? $allData["uscitaDSCRCFmese4"] : 0,
                    'uscitaDSCRCFmese5' => ($allData["uscitaDSCRCFmese5"] != null) ? $allData["uscitaDSCRCFmese5"] : 0,
                    'uscitaDSCRCFmese6' => ($allData["uscitaDSCRCFmese6"] != null) ? $allData["uscitaDSCRCFmese6"] : 0,
                    'rimborsoDSCRmese1' => ($allData["rimborsoDSCRmese1"] != null) ? $allData["rimborsoDSCRmese1"] : 0,
                    'rimborsoDSCRmese2' => ($allData["rimborsoDSCRmese2"] != null) ? $allData["rimborsoDSCRmese2"] : 0,
                    'rimborsoDSCRmese3' => ($allData["rimborsoDSCRmese3"] != null) ? $allData["rimborsoDSCRmese3"] : 0,
                    'rimborsoDSCRmese4' => ($allData["rimborsoDSCRmese4"] != null) ? $allData["rimborsoDSCRmese4"] : 0,
                    'rimborsoDSCRmese5' => ($allData["rimborsoDSCRmese5"] != null) ? $allData["rimborsoDSCRmese5"] : 0,
                    'rimborsoDSCRmese6' => ($allData["rimborsoDSCRmese6"] != null) ? $allData["rimborsoDSCRmese6"] : 0,
                    'agenziaEntrate1' => ($allData["agenziaEntrate1"] != null) ? $allData["agenziaEntrate1"] : 0,
                    'agenziaEntrate3' => ($allData["agenziaEntrate3"] != null) ? $allData["agenziaEntrate3"] : 0,
                    'agenziaEntrate2' => ($allData["agenziaEntrate2"] != null) ? $allData["agenziaEntrate2"] : 0,
                    'agenziaEntrate4' => ($allData["agenziaEntrate4"] != null) ? $allData["agenziaEntrate4"] : 0,
                    'INPS1' => ($allData["INPS1"] != null) ? $allData["INPS1"] : 0,
                    'INPS2' => ($allData["INPS2"] != null) ? $allData["INPS2"] : 0,
                    'INPS3' => ($allData["INPS3"] != null) ? $allData["INPS3"] : 0,
                    'riscossione' => ($allData["riscossione"] != null) ? $allData["riscossione"] : 0,
                    'retribuzioni1' => ($allData["retribuzioni1"] != null) ? $allData["retribuzioni1"] : 0,
                    'retribuzioni2' => ($allData["retribuzioni2"] != null) ? $allData["retribuzioni2"] : 0,
                    'retribuzioni3' => ($allData["retribuzioni3"] != null) ? $allData["retribuzioni3"] : 0,
                    'fornitori1' => ($allData["fornitori1"] != null) ? $allData["fornitori1"] : 0,
                    'fornitori2' => ($allData["fornitori2"] != null) ? $allData["fornitori2"] : 0,
                    'alertAgenziaEntrate' => ($allData['alertAgenziaEntrate'] != null) ? true : false,
                    'alertRiscossione' => ($allData['alertRiscossione'] != null) ? true : false,
                    'alertRetribuzioni' => ($allData['alertRetribuzioni'] != null) ? true : false,
                    'alertINPS' => ($allData['alertINPS'] != null) ? true : false,
                    'alertFornitori' => ($allData['alertFornitori'] != null) ? true : false,
                    'created_at' => $now,
                ]);

                $response = [
                    'Message' => "Analisi effettuata correttamente",
                    'Alert' => $allData['alertDSCR'],
                ];

                return $response;
            } else {
                DB::table('basic')->where('bilancio_id', '=', $idBilancio)->update([
                    'bilancio_id' => ($allData["idBilancio"] != null) ? $allData["idBilancio"] : 0,
                    'DSCR' => ($allData["DSCR"] != null) ? $allData["DSCR"] : 0,
                    'alertDSCR' => ($allData['alertDSCR'] != null) ? $allData["alertDSCR"] : 0,
                    'resultDSCR' => $calcoloDSCR,
                    'DSCRDate' => ($allData["DSCRDate"] != null) ? $allData["DSCRDate"] : 0,
                    'DSCRdispLiquida' => ($allData["DSCRdispLiquida"] != null) ? $allData["DSCRdispLiquida"] : 0,
                    'entrataDSCRCFmese1' => ($allData["entrataDSCRCFmese1"] != null) ? $allData["entrataDSCRCFmese1"] : 0,
                    'entrataDSCRCFmese2' => ($allData["entrataDSCRCFmese2"] != null) ? $allData["entrataDSCRCFmese2"] : 0,
                    'entrataDSCRCFmese3' => ($allData["entrataDSCRCFmese3"] != null) ? $allData["entrataDSCRCFmese3"] : 0,
                    'entrataDSCRCFmese4' => ($allData["entrataDSCRCFmese4"] != null) ? $allData["entrataDSCRCFmese4"] : 0,
                    'entrataDSCRCFmese5' => ($allData["entrataDSCRCFmese5"] != null) ? $allData["entrataDSCRCFmese5"] : 0,
                    'entrataDSCRCFmese6' => ($allData["entrataDSCRCFmese6"] != null) ? $allData["entrataDSCRCFmese6"] : 0,
                    'uscitaDSCRCFmese1' => ($allData["uscitaDSCRCFmese1"] != null) ? $allData["uscitaDSCRCFmese1"] : 0,
                    'uscitaDSCRCFmese2' => ($allData["uscitaDSCRCFmese2"] != null) ? $allData["uscitaDSCRCFmese2"] : 0,
                    'uscitaDSCRCFmese3' => ($allData["uscitaDSCRCFmese3"] != null) ? $allData["uscitaDSCRCFmese3"] : 0,
                    'uscitaDSCRCFmese4' => ($allData["uscitaDSCRCFmese4"] != null) ? $allData["uscitaDSCRCFmese4"] : 0,
                    'uscitaDSCRCFmese5' => ($allData["uscitaDSCRCFmese5"] != null) ? $allData["uscitaDSCRCFmese5"] : 0,
                    'uscitaDSCRCFmese6' => ($allData["uscitaDSCRCFmese6"] != null) ? $allData["uscitaDSCRCFmese6"] : 0,
                    'rimborsoDSCRmese1' => ($allData["rimborsoDSCRmese1"] != null) ? $allData["rimborsoDSCRmese1"] : 0,
                    'rimborsoDSCRmese2' => ($allData["rimborsoDSCRmese2"] != null) ? $allData["rimborsoDSCRmese2"] : 0,
                    'rimborsoDSCRmese3' => ($allData["rimborsoDSCRmese3"] != null) ? $allData["rimborsoDSCRmese3"] : 0,
                    'rimborsoDSCRmese4' => ($allData["rimborsoDSCRmese4"] != null) ? $allData["rimborsoDSCRmese4"] : 0,
                    'rimborsoDSCRmese5' => ($allData["rimborsoDSCRmese5"] != null) ? $allData["rimborsoDSCRmese5"] : 0,
                    'rimborsoDSCRmese6' => ($allData["rimborsoDSCRmese6"] != null) ? $allData["rimborsoDSCRmese6"] : 0,
                    'agenziaEntrate1' => ($allData["agenziaEntrate1"] != null) ? $allData["agenziaEntrate1"] : 0,
                    'agenziaEntrate3' => ($allData["agenziaEntrate3"] != null) ? $allData["agenziaEntrate3"] : 0,
                    'agenziaEntrate2' => ($allData["agenziaEntrate2"] != null) ? $allData["agenziaEntrate2"] : 0,
                    'agenziaEntrate4' => ($allData["agenziaEntrate4"] != null) ? $allData["agenziaEntrate4"] : 0,
                    'alertAgenziaEntrate' => ($allData['alertAgenziaEntrate'] != null) ? $allData["alertAgenziaEntrate"] : 0,
                    'INPS1' => ($allData["INPS1"] != null) ? $allData["INPS1"] : 0,
                    'INPS2' => ($allData["INPS2"] != null) ? $allData["INPS2"] : 0,
                    'INPS3' => ($allData["INPS3"] != null) ? $allData["INPS3"] : 0,
                    'alertINPS' => ($allData['alertINPS'] != null) ? $allData["alertINPS"] : 0,
                    'riscossione' => ($allData["riscossione"] != null) ? $allData["riscossione"] : 0,
                    'alertRiscossione' => ($allData['alertRiscossione'] != null) ? $allData["alertRiscossione"] : 0,
                    'retribuzioni1' => ($allData["retribuzioni1"] != null) ? $allData["retribuzioni1"] : 0,
                    'retribuzioni2' => ($allData["retribuzioni2"] != null) ? $allData["retribuzioni2"] : 0,
                    'retribuzioni3' => ($allData["retribuzioni3"] != null) ? $allData["retribuzioni3"] : 0,
                    'alertRetribuzioni' => ($allData['alertRetribuzioni'] != null) ? $allData["alertRetribuzioni"] : 0,
                    'fornitori1' => ($allData["fornitori1"] != null) ? $allData["fornitori1"] : 0,
                    'fornitori2' => ($allData["fornitori2"] != null) ? $allData["fornitori2"] : 0,
                    'alertFornitori' => ($allData['alertFornitori'] != null) ? $allData["alertFornitori"] : 0,
                    'updated_at' => $now,
                ]);

                $response = [
                    'Message' => "Analisi aggiornata correttamente",
                    'Alert' => $allData['alertDSCR'],
                ];

                return $response;
            }
    }
}
