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

# Create data dirs, set permissions, skip welcome — all as root before switching user
RUN mkdir -p /app/data/images /app/data/thumbs /app/data/tmp /app/data/keying /app/data/qrcodes \
    && chown -R application:application /app \
    && chmod -R 777 /app/data \
    && touch /app/welcome/.skip_welcome

# Switch to application user (required by Render — no root at runtime)
USER application

# Install and build
RUN npm install \
    && npm run build:gulp \
    && echo 'render build' > HEAD \
    && php bin/composer install --no-dev --optimize-autoloader
