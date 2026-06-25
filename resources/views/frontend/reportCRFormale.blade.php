<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relazione Analisi Centrale Rischi Andamentale</title>
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

        <div class="cover-title">RELAZIONE DI ANALISI<br>DELLA CENTRALE RISCHI</div>
        <div class="cover-subtitle">Analisi Andamentale e Scoring del Rischio Creditizio</div>

        <div class="cover-line"></div>

        <div class="cover-meta">
            <strong>Periodo di Riferimento:</strong> {{ $response['Scoring']['Panoramica']['PeriodoRiferimento']['Inizio'] ?? 'N/D' }} — {{ $response['Scoring']['Panoramica']['PeriodoRiferimento']['Fine'] ?? 'N/D' }}<br>
            <strong>Data Elaborazione:</strong> {{ date('d/m/Y') }}<br>
            <strong>N. Intermediari Finanziari:</strong> {{ $response['Scoring']['Panoramica']['NumeroIntermediari'] ?? 'N/D' }}<br>
            <strong>Elaborazione a cura di:</strong> Key Performance Softwares S.r.l.
        </div>
    </div>


    {{-- ============================================
         §1 — INTRODUZIONE E PANORAMICA
         ============================================ --}}
    <div class="section">
        <div class="section-title">1. Introduzione e Panoramica</div>

        <p class="paragraph">
            La presente relazione è stata elaborata dal software <strong>ADA — Controllo di Gestione</strong>
            ed ha l'obiettivo di fornire un'analisi dettagliata della posizione creditizia dell'impresa
            presso il Sistema di Intermediazione Finanziaria, basata sui dati estratti dalla
            <strong>Centrale dei Rischi della Banca d'Italia</strong>.
        </p>

        <p class="paragraph">
            La Centrale dei Rischi (CR) è un sistema informativo gestito dalla Banca d'Italia che raccoglie le
            informazioni fornite dagli intermediari finanziari sui rapporti di credito con la propria clientela.
            L'analisi andamentale consente di monitorare l'evoluzione del profilo di rischio nel tempo,
            identificando eventuali anomalie, tensioni finanziarie e segnali pregiudizievoli.
        </p>

        <p class="paragraph">
            L'analisi copre il periodo compreso tra
            <strong>{{ $response['Scoring']['Panoramica']['PeriodoRiferimento']['Inizio'] ?? 'N/D' }}</strong> e
            <strong>{{ $response['Scoring']['Panoramica']['PeriodoRiferimento']['Fine'] ?? 'N/D' }}</strong>.
        </p>
    </div>

    {{-- Tabella Dati Generali --}}
    <div class="section">
        <div class="subsection-title">Dati Generali dell'Analisi</div>

        @php
            $finalScore = $response['Scoring']['Panoramica']['FinalScore'] ?? '0';
            $finalScoreNum = (float)str_replace(',', '.', $finalScore);
            $displayScore = number_format($finalScoreNum, 2, ',', '.');
        @endphp

        <table class="table-company">
            <thead>
                <tr>
                    <th colspan="2">DATI GENERALI — Analisi del {{ date('d/m/Y') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="label-cell">Periodo Analizzato</td>
                    <td class="value-cell">{{ $response['Scoring']['Panoramica']['PeriodoRiferimento']['Inizio'] ?? '' }} — {{ $response['Scoring']['Panoramica']['PeriodoRiferimento']['Fine'] ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Scoring Finale</td>
                    <td class="value-cell"><strong>{{ $displayScore }} / 10</strong></td>
                </tr>
                <tr>
                    <td class="label-cell">N. Intermediari Finanziari</td>
                    <td class="value-cell">{{ $response['Scoring']['Panoramica']['NumeroIntermediari'] ?? '0' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">N. Posizioni Contestate</td>
                    <td class="value-cell">{{ $response['Scoring']['Panoramica']['NumeroPosizioniContestate'] ?? '0' }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Score box --}}
        <div class="highlight-box-score">
            <div class="score-value">{{ $displayScore }}</div>
            <div class="score-label">Scoring Centrale Rischi su 10</div>
            @php
                if ($finalScoreNum >= 8) { $giudizioCR = 'Situazione positiva'; }
                elseif ($finalScoreNum >= 6) { $giudizioCR = 'Situazione nella norma'; }
                elseif ($finalScoreNum >= 4) { $giudizioCR = 'Attenzione - Tensioni rilevate'; }
                else { $giudizioCR = 'Situazione critica'; }
            @endphp
            <div style="margin-top:2mm; font-size:10pt; font-weight:bold; color:#334155;">
                {{ $giudizioCR }}
            </div>
        </div>
    </div>


    {{-- ============================================
         §2 — ANOMALIE DEGLI UTILIZZI
         ============================================ --}}
    <div class="page-break"></div>
    <div class="section">
        <div class="section-title">2. Anomalie degli Utilizzi</div>

        <p class="paragraph">
            Le anomalie degli utilizzi rappresentano tensioni finanziarie rilevate nell'utilizzo delle linee
            di credito concesse dagli intermediari. Un utilizzo anomalo può indicare difficoltà di liquidità
            dell'impresa e un potenziale peggioramento della qualità creditizia.
        </p>

        <table class="table-formal">
            <thead>
                <tr>
                    <th style="width:60%">Parametro Analizzato</th>
                    <th style="width:20%">Esito</th>
                    <th style="width:20%">Stato</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Tensione Finanziaria Utilizzi Autoliquidanti</td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomalieUtilizzi']['TensioneAutoliquidanti'])
                            <span class="esito-si">Sì</span>
                        @else
                            <span class="esito-no">No</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomalieUtilizzi']['TensioneAutoliquidanti'])
                            <span class="badge badge-risk">Tensione</span>
                        @else
                            <span class="badge badge-ok">Regolare</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Tensione Finanziaria Utilizzi a Revoca</td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomalieUtilizzi']['TensioneRevoca'])
                            <span class="esito-si">Sì</span>
                        @else
                            <span class="esito-no">No</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomalieUtilizzi']['TensioneRevoca'])
                            <span class="badge badge-risk">Tensione</span>
                        @else
                            <span class="badge badge-ok">Regolare</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Tensione Finanziaria Utilizzi a Scadenza</td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomalieUtilizzi']['TensioneScadenza'])
                            <span class="esito-si">Sì</span>
                        @else
                            <span class="esito-no">No</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomalieUtilizzi']['TensioneScadenza'])
                            <span class="badge badge-risk">Tensione</span>
                        @else
                            <span class="badge badge-ok">Regolare</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="paragraph-small">
            <strong>Nota:</strong> La tensione finanziaria viene rilevata quando l'utilizzo delle linee di credito
            supera in modo significativo e persistente i limiti dell'accordato operativo, indicando potenziali
            difficoltà nella gestione della liquidità aziendale.
        </p>
    </div>


    {{-- ============================================
         §3 — ANOMALIE LIEVI
         ============================================ --}}
    <div class="section">
        <div class="section-title">3. Anomalie Lievi</div>

        <p class="paragraph">
            Le anomalie lievi comprendono la presenza di impagati e sconfini sulle posizioni creditizie.
            Sebbene singolarmente non costituiscano segnali di allarme grave, la loro persistenza o
            l'aumento della frequenza possono indicare un deterioramento del profilo creditizio.
        </p>

        <table class="table-formal">
            <thead>
                <tr>
                    <th style="width:55%">Parametro Analizzato</th>
                    <th style="width:25%">Esito / Valore</th>
                    <th style="width:20%">Stato</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Impagati</td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomalieLievi']['Impagati'])
                            <span class="esito-si">Sì</span>
                        @else
                            <span class="esito-no">No</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomalieLievi']['Impagati'])
                            <span class="badge badge-risk">Anomalia</span>
                        @else
                            <span class="badge badge-ok">Regolare</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Presenza Sconfini</td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomalieLievi']['Sconfini'])
                            <span class="esito-si">Sì</span>
                        @else
                            <span class="esito-no">No</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomalieLievi']['Sconfini'])
                            <span class="badge badge-risk">Anomalia</span>
                        @else
                            <span class="badge badge-ok">Regolare</span>
                        @endif
                    </td>
                </tr>
                @if($response['Scoring']['AnomalieLievi']['Sconfini'])
                <tr>
                    <td style="padding-left:8mm;">— N° Sconfini Autoliquidanti</td>
                    <td class="text-center"><strong>{{ $response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI AUTOLIQUIDANTI'] ?? '0' }}</strong></td>
                    <td class="text-center"><span class="badge badge-neutral">Informativo</span></td>
                </tr>
                <tr>
                    <td style="padding-left:8mm;">— N° Sconfini a Revoca</td>
                    <td class="text-center"><strong>{{ $response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI A REVOCA'] ?? '0' }}</strong></td>
                    <td class="text-center"><span class="badge badge-neutral">Informativo</span></td>
                </tr>
                <tr>
                    <td style="padding-left:8mm;">— N° Sconfini a Scadenza</td>
                    <td class="text-center"><strong>{{ $response['Scoring']['AnomalieLievi']['NumeroSconfiniPerTipo']['RISCHI A SCADENZA'] ?? '0' }}</strong></td>
                    <td class="text-center"><span class="badge badge-neutral">Informativo</span></td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>


    {{-- ============================================
         §4 — ANOMALIE QUASI PREGIUDIZIEVOLI
         ============================================ --}}
    <div class="page-break"></div>
    <div class="section">
        <div class="section-title">4. Anomalie Quasi Pregiudizievoli</div>

        <p class="paragraph">
            Le anomalie quasi pregiudizievoli riguardano sconfini con durata significativa che possono
            rappresentare un segnale di deterioramento del credito. La classificazione per fasce temporali
            consente di valutare la gravità e la persistenza degli sconfinamenti.
        </p>

        <table class="table-formal">
            <thead>
                <tr>
                    <th style="width:55%">Parametro Analizzato</th>
                    <th style="width:20%">Esito</th>
                    <th style="width:25%">Gravità</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Sconfini Entro 90 Giorni</td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomalieQuasiPregiudizievoli']['SconfiniEntroNovantaGiorni'])
                            <span class="esito-si">Sì</span>
                        @else
                            <span class="esito-no">No</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomalieQuasiPregiudizievoli']['SconfiniEntroNovantaGiorni'])
                            <span class="badge badge-warning">Moderata</span>
                        @else
                            <span class="badge badge-ok">Nessuna</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Sconfini Oltre 90 Giorni ed Entro 180 Giorni</td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomalieQuasiPregiudizievoli']['SconfiniEntroCentoOttantaGiorni'])
                            <span class="esito-si">Sì</span>
                        @else
                            <span class="esito-no">No</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomalieQuasiPregiudizievoli']['SconfiniEntroCentoOttantaGiorni'])
                            <span class="badge badge-risk">Elevata</span>
                        @else
                            <span class="badge badge-ok">Nessuna</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Sconfini Oltre 180 Giorni</td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomalieQuasiPregiudizievoli']['SconfiniOltreCentoOttantaGiorni'])
                            <span class="esito-si">Sì</span>
                        @else
                            <span class="esito-no">No</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomalieQuasiPregiudizievoli']['SconfiniOltreCentoOttantaGiorni'])
                            <span class="badge badge-risk">Critica</span>
                        @else
                            <span class="badge badge-ok">Nessuna</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>


    {{-- ============================================
         §5 — ANOMALIE PREGIUDIZIEVOLI
         ============================================ --}}
    <div class="section">
        <div class="section-title">5. Anomalie Pregiudizievoli</div>

        <p class="paragraph">
            Le anomalie pregiudizievoli rappresentano le situazioni più gravi nel quadro dell'analisi della Centrale Rischi.
            La presenza di sofferenze, crediti passati a perdita o garanzie attivate con esito negativo indica
            un serio deterioramento della qualità creditizia dell'impresa presso il sistema bancario.
        </p>

        <table class="table-formal">
            <thead>
                <tr>
                    <th style="width:55%">Parametro Analizzato</th>
                    <th style="width:20%">Esito</th>
                    <th style="width:25%">Severità</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Garanzie Attivate con Esito Negativo</td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomaliePregiudizievoli']['GaranzieAttivateEsitoNegativo'])
                            <span class="esito-si">Sì</span>
                        @else
                            <span class="esito-no">No</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomaliePregiudizievoli']['GaranzieAttivateEsitoNegativo'])
                            <span class="badge badge-risk">Grave</span>
                        @else
                            <span class="badge badge-ok">Assente</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Sofferenze</td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomaliePregiudizievoli']['Sofferenze'])
                            <span class="esito-si">Sì</span>
                        @else
                            <span class="esito-no">No</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomaliePregiudizievoli']['Sofferenze'])
                            <span class="badge badge-risk">Grave</span>
                        @else
                            <span class="badge badge-ok">Assente</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Presenza Crediti Passati a Perdita</td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomaliePregiudizievoli']['CreditiPassatiPerdita'])
                            <span class="esito-si">Sì</span>
                        @else
                            <span class="esito-no">No</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($response['Scoring']['AnomaliePregiudizievoli']['CreditiPassatiPerdita'])
                            <span class="badge badge-risk">Grave</span>
                        @else
                            <span class="badge badge-ok">Assente</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>


    {{-- ============================================
         §6 — RIEPILOGO E GIUDIZIO FINALE
         ============================================ --}}
    <div class="page-break"></div>
    <div class="section">
        <div class="section-title">6. Riepilogo e Giudizio Finale</div>

        <p class="paragraph">
            La seguente tabella riassume tutti i risultati dell'analisi della Centrale Rischi Andamentale.
            Lo scoring finale è calcolato sulla base della valutazione complessiva di tutte le anomalie
            rilevate, pesate per gravità e persistenza nel periodo di riferimento.
        </p>

        {{-- Tabella sinottica --}}
        <table class="table-summary">
            <thead>
                <tr>
                    <th style="width:55%">Area di Analisi</th>
                    <th style="width:20%">Esito</th>
                    <th style="width:25%">Stato</th>
                </tr>
            </thead>
            <tbody>
                {{-- Utilizzi --}}
                <tr>
                    <td><strong>Tensione Utilizzi Autoliquidanti</strong></td>
                    <td class="text-center">{{ $response['Scoring']['AnomalieUtilizzi']['TensioneAutoliquidanti'] ? 'Sì' : 'No' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $response['Scoring']['AnomalieUtilizzi']['TensioneAutoliquidanti'] ? 'badge-risk' : 'badge-ok' }}">
                            {{ $response['Scoring']['AnomalieUtilizzi']['TensioneAutoliquidanti'] ? 'Tensione' : 'OK' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Tensione Utilizzi a Revoca</strong></td>
                    <td class="text-center">{{ $response['Scoring']['AnomalieUtilizzi']['TensioneRevoca'] ? 'Sì' : 'No' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $response['Scoring']['AnomalieUtilizzi']['TensioneRevoca'] ? 'badge-risk' : 'badge-ok' }}">
                            {{ $response['Scoring']['AnomalieUtilizzi']['TensioneRevoca'] ? 'Tensione' : 'OK' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Tensione Utilizzi a Scadenza</strong></td>
                    <td class="text-center">{{ $response['Scoring']['AnomalieUtilizzi']['TensioneScadenza'] ? 'Sì' : 'No' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $response['Scoring']['AnomalieUtilizzi']['TensioneScadenza'] ? 'badge-risk' : 'badge-ok' }}">
                            {{ $response['Scoring']['AnomalieUtilizzi']['TensioneScadenza'] ? 'Tensione' : 'OK' }}
                        </span>
                    </td>
                </tr>
                {{-- Lievi --}}
                <tr>
                    <td><strong>Impagati</strong></td>
                    <td class="text-center">{{ $response['Scoring']['AnomalieLievi']['Impagati'] ? 'Sì' : 'No' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $response['Scoring']['AnomalieLievi']['Impagati'] ? 'badge-risk' : 'badge-ok' }}">
                            {{ $response['Scoring']['AnomalieLievi']['Impagati'] ? 'Anomalia' : 'OK' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Presenza Sconfini</strong></td>
                    <td class="text-center">{{ $response['Scoring']['AnomalieLievi']['Sconfini'] ? 'Sì' : 'No' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $response['Scoring']['AnomalieLievi']['Sconfini'] ? 'badge-risk' : 'badge-ok' }}">
                            {{ $response['Scoring']['AnomalieLievi']['Sconfini'] ? 'Anomalia' : 'OK' }}
                        </span>
                    </td>
                </tr>
                {{-- Quasi Pregiudizievoli --}}
                <tr>
                    <td><strong>Sconfini Entro 90 Giorni</strong></td>
                    <td class="text-center">{{ $response['Scoring']['AnomalieQuasiPregiudizievoli']['SconfiniEntroNovantaGiorni'] ? 'Sì' : 'No' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $response['Scoring']['AnomalieQuasiPregiudizievoli']['SconfiniEntroNovantaGiorni'] ? 'badge-warning' : 'badge-ok' }}">
                            {{ $response['Scoring']['AnomalieQuasiPregiudizievoli']['SconfiniEntroNovantaGiorni'] ? 'Attenzione' : 'OK' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Sconfini 90-180 Giorni</strong></td>
                    <td class="text-center">{{ $response['Scoring']['AnomalieQuasiPregiudizievoli']['SconfiniEntroCentoOttantaGiorni'] ? 'Sì' : 'No' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $response['Scoring']['AnomalieQuasiPregiudizievoli']['SconfiniEntroCentoOttantaGiorni'] ? 'badge-risk' : 'badge-ok' }}">
                            {{ $response['Scoring']['AnomalieQuasiPregiudizievoli']['SconfiniEntroCentoOttantaGiorni'] ? 'Critico' : 'OK' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Sconfini Oltre 180 Giorni</strong></td>
                    <td class="text-center">{{ $response['Scoring']['AnomalieQuasiPregiudizievoli']['SconfiniOltreCentoOttantaGiorni'] ? 'Sì' : 'No' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $response['Scoring']['AnomalieQuasiPregiudizievoli']['SconfiniOltreCentoOttantaGiorni'] ? 'badge-risk' : 'badge-ok' }}">
                            {{ $response['Scoring']['AnomalieQuasiPregiudizievoli']['SconfiniOltreCentoOttantaGiorni'] ? 'Critico' : 'OK' }}
                        </span>
                    </td>
                </tr>
                {{-- Pregiudizievoli --}}
                <tr>
                    <td><strong>Garanzie con Esito Negativo</strong></td>
                    <td class="text-center">{{ $response['Scoring']['AnomaliePregiudizievoli']['GaranzieAttivateEsitoNegativo'] ? 'Sì' : 'No' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $response['Scoring']['AnomaliePregiudizievoli']['GaranzieAttivateEsitoNegativo'] ? 'badge-risk' : 'badge-ok' }}">
                            {{ $response['Scoring']['AnomaliePregiudizievoli']['GaranzieAttivateEsitoNegativo'] ? 'Grave' : 'OK' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Sofferenze</strong></td>
                    <td class="text-center">{{ $response['Scoring']['AnomaliePregiudizievoli']['Sofferenze'] ? 'Sì' : 'No' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $response['Scoring']['AnomaliePregiudizievoli']['Sofferenze'] ? 'badge-risk' : 'badge-ok' }}">
                            {{ $response['Scoring']['AnomaliePregiudizievoli']['Sofferenze'] ? 'Grave' : 'OK' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Crediti Passati a Perdita</strong></td>
                    <td class="text-center">{{ $response['Scoring']['AnomaliePregiudizievoli']['CreditiPassatiPerdita'] ? 'Sì' : 'No' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $response['Scoring']['AnomaliePregiudizievoli']['CreditiPassatiPerdita'] ? 'badge-risk' : 'badge-ok' }}">
                            {{ $response['Scoring']['AnomaliePregiudizievoli']['CreditiPassatiPerdita'] ? 'Grave' : 'OK' }}
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="highlight-box" style="margin-top: 6mm;">
            <p class="paragraph-small" style="margin-bottom:0;">
                <strong>Conclusioni:</strong> La presente relazione è stata generata in data {{ date('d/m/Y') }}
                sulla base dei dati disponibili nella Centrale dei Rischi della Banca d'Italia.
                I risultati dell'analisi andamentale hanno carattere indicativo e devono essere integrati con
                valutazioni qualitative da parte della direzione aziendale e degli organi di controllo.
                Si raccomanda un monitoraggio continuo delle posizioni creditizie presso tutti gli intermediari finanziari.
            </p>
        </div>
    </div>
    </div>{{-- /content-wrapper --}}

</body>
</html>
