FROM webdevops/php:8.2-alpine

# php-event extension
RUN \
    apk add autoconf openssl-dev && apk add build-base &&\
    apk add linux-headers && apk add libevent-dev && apk add openldap-dev && apk add imagemagick-dev && \
    docker-php-ext-install opcache && docker-php-ext-enable opcache && \
    pecl install xdebug && docker-php-ext-enable xdebug && \
    apk add curl && \
    echo '' | pecl install event && \
    docker-php-ext-enable --ini-name zz-event.ini event

# SWOW extension
RUN \
    apk add --no-cache curl-dev && \    
    git clone https://github.com/swow/swow.git &&\
    cd swow/ext && \
    phpize && ./configure && make && \
    make install && \
    docker-php-ext-enable swow

WORKDIR "/"