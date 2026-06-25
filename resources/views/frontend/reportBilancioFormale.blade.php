<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relazione Analisi di Bilancio — {{ $datiImpresa['ragione_sociale'] ?? 'Azienda' }}</title>
    @include('frontend.partials._pdfStyles')
</head>
<body>

    {{-- Carta intestata --}}
    @include('frontend.partials._pdfLetterhead')

    <div class="content-wrapper">

    {{-- ============================================
         COPERTINA
         ============================================ --}}
    <div class="cover-page">
        <div class="cover-logo">
            <img src="{{ public_path('images/ada_logo.png') }}" alt="ADA Logo">
        </div>

        <div class="cover-title">RELAZIONE DI ANALISI DI BILANCIO</div>
        <div class="cover-subtitle">Analisi degli Indici Patrimoniali e Finanziari</div>

        <div class="cover-line"></div>

        <div class="cover-company">{{ $datiImpresa['ragione_sociale'] ?? 'N/D' }}</div>

        <div class="cover-meta">
            <strong>Tipologia Impresa:</strong> {{ $datiImpresa['tipologia_impresa'] ?? 'N/D' }}<br>
            <strong>Settore Attività:</strong> {{ $datiImpresa['settore'] ?? 'N/D' }}<br>
            <strong>Esercizio Precedente:</strong> {{ $datiImpresa['data_chiusura'] ?? 'N/D' }}<br>
            <strong>Esercizio Corrente:</strong> {{ $datiImpresa['data_ultima'] ?? 'N/D' }}<br>
            <strong>Data Elaborazione:</strong> {{ date('d/m/Y') }}<br>
            <strong>Rif.:</strong> DOC-{{ $datiImpresa['idDocumento'] ?? '000' }}-{{ date('Y') }}
        </div>
    </div>


    {{-- ============================================
         §1 — INTRODUZIONE
         ============================================ --}}
    <div class="section">
        <div class="section-title">1. Introduzione</div>

        <p class="paragraph">
            La presente relazione è stata elaborata dal software <strong>ADA — Controllo di Gestione</strong> ed ha
            l'obiettivo di fornire un'analisi dettagliata della situazione economico-patrimoniale e finanziaria
            dell'impresa <strong>{{ $datiImpresa['ragione_sociale'] ?? 'N/D' }}</strong>,
            sulla base dei dati di bilancio depositati relativi all'esercizio {{ $datiImpresa['data_ultima'] ?? 'N/D' }}.
        </p>

        <p class="paragraph">
            L'analisi si basa sugli indicatori definiti dal <strong>Consiglio Nazionale dei Dottori Commercialisti
            e degli Esperti Contabili (CNDCEC)</strong>, in conformità a quanto previsto dal
            <strong>Codice della Crisi d'Impresa e dell'Insolvenza (D.Lgs. 14/2019)</strong>,
            al fine di identificare tempestivamente segnali di squilibrio patrimoniale, economico e finanziario
            che possano rendere probabile lo stato di crisi dell'impresa.
        </p>

        <p class="paragraph">
            L'elaborazione è stata effettuata a cura di <strong>Key Performance Softwares S.r.l.</strong>
            in data {{ date('d/m/Y') }}.
        </p>
    </div>

    {{-- Tabella Dati Impresa --}}
    <div class="section">
        <div class="subsection-title">Dati Anagrafici dell'Impresa</div>

        <table class="table-company">
            <thead>
                <tr>
                    <th colspan="2">DATI IMPRESA</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="label-cell">Ditta / Denominazione / Ragione Sociale</td>
                    <td class="value-cell">{{ $datiImpresa['ragione_sociale'] ?? 'N/D' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Tipologia Impresa</td>
                    <td class="value-cell">{{ $datiImpresa['tipologia_impresa'] ?? 'N/D' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Settore di Attività</td>
                    <td class="value-cell">{{ $datiImpresa['settore'] ?? 'N/D' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Esercizio Precedente</td>
                    <td class="value-cell">{{ $datiImpresa['data_chiusura'] ?? 'N/D' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Esercizio Corrente</td>
                    <td class="value-cell">{{ $datiImpresa['data_ultima'] ?? 'N/D' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Elaborazione a cura di</td>
                    <td class="value-cell">Key Performance Softwares S.r.l.</td>
                </tr>
            </tbody>
        </table>
    </div>


    {{-- ============================================
         §2 — INDICI PRIMARI (CNDCEC)
         ============================================ --}}
    <div class="page-break"></div>
    <div class="section">
        <div class="section-title">2. Analisi degli Indici Primari (CNDCEC)</div>

        <p class="paragraph">
            Gli indici primari rappresentano i parametri fondamentali stabiliti dal CNDCEC per la verifica
            della continuità aziendale. Ciascun indice viene confrontato con le soglie di riferimento specifiche
            per il settore di attività dell'impresa. Un valore che supera la soglia critica indica un potenziale
            segnale di squilibrio.
        </p>

        @if(isset($datiImpresa['bilancioAnalisi']['Indici']['Basic']) && is_array($datiImpresa['bilancioAnalisi']['Indici']['Basic']))
        <table class="table-formal">
            <thead>
                <tr>
                    <th style="width:45%">Indice Analizzato</th>
                    <th style="width:25%">Valore ({{ $datiImpresa['data_ultima'] ?? '' }})</th>
                    <th style="width:15%">Fuori Soglia</th>
                    <th style="width:15%">Esito</th>
                </tr>
            </thead>
            <tbody>
                @foreach($datiImpresa['bilancioAnalisi']['Indici']['Basic'] as $key => $singleIndice)
                    @if(isset($singleIndice['value']))
                    <tr>
                        <td><strong>{{ $key }}</strong></td>
                        <td class="text-right">{{ $singleIndice['value'] }}%</td>
                        <td class="text-center">
                            @if($singleIndice['fuoriSoglia'] == true)
                                <span class="badge badge-risk">Sì</span>
                            @else
                                <span class="badge badge-ok">No</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($singleIndice['fuoriSoglia'] == true)
                                <span class="esito-si">⚠ Critico</span>
                            @else
                                <span class="esito-no">✓ Regolare</span>
                            @endif
                        </td>
                    </tr>
                    @elseif(is_string($singleIndice))
                    <tr>
                        <td><strong>{{ $key }}</strong></td>
                        <td colspan="3" class="text-center">
                            @if(str_contains($singleIndice, 'NON a Rischio') || str_contains($singleIndice, 'non a rischio'))
                                <span class="badge badge-ok">{{ $singleIndice }}</span>
                            @elseif(str_contains($singleIndice, 'a Rischio') || str_contains($singleIndice, 'a rischio'))
                                <span class="badge badge-risk">{{ $singleIndice }}</span>
                            @else
                                <span class="badge badge-neutral">{{ $singleIndice }}</span>
                            @endif
                        </td>
                    </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        <p class="paragraph-small">
            <strong>Nota metodologica:</strong> Gli indici sono calcolati sulla base dei dati XBRL estratti dal bilancio
            depositato. I valori sono espressi in percentuale e confrontati con le soglie di riferimento definite dal CNDCEC
            per la specifica tipologia di impresa e settore di attività.
        </p>
        @else
        <div class="highlight-box-warning">
            <strong>Attenzione:</strong> Non sono disponibili dati per il calcolo degli indici primari.
        </div>
        @endif
    </div>


    {{-- ============================================
         §3 — INDICI AVANZATI
         ============================================ --}}
    @if(isset($datiImpresa['bilancioAnalisi']['Indici']['Advanced']) && is_array($datiImpresa['bilancioAnalisi']['Indici']['Advanced']))
    <div class="page-break"></div>
    <div class="section">
        <div class="section-title">3. Analisi degli Indici Avanzati</div>

        <p class="paragraph">
            Gli indici avanzati forniscono un'analisi supplementare della struttura patrimoniale e finanziaria
            dell'impresa. Questi indicatori consentono di approfondire aspetti specifici quali la redditività,
            la solidità patrimoniale, la capacità di rimborso del debito e l'efficienza operativa.
        </p>

        <table class="table-formal">
            <thead>
                <tr>
                    <th style="width:50%">Indice Analizzato</th>
                    <th style="width:25%">Valore Calcolato</th>
                    <th style="width:25%">Fuori Soglia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($datiImpresa['bilancioAnalisi']['Indici']['Advanced'] as $key => $singleIndice)
                    @if($singleIndice !== false && isset($singleIndice['value']))
                    <tr>
                        <td><strong>{{ str_replace('_', ' ', $key) }}</strong></td>
                        <td class="text-right">{{ $singleIndice['value'] }}%</td>
                        <td class="text-center">
                            @if(isset($singleIndice['fuoriSoglia']) && $singleIndice['fuoriSoglia'] == true)
                                <span class="badge badge-risk">Sì</span>
                            @else
                                <span class="badge badge-ok">No</span>
                            @endif
                        </td>
                    </tr>
                    @elseif($singleIndice === false)
                    <tr>
                        <td><strong>{{ str_replace('_', ' ', $key) }}</strong></td>
                        <td class="text-center" colspan="2"><span class="badge badge-warning">Dati insufficienti</span></td>
                    </tr>
                    @elseif(is_string($singleIndice))
                    <tr>
                        <td><strong>{{ str_replace('_', ' ', $key) }}</strong></td>
                        <td colspan="2" class="text-center"><span class="badge badge-neutral">{{ $singleIndice }}</span></td>
                    </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
    @endif


    {{-- ============================================
         §4 — QUESTIONARI DI ALLERTA QUALITATIVA
         ============================================ --}}
    @if(isset($datiImpresa['bilancioAnalisi']['Questionari']) && is_array($datiImpresa['bilancioAnalisi']['Questionari']))
    <div class="page-break"></div>
    <div class="section">
        <div class="section-title">4. Questionari di Allerta Qualitativa (CNDC)</div>

        <p class="paragraph">
            La presente sezione riporta i risultati dei questionari qualitativi compilati per la verifica
            di situazioni di allerta ai sensi dell'art. 13 del D.Lgs. 14/2019. Ciascuna voce è stata
            valutata in base ai dati forniti dall'impresa e confrontata con le soglie normative di riferimento.
        </p>

        <table class="table-formal">
            <thead>
                <tr>
                    <th style="width:55%">Voce Questionario</th>
                    <th style="width:25%">Esito ({{ $datiImpresa['data_ultima'] ?? '' }})</th>
                    <th style="width:20%">Stato</th>
                </tr>
            </thead>
            <tbody>
                @foreach($datiImpresa['bilancioAnalisi']['Questionari'] as $key => $alertQuestionari)
                <tr>
                    <td><strong>{{ $key }}</strong></td>
                    <td class="text-center">
                        @if(isset($alertQuestionari->alert))
                            {{ $alertQuestionari->alert }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-center">
                        @if(isset($alertQuestionari->alert) && ($alertQuestionari->alert === 'Azienda a rischio' || $alertQuestionari->alert === 'Azienda a Rischio'))
                            <span class="badge badge-risk">Rischio</span>
                        @elseif(isset($alertQuestionari->alert) && ($alertQuestionari->alert === 'Azienda NON a rischio' || $alertQuestionari->alert === 'Azienda NON a Rischio'))
                            <span class="badge badge-ok">OK</span>
                        @elseif(!isset($alertQuestionari->alert))
                            <span class="badge badge-warning">N/D</span>
                        @else
                            <span class="badge badge-neutral">{{ $alertQuestionari->alert }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <p class="paragraph-small">
            <strong>Legenda:</strong>
            <span class="badge badge-ok">OK</span> Nessuna criticità rilevata —
            <span class="badge badge-risk">Rischio</span> Superamento soglia di allerta —
            <span class="badge badge-warning">N/D</span> Dati non disponibili o non compilati.
        </p>
    </div>
    @endif


    {{-- ============================================
         §5 — RIEPILOGO E GIUDIZIO FINALE
         ============================================ --}}
    <div class="page-break"></div>
    <div class="section">
        <div class="section-title">5. Riepilogo e Giudizio Finale</div>

        <p class="paragraph">
            La seguente tabella riassume i risultati complessivi dell'analisi di bilancio condotta
            sull'impresa <strong>{{ $datiImpresa['ragione_sociale'] ?? 'N/D' }}</strong>.
            Il punteggio finale è calcolato sulla base della media ponderata degli indici primari e avanzati,
            tenendo conto delle soglie settoriali e delle eventuali criticità emerse dai questionari qualitativi.
        </p>

        @if(isset($datiImpresa['bilancioAnalisi']['Score']))
        @php
            $scoreRaw = $datiImpresa['bilancioAnalisi']['Score'] ?? 0;
            $scoreNum = is_numeric($scoreRaw) ? $scoreRaw : floatval(str_replace(',', '.', $scoreRaw));
            $scoreDisplay = number_format($scoreNum, 1, ',', '.');

            if ($scoreNum >= 90) { $giudizio = 'Solido'; $giudizioClass = 'highlight-box-success'; }
            elseif ($scoreNum >= 70) { $giudizio = 'Buono'; $giudizioClass = 'highlight-box-success'; }
            elseif ($scoreNum >= 50) { $giudizio = 'Neutro / Debole'; $giudizioClass = 'highlight-box'; }
            else { $giudizio = 'Fragile / Critico'; $giudizioClass = 'highlight-box-warning'; }
        @endphp

        <div class="highlight-box-score">
            <div class="score-value">{{ $scoreDisplay }}</div>
            <div class="score-label">Scoring Complessivo su 100</div>
            <div style="margin-top:2mm; font-size:11pt; font-weight:bold; color:#334155;">
                Giudizio: {{ $giudizio }}
            </div>
        </div>
        @endif

        {{-- Tabella sinottica --}}
        <div class="subsection-title">Quadro Sinottico degli Indicatori</div>

        <table class="table-summary">
            <thead>
                <tr>
                    <th style="width:50%">Indicatore / Parametro</th>
                    <th style="width:25%">Risultato</th>
                    <th style="width:25%">Stato</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($datiImpresa['bilancioAnalisi']['Indici']['Basic']) && is_array($datiImpresa['bilancioAnalisi']['Indici']['Basic']))
                @foreach($datiImpresa['bilancioAnalisi']['Indici']['Basic'] as $key => $singleIndice)
                    @if(isset($singleIndice['value']))
                    <tr>
                        <td>{{ $key }}</td>
                        <td class="text-right">{{ $singleIndice['value'] }}%</td>
                        <td class="text-center">
                            @if($singleIndice['fuoriSoglia'] == true)
                                <span class="badge badge-risk">⚠ Critico</span>
                            @else
                                <span class="badge badge-ok">✓ Regolare</span>
                            @endif
                        </td>
                    </tr>
                    @endif
                @endforeach
                @endif

                @if(isset($datiImpresa['bilancioAnalisi']['Questionari']) && is_array($datiImpresa['bilancioAnalisi']['Questionari']))
                @foreach($datiImpresa['bilancioAnalisi']['Questionari'] as $key => $alertQ)
                <tr>
                    <td>{{ $key }}</td>
                    <td class="text-center">
                        @if(isset($alertQ->alert)) {{ $alertQ->alert }} @else N/A @endif
                    </td>
                    <td class="text-center">
                        @if(isset($alertQ->alert) && str_contains($alertQ->alert, 'a rischio') || (isset($alertQ->alert) && str_contains($alertQ->alert, 'a Rischio')))
                            @if(!str_contains($alertQ->alert, 'NON'))
                                <span class="badge badge-risk">Rischio</span>
                            @else
                                <span class="badge badge-ok">OK</span>
                            @endif
                        @elseif(!isset($alertQ->alert))
                            <span class="badge badge-warning">N/D</span>
                        @else
                            <span class="badge badge-ok">OK</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                @endif
            </tbody>
        </table>

        <div class="highlight-box" style="margin-top: 6mm;">
            <p class="paragraph-small" style="margin-bottom:0;">
                <strong>Conclusioni:</strong> La presente relazione è stata generata in data {{ date('d/m/Y') }}
                sulla base dei dati di bilancio disponibili. I risultati dell'analisi hanno carattere indicativo e
                devono essere integrati con valutazioni qualitative da parte degli organi di controllo e dell'organo
                amministrativo dell'impresa, ai sensi di quanto previsto dal Codice della Crisi d'Impresa e dell'Insolvenza.
            </p>
        </div>
    </div>
    </div>{{-- /content-wrapper --}}

</body>
</html>
