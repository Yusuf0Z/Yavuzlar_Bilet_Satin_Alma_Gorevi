FROM php:8.1-apache

# Sistem paketlerini güncelle ve gerekli paketleri kur
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# SQLite ve diğer PHP extension'larını kur
RUN docker-php-ext-install pdo pdo_sqlite

# Apache modüllerini etkinleştir
RUN a2enmod rewrite

# Çalışma dizinini ayarla
WORKDIR /var/www/html

# Dosyaları kopyala
COPY . .

# Dosya izinlerini ayarla
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# SQLite veritabanı dosyası için yazma izni
RUN touch /var/www/html/yavuzlar.db \
    && chown www-data:www-data /var/www/html/yavuzlar.db \
    && chmod 666 /var/www/html/yavuzlar.db

EXPOSE 80