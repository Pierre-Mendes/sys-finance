#!/bin/sh
# Entrypoint do backend em desenvolvimento: prepara dependências e banco antes de subir o Apache.
set -e
cd /var/www/html

# vendor/ fica num volume do Docker: instala na primeira subida e quando o composer.lock muda.
if [ ! -f vendor/autoload.php ] || [ ! -f vendor/.installed ] || [ composer.lock -nt vendor/.installed ]; then
    composer install --no-interaction --prefer-dist --no-progress
    touch vendor/.installed
fi

# Sem permissão de escrita em logs/ (pasta do host), a API manda os logs para o stderr: `docker compose logs backend`.

# O healthcheck do MySQL pode passar enquanto o servidor de inicialização ainda roda: tenta por até ~60 s.
if [ "${RUN_MIGRATIONS:-1}" = "1" ]; then
    tries=0
    until php vendor/bin/phinx migrate -e development; do
        tries=$((tries + 1))
        [ "$tries" -ge 20 ] && echo "Migrations falharam" >&2 && exit 1
        echo "Banco ainda não está pronto, tentando de novo em 3 s..."
        sleep 3
    done
fi

exec "$@"
