FROM php:8.1-apache

RUN apt-get update \
    && apt-get install -y libzip-dev unzip git \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

COPY . /var/www/html/

RUN if [ -d /var/www/html/uploads ]; then chown -R www-data:www-data /var/www/html/uploads; fi

EXPOSE 80

CMD ["apache2-foreground"]
