"""
ADA RAG Service — FastAPI application.

Endpoints
---------
POST /query      — ask the AI agent a question (RAG + optional SQL)
POST /ingest     — ingest a document into the vector store
POST /ingest-text — ingest raw text (no file upload)
DELETE /document  — remove a document from the vector store
GET  /health     — health check
GET  /stats      — vector store statistics
"""
from __future__ import annotations

import logging
import os
import tempfile
from pathlib import Path
from typing import Any, Dict, List, Optional, Union

from fastapi import FastAPI, File, Form, HTTPException, UploadFile
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel

import config  # noqa: F401  — triggers env loading
from services import document_processor as dp
from services import vector_store as vs
from services import rag_chain
from services import bilancio_processor as bp

# ---------------------------------------------------------------------------
# Logging
# ---------------------------------------------------------------------------
logging.basicConfig(
    level=getattr(logging, config.LOG_LEVEL.upper(), logging.INFO),
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s",
)
logger = logging.getLogger("rag-service")

# ---------------------------------------------------------------------------
# FastAPI app
# ---------------------------------------------------------------------------
app = FastAPI(
    title="ADA RAG Service",
    version="2.0.0",
    description="Retrieval-Augmented Generation microservice for ADA CRM",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)


# ---------------------------------------------------------------------------
# Request / Response models
# ---------------------------------------------------------------------------

class PageContext(BaseModel):
    """Structured page context from the CRM frontend."""
    page: Optional[str] = None
    summary: Optional[str] = None
    data: Optional[Any] = None


class QueryRequest(BaseModel):
    question: str
    company_id: Union[int, str]
    history: Optional[List[Dict[str, str]]] = None
    doc_type: Optional[str] = None
    top_k: Optional[int] = None
    page_context: Optional[PageContext] = None


class QueryResponse(BaseModel):
    answer: str
    sources: List[Dict[str, Any]]
    chunks_used: int


class IngestResponse(BaseModel):
    ok: bool
    document_id: str
    chunks_stored: int


class DeleteResponse(BaseModel):
    ok: bool
    chunks_deleted: int


class TaggedChunk(BaseModel):
    text: str
    tags: Dict[str, str] = {}


class StructuredIngestRequest(BaseModel):
    document_id: str
    company_id: Union[int, str]
    doc_type: str = "documento"
    filename: str = ""
    sections: List[TaggedChunk]


class BilancioIngestRequest(BaseModel):
    document_id: str
    company_id: Union[int, str]
    azienda: str = ""
    anno: str = ""
    voci_data: Dict[str, Any]
    analisi_data: Optional[Dict[str, Any]] = None
    nota_integrativa: Optional[str] = None


# ---------------------------------------------------------------------------
# Endpoints
# ---------------------------------------------------------------------------

@app.post("/query", response_model=QueryResponse)
async def query_agent(req: QueryRequest):
    """Ask the RAG agent a question."""
    if not req.question.strip():
        raise HTTPException(422, "question is required")

    # Convert page_context Pydantic model to dict if present
    page_ctx = None
    if req.page_context:
        page_ctx = req.page_context.model_dump(exclude_none=True)

    result = rag_chain.query(
        question=req.question,
        company_id=req.company_id,
        history=req.history,
        top_k=req.top_k,
        doc_type=req.doc_type,
        page_context=page_ctx,
    )
    return QueryResponse(**result)


@app.post("/ingest", response_model=IngestResponse)
async def ingest_document(
    file: UploadFile = File(...),
    document_id: str = Form(...),
    company_id: str = Form(...),
    doc_type: str = Form("documento"),
    filename: str = Form(""),
):
    """
    Upload and ingest a document into the vector store.

    The file is temporarily saved, text is extracted and chunked,
    then embeddings are generated and stored in ChromaDB.
    """
    original_filename = filename or file.filename or "unknown"
    suffix = Path(original_filename).suffix

    # Save to temp
    with tempfile.NamedTemporaryFile(delete=False, suffix=suffix) as tmp:
        content = await file.read()
        tmp.write(content)
        tmp_path = tmp.name

    try:
        text = dp.extract_text(tmp_path)
        if not text.strip():
            raise HTTPException(400, "Could not extract text from the uploaded file")

        chunks = dp.split_text(text)
        metadata = {
            "doc_type": doc_type,
            "filename": original_filename,
        }
        stored = vs.ingest_chunks(chunks, document_id, company_id, metadata)
        return IngestResponse(ok=True, document_id=document_id, chunks_stored=stored)

    finally:
        os.unlink(tmp_path)


@app.post("/ingest-text", response_model=IngestResponse)
async def ingest_text(
    text: str = Form(...),
    document_id: str = Form(...),
    company_id: str = Form(...),
    doc_type: str = Form("documento"),
    filename: str = Form(""),
    structured: str = Form("false"),
):
    """
    Ingest raw text directly (no file upload needed).
    Useful when the Laravel backend has already extracted the text.
    
    Set structured=true for JSON/tabular data to use larger chunks.
    """
    if not text.strip():
        raise HTTPException(400, "text is required")

    # Use structured chunking for financial/JSON data
    if structured.lower() in ("true", "1", "yes"):
        chunks = dp.split_structured_text(text)
    else:
        chunks = dp.split_text(text)

    metadata = {
        "doc_type": doc_type,
        "filename": filename or document_id,
    }
    stored = vs.ingest_chunks(chunks, document_id, company_id, metadata)
    return IngestResponse(ok=True, document_id=document_id, chunks_stored=stored)


