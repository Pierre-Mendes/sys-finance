# Estágios:
#   frontend-builder → build do Vue (só produção)
#   php-base         → PHP 8.4 + Apache com extensões e hardening (comum a dev e produção)
#   backend-dev      → desenvolvimento: código montado por volume, migrations no start (docker-compose.yml)
#   production       → imagem final com API + SPA buildada (último estágio = alvo padrão do build)

# --- ESTÁGIO 1: Build do Frontend (Node.js) ---
FROM node:24-alpine AS frontend-builder
WORKDIR /app
COPY frontend/package*.json ./
RUN npm install
COPY frontend/ ./
# Passar a URL da API para o build do frontend (usando o IP do servidor)
ARG VITE_API_BASE_URL
ENV VITE_API_BASE_URL=$VITE_API_BASE_URL
# DSN do Sentry/GlitchTip do frontend (público por natureza; os eventos passam pelo túnel /api/monitoring/sentry)
ARG VITE_SENTRY_DSN
ENV VITE_SENTRY_DSN=$VITE_SENTRY_DSN
RUN npm run build

# --- ESTÁGIO 2: Base PHP/Apache ---
FROM php:8.4-apache AS php-base
WORKDIR /var/www/html

# Habilitando modulo de reescrita do apache (mod_rewrite)
# O security.conf do Debian (ServerTokens OS) é carregado depois de confs com nome "menor",
# então ajustamos o próprio arquivo e ainda garantimos um override que carrega por último (zz-).
RUN a2enmod rewrite headers \
    && sed -ri 's/^\s*ServerTokens\s+.*/ServerTokens Prod/; s/^\s*ServerSignature\s+.*/ServerSignature Off/; s/^\s*TraceEnable\s+.*/TraceEnable Off/' /etc/apache2/conf-available/security.conf \
    && printf 'ServerTokens Prod\nServerSignature Off\nTraceEnable Off\n' > /etc/apache2/conf-available/zz-security-hardening.conf \
    && a2enconf zz-security-hardening

# Instalando as dependencias do sistema necessarias para o Composer
RUN apt-get update && apt-get install -y git zip unzip poppler-utils \
    && docker-php-ext-install mysqli pdo pdo_mysql opcache bcmath

# Instalando o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuração do PHP
COPY opcache.ini /usr/local/etc/php/conf.d/opcache.ini
RUN printf 'expose_php = Off\n' > /usr/local/etc/php/conf.d/security.ini

# Configurar diretório seguro para o Git
RUN git config --global --add safe.directory /var/www/html

# Update apache document root para a pasta "public"
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configurar Apache para não remover variáveis de ambiente
# CSP_CONNECT_SRC precisa de um valor padrão: o Apache escreve "(null)" no CSP se a variável não existir.
ENV CSP_CONNECT_SRC="https://*.sentry.io"
RUN echo "PassEnv DB_HOST DB_USER DB_PASS DB_NAME APP_ENV APP_TIMEZONE JWT_SECRET JWT_TTL SENTRY_DSN SENTRY_FRONTEND_DSN SENTRY_TRACES_SAMPLE_RATE APP_RELEASE CORS_ALLOWED_ORIGINS CSP_CONNECT_SRC VAPID_SUBJECT VAPID_PUBLIC_KEY VAPID_PRIVATE_KEY COOKIE_SECURE COOKIE_SAMESITE TELEGRAM_BOT_TOKEN TELEGRAM_BOT_USERNAME TELEGRAM_WEBHOOK_SECRET APP_URL TRUSTED_PROXIES" > /etc/apache2/conf-available/passenv.conf \
    && a2enconf passenv

# --- ESTÁGIO 3: Desenvolvimento (docker-compose.yml) ---
# O código vem por volume; o entrypoint instala o Composer e roda as migrations.
# Roda como www-data (sem root): Apache escuta na 8080 e vendor/ e storage/ são volumes do container.
FROM php-base AS backend-dev
RUN sed -ri 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf \
    && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf \
    && mkdir -p /var/www/html/vendor /var/www/html/storage/temp /tmp/composer \
    && chown -R www-data:www-data /var/www/html /tmp/composer
COPY --chmod=755 docker/dev/backend-entrypoint.sh /usr/local/bin/backend-entrypoint
ENV COMPOSER_HOME=/tmp/composer
USER www-data
EXPOSE 8080
ENTRYPOINT ["backend-entrypoint"]
CMD ["apache2-foreground"]

# --- ESTÁGIO 4: Produção (alvo padrão) ---
FROM php-base AS production

# Copiar o código do Backend
COPY . .

# Copiar o build do Frontend para a pasta pública do PHP
COPY --from=frontend-builder /app/dist/ ./public/

# Instalar dependências do Composer (sem dev e otimizado)
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Criar pastas de sistema e ajustar permissões
RUN mkdir -p logs storage/temp && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html/logs /var/www/html/storage/temp
