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
        $dataAnalisis = array();
        // $righeUtilizzate = array();
        $imposteRedditoEsercizioImposteAnticipate = isset($bilancioJSON->ImposteRedditoEsercizioCorrentiDifferiteAnticipateImposteDifferiteAnticipate) ? $bilancioJSON->ImposteRedditoEsercizioCorrentiDifferiteAnticipateImposteDifferiteAnticipate : 0;

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

            // dd($periodStart, $periodEnd, $daysToYear, $bilancioJSON);
        }

        $TotaleAttivo = (isset($bilancioJSON->TotaleAttivo) ? $bilancioJSON->TotaleAttivo : 0);
        $CostiProduzioneAltriAccantonamenti = (isset($bilancioJSON->CostiProduzioneAltriAccantonamenti) ? $bilancioJSON->CostiProduzioneAltriAccantonamenti : 0);
        $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni = (isset($bilancioJSON->CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni) ? $bilancioJSON->CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni : 0);

        $setBilancioData = $bilancioCalculationHelper->setBilancioData($bilancioJSON);

        // ### PATRIMONIO_NETTO
        $PN_NEGATIVO = $bilancioCalculationHelper->getPatrimonioNettoNegativo();
        $TotalePatrimonioNetto = $bilancioCalculationHelper->getTotalePatrimonioNetto();
        $arrayConVoci['PATRIMONIO_NETTO'] = array('TotalePatrimonioNetto', 'TotaleCreditiVersoSociVersamentiAncoraDovuti');


        $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo : 0));
        $UtilePerditaEsercizio = (isset($bilancioJSON->UtilePerditaEsercizio) ? $bilancioJSON->UtilePerditaEsercizio : 0);

        // Valori bilancio
        // ### OF_RICAVI ###
        $OF_RICAVI = $bilancioCalculationHelper->getOfRicavi();
        $arrayConVoci['OF_Fatturato'] = array('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 'ValoreProduzioneRicaviVenditePrestazioni');

        // ### ADEGUATEZZA_PATRIMONIALE ###
        $ADEGUATEZZA_PATRIMONIALE = $bilancioCalculationHelper->getAdeguatezzaPatrimoniale($bilancioJSON);
        $arrayConVoci['ADEGUATEZZA_PATRIMONIALE'] = array('TotaleDebiti', 'PassivoRateiRisconti');
        $arrayConVoci['ADEGUATEZZA_PATRIMONIALE'] = array_merge($arrayConVoci['ADEGUATEZZA_PATRIMONIALE'], $arrayConVoci['PATRIMONIO_NETTO']);


        $TotaleDebiti = $bilancioCalculationHelper->getTotaleDebiti();


        // ### RITORNO_LIQUIDO_ATTIVO ###
        $getRitornoLiquidoAttivo = $bilancioCalculationHelper->getRitornoLiquidoAttivo();
        // $DebitiEsigibiliEntroEsercizioSuccessivo = $getRitornoLiquidoAttivo['DebitiEsigibiliEntroEsercizioSuccessivo'];
        $TotaleDisponibilitaLiquide = $getRitornoLiquidoAttivo['TotaleDisponibilitaLiquide'];
        $AttivoRateiRisconti = $getRitornoLiquidoAttivo['AttivoRateiRisconti'];
        $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = $getRitornoLiquidoAttivo['TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni'];
        $TotaleRimanenze = $getRitornoLiquidoAttivo['TotaleRimanenze'];
        // $TotaleCrediti = $getRitornoLiquidoAttivo['TotaleCrediti'];
        $PassivoRateiRisconti = $getRitornoLiquidoAttivo['PassivoRateiRisconti'];


        $TotaleCreditiEntroDodiciMesi = $bilancioCalculationHelper->getTotaleCreditiEntroDodiciMesi();
        $TotaleDebitiEntroDodiciMesi = $bilancioCalculationHelper->getTotaleDebitiEntroDodiciMesi();

        // ### LIQUIDITA ###
        $CostiProduzioneAccantonamentiRischi = (isset($bilancioJSON->CostiProduzioneAccantonamentiRischi) ? $bilancioJSON->CostiProduzioneAccantonamentiRischi : 0);
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari;
        $ValoreProduzioneRicaviVenditePrestazioni = $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni;
        if ($TotaleAttivo == 0) {
            $LIQUIDITA = 0;
            $dataAnalisis['LIQUIDITA'] = $LIQUIDITA . '%';
        } else {
            $LIQUIDITA = number_format((float)(($UtilePerditaEsercizio + (float)$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni + (float)$CostiProduzioneAccantonamentiRischi + (float)$CostiProduzioneAltriAccantonamenti) / (float)$TotaleAttivo) * 100, 2, ',', '');
            $dataAnalisis['LIQUIDITA'] = $LIQUIDITA . '%';
        }
        $arrayConVoci['LIQUIDITA'] = array('UtilePerditaEsercizio', 'CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 'CostiProduzioneAccantonamentiRischi', 'CostiProduzioneAltriAccantonamenti', 'TotaleAttivo');

        // ### INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO ###
        $DebitiDebitiTributariTotaleDebitiTributariCorrente = (isset($bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari) ? $bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari : 0);
        $DebitiDebitiTributariTotaleDebitiTributari = (isset($bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari) ? $bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari : 0);
        $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale = (isset($bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) ? $bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale : 0);
        $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari;
        $ValoreProduzioneRicaviVenditePrestazioni = $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni;
        if ($TotaleAttivo == 0) {
            $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO = number_format((($DebitiDebitiTributariTotaleDebitiTributari + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) / 1) * 100, 2, ',', '');
            $dataAnalisis['INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO'] = $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO . '%';
        } else {
            $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO = number_format((($DebitiDebitiTributariTotaleDebitiTributari + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) / $TotaleAttivo) * 100, 2, ',', '');
            $dataAnalisis['INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO'] = $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO . '%';
        }

        $arrayConVoci['INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO'] = array('DebitiDebitiTributariTotaleDebitiTributari', 'DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale', 'TotaleAttivo');

        // dd($INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO);

        // INDICI ADVANCED

        //### Andamento del fatturato
        $ValoreProduzioneRicaviVenditePrestazioniCurr = (isset($bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni) ? $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni : 0);
        $ValoreProduzioneRicaviVenditePrestazioniPrev = (isset($bilancioJSONprev->ValoreProduzioneRicaviVenditePrestazioni) ? $bilancioJSONprev->ValoreProduzioneRicaviVenditePrestazioni : 0);
        if ($ValoreProduzioneRicaviVenditePrestazioniPrev == 0) {
            $AndamentoDelFatturato = number_format((float)(- (1 - (($ValoreProduzioneRicaviVenditePrestazioniCurr) / (1)))) * 100, 2, ',', '');
            $dataAnalisis['Andamento_del_fatturato'] = $AndamentoDelFatturato . '%';
        } else {
            $AndamentoDelFatturato = number_format((float)(- (1 - (($ValoreProduzioneRicaviVenditePrestazioniCurr) / ($ValoreProduzioneRicaviVenditePrestazioniPrev)))) * 100, 2, ',', '');
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

        $MOLcurr = $TotaleValoreProduzione - (float)$CostiProduzioneMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneGodimentoBeniTerzi - (float)$CostiProduzioneServizi - (float)$CostiProduzionePersonaleTotaleCostiPersonale - (float)$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneOneriDiversiGestione;
        $MOLprev = $TotaleValoreProduzionePrecedente - (float)$CostiProduzioneMateriePrimeSussidiarieConsumoMerciPrecedente - (float)$CostiProduzioneGodimentoBeniTerziPrecedente - (float)$CostiProduzioneServiziPrecedente - (float)$CostiProduzionePersonaleTotaleCostiPersonalePrecedente - (float)$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerciPrecedente - (float)$CostiProduzioneOneriDiversiGestionePrecedente;

        if ($MOLprev == 0) {
            $AndamentoMOL = number_format(- (1 - ($MOLcurr / 1)) * 100, 2, ',', '');
            $dataAnalisis['Andamento_del_MOL'] = $AndamentoMOL . '%';
        } else {
            $AndamentoMOL = number_format(- (1 - ($MOLcurr / $MOLprev)) * 100, 2, ',', '');
            $dataAnalisis['Andamento_del_MOL'] = $AndamentoMOL . '%';
        }

        $arrayConVoci['Andamento_del_MOL'] = array('TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzioneServizi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione');

        // ### ROI

        $DifferenzaValoreCostiProduzione = (isset($bilancioJSON->DifferenzaValoreCostiProduzione) ? $bilancioJSON->DifferenzaValoreCostiProduzione : 0);
        if ($TotaleAttivo == 0) {
            $ROI = number_format((float)($DifferenzaValoreCostiProduzione / 1) * 100, 2, ',', '');
            $dataAnalisis['ROI'] = $ROI . '%';
        } else {
            $ROI = number_format((float)($DifferenzaValoreCostiProduzione / $TotaleAttivo) * 100, 2, ',', '');
            $dataAnalisis['ROI'] = $ROI . '%';
        }

        $arrayConVoci['ROI'] = array('DifferenzaValoreCostiProduzione', 'TotaleAttivo');

        // ### ROS

        if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
            $ROS = number_format((float)($DifferenzaValoreCostiProduzione / 1) * 100, 2, ',', '');
            $dataAnalisis['ROS'] = $ROS . '%';
        } else {
            $ROS = number_format((float)($DifferenzaValoreCostiProduzione / $ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '');
            $dataAnalisis['ROS'] = $ROS . '%';
        }

        $arrayConVoci['ROS'] = array('DifferenzaValoreCostiProduzione', 'ValoreProduzioneRicaviVenditePrestazioni');

        //### ROE

        if ($TotalePatrimonioNetto == 0) {
            $ROE = number_format((float)($UtilePerditaEsercizio / 1) * 100, 2, ',', '');
            $dataAnalisis['ROE'] = $ROE . '%';
        } else {
            $ROE = number_format((float)($UtilePerditaEsercizio / $TotalePatrimonioNetto) * 100, 2, ',', '');
            $dataAnalisis['ROE'] = $ROE . '%';
        }

        $arrayConVoci['ROE'] = array('UtilePerditaEsercizio', 'TotalePatrimonioNetto');

        //### EBITDA/Fatturato

        if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
            $EBITDA_FATTURATO = number_format((float)(($TotaleValoreProduzione - (float)$CostiProduzioneMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneServizi - (float)$CostiProduzioneGodimentoBeniTerzi - (float)$CostiProduzionePersonaleTotaleCostiPersonale - (float)$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneOneriDiversiGestione) / 1) * 100, 2, ',', '');
            $dataAnalisis['EBITDA_Fatturato'] = $EBITDA_FATTURATO . '%';
        } else {
            $EBITDA_FATTURATO = number_format((float)(($TotaleValoreProduzione - (float)$CostiProduzioneMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneServizi - (float)$CostiProduzioneGodimentoBeniTerzi - (float)$CostiProduzionePersonaleTotaleCostiPersonale - (float)$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneOneriDiversiGestione) / $ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '');
            $dataAnalisis['EBITDA_Fatturato'] = $EBITDA_FATTURATO . '%';
        }

        $arrayConVoci['EBITDA_Fatturato'] = array('TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneServizi', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione', 'ValoreProduzioneRicaviVenditePrestazioni');

        //### Andamento dei mezzi propri

        $TotalePatrimonioNettoCurr = (isset($bilancioJSON->TotalePatrimonioNetto) ? $bilancioJSON->TotalePatrimonioNetto : 0);
        $TotalePatrimonioNettoPrev = (isset($bilancioJSONprev->TotalePatrimonioNetto) ? $bilancioJSONprev->TotalePatrimonioNetto : 0);
        if ($TotalePatrimonioNettoPrev == 0) {
            $AndamentoDeiMezziPropri = number_format((float)(($TotalePatrimonioNettoCurr / 1) - 1) * 100, 2, ',', '');
            $dataAnalisis['Andamento_dei_mezzi_propri'] = $AndamentoDeiMezziPropri . '%';
        } else {
            $AndamentoDeiMezziPropri = number_format((float)(($TotalePatrimonioNettoCurr / $TotalePatrimonioNettoPrev) - 1) * 100, 2, ',', '');
            $dataAnalisis['Andamento_dei_mezzi_propri'] = $AndamentoDeiMezziPropri . '%';
        }

        $arrayConVoci['Andamento_dei_mezzi_propri'] = array('TotalePatrimonioNetto');

        //### Margine Struttura Primario
        $TotaleImmobilizzazioni = (isset($bilancioJSON->TotaleImmobilizzazioni) ? $bilancioJSON->TotaleImmobilizzazioni : 0);
        if ($TotaleImmobilizzazioni == 0) {
            $Margine_Struttura_Primario = number_format((float)($TotalePatrimonioNetto / 1) * 100, 2, ',', '');
            $dataAnalisis['Margine_Struttura_Primario'] = $Margine_Struttura_Primario;
        } else {
            $Margine_Struttura_Primario = number_format((float)($TotalePatrimonioNetto / $TotaleImmobilizzazioni) * 100, 2, ',', '');
            $dataAnalisis['Margine_Struttura_Primario'] = $Margine_Struttura_Primario;
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
        // $DebitiEsigibiliOltreEsercizioSuccessivo = isset($bilancioJSON->DebitiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiEsigibiliOltreEsercizioSuccessivo : 0;


        // $QuarantaTre = $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo + (float)$DebitiAccontiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo + (float)$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo; //(isset($bilancioJSON->DebitiOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiOltreEsercizioSuccessivo : 0);

        if ($TotaleImmobilizzazioni == 0) {
            $Margine_Struttura_Secondario_Semplificato = number_format((float)(($TotalePatrimonioNetto + $TrattamentoFineRapportoLavoroSubordinato + (float)$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo + (float)$DebitiAccontiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo + (float)$DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) / 1) * 100, 2, ',', '');
            // $Margine_Struttura_Secondario_Ordinario = number_format((float)(($TotalePatrimonioNetto + $TrattamentoFineRapportoLavoroSubordinato + $QuarantaTre) / 1) * 100, 2, ',', '');
            $dataAnalisis['Margine_Struttura_Secondario'] = $Margine_Struttura_Secondario_Semplificato;
        } else {
            $Margine_Struttura_Secondario_Semplificato = number_format((float)(($TotalePatrimonioNetto + $TrattamentoFineRapportoLavoroSubordinato + (float)$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo + (float)$DebitiAccontiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo + (float)$DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) / $TotaleImmobilizzazioni) * 100, 2, ',', '');
            // $Margine_Struttura_Secondario_Ordinario = number_format((float)(($TotalePatrimonioNetto + $TrattamentoFineRapportoLavoroSubordinato + $QuarantaTre) / $TotaleImmobilizzazioni) * 100, 2, ',', '');
            $dataAnalisis['Margine_Struttura_Secondario'] = $Margine_Struttura_Secondario_Semplificato;
        }

        $arrayConVoci['Margine_Struttura_Secondario'] = array('TotalePatrimonioNetto', 'TrattamentoFineRapportoLavoroSubordinat', 'DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 'DebitiAccontiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo', 'DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo', 'TotaleImmobilizzazioni');

        // CURRENT RADIO (VEDI INDICE RITORNO LIQUIDO ATT)
        $denominatoreRitornoLiquidoAttivo = 0;
        if (((float)$TotaleDebitiEntroDodiciMesi + (float)$PassivoRateiRisconti) == 0) {
            $denominatoreRitornoLiquidoAttivo = 1;
        }

        $formula = ((float)$TotaleDisponibilitaLiquide + (float)$AttivoRateiRisconti + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$TotaleRimanenze + (float)$TotaleCreditiEntroDodiciMesi) / ((float)$TotaleDebitiEntroDodiciMesi + (float)$PassivoRateiRisconti + (float)$denominatoreRitornoLiquidoAttivo);

        $RITORNO_LIQUIDO_ATTIVO = number_format((float)$formula * 100, 2, ',', '');
        $dataAnalisis['Current_Ratio'] = $RITORNO_LIQUIDO_ATTIVO . '%';
        $arrayConVoci['Current_Ratio'] = array('TotaleDisponibilitaLiquide', 'AttivoRateiRisconti', 'TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 'TotaleRimanenze', 'TotaleCreditiEntroDodiciMesi', 'TotaleDebitiEntroDodiciMesi', 'PassivoRateiRisconti');

        //### Attivita a breve / Passività a Breve

        // TRENTACINQUE
        $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo : 0);
        $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo : 0);
        // $CreditiVersoControllantiEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoControllantiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoControllantiEsigibiliOltreEsercizioSuccessivo : 0);
        $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo : 0));
        $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo : 0));
        $TrentaCinque = $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo;
        // $CreditiCreditiTributariTotaleCreditiTributari = (isset($bilancioJSON->CreditiCreditiTributariTotaleCreditiTributari) ? $bilancioJSON->CreditiCreditiTributariTotaleCreditiTributari : $val = (isset($bilancioJSONprev->CreditiCreditiTributariTotaleCreditiTributari) ? $bilancioJSONprev->CreditiCreditiTributariTotaleCreditiTributari : 0));

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
        $QuarantaNove = $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAccontiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo;
        $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo : 0);

        /* if ($QuarantaNove > 0 || $PassivoRateiRisconti > 0) {
            $Attivita_a_breve_Passivita_a_Breve_Semplificato = number_format((float)((((float)$TotaleDisponibilitaLiquide + (float)$TrentaCinque + (float)$TotaleRimanenze + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$AttivoRateiRisconti) / ((float)$QuarantaNove + (float)$PassivoRateiRisconti))), 2, ',', '');
            //$dataAnalisis['Attivita_a_breve_Passività_a_Breve_Semplificato'] = $Attivita_a_breve_Passivita_a_Breve_Semplificato.'%';
        }  */
        $Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore = (float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAccontiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + (float)$PassivoRateiRisconti;

        if ($Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore > 0) {
            $Attivita_a_breve_Passivita_a_Breve_Ordinario = number_format((float)((($TotaleDisponibilitaLiquide + (float)$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo + (float)$TotaleRimanenze + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$AttivoRateiRisconti) / ($Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore))) * 100, 2, ',', '');
            $dataAnalisis['Attivita_a_breve_Passività_a_Breve_Ordinario'] = $Attivita_a_breve_Passivita_a_Breve_Ordinario . '%';
        }

        $arrayConVoci['Attivita_a_breve_Passività_a_Breve_Ordinario'] = array();

        // ACID TEST
        /* if ($DebitiEsigibiliEntroEsercizioSuccessivo > 0 || $PassivoRateiRisconti > 0) {
                $AcidTest = number_format((float)(((float)$TotaleCrediti + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$TotaleDisponibilitaLiquide + (float)$AttivoRateiRisconti) / ((float)$DebitiEsigibiliEntroEsercizioSuccessivo + (float)$PassivoRateiRisconti)), 2, ',', '');
             // $dataAnalisis['AcidTest'] = $AcidTest.'%';
        } */

        /* if ($QuarantaNove > 0 || $PassivoRateiRisconti > 0) {
            $ACID_TEST_Semplificato = number_format((float)((((float)$TotaleDisponibilitaLiquide + (float)$TrentaCinque + (float)$TotaleRimanenze + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$AttivoRateiRisconti - (float)$TotaleRimanenze) / ((float)$QuarantaNove + (float)$PassivoRateiRisconti))), 2, ',', '');
            // $dataAnalisis['ACID_TEST_Semplificato'] = $ACID_TEST_Semplificato.'%';
        } */

        $ACID_TEST_Ordinario_divisore = (float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAccontiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + (float)$PassivoRateiRisconti;

        if ($ACID_TEST_Ordinario_divisore > 0) {
            $ACID_TEST_Ordinario = number_format((float)((((float)$TotaleDisponibilitaLiquide + (float)$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo + (float)$TotaleRimanenze + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$AttivoRateiRisconti - $TotaleRimanenze) / ($ACID_TEST_Ordinario_divisore))) * 100, 2, ',', '');
            $dataAnalisis['Acid_Test'] = $ACID_TEST_Ordinario . '%';
        }
        //dd('TotaleDisponibilitaLiquide', $TotaleDisponibilitaLiquide, 'CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo', $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo, 'CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo, 'CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo, 'CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo', $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo, 'CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo, 'CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo, 'TotaleRimanenze', $TotaleRimanenze, 'TotaleRimanenze', $TotaleRimanenze, 'AttivoRateiRisconti', $AttivoRateiRisconti, 'TotaleRimanenze', $TotaleRimanenze, 'DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo, 'DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo, 'DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo, 'DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo, 'DebitiAccontiEsigibiliEntroEsercizioSuccessivo', $DebitiAccontiEsigibiliEntroEsercizioSuccessivo, 'DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo, 'DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo', $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo, 'DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo, 'DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo, 'DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo', $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo, 'DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo, 'DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo, 'DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo', $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo, 'DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo, 'PassivoRateiRisconti', $PassivoRateiRisconti);
        $arrayConVoci['Acid_Test'] = array('TotaleDisponibilitaLiquide', 'CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo', 'CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 'CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 'CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 'CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 'CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 'TotaleRimanenze', 'TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 'AttivoRateiRisconti', 'TotaleRimanenze', 'DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 'DebitiAccontiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 'DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 'PassivoRateiRisconti');

        // AUTONOMIA FINANZIARIA
        if (($TotalePatrimonioNetto + $TotaleDebiti) == 0) {
            $AUTONOMIA_FINANZIARIA = number_format((float)(($TotalePatrimonioNetto / 1)) * 100, 2, ',', '');
            $dataAnalisis['Autonomia_Finanziaria'] = $AUTONOMIA_FINANZIARIA . '%';
        } else {
            $AUTONOMIA_FINANZIARIA = number_format((float)(($TotalePatrimonioNetto / ($TotalePatrimonioNetto + $TotaleDebiti))) * 100, 2, ',', '');
            $dataAnalisis['Autonomia_Finanziaria'] = $AUTONOMIA_FINANZIARIA . '%';
        }

        $arrayConVoci['Autonomia_Finanziaria'] = array('TotalePatrimonioNetto', 'TotalePatrimonioNetto', 'TotaleDebiti');

        // LIVELLO INVESTIMENTI AZIENDALI
        if ($TotaleAttivo == 0) {
            $LIVELLO_INVESTIMENTI_AZIENDALI = number_format((float)($TotalePatrimonioNetto / 0.1) * 100, 2, ',', '');
            $dataAnalisis['Livello_investimenti_aziendali'] = $LIVELLO_INVESTIMENTI_AZIENDALI . '%';
        } else {
            $LIVELLO_INVESTIMENTI_AZIENDALI = number_format((float)($TotalePatrimonioNetto / $TotaleAttivo) * 100, 2, ',', '');
            $dataAnalisis['Livello_investimenti_aziendali'] = $LIVELLO_INVESTIMENTI_AZIENDALI . '%';
        }

        $arrayConVoci['Livello_investimenti_aziendali'] = array('TotalePatrimonioNetto', 'TotaleAttivo');


        // PFN / EBITDA
        $ImmobilizzazioniFinanziarieCreditiTotaleCrediti = (isset($bilancioJSON->ImmobilizzazioniFinanziarieCreditiTotaleCrediti) ? $bilancioJSON->ImmobilizzazioniFinanziarieCreditiTotaleCrediti : 0);
        $debitiFinanziariCurr = $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;
        if ($MOLcurr == 0) {
            $PFN_EBITDA = number_format((float)(($debitiFinanziariCurr - $TotaleDisponibilitaLiquide - $ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / 1), 2, '.', ',');
            $dataAnalisis['PFN_EBITDA'] = (float)$PFN_EBITDA * 100;
        } else {
            $PFN_EBITDA = number_format((float)(((float)$debitiFinanziariCurr - (float)$TotaleDisponibilitaLiquide - (float)$ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / (float)$MOLcurr), 2, '.', ',');
            $dataAnalisis['PFN_EBITDA'] = (float)$PFN_EBITDA * 100;
        }
        // dd($PFN_EBITDA, $OF_RICAVI);

        $arrayConVoci['PFN_EBITDA'] = array('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 'TotaleDisponibilitaLiquide', 'ImmobilizzazioniFinanziarieCreditiTotaleCrediti', 'TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzioneServizi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione');

        // Peso Oneri Finanziari (OF/Fatturato)
        $denominatoreOFfatturato = $ValoreProduzioneRicaviVenditePrestazioni;
        if ($denominatoreOFfatturato == 0) {
            $denominatoreOFfatturato = 1;
        }
        $Peso_Oneri_Finanziari = number_format((float)($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari / $denominatoreOFfatturato) * 100,  2, ',', '');
        $dataAnalisis['Peso_Oneri_Finanziari'] = $Peso_Oneri_Finanziari . '%';
        $arrayConVoci['Peso_Oneri_Finanziari'] = array('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 'ValoreProduzioneRicaviVenditePrestazioni');

        // Copertura Lorda degli Oneri Finanziari
        if ($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari == 0) {
            $Copertura_Lorda_degli_Oneri_Finanziari = number_format((float)(($TotaleValoreProduzione - (float)$CostiProduzioneMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneServizi - (float)$CostiProduzioneGodimentoBeniTerzi - (float)$CostiProduzionePersonaleTotaleCostiPersonale - (float)$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneOneriDiversiGestione) / 1), 2, '.', ',');
            $dataAnalisis['Copertura_Lorda_OF'] = (float)$Copertura_Lorda_degli_Oneri_Finanziari * 100;
        } else {
            $Copertura_Lorda_degli_Oneri_Finanziari = number_format((float)(($TotaleValoreProduzione - (float)$CostiProduzioneMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneServizi - (float)$CostiProduzioneGodimentoBeniTerzi - (float)$CostiProduzionePersonaleTotaleCostiPersonale - (float)$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneOneriDiversiGestione) / $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari), 2, '.', ',');
            $dataAnalisis['Copertura_Lorda_OF'] = (float)$Copertura_Lorda_degli_Oneri_Finanziari * 100;
        }

        $arrayConVoci['Copertura_Lorda_OF'] = array('TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneServizi', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione', 'ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari');

        // EBIT / OF
        if ($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari == 0) {
            $EBIT_OF = number_format((float)(($TotaleValoreProduzione - (float)$CostiProduzioneMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneServizi - (float)$CostiProduzioneGodimentoBeniTerzi - (float)$CostiProduzionePersonaleTotaleCostiPersonale - (float)$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneOneriDiversiGestione - (float)$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - (float)$CostiProduzioneAccantonamentiRischi - (float)$CostiProduzioneAltriAccantonamenti) / 1), 2, '.', ',');
            $dataAnalisis['EBIT_OF'] = (float)$EBIT_OF * 100;
        } else {
            $EBIT_OF = number_format((float)(((float)$TotaleValoreProduzione - (float)$CostiProduzioneMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneServizi - (float)$CostiProduzioneGodimentoBeniTerzi - (float)$CostiProduzionePersonaleTotaleCostiPersonale - (float)$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneOneriDiversiGestione - (float)$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - (float)$CostiProduzioneAccantonamentiRischi - (float)$CostiProduzioneAltriAccantonamenti) / (float)$ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari), 2, '.', ',');
            $dataAnalisis['EBIT_OF'] = (float)$EBIT_OF * 100;
        }

        $arrayConVoci['EBIT_OF'] = array('TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneServizi', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione', 'CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 'CostiProduzioneAccantonamentiRischi', 'CostiProduzioneAltriAccantonamenti', 'ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari');

        //Costo del personale
        if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
            $Costo_del_personale = number_format((float)($CostiProduzionePersonaleTotaleCostiPersonale / 1) * 100, 2, ',', '');
            $dataAnalisis['Costo_del_personale'] = $Costo_del_personale . '%';
        } else {
            $Costo_del_personale = number_format((float)($CostiProduzionePersonaleTotaleCostiPersonale / $ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '');
            $dataAnalisis['Costo_del_personale'] = $Costo_del_personale . '%';
        }

        $arrayConVoci['Costo_del_personale'] = array('CostiProduzionePersonaleTotaleCostiPersonale', 'ValoreProduzioneRicaviVenditePrestazioni');

        // CF / Attivo
        if ($TotaleAttivo == 0) {
            $CreditiImposteAnticipateTotaleImposteAnticipate = (isset($bilancioJSON->CreditiImposteAnticipateTotaleImposteAnticipate) ? $bilancioJSON->CreditiImposteAnticipateTotaleImposteAnticipate : 0);
            $CF_ATTIVO = number_format((float)(($UtilePerditaEsercizio + (float)$CostiProduzioneAccantonamentiRischi + (float)$CostiProduzioneAltriAccantonamenti + (float)$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - $imposteRedditoEsercizioImposteAnticipate) / 0.1) * 100, 2, '.', ',');
            $dataAnalisis['CF_Attivo'] = $CF_ATTIVO . '%';
        } else {
            $CreditiImposteAnticipateTotaleImposteAnticipate = (isset($bilancioJSON->CreditiImposteAnticipateTotaleImposteAnticipate) ? $bilancioJSON->CreditiImposteAnticipateTotaleImposteAnticipate : 0);
            $CF_ATTIVO = number_format((float)(((float)$UtilePerditaEsercizio + (float)$CostiProduzioneAccantonamentiRischi + (float)$CostiProduzioneAltriAccantonamenti + (float)$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - (float)$imposteRedditoEsercizioImposteAnticipate) / (float)$TotaleAttivo) * 100, 2, '.', ',');
            $dataAnalisis['CF_Attivo'] = $CF_ATTIVO . '%';
        }


        $arrayConVoci['CF_Attivo'] = array('UtilePerditaEsercizio', 'CostiProduzioneAccantonamentiRischi', 'CostiProduzioneAltriAccantonamenti', 'CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 'CreditiImposteAnticipateTotaleImposteAnticipate', 'TotaleAttivo');

        //Indice di Indebitamento (PFN/PN)
        if ($TotalePatrimonioNetto == 0) {
            $MOLannoCorrente = $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;
            $Indice_di_Indebitamento = number_format((float)(($MOLannoCorrente - $TotaleDisponibilitaLiquide - $ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / 0.1) * 100, 2, ',', '');
            $dataAnalisis['Indice_di_Indebitamento'] = $Indice_di_Indebitamento . '%';
        } else {
            $MOLannoCorrente = $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;
            $Indice_di_Indebitamento = number_format((float)(((float)$MOLannoCorrente - (float)$TotaleDisponibilitaLiquide - (float)$ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / (float)$TotalePatrimonioNetto) * 100, 2, ',', '');
            $dataAnalisis['Indice_di_Indebitamento'] = $Indice_di_Indebitamento . '%';
        }

        $arrayConVoci['Indice_di_Indebitamento'] = array('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 'TotaleDisponibilitaLiquide', 'ImmobilizzazioniFinanziarieCreditiTotaleCrediti', 'TotalePatrimonioNetto');

        // $indiciBilancio = array();

        //SALDO DEBITI VS FISCO

        $DebitiDebitiTributariTotaleDebitiTributariCorrente = (isset($bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari) ? $bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari : 0);
        $FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente = (isset($bilancioJSON->FondiRischiOneriTrattamentoQuiescenzaObblighiSimili) ? $bilancioJSON->FondiRischiOneriTrattamentoQuiescenzaObblighiSimili : 0);
        $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipatePrecedente = isset($bilancioJSONprev->ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate) ? $bilancioJSONprev->ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate : 0;
        $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate = isset($bilancioJSON->ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate) ? $bilancioJSON->ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate : 0;

        $DifferenzaImposteReddito = ($ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate + $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipatePrecedente) / 2;
        if ($DifferenzaImposteReddito == 0) {
            $SaldoDebitiVSFisco = number_format(($FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente + (float)$DebitiDebitiTributariTotaleDebitiTributariCorrente) / 1, 2, '.', ',');
            $dataAnalisis['Saldo_dei_Debiti_verso_il_Fisco'] = $SaldoDebitiVSFisco * 100;
        } else {
            $SaldoDebitiVSFisco = number_format(((float)$FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente + (float)$DebitiDebitiTributariTotaleDebitiTributariCorrente) / (float)$DifferenzaImposteReddito, 2, '.', ',');
            $dataAnalisis['Saldo_dei_Debiti_verso_il_Fisco'] = (float)$SaldoDebitiVSFisco * 100;
        }

        $arrayConVoci['Saldo_dei_Debiti_verso_il_Fisco'] = array('FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente', 'DebitiDebitiTributariTotaleDebitiTributariCorrente', 'DifferenzaImposteReddito');



        $explodedDate = new DateTime((explode(' ', $bilancio->year))[0]);

        $valutazioneBilancio = $this->valutazioneIndici($dataAnalisis, $tipoAzienda, $explodedDate->format('Y'));

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
}
