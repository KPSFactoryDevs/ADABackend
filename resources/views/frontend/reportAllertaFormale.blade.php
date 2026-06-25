<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relazione di Analisi di Allerta</title>
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

        <div class="cover-title">RELAZIONE DI ANALISI<br>DI ALLERTA</div>
        <div class="cover-subtitle">Valutazione della Continuità Aziendale e Segnali di Crisi</div>

        <div class="cover-line"></div>

        <div class="cover-meta">
            <strong>Data Elaborazione:</strong> {{ date('d/m/Y') }}<br>
            <strong>Scoring Generale:</strong> {{ $dati['pageData']['FinalScore'] ?? 'N/D' }}<br>
            <strong>Elaborazione a cura di:</strong> Key Performance Softwares S.r.l.
        </div>
    </div>


    {{-- ============================================
         §1 — INTRODUZIONE
         ============================================ --}}
    <div class="section">
        <div class="section-title">1. Introduzione</div>

        <p class="paragraph">
            La presente relazione è stata elaborata dal software <strong>ADA — Controllo di Gestione</strong>
            ed ha l'obiettivo di fornire una valutazione complessiva dello stato di salute dell'impresa
            ai sensi del <strong>Codice della Crisi d'Impresa e dell'Insolvenza (D.Lgs. 14/2019)</strong>.
        </p>

        <p class="paragraph">
            L'analisi di allerta integra tre fonti principali di informazione: l'<strong>analisi di bilancio</strong>
            (indici patrimoniali, finanziari e reddituali), l'<strong>analisi della Centrale Rischi</strong>
            (posizioni creditizie, anomalie, tensioni) e i <strong>questionari qualitativi AS IS e TO BE</strong>
            (valutazione della situazione corrente e prospettica dell'impresa).
        </p>

        <p class="paragraph">
            Il risultato finale è uno <strong>scoring generale</strong> che sintetizza tutte le componenti
            dell'analisi in un unico indicatore, consentendo una rapida identificazione del livello di rischio.
        </p>

        {{-- Score box --}}
        <div class="highlight-box-score">
            <div class="score-value">{{ $dati['pageData']['FinalScore'] ?? 'N/D' }}</div>
            <div class="score-label">Scoring Generale di Allerta</div>
        </div>
    </div>


    {{-- ============================================
         §2 — ANALISI PER AREA D'INTERESSE
         ============================================ --}}
    <div class="section">
        <div class="section-title">2. Analisi per Area d'Interesse</div>

        <p class="paragraph">
            La seguente tabella riporta il giudizio sintetico per ciascuna area di valutazione.
            Ogni area contribuisce alla determinazione dello scoring generale attraverso un peso
            specifico calibrato sulla rilevanza del segnale di allerta.
        </p>

        <table class="table-formal">
            <thead>
                <tr>
                    <th style="width:55%">Area di Valutazione</th>
                    <th style="width:45%">Giudizio</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dati['GeneralScore'] as $key => $valore)
                <tr>
                    <td><strong>{{ str_replace('_', ' ', $key) }}</strong></td>
                    <td class="text-center">
                        @php
                            $valStr = is_string($valore) ? strtolower($valore) : '';
                            $isNumeric = is_numeric($valore);
                        @endphp
                        @if($isNumeric)
                            <strong>{{ $valore }}</strong>
                        @elseif(str_contains($valStr, 'negativ') || str_contains($valStr, 'alto') || str_contains($valStr, 'critico') || str_contains($valStr, 'rischio'))
                            <span class="badge badge-risk">{{ $valore }}</span>
                        @elseif(str_contains($valStr, 'positiv') || str_contains($valStr, 'buon') || str_contains($valStr, 'basso') || str_contains($valStr, 'nella norma'))
                            <span class="badge badge-ok">{{ $valore }}</span>
                        @elseif(str_contains($valStr, 'medio') || str_contains($valStr, 'attenzione'))
                            <span class="badge badge-warning">{{ $valore }}</span>
                        @else
                            <span class="badge badge-neutral">{{ $valore }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>


    {{-- ============================================
         §3 — ANALISI DI BILANCIO (DETTAGLIO)
         ============================================ --}}
    <div class="page-break"></div>
    <div class="section">
        <div class="section-title">3. Analisi di Bilancio — Dettaglio</div>

        <p class="paragraph">
            La presente sezione riporta i risultati dell'analisi degli indici di bilancio avanzati.
            Ciascun indice è stato calcolato sulla base dei dati contabili estratti dal bilancio
            XBRL depositato e confrontato con le soglie settoriali di riferimento.
        </p>

        @php
            $scoreBilancio = $dati['pageData']['ValutazioneGeneraleBilancio']['Score'] ?? '0';
            $scoreBilancioNum = (float)str_replace(',', '.', $scoreBilancio) * 10;
            $displayScoreBil = number_format($scoreBilancioNum, 1, ',', '.');
        @endphp

        <div class="highlight-box-score">
            <div class="score-value">{{ $displayScoreBil }}</div>
            <div class="score-label">Scoring Analisi Bilancio su 10</div>
        </div>

        @if(isset($dati['pageData']['ValutazioneGeneraleBilancio']['Giudizi']) && is_array($dati['pageData']['ValutazioneGeneraleBilancio']['Giudizi']))
        <table class="table-formal">
            <thead>
                <tr>
                    <th style="width:40%">Indice</th>
                    <th style="width:25%">Valore</th>
                    <th style="width:35%">Giudizio</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dati['pageData']['ValutazioneGeneraleBilancio']['Giudizi'] as $key => $giudizioData)
                    @if($giudizioData != false)
                    <tr>
                        <td><strong>{{ str_replace('_', ' ', $key) }}</strong></td>
                        <td class="text-right">
                            @if(isset($dati['pageData']['bilancioData']['Indici']['Advanced'][$key]))
                                {{ $dati['pageData']['bilancioData']['Indici']['Advanced'][$key] }}%
                            @else
                                N/D
                            @endif
                        </td>
                        <td class="text-center">
                            @php
                                $giudTxt = $giudizioData['Giudizio'] ?? 'N/D';
                                $giudLow = strtolower($giudTxt);
                            @endphp
                            @if(str_contains($giudLow, 'negativ') || str_contains($giudLow, 'pessim') || str_contains($giudLow, 'critico') || str_contains($giudLow, 'insufficiente'))
                                <span class="badge badge-risk">{{ $giudTxt }}</span>
                            @elseif(str_contains($giudLow, 'positiv') || str_contains($giudLow, 'buon') || str_contains($giudLow, 'ottim') || str_contains($giudLow, 'sufficiente'))
                                <span class="badge badge-ok">{{ $giudTxt }}</span>
                            @else
                                <span class="badge badge-neutral">{{ $giudTxt }}</span>
                            @endif
                        </td>
                    </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        @endif
    </div>


    {{-- ============================================
         §4 — ANALISI CENTRALE RISCHI (DETTAGLIO)
         ============================================ --}}
    <div class="page-break"></div>
    <div class="section">
        <div class="section-title">4. Analisi Centrale Rischi — Dettaglio</div>

        <p class="paragraph">
            La presente sezione riporta i risultati dell'analisi della Centrale dei Rischi della Banca d'Italia.
            Sono state esaminate 16 voci di analisi, suddivise per area tematica, ciascuna delle quali
            contribuisce alla determinazione dello scoring CR complessivo.
        </p>

        @php
            $scoreCR = $dati['pageData']['scoreCR'] ?? '0';
            $scoreCRNum = (float)str_replace(',', '.', $scoreCR) * 10;
            $displayScoreCR = number_format($scoreCRNum, 1, ',', '.');
        @endphp

        <div class="highlight-box-score">
            <div class="score-value">{{ $displayScoreCR }}</div>
            <div class="score-label">Scoring Centrale Rischi su 10</div>
        </div>

        {{-- Tabella CR dettagliata --}}
        @php
            $crLabels = [
                1  => ['cat' => 'CR Scoring',                    'text' => 'Valutazione Negativa del CR Scoring che deriva dall\'analisi sintetica della CR'],
                2  => ['cat' => 'Sconfini e Ritardi',            'text' => 'Sconfini significativi e/o ripetuti nel corso degli ultimi 12 mesi'],
                3  => ['cat' => 'Sconfini e Ritardi',            'text' => 'Mancato pagamento di finanziamenti o di altre scadenze'],
                4  => ['cat' => 'Aumento Garanzie',              'text' => 'Aumento delle richieste di garanzie su beni aziendali'],
                5  => ['cat' => 'Aumento Garanzie',              'text' => 'Aumento delle garanzie concesse su esposizioni di altri soggetti'],
                6  => ['cat' => 'Insoluti Portafoglio Anticipi', 'text' => 'Aumento significativo o peso elevato di incidenza insoluti su anticipo crediti'],
                7  => ['cat' => 'Affidamenti e Utilizzi',        'text' => 'Aumento significativo delle richieste di affidamenti di cassa'],
                8  => ['cat' => 'Affidamenti e Utilizzi',        'text' => 'Richiesta finanziamenti straordinari'],
                9  => ['cat' => 'Affidamenti e Utilizzi',        'text' => 'Crescita continua e rilevante di utilizzi per liquidità di cassa'],
                10 => ['cat' => 'Affidamenti e Utilizzi',        'text' => 'Crescita continua e rilevante di utilizzi per smobilizzo crediti commerciali o tensione finanziaria'],
                11 => ['cat' => 'Rientro Linee',                 'text' => 'Rientri nelle linee di cassa'],
                12 => ['cat' => 'Rientro Linee',                 'text' => 'Rientri nelle linee anticipi SBF/fatture'],
                13 => ['cat' => 'Rientro Linee',                 'text' => 'Rientri nelle linee di crediti per firma'],
                14 => ['cat' => 'Segnalazioni Pregiudizievoli',  'text' => 'Presenza sconfinamenti fra 90gg e 180gg oppure oltre i 180gg'],
                15 => ['cat' => 'Segnalazioni Pregiudizievoli',  'text' => 'Presenza di garanzie attivate con esito negativo'],
                16 => ['cat' => 'Segnalazioni Pregiudizievoli',  'text' => 'Presenza di sofferenze o crediti passati a perdita'],
            ];
            $lastCat = '';
        @endphp

        <table class="table-formal">
            <thead>
                <tr>
                    <th style="width:5%">N.</th>
                    <th style="width:65%">Parametro Analizzato</th>
                    <th style="width:15%">Esito</th>
                    <th style="width:15%">Stato</th>
                </tr>
            </thead>
            <tbody>
                @foreach($crLabels as $n => $info)
                    @if($info['cat'] !== $lastCat)
                    <tr>
                        <td colspan="4" style="background-color:#eef2ff; font-weight:bold; font-size:8pt; color:#2D3192; padding:2mm 3mm;">
                            {{ $info['cat'] }}
                        </td>
                    </tr>
                    @php $lastCat = $info['cat']; @endphp
                    @endif
                    <tr>
                        <td class="text-center">{{ $n }}</td>
                        <td>{{ $info['text'] }}</td>
                        <td class="text-center">
                            @if(isset($dati['pageData']['crAlerts'][$n]) && $dati['pageData']['crAlerts'][$n])
                                <span class="esito-si">Sì</span>
                            @else
                                <span class="esito-no">No</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if(isset($dati['pageData']['crAlerts'][$n]) && $dati['pageData']['crAlerts'][$n])
                                <span class="badge badge-risk">Alert</span>
                            @else
                                <span class="badge badge-ok">OK</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>


    {{-- ============================================
         §5 — QUESTIONARIO AS IS
         ============================================ --}}
    <div class="page-break"></div>
    <div class="section">
        <div class="section-title">5. Questionario AS IS — Situazione Aziendale Corrente</div>

        <p class="paragraph">
            Il questionario AS IS consente di valutare la situazione corrente dell'impresa attraverso
            domande qualitative suddivise in quattro macro-aree: anomalie dei pagamenti verso controparti
            commerciali, anomalie gestionali, minacce erariali e rischi caratteristici, ed eventi pregiudizievoli.
        </p>

        @php
            $asIsGroups = [
                ['title' => 'Anomalie dei Pagamenti verso Controparti Commerciali', 'keys' => ['1-1','1-2','1-3','1-4','1-5','1-6','1-7','1-8']],
                ['title' => 'Anomalie Gestionali', 'keys' => ['2-1','2-2','2-3','2-4','2-5','2-6','2-7','2-8','2-9','2-10']],
                ['title' => 'Minacce Erariali e Rischi Caratteristici', 'keys' => ['3-1','3-2','3-3','3-4']],
                ['title' => 'Minacce da Eventi Pregiudizievoli', 'keys' => ['4-1','4-2','4-3','4-4','4-5','4-6']],
            ];

            $asIsTexts = [
                '1-1' => 'Sono presenti fatture e avvisi di pagamento di cui non si è rispettata la scadenza?',
                '1-2' => 'Ci sono ritardi nei pagamenti ai fornitori superiori a 90 giorni per un ammontare superiore a quello dei debiti non scaduti?',
                '1-3' => 'I fornitori hanno modificato le condizioni di pagamento delle forniture?',
                '1-4' => 'Avete inoltrato richieste di rimodulazione delle scadenze nei pagamenti richiedendo di aumentare la dilazione?',
                '1-5' => 'Sono state modificate le condizioni di incasso da parte dei clienti con un conseguente allungamento dei tempi di incasso?',
                '1-6' => 'Sono stati registrati mancati incassi per importi considerevoli superiori a 90gg?',
                '1-7' => 'Sono presenti contenziosi in atto con clienti o fornitori?',
                '1-8' => 'Sono presenti criticità con clienti derivanti da non conformità o ritardi?',
                '2-1' => 'Sono state registrate perdite di fette di mercato, commesse o clienti importanti?',
                '2-2' => 'Si sono verificate perdite di membri della direzione o di figure di responsabilità strategiche senza una loro sostituzione?',
                '2-3' => 'Sono presenti problemi con la gestione del personale?',
                '2-4' => 'Ci sono ritardi nei pagamenti relativi alle retribuzioni superiori a 60gg?',
                '2-5' => 'Sono presenti nel mercato nuove aziende concorrenti che possono mettere in difficoltà l\'azienda?',
                '2-6' => 'Sono state richieste dilazioni alle banche su finanziamenti in essere?',
                '2-7' => 'Vi è la possibilità di incorrere in problemi inerenti l\'approvvigionamento di prodotti fondamentali?',
                '2-8' => 'Sono state registrate variazioni nell\'assetto societario?',
                '2-9' => 'Le scelte gestionali portate avanti dall\'amministratore risultano in contrasto con la mission aziendale?',
                '2-10' => 'Vi è possibilità che si verifichino eventi catastrofici per i quali non si dispone di adeguata copertura assicurativa?',
                '3-1' => 'Sono presenti mancati pagamenti verso Agenzia delle Entrate ed Enti di riscossione per oltre 6 mesi?',
                '3-2' => 'Sono presenti mancati pagamenti verso INPS e INAIL per oltre 6 mesi?',
                '3-3' => 'Vi sono procedimenti legali o regolamentari in corso che potrebbero comportare richieste di risarcimento?',
                '3-4' => 'Sono entrate in atto modifiche di leggi o regolamenti che potrebbero influenzare negativamente l\'impresa?',
                '4-1' => 'Sono presenti iscrizioni di ipoteche giudiziarie, pegni e forme tecniche di prelazioni sui beni aziendali?',
                '4-2' => 'Sono stati ricevuti decreti ingiuntivi ed atti ricognitivi di avvio di azioni per il recupero di crediti?',
                '4-3' => 'L\'azienda ha subito il protesto di assegni e cambiali?',
                '4-4' => 'Sono in atto azioni volte alla liquidazione dell\'azienda o alla cessazione dell\'attività?',
                '4-5' => 'Vi sono istanze di fallimento avanzate dai creditori aziendali?',
                '4-6' => 'Si è verificato il default o il fallimento di garanti legati all\'azienda (rischio infragruppo)?',
            ];
        @endphp

        @foreach($asIsGroups as $group)
        <div class="subsection-title">{{ $group['title'] }}</div>

        <table class="table-formal">
            <thead>
                <tr>
                    <th style="width:55%">Domanda</th>
                    <th style="width:15%">Esito</th>
                    <th style="width:30%">Spiegazione</th>
                </tr>
            </thead>
            <tbody>
                @foreach($group['keys'] as $key)
                <tr>
                    <td>{{ $asIsTexts[$key] ?? $key }}</td>
                    <td class="text-center">
                        @php
                            $result = $dati['pageData']['arrayQuestionarioAsIs'][$key]['Result'] ?? 'N/D';
                        @endphp
                        @if($result === 'Si' || $result === 'Sì')
                            <span class="badge badge-risk">{{ $result }}</span>
                        @elseif($result === 'No')
                            <span class="badge badge-ok">{{ $result }}</span>
                        @else
                            <span class="badge badge-warning">{{ $result }}</span>
                        @endif
                    </td>
                    <td style="font-size:7.5pt; color:#475569;">
                        {{ $dati['pageData']['arrayQuestionarioAsIs'][$key]['Details'] ?? '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endforeach
    </div>


    {{-- ============================================
         §6 — QUESTIONARIO TO BE (FORWARD LOOKING)
         ============================================ --}}
    <div class="page-break"></div>
    <div class="section">
        <div class="section-title">6. Questionario TO BE — Valutazione Prospettica (Forward Looking)</div>

        <p class="paragraph">
            Il questionario TO BE è finalizzato a valutare l'impresa attraverso un approccio previsionale.
            Le domande coprono aspetti relativi al fatturato previsto, alla gestione dei costi, alla
            concentrazione della clientela, alla pianificazione finanziaria e alla capacità di far fronte
            agli impegni nei prossimi sei mesi.
        </p>

        @php
            $flTexts = [
                'forwardLooking1'  => 'Per i prossimi 6 mesi l\'azienda prevede un fatturato rispetto al semestre precedente:',
                'forwardLooking2'  => 'L\'azienda prevede di chiedere nuovi finanziamenti nei prossimi 6 mesi?',
                'forwardLooking3'  => 'L\'azienda prevede costi di gestione (fissi e variabili) per i prossimi 6 mesi rispetto al semestre precedente:',
                'forwardLooking4'  => 'Su quanti clienti è concentrato il fatturato dei prossimi 6 mesi?',
                'forwardLooking5'  => 'L\'azienda utilizza un sistema di pianificazione e controllo di gestione?',
                'forwardLooking6'  => 'L\'azienda prevede costi straordinari per i prossimi 6 mesi?',
                'forwardLooking7'  => 'L\'azienda prevede di utilizzare al limite le disponibilità per liquidità di cassa nei prossimi 6 mesi?',
                'forwardLooking8'  => 'Flussi di cassa della gestione operativa sufficienti a coprire gli impegni finanziari dei prossimi 6 mesi (DSCR)?',
                'forwardLooking9'  => 'Le disponibilità di cassa attuali e le entrate dei prossimi 6 mesi copriranno tutte le uscite (margine di tesoreria positivo)?',
                'forwardLooking10' => 'L\'azienda prevede tempi medi di pagamento ai fornitori inferiori ai tempi medi di incasso dai clienti?',
                'forwardLooking11' => 'L\'azienda prevede ritardi significativi nei pagamenti verso terzi nei prossimi 6 mesi?',
                'forwardLooking12' => 'L\'azienda prevede di utilizzare al limite le disponibilità del castelletto anticipi nei prossimi 6 mesi?',
            ];
        @endphp

        <table class="table-formal">
            <thead>
                <tr>
                    <th style="width:5%">N.</th>
                    <th style="width:60%">Domanda</th>
                    <th style="width:35%">Risposta Selezionata</th>
                </tr>
            </thead>
            <tbody>
                @foreach($flTexts as $flKey => $flText)
                <tr>
                    <td class="text-center">{{ (int)str_replace('forwardLooking', '', $flKey) }}</td>
                    <td>{{ $flText }}</td>
                    <td class="text-center">
                        @php
                            $flVal = $dati['pageData']['arrayForwardLookingToBe'][$flKey] ?? null;
                            $flAnswer = ($flVal && isset($risposte[$flKey][$flVal])) ? $risposte[$flKey][$flVal] : 'N/D';
                        @endphp
                        <strong>{{ $flAnswer }}</strong>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>


    {{-- ============================================
         §7 — RIEPILOGO FINALE E CONCLUSIONI
         ============================================ --}}
    <div class="page-break"></div>
    <div class="section">
        <div class="section-title">7. Riepilogo Finale e Conclusioni</div>

        <p class="paragraph">
            La seguente tabella riassume tutti i risultati dell'analisi di allerta condotta sull'impresa.
            Lo scoring finale integra le componenti di bilancio, la valutazione della Centrale Rischi,
            i questionari qualitativi AS IS e la valutazione prospettica TO BE.
        </p>

        {{-- Scoring finale --}}
        <div class="highlight-box-score">
            <div class="score-value">{{ $dati['pageData']['FinalScore'] ?? 'N/D' }}</div>
            <div class="score-label">Scoring Finale di Allerta</div>
        </div>

        {{-- Tabella sinottica --}}
        <div class="subsection-title">Quadro Sinottico Complessivo</div>

        <table class="table-summary">
            <thead>
                <tr>
                    <th style="width:50%">Area di Valutazione</th>
                    <th style="width:50%">Giudizio</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dati['GeneralScore'] as $key => $valore)
                <tr>
                    <td><strong>{{ str_replace('_', ' ', $key) }}</strong></td>
                    <td class="text-center">
                        @php
                            $valStr = is_string($valore) ? strtolower($valore) : '';
                            $isNum = is_numeric($valore);
                        @endphp
                        @if($isNum)
                            <strong>{{ $valore }}</strong>
                        @elseif(str_contains($valStr, 'negativ') || str_contains($valStr, 'alto') || str_contains($valStr, 'critico'))
                            <span class="badge badge-risk">{{ $valore }}</span>
                        @elseif(str_contains($valStr, 'positiv') || str_contains($valStr, 'buon') || str_contains($valStr, 'basso'))
                            <span class="badge badge-ok">{{ $valore }}</span>
                        @else
                            <span class="badge badge-neutral">{{ $valore }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Dettaglio scoring --}}
        <div class="subsection-title">Dettaglio Scoring Componenti</div>

        <table class="table-summary">
            <thead>
                <tr>
                    <th style="width:50%">Componente</th>
                    <th style="width:25%">Valore</th>
                    <th style="width:25%">Scala</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Scoring Analisi Bilancio</td>
                    <td class="text-center"><strong>{{ $displayScoreBil ?? 'N/D' }}</strong></td>
                    <td class="text-center">su 10</td>
                </tr>
                <tr>
                    <td>Scoring Centrale Rischi</td>
                    <td class="text-center"><strong>{{ $displayScoreCR ?? 'N/D' }}</strong></td>
                    <td class="text-center">su 10</td>
                </tr>
                <tr>
                    <td>Scoring Finale di Allerta</td>
                    <td class="text-center"><strong>{{ $dati['pageData']['FinalScore'] ?? 'N/D' }}</strong></td>
                    <td class="text-center">Complessivo</td>
                </tr>
            </tbody>
        </table>

        {{-- Conclusioni --}}
        <div class="highlight-box" style="margin-top: 6mm;">
            <p class="paragraph-small" style="margin-bottom:0;">
                <strong>Conclusioni:</strong> La presente relazione di allerta è stata generata in data {{ date('d/m/Y') }}
                sulla base dei dati di bilancio, della Centrale dei Rischi e dei questionari qualitativi compilati.
                I risultati dell'analisi hanno carattere indicativo e si configurano come strumento di supporto
                per gli organi di controllo e l'organo amministrativo dell'impresa ai sensi del
                Codice della Crisi d'Impresa e dell'Insolvenza (D.Lgs. 14/2019).
                Si raccomanda l'adozione delle opportune misure correttive laddove i segnali di allerta
                evidenzino situazioni di potenziale squilibrio patrimoniale, economico o finanziario.
            </p>
        </div>
    </div>
    </div>{{-- /content-wrapper --}}

</body>
</html>
