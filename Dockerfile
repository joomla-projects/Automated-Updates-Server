FROM dunglas/frankenphp
ARG UID
ARG GID

RUN apt-get update && apt-get install -y git

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN install-php-extensions \
    pcntl \
    pdo_mysql \
    zip \
    curl \
    redis

RUN addgroup --gid $GID nonroot && adduser --uid $UID --gid $GID --disabled-password --gecos "" nonroot
USER nonroot

COPY  --chown=nonroot:nonroot . /app
RUN composer install --no-dev

ENTRYPOINT ["php", "artisan", "octane:frankenphp"]
