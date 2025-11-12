FROM php:8.1-apache

# Copy project files
COPY . /var/www/html/

# Ensure permissions and enable rewrite module for pretty URLs
RUN chown -R www-data:www-data /var/www/html \
    && a2enmod rewrite

# Expose default HTTP port
EXPOSE 80

# Start Apache in the foreground (default command for php:apache images)
CMD ["apache2-foreground"]
