<?php

namespace App\Helpers\Bilanci;

use App\Http\Requests;
use App;
use App\Models\Bilanci;
use App\Models\Basic;
use App\Models\Voci;
use App\Models\Account;
use App\Models\cr;
use App\Models\soglie;
use App\Models\range;
use App\Models\Roe;
use App\Models\Document;
use Exception;
use App\Helpers\Bilanci\BilanciCalculationsHelper;
use App\Models\CustomLog;
use XBRL_DFR;
use XBRL_Global;
use XBRL\XBRL_Types;
use XBRL_Instance;
use App\Helpers\Bilanci\BilanciCalculationsHelperAdvanced;
use App\Helpers\Bilanci\BilanciCalculationsHelperSemplified;
use Laravel\Passport\Token;
use App\Domains\Auth\Models\User;

class BilanciHelper
{
    public function indexesToFormat($indexValue)
    {
        if (isset($indexValue['Basic'])) {
            foreach ($indexValue['Basic'] as $key => $singleIndexBasic) {
                if (isset($singleIndexBasic['value'])) {
                    $indexValue['Basic'][$key]['value'] = number_format($singleIndexBasic['value'], 2, ',', '.');
                }
            }
        }

        if (isset($indexValue['Advanced'])) {
            foreach ($indexValue['Advanced'] as $key => $singleIndexAdvanced) {
                if (isset($singleIndexAdvanced['value'])) {
                    $indexValue['Advanced'][$key]['value'] = number_format($singleIndexAdvanced['value'], 2, ',', '.');
                } elseif (!$indexValue['Advanced'][$key]) {
                    $indexValue['Advanced'][$key] = false;
                } else {
                    $indexValue['Advanced'][$key] = number_format($singleIndexAdvanced, 2, ',', '.');
                }
            }
        }

        return $indexValue;
    }

