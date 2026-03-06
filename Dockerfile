FROM php:7.4-fpm

# Install nginx, supervisor, SQLite, and PHP sockets extension
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    libsqlite3-dev \
    curl \
    && docker-php-ext-install sockets pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Remove default nginx site
RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/sites-available/default

# Copy nginx config
COPY nginx.conf /etc/nginx/sites-enabled/default

# Copy application files
COPY . /var/www/html/

# Create data directory and set permissions
RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/ \
    && chmod -R 777 /var/www/html/data \
    && chmod -R 777 /var/www/html/img

# Create supervisor config to run both nginx and php-fpm
COPY <<'EOF' /etc/supervisor/conf.d/app.conf
[supervisord]
nodaemon=true
user=root

[program:php-fpm]
command=php-fpm -F
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:nginx]
command=nginx -g "daemon off;"
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
EOF

# Expose port 80 for Coolify
EXPOSE 80

# Persistent database storage
VOLUME ["/var/www/html/data"]

# Health check for Coolify
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s \
    CMD curl -f http://localhost/admin.php || exit 1

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/app.conf"]
