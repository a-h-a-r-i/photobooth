FROM php:8.4-apache

# Install dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
        build-essential \
        curl \
        git \
        gnupg \
        gphoto2 \
        libimage-exiftool-perl \
        libpng-dev \
        libjpeg-dev \
        libwebp-dev \
        libzip-dev \
        python3 \
        rsync \
        unzip \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install gd zip exif \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache config
RUN echo "LimitRequestLine 12000" > /etc/apache2/conf-available/limits.conf \
    && a2enconf limits \
    && a2enmod rewrite

WORKDIR /var/www/html
COPY . .

# Create data dirs, set permissions
RUN mkdir -p data/images data/thumbs data/tmp data/keying data/qrcodes \
    && touch welcome/.skip_welcome \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 data

# Install and build as www-data
USER www-data
RUN npm install \
    && npm run build:gulp \
    && echo 'render build' > HEAD \
    && composer install --no-dev --optimize-autoloader

USER root
# Point Apache to the app root
RUN sed -i 's|/var/www/html|/var/www/html|g' /etc/apache2/sites-available/000-default.conf

EXPOSE 80
