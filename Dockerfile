FROM jitesoft/php:8.1-cli AS base_img
# Hash for composer 2.9.5
ARG COMPOSER_HASH=c8b085408188070d5f52bcfe4ecfbee5f727afa458b2573b8eaaf77b3419b0bf2768dc67c86944da1544f06fa544fd47

RUN apk update && apk upgrade \
    && apk add git

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions mailparse xdebug

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    COMPOSER_HASH=${COMPOSER_HASH} php -r "if (hash_file('sha384', 'composer-setup.php') === getenv('COMPOSER_HASH') ) { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');"

RUN mv composer.phar /usr/local/bin/composer

WORKDIR /app/