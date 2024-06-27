# image
FROM php:8.2-apache

# Set php
COPY docker/php/php.ini /usr/local/etc/php/

COPY . /var/www/html

# Set apache
COPY docker/php/apache2.conf /etc/apache2/
COPY docker/php/sites/*.conf /etc/apache2/sites-available/
RUN mv /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled
RUN /bin/sh -c a2enmod rewrite

WORKDIR /var/www/html

RUN chmod 777 storage -R

# Install
RUN apt-get update && apt-get install -y vim libonig-dev zip git curl wget librdkafka-dev
RUN pecl install rdkafka
RUN docker-php-ext-enable rdkafka

# composer
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

RUN apt-get update \
   # pgsql headers
    && apt-get install -y libpq-dev \
    && docker-php-ext-install pgsql pdo_pgsql pdo

RUN apt-get clean

RUN pecl install apcu

COPY docker/php/conf.d/ext-apcu.ini /usr/local/etc/php/conf.d/

# KAFKA
RUN echo "extension=rdkafka.so" > /usr/local/etc/php/conf.d/rdkafka.ini
