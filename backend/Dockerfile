FROM alpine:3.18 AS builder

RUN apk add --no-cache bash

FROM php:8.2-cli-alpine

RUN apk add --no-cache bash \
    && docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html

COPY index.php config.php items.php ./
COPY wait-for-it.sh /usr/local/bin/wait-for-it.sh

RUN chmod +x /usr/local/bin/wait-for-it.sh

EXPOSE 8000

CMD ["sh", "-c", "wait-for-it.sh mysql_contenidor:3306 -- php -S 0.0.0.0:8000 index.php"]

