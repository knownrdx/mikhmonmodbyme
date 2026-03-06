FROM php:7.4-fpm

# Install nginx, supervisor, and required PHP extensions
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    libsqlite3-dev \
    && docker-php-ext-install sockets pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Enable SQLite3 extension (built-in but ensure it's there)
RUN docker-php-ext-enable pdo_sqlite

# Copy nginx config
COPY nginx.conf /etc/nginx/sites-available/default

# Copy application files
COPY . /var/www/html/

# Create data directory for SQLite database with proper permissions
RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/ \
    && chmod -R 777 /var/www/html/data

# Create supervisor config
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

EXPOSE 80

# Use volume for persistent database
VOLUME ["/var/www/html/data"]

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/app.conf"]
