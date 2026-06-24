<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #1e293b; margin: 0; padding: 0; background: #f1f5f9; }
        .container { max-width: 640px; margin: 32px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #5b63ff 0%, #4338ca 100%); padding: 32px; color: #fff; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
        .header p { margin: 8px 0 0; font-size: 14px; opacity: 0.9; }
        .body { padding: 32px; }
        .section { margin-bottom: 24px; }
        .section-title { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 12px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .info-item { }
        .info-label { font-size: 12px; color: #94a3b8; margin-bottom: 2px; }
        .info-value { font-size: 14px; font-weight: 500; }
        .score-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        .score-green { background: #dcfce7; color: #166534; }
        .score-red { background: #fee2e2; color: #991b1b; }
        .score-gray { background: #f1f5f9; color: #64748b; }
        .notes-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; font-size: 14px; color: #334155; }
        .footer { padding: 24px 32px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center; font-size: 12px; color: #94a3b8; }
        .docs-list { list-style: none; padding: 0; margin: 0; }
        .docs-list li { padding: 8px 12px; background: #f8fafc; border-radius: 6px; margin-bottom: 6px; font-size: 13px; display: flex; align-items: center; gap: 8px; }
        .docs-list li::before { content: '📎'; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Richiesta Valutazione Cliente</h1>
            <p>{{ $companyName }} richiede la valutazione del seguente cliente per la cessione del credito.</p>
        </div>

        <div class="body">
            <!-- Anagrafica -->
            <div class="section">
                <div class="section-title">Anagrafica Cliente</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Ragione Sociale</div>
                        <div class="info-value">{{ $client->nome }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Partita IVA</div>
                        <div class="info-value">{{ $client->piva ?: $client->vat_number ?: '—' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Regione</div>
                        <div class="info-value">{{ $client->regione ?: '—' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Città</div>
                        <div class="info-value">{{ $client->citta ?: '—' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value">{{ $client->email ?: '—' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Fatturato</div>
                        <div class="info-value">{{ $client->fatturato ? number_format($client->fatturato, 2, ',', '.') . ' €' : '—' }}</div>
                    </div>
                </div>
            </div>

            <!-- Score -->
            <div class="section">
                <div class="section-title">Score Documenti</div>
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    @if($client->has_invoices)
                        <span class="score-badge score-green">📄 Fatture ✓</span>
                    @else
                        <span class="score-badge score-gray">📄 Fatture —</span>
                    @endif

                    @if($client->has_bilancio)
                        @if($client->bilancio_score >= 8)
                            <span class="score-badge score-green">📊 Bilancio {{ $client->bilancio_score }}/10</span>
                        @else
                            <span class="score-badge score-red">📊 Bilancio {{ $client->bilancio_score }}/10</span>
                        @endif
                    @else
                        <span class="score-badge score-gray">📊 Bilancio —</span>
                    @endif

                    @if($client->has_cr)
                        @if($client->cr_score >= 8)
                            <span class="score-badge score-green">📈 CR {{ $client->cr_score }}/10</span>
                        @else
                            <span class="score-badge score-red">📈 CR {{ $client->cr_score }}/10</span>
                        @endif
                    @else
                        <span class="score-badge score-gray">📈 CR —</span>
                    @endif
                </div>
            </div>

            <!-- Documenti allegati -->
            @if($client->documents && $client->documents->count() > 0)
            <div class="section">
                <div class="section-title">Documenti Allegati</div>
                <ul class="docs-list">
                    @foreach($client->documents as $doc)
                        <li>{{ $doc->original_filename ?: $doc->filename }} <span style="color: #94a3b8; margin-left: auto;">{{ ucfirst(str_replace('_', ' ', $doc->type)) }}</span></li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Note -->
            @if($notes)
            <div class="section">
                <div class="section-title">Note Aggiuntive</div>
                <div class="notes-box">{{ $notes }}</div>
            </div>
            @endif
        </div>

        <div class="footer">
            Inviato da ADA CRM · KPS Factory &middot; {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>
