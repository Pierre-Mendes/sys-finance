#!/usr/bin/env bash
# Backup do MySQL de produção (roda no serviço "backup" do docker-compose.prod.yml).
#   backup.sh         -> loop: um backup a cada BACKUP_INTERVAL_HOURS (padrão 24h)
#   backup.sh once    -> um backup agora (o deploy chama antes das migrations)
# Arquivos: /backups/<banco>-AAAAMMDD-HHMMSS.sql.gz (pasta ./backups no host), mantidos por BACKUP_RETENTION_DAYS.
set -uo pipefail

dir=/backups
keep_days=${BACKUP_RETENTION_DAYS:-14}
interval_hours=${BACKUP_INTERVAL_HOURS:-24}

run_backup() {
  local out
  out="$dir/${DB_NAME}-$(date +%Y%m%d-%H%M%S).sql.gz"
  if ! mysqldump -h "${DB_HOST:-db}" -u root --single-transaction --quick --routines --triggers \
        --no-tablespaces "$DB_NAME" | gzip -9 > "$out.tmp"; then
    rm -f "$out.tmp"
    echo "[backup] FALHOU em $(date -Is)" >&2
    return 1
  fi
  mv "$out.tmp" "$out"
  find "$dir" -name "*.sql.gz" -mtime +"$keep_days" -delete
  echo "[backup] ok: $out ($(du -h "$out" | cut -f1))"
}

if [ "${1:-}" = "once" ]; then
  run_backup
  exit $?
fi

while true; do
  run_backup || true
  sleep $((interval_hours * 3600))
done
