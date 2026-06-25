<?php

namespace App\Services;

use App\Models\BankStatement;
use App\Models\BankStatementEntry;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BankStatementAnalyzer
{
    /**
     * Analizza un estratto conto: parsing PDF via AI + calcoli finanziari.
     */
    public function analyze(BankStatement $statement): array
    {
        $statement->update(['status' => 'analyzing']);

        try {
            // 1. Estrai testo dal PDF
            $pdfText = $this->extractPdfText($statement);

            // 2. Parsing AI → movimenti strutturati + metadata
            $parsed = $this->parseWithAI($pdfText);

            // 3. Salva movimenti nel DB
            $this->saveEntries($statement, $parsed['movimenti'] ?? []);

            // 4. Aggiorna metadata estratto conto
            $statement->update([
                'bank_name'      => $parsed['banca'] ?? null,
                'iban'           => $parsed['iban'] ?? null,
                'fido_accordato' => $parsed['fido_accordato'] ?? null,
                'date_from'      => $parsed['periodo_da'] ?? null,
                'date_to'        => $parsed['periodo_a'] ?? null,
            ]);

            // 5. Calcola analisi finanziaria
            $analysis = $this->computeAnalysis($statement);

            // 6. Salva risultati
            $statement->update([
                'status'        => 'completed',
                'analysis_data' => $analysis,
            ]);

            return $analysis;

        } catch (\Throwable $e) {
            Log::error('BankStatementAnalyzer error: ' . $e->getMessage(), [
                'statement_id' => $statement->id,
                'trace'        => $e->getTraceAsString(),
            ]);

            $statement->update([
                'status'        => 'error',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Estrae testo raw dal PDF usando pdftotext (poppler-utils).
     */
    private function extractPdfText(BankStatement $statement): string
    {
        $disk = Storage::disk('estratticonto');
        $fullPath = $disk->path($statement->filepath);

        if (!file_exists($fullPath)) {
            throw new \RuntimeException("File PDF non trovato: {$fullPath}");
        }

        // Prova con pdftotext (richiede poppler-utils)
        $outputFile = tempnam(sys_get_temp_dir(), 'ec_') . '.txt';

        $cmd = sprintf(
            'pdftotext -layout %s %s 2>&1',
            escapeshellarg($fullPath),
            escapeshellarg($outputFile)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($outputFile)) {
            // Fallback: prova senza -layout
            $cmd2 = sprintf('pdftotext %s %s 2>&1', escapeshellarg($fullPath), escapeshellarg($outputFile));
            exec($cmd2, $output2, $returnCode2);

            if ($returnCode2 !== 0 || !file_exists($outputFile)) {
                throw new \RuntimeException("Impossibile estrarre testo dal PDF. Assicurati che pdftotext sia installato (poppler-utils).");
            }
        }

        $text = file_get_contents($outputFile);
        @unlink($outputFile);

        if (empty(trim($text))) {
            throw new \RuntimeException("Il PDF non contiene testo estraibile. Potrebbe essere un PDF con immagini (scansione).");
        }

        return $text;
    }

    /**
     * Usa OpenAI GPT-4o per parsare il testo dell'estratto conto in dati strutturati.
     */
    private function parseWithAI(string $pdfText): array
    {
        $apiKey = env('OPENAI_API_KEY');
        if (!$apiKey) {
            throw new \RuntimeException('OPENAI_API_KEY non configurata.');
        }

        // Tronca se troppo lungo (GPT-4o ha 128k context, ma per sicurezza limitiamo)
        $maxChars = 80000;
        if (mb_strlen($pdfText) > $maxChars) {
            $pdfText = mb_substr($pdfText, 0, $maxChars);
        }

        $system = <<<SYS
Sei un esperto analista bancario. Ti viene dato il testo estratto da un PDF di un estratto conto bancario italiano.
Devi estrarre i dati strutturati e restituirli SOLO come JSON valido, senza testo extra.

Il JSON deve avere questa struttura:
{
  "banca": "Nome della banca",
  "iban": "IBAN del conto se presente",
  "fido_accordato": numero o null (il limite di credito/fido/affidamento concesso, in euro),
  "periodo_da": "YYYY-MM-DD",
  "periodo_a": "YYYY-MM-DD",
  "movimenti": [
    {
      "date_operazione": "YYYY-MM-DD",
      "date_valuta": "YYYY-MM-DD" o null,
      "descrizione": "descrizione del movimento",
      "dare": numero (importo dare/uscita, 0 se non c'è),
      "avere": numero (importo avere/entrata, 0 se non c'è),
      "saldo": numero o null (saldo progressivo dopo il movimento)
    }
  ]
}

REGOLE IMPORTANTI:
- I movimenti devono essere in ordine cronologico (dal più vecchio al più recente)
- "dare" = uscite/addebiti (importi positivi, non negativi)
- "avere" = entrate/accrediti (importi positivi, non negativi)
- Il fido/affidamento è la linea di credito concessa dalla banca, spesso indicata come "fido", "affidamento", "linea di credito", "castelletto", o nei dettagli del conto. Cercalo accuratamente nel documento.
- Se trovi un "saldo competenze", "scalare interessi" con voci come "numeri creditori/debitori" o "fido accordato", estrai il fido da lì.
- Le date devono essere in formato YYYY-MM-DD
- Gli importi devono essere numeri (no punti come separatore migliaia, usa il punto come separatore decimale)
- Esempio: "1.234,56" nel PDF → 1234.56 nel JSON
- Se un dato non è presente, usa null
- NON inventare dati, estrai solo quelli presenti nel documento
SYS;

        $client = new \GuzzleHttp\Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout'  => 180,
        ]);

        $response = $client->post('chat/completions', [
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'model'    => env('OPENAI_MODEL', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user',   'content' => "Ecco il testo dell'estratto conto da analizzare:\n\n" . $pdfText],
                ],
                'temperature' => 0.1,
                'max_tokens'  => 16000,
            ],
        ]);

        $body = json_decode((string) $response->getBody(), true) ?: [];
        $content = $body['choices'][0]['message']['content'] ?? '';

        // Parse JSON dalla risposta
        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            // Fallback: cerca blocco JSON se il modello ha aggiunto testo
            if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $content, $m)) {
                $decoded = json_decode($m[0], true);
            }
        }

        if (!is_array($decoded)) {
            throw new \RuntimeException('AI non ha restituito JSON valido per il parsing del PDF.');
        }

        return $decoded;
    }

    /**
     * Salva i movimenti estratti nel database.
     */
    private function saveEntries(BankStatement $statement, array $movimenti): void
    {
        // Elimina movimenti precedenti (re-analisi)
        $statement->entries()->delete();

        $batch = [];
        $now = now();

        foreach ($movimenti as $mov) {
            $batch[] = [
                'bank_statement_id' => $statement->id,
                'date_operazione'   => $mov['date_operazione'] ?? null,
                'date_valuta'       => $mov['date_valuta'] ?? null,
                'descrizione'       => $mov['descrizione'] ?? '',
                'dare'              => (float) ($mov['dare'] ?? 0),
                'avere'             => (float) ($mov['avere'] ?? 0),
                'saldo'             => isset($mov['saldo']) ? (float) $mov['saldo'] : null,
                'created_at'        => $now,
                'updated_at'        => $now,
            ];
        }

        // Insert in chunks per performance
        foreach (array_chunk($batch, 100) as $chunk) {
            BankStatementEntry::insert($chunk);
        }
    }

    /**
     * Calcola l'analisi finanziaria completa dall'estratto conto.
     * Tutti i calcoli sono basati sui movimenti reali estratti, NON su valori AI.
     */
    private function computeAnalysis(BankStatement $statement): array
    {
        $allEntries = $statement->entries()
            ->orderBy('date_operazione')
            ->get();

        if ($allEntries->isEmpty()) {
            return [
                'summary'   => ['message' => 'Nessun movimento trovato'],
                'daily'     => [],
                'alerts'    => [],
                'kpi'       => [],
            ];
        }

        $fido = (float) ($statement->fido_accordato ?? 0);
        $hasFido = $fido > 0;

        // ══════════════════════════════════════════════════════════════
        // 1. Separa voci di SALDO (iniziale/finale) dai movimenti reali
        // ══════════════════════════════════════════════════════════════
        $saldoIniziale = null;
        $saldoFinale   = null;
        $realEntries   = [];

        foreach ($allEntries as $entry) {
            $desc = strtoupper(trim($entry->descrizione ?? ''));

            // Identifica SALDO INIZIALE
            if (preg_match('/\bSALDO\s*(INIZIALE|INIZIO|PRECEDENTE|LIQUIDO\s*PRECEDENTE|CONTABILE\s*INIZIALE|A\s*RIPORTARE)\b/i', $desc)) {
                $bal = $this->extractBalanceFromSaldoEntry($entry, $desc);
                if ($bal !== null) $saldoIniziale = $bal;
                continue; // non contare come movimento
            }

            // Identifica SALDO FINALE
            if (preg_match('/\bSALDO\s*(FINALE|FINE|CONTABILE\s*FINALE|LIQUIDO\s*FINALE)\b/i', $desc)) {
                $bal = $this->extractBalanceFromSaldoEntry($entry, $desc);
                if ($bal !== null) $saldoFinale = $bal;
                continue; // non contare come movimento
            }

            $realEntries[] = $entry;
        }

        $entries = collect($realEntries);

        // ══════════════════════════════════════════════════════════════
        // 2. KPI calcolati dai soli movimenti reali
        // ══════════════════════════════════════════════════════════════
        $totalDare    = round($entries->sum('dare'), 2);
        $totalAvere   = round($entries->sum('avere'), 2);
        $numMovimenti = $entries->count();

        // ══════════════════════════════════════════════════════════════
        // 3. Raggruppa movimenti per giornata
        // ══════════════════════════════════════════════════════════════
        $daily = [];
        foreach ($realEntries as $entry) {
            $dateStr = $entry->date_operazione->format('Y-m-d');
            if (!isset($daily[$dateStr])) {
                $daily[$dateStr] = [
                    'date'       => $dateStr,
                    'dare_tot'   => 0,
                    'avere_tot'  => 0,
                    'saldo'      => null,
                    'movimenti'  => 0,
                ];
            }
            $daily[$dateStr]['dare_tot']  += $entry->dare;
            $daily[$dateStr]['avere_tot'] += $entry->avere;
            $daily[$dateStr]['movimenti']++;
        }

        $dailyArr = array_values($daily);
        usort($dailyArr, fn($a, $b) => strcmp($a['date'], $b['date']));

        // ══════════════════════════════════════════════════════════════
        // 4. Ricostruisci saldi giornalieri dai movimenti + ancore
        //    NON usa i valori saldo forniti dall'AI per singolo movimento
        // ══════════════════════════════════════════════════════════════
        if ($saldoFinale !== null) {
            // Ricostruisci all'indietro dal saldo finale
            $runSaldo = $saldoFinale;
            for ($i = count($dailyArr) - 1; $i >= 0; $i--) {
                $dailyArr[$i]['saldo'] = round($runSaldo, 2);
                // Annulla i movimenti del giorno per ottenere il saldo di fine giorno precedente
                $runSaldo = $runSaldo - $dailyArr[$i]['avere_tot'] + $dailyArr[$i]['dare_tot'];
            }
        } elseif ($saldoIniziale !== null) {
            // Ricostruisci in avanti dal saldo iniziale
            $runSaldo = $saldoIniziale;
            foreach ($dailyArr as &$d) {
                $runSaldo = $runSaldo + $d['avere_tot'] - $d['dare_tot'];
                $d['saldo'] = round($runSaldo, 2);
            }
            unset($d);
        } else {
            // Nessuna ancora disponibile: ricostruisci cumulativamente da 0
            $runSaldo = 0;
            foreach ($dailyArr as &$d) {
                $runSaldo = $runSaldo + $d['avere_tot'] - $d['dare_tot'];
                $d['saldo'] = round($runSaldo, 2);
            }
            unset($d);
        }

        // ══════════════════════════════════════════════════════════════
        // 5. Calcolo utilizzo fido per ogni giornata
        // ══════════════════════════════════════════════════════════════
        foreach ($dailyArr as &$d) {
            if ($hasFido && $d['saldo'] !== null) {
                $utilizzo = $d['saldo'] < 0
                    ? min(100, (abs($d['saldo']) / $fido) * 100)
                    : 0;
                $d['utilizzo_fido'] = round($utilizzo, 2);
                $d['sconfinamento'] = $d['saldo'] < -$fido;
            } else {
                $d['utilizzo_fido'] = null;
                $d['sconfinamento'] = false;
            }
        }
        unset($d);

        // ══════════════════════════════════════════════════════════════
        // 6. KPI saldi (calcolati dai saldi ricostruiti)
        // ══════════════════════════════════════════════════════════════
        $saldi = array_filter(array_column($dailyArr, 'saldo'), fn($v) => $v !== null);
        $saldoMedio = !empty($saldi) ? round(array_sum($saldi) / count($saldi), 2) : 0;
        $saldoMin   = !empty($saldi) ? round(min($saldi), 2) : 0;
        $saldoMax   = !empty($saldi) ? round(max($saldi), 2) : 0;

        // Utilizzo fido medio
        $utilizzoValues = array_filter(array_column($dailyArr, 'utilizzo_fido'), fn($v) => $v !== null);
        $utilizzoMedio  = !empty($utilizzoValues) ? round(array_sum($utilizzoValues) / count($utilizzoValues), 2) : 0;
        $utilizzoMax    = !empty($utilizzoValues) ? max($utilizzoValues) : 0;

        // Giorni sconfinamento
        $giorniSconfinamento = count(array_filter($dailyArr, fn($d) => $d['sconfinamento']));

        // Giorni analizzati: dalle date del periodo
        if ($statement->date_from && $statement->date_to) {
            $giorniTotali = (int) $statement->date_from->diffInDays($statement->date_to) + 1;
        } else {
            $dates = array_column($dailyArr, 'date');
            if (count($dates) >= 2) {
                $first = new \DateTime(min($dates));
                $last  = new \DateTime(max($dates));
                $giorniTotali = (int) $first->diff($last)->days + 1;
            } else {
                $giorniTotali = count($dailyArr);
            }
        }

        // Turnover ratio
        $turnover = $totalDare + $totalAvere;
        $turnoverRatio = $saldoMedio != 0
            ? round($turnover / abs($saldoMedio), 2)
            : ($turnover > 0 ? 999 : 0);

        // ── Alerts ──
        $alerts = [];

        if ($hasFido) {
            if ($utilizzoMedio > 90) {
                $alerts[] = [
                    'type'    => 'danger',
                    'code'    => 'SOVRAUTILIZZO_FIDO',
                    'title'   => 'Sovrautilizzo Fido',
                    'message' => "Utilizzo medio del fido al {$utilizzoMedio}% (soglia critica: 90%). Il conto utilizza quasi l'intera linea di credito.",
                    'value'   => $utilizzoMedio,
                ];
            } elseif ($utilizzoMedio > 70) {
                $alerts[] = [
                    'type'    => 'warning',
                    'code'    => 'UTILIZZO_ELEVATO_FIDO',
                    'title'   => 'Utilizzo Elevato Fido',
                    'message' => "Utilizzo medio del fido al {$utilizzoMedio}% (attenzione sopra il 70%).",
                    'value'   => $utilizzoMedio,
                ];
            } else {
                $alerts[] = [
                    'type'    => 'success',
                    'code'    => 'FIDO_OK',
                    'title'   => 'Utilizzo Fido nella norma',
                    'message' => "Utilizzo medio del fido al {$utilizzoMedio}%. Situazione regolare.",
                    'value'   => $utilizzoMedio,
                ];
            }

            if ($giorniSconfinamento > 0) {
                $pct = round(($giorniSconfinamento / $giorniTotali) * 100, 1);
                $alerts[] = [
                    'type'    => 'danger',
                    'code'    => 'SCONFINAMENTO',
                    'title'   => 'Sconfinamento Fido',
                    'message' => "Il conto ha superato il fido per {$giorniSconfinamento} giorni su {$giorniTotali} ({$pct}%).",
                    'value'   => $giorniSconfinamento,
                ];
            }

            if ($utilizzoMax > 100) {
                $alerts[] = [
                    'type'    => 'danger',
                    'code'    => 'SUPERAMENTO_FIDO',
                    'title'   => 'Superamento Fido Massimo',
                    'message' => "Picco utilizzo fido raggiunto: {$utilizzoMax}%. Il conto ha ecceduto la linea di credito.",
                    'value'   => $utilizzoMax,
                ];
            }
        } else {
            if ($turnoverRatio > 20) {
                $alerts[] = [
                    'type'    => 'danger',
                    'code'    => 'MOVIMENTAZIONE_SOSPETTA',
                    'title'   => 'Movimentazione Sospetta',
                    'message' => "Il denaro transita molto velocemente sul conto (ratio {$turnoverRatio}x). Il turnover è sproporzionato rispetto al saldo medio. Possibile conto di passaggio.",
                    'value'   => $turnoverRatio,
                ];
            } elseif ($turnoverRatio > 10) {
                $alerts[] = [
                    'type'    => 'warning',
                    'code'    => 'MOVIMENTAZIONE_ELEVATA',
                    'title'   => 'Movimentazione Elevata',
                    'message' => "Il turnover è elevato rispetto al saldo medio (ratio {$turnoverRatio}x). Monitorare la situazione.",
                    'value'   => $turnoverRatio,
                ];
            } else {
                $alerts[] = [
                    'type'    => 'success',
                    'code'    => 'MOVIMENTAZIONE_OK',
                    'title'   => 'Movimentazione nella norma',
                    'message' => "Il rapporto turnover/saldo è regolare (ratio {$turnoverRatio}x).",
                    'value'   => $turnoverRatio,
                ];
            }

            $alerts[] = [
                'type'    => 'info',
                'code'    => 'NO_FIDO',
                'title'   => 'Nessun Fido Rilevato',
                'message' => "Non è stato rilevato un fido/affidamento nel documento. L'analisi si concentra sulla movimentazione.",
                'value'   => 0,
            ];
        }

        // ── Scoring complessivo (0-100) ──
        $score = $this->computeScore($hasFido, $utilizzoMedio, $giorniSconfinamento, $giorniTotali, $turnoverRatio);

        return [
            'summary' => [
                'score'                  => $score,
                'score_label'            => $this->scoreLabel($score),
                'fido_accordato'         => $hasFido ? $fido : null,
                'has_fido'               => $hasFido,
                'saldo_medio'            => $saldoMedio,
                'saldo_min'              => $saldoMin,
                'saldo_max'              => $saldoMax,
                'totale_dare'            => $totalDare,
                'totale_avere'           => $totalAvere,
                'num_movimenti'          => $numMovimenti,
                'giorni_analizzati'      => $giorniTotali,
                'utilizzo_fido_medio'    => $hasFido ? $utilizzoMedio : null,
                'utilizzo_fido_max'      => $hasFido ? $utilizzoMax : null,
                'giorni_sconfinamento'   => $giorniSconfinamento,
                'turnover_ratio'         => !$hasFido ? $turnoverRatio : null,
            ],
            'daily'  => $dailyArr,
            'alerts' => $alerts,
        ];
    }

    /**
     * Estrae il valore di saldo da una voce SALDO INIZIALE/FINALE.
     * "A VS. DEBITO" → saldo negativo, "A VS. CREDITO" → saldo positivo.
     */
    private function extractBalanceFromSaldoEntry($entry, string $desc): ?float
    {
        $isDebito  = (bool) preg_match('/DEBITO/i', $desc);
        $isCredito = (bool) preg_match('/CREDITO/i', $desc);

        $dare  = (float) ($entry->dare ?? 0);
        $avere = (float) ($entry->avere ?? 0);
        $amount = max($dare, $avere);

        // Se l'AI ha fornito un saldo diretto e non c'è importo dare/avere
        if ($amount == 0 && $entry->saldo !== null) {
            return (float) $entry->saldo;
        }

        if ($amount == 0) return null;

        // "A VS. DEBITO" → il saldo è negativo (conto in rosso)
        if ($isDebito)  return -$amount;
        // "A VS. CREDITO" → il saldo è positivo
        if ($isCredito) return $amount;

        // Default: dare = debito (negativo), avere = credito (positivo)
        if ($dare > 0) return -$dare;
        if ($avere > 0) return $avere;

        return null;
    }

    /**
     * Calcola uno score complessivo di salute del conto (0-100).
     */
    private function computeScore(bool $hasFido, float $utilizzoMedio, int $giorniSconf, int $giorniTot, float $turnoverRatio): int
    {
        if ($hasFido) {
            // Base: inversamente proporzionale all'utilizzo fido
            $base = max(0, 100 - $utilizzoMedio);

            // Penalità per sconfinamento
            $sconfPenalty = $giorniTot > 0
                ? ($giorniSconf / $giorniTot) * 30
                : 0;

            $score = $base - $sconfPenalty;
        } else {
            // Senza fido: valuta sulla base del turnover ratio
            if ($turnoverRatio <= 5)  $score = 95;
            elseif ($turnoverRatio <= 10) $score = 75;
            elseif ($turnoverRatio <= 20) $score = 50;
            elseif ($turnoverRatio <= 50) $score = 30;
            else $score = 10;
        }

        return max(0, min(100, (int) round($score)));
    }

    private function scoreLabel(int $score): string
    {
        if ($score >= 90) return 'Ottimo';
        if ($score >= 70) return 'Buono';
        if ($score >= 50) return 'Attenzione';
        if ($score >= 30) return 'Critico';
        return 'Grave';
    }
}
