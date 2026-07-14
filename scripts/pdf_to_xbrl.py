#!/usr/bin/env python3
"""
pdf_to_xbrl.py – Converts a PDF bilancio (bilancio di verifica) into XBRL format
compatible with the Italian XBRL taxonomy (itcc-ci-*-2018-11-04).

Usage:
    python3 scripts/pdf_to_xbrl.py <pdf_path> <output_dir> [--openai-key KEY] [--model MODEL]

Outputs JSON to stdout:
    {"success": true, "xbrl_path": "/path/to/generated.xbrl", "taxonomy": "itcc-ci-abb-2018-11-04.xsd", "company_name": "...", "year": "2022"}
"""

import argparse
import json
import os
import re
import subprocess
import sys
import time
from datetime import datetime

# ---------------------------------------------------------------------------
# 1. PDF TEXT EXTRACTION
# ---------------------------------------------------------------------------

def extract_text_from_pdf(pdf_path: str) -> str:
    """Extract text from PDF using pdftotext with layout preservation."""
    try:
        result = subprocess.run(
            ['pdftotext', '-layout', pdf_path, '-'],
            capture_output=True, text=True, timeout=30
        )
        if result.returncode == 0 and result.stdout.strip():
            return result.stdout
    except (FileNotFoundError, subprocess.TimeoutExpired):
        pass

    raise RuntimeError(f"Cannot extract text from PDF: {pdf_path}")


# ---------------------------------------------------------------------------
# 2. PARSING – Extract accounts from the trial balance text
# ---------------------------------------------------------------------------

def parse_trial_balance(text: str) -> dict:
    """
    Parse the trial balance text and extract:
    - metadata (company name, year)
    - accounts with their amounts organized by section
    """
    lines = text.split('\n')

    # Extract metadata
    company_name = ""
    year = ""
    for line in lines[:10]:
        line_stripped = line.strip()
        # Company name is usually on the first line, right-aligned
        if 'bilancio di verifica' in line_stripped.lower():
            # Company name is typically at the end of this line
            parts = line_stripped.split('  ')
            parts = [p.strip() for p in parts if p.strip()]
            if len(parts) >= 2:
                company_name = parts[-1]
        if 'es. di competenza' in line_stripped.lower():
            match = re.search(r'(\d{4})', line_stripped)
            if match:
                year = match.group(1)

    # Parse account entries
    # Each section has: Mastro, Conto, Descrizione, Importo
    accounts = {
        'attivita': [],
        'passivita': [],
        'costi': [],
        'ricavi': [],
        'totali': {}
    }

    current_section = None
    # Determine section by page headers
    page_sections = []

    for i, line in enumerate(lines):
        lower = line.lower().strip()

        # Detect section headers
        if 'attività' in lower and 'passività' in lower:
            current_section = 'attivita_passivita'
        elif 'costi' in lower and 'ricavi' in lower and ('mastri' in lower or i > 0):
            current_section = 'costi_ricavi'

        # Extract totals
        if 'totale attivi' in lower:
            match = re.search(r'([\d.,]+)\s*$', line.strip())
            if match:
                accounts['totali']['totale_attivita'] = _parse_amount(match.group(1))
        if 'totale passivi' in lower:
            match = re.search(r'([\d.,]+)\s*$', line.strip())
            if match:
                accounts['totali']['totale_passivita'] = _parse_amount(match.group(1))
        if 'totale costi' in lower:
            match = re.search(r'([\d.,]+)\s*$', line.strip())
            if match:
                accounts['totali']['totale_costi'] = _parse_amount(match.group(1))
        if 'totale ricavi' in lower:
            match = re.search(r'([\d.,]+)\s*$', line.strip())
            if match:
                accounts['totali']['totale_ricavi'] = _parse_amount(match.group(1))
        if 'differenza esercizio' in lower:
            match = re.search(r'([\d.,]+)\s*$', line.strip())
            if match:
                accounts['totali']['differenza_esercizio'] = _parse_amount(match.group(1))

        # Parse account lines - they have amounts with Italian number format
        if current_section and line.strip():
            _parse_account_line(line, current_section, accounts)

    return {
        'company_name': company_name,
        'year': year,
        'accounts': accounts,
    }