    public function getIndexesForBalanceTaxonomy($idBilancio, $filePath = false, $instance = false, $codiceDocumento = false, $userID = false)
    {

        $vocis = Voci::pluck('name', 'extended_name')->all();

        if ($userID) {
            $user = User::findOrFail($userID);

            if ($user->modAnalisi === 1) {
                // Analisi semplificata
                $calculationHelper = new BilanciCalculationsHelperSemplified();
            } else {
                // Analisi avanzata
                $calculationHelper = new BilanciCalculationsHelperAdvanced();
            }
        } else {
            $calculationHelper = new BilanciCalculationsHelperAdvanced;
        }

        $calculationHelper->documentId = $idBilancio;
        $calculationHelper->codice_documento = $codiceDocumento;

        if ($instance == true) {
            $calculationHelper->setCurrentInstance($instance);
        } else {
            $document = Document::findOrFail($idBilancio);
            $filePath = base_path() . '/public/bilanci/' . $document->filename;
            $taxonomyName = $document->taxonomy;
            $taxonomyPath = base_path() . "/taxonomies/2018-11-04/" . $taxonomyName;

            $readXBRL = XBRL_Instance::FromInstanceDocument($filePath, $taxonomyPath, $emptyInstance);
            $calculationHelper->setCurrentInstance($readXBRL);
        }

        $indexFormattedValue = [
            "Basic" => [
                "Sostenibilità Oneri Finanziari" => $calculationHelper->getSostenibilitaOneriFinanziari(),
                "Adeguatezza Patrimoniale" => $calculationHelper->getAdeguatezzaPatrimonialeEvaluation(),
                "Liquidità" => $calculationHelper->getLiquiditaEvaluation(),
                "Indebitamento Previdenziale Tributario" => $calculationHelper->getIndebitamentoPrevidenziale(),
                "Ritorno Liquido Attivo" => $calculationHelper->getRitornoLiquidoAttivo(),
                "Indice CNDCEC" => $calculationHelper->getIndiceCNDCEC(),
            ],
            "Advanced" => [
                'OF Ricavi' => $calculationHelper->getOfRicavi(),
                'Adeguatezza Patrimoniale' => $calculationHelper->getAdeguatezzaPatrimoniale(),
                'Liquidità' => $calculationHelper->getLiquidita(),
                'Andamento del fatturato' => $calculationHelper->getAndamentoDelFatturato(),
                'Andamento del MOL' => ($calculationHelper->getAndamentoDelMol('Andamento Del Mol')) ? $calculationHelper->getAndamentoDelMol('Andamento Del Mol')['AndamentoMOL'] : false,
                'ROI' => $calculationHelper->getROI(),
                'ROS' => $calculationHelper->getROS(),
                'ROE' => $calculationHelper->getROE(),
                'EBITDA Fatturato' => $calculationHelper->getEbitdaFatturato(),
                'Andamento dei mezzi propri' => $calculationHelper->getAndamentoDeiMezziPropri(),
                'Margine Struttura Primario' => $calculationHelper->getMargineStrutturaPrimario(),
                'Margine Struttura Secondario' => $calculationHelper->getMargineStrutturaSecondario(),
                'Current Ratio' => $calculationHelper->getCurrentRatio(),
                'Attivita Passivita a Breve' => ($calculationHelper->getAttivitaPassivitaABreve('Attivita Passivita a Breve')) ? $calculationHelper->getAttivitaPassivitaABreve('Attivita Passivita a Breve')['Attivita_a_breve_Passività_a_Breve_Ordinario'] : false,
                'Acid Test' => $calculationHelper->getAcidTest(),
                'Acid Test Ordinario' => $calculationHelper->getAcidTestOrdinario(),
                'Autonomia Finanziaria' => $calculationHelper->getAutonomiaFinanziaria(),
                'Livello investimenti aziendali' => $calculationHelper->getLivelloInvestimentiAziendali(),
                'PFN EBITDA' => $calculationHelper->getPfnEbitda(),
                'Peso Oneri Finanziari' => $calculationHelper->getPesoOneriFinanziari(),
                'Copertura Lorda OF' => $calculationHelper->getCoperturaLordaDegliOneriFinanziari(),
                'EBIT OF' => $calculationHelper->getEbitOf(),
                'Costo Del Personale' => $calculationHelper->getCostoDelPersonale(),
                'CF Attivo' => $calculationHelper->getCfAttivo(),
                'Indice di Indebitamento' => $calculationHelper->getIndiceDiIndebitamento(),
                'Saldo dei Debiti verso il Fisco' => $calculationHelper->getSaldoDebitiVsFisco(),
            ]
        ];

        $indexFormattedValue = $this->indexesToFormat($indexFormattedValue);

        return array(
            "Indici" => $indexFormattedValue,
            'ValuesFromDb' => $calculationHelper->getMissingVoicesFromDb(),
            'indiceVociMancanti' => $calculationHelper->_missingVoicesArray,
            'labels' => $vocis,
            'Questionari' => [
                'Questionario DSCR' => $calculationHelper->getDSCRData(),
                'Agenzia delle Entrate' => $calculationHelper->getAgenziaEntrateData(),
                'INPS' => $calculationHelper->getInpsData(),
                'Agente della Riscossione' => $calculationHelper->getRiscossioneData(),
                'Debiti per Retribuzioni' => $calculationHelper->getRetribuzioniData(),
                'Debiti verso Fornitori' => $calculationHelper->getFornitoriData()
            ]
        );
    }

