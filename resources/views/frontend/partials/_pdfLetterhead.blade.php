{{-- Carta Intestata ADA — Header e Footer fissi su ogni pagina --}}

{{-- HEADER --}}
<div class="letterhead-header">
    <div class="letterhead-header-inner">
        <div class="letterhead-logo-cell">
            <img src="{{ public_path('images/ada_logo.png') }}" alt="ADA Logo">
        </div>
        <div class="letterhead-text-cell">
            <div class="letterhead-title">ADA — Controllo di Gestione</div>
            <div class="letterhead-subtitle">Software di Analisi Finanziaria e Monitoraggio Aziendale</div>
        </div>
    </div>
    <div class="letterhead-line"></div>
</div>

{{-- FOOTER --}}
<div class="letterhead-footer">
    <div class="letterhead-footer-line"></div>
    <div class="letterhead-footer-content">
        <table>
            <tr>
                <td style="width:50%; text-align:left;">
                    Documento generato automaticamente dal software ADA — Controllo di Gestione<br>
                    <em>Documento riservato e confidenziale</em>
                </td>
                <td style="width:25%; text-align:center;">
                    Data emissione: {{ date('d/m/Y H:i') }}
                </td>
                <td style="width:25%; text-align:right;">
                    Pagina <span class="page-number"></span>
                </td>
            </tr>
        </table>
    </div>
</div>
