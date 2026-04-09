FROM php:8.4-apache

# Habilitando modulo de reescrita do apache (mod_rewrite)
RUN a2enmod rewrite

# Instalando as dependencias do sistema necessarias para o Composer
RUN apt-get update && apt-get install -y git zip unzip \
    && docker-php-ext-install mysqli pdo pdo_mysql opcache

# Instalando o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuração do PHP
COPY opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copiar o código da aplicação
COPY . /var/www/html/

# Instalar dependências do Composer (sem dev e otimizado)
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Alterando permissões padrao da pasta web
RUN chown -R www-data:www-data /var/www/html

# Update apache document root para a pasta "public" para proteção da API e source
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
