FROM dunglas/frankenphp

RUN apt-get update && apt-get install -y git

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN install-php-extensions \
    pcntl \
    pdo_mysql \
    zip \
    curl \
    redis

COPY . /app

RUN composer install --no-dev

ENTRYPOINT ["php", "artisan", "octane:frankenphp"]
