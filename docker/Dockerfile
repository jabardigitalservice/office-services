FROM registry.digitalservice.id/proxyjds/library/alpine:3.13

LABEL Maintainer="Firman Ayocoding <ayocodingit@gmail.com>" \
    Description="Lightweight container with Nginx 1.16 & PHP-FPM 8.0 based on Alpine Linux (forked from trafex/alpine-nginx-php8)."

# Fix iconv issue when generate pdf
RUN apk --no-cache add --repository http://dl-cdn.alpinelinux.org/alpine/v3.13/community/ gnu-libiconv
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php

# Install packages
RUN apk --no-cache add \
    php8 \
    php8-phar \
    php8-opcache \
    php8-ctype \
    php8-curl \
    php8-dom \
    php8-iconv \
    php8-intl \
    php8-mbstring \
    php8-pdo_mysql \
    php8-pdo_sqlite \
    php8-openssl \
    php8-redis \
    php8-session \
    php8-zip \
    php8-zlib \
    php8-sqlite3 \
    php8-gd \
    php8-xml \
    php8-xmlreader \
    php8-simplexml \
    php8-xmlwriter \
    php8-fileinfo \
    php8-tokenizer \
    php8-sockets \
    php8-pcntl \
    php8-sodium \
    php8-fpm \
    nginx \
    supervisor \
    curl

# https://github.com/codecasts/php-alpine/issues/21
RUN ln -s /usr/bin/php8 /usr/bin/php

# Configure docker-entrypoint
# COPY docker-config/docker-entrypoint.sh /usr/local/bin/start
# RUN chmod +x /usr/local/bin/start

# Install new relic
RUN curl -L "https://download.newrelic.com/php_agent/archive/9.21.0.311/newrelic-php5-9.21.0.311-linux.tar.gz" | tar -C /tmp -zx \
 && export NR_INSTALL_SILENT=1 \
 && export NR_INSTALL_USE_CP_NOT_LN=1 \
 && export NR_INSTALL_ARCH=x64 \
 && /tmp/newrelic-php5-*/newrelic-install install \
 && rm -rf /tmp/newrelic-php5-* /tmp/nrinstall*

# Configure nginx
COPY docker/docker-config/nginx.conf /etc/nginx/nginx.conf

# Remove default server definition
RUN rm /etc/nginx/conf.d/default.conf

# Configure PHP-FPM
COPY docker/docker-config/fpm-pool.conf /etc/php8/php-fpm.d/www.conf
COPY docker/docker-config/php.ini /etc/php8/conf.d/custom.ini

# Configure newrelic
COPY docker/docker-config/newrelic.ini /etc/php8/conf.d/newrelic.ini

# Configure supervisord
COPY docker/docker-config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Setup document root
RUN mkdir -p /var/www/html

# Make sure files/folders needed by the processes are accessable when they run under the nobody user
RUN chown -R nobody.nobody /var/www/html && \
    chown -R nobody.nobody /run && \
    chown -R nobody.nobody /var/lib/nginx && \
    chown -R nobody.nobody /var/log/nginx

# Switch to use a non-root user from here on
USER nobody

# Add application
WORKDIR /var/www/html

# Copy App to Workdir
COPY --chown=nobody src/ /var/www/html/

# Install composer from the official image
COPY --from=registry.digitalservice.id/proxyjds/library/composer:2.0.9 /usr/bin/composer /usr/bin/composer

# Run composer install to install the dependencies
RUN composer install --no-cache --no-dev --optimize-autoloader --prefer-dist --no-interaction --no-progress

# Set ENV DOCKER APP
ARG DOCKER_APP
ENV DOCKER_APP $DOCKER_APP

# Expose the port nginx is reachable on
EXPOSE 8080

# Let supervisord start nginx & php-fpm
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]