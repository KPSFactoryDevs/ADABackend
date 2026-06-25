<style>
    /* ============================================
       ADA – Controllo di Gestione
       Stili PDF Condivisi — Carta Intestata
       ============================================ */

    @page {
        margin: 35mm 30mm 40mm 30mm;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 10pt;
        color: #1e293b;
        line-height: 1.6;
        padding: 0 5mm;
    }

    /* Wrapper di contenuto con padding extra per DomPDF */
    .content-wrapper {
        padding: 0 3mm;
    }

    /* ---- Header Carta Intestata ---- */
    .letterhead-header {
        position: fixed;
        top: -30mm;
        left: -5mm;
        right: -5mm;
        height: 22mm;
    }

    .letterhead-header-inner {
        display: table;
        width: 100%;
    }

    .letterhead-logo-cell {
        display: table-cell;
        vertical-align: middle;
        width: 50mm;
    }

    .letterhead-logo-cell img {
        height: 14mm;
    }

    .letterhead-text-cell {
        display: table-cell;
        vertical-align: middle;
        text-align: right;
    }

    .letterhead-title {
        font-size: 13pt;
        font-weight: bold;
        color: #2D3192;
        letter-spacing: 0.5pt;
    }

    .letterhead-subtitle {
        font-size: 7.5pt;
        color: #64748b;
        margin-top: 1mm;
    }

    .letterhead-line {
        margin-top: 2mm;
        height: 1.5pt;
        background: linear-gradient(90deg, #2D3192, #5b63ff);
        /* DomPDF fallback */
        background-color: #2D3192;
    }

    /* ---- Footer Carta Intestata ---- */
    .letterhead-footer {
        position: fixed;
        bottom: -35mm;
        left: -5mm;
        right: -5mm;
        height: 25mm;
    }

    .letterhead-footer-line {
        height: 1pt;
        background-color: #cbd5e1;
    }

    .letterhead-footer-content {
        margin-top: 2mm;
        font-size: 6.5pt;
        color: #94a3b8;
    }

    .letterhead-footer-content table {
        width: 100%;
        border: none;
    }

    .letterhead-footer-content table td {
        border: none;
        padding: 0;
        vertical-align: top;
        font-size: 6.5pt;
        color: #94a3b8;
    }

    .letterhead-footer .page-number:after {
        content: counter(page);
    }

    /* ---- Copertina ---- */
    .cover-page {
        page-break-after: always;
        text-align: center;
        padding-top: 60mm;
    }

    .cover-logo {
        margin-bottom: 15mm;
    }

    .cover-logo img {
        height: 30mm;
    }

    .cover-title {
        font-size: 22pt;
        font-weight: bold;
        color: #2D3192;
        margin-bottom: 5mm;
        letter-spacing: 1pt;
    }

    .cover-subtitle {
        font-size: 14pt;
        color: #334155;
        margin-bottom: 3mm;
    }

    .cover-company {
        font-size: 16pt;
        font-weight: bold;
        color: #1e293b;
        margin-top: 10mm;
        margin-bottom: 10mm;
    }

    .cover-line {
        width: 80mm;
        height: 2pt;
        background-color: #2D3192;
        margin: 8mm auto;
    }

    .cover-meta {
        font-size: 9pt;
        color: #64748b;
        margin-top: 15mm;
        line-height: 2;
    }

    .cover-meta strong {
        color: #334155;
    }

    /* ---- Sezioni ---- */
    .section {
        margin-bottom: 8mm;
    }

    .section-title {
        font-size: 13pt;
        font-weight: bold;
        color: #2D3192;
        padding-bottom: 2mm;
        border-bottom: 2pt solid #2D3192;
        margin-bottom: 4mm;
    }

    .section-title-numbered {
        font-size: 13pt;
        font-weight: bold;
        color: #2D3192;
        padding-bottom: 2mm;
        border-bottom: 2pt solid #2D3192;
        margin-bottom: 4mm;
    }

    .subsection-title {
        font-size: 11pt;
        font-weight: bold;
        color: #334155;
        margin-top: 5mm;
        margin-bottom: 3mm;
        padding-left: 3mm;
        border-left: 3pt solid #5b63ff;
    }

    /* ---- Paragrafi ---- */
    .paragraph {
        font-size: 9.5pt;
        line-height: 1.7;
        color: #334155;
        margin-bottom: 4mm;
        text-align: justify;
    }

    .paragraph-small {
        font-size: 8.5pt;
        line-height: 1.6;
        color: #475569;
        margin-bottom: 3mm;
        text-align: justify;
    }

    /* ---- Tabelle Formali ---- */
    table.table-formal {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 5mm;
        font-size: 9pt;
    }

    table.table-formal th {
        background-color: #2D3192;
        color: #ffffff;
        font-weight: bold;
        font-size: 8.5pt;
        padding: 3mm 3mm;
        text-align: left;
        border: 0.5pt solid #2D3192;
    }

    table.table-formal td {
        padding: 2.5mm 3mm;
        border: 0.5pt solid #cbd5e1;
        font-size: 8.5pt;
        vertical-align: middle;
    }

    table.table-formal tr:nth-child(even) td {
        background-color: #f8fafc;
    }

    table.table-formal tr:hover td {
        background-color: #f1f5f9;
    }

    /* Tabella Riepilogativa (header chiaro) */
    table.table-summary {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 5mm;
        font-size: 9pt;
    }

    table.table-summary th {
        background-color: #f1f5f9;
        color: #334155;
        font-weight: bold;
        font-size: 8.5pt;
        padding: 2.5mm 3mm;
        text-align: left;
        border: 0.5pt solid #e2e8f0;
    }

    table.table-summary td {
        padding: 2.5mm 3mm;
        border: 0.5pt solid #e2e8f0;
        font-size: 8.5pt;
        vertical-align: middle;
    }

    table.table-summary tr:nth-child(even) td {
        background-color: #f8fafc;
    }

    /* Tabella Dati Impresa */
    table.table-company {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 5mm;
        font-size: 9pt;
    }

    table.table-company th {
        background-color: #2D3192;
        color: #ffffff;
        font-weight: bold;
        font-size: 9pt;
        padding: 3mm 4mm;
        text-align: center;
        border: 0.5pt solid #2D3192;
    }

    table.table-company td.label-cell {
        background-color: #f1f5f9;
        font-weight: bold;
        font-size: 8.5pt;
        color: #334155;
        padding: 2.5mm 4mm;
        width: 40%;
        border: 0.5pt solid #e2e8f0;
    }

    table.table-company td.value-cell {
        font-size: 8.5pt;
        color: #1e293b;
        padding: 2.5mm 4mm;
        border: 0.5pt solid #e2e8f0;
    }

    /* ---- Badge / Indicatori di Stato ---- */
    .badge {
        display: inline-block;
        padding: 1mm 3mm;
        border-radius: 2mm;
        font-size: 7.5pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.3pt;
    }

    .badge-ok {
        background-color: #dcfce7;
        color: #166534;
        border: 0.5pt solid #bbf7d0;
    }

    .badge-risk {
        background-color: #fef2f2;
        color: #991b1b;
        border: 0.5pt solid #fecaca;
    }

    .badge-warning {
        background-color: #fffbeb;
        color: #92400e;
        border: 0.5pt solid #fde68a;
    }

    .badge-neutral {
        background-color: #f1f5f9;
        color: #475569;
        border: 0.5pt solid #e2e8f0;
    }

    /* ---- Highlight Box (per scoring o dati importanti) ---- */
    .highlight-box {
        border: 1pt solid #e2e8f0;
        border-left: 4pt solid #2D3192;
        background-color: #f8fafc;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
    }

    .highlight-box-score {
        border: 1pt solid #e2e8f0;
        border-left: 4pt solid #2D3192;
        background-color: #eef2ff;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
        text-align: center;
    }

    .highlight-box-score .score-value {
        font-size: 28pt;
        font-weight: bold;
        color: #2D3192;
    }

    .highlight-box-score .score-label {
        font-size: 9pt;
        color: #64748b;
        margin-top: 1mm;
    }

    .highlight-box-warning {
        border: 1pt solid #fecaca;
        border-left: 4pt solid #dc2626;
        background-color: #fef2f2;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
    }

    .highlight-box-success {
        border: 1pt solid #bbf7d0;
        border-left: 4pt solid #16a34a;
        background-color: #f0fdf4;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
    }

    /* ---- Utility ---- */
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-left { text-align: left; }
    .font-bold { font-weight: bold; }
    .text-muted { color: #94a3b8; }
    .text-primary { color: #2D3192; }
    .text-danger { color: #dc2626; }
    .text-success { color: #16a34a; }
    .mt-2 { margin-top: 2mm; }
    .mt-4 { margin-top: 4mm; }
    .mt-6 { margin-top: 6mm; }
    .mb-2 { margin-bottom: 2mm; }
    .mb-4 { margin-bottom: 4mm; }
    .page-break { page-break-before: always; }

    /* ---- Esito Si/No con colore ---- */
    .esito-si {
        background-color: #fef2f2;
        color: #991b1b;
        font-weight: bold;
        padding: 1mm 3mm;
    }

    .esito-no {
        background-color: #f0fdf4;
        color: #166534;
        font-weight: bold;
        padding: 1mm 3mm;
    }

    .esito-na {
        background-color: #fffbeb;
        color: #92400e;
        font-weight: bold;
        padding: 1mm 3mm;
    }
</style>
