<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AIController extends Controller
{
    /** Config */
    private const MAX_ROWS   = 2000;   // limite righe
    private const TIMEOUT_MS = 120000; // 120s

    public function queryAI(Request $request)
    {
        $q = trim((string) $request->input('question', ''));
        if ($q === '') {
            return response()->json(['message' => 'Missing question'], 422);
        }

        // 1) Schema DB → guida il modello a costruire la SELECT
        $schema = $this->introspectSchema();

        // 2) Prompt
        $system = <<<SYS
Sei un assistente SQL per MySQL. In base allo schema che ti fornisco:
- Genera UNA SOLA query **SELECT** sicura (no DDL/DML, no WITH/CTE, niente ";")
- Usa solo tabelle/colonne esistenti e nomi esatti
- Se serve filtrare per anno, usa: YEAR(colonna_data)=YYYY
- Se si chiede "fatturato", intendi SUM(invoices.amount_gross) per direction="issued"
- Aggiungi SEMPRE "LIMIT {MAX}" alla fine
- Rispondi **solo in JSON** nel formato: {"sql":"...","explanation":"...","natural_answer_template":"..."} senza testo extra.
SYS;

        $user = [
            "role"    => "user",
            "content" => "Domanda: {$q}\n\nSchema JSON:\n" . json_encode($schema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        ];

        try {
            // 3) Chat Completions → chiediamo JSON puro
            $json = $this->openaiChatJsonStrict($system, [$user]);

            $sql = trim((string)($json['sql'] ?? ''));
            if (!$this->isSelectSafe($sql)) {
                return response()->json([
                    'message'   => 'Query rifiutata per sicurezza.',
                    'generated' => $json,
                ], 400);
            }

            // 4) Forza LIMIT
            $sql = $this->enforceLimit($sql, self::MAX_ROWS);

            // 5) Esegui in sola lettura
            $rows = DB::select($sql);

            // 6) Riassunto “best effort”
            $summary = $this->autoSummarize($rows, $q, $json['natural_answer_template'] ?? null);

            return response()->json([
                'ok'          => true,
                'question'    => $q,
                'sql'         => $sql,
                'explanation' => $json['explanation'] ?? null,
                'summary'     => $summary,
                'data'        => $rows,
                'limit'       => self::MAX_ROWS,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Errore AI',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /* ================= Helpers ================= */

    private function introspectSchema(): array
    {
        // Whitelist tabelle pertinenti al dominio
        $allowed = ['clients','invoices','companies','users','crs','documenti'];

        // ⚠️ information_schema.columns espone MAIUSCOLO → usa alias
        $columns = DB::table('information_schema.columns')
            ->select(
                DB::raw('TABLE_NAME AS table_name'),
                DB::raw('COLUMN_NAME AS column_name'),
                DB::raw('DATA_TYPE AS data_type'),
                DB::raw('IS_NULLABLE AS is_nullable'),
                DB::raw('ORDINAL_POSITION AS ordinal_position')
            )
            ->whereIn('TABLE_NAME', $allowed)
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->orderBy('table_name')
            ->orderBy('ordinal_position')
            ->get();

        $byTable = [];
        foreach ($columns as $c) {
            $t = (string)$c->table_name;
            $byTable[$t][] = [
                'name'     => (string)$c->column_name,
                'type'     => (string)$c->data_type,
                'nullable' => $c->is_nullable === 'YES',
            ];
        }

        // campioni (opzionali, pochi)
        $samples = [];
        foreach ($allowed as $t) {
            try { $samples[$t] = DB::table($t)->limit(3)->get(); }
            catch (\Throwable $e) { $samples[$t] = []; }
        }

        return [
            'dialect' => 'mysql',
            'tables'  => array_map(fn($t)=>[
                'name'    => $t,
                'columns' => $byTable[$t] ?? [],
            ], $allowed),
            'samples' => $samples,
            'notes'   => [
                'dates' => ['invoices.date','invoices.due_date','invoices.created_at','clients.created_at'],
                'business' => [
                    'fatturato' => 'somma invoices.amount_gross per direction="issued"',
                    'clienti'   => 'da clients o da invoices.client_name/vat',
                ],
            ],
        ];
    }

    private function openaiChatJsonStrict(string $system, array $messages): array
    {
        $apiKey = env('OPENAI_API_KEY');
        $model  = env('OPENAI_MODEL', 'gpt-4o-mini');

        $client = new \GuzzleHttp\Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout'  => self::TIMEOUT_MS / 1000.0,
        ]);

        // Aggiungiamo un messaggio che forza il JSON puro
        array_unshift($messages, [
            'role' => 'system',
            'content' => $system . "\n\nDEVI RISPOSTE SOLO CON JSON VALIDO. NIENTE TESTO PRIMA/DOPO."
        ]);

        // (Opzionale) Un ulteriore “guard-rail”:
        $messages[] = [
            'role' => 'system',
            'content' => 'Output finale: solo JSON valido con chiavi sql, explanation, natural_answer_template.'
        ];

        $res = $client->post('chat/completions', [
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'model'    => $model,
                'messages' => $messages,
                // Se il tuo account supporta json_object su chat/completions:
                // 'response_format' => ['type' => 'json_object'],
                // Altrimenti lasciamo vuoto e validiamo lato server:
                'temperature' => 0.2,
            ],
        ]);

        $body = json_decode((string)$res->getBody(), true) ?: [];
        $txt  = $body['choices'][0]['message']['content'] ?? '';

        // Tenta parse JSON “puro”
        $decoded = json_decode($txt, true);
        if (!is_array($decoded)) {
            // fallback: estrai blocco JSON se il modello ha aggiunto testo
            if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $txt, $m)) {
                $decoded = json_decode($m[0], true);
            }
        }
        if (!is_array($decoded)) {
            throw new \RuntimeException('Il modello non ha restituito JSON valido.');
        }
        return $decoded;
    }

    private function isSelectSafe(string $sql): bool
    {
        $s = strtolower(preg_replace('/\s+/', ' ', $sql));
        if (!Str::startsWith(trim($s), 'select')) return false;

        // blocca comandi pericolosi
        foreach ([';',' drop ',' insert ',' update ',' delete ',' alter ',' truncate ',' create ',' grant ',' revoke ',' with '] as $bad) {
            if (str_contains($s, $bad)) return false;
        }

        // whitelisting tabelle
        $allowed = ['clients','invoices','companies','users','crs','documenti'];
        if (preg_match_all('/\bfrom\s+([a-z0-9_]+)/i', $sql, $m)) {
            foreach ($m[1] as $t) if (!in_array(strtolower($t), $allowed, true)) return false;
        }
        if (preg_match_all('/\bjoin\s+([a-z0-9_]+)/i', $sql, $m2)) {
            foreach ($m2[1] as $t) if (!in_array(strtolower($t), $allowed, true)) return false;
        }
        return true;
    }

    private function enforceLimit(string $sql, int $limit): string
    {
        $s = trim($sql);
        if (preg_match('/\blimit\s+\d+/i', $s)) return $s;
        return $s . ' LIMIT ' . $limit;
    }

    private function autoSummarize(array $rows, string $question, ?string $template): string
    {
        if (empty($rows)) return "Nessun risultato trovato per: “{$question}”.";

        // Caso 1 riga → prova a cogliere sum/count
        if (count($rows) === 1 && isset($rows[0])) {
            $obj = (array)$rows[0]; // fix PHP 7: niente isset((array)$rows[0])
            foreach (['tot','total','sum','fatturato','count'] as $k) {
                foreach ($obj as $col=>$val) {
                    if (stripos($col,$k)!==false && is_numeric($val)) {
                        $v = number_format((float)$val, 2, ',', '.');
                        return "Risposta: {$v}.";
                    }
                }
            }
        }

        // Default: conta righe
        $count = count($rows);
        return "Ho trovato {$count} righe pertinenti.";
    }
}