@app.delete("/document")
async def delete_document(document_id: str, company_id: str):
    """Remove a document's chunks from the vector store."""
    deleted = vs.delete_document(document_id, company_id)
    return DeleteResponse(ok=True, chunks_deleted=deleted)


@app.post("/ingest-structured", response_model=IngestResponse)
async def ingest_structured(req: StructuredIngestRequest):
    """
    Ingest pre-processed chunks with per-chunk tags.
    
    Each section has text + tags (section, topic, anno, azienda).
    Tags are stored as ChromaDB metadata for filtered retrieval.
    """
    if not req.sections:
        raise HTTPException(400, "sections list is empty")

    tagged_chunks = [{"text": s.text, "tags": {**s.tags, "doc_type": req.doc_type}} for s in req.sections]
    stored = vs.ingest_tagged_chunks(tagged_chunks, req.document_id, req.company_id)
    return IngestResponse(ok=True, document_id=req.document_id, chunks_stored=stored)


@app.post("/ingest-bilancio", response_model=IngestResponse)
async def ingest_bilancio(req: BilancioIngestRequest):
    """
    Ingest a bilancio by processing XBRL data into semantic chunks.
    
    Automatically:
    - Translates XBRL keys to Italian labels
    - Groups voci by section (Conto Economico, SP Attivo, SP Passivo)
    - Creates contextual headers for each chunk
    - Tags each chunk with section, topic, anno, azienda
    """
    # Delete existing chunks for this document
    vs.delete_document(req.document_id, req.company_id)

    # Process through bilancio processor
    tagged_chunks = bp.process_bilancio(
        voci_data=req.voci_data,
        analisi_data=req.analisi_data,
        azienda=req.azienda,
        anno=req.anno,
        nota_integrativa=req.nota_integrativa or "",
    )

    if not tagged_chunks:
        raise HTTPException(400, "No data to index")

    stored = vs.ingest_tagged_chunks(tagged_chunks, req.document_id, req.company_id)
    return IngestResponse(ok=True, document_id=req.document_id, chunks_stored=stored)


@app.get("/health")
async def health():
    """Health check."""
    return {
        "status": "ok",
        "service": "ada-rag-service",
        "version": "2.0.0",
        "model": config.OPENAI_MODEL,
        "openai_configured": bool(config.OPENAI_API_KEY),
    }


@app.get("/stats")
async def stats():
    """Return vector store statistics."""
    return vs.get_collection_stats()


# ---------------------------------------------------------------------------
# Startup: index knowledge base (only if not already present)
# ---------------------------------------------------------------------------

def _index_knowledge_base(force: bool = False):
    """
    Index all markdown files in the knowledge/ directory
    into the system collection on startup.
    
    Skips indexing if the collection already has data (to avoid
    burning OpenAI embedding rate limits on every restart).
    Use force=True or the /reindex-kb endpoint to force re-indexing.
    """
    kb_dir = Path(config.KNOWLEDGE_DIR)
    if not kb_dir.exists():
        logger.warning("Knowledge base dir not found: %s", kb_dir)
        return 0

    # Check if KB is already indexed
    if not force:
        try:
            stats = vs.get_collection_stats()
            existing = stats.get("system-knowledge", 0)
            if isinstance(existing, int) and existing > 0:
                logger.info(
                    "Knowledge base already indexed (%d chunks). Skipping. "
                    "Use POST /reindex-kb to force re-indexing.",
                    existing,
                )
                return existing
        except Exception:
            pass  # If we can't check, proceed with indexing

    total = 0
    for md_file in sorted(kb_dir.rglob("*.md")):
        doc_id = f"kb__{md_file.relative_to(kb_dir).as_posix().replace('/', '__')}"
        text = md_file.read_text(encoding="utf-8", errors="ignore")
        if not text.strip():
            continue
        chunks = dp.split_text(text, chunk_size=800, chunk_overlap=150)
        stored = vs.ingest_system_chunks(
            chunks, doc_id,
            metadata_base={
                "doc_type": "knowledge_base",
                "filename": md_file.name,
                "topic": md_file.stem,
            },
        )
        total += stored
        logger.info("KB indexed: %s → %d chunks", md_file.name, stored)

    logger.info("Knowledge base indexing complete: %d total chunks", total)
    return total


@app.post("/reindex-kb")
async def reindex_kb():
    """Force re-indexing of the knowledge base."""
    total = _index_knowledge_base(force=True)
    return {"ok": True, "chunks_indexed": total}


@app.on_event("startup")
async def on_startup():
    logger.info("ADA RAG Service v2.0.0 starting up…")
    _index_knowledge_base(force=False)
    logger.info("Ready. Model: %s, Top-K: %d", config.OPENAI_MODEL, config.TOP_K)