def _parse_amount(amount_str: str) -> float:
    """Parse Italian formatted number (1.234,56) to float."""
    if not amount_str:
        return 0.0
    # Remove spaces
    amount_str = amount_str.strip()
    # Italian format: dots as thousands separator, comma as decimal
    amount_str = amount_str.replace('.', '').replace(',', '.')
    try:
        return float(amount_str)
    except ValueError:
        return 0.0


def _parse_account_line(line: str, section: str, accounts: dict):
    """
    Parse a single line from the trial balance.
    The PDF layout has two columns:
    - Left side: Attività or Costi
    - Right side: Passività or Ricavi

    Lines with amounts look like:
    "    CASSA                         CASSA                    207,07           BPER ANT          BPER c/antic...  27.026,45"
    or mastro summary lines (all caps with indentation):
    " BANCHE                        BANCHE                   104.298,87"
    """
    if not line.strip():
        return
    # Skip header/footer lines
    if any(skip in line.lower() for skip in [
        'mastri', 'conto', 'descrizione', 'importi in euro',
        'elaborato il', 'bilancio di verifica pag', 'selezioni',
        'tipo bilancio', 'escludi', 'compresi', 'stato:', 'confermati',
        'es. di competenza', 'riepilogo', 'totale a pareggio',
        'diff. eser', 'differenza'
    ]):
        return

    # Find amounts in the line (Italian format: digits with dots and comma)
    amount_pattern = r'([\d]{1,3}(?:\.[\d]{3})*,[\d]{2})'
    amounts_with_pos = [(m.group(1), m.start()) for m in re.finditer(amount_pattern, line)]

    if not amounts_with_pos:
        return

    # Determine column split point (approximately middle of typical 200-char line)
    line_len = len(line)
    mid_point = line_len // 2

    for amount_str, pos in amounts_with_pos:
        amount = _parse_amount(amount_str)
        if amount == 0:
            continue

        # Extract description: text before the amount
        # Find the text segment before this amount
        preceding_text = line[:pos].strip()

        # Get the last meaningful text segment (description)
        # For right-column entries, we need text after the mid-point
        text_parts = preceding_text.split('  ')
        text_parts = [p.strip() for p in text_parts if p.strip()]

        if not text_parts:
            continue

        description = text_parts[-1] if text_parts else ""
        # Try to get the mastro (first part) and account name
        mastro = text_parts[0] if len(text_parts) > 1 else ""

        is_mastro_summary = (
            description == description.upper() and
            len(description) > 2 and
            not any(c.isdigit() for c in description)
        )

        entry = {
            'mastro': mastro,
            'description': description,
            'amount': amount,
            'is_summary': is_mastro_summary,
            'raw_line': line.strip()[:120],
        }

        if section == 'attivita_passivita':
            if pos < mid_point:
                accounts['attivita'].append(entry)
            else:
                accounts['passivita'].append(entry)
        elif section == 'costi_ricavi':
            if pos < mid_point:
                accounts['costi'].append(entry)
            else:
                accounts['ricavi'].append(entry)


# ---------------------------------------------------------------------------
# 3. LLM MAPPING – Map accounts to XBRL taxonomy via OpenAI
# ---------------------------------------------------------------------------

