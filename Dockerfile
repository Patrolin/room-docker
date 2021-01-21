FROM php:7.4-alpine
RUN docker-php-ext-install sockets
RUN docker-php-ext-install pdo_mysql
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY ./room /app
WORKDIR /app

CMD [ "php", "main.php" ]
