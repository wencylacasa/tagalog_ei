FROM php:8.2-apache

WORKDIR /var/www/html

COPY ./ /var/www/html/

RUN a2enmod rewrite

# Install cURL for Gemini API
RUN apt-get update && apt-get install -y curl && rm -rf /var/lib/apt/lists/*

# Set DirectoryIndex to chat-bot.php
RUN echo "DirectoryIndex chat-bot.php" >> /etc/apache2/apache2.conf

# Fix permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

EXPOSE 8080

# Apache runs in foreground
CMD ["apache2-foreground"]