# The XBRL element names used by the system for financial analysis
XBRL_ELEMENTS = {
    # Stato Patrimoniale - Attivo
    "TotaleCreditiVersoSociVersamentiAncoraDovuti": "Crediti verso soci per versamenti ancora dovuti",
    "TotaleImmobilizzazioni": "Totale immobilizzazioni",
    "TotaleRimanenze": "Totale rimanenze",
    "TotaleCrediti": "Totale crediti",
    "CreditiEsigibiliEntroEsercizioSuccessivo": "Crediti esigibili entro esercizio successivo",
    "CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo": "Crediti verso clienti entro esercizio",
    "CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo": "Crediti verso imprese controllate entro esercizio",
    "CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo": "Crediti verso imprese collegate entro esercizio",
    "CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo": "Crediti verso controllanti entro esercizio",
    "CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo": "Crediti tributari entro esercizio",
    "CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo": "Crediti verso altri entro esercizio",
    "CreditiImposteAnticipateTotaleImposteAnticipate": "Crediti imposte anticipate",
    "TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni": "Totale attività finanziarie non immobilizzate",
    "TotaleDisponibilitaLiquide": "Totale disponibilità liquide",
    "AttivoRateiRisconti": "Ratei e risconti attivi",
    "TotaleAttivo": "Totale attivo",

    # Stato Patrimoniale - Passivo
    "TotalePatrimonioNetto": "Totale patrimonio netto",
    "FondiRischiOneriTrattamentoQuiescenzaObblighiSimili": "Fondi rischi e oneri",
    "TrattamentoFineRapportoLavoroSubordinato": "TFR",
    "TotaleDebiti": "Totale debiti",
    "DebitiEsigibiliEntroEsercizioSuccessivo": "Debiti entro esercizio successivo",
    "DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo": "Debiti obbligazioni entro esercizio",
    "DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo": "Debiti obbligazioni convertibili entro esercizio",
    "DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo": "Debiti verso soci finanziamenti entro esercizio",
    "DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo": "Debiti verso banche entro esercizio",
    "DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo": "Debiti verso altri finanziatori entro esercizio",
    "DebitiAccontiEsigibiliEntroEsercizioSuccessivo": "Debiti acconti entro esercizio",
    "DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo": "Debiti verso fornitori entro esercizio",
    "DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo": "Debiti rappresentati da titoli di credito entro esercizio",
    "DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo": "Debiti verso imprese controllate entro esercizio",
    "DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo": "Debiti verso imprese collegate entro esercizio",
    "DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo": "Debiti verso controllanti entro esercizio",
    "DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo": "Debiti tributari entro esercizio",
    "DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo": "Debiti verso istituti previdenza entro esercizio",
    "DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo": "Altri debiti entro esercizio",
    "DebitiDebitiTributariTotaleDebitiTributari": "Totale debiti tributari",
    "DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale": "Totale debiti verso istituti previdenza",
    "PassivoRateiRisconti": "Ratei e risconti passivi",

    # Debiti oltre esercizio successivo
    "DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo": "Debiti obbligazioni oltre esercizio",
    "DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo": "Debiti obbligazioni convertibili oltre esercizio",
    "DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo": "Debiti verso soci finanziamenti oltre esercizio",
    "DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo": "Debiti verso banche oltre esercizio",
    "DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo": "Debiti verso altri finanziatori oltre esercizio",
    "DebitiAccontiEsigibiliOltreEsercizioSuccessivo": "Debiti acconti oltre esercizio",
    "DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo": "Debiti verso fornitori oltre esercizio",
    "DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo": "Debiti rappresentati da titoli di credito oltre esercizio",
    "DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo": "Debiti verso imprese controllate oltre esercizio",
    "DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo": "Debiti verso imprese collegate oltre esercizio",
    "DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo": "Debiti verso controllanti oltre esercizio",
    "DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo": "Debiti tributari oltre esercizio",
    "DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo": "Debiti verso istituti previdenza oltre esercizio",
    "DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo": "Altri debiti oltre esercizio",

    # Conto Economico
    "ValoreProduzioneRicaviVenditePrestazioni": "Ricavi delle vendite e prestazioni",
    "TotaleValoreProduzione": "Totale valore della produzione",
    "CostiProduzioneMateriePrimeSussidiarieConsumoMerci": "Costi materie prime, sussidiarie, merci",
    "CostiProduzioneServizi": "Costi per servizi",
    "CostiProduzioneGodimentoBeniTerzi": "Costi godimento beni di terzi",
    "CostiProduzionePersonaleTotaleCostiPersonale": "Totale costi del personale",
    "CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni": "Totale ammortamenti e svalutazioni",
    "CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci": "Variazioni rimanenze",
    "CostiProduzioneAccantonamentiRischi": "Accantonamenti per rischi",
    "CostiProduzioneAltriAccantonamenti": "Altri accantonamenti",
    "CostiProduzioneOneriDiversiGestione": "Oneri diversi di gestione",
    "DifferenzaValoreCostiProduzione": "Differenza valore e costi produzione",
    "ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari": "Totale interessi e altri oneri finanziari",
    "ImmobilizzazioniFinanziarieCreditiTotaleCrediti": "Crediti immobilizzazioni finanziarie",
    "ImposteRedditoEsercizioCorrentiDifferiteAnticipateImposteDifferiteAnticipate": "Imposte differite e anticipate",
    "ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate": "Totale imposte",
    "UtilePerditaEsercizio": "Utile (perdita) dell'esercizio",

    # Dati anagrafici
    "DatiAnagraficiDenominazione": "Denominazione azienda",
    "DatiAnagraficiCodiceFiscale": "Codice fiscale",
    "DatiAnagraficiSedeLegaleIndirizzo": "Sede legale indirizzo",
    "DatiAnagraficiSedeLegaleCap": "Sede legale CAP",
    "DatiAnagraficiSedeLegaleComune": "Sede legale comune",
    "DatiAnagraficiSedeLegaleProvincia": "Sede legale provincia",
}


