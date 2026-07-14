"""
Vector store wrapper around ChromaDB.

Each company gets its own collection (`company_{id}`) to enforce multi-tenancy
at the storage level.  A special `_system_knowledge` collection holds the
knowledge-base articles that are visible to every company.
"""
from __future__ import annotations

import logging
import time
from pathlib import Path
from typing import Any, Dict, List, Optional

import chromadb
from chromadb.config import Settings
from langchain_openai import OpenAIEmbeddings

from config import CHROMA_PERSIST_DIR, OPENAI_API_KEY, OPENAI_EMBEDDING_MODEL

logger = logging.getLogger(__name__)

# Singleton client — created lazily
_client: chromadb.ClientAPI | None = None
_embedder: OpenAIEmbeddings | None = None

SYSTEM_COLLECTION = "system-knowledge"


def _get_client() -> chromadb.ClientAPI:
    global _client
    if _client is None:
        persist_dir = Path(CHROMA_PERSIST_DIR)
        persist_dir.mkdir(parents=True, exist_ok=True)
        _client = chromadb.PersistentClient(
            path=str(persist_dir),
            settings=Settings(anonymized_telemetry=False),
        )
        logger.info("ChromaDB client initialised at %s", persist_dir)
    return _client


def _get_embedder() -> OpenAIEmbeddings:
    global _embedder
    if _embedder is None:
        _embedder = OpenAIEmbeddings(
            model=OPENAI_EMBEDDING_MODEL,
            openai_api_key=OPENAI_API_KEY,
        )
    return _embedder


def _collection_name(company_id: int | str) -> str:
    return f"company_{company_id}"


# ---------------------------------------------------------------------------
# Write operations
# ---------------------------------------------------------------------------

def ingest_chunks(
    chunks: List[str],
    document_id: str,
    company_id: int | str,
    metadata_base: Dict[str, Any] | None = None,
) -> int:
    """
    Embed *chunks* and store them in the company's collection.

    Returns the number of chunks stored.
    """
    if not chunks:
        return 0

    client = _get_client()
    embedder = _get_embedder()
    col_name = _collection_name(company_id)
    collection = client.get_or_create_collection(name=col_name)

    # Build ids & metadata
    ids: list[str] = []
    metadatas: list[dict] = []
    for i, chunk in enumerate(chunks):
        chunk_id = f"{document_id}__chunk_{i}"
        meta = {
            "document_id": str(document_id),
            "chunk_index": i,
            "company_id": str(company_id),
            **(metadata_base or {}),
        }
        ids.append(chunk_id)
        metadatas.append(meta)

    # Generate embeddings
    embeddings = embedder.embed_documents(chunks)

    collection.upsert(
        ids=ids,
        embeddings=embeddings,
        documents=chunks,
        metadatas=metadatas,
    )
    logger.info(
        "Ingested %d chunks for doc=%s into collection=%s",
        len(chunks), document_id, col_name,
    )
    return len(chunks)


def ingest_system_chunks(
    chunks: List[str],
    document_id: str,
    metadata_base: Dict[str, Any] | None = None,
) -> int:
    """Ingest knowledge-base chunks visible to all companies."""
    if not chunks:
        return 0

    client = _get_client()
    embedder = _get_embedder()
    collection = client.get_or_create_collection(name=SYSTEM_COLLECTION)

    ids = [f"{document_id}__chunk_{i}" for i in range(len(chunks))]
    metadatas = [
        {"document_id": str(document_id), "chunk_index": i, **(metadata_base or {})}
        for i in range(len(chunks))
    ]
    embeddings = embedder.embed_documents(chunks)

    collection.upsert(
        ids=ids,
        embeddings=embeddings,
        documents=chunks,
        metadatas=metadatas,
    )
    logger.info("Ingested %d system KB chunks for doc=%s", len(chunks), document_id)
    return len(chunks)


