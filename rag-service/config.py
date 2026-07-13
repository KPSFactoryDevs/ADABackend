"""
Centralised configuration — reads from env vars or .env file.
"""
import os
from pathlib import Path
from dotenv import load_dotenv

# Load .env from the Laravel root (one level up) if present
_BACKEND_ROOT = Path(__file__).resolve().parent.parent
_ENV_FILE = _BACKEND_ROOT / ".env"
if _ENV_FILE.exists():
    load_dotenv(_ENV_FILE)

# --- OpenAI ---------------------------------------------------------------
OPENAI_API_KEY: str = os.getenv("OPENAI_API_KEY", "")
OPENAI_MODEL: str = os.getenv("OPENAI_MODEL", "gpt-4o-mini")
OPENAI_EMBEDDING_MODEL: str = os.getenv("OPENAI_EMBEDDING_MODEL", "text-embedding-3-small")

# --- ChromaDB --------------------------------------------------------------
CHROMA_PERSIST_DIR: str = os.getenv(
    "CHROMA_PERSIST_DIR",
    str(Path(__file__).resolve().parent / "data" / "chroma"),
)

# --- RAG tuning ------------------------------------------------------------
CHUNK_SIZE: int = int(os.getenv("RAG_CHUNK_SIZE", "1000"))
CHUNK_OVERLAP: int = int(os.getenv("RAG_CHUNK_OVERLAP", "200"))
TOP_K: int = int(os.getenv("RAG_TOP_K", "6"))

# --- Knowledge base --------------------------------------------------------
KNOWLEDGE_DIR: str = str(Path(__file__).resolve().parent / "knowledge")

# --- Misc ------------------------------------------------------------------
LOG_LEVEL: str = os.getenv("LOG_LEVEL", "INFO")
