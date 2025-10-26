FROM phpdockerio/php:8.3-fpm
ARG UID
ARG GID

WORKDIR "/application"

RUN apt-get update \
    && apt-get -y --no-install-recommends install \
        php8.3-mysql \
        php8.3-redis \
        php8.3-curl \
        php8.3-zip \
        git \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN addgroup --gid $GID nonroot && adduser --uid $UID --gid $GID --disabled-password --gecos "" nonroot


