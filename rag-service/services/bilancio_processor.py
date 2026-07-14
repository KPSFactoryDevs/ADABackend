"""
Bilancio processor — transforms raw XBRL bilancio data into
semantically structured chunks with contextual headers and tags
for optimal RAG retrieval.
"""
from __future__ import annotations

import json
import logging
from pathlib import Path
from typing import Any, Dict, List, Tuple

logger = logging.getLogger(__name__)

# Load XBRL mapping
_MAPPING_PATH = Path(__file__).parent.parent / "data" / "xbrl_mapping.json"
_XBRL_MAPPING: Dict[str, Dict[str, str]] = {}

try:
    _XBRL_MAPPING = json.loads(_MAPPING_PATH.read_text(encoding="utf-8"))
    logger.info("Loaded XBRL mapping: %d entries", len(_XBRL_MAPPING))
except Exception as e:
    logger.warning("Could not load XBRL mapping: %s", e)


# Section display names
_SECTION_LABELS = {
    "conto_economico": "Conto Economico",
    "stato_patrimoniale_attivo": "Stato Patrimoniale — Attivo",
    "stato_patrimoniale_passivo": "Stato Patrimoniale — Passivo",
    "indici": "Indici di Bilancio",
    "nota_integrativa": "Nota Integrativa",
    "analisi": "Analisi e Giudizio",
}


def _format_value(value: Any) -> str:
    """Format a financial value for display."""
    if value is None or value == "" or value == "?":
        return "N/D"
    try:
        num = float(value)
        if num == int(num):
            # Format as integer with thousands separator
            return f"{int(num):,.0f}".replace(",", ".") + " €"
        else:
            return f"{num:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".") + " €"
    except (ValueError, TypeError):
        return str(value)


def _translate_xbrl_key(key: str) -> Tuple[str, str, str]:
    """
    Translate an XBRL key to Italian label, section, and topic.
    
    Returns: (label, section, topic)
    """
    # Try direct mapping
    if key in _XBRL_MAPPING:
        m = _XBRL_MAPPING[key]
        return m["label"], m["section"], m.get("topic", "altro")
    
    # Try stripping common prefixes
    stripped = key
    for prefix in ["itcc-ci:", "itcc:", "it-gaap-ci:"]:
        if stripped.startswith(prefix):
            stripped = stripped[len(prefix):]
    
    if stripped in _XBRL_MAPPING:
        m = _XBRL_MAPPING[stripped]
        return m["label"], m["section"], m.get("topic", "altro")
    
    # Fallback: convert CamelCase to readable
    import re
    readable = re.sub(r'(?<=[a-z])(?=[A-Z])', ' ', stripped)
    readable = readable.replace('_', ' ').strip()
    
    # Guess section from key name
    section = "altro"
    if any(kw in stripped.lower() for kw in ["valore", "costi", "ricavi", "utile", "perdita", "risultato", "ammortament"]):
        section = "conto_economico"
    elif any(kw in stripped.lower() for kw in ["attivo", "immobilizzazion", "circolante", "crediti", "disponibilit"]):
        section = "stato_patrimoniale_attivo"
    elif any(kw in stripped.lower() for kw in ["passivo", "patrimonio", "debiti", "fondo", "capitale"]):
        section = "stato_patrimoniale_passivo"
    
    return readable, section, "altro"


