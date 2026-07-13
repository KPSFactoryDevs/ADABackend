"""
ADA RAG Service — FastAPI application.

Endpoints
---------
POST /query      — ask the AI agent a question (RAG + optional SQL)
POST /ingest     — ingest a document into the vector store
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
    version="1.0.0",
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

class QueryRequest(BaseModel):
    question: str
    company_id: Union[int, str]
    history: Optional[List[Dict[str, str]]] = None
    doc_type: Optional[str] = None
    top_k: Optional[int] = None


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


# ---------------------------------------------------------------------------
# Endpoints
# ---------------------------------------------------------------------------

@app.post("/query", response_model=QueryResponse)
async def query_agent(req: QueryRequest):
    """Ask the RAG agent a question."""
    if not req.question.strip():
        raise HTTPException(422, "question is required")

    result = rag_chain.query(
        question=req.question,
        company_id=req.company_id,
        history=req.history,
        top_k=req.top_k,
        doc_type=req.doc_type,
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
):
    """
    Ingest raw text directly (no file upload needed).
    Useful when the Laravel backend has already extracted the text.
    """
    if not text.strip():
        raise HTTPException(400, "text is required")

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


@app.get("/health")
async def health():
    """Health check."""
    return {
        "status": "ok",
        "service": "ada-rag-service",
        "openai_configured": bool(config.OPENAI_API_KEY),
    }


@app.get("/stats")
async def stats():
    """Return vector store statistics."""
    return vs.get_collection_stats()


# ---------------------------------------------------------------------------
# Startup: index knowledge base
# ---------------------------------------------------------------------------

def _index_knowledge_base():
    """
    Index all markdown files in the knowledge/ directory
    into the system collection on startup.
    """
    kb_dir = Path(config.KNOWLEDGE_DIR)
    if not kb_dir.exists():
        logger.warning("Knowledge base dir not found: %s", kb_dir)
        return

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


@app.on_event("startup")
async def on_startup():
    logger.info("ADA RAG Service starting up…")
    _index_knowledge_base()
    logger.info("Ready.")
