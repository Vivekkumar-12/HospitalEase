FROM php:8.1-apache

# Install PHP extensions (PDO, MySQL, GD, etc.)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql gd mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy project files to Apache root
COPY . /var/www/html/

# Create .htaccess to ensure PHP files are executed
RUN echo '<IfModule mod_rewrite.c>' > /var/www/html/.htaccess && \
    echo 'RewriteEngine On' >> /var/www/html/.htaccess && \
    echo 'RewriteCond %{REQUEST_FILENAME} !-f' >> /var/www/html/.htaccess && \
    echo 'RewriteCond %{REQUEST_FILENAME} !-d' >> /var/www/html/.htaccess && \
    echo 'RewriteRule ^(.*)$ index.php [QSA,L]' >> /var/www/html/.htaccess && \
    echo '</IfModule>'

# Ensure proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Enable Apache modules required for PHP execution
RUN a2enmod rewrite \
    && a2enmod php8.1

# Configure Apache to serve index.php by default
RUN echo 'DirectoryIndex index.php index.html index.htm' >> /etc/apache2/mods-available/dir.conf

# Expose HTTP port
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
