FROM webdevops/php-apache:8.4

# Adjust LimitRequestLine and install dependencies
RUN echo "LimitRequestLine 12000" > /opt/docker/etc/httpd/conf.d/limits.conf \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        build-essential \
        git \
        gphoto2 \
        libimage-exiftool-perl \
        rsync \
        udisks2 \
        python3 \
        ca-certificates \
        curl \
        gnupg \
        nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy files
WORKDIR /app
COPY . .

# Create data dirs, fix npm cache ownership, set permissions — all as root
RUN mkdir -p /app/data/images /app/data/thumbs /app/data/tmp /app/data/keying /app/data/qrcodes \
    && mkdir -p /app/.npm-cache \
    && chown -R application:application /app \
    && chmod -R 777 /app/data \
    && touch /app/welcome/.skip_welcome

# Switch to application user (Render does not allow root at runtime)
USER application

# Point npm cache to a user-writable location
ENV NPM_CONFIG_CACHE=/app/.npm-cache

# Install and build
RUN npm install \
    && npm run build:gulp \
    && echo 'render build' > HEAD \
    && php bin/composer install --no-dev --optimize-autoloader
