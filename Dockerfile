FROM php:8.0-cli-alpine as base

ENV PHP_SWOOLE_VERSION="v4.6.7" \
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

RUN git clone --depth 1 --branch "${PHP_SWOOLE_VERSION}" https://github.com/swoole/swoole-src.git && \
  cd swoole-src && \
  phpize && \
  ./configure --enable-http2 && \
  make && make install && \
  cd ..

RUN docker-php-ext-install \
    pdo \
    pdo_mysql

RUN pecl install \
    mongodb \
    redis

RUN docker-php-ext-enable \
    swoole \
    pdo \
    pdo_mysql \
    mongodb \
    redis

FROM base as build
RUN curl -sS https://getcomposer.org/installer | \
    php -- --install-dir=/usr/bin --filename=composer
COPY composer.json .
COPY composer.lock .
RUN composer install

FROM base as final
COPY --from=build /vendor vendor
COPY src /src
COPY app /app
COPY bin /usr/local/bin

EXPOSE 80

ENTRYPOINT ["php", "app/api.php"]
