FROM php:8.1-cli-alpine as base

ENV PHP_SWOOLE_VERSION="v4.8.0" \
    _APP_DB_HOST="localhost" \
    _APP_DB_PORT=3306 \
    _APP_DB_USER="root" \
    _APP_DB_PASS="" \
    _APP_DB_SCHEMA="test"

RUN apk upgrade --update \
    && apk add --no-cache --virtual .build-deps \
    make \
    automake \
    autoconf \
    gcc \
    g++ \
    git \
    zlib-dev \
    brotli-dev \
    yaml-dev

RUN pecl install \
    mongodb \
    redis \
    swoole

RUN docker-php-ext-install \
    pdo \
    pdo_mysql

RUN docker-php-ext-enable \
    swoole \
    pdo \
    pdo_mysql \
    mongodb \
    redis

#FROM base as swoole
#
#RUN git clone --depth 1 --branch "${PHP_SWOOLE_VERSION}" https://github.com/swoole/swoole-src.git && \
#  cd swoole-src && \
#  phpize && \
#  ./configure --enable-http2 && \
#  make && make install && \
#  cd ..

FROM base as composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
COPY composer.json .
COPY composer.lock .
RUN composer install --optimize-autoloader

FROM base as final

COPY --from=composer /vendor /opt/vendor

#COPY --from=swoole /usr/local/lib/php/extensions/no-debug-non-zts-20200930/swoole.so /usr/local/lib/php/extensions/no-debug-non-zts-20200930/yasd.so* /usr/local/lib/php/extensions/no-debug-non-zts-20200930/

COPY ./src /opt/src
COPY ./app /opt/app
COPY bin /usr/local/bin/

RUN chmod +x /usr/local/bin/graphql && \
    chmod +x /usr/local/bin/rest

WORKDIR /opt

EXPOSE 80
