"""
RAG chain — orchestrates retrieval + generation.

Workflow:
  1. Receive a user question (+ optional conversation history)
  2. Semantic-search the vector store for relevant chunks
  3. Build a prompt with system instructions + retrieved context
  4. Call GPT-4o for the final answer
  5. Return the answer together with the sources used
"""
from __future__ import annotations

import json
import logging
import time
from typing import Any, Dict, List, Optional

from openai import OpenAI

from config import OPENAI_API_KEY, OPENAI_MODEL, TOP_K
from services.vector_store import search as vs_search

logger = logging.getLogger(__name__)

_openai_client: OpenAI | None = None


def _get_openai() -> OpenAI:
    global _openai_client
    if _openai_client is None:
        _openai_client = OpenAI(api_key=OPENAI_API_KEY)
    return _openai_client


# ---------------------------------------------------------------------------
# System prompt
# ---------------------------------------------------------------------------

SYSTEM_PROMPT = """\
Sei **ADA AI**, l'assistente intelligente della piattaforma ADA — un software di analisi finanziaria per PMI italiane.

### Il tuo ruolo
- Rispondi a domande sull'utilizzo della piattaforma ADA (guida, moduli, funzionalità).
- Rispondi a domande sui documenti finanziari dell'utente (bilanci, centrale rischi, fatture, estratti conto).
- Usa TUTTI i dati disponibili: sia il contesto recuperato dai documenti, sia i dati della pagina visibile dall'utente.

### Fonti dati (in ordine di priorità)
1. **Dati visibili in pagina**: Se presenti nella sezione "DATI PAGINA", questi sono i dati REALI che l'utente sta guardando — usa questi come fonte PRIMARIA per rispondere.
2. **Documenti indicizzati**: I chunks recuperati dal database vettoriale nella sezione "DOCUMENTI".
3. Se nessuna fonte contiene i dati richiesti, dillo chiaramente — non inventare.

### Regole
1. Rispondi sempre in **italiano**.
2. Sii conciso ma completo. Usa elenchi puntati quando utile.
3. Se citi dati da un documento, indica la fonte (nome file, tipo, anno).
4. Per dati numerici, usa la formattazione italiana (es. 1.234.567,89 €).
5. Se l'utente chiede qualcosa fuori ambito finanziario/ADA, declina educatamente.
6. Mai inventare numeri o dati finanziari.
7. Quando i dati della pagina sono disponibili, USALI per rispondere — sono dati reali dell'utente.
8. Se la domanda riguarda dati che vedi nei DATI PAGINA, rispondi DIRETTAMENTE da lì senza esitazione.
"""


# ---------------------------------------------------------------------------
# Chain
# ---------------------------------------------------------------------------