def map_accounts_to_xbrl_via_llm(parsed_data: dict, api_key: str, model: str = "gpt-4o-mini") -> dict:
    """
    Use OpenAI to map trial balance accounts to XBRL taxonomy elements.
    Returns a dict of {xbrl_element_name: value}.
    """
    import urllib.request

    accounts = parsed_data['accounts']

    # Build a summary of all accounts with their amounts
    accounts_text = "## ATTIVITÀ (Stato Patrimoniale - Attivo)\n"
    for acc in accounts.get('attivita', []):
        summary_flag = " [TOTALE MASTRO]" if acc['is_summary'] else ""
        accounts_text += f"- {acc['description']}: €{acc['amount']:,.2f}{summary_flag}\n"

    accounts_text += "\n## PASSIVITÀ (Stato Patrimoniale - Passivo)\n"
    for acc in accounts.get('passivita', []):
        summary_flag = " [TOTALE MASTRO]" if acc['is_summary'] else ""
        accounts_text += f"- {acc['description']}: €{acc['amount']:,.2f}{summary_flag}\n"

    accounts_text += "\n## COSTI (Conto Economico)\n"
    for acc in accounts.get('costi', []):
        summary_flag = " [TOTALE MASTRO]" if acc['is_summary'] else ""
        accounts_text += f"- {acc['description']}: €{acc['amount']:,.2f}{summary_flag}\n"

    accounts_text += "\n## RICAVI (Conto Economico)\n"
    for acc in accounts.get('ricavi', []):
        summary_flag = " [TOTALE MASTRO]" if acc['is_summary'] else ""
        accounts_text += f"- {acc['description']}: €{acc['amount']:,.2f}{summary_flag}\n"

    if accounts.get('totali'):
        accounts_text += "\n## TOTALI RIEPILOGO\n"
        for k, v in accounts['totali'].items():
            accounts_text += f"- {k}: €{v:,.2f}\n"

    # Build the XBRL elements reference
    elements_reference = "\n".join([
        f'- "{k}": {v}'
        for k, v in XBRL_ELEMENTS.items()
    ])

    prompt = f"""Sei un esperto contabile italiano. Ti viene fornito un bilancio di verifica a sezioni contrapposte estratto da un PDF.
Devi mappare i conti del bilancio di verifica alle voci della tassonomia XBRL italiana (ITCC-CI).

IMPORTANTE:
- Un bilancio di verifica mostra i saldi dei singoli conti contabili. Devi AGGREGARLI nelle voci XBRL della tassonomia.
- Le voci XBRL rappresentano le macro-categorie del bilancio civilistico (art. 2424 e 2425 del Codice Civile).
- Usa il tuo giudizio professionale per aggregare i conti nelle voci corrette.
- Se un conto non è mappabile a nessuna voce XBRL, ignoralo.
- I valori devono essere in Euro, arrotondati all'unità.
- Per le voci di totale (TotaleAttivo, TotalePassivo, etc), sommale correttamente o usa i totali forniti.
- Tutte le voci di debito sono considerate "entro esercizio successivo" salvo indicazione contraria.

DATI DEL BILANCIO DI VERIFICA:
Azienda: {parsed_data['company_name']}
Anno: {parsed_data['year']}

{accounts_text}

VOCI XBRL DISPONIBILI (nome_elemento: descrizione):
{elements_reference}

DEDUCI ANCHE il tipo di bilancio:
- Se l'azienda ha un attivo totale <= 4.400.000€ e ricavi <= 8.800.000€ e dipendenti medi <= 50: "micro" -> taxonomy "itcc-ci-micr-2018-11-04.xsd"
- Se l'azienda ha un attivo totale <= 20.000.000€ e ricavi <= 40.000.000€ e dipendenti medi <= 250: "abbreviato" -> taxonomy "itcc-ci-abb-2018-11-04.xsd"
- Altrimenti: "ordinario" -> taxonomy "itcc-ci-ese-2018-11-04.xsd"
In caso di dubbio, usa "abbreviato".

Rispondi ESCLUSIVAMENTE con un JSON valido (senza markdown, senza commenti), con questa struttura:
{{
  "taxonomy_type": "abbreviato|ordinario|micro",
  "taxonomy_file": "itcc-ci-abb-2018-11-04.xsd",
  "mappings": {{
    "NomeElementoXBRL": valore_numerico_intero,
    ...
  }}
}}

Includi SOLO le voci per cui hai un valore. Non includere voci con valore 0 a meno che non siano significative (come UtilePerditaEsercizio che può essere 0).
Per DatiAnagraficiDenominazione usa il nome dell'azienda come stringa.
"""

    # Call OpenAI API
    headers = {
        "Content-Type": "application/json",
        "Authorization": f"Bearer {api_key}",
    }

    body = json.dumps({
        "model": model,
        "messages": [
            {"role": "system", "content": "Sei un esperto contabile italiano specializzato nella tassonomia XBRL ITCC-CI per bilanci depositati presso la Camera di Commercio. Rispondi sempre e solo con JSON valido."},
            {"role": "user", "content": prompt}
        ],
        "temperature": 0.1,
        "max_tokens": 4000,
    }).encode('utf-8')

    req = urllib.request.Request(
        "https://api.openai.com/v1/chat/completions",
        data=body,
        headers=headers,
        method="POST"
    )

    try:
        with urllib.request.urlopen(req, timeout=120) as resp:
            response_data = json.loads(resp.read().decode('utf-8'))
    except Exception as e:
        raise RuntimeError(f"OpenAI API call failed: {e}")

    # Parse response
    content = response_data['choices'][0]['message']['content'].strip()

    # Clean possible markdown fences
    if content.startswith('```'):
        content = re.sub(r'^```(?:json)?\s*', '', content)
        content = re.sub(r'\s*```$', '', content)

    try:
        result = json.loads(content)
    except json.JSONDecodeError as e:
        raise RuntimeError(f"Failed to parse LLM response as JSON: {e}\nResponse: {content[:500]}")

    return result


