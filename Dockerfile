FROM php:8.1-apache

# Install PHP extensions and system dependencies
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    cron \
    default-mysql-client \
    xxd \
    python3 \
    python3-pip \
    python3-venv \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        mysqli \
        pdo_mysql \
        zip \
        intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules (access_compat needed for .htaccess Order/Allow/Deny directives)
RUN a2enmod rewrite headers expires deflate access_compat

# PHP configuration
RUN echo "upload_max_filesize = 64M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 64M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_input_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "display_errors = On" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/uploads.ini

# Apache configuration - allow .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . /var/www/html/

# Keep install/mysql for entrypoint SQL migrations
# Remove the web installer only
RUN rm -f /var/www/html/install/index.php /var/www/html/install/install.php 2>/dev/null || true

# Set up Python virtual environment for pipeline
RUN python3 -m venv /var/www/html/scripts/venv \
    && /var/www/html/scripts/venv/bin/pip install --no-cache-dir -r /var/www/html/scripts/requirements.txt

# Create required directories
RUN mkdir -p \
    /var/www/html/tmp/aCompile \
    /var/www/html/tmp/compile \
    /var/www/html/tmp/cache \
    /var/www/html/tmp/errorLog \
    /var/www/html/tmp/upload \
    /var/www/html/files \
    /var/www/html/scripts/data \
    /var/www/html/scripts/logs \
    /var/www/html/data

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/tmp \
    && chmod -R 777 /var/www/html/files \
    && chmod -R 777 /var/www/html/plugins \
    && chmod -R 777 /var/www/html/scripts/data \
    && chmod -R 777 /var/www/html/scripts/logs

# Copy cron configuration (strip Windows CRLF line endings)
COPY deploy/crontab /etc/cron.d/solar-pipeline
RUN sed -i 's/\r$//' /etc/cron.d/solar-pipeline \
    && chmod 0644 /etc/cron.d/solar-pipeline && crontab /etc/cron.d/solar-pipeline

# Startup script (strip Windows CRLF line endings)
COPY deploy/entrypoint.sh /entrypoint.sh
RUN sed -i 's/\r$//' /entrypoint.sh && chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
