FROM php:8.4-apache

# Install dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
        build-essential \
        curl \
        git \
        gphoto2 \
        gnupg \
        ca-certificates \
        libimage-exiftool-perl \
        libpng-dev \
        libjpeg-dev \
        libwebp-dev \
        libzip-dev \
        python3 \
        rsync \
        unzip \
        udisks2 \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install gd zip exif \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache config
RUN echo "LimitRequestLine 12000" > /etc/apache2/conf-available/limits.conf \
    && a2enconf limits \
    && a2enmod rewrite

# Set document root
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -i 's|/var/www/html|/var/www/html|g' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . .

# Create data dirs, fix permissions
RUN mkdir -p data/images data/thumbs data/tmp data/keying data/qrcodes \
    && mkdir -p .npm-cache \
    && touch welcome/.skip_welcome \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 data

ENV NPM_CONFIG_CACHE=/var/www/html/.npm-cache

# Build as www-data
USER www-data
RUN npm install \
    && npm run build:gulp \
    && echo 'render build' > HEAD \
    && composer install --no-dev --optimize-autoloader

USER root
EXPOSE 80
CMD ["apache2-foreground"]
