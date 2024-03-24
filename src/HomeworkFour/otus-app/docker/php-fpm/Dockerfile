FROM php:8.3-fpm-alpine

WORKDIR /app

ENV TZ=Europe/Moscow
RUN apk add --no-cache git patch openssh-client bash nano tzdata nano dpkg \
    && printf '[PHP]\ndate.timezone = "%s"\n', ${TZ} > /usr/local/etc/php/conf.d/tzone.ini \
    && curl -sSLf -o /usr/local/bin/install-php-extensions https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
	&& chmod +x /usr/local/bin/install-php-extensions

RUN install-php-extensions pgsql gd intl zip @composer-2.4.4 \
    calendar gettext mcrypt pcntl pdo_pgsql xsl opcache

# Install mbstring extension
RUN apk add --no-cache \
    oniguruma-dev \
    && docker-php-ext-install mbstring \
    && docker-php-ext-enable mbstring \
    && rm -rf /tmp/*

RUN apk add --no-cache icu-dev

RUN docker-php-ext-install intl > /dev/null