    public function getIndexesForBalanceTaxonomyForAi($idBilancio, $filePath = false, $instance = false, $codiceDocumento = false, $userID = false)
    {

        $vocis = Voci::pluck('name', 'extended_name')->all();

        if ($userID) {
            $user = User::findOrFail($userID);

            if ($user->modAnalisi === 1) {
                // Analisi semplificata
                $calculationHelper = new BilanciCalculationsHelperSemplified();
            } else {
                // Analisi avanzata
                $calculationHelper = new BilanciCalculationsHelperAdvanced();
            }
        } else {
            $calculationHelper = new BilanciCalculationsHelperAdvanced;
        }

        $calculationHelper->documentId = $idBilancio;
        $calculationHelper->codice_documento = $codiceDocumento;

        if ($instance == true) {
            $calculationHelper->setCurrentInstance($instance);
        } else {
            $document = Document::findOrFail($idBilancio);
            $filePath = base_path() . '/public/bilanci/' . $document->filename;
            $taxonomyName = $document->taxonomy;
            $taxonomyPath = base_path() . "/taxonomies/2018-11-04/" . $taxonomyName;

            $readXBRL = XBRL_Instance::FromInstanceDocument($filePath, $taxonomyPath, $emptyInstance);
            $calculationHelper->setCurrentInstance($readXBRL);
        }

        $indexFormattedValue = [
            "Basic" => [
                "Sostenibilità Oneri Finanziari" => $calculationHelper->getSostenibilitaOneriFinanziari(),
                "Adeguatezza Patrimoniale" => $calculationHelper->getAdeguatezzaPatrimonialeEvaluation(),
                "Liquidità" => $calculationHelper->getLiquiditaEvaluation(),
                "Indebitamento Previdenziale Tributario" => $calculationHelper->getIndebitamentoPrevidenziale(),
                "Ritorno Liquido Attivo" => $calculationHelper->getRitornoLiquidoAttivo(),
                "Indice CNDCEC" => $calculationHelper->getIndiceCNDCEC(),
            ],
            "Advanced" => [
                'OF Ricavi' => $calculationHelper->getOfRicavi(),
                'Adeguatezza Patrimoniale' => $calculationHelper->getAdeguatezzaPatrimoniale(),
                'Liquidità' => $calculationHelper->getLiquidita(),
                'Andamento del fatturato' => $calculationHelper->getAndamentoDelFatturato(),
                'Andamento del MOL' => ($calculationHelper->getAndamentoDelMol('Andamento Del Mol')) ? $calculationHelper->getAndamentoDelMol('Andamento Del Mol')['AndamentoMOL'] : false,
                'ROI' => $calculationHelper->getROI(),
                'ROS' => $calculationHelper->getROS(),
                'ROE' => $calculationHelper->getROE(),
                'EBITDA Fatturato' => $calculationHelper->getEbitdaFatturato(),
                'Andamento dei mezzi propri' => $calculationHelper->getAndamentoDeiMezziPropri(),
                'Margine Struttura Primario' => $calculationHelper->getMargineStrutturaPrimario(),
                'Margine Struttura Secondario' => $calculationHelper->getMargineStrutturaSecondario(),
                'Current Ratio' => $calculationHelper->getCurrentRatio(),
                'Attivita Passivita a Breve' => ($calculationHelper->getAttivitaPassivitaABreve('Attivita Passivita a Breve')) ? $calculationHelper->getAttivitaPassivitaABreve('Attivita Passivita a Breve')['Attivita_a_breve_Passività_a_Breve_Ordinario'] : false,
                'Acid Test' => $calculationHelper->getAcidTest(),
                'Acid Test Ordinario' => $calculationHelper->getAcidTestOrdinario(),
                'Autonomia Finanziaria' => $calculationHelper->getAutonomiaFinanziaria(),
                'Livello investimenti aziendali' => $calculationHelper->getLivelloInvestimentiAziendali(),
                'PFN EBITDA' => $calculationHelper->getPfnEbitda(),
                'Peso Oneri Finanziari' => $calculationHelper->getPesoOneriFinanziari(),
                'Copertura Lorda OF' => $calculationHelper->getCoperturaLordaDegliOneriFinanziari(),
                'EBIT OF' => $calculationHelper->getEbitOf(),
                'Costo Del Personale' => $calculationHelper->getCostoDelPersonale(),
                'CF Attivo' => $calculationHelper->getCfAttivo(),
                'Indice di Indebitamento' => $calculationHelper->getIndiceDiIndebitamento(),
                'Saldo dei Debiti verso il Fisco' => $calculationHelper->getSaldoDebitiVsFisco(),
            ]
        ];

        $indexFormattedValue = $this->indexesToFormat($indexFormattedValue);

        return array(
            "Indici" => $indexFormattedValue,
            'Questionari' => [
                'Questionario DSCR' => $calculationHelper->getDSCRData(),
                'Agenzia delle Entrate' => $calculationHelper->getAgenziaEntrateData(),
                'INPS' => $calculationHelper->getInpsData(),
                'Agente della Riscossione' => $calculationHelper->getRiscossioneData(),
                'Debiti per Retribuzioni' => $calculationHelper->getRetribuzioniData(),
                'Debiti verso Fornitori' => $calculationHelper->getFornitoriData()
            ]
        );
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
            if ($value === false || $value === null) {
                $arrayGiudizi[$label] = false;
                continue;
            }

            $value = (float) str_replace(',', '.', $value);
            $arrayIndici[$label] = $value / 100;

            if ($label == 'ROE') {
                $roeRow = Roe::where('year', '<=', $currentYear)->orderBy('year', 'desc')->first();
                $tassoInflazione = $roeRow ? (float) $roeRow->value / 100 : 0.005; // 0.5% default se mancano del tutto

                $value /= 100;
                if ($value < $tassoInflazione + 0.02) {
                    $scoringAreaBilancio += 0.0426 * 0.1;
                    $arrayGiudizi[$label]['Scoring'] = 0.0426 * 0.1;
                    $arrayGiudizi[$label]['Giudizio'] = 'Rischio Elevato';
                } else if ($value >= $tassoInflazione + 0.02 && $value < $tassoInflazione + 0.03) {
                    $scoringAreaBilancio += 0.0426 * 0.3;
                    $arrayGiudizi[$label]['Scoring'] = 0.0426 * 0.3;
                    $arrayGiudizi[$label]['Giudizio'] = 'Situazione Critica';
                } else if ($value >= $tassoInflazione + 0.03 && $value < $tassoInflazione + 0.05) {
                    $scoringAreaBilancio += 0.0426 * 0.6;
                    $arrayGiudizi[$label]['Scoring'] = 0.0426 * 0.6;
                    $arrayGiudizi[$label]['Giudizio'] = 'Buono';
                } else if ($value >= $tassoInflazione + 0.05) {
                    $scoringAreaBilancio += 0.0426 * 1;
                    $arrayGiudizi[$label]['Scoring'] = 0.0426 * 1;
                    $arrayGiudizi[$label]['Giudizio'] = 'Ottimo';
                }

                if (isset($arrayGiudizi[$label]['Scoring'])) {
                    $arrayGiudizi[$label]['Scoring'] = number_format($arrayGiudizi[$label]['Scoring'], 2, ',', '.');
                }
                continue; // ROE ha logica custom, skip DB lookup
            }
            //  $arraySoglie[$label] = range::where([['range_min', '<', $arrayIndici[$label]], ['range_max', '>', $arrayIndici[$label]], ['indice', '=', $label], ['tipo_azienda', '=', $tipoAzienda]])->with('pesi')->get();
            $arraySoglie[$label] = range::where(
                [['range_min', '<', $arrayIndici[$label]], ['range_max', '>', $arrayIndici[$label]], ['indice', '=', $label], ['tipo_azienda', '=', 'Generica']]
            )->with('pesi')
                ->get()
                ->first();


            if ($arraySoglie[$label]) {
                $arrayGiudizi[$label]['Scoring'] = ($arraySoglie[$label]->pesi->peso) * ($arraySoglie[$label]->score);
                $arrayGiudizi[$label]['Giudizio'] = $arraySoglie[$label]->giudizio;
                $scoringAreaBilancio += $arrayGiudizi[$label]['Scoring'];
            } else {
                $arrayGiudizi[$label] = false;
            }

            if (isset($arrayGiudizi[$label]['Scoring'])) {
                $arrayGiudizi[$label]['Scoring'] = number_format($arrayGiudizi[$label]['Scoring'], 2, ',', '.');
            }

        }

