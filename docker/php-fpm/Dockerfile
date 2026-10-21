FROM php:8.3-fpm-trixie
ARG UID
ARG GID

WORKDIR "/application"

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libcurl4-openssl-dev \
        libzip-dev \
        zlib1g-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mysqli \
        curl \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove \
        libcurl4-openssl-dev \
        libzip-dev \
        zlib1g-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN addgroup --gid $GID nonroot && adduser --uid $UID --gid $GID --disabled-password --gecos "" nonroot


