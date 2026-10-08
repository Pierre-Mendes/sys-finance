#!/usr/bin/env bash
# Publica uma versão do sysfinance no servidor (homelab ou VPS), a partir da pasta do repositório.
#
#   scripts/deploy.sh            publica o main
#   scripts/deploy.sh v1.2.0     publica uma tag, branch ou SHA (para voltar atrás, informe o SHA anterior)
#
# Passos: backup do banco -> checkout da versão -> build -> migrations -> checagem de saúde.
# Se algo falhar, volta o código para a última versão saudável (.last_good_deploy). O banco não é
# restaurado sozinho (para não perder dados): o comando de restauração do backup é mostrado no fim.
#
# Usado também pelo workflow "Deploy to Production" (.github/workflows/deploy-prod.yml).

# Tudo dentro de main(): o bash lê o script inteiro antes de rodar, então o checkout de outra versão
# (que pode trocar este arquivo) não corrompe a execução em andamento.
main() {
  set -euo pipefail

  local ref="${1:-main}"
  case "$ref" in
    *[!A-Za-z0-9._/-]*|"") echo "ref inválido: $ref" >&2; return 1 ;;
  esac

  # Pasta do repositório: a do script, ou SYSFINANCE_DIR quando o script roda de uma cópia (workflow)
  cd "${SYSFINANCE_DIR:-$(dirname "$0")/..}"
  if [ ! -f .env ]; then
    echo "Falta o .env (copie de .env.example e preencha; veja docs/guides/homelab.md)." >&2
    return 1
  fi

  local compose="docker compose -f docker-compose.prod.yml"
  # GlitchTip (monitoramento de erros) entra quando configurado no .env
  if grep -Eq '^GLITCHTIP_SECRET_KEY=.+' .env; then
    compose="$compose -f docker-compose.monitoring.yml"
  fi

  # Checagem de saúde pela porta publicada (WEB_BIND do .env: "80" ou "127.0.0.1:8080")
  local bind health_url
  bind=$(grep -E '^WEB_BIND=' .env | tail -1 | cut -d= -f2- | tr -d '"'"'"' ' || true)
  bind="${bind:-80}"
  case "$bind" in
    *:*) health_url="http://$bind/api/metrics" ;;
    *)   health_url="http://127.0.0.1:$bind/api/metrics" ;;
  esac

  healthy() {
    local i
    for i in $(seq 1 30); do
      if curl -fsS -o /dev/null "$health_url"; then return 0; fi
      sleep 2
    done
    return 1
  }

  # Versão que está no ar agora (para voltar atrás se a nova falhar)
  local prev new target
  prev=$(cat .last_good_deploy 2>/dev/null || git rev-parse HEAD)

  git fetch --tags origin
  if git rev-parse -q --verify "origin/$ref^{commit}" >/dev/null; then
    target="origin/$ref"
  else
    target="$ref"
  fi
  git checkout -q --detach "$target"
  new=$(git rev-parse HEAD)
  echo "Publicando $new (anterior: $prev)"

  # phpMyAdmin nunca fica no ar depois de um deploy (só sob demanda, via túnel SSH)
  docker rm -f financas-pma-prod >/dev/null 2>&1 || true

  # Backup antes de qualquer migration
  $compose up -d db backup
  docker exec financas-backup-prod backup.sh once

  if $compose up -d --build \
     && docker exec financas-web-prod php vendor/bin/phinx migrate -e production \
     && healthy; then
    echo "$new" > .last_good_deploy
    docker image prune -f >/dev/null
    echo "Deploy OK: $new"
    return 0
  fi

  echo "Deploy de $new falhou. Voltando o código para $prev." >&2
  git checkout -q --detach "$prev"
  $compose up -d --build
  healthy || echo "A versão anterior também não respondeu. Verifique os logs: $compose logs web" >&2
  local last_backup
  last_backup=$(ls -1t backups/*.sql.gz 2>/dev/null | head -1 || true)
  if [ -n "$last_backup" ]; then
    echo "Se a migration alterou o banco, restaure o backup feito antes do deploy:" >&2
    echo "  docker exec -it financas-backup-prod restore.sh /$last_backup" >&2
  fi
  return 1
}

main "$@"; exit $?