def process_bilancio(
    voci_data: Dict[str, Any],
    analisi_data: Dict[str, Any] | None = None,
    azienda: str = "",
    anno: str = "",
    nota_integrativa: str = "",
) -> List[Dict[str, Any]]:
    """
    Process bilancio data into structured chunks with tags.
    
    Parameters
    ----------
    voci_data : dict
        The cleaned bilancio data (XBRL key -> value)
    analisi_data : dict, optional
        The bilancio analysis (indici, giudizio, score)
    azienda : str
        Company name
    anno : str
        Fiscal year
    nota_integrativa : str
        Text of the nota integrativa (if available)
    
    Returns
    -------
    list of dict, each with keys: text, tags
    """
    chunks: List[Dict[str, Any]] = []
    
    # Group voci by section
    sections: Dict[str, List[Tuple[str, str, str, Any]]] = {}  # section -> [(label, topic, key, value)]
    
    for key, value in voci_data.items():
        if value is None or value == "" or value == 0:
            continue
        label, section, topic = _translate_xbrl_key(key)
        if section not in sections:
            sections[section] = []
        sections[section].append((label, topic, key, value))
    
    # Create a chunk for each section
    header_base = f"BILANCIO {azienda}" + (f" — Anno {anno}" if anno else "")
    
    for section_key, items in sections.items():
        section_label = _SECTION_LABELS.get(section_key, section_key)
        
        # Build contextual header
        lines = [f"[{header_base} | Sezione: {section_label}]"]
        lines.append("")
        
        # Collect unique topics for this section
        topics = set()
        
        for label, topic, _key, value in items:
            formatted = _format_value(value)
            lines.append(f"• {label}: {formatted}")
            topics.add(topic)
        
        chunk_text = "\n".join(lines)
        
        # Only create chunk if it has meaningful content
        if len(items) > 0:
            chunks.append({
                "text": chunk_text,
                "tags": {
                    "section": section_key,
                    "topic": ",".join(sorted(topics)),
                    "anno": str(anno),
                    "azienda": azienda,
                    "doc_type": "bilancio",
                },
            })
    
    # Create analysis/indici chunk if available
    if analisi_data:
        analysis_lines = [f"[{header_base} | Sezione: Analisi e Indici]", ""]
        
        # Giudizio and score
        if "Giudizio" in analisi_data:
            analysis_lines.append(f"• Giudizio complessivo: {analisi_data['Giudizio']}")
        if "Score" in analisi_data:
            analysis_lines.append(f"• Score: {analisi_data['Score']}/100")
        if "AdvancedGiudizi" in analisi_data:
            analysis_lines.append(f"• Giudizi dettagliati: {json.dumps(analisi_data['AdvancedGiudizi'], ensure_ascii=False)}")
        
        # Basic indices
        if "Basic" in analisi_data and isinstance(analisi_data["Basic"], dict):
            analysis_lines.append("\nIndici Base:")
            for idx_name, idx_data in analisi_data["Basic"].items():
                if isinstance(idx_data, dict) and "value" in idx_data:
                    val = idx_data["value"]
                    fuori = " ⚠️ FUORI SOGLIA" if idx_data.get("fuoriSoglia") else ""
                    analysis_lines.append(f"  • {idx_name}: {val}{fuori}")
        
        # Advanced indices
        if "Advanced" in analisi_data and isinstance(analisi_data["Advanced"], dict):
            analysis_lines.append("\nIndici Avanzati:")
            for idx_name, idx_data in analisi_data["Advanced"].items():
                if isinstance(idx_data, dict) and "value" in idx_data:
                    val = idx_data["value"]
                    analysis_lines.append(f"  • {idx_name}: {val}")
                elif idx_data and not isinstance(idx_data, dict):
                    analysis_lines.append(f"  • {idx_name}: {idx_data}")
        
        chunks.append({
            "text": "\n".join(analysis_lines),
            "tags": {
                "section": "indici",
                "topic": "analisi,score,giudizio",
                "anno": str(anno),
                "azienda": azienda,
                "doc_type": "bilancio",
            },
        })
    
    # Create nota integrativa chunk if available
    if nota_integrativa and len(nota_integrativa.strip()) > 50:
        # Truncate if too long
        nota_text = nota_integrativa[:8000] if len(nota_integrativa) > 8000 else nota_integrativa
        chunks.append({
            "text": f"[{header_base} | Sezione: Nota Integrativa]\n\n{nota_text}",
            "tags": {
                "section": "nota_integrativa",
                "topic": "nota_integrativa",
                "anno": str(anno),
                "azienda": azienda,
                "doc_type": "bilancio",
            },
        })
    
    logger.info(
        "Processed bilancio %s %s: %d sections, %d total chunks",
        azienda, anno, len(sections), len(chunks),
    )
    return chunks