# ---------------------------------------------------------------------------
# 4. XBRL GENERATION – Create a valid XBRL instance document
# ---------------------------------------------------------------------------

def generate_xbrl(mapping_result: dict, company_name: str, year: str, output_path: str) -> str:
    """
    Generate a valid XBRL instance document from the mapped data.
    """
    taxonomy_file = mapping_result.get('taxonomy_file', 'itcc-ci-abb-2018-11-04.xsd')
    mappings = mapping_result.get('mappings', {})

    # Determine namespace prefix based on taxonomy type
    taxonomy_type = mapping_result.get('taxonomy_type', 'abbreviato')
    if taxonomy_type == 'micro':
        ns_prefix = 'itcc-ci-micr'
        ns_uri = 'http://www.infocamere.it/itnn/fr/itcc/ci/micr/2018-11-04'
    elif taxonomy_type == 'ordinario':
        ns_prefix = 'itcc-ci-ese'
        ns_uri = 'http://www.infocamere.it/itnn/fr/itcc/ci/ese/2018-11-04'
    else:  # abbreviato
        ns_prefix = 'itcc-ci-abb'
        ns_uri = 'http://www.infocamere.it/itnn/fr/itcc/ci/abb/2018-11-04'

    # Also need the main itcc-ci namespace for elements
    main_ns = 'http://www.infocamere.it/itnn/fr/itcc/ci/2018-11-04'

    year_int = int(year) if year else datetime.now().year
    period_start = f"{year_int}-01-01"
    period_end = f"{year_int}-12-31"
    prev_period_end = f"{year_int - 1}-12-31"
    prev_period_start = f"{year_int - 1}-01-01"

    # Build XBRL document
    lines = []
    lines.append('<?xml version="1.0" encoding="utf-8"?>')
    lines.append(f'<!--Generated from PDF by ADA CRM - {datetime.now().isoformat()}-->')
    lines.append(
        f'<xbrli:xbrl '
        f'xmlns="http://www.xbrl.org/2003/instance" '
        f'xmlns:{ns_prefix}="{ns_uri}" '
        f'xmlns:link="http://www.xbrl.org/2003/linkbase" '
        f'xmlns:itcc-ci="{main_ns}" '
        f'xmlns:iso4217="http://www.xbrl.org/2003/iso4217" '
        f'xmlns:xlink="http://www.w3.org/1999/xlink" '
        f'xmlns:xbrli="http://www.xbrl.org/2003/instance">'
    )

    # Schema reference
    lines.append(f'  <link:schemaRef xlink:type="simple" '
                 f'xlink:arcrole="http://www.w3.org/1999/xlink/properties/linkbase" '
                 f'xlink:href="{taxonomy_file}"/>')

    # Contexts
    # c1_i - current instant (balance sheet date)
    lines.append('  <xbrli:context id="c1_i">')
    lines.append('    <xbrli:entity>')
    lines.append('      <xbrli:identifier scheme="http://www.infocamere.it">00000000000</xbrli:identifier>')
    lines.append('    </xbrli:entity>')
    lines.append('    <xbrli:period>')
    lines.append(f'      <xbrli:instant>{period_end}</xbrli:instant>')
    lines.append('    </xbrli:period>')
    lines.append('  </xbrli:context>')

    # c1_d - current duration (income statement period)
    lines.append('  <xbrli:context id="c1_d">')
    lines.append('    <xbrli:entity>')
    lines.append('      <xbrli:identifier scheme="http://www.infocamere.it">00000000000</xbrli:identifier>')
    lines.append('    </xbrli:entity>')
    lines.append('    <xbrli:period>')
    lines.append(f'      <xbrli:startDate>{period_start}</xbrli:startDate>')
    lines.append(f'      <xbrli:endDate>{period_end}</xbrli:endDate>')
    lines.append('    </xbrli:period>')
    lines.append('  </xbrli:context>')

    # c0_i - previous instant
    lines.append('  <xbrli:context id="c0_i">')
    lines.append('    <xbrli:entity>')
    lines.append('      <xbrli:identifier scheme="http://www.infocamere.it">00000000000</xbrli:identifier>')
    lines.append('    </xbrli:entity>')
    lines.append('    <xbrli:period>')
    lines.append(f'      <xbrli:instant>{prev_period_end}</xbrli:instant>')
    lines.append('    </xbrli:period>')
    lines.append('  </xbrli:context>')

    # c0_d - previous duration
    lines.append('  <xbrli:context id="c0_d">')
    lines.append('    <xbrli:entity>')
    lines.append('      <xbrli:identifier scheme="http://www.infocamere.it">00000000000</xbrli:identifier>')
    lines.append('    </xbrli:entity>')
    lines.append('    <xbrli:period>')
    lines.append(f'      <xbrli:startDate>{prev_period_start}</xbrli:startDate>')
    lines.append(f'      <xbrli:endDate>{prev_period_end}</xbrli:endDate>')
    lines.append('    </xbrli:period>')
    lines.append('  </xbrli:context>')

    # Units
    lines.append('  <xbrli:unit id="EUR">')
    lines.append('    <xbrli:measure>iso4217:EUR</xbrli:measure>')
    lines.append('  </xbrli:unit>')
    lines.append('  <xbrli:unit id="shares">')
    lines.append('    <xbrli:measure>xbrli:shares</xbrli:measure>')
    lines.append('  </xbrli:unit>')
    lines.append('  <xbrli:unit id="pure">')
    lines.append('    <xbrli:measure>xbrli:pure</xbrli:measure>')
    lines.append('  </xbrli:unit>')

    # Elements from mappings
    # Determine which context to use: balance sheet items use instant (c1_i),
    # income statement items use duration (c1_d)
    INCOME_STATEMENT_PREFIXES = [
        'ValoreProduzione', 'TotaleValoreProduzione', 'CostiProduzione',
        'DifferenzaValoreCosti', 'ProventiOneriFinanziari', 'Imposte',
        'UtilePerdita', 'RettificheValoreAttivitaPassivitaFinanziarie',
        'ProventiFruttiferiCapitale', 'RivalutazioniAttivitaFinanziarie',
        'SvalutazioniAttivitaFinanziarie',
    ]

    STRING_ELEMENTS = [
        'DatiAnagraficiDenominazione', 'DatiAnagraficiCodiceFiscale',
        'DatiAnagraficiSedeLegaleIndirizzo', 'DatiAnagraficiSedeLegaleCap',
        'DatiAnagraficiSedeLegaleComune', 'DatiAnagraficiSedeLegaleProvincia',
    ]

    for element_name, value in mappings.items():
        # Skip null/empty values
        if value is None:
            continue

        # Determine context
        is_income = any(element_name.startswith(prefix) for prefix in INCOME_STATEMENT_PREFIXES)
        context_ref = "c1_d" if is_income else "c1_i"

        if element_name in STRING_ELEMENTS:
            # String element (no unit)
            escaped_value = str(value).replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;')
            lines.append(f'  <itcc-ci:{element_name} contextRef="{context_ref}">{escaped_value}</itcc-ci:{element_name}>')
        else:
            # Monetary element
            try:
                int_value = int(round(float(value)))
            except (ValueError, TypeError):
                continue
            lines.append(f'  <itcc-ci:{element_name} contextRef="{context_ref}" unitRef="EUR" decimals="0">{int_value}</itcc-ci:{element_name}>')

    lines.append('</xbrli:xbrl>')

    # Write to file
    xbrl_content = '\n'.join(lines)
    with open(output_path, 'w', encoding='utf-8') as f:
        f.write(xbrl_content)

    return output_path


