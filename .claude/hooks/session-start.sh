#!/bin/bash
# Prepara sessões do Claude Code on the web: dependências para testes/lint e índices
# de código (CodeGraph + Serena) para o agente gastar menos tokens navegando no projeto.
set -euo pipefail

# Só no ambiente remoto (web); localmente cada dev controla o próprio ambiente.
if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
  exit 0
fi

cd "${CLAUDE_PROJECT_DIR:-$(pwd)}"

export COMPOSER_ALLOW_SUPERUSER=1
export CODEGRAPH_TELEMETRY=0
export DO_NOT_TRACK=1

CODEGRAPH="@colbymchenry/codegraph@1.6.1"
SERENA="git+https://github.com/oraios/serena@8a3ce35cae29a93748842ba463231d6c78d181e7"

echo "[session-start] composer install"
composer install --no-interaction --no-progress --prefer-dist

echo "[session-start] npm install (frontend)"
(cd frontend && npm install --no-audit --no-fund)

echo "[session-start] CodeGraph index"
if [ -f .codegraph/codegraph.db ]; then
  npx -y "$CODEGRAPH" sync .
else
  npx -y "$CODEGRAPH" init -y .
fi

# Serena: baixa os language servers (PHP/Vue) e aquece o cache de símbolos.
# Não é fatal: o MCP do Serena também indexa sob demanda.
if command -v uvx >/dev/null 2>&1; then
  echo "[session-start] Serena index"
  uvx --from "$SERENA" serena project index . --log-level WARNING \
    || echo "[session-start] aviso: indexação do Serena falhou; seguirá sob demanda"
else
  echo "[session-start] aviso: uvx não encontrado; MCP do Serena ficará indisponível"
fi

echo "[session-start] pronto"