def delete_document(document_id: str, company_id: int | str) -> int:
    """Remove all chunks belonging to *document_id* from the company collection."""
    client = _get_client()
    col_name = _collection_name(company_id)
    try:
        collection = client.get_collection(name=col_name)
    except Exception:
        return 0

    # ChromaDB supports where-filtering on metadata
    existing = collection.get(where={"document_id": str(document_id)})
    if existing and existing["ids"]:
        collection.delete(ids=existing["ids"])
        logger.info("Deleted %d chunks for doc=%s", len(existing["ids"]), document_id)
        return len(existing["ids"])
    return 0


# ---------------------------------------------------------------------------
# Read operations
# ---------------------------------------------------------------------------

def search(
    query: str,
    company_id: int | str,
    top_k: int = 10,
    doc_type: Optional[str] = None,
) -> List[Dict[str, Any]]:
    """
    Semantic search across the company collection AND the system KB.

    Returns a list of dicts: {text, metadata, distance}.
    
    Split: up to top_k from company docs, up to 4 from system KB,
    then merge, sort by distance, and return top_k total.
    """
    client = _get_client()
    embedder = _get_embedder()

    # Embed query with retry for rate limits
    query_embedding = None
    for attempt in range(3):
        try:
            query_embedding = embedder.embed_query(query)
            break
        except Exception as e:
            if "429" in str(e) or "rate" in str(e).lower():
                wait = 3 * (attempt + 1)
                logger.warning("Embedding rate limited (attempt %d/3), waiting %ds", attempt + 1, wait)
                time.sleep(wait)
            else:
                raise
    if query_embedding is None:
        logger.error("All embedding retry attempts failed")
        return []

    results: list[dict] = []

    # 1) Company-specific docs — request more to ensure good coverage
    col_name = _collection_name(company_id)
    try:
        collection = client.get_collection(name=col_name)
        where_filter = None
        if doc_type:
            where_filter = {"doc_type": doc_type}

        company_k = top_k  # request full top_k from company
        res = collection.query(
            query_embeddings=[query_embedding],
            n_results=company_k,
            where=where_filter,
        )
        for i, doc in enumerate(res["documents"][0]):
            results.append({
                "text": doc,
                "metadata": res["metadatas"][0][i] if res["metadatas"] else {},
                "distance": res["distances"][0][i] if res["distances"] else None,
                "source": "company",
            })
        logger.info(
            "Company search (%s): found %d chunks (requested %d)",
            col_name, len(res["documents"][0]), company_k,
        )
    except Exception as e:
        logger.debug("No company collection yet for %s: %s", col_name, e)

    # 2) System knowledge base (always searched, up to 4 results)
    system_k = min(top_k, 4)
    try:
        sys_collection = client.get_collection(name=SYSTEM_COLLECTION)
        sys_res = sys_collection.query(
            query_embeddings=[query_embedding],
            n_results=system_k,
        )
        for i, doc in enumerate(sys_res["documents"][0]):
            results.append({
                "text": doc,
                "metadata": sys_res["metadatas"][0][i] if sys_res["metadatas"] else {},
                "distance": sys_res["distances"][0][i] if sys_res["distances"] else None,
                "source": "system_kb",
            })
        logger.info("System KB search: found %d chunks", len(sys_res["documents"][0]))
    except Exception as e:
        logger.debug("No system KB collection yet: %s", e)

    # Sort by distance (ascending = most similar first) and cap at top_k
    results.sort(key=lambda r: r.get("distance") or 999)
    final = results[:top_k]
    logger.info(
        "Search total: %d candidates → returning %d (company: %d, system: %d)",
        len(results), len(final),
        sum(1 for r in final if r["source"] == "company"),
        sum(1 for r in final if r["source"] == "system_kb"),
    )
    return final


def get_collection_stats(company_id: int | str | None = None) -> Dict[str, Any]:
    """Return basic stats about the vector store."""
    client = _get_client()
    stats: dict[str, Any] = {}
    collections = client.list_collections()
    for col in collections:
        name = col.name if hasattr(col, "name") else str(col)
        try:
            c = client.get_collection(name=name)
            stats[name] = c.count()
        except Exception:
            stats[name] = "error"
    return stats
