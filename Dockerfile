# Dockerfile
FROM php:7.4-apache

# Apache mod_rewrite'ı etkinleştir
RUN a2enmod rewrite

# Çalışma dosyalarını kopyala
COPY ./src /var/www/html/

# Images və uploads qovluqlarını yarat və icazələri təyin et
RUN mkdir -p /var/www/html/images && \
    mkdir -p /var/www/html/uploads && \
    chown -R www-data:www-data /var/www/html/images && \
    chown -R www-data:www-data /var/www/html/uploads && \
    chmod -R 755 /var/www/html/images && \
    chmod -R 777 /var/www/html/uploads

# Session dizini oluştur ve izinleri ayarla
RUN mkdir -p /var/lib/php/sessions && \
    chown -R www-data:www-data /var/lib/php/sessions && \
    chmod -R 755 /var/lib/php/sessions

# İzinleri ayarla
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# PHP eklentilerini ve gerekli paketleri kur
RUN apt-get update && apt-get install -y \
    default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql mysqli

# PHP upload limitlərini artır
RUN echo "upload_max_filesize = 10M" > /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/uploads.ini

# MySQL bağlantısı için bekleme script'ini ekle
COPY wait-for-mysql.sh /usr/local/bin/wait-for-mysql.sh
RUN chmod +x /usr/local/bin/wait-for-mysql.sh

EXPOSE 80
