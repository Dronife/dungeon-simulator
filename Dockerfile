# Use PHP 8.4 FPM image
FROM php:8.4-fpm
WORKDIR /var/www

# ============ CORE (Laravel minimum) ============
RUN apt-get update && apt-get install -y \
    git unzip curl \
    libzip-dev libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ============ PDF EXPORT (Puppeteer/Chromium) ============
# chromium chromium-sandbox fonts-liberation \
# libasound2 libatk-bridge2.0-0 libdrm2 libgtk-3-0 \
# libnspr4 libnss3 libx11-xcb1 libxcomposite1 \
# libxdamage1 libxrandr2 xdg-utils libxss1 \
# ca-certificates gnupg \
# ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium
# ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true

# ============ PDF MANIPULATION (merge/split) ============
# pdftk ghostscript

# ============ FRONTEND BUILD (if building assets in container) ============
# curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
# && apt-get install -y nodejs

# ============ BACKGROUND JOBS (queues/workers) ============
# supervisor
# bcmath (if doing precise math for game mechanics)

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN chown -R www-data:www-data /var/www

EXPOSE 9000
CMD ["php-fpm"]