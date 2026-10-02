# --- ESTÁGIO 1: Build do Frontend (Node.js) ---
FROM node:20-alpine AS frontend-builder
WORKDIR /app
COPY frontend/package*.json ./
RUN npm install
COPY frontend/ ./
# Passar a URL da API para o build do frontend (usando o IP do servidor)
ARG VITE_API_BASE_URL
ENV VITE_API_BASE_URL=$VITE_API_BASE_URL
RUN npm run build

# --- ESTÁGIO 2: Servidor Backend e Hospedagem (PHP/Apache) ---
FROM php:8.4-apache
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

# Copiar o código do Backend
COPY . .

# Copiar o build do Frontend para a pasta pública do PHP
COPY --from=frontend-builder /app/dist/ ./public/

# Configurar diretório seguro para o Git
RUN git config --global --add safe.directory /var/www/html

# Instalar dependências do Composer (sem dev e otimizado)
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Criar pastas de sistema e ajustar permissões
RUN mkdir -p logs storage/temp && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html/logs /var/www/html/storage/temp

# Update apache document root para a pasta "public"
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configurar Apache para não remover variáveis de ambiente
# CSP_CONNECT_SRC precisa de um valor padrão: o Apache escreve "(null)" no CSP se a variável não existir.
ENV CSP_CONNECT_SRC="https://*.sentry.io"
RUN echo "PassEnv DB_HOST DB_USER DB_PASS DB_NAME APP_ENV APP_TIMEZONE JWT_SECRET JWT_TTL SENTRY_DSN CORS_ALLOWED_ORIGINS CSP_CONNECT_SRC VAPID_SUBJECT VAPID_PUBLIC_KEY VAPID_PRIVATE_KEY COOKIE_SECURE COOKIE_SAMESITE" > /etc/apache2/conf-available/passenv.conf \
    && a2enconf passenv