# ---------------------------------------------------------------------------
# 5. MAIN
# ---------------------------------------------------------------------------

def main():
    parser = argparse.ArgumentParser(description='Convert PDF bilancio to XBRL')
    parser.add_argument('pdf_path', help='Path to the PDF file')
    parser.add_argument('output_dir', help='Directory where to save the generated XBRL file')
    parser.add_argument('--openai-key', default=None, help='OpenAI API key (or set OPENAI_API_KEY env var)')
    parser.add_argument('--model', default=None, help='OpenAI model (or set OPENAI_MODEL env var)')
    args = parser.parse_args()

    api_key = args.openai_key or os.environ.get('OPENAI_API_KEY', '')
    model = args.model or os.environ.get('OPENAI_MODEL', 'gpt-4o-mini')

    if not api_key:
        print(json.dumps({"success": False, "error": "OPENAI_API_KEY not set"}), file=sys.stdout)
        sys.exit(1)

    if not os.path.isfile(args.pdf_path):
        print(json.dumps({"success": False, "error": f"PDF file not found: {args.pdf_path}"}), file=sys.stdout)
        sys.exit(1)

    try:
        # Step 1: Extract text
        text = extract_text_from_pdf(args.pdf_path)

        # Step 2: Parse trial balance
        parsed = parse_trial_balance(text)

        if not parsed['accounts']['attivita'] and not parsed['accounts']['costi']:
            print(json.dumps({"success": False, "error": "No accounts found in the PDF. Is this a bilancio di verifica?"}), file=sys.stdout)
            sys.exit(1)

        # Step 3: Map to XBRL via OpenAI
        mapping_result = map_accounts_to_xbrl_via_llm(parsed, api_key, model)

        # Step 4: Generate XBRL
        filename = f"Bilancio_{int(time.time())}.xbrl"
        output_path = os.path.join(args.output_dir, filename)
        generate_xbrl(mapping_result, parsed['company_name'], parsed['year'], output_path)

        taxonomy = mapping_result.get('taxonomy_file', 'itcc-ci-abb-2018-11-04.xsd')

        print(json.dumps({
            "success": True,
            "xbrl_path": output_path,
            "xbrl_filename": filename,
            "taxonomy": taxonomy,
            "taxonomy_type": mapping_result.get('taxonomy_type', 'abbreviato'),
            "company_name": parsed['company_name'],
            "year": parsed['year'],
            "accounts_found": {
                "attivita": len(parsed['accounts'].get('attivita', [])),
                "passivita": len(parsed['accounts'].get('passivita', [])),
                "costi": len(parsed['accounts'].get('costi', [])),
                "ricavi": len(parsed['accounts'].get('ricavi', [])),
            },
            "xbrl_elements_mapped": len(mapping_result.get('mappings', {})),
        }), file=sys.stdout)

    except Exception as e:
        print(json.dumps({"success": False, "error": str(e)}), file=sys.stdout)
        sys.exit(1)


if __name__ == '__main__':
    main()
