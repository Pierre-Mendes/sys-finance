#!/usr/bin/env bash
# Restaura um backup no banco de produção. SOBRESCREVE os dados atuais.
#   docker exec -it financas-backup-prod restore.sh /backups/<arquivo>.sql.gz
set -euo pipefail

file=${1:?Informe o arquivo: restore.sh /backups/<arquivo>.sql.gz}
[ -f "$file" ] || { echo "Arquivo não encontrado: $file" >&2; exit 1; }

if [ "${2:-}" != "--yes" ]; then
  read -r -p "Isto apaga os dados atuais de '$DB_NAME' e restaura '$file'. Digite RESTAURAR para continuar: " answer
  [ "$answer" = "RESTAURAR" ] || { echo "Cancelado."; exit 1; }
fi

gunzip -c "$file" | mysql -h "${DB_HOST:-db}" -u root "$DB_NAME"
echo "[restore] ok: $file -> $DB_NAME"
