FROM php:7.4-alpine
RUN docker-php-ext-install sockets
RUN docker-php-ext-install pdo_mysql
RUN apk update
RUN apk upgrade
RUN apk add --no-cache --update --virtual buildDeps autoconf g++ make
RUN pecl install ds
RUN docker-php-ext-enable ds
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY ./room /app
WORKDIR /app

CMD [ "php", "main.php" ]
