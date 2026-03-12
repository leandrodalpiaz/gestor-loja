FROM php:8.2-apache

# Habilitar o mod_rewrite do Apache (importante para o nosso .htaccess)
RUN a2enmod rewrite

# Instalar extensões essenciais para conectar com PostgreSQL (Supabase)
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Configurar o Apache para apontar a raiz do site para a pasta /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copiar todos os arquivos para dentro do container
COPY . /var/www/html/

# Dar as permissões corretas para o apache ler os arquivos
RUN chown -R www-data:www-data /var/www/html