        return array("Score" => number_format($scoringAreaBilancio, 2, ',', '.'), "Giudizi" => $arrayGiudizi);
    }

    /**
     * Valutazione complessiva del bilancio su scala 0-1.
     *
     * Combina quattro componenti con i seguenti pesi:
     *   - Indici Basic  (CNDCEC)       → peso 0.45
     *   - Indici Advanced               → peso 0.25
     *   - Questionari Allerta           → peso 0.20
     *   - Completezza dati             → peso 0.10
     *
     * @param array  $bilancioData   Risultato di getIndexesForBalanceTaxonomy()
     * @param string $tipoAzienda    Tipo azienda (Commercio, Industria, Servizi)
     * @param string $currentYear    Anno corrente
     * @return array  ['Score' => string(0-1), 'Giudizio' => string, 'Giudizi' => array, 'Dettaglio' => array]
     */
    public function valutazioneComplessivaBilancio($bilancioData, $tipoAzienda, $currentYear)
    {
        $basicIndices  = $bilancioData['Indici']['Basic'] ?? [];
        $advIndices    = $bilancioData['Indici']['Advanced'] ?? [];
        $questionari   = $bilancioData['Questionari'] ?? [];

        // ── 1) Indici Basic (peso 0.45) ──
        // Ogni indice Basic restituisce { fuoriSoglia: bool, value: mixed } o false
        $basicEvaluable = 0;
        $basicOk = 0;
        foreach ($basicIndices as $label => $indexData) {
            if ($label === 'Indice CNDCEC') continue; // È l'indice riassuntivo, trattato a parte
            if ($indexData === false || $indexData === null) continue;
            if (!is_array($indexData)) continue;

            $basicEvaluable++;
            if (isset($indexData['fuoriSoglia']) && $indexData['fuoriSoglia'] === false) {
                $basicOk++;
            }
        }

        // L'indice CNDCEC ha peso doppio (come nel frontend)
        $summaryWeight = 0;
        $summaryOk = 0;
        if (isset($basicIndices['Indice CNDCEC']) && $basicIndices['Indice CNDCEC'] !== false) {
            $cndcec = $basicIndices['Indice CNDCEC'];
            $summaryWeight = 2;
            if (is_array($cndcec) && isset($cndcec['fuoriSoglia'])) {
                $summaryOk = $cndcec['fuoriSoglia'] ? 0 : 2;
            }
        }

        $basicTotal = $basicEvaluable + $summaryWeight;
        $basicScore = $basicTotal > 0
            ? (($basicOk + $summaryOk) / $basicTotal)
            : 0;

        // ── 2) Indici Advanced (peso 0.25) ──
        // Riusa la valutazioneIndici esistente per coerenza col DB dei range/pesi
        $advValutazione = $this->valutazioneIndici($advIndices, $tipoAzienda, $currentYear);
        $advRawScore = (float) str_replace(',', '.', $advValutazione['Score']);
        // Normalizza a 0-1: il max teorico di valutazioneIndici è ~1.0
        $advScore = min(1.0, $advRawScore);

        // ── 3) Questionari Allerta (peso 0.20) ──
        // Questionari: Agenzia delle Entrate, INPS, Agente della Riscossione,
        //              Debiti per Retribuzioni, Debiti verso Fornitori
        $qKeys = ['Agenzia delle Entrate', 'INPS', 'Agente della Riscossione', 'Debiti per Retribuzioni', 'Debiti verso Fornitori'];
        $qEvaluable = 0;
        $qOk = 0;
        foreach ($qKeys as $qKey) {
            if (!isset($questionari[$qKey]) || $questionari[$qKey] === false) continue;
            $qData = $questionari[$qKey];
            if (!is_array($qData) && !is_object($qData)) continue;

            // Cerca il campo alert
            $alertVal = null;
            if (is_object($qData)) $qData = (array) $qData;
            $alertVal = $qData['alert'] ?? $qData['alertAgenziaEntrate'] ?? $qData['alertINPS']
                ?? $qData['alertRiscossione'] ?? $qData['alertRetribuzioni'] ?? $qData['alertFornitori'] ?? null;

            if ($alertVal === null || strtolower((string) $alertVal) === 'dati mancanti') continue;

            $qEvaluable++;
            if (strtolower((string) $alertVal) === 'no') {
                $qOk++;
            }
        }
        $qScore = $qEvaluable > 0 ? ($qOk / $qEvaluable) : 0;

        // ── 4) Completezza dati (peso 0.10) ──
        // Proporzione di indici non mancanti + proporzione questionari compilati
        $totalIndices = count($basicIndices) + count($advIndices);
        $totalMissing = 0;
        foreach ($basicIndices as $v) {
            if ($v === false || $v === null) $totalMissing++;
        }
        foreach ($advIndices as $v) {
            if ($v === false || $v === null) $totalMissing++;
        }
        $totalCompiled = $totalIndices - $totalMissing;
        $dataCompleteness = $totalIndices > 0 ? ($totalCompiled / $totalIndices) : 0;
        $qCompleteness = count($qKeys) > 0 ? ($qEvaluable / count($qKeys)) : 0;
        $completenessScore = ($dataCompleteness * 0.6) + ($qCompleteness * 0.4); // contributo relativo

        // ── Composizione finale (scala 0-1) ──
        $compositeScore = ($basicScore * 0.45)
                        + ($advScore * 0.25)
                        + ($qScore * 0.20)
                        + ($completenessScore * 0.10);

        $compositeScore = max(0, min(1, $compositeScore));

        // ── Classificazione ──
        $rangeGiudizi = [
            ['Min' => 0,    'Max' => 0.14, 'Giudizio' => 'Default'],
            ['Min' => 0.14, 'Max' => 0.28, 'Giudizio' => 'Situazione Grave'],
            ['Min' => 0.28, 'Max' => 0.42, 'Giudizio' => 'Alert'],
            ['Min' => 0.42, 'Max' => 0.56, 'Giudizio' => 'Rischio alert'],
            ['Min' => 0.56, 'Max' => 0.70, 'Giudizio' => 'Fragilità elevata'],
            ['Min' => 0.70, 'Max' => 0.85, 'Giudizio' => 'Fragilità'],
            ['Min' => 0.85, 'Max' => 1.01, 'Giudizio' => 'Solidità'],
        ];

        $giudizio = 'N/A';
        foreach ($rangeGiudizi as $range) {
            if ($compositeScore >= $range['Min'] && $compositeScore < $range['Max']) {
                $giudizio = $range['Giudizio'];
                break;
            }
        }

        return [
            'Score' => number_format($compositeScore, 2, ',', '.'),
            'Giudizio' => $giudizio,
            'Giudizi' => $advValutazione['Giudizi'],
            'Dettaglio' => [
                'basicScore' => round($basicScore, 4),
                'advScore' => round($advScore, 4),
                'qScore' => round($qScore, 4),
                'completenessScore' => round($completenessScore, 4),
            ],
        ];
    }


    public function saveAnalisiBasicToDB($allData, $idBilancio)
    {
        if (str_contains($idBilancio, '"')) {
            $idBilancio = str_replace('"', '', $idBilancio);
        }

        // $calcoloDSCR = $this->getCalcoloDSCR($allData);
        $calcoloDSCR = null;
        // $dscrData = $this->getAnalisisDataFull($allData);

        if ($calcoloDSCR == null) {
            $dscrData['alertDSCR'] = "DSCR Non Calcolabile: dati mancanti";
        }

        if ($calcoloDSCR > 1) {
            $dscrData['alertDSCR'] = 'Azienda non a rischio';
        } else {
            $dscrData['alertDSCR'] = 'Azienda a rischio';
        }

        $dscrData['bilancio_id'] = (int) $idBilancio;

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

    public function getPeriodFromContext($contexts)
    {

        if (isset($contexts)) {
            foreach ($contexts as $singleContext) {
                $period[] = explode('-', $singleContext->period->startDate)[0];
            }

            rsort($period, SORT_NUMERIC);

            if (isset($period[3])) {
                $annoInizio = $period[3];
            } else {
                $annoInizio = $period[1];
            }
            $annoFine = $period[0];

            return [
                'anno_inizio' => $annoInizio,
                'anno_fine' => $annoFine
            ];
        } else {
            return [
                'anno_inizio' => false,
                'anno_fine' => false
            ];
        }
    }

    public function getNomeAziendaFromElements($jsonData)
    {
        if (isset($jsonData)) {
            foreach ($jsonData as $singleJson) {
                $nomeAzienda = $singleJson->value;
            }

            return $nomeAzienda;
        } else {
            return false;
        }
    }

    public function generateHTMLRender($instance, $taxonomy)
    {
        $cacheLocation = __DIR__ . '/cache';
        $compiledLocation = $taxonomy;        // percorso tassonomia compilata
        $languageCode = 'it';
        global $use_xbrl_functions;
        $use_xbrl_functions = true;

        try {

            /* -----------------------------------------------------------
             *  1) Verifiche preliminari sui file
             * ----------------------------------------------------------*/
            if (!file_exists($instance)) {         // l’istanza XBRL DEVE esistere
                return false;
            }
            // se vuoi che la funzione continui anche se la cartella
            // criptata della tassonomia non esiste, commenta la riga seguente
            if (!file_exists($compiledLocation)) {
                return false;
            }

            /* -----------------------------------------------------------
             *  2) Reset + inizializzazione libreria
             * ----------------------------------------------------------*/
            global $reportModelStructureRuleViolations;
            $reportModelStructureRuleViolations = false;

            XBRL_Global::reset();
            XBRL_Types::reset();
            new \XBRL_IFRS();

            /* -----------------------------------------------------------
             *  3) Apertura istanza con estensione tassonomia compilata
             * ----------------------------------------------------------*/
            $schemaHRef = $this->getInstanceTaxonomyHRef($instance);
            $compiledTaxonomyFilename = base_path("/taxonomies/2018-11-04/{$schemaHRef}");

            $instanceObj = XBRL_Instance::FromInstanceDocumentWithExtensionTaxonomy(
                $instance,
                $compiledTaxonomyFilename
            );

            $formulas = null;
            $results = [];
            $instanceTaxonomy = $instanceObj->getInstanceTaxonomy();
            $dfr = new XBRL_DFR($instanceTaxonomy);

            /* -----------------------------------------------------------
             *  4) Opzioni di rendering: mostriamo solo il necessario
             * ----------------------------------------------------------*/
            $dfr->includeCheckboxControls = false;
            $dfr->includeComponent = false;
            $dfr->includeSlicers = false;
            $dfr->includeFactsTable = false;
            $dfr->includeWidthcontrols = false;
            $dfr->includeBusinessRules = false;

            // *** NEW ➜ nasconde header/label di servizio (Etichetta, Tipo periodo, …)
            $dfr->showLabels = false;     // se la versione della libreria supporta la proprietà
            $dfr->showHeader = false;     // idem

            $presentationNetworks = $dfr->validateDFR($formulas, true, $languageCode);

            $renders = $dfr->renderPresentationNetworks(
                $presentationNetworks,
                $instanceObj,
                $formulas,
                false,               // niente facts table
                $languageCode,
                false,
                $results
            );

            /* -----------------------------------------------------------
             *  5) Costruzione HTML + filtro righe/colonne indesiderate
             * ----------------------------------------------------------*/
            $indexHTML =
                "<html>\n<head>\n" .
                "  <title>XBRL Rendered Views</title>\n" .
                "  <link rel='stylesheet' id='render-report-css' href='https://kpsfactory.com/wp-content/uploads/2024/xbrl-render-report.css'>\n" .

                '<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
      integrity="sha512-Fo3rlrZj/k7ujTnHg4C+6x5a4v0jtF1Rn+aWdPFKWeW1Vlj6W1x+EzoCVx3l1Y+FznTlwptg8Eq97nM7yS3+CA=="
      crossorigin="anonymous" referrerpolicy="no-referrer" />' .
                // *** NEW ➜ piccolo CSS per nascondere eventuali colonne residue
                "  <style>
                 thead,                       /* header intero */
                 th:nth-child(1), td:nth-child(1), /* colonna Etichetta */
                 th:nth-child(3), td:nth-child(3), /* Tipo periodo / classe   */
                 th:nth-child(4), td:nth-child(4)  /* Bilancio / NA ecc.      */
                 {display:none;}
                            div#primary {
                    margin: 0 auto 0 auto!important;
    max-width: 1000px!important;
    }
    .match: {
display:none;
}
                  .structure-table {
                 display:none!important;}
                 .report-table {
                     grid-template-columns: 600px repeat(1, minmax(300px, max-content))!important;
    }
               </style>\n" .
                "  <script src='https://code.jquery.com/jquery-1.12.4.min.js'></script>\n" .
                "</head>\n<body>\n";

            foreach ($renders as $render) {

                if (isset($render['hasReport']) && !$render['hasReport']) {
                    continue;
                }

                foreach ($render['entities'] as $networkHTML) {

                    // *** NEW ➜ rimuove eventuale <thead> con regex, come “doppia
                    //          sicurezza” nel caso lo stile non basti.
                    $networkHTML = preg_replace(
                        [
                            '/<thead\b[^>]*>.*?<\/thead>/is',                                   // header
                            '/<tr[^>]*>.*?(Etichetta|Fatto impostato tipo|Tipo di periodo).*?<\/tr>/is'  // righe service
                        ],
                        '',
                        $networkHTML
                    );

                    $indexHTML .= "  <div id='primary'>\n{$networkHTML}\n  </div>\n";
                }
            }

            $indexHTML .= "</body>\n</html>";

            return $indexHTML;

        } catch (\Throwable $ex) {

            // *** NEW ➜ log esteso per debug
            logger()->error('generateHTMLRender failed', [
                'msg' => $ex->getMessage(),
                'file' => $ex->getFile() . ':' . $ex->getLine(),
            ]);

            return false;
        }
    }





    public function getInstanceTaxonomyHRef($filename)
    {
        try {
            $dom = new \DOMDocument();

            $dom->load(html_entity_decode($filename, ENT_COMPAT, "UTF-8"));

            $domXPath = new \DOMXPath($dom);
            $domXPath->registerNamespace('xbrli', "http://www.xbrl.org/2003/instance");
            $domXPath->registerNamespace('link', "http://www.w3.org/1999/xlink");
            $nodes = $domXPath->query("/xbrli:xbrl/link:schemaRef");
            /** @var $domElement DOMElement */
            $domElement = $nodes[0];
            if ($domElement) {
                return $domElement->getAttribute('xlink:href');
                ;
            } else {
                return "itcc-ci-abb-2018-11-04.xsd";
            }
        } catch (Exception $e) {
            return "itcc-ci-abb-2018-11-04.xsd";
        }
    }

    /**
     * Handler for 'set_error_handler' function
     * @param int $error_level Contains the level of the error raised, as an integer.
     * @param string $error_message Contains the error message, as a string.
     * @param string $error_file Contains the filename that the error was raised in, as a string.
     * @param int $error_line Contains the line number the error was raised at, as an integer.
     * @param array $error_context An array that points to the active symbol table at the point the error occurred
     */
    public function errorHandler($error_level, $error_message, $error_file, $error_line, $error_context)
    {
        $error = array(
            "level" => $error_level,
            "message" => $error_message,
            "file" => $error_file,
            "line" => $error_line,
        );

        switch ($error_level) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_PARSE:
                $error['class'] = "fatal";
                break;

            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                $error['class'] = "error";
                break;

            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                $error['class'] = "warn";
                break;

            case E_NOTICE:
                return true; // Ignore notices

            case E_USER_NOTICE:
                $error['class'] = "info";
                break;

            case E_STRICT:
                $error['class'] = "debug";
                break;

            default:
                $error['class'] = "warn";
        }

        print_r($error);
        error_log(print_r($error, true));
    }


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

    public function getCurrentUserIdFromToken($request)
    {
        try {
            $access_token = $request->header('Authorization');
            $auth_header = explode(' ', $access_token);
            $token = $auth_header[1];
            $token_parts = explode('.', $token);
            $token_header = $token_parts[1];
            $token_header_json = base64_decode($token_header);
            $token_header_array = json_decode($token_header_json, true);
            $token_id = $token_header_array['jti'];

            $user = Token::findOrFail($token_id)->user;

            return $user->id;
        } catch (Exception $e) {
            return 'Unauthorized';
        }
    }
}







