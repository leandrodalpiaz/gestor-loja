FROM php:8.2-apache

# Habilitar modulos essenciais do Apache
RUN a2enmod rewrite headers

# Configurar o Apache para apontar a raiz do site para a pasta /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configuração robusta para o DocumentRoot permitir rewrite
RUN printf '<Directory /var/www/html/public>\n  Options -MultiViews +FollowSymLinks\n  AllowOverride All\n  Require all granted\n  RewriteEngine On\n  RewriteCond %%{REQUEST_FILENAME} !-f\n  RewriteCond %%{REQUEST_FILENAME} !-d\n  RewriteRule ^(.*)$$ index.php?path=$$1 [QSA,L]\n</Directory>\n' \
    > /etc/apache2/conf-available/php-app.conf \
    && a2enconf php-app

# Configurar ServerName para eliminar warning do Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Instalar extensões essenciais para PostgreSQL e processamento de Imagens (GD)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql gd

# Copiar todos os arquivos para dentro do container
COPY . /var/www/html/

# Dar as permissões corretas para o apache ler os arquivos
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html/public
