FROM php:8.2-apache

# Habilitar modulos essenciais do Apache (deflate = compressao gzip das respostas)
RUN a2enmod rewrite headers deflate

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
    && docker-php-ext-install pdo pdo_pgsql gd \
    && docker-php-ext-enable opcache

# OPcache: sem isso o PHP recompila TODO o codigo-fonte a cada request (nao
# ha nenhum cache de bytecode por padrao na imagem php:8.2-apache), o que
# custa CPU/latencia extra em toda pagina, agravado pela CPU limitada do
# plano Render Free. validate_timestamps=1 mantem hot-reload em dev/deploy
# (arquivo mudou -> recompila), so nao recompila em toda requisicao identica.
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=0'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.revalidate_freq=0'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Compressao gzip para respostas JSON/HTML da API (o Angular fica no
# Cloudflare Pages, que ja comprime sozinho; isto cobre as respostas da API).
RUN { \
        echo '<IfModule mod_deflate.c>'; \
        echo '  AddOutputFilterByType DEFLATE application/json text/html text/css application/javascript text/plain'; \
        echo '</IfModule>'; \
    } > /etc/apache2/conf-available/deflate-app.conf \
    && a2enconf deflate-app

# Copiar todos os arquivos para dentro do container
COPY . /var/www/html/

# Dar as permissões corretas para o apache ler os arquivos
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html/public
