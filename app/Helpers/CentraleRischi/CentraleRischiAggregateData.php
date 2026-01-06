<?php

namespace App\Helpers\CentraleRischi;

class CentraleRischiAggregateData
{
    /**
     * Dato l’array JSON estratto dal PDF di Centrale Rischi,
     * restituisce una struttura indicizzata per
     *  └─ anno
     *      └─ mese
     *          └─ intermediario
     *              └─ sezione (Cassa, Firma, Segnalazioni, …)
     *                  └─ singole righe di dettaglio
     */
    public function crJsonToArray(array $json): array
    {
        /* === variabili di stato =============================================================== */
        $crData               = [];
        $currentYear          = null;
        $currentMonth         = null;
        $currentIntermediario = null;
        $seen = [];
        $cointestazione = null;

        $isSegnalazione         = false;         // diventa true quando si incontra il paragrafo apposito
        $currentHeaderKeys      = [];            // header della tabella “Categoria …”
        $currentGarHeaderKeys   = [];            // header della tabella dei garanti
        /* ====================================================================================== */

        /* mappa “categoria” -> “sezione”  (come nella versione storica) */
        $categoriaSezione = [
            'RISCHI A SCADENZA'                                => 'Cassa',
'CREDITI ACQUISITI DA CLIENTELA DIVERSA DA INTERMEDIARI - DEBITORI CEDUTI' => 'Informativa',
            'RISCHI AUTOLIQUIDANTI'                           => 'Cassa',
            'RISCHI A REVOCA'                                 => 'Cassa',
            'RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI'         => 'Informativa',
            'SOFFERENZE'                                      => 'Sofferenze',
            'GARANZIE CONNESSE CON OPERAZIONI DI NATURA COMMERCIALE' => 'Firma',
            'GARANZIE RICEVUTE'                               => 'Garanzie',
            'SOFFERENZE - CREDITI PASSATI A PERDITA'          => 'Informativa',
            // sezione dedicata ai garanti
            'Garanti'                                         => 'Garanti',
        ];

        /* === PARSING ========================================================================== */
        foreach ($json as $pageArray) {               // ogni “pagina” del PDF
            foreach ($pageArray as $rowObj) {         // ogni riga‑array
              
                /* 1. normalizziamo la riga */
                $row       = (array) $rowObj;                     // array di colonne
                $cells     = array_values(array_map('trim', $row)); // liste “pulita”
           
                $rowString = implode(' ', $cells);                // versione monocolonna per ricerche rapide
      
                /* 2. COINTESTAZIONE ------------------------------------------------------------------ */
                if (stripos($rowString, 'Cointestazione:') !== false) {
                    // “Cointestazione: Mario Rossi e Luca Bianchi”
                    $cointestazione = trim(substr($rowString, stripos($rowString, ':') + 1));
                }

                /* 3. DATA DI RIFERIMENTO ------------------------------------------------------------- */
                if (stripos($rowString, 'DATA DI RIFERIMENTO') !== false) {
                    // nella colonna successiva (o subito dopo) troviamo “settembre 2024” ecc.
                    $pattern = '/\b(gennaio|febbraio|marzo|aprile|maggio|giugno|luglio|agosto|settembre|ottobre|novembre|dicembre)\s+(\d{4})/iu';
                    if (preg_match($pattern, $rowString, $m)) {
                        $currentMonth = (strtolower($m[1]));  // “Settembre”
                        $currentYear  = $m[2];
                    } elseif (!empty($cells[1])) {                   // fallback: la data sta nella 2ª colonna
                        $parts        = preg_split('/\s+/', $cells[1]);
                        $currentMonth = (strtolower($parts[0] ?? ''));
                        $currentYear  = $parts[1] ?? null;
                    }

                    /* inizializziamo i livelli della struttura dati */
                    if ($currentYear && !isset($crData[$currentYear])) {
                        $crData[$currentYear] = [];
                    }
                    if ($currentYear && $currentMonth && !isset($crData[$currentYear][$currentMonth])) {
                        $crData[$currentYear][$currentMonth] = [];
                    }
                }

                $cell0 = isset($cells[0]) ? $this->clean($cells[0]) : '';
              /* 4. INTERMEDIARIO ----------------------------------------------------- */
              if (stripos($rowString, 'Intermediario:') !== false) {
          
                $intermed = $this->extractBank($cells);
              
                if ($intermed !== '') {
                    $currentIntermediario = $intermed;
    // indice della cella che contiene la label
    $labelIdx = null;
    foreach ($cells as $i => $val) {
        if (mb_strtolower($this->clean($val)) === 'intermediario:') {
            $labelIdx = $i;
            break;
        }
    }

    // prima cella “utile” dopo la label (salta testo di servizio)
    $noise = [
        'sezione informativa',
        'crediti per cassa',
        'non ci sono segnalazioni.',
        'situazione corrente'
    ];

    $intermed = '';
    if ($labelIdx !== null) {
        for ($j = $labelIdx + 1; $j < count($cells); $j++) {
            $candidate = $this->clean($cells[$j]);
            if ($candidate === '') continue;

            $low = mb_strtolower($candidate);
            $skip = false;
            foreach ($noise as $n) {
                if (str_starts_with($low, $n)) {
                    $skip = true; break;
                }
            }
            if (!$skip) {
                $intermed = $candidate;
                break;
            }
        }
    }

    if ($intermed !== '') {
        $currentIntermediario = $intermed;
        $isSegnalazione       = false;

        if ($currentYear && $currentMonth
            && !isset($crData[$currentYear][$currentMonth][$currentIntermediario])) {
            $crData[$currentYear][$currentMonth][$currentIntermediario] = [];
        }
    }
                }
    continue;   // chiusa la riga Intermediario
}





                /* 5. SEGMENTO “Di seguito si riportano le segnalazioni …” --------------------------- */
                if (stripos($rowString, 'Di seguito si riportano le segnalazioni') !== false) {
                    $isSegnalazione = true;
                }
/* 6. HEADER “Categoria …” -------------------------------------------- */
$labelIdx = null;
foreach ($cells as $i => $val) {
    if (mb_strtolower($this->clean($val)) === 'categoria') {
        $labelIdx = $i;
        break;
    }
}
if ($labelIdx !== null) {
    $currentHeaderKeys = array_map([$this,'clean'], $cells);
    continue;
}

                
           
           

/* ------------------------------------------------------------------ */
/* 7. RIGA DI DETTAGLIO CATEGORIA ----------------------------------- */
/* ------------------------------------------------------------------ */

// 7-a) CERCO la label “Categoria” nella riga
$cellKey = $this->clean($cells[0] ?? '');          // prima provo col.-0

if ($cellKey === '' || !isset($categoriaSezione[$cellKey])) {
    // se non trovata, scorro tutte le celle
    foreach ($cells as $val) {
        $tmp = $this->clean($val);
        if (isset($categoriaSezione[$tmp])) {
            $cellKey = $tmp;
            break;
        }
    }
}

// 7-b) Se ho individuato una categoria valida…
if ($cellKey !== '' && isset($categoriaSezione[$cellKey])) {

    /* ---------- ricostruisco la riga header -> valore ------------- */
    $rowAssoc = [];
    foreach ($currentHeaderKeys as $idx => $headerLabel) {
        $rowAssoc[$headerLabel ?: "col_$idx"] =
            $this->clean($cells[$idx] ?? '');
    }
    $rowAssoc['Cointestazione'] = $cointestazione;

    /* ---------- deduplica: hash della riga “pulita” --------------- */
                                   // registro
    $hash = md5(json_encode(array_filter($rowAssoc)));     // chiave
    if (isset($seen[$hash])) {
        continue;   // salta SOLO questa riga ✅
    }
    $seen[$hash] = true;

    /* ---------- sezione corretta ---------------------------------- */
    $sezione = $categoriaSezione[$cellKey];

    /* ---------- push nello scheletro ------------------------------ */
    $crData[$currentYear][$currentMonth][$currentIntermediario][$sezione][] =
        $rowAssoc;
}


                /* 8. HEADER TABELLA GARANTI (“Garante” …) ------------------------------------------- */
                if (isset($cells[0]) && str_ireplace(' ', ' ', $cells[0]) === 'Garante') { // NB: spazio non‑break
                    $currentGarHeaderKeys = $cells;          // salviamo le intestazioni
                    continue;
                }

                /* 9. DETTAGLIO GARANTE (“… codice censito …”) --------------------------------------- */
                if (!empty($currentGarHeaderKeys) && stripos($rowString, 'codice censito') !== false) {
                    $garRow = [];
                    foreach ($currentGarHeaderKeys as $idx => $headerLabel) {
                        $garRow[$headerLabel] = $cells[$idx] ?? null;
                    }
                    // push nella sezione Garanti
                    if ($currentYear && $currentMonth && $currentIntermediario) {
                        $crData[$currentYear][$currentMonth][$currentIntermediario]['Garanti'][] = $garRow;
                    }
                }
            }
        }

    dump($crData);
        return $crData;
    }

    private function clean($txt)
    {
        // rimuove CR/LF, spazi duplicati, tab etc.
        return preg_replace('/\s+/u', ' ', trim($txt));
    }

    private function extractBank(array $cells): string
{
  
    $labelSeen = false;  $candidate = '';

    foreach ($cells as $val) {
        $txt = $this->clean($val);
        if ($txt === '') continue;

  
        // salta testo di servizio
        $low = mb_strtolower($txt);
        if (str_starts_with($low, 'sezione informativa')
         || str_starts_with($low, 'crediti per cassa')
         || str_starts_with($low, 'non ci sono segnalazioni')
         || str_starts_with($low, 'situazione corrente')
         || str_starts_with($low, 'le informazioni sono disponibili')) {
            continue;
        }

        // aggiorno; la banca più probabile è l'ultima valida
        $candidate = $txt;
    }
    return $candidate;
}

}
