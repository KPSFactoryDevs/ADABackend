"""
RAG chain — orchestrates retrieval + generation.

Workflow:
  1. Receive a user question (+ optional conversation history)
  2. Semantic-search the vector store for relevant chunks
  3. Build a prompt with system instructions + retrieved context
  4. Call GPT-4o-mini for the final answer
  5. Return the answer together with the sources used
"""
from __future__ import annotations

import logging
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
1. **Dati visibili in pagina**: La domanda dell'utente può contenere un blocco `[Contesto pagina: ...]` con `Dati visibili: {...}`. Questi sono i dati REALI che l'utente sta guardando — usa questi come fonte PRIMARIA per rispondere.
2. **Documenti indicizzati**: I chunks recuperati dal database vettoriale.
3. Se nessuna fonte contiene i dati richiesti, dillo chiaramente — non inventare.

### Regole
1. Rispondi sempre in **italiano**.
2. Sii conciso ma completo. Usa elenchi puntati quando utile.
3. Se citi dati da un documento, indica la fonte (nome file, tipo, anno).
4. Per dati numerici, usa la formattazione italiana (es. 1.234.567,89 €).
5. Se l'utente chiede qualcosa fuori ambito finanziario/ADA, declina educatamente.
6. Mai inventare numeri o dati finanziari.
7. Quando la domanda contiene dati visibili della pagina, USALI per rispondere — sono dati reali dell'utente.
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
) -> Dict[str, Any]:
    """
    Run the full RAG pipeline.

    Returns
    -------
    dict with keys: answer, sources, chunks_used
    """
    k = top_k or TOP_K

    # 1) Retrieve
    chunks = vs_search(question, company_id=company_id, top_k=k, doc_type=doc_type)
    logger.info("Retrieved %d chunks for question: %.80s…", len(chunks), question)

    # 2) Build context block
    if chunks:
        context_parts: list[str] = []
        for i, c in enumerate(chunks, 1):
            meta = c.get("metadata", {})
            source_label = meta.get("filename", meta.get("document_id", f"chunk {i}"))
            doc_type_label = meta.get("doc_type", "documento")
            context_parts.append(
                f"--- Fonte {i}: {source_label} ({doc_type_label}) ---\n{c['text']}"
            )
        context_block = "\n\n".join(context_parts)
    else:
        context_block = "(Nessun documento rilevante trovato nel database.)"

    # 3) Assemble messages
    messages: list[dict] = [{"role": "system", "content": SYSTEM_PROMPT}]

    # Add conversation history (last 10 messages max)
    if history:
        for msg in history[-10:]:
            role = msg.get("role", "user")
            if role in ("user", "assistant"):
                messages.append({"role": role, "content": msg["content"]})

    # User message with context
    user_msg = (
        f"### Contesto recuperato dai documenti\n{context_block}\n\n"
        f"### Domanda dell'utente\n{question}"
    )
    messages.append({"role": "user", "content": user_msg})

    # 4) Call GPT
    client = _get_openai()
    response = client.chat.completions.create(
        model=OPENAI_MODEL,
        messages=messages,
        temperature=0.3,
        max_tokens=2048,
    )

    answer = response.choices[0].message.content or ""

    # 5) Build sources list
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
