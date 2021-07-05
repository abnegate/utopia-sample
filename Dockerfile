FROM php:8.0-cli-alpine as ext

ENV PHP_SWOOLE_VERSION "v4.6.7"

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
  cd .. \

RUN pecl install mongodb redis \
    && docker-php-ext-enable swoole mongodb redis

FROM ext as build
RUN curl -sS https://getcomposer.org/installer | \
    php -- --install-dir=/usr/bin --filename=composer

COPY composer.json composer.lock ./
RUN composer install

FROM ext as final
COPY --from=build ./vendor ./
COPY app ./app
ENTRYPOINT ["php", "app/app.php"]



