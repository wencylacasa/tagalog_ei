# Use official PHP image with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Copy bot code to container
COPY ./ /var/www/html/

# Enable Apache mod_rewrite (optional, good for routing)
RUN a2enmod rewrite

# Install cURL (required for Gemini API calls)
RUN apt-get update && apt-get install -y curl && rm -rf /var/lib/apt/lists/*

# Expose port 8080 (Render uses $PORT environment variable)
EXPOSE 8080

# Tell Apache to listen on port 8080
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Start Apache in foreground
CMD ["apache2-foreground"]
