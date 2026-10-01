#!/usr/bin/env python3
"""Busca local (RAG leve) na documentação do projeto.

Divide os arquivos .md de docs/ (e README/CLAUDE.md) em trechos por seção e ranqueia com BM25,
devolvendo só os trechos relevantes com arquivo:linha. Assim o agente lê poucas linhas em vez de
documentos inteiros. Sem dependências e sem rede.

Uso:
    python3 scripts/docs_search.py "como criar uma migration"
    python3 scripts/docs_search.py "rate limit" -k 5 --max-lines 30
"""
from __future__ import annotations

import argparse
import math
import re
import sys
import unicodedata
from dataclasses import dataclass
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SOURCES = [ROOT / "docs", ROOT / "README.md", ROOT / "CLAUDE.md"]

STOPWORDS = set(
    """a o os as um uma uns umas de do da dos das em no na nos nas por para pra com sem que se e ou
    é ser como qual quais onde quando porque mais menos muito ao aos à às the of to in and or for on is
    are be with how what where when""".split()
)


@dataclass
class Chunk:
    path: Path
    start: int
    heading: str
    lines: list[str]

    @property
    def text(self) -> str:
        return "\n".join(self.lines)


def normalize(text: str) -> str:
    text = unicodedata.normalize("NFKD", text.lower())
    return "".join(c for c in text if not unicodedata.combining(c))


def tokenize(text: str) -> list[str]:
    tokens = re.findall(r"[a-z0-9_]+", normalize(text))
    out = []
    for t in tokens:
        if t in STOPWORDS or len(t) < 2:
            continue
        out.append(t)
        # Quebra identificadores (snake_case / camelCase já minúsculo) para casar partes.
        if "_" in t:
            out.extend(p for p in t.split("_") if len(p) > 1)
    return out


def iter_markdown_files() -> list[Path]:
    files: list[Path] = []
    for src in SOURCES:
        if src.is_dir():
            files.extend(sorted(src.rglob("*.md")))
        elif src.is_file():
            files.append(src)
    return files


def split_chunks(path: Path) -> list[Chunk]:
    lines = path.read_text(encoding="utf-8", errors="replace").splitlines()
    chunks: list[Chunk] = []
    current = Chunk(path, 1, path.stem, [])
    in_code = False
    for i, line in enumerate(lines, start=1):
        if line.strip().startswith("```"):
            in_code = not in_code
        if not in_code and re.match(r"^#{1,4}\s", line) and current.lines:
            chunks.append(current)
            current = Chunk(path, i, line.lstrip("#").strip(), [])
        elif not in_code and re.match(r"^#{1,4}\s", line):
            current.heading = line.lstrip("#").strip()
            current.start = i
        current.lines.append(line)
    if current.lines:
        chunks.append(current)
    return [c for c in chunks if c.text.strip()]


def bm25(chunks: list[Chunk], query: str, k1: float = 1.5, b: float = 0.75) -> list[tuple[float, Chunk]]:
    docs = [tokenize(c.heading + " " + c.heading + "\n" + c.text) for c in chunks]  # título pesa em dobro
    n = len(docs)
    avgdl = sum(len(d) for d in docs) / max(n, 1)
    df: dict[str, int] = {}
    for d in docs:
        for term in set(d):
            df[term] = df.get(term, 0) + 1

    q_terms = tokenize(query)
    scored = []
    for chunk, doc in zip(chunks, docs):
        if not doc:
            continue
        tf: dict[str, int] = {}
        for term in doc:
            tf[term] = tf.get(term, 0) + 1
        score = 0.0
        for term in q_terms:
            # Casamento por prefixo ajuda com plural/flexões ("migration" ~ "migrations").
            freq = sum(c for t, c in tf.items() if t == term or (len(term) >= 4 and t.startswith(term)))
            if not freq:
                continue
            idf = math.log(1 + (n - df.get(term, 0) + 0.5) / (df.get(term, 0) + 0.5))
            score += idf * freq * (k1 + 1) / (freq + k1 * (1 - b + b * len(doc) / avgdl))
        if score > 0:
            scored.append((score, chunk))
    scored.sort(key=lambda s: s[0], reverse=True)
    return scored


def main() -> int:
    parser = argparse.ArgumentParser(description="Busca trechos relevantes na documentação (BM25 local).")
    parser.add_argument("query", help="pergunta ou palavras-chave")
    parser.add_argument("-k", type=int, default=3, help="quantidade de trechos (padrão: 3)")
    parser.add_argument("--max-lines", type=int, default=25, help="linhas por trecho (padrão: 25)")
    args = parser.parse_args()

    chunks = [c for f in iter_markdown_files() for c in split_chunks(f)]
    results = bm25(chunks, args.query)[: args.k]
    if not results:
        print("Nenhum trecho encontrado. Veja docs/INDEX.md.")
        return 1

    for score, chunk in results:
        rel = chunk.path.relative_to(ROOT)
        print(f"── {rel}:{chunk.start} · {chunk.heading} (score {score:.2f})")
        body = chunk.lines[: args.max_lines]
        print("\n".join(body))
        if len(chunk.lines) > args.max_lines:
            print(f"… (+{len(chunk.lines) - args.max_lines} linhas em {rel}:{chunk.start})")
        print()
    return 0


if __name__ == "__main__":
    sys.exit(main())
