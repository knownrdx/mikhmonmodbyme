FROM php:7.4-fpm

# Install nginx and required PHP extensions
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    && docker-php-ext-install sockets \
    && rm -rf /var/lib/apt/lists/*

# Copy nginx config
COPY nginx.conf /etc/nginx/sites-available/default

# Update nginx config for local PHP-FPM (not separate container)
RUN sed -i 's/php_7_4:9000/127.0.0.1:9000/g' /etc/nginx/sites-available/default

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/

# Create supervisor config to run both nginx and php-fpm
RUN echo '[supervisord]' > /etc/supervisor/conf.d/app.conf && \
    echo 'nodaemon=true' >> /etc/supervisor/conf.d/app.conf && \
    echo '' >> /etc/supervisor/conf.d/app.conf && \
    echo '[program:php-fpm]' >> /etc/supervisor/conf.d/app.conf && \
    echo 'command=php-fpm -F' >> /etc/supervisor/conf.d/app.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/app.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/app.conf && \
    echo '' >> /etc/supervisor/conf.d/app.conf && \
    echo '[program:nginx]' >> /etc/supervisor/conf.d/app.conf && \
    echo 'command=nginx -g "daemon off;"' >> /etc/supervisor/conf.d/app.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/app.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/app.conf

# Update nginx.conf root to /var/www/html
RUN sed -i 's|root /var/www/;|root /var/www/html/;|g' /etc/nginx/sites-available/default

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/app.conf"]