def query(
    question: str,
    company_id: int | str,
    history: Optional[List[Dict[str, str]]] = None,
    top_k: int | None = None,
    doc_type: Optional[str] = None,
    page_context: Optional[Dict[str, Any]] = None,
) -> Dict[str, Any]:
    """
    Run the full RAG pipeline.

    Parameters
    ----------
    question : str
        The user's question (clean, without injected context).
    company_id : int | str
        The company to search documents for.
    history : list, optional
        Previous conversation messages.
    top_k : int, optional
        Number of chunks to retrieve.
    doc_type : str, optional
        Filter by document type.
    page_context : dict, optional
        Structured page context with keys: page, summary, data.
        This is the data the user is currently looking at in the CRM.

    Returns
    -------
    dict with keys: answer, sources, chunks_used
    """
    k = top_k or TOP_K

    # 1) Retrieve from vector store
    chunks = vs_search(question, company_id=company_id, top_k=k, doc_type=doc_type)
    logger.info("Retrieved %d chunks for question: %.80s…", len(chunks), question)

    # 2) Build context block from retrieved documents
    if chunks:
        context_parts: list[str] = []
        for i, c in enumerate(chunks, 1):
            meta = c.get("metadata", {})
            source_label = meta.get("filename", meta.get("document_id", f"chunk {i}"))
            doc_type_label = meta.get("doc_type", "documento")
            dist = c.get("distance", "?")
            context_parts.append(
                f"--- Fonte {i}: {source_label} ({doc_type_label}) [dist={dist}] ---\n{c['text']}"
            )
        context_block = "\n\n".join(context_parts)
    else:
        context_block = "(Nessun documento rilevante trovato nel database.)"

    # 3) Build page context block (structured, no regex needed)
    page_context_block = ""
    if page_context and isinstance(page_context, dict):
        page_name = page_context.get("page", "Pagina")
        page_summary = page_context.get("summary", "")
        page_data = page_context.get("data", {})

        page_context_block = (
            f"### 🔴 DATI PAGINA — {page_name}\n"
            f"Questi sono i dati REALI che l'utente sta guardando in questo momento. "
            f"USALI come fonte PRIMARIA per rispondere.\n"
        )
        if page_summary:
            page_context_block += f"Riepilogo: {page_summary}\n"

        if page_data:
            # Format data as readable key-value pairs
            if isinstance(page_data, dict):
                page_context_block += "\nDati:\n"
                page_context_block += _format_page_data(page_data)
            else:
                page_context_block += f"\nDati: {json.dumps(page_data, ensure_ascii=False)}\n"

        logger.info(
            "Page context provided: page=%s, summary=%s, data_keys=%s",
            page_name, page_summary[:50] if page_summary else "none",
            list(page_data.keys()) if isinstance(page_data, dict) else "non-dict",
        )
    else:
        logger.info("No page context provided")

    # 4) Assemble messages
    messages: list[dict] = [{"role": "system", "content": SYSTEM_PROMPT}]

    # Add conversation history (last 10 messages max)
    if history:
        for msg in history[-10:]:
            role = msg.get("role", "user")
            if role in ("user", "assistant"):
                messages.append({"role": role, "content": msg["content"]})

    # User message with context — page data FIRST (highest priority)
    parts = []
    if page_context_block:
        parts.append(page_context_block)
    parts.append(f"### DOCUMENTI (dal database vettoriale)\n{context_block}")
    parts.append(f"### DOMANDA DELL'UTENTE\n{question}")
    user_msg = "\n\n".join(parts)
    messages.append({"role": "user", "content": user_msg})

    logger.info("Prompt assembled: %d messages, user_msg length=%d", len(messages), len(user_msg))

    # 5) Call GPT (with retry for rate limits)
    client = _get_openai()
    answer = ""
    last_error = None
    for attempt in range(3):
        try:
            response = client.chat.completions.create(
                model=OPENAI_MODEL,
                messages=messages,
                temperature=0.3,
                max_tokens=4096,
            )
            answer = response.choices[0].message.content or ""
            last_error = None
            break
        except Exception as e:
            last_error = e
            error_str = str(e)
            if "429" in error_str or "rate" in error_str.lower():
                wait = 2 ** (attempt + 1)  # 2, 4, 8 seconds
                logger.warning("Rate limited (attempt %d/3), waiting %ds…", attempt + 1, wait)
                time.sleep(wait)
            else:
                raise

    # If all retries failed, raise so the caller gets a proper error
    if last_error is not None:
        logger.error("All 3 GPT retry attempts failed: %s", last_error)
        raise last_error

    # 6) Build sources list
    sources: list[dict] = []
    seen_docs: set[str] = set()
    for c in chunks:
        doc_id = c.get("metadata", {}).get("document_id", "")
        if doc_id and doc_id not in seen_docs:
            seen_docs.add(doc_id)
            sources.append({
                "document_id": doc_id,
                "filename": c["metadata"].get("filename", ""),
                "doc_type": c["metadata"].get("doc_type", ""),
                "source": c.get("source", ""),
            })

    return {
        "answer": answer,
        "sources": sources,
        "chunks_used": len(chunks),
    }


def _format_page_data(data: dict, indent: int = 0) -> str:
    """
    Recursively format a dict of page data into readable key-value text.
    Handles nested dicts and lists of dicts.
    """
    lines: list[str] = []
    prefix = "  " * indent

    for key, value in data.items():
        if value is None:
            continue
        elif isinstance(value, dict):
            lines.append(f"{prefix}• {key}:")
            lines.append(_format_page_data(value, indent + 1))
        elif isinstance(value, list):
            lines.append(f"{prefix}• {key}: ({len(value)} elementi)")
            for i, item in enumerate(value[:20]):  # cap at 20 items
                if isinstance(item, dict):
                    item_str = ", ".join(f"{k}={v}" for k, v in item.items() if v is not None)
                    lines.append(f"{prefix}  [{i+1}] {item_str}")
                else:
                    lines.append(f"{prefix}  [{i+1}] {item}")
            if len(value) > 20:
                lines.append(f"{prefix}  ... e altri {len(value) - 20} elementi")
        else:
            lines.append(f"{prefix}• {key}: {value}")

    return "\n".join(lines)
