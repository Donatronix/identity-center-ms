FROM alpine:latest
LABEL Maintainer="Ihor Porokhnenko <ihor.porokhnenko@gmail.com>"
LABEL Description="Lightweight container with Nginx & PHP-FPM 8 based on Alpine Linux."

# Do a single run command to make the intermediary containers smaller.
#RUN set -ex

## Update package list
RUN apk update

## Install packages necessary during the build phase
RUN apk --no-cache add \
    mc \
    nano \
    curl \
    nginx \
    php8 \
    php8-intl \
    php8-fpm \
    php8-ctype \
    php8-curl \
    php8-pdo \
    php8-pdo_mysql \
    php8-dom \
    php8-sodium \
    php8-exif \
    php8-fileinfo \
    php8-gd \
    php8-iconv \
    php8-json \
    php8-mbstring \
    php8-opcache \
    php8-openssl \
    php8-pecl-imagick \
    php8-pecl-redis \
    php8-phar \
    php8-session \
    php8-simplexml \
    php8-soap \
    php8-xml \
    php8-xmlreader \
    php8-zip \
    php8-zlib \
    php8-xmlwriter \
    php8-tokenizer \
    supervisor \
    tzdata \
    htop

# Creating symlink php8 => php
RUN ln -s /usr/bin/php8 /usr/bin/php

# Install PHP tools
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN rm -rf /composer-setup.php

## Clean apk cache after all installed packages
RUN rm -rf /var/cache/apk/*

# Configure nginx
RUN rm -rf /etc/nginx/http.d/default.conf
COPY config/nginx.conf /etc/nginx/nginx.conf
COPY config/vhost.conf /etc/nginx/http.d/vhost.conf

# Configure PHP-FPM
COPY config/fpm-pool.conf /etc/php8/php-fpm.d/www.conf
COPY config/php.ini /etc/php8/conf.d/custom.ini

# Configure supervisord
COPY config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Setup document root
RUN mkdir -p /var/www/html

# Make sure files/folders needed by the processes are accessable when they run under the root user
RUN chown -R root:root /var/www/html && \
  chown -R root:root /run && \
  chown -R root:root /var/lib/nginx && \
  chown -R root:root /var/log/nginx

# Switch to use a non-root user from here on
USER root

## Copy existing application directory contents
WORKDIR /var/www/html
COPY --chown=root ./web/ /var/www/html/
#COPY ./web/ /var/www/html/
VOLUME /var/www/html/

## Composer packages install & update
#RUN composer -v install
#RUN composer -v update

RUN mkdir storage/framework/sessions
RUN mkdir storage/framework/views
RUN mkdir storage/framework/cache
# Expose the port nginx is reachable on
EXPOSE 80
#443

# Let supervisord start nginx & php-fpm
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# Configure a healthcheck to validate that everything is up&running
#HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1/fpm-ping
