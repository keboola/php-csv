ARG PHP_VERSION=7.4

FROM php:${PHP_VERSION:-7.4}

ARG DEBIAN_FRONTEND=noninteractive
ARG COMPOSER_FLAGS="--prefer-dist --no-interaction"

ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_PROCESS_TIMEOUT 3600

WORKDIR /code/

COPY composer-install.sh /tmp/composer-install.sh

RUN apt-get update -q \
  && apt-get install unzip git wget -y --no-install-recommends \
  && rm -rf /var/lib/apt/lists/* \
  && /tmp/composer-install.sh \
  && rm /tmp/composer-install.sh \
  && mv composer.phar /usr/local/bin/composer

## Composer - deps always cached unless changed
# First copy only composer files
COPY composer.* /code/
# Download dependencies, but don't run scripts or init autoloaders as the app is missing
RUN composer install $COMPOSER_FLAGS --no-scripts --no-autoloader
# copy rest of the app
COPY . /code/
# run normal composer - all deps are cached already
RUN composer install $COMPOSER_FLAGS

CMD bash
