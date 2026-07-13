"""
Document processor — extracts text from PDFs, XML (FatturaPA) and plain text,
then splits it into overlapping chunks ready for embedding.
"""
from __future__ import annotations

import logging
import re
import xml.etree.ElementTree as ET
from pathlib import Path
from typing import List

from langchain_text_splitters import RecursiveCharacterTextSplitter

from config import CHUNK_SIZE, CHUNK_OVERLAP, STRUCTURED_CHUNK_SIZE, STRUCTURED_CHUNK_OVERLAP

logger = logging.getLogger(__name__)


# ---------------------------------------------------------------------------
# Text extraction
# ---------------------------------------------------------------------------

def extract_text_from_pdf(path: str) -> str:
    """Extract text from a PDF file using pdfplumber."""
    import pdfplumber

    pages: list[str] = []
    with pdfplumber.open(path) as pdf:
        for page in pdf.pages:
            text = page.extract_text()
            if text:
                pages.append(text)
    return "\n\n".join(pages)


def extract_text_from_xml(path: str) -> str:
    """
    Extract meaningful text from a FatturaPA XML.
    Walks the tree and collects tag-value pairs.
    """
    try:
        tree = ET.parse(path)
        root = tree.getroot()
    except ET.ParseError:
        # fallback: read as plain text
        return Path(path).read_text(errors="ignore")

    # Strip namespaces for easier traversal
    ns_re = re.compile(r"\{[^}]+\}")
    lines: list[str] = []
    for elem in root.iter():
        tag = ns_re.sub("", elem.tag)
        text = (elem.text or "").strip()
        if text:
            lines.append(f"{tag}: {text}")
    return "\n".join(lines)


def extract_text_from_plain(path: str) -> str:
    """Read a plain-text or Markdown file."""
    return Path(path).read_text(encoding="utf-8", errors="ignore")


def extract_text(path: str) -> str:
    """Auto-detect format and extract text."""
    p = Path(path)
    suffix = p.suffix.lower()

    if suffix == ".pdf":
        return extract_text_from_pdf(path)
    elif suffix == ".xml":
        return extract_text_from_xml(path)
    elif suffix in (".md", ".txt", ".csv", ".json"):
        return extract_text_from_plain(path)
    else:
        # best effort
        logger.warning("Unknown file format %s — reading as plain text", suffix)
        return extract_text_from_plain(path)


# ---------------------------------------------------------------------------
# Chunking
# ---------------------------------------------------------------------------

def split_text(text: str, chunk_size: int | None = None, chunk_overlap: int | None = None) -> List[str]:
    """Split text into overlapping chunks."""
    splitter = RecursiveCharacterTextSplitter(
        chunk_size=chunk_size or CHUNK_SIZE,
        chunk_overlap=chunk_overlap or CHUNK_OVERLAP,
        separators=["\n\n", "\n", ". ", " ", ""],
        length_function=len,
    )
    return splitter.split_text(text)


def split_structured_text(text: str) -> List[str]:
    """
    Split structured/tabular text (JSON, financial data) into larger chunks
    to preserve data integrity. Uses bigger chunk sizes to avoid splitting
    key-value pairs or table rows.
    """
    splitter = RecursiveCharacterTextSplitter(
        chunk_size=STRUCTURED_CHUNK_SIZE,
        chunk_overlap=STRUCTURED_CHUNK_OVERLAP,
        separators=["\n\n\n", "\n\n", "\n", "}, ", ", ", " ", ""],
        length_function=len,
    )
    return splitter.split_text(text)
