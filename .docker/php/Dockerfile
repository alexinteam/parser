#
#--------------------------------------------------------------------------
# Image Setup
# $REF: $
#--------------------------------------------------------------------------
#
# Arguments:
#     PUID                 Индетификатор пользователя. Default "1000"
#     INSTALL_GIT          Установка git (false|true). Default "true"
#     PHP_INSTALL_XDEBUG   Включить расшимрения xdebug в конфиг php (false|true). Default "false"
#     PHP_XDEBUG_PORT      Порт для xdebug. Default "9000"
#

FROM php:7.3-fpm

#
#--------------------------------------------------------------------------
# Core Software's Installation
#--------------------------------------------------------------------------
#
RUN apt-get -qy update && apt-get -qy install --no-install-recommends \
        wget curl \
        sudo \
        zip unzip \
        lsb-release \
        gnupg openssh-client libssh2-1-dev libssh2-1 \
        libmemcached-dev \
        libz-dev \
        libpq-dev \
        libjpeg-dev \
        libpng-dev \
        libfreetype6-dev \
        libssl-dev openssh-client \
        libssh2-1-dev libssh2-1 \
        libmcrypt-dev \
        libzip-dev libicu-dev \
        libldap2-dev \
        librabbitmq-dev \
        libgmp-dev \
        libxml2-dev \
        python-setuptools \
        libxslt-dev \
        libc-client-dev libkrb5-dev \
    && apt-get autoremove -qy && apt-get clean -qy && rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

# END Core Software's Installation  -------------

#
#--------------------------------------------------------------------------
# PHP Installation
#--------------------------------------------------------------------------
#
RUN echo "docker-php-ext-install"       && \
    pecl update-channels                && \
  docker-php-ext-configure gd              \
        --with-jpeg-dir=/usr/lib           \
        --with-freetype-dir=/usr/include/freetype2 && \
    docker-php-ext-install gd           && \
  docker-php-ext-configure zip             \
        --with-libzip                   && \
  docker-php-ext-configure ldap            \
        --with-libdir=lib/x86_64-linux-gnu/ && \
    docker-php-ext-install ldap         && \
  docker-php-ext-configure imap            \
            --with-kerberos --with-imap-ssl && \
    docker-php-ext-install imap         && \
  docker-php-ext-configure intl         && \
    docker-php-ext-install intl         && \
    docker-php-ext-install bcmath       && \
    docker-php-ext-install exif         && \
    docker-php-ext-install gmp          && \
    docker-php-ext-install opcache      && \
    docker-php-ext-install pcntl        && \
    docker-php-ext-install pdo_pgsql    && \
    docker-php-ext-install pgsql        && \
    docker-php-ext-install sockets      && \
    docker-php-ext-install xsl          && \
    docker-php-ext-install zip          && \
    docker-php-ext-install soap         && \
        docker-php-ext-enable soap      && \
    docker-php-ext-install pdo_mysql    && \
        docker-php-ext-enable pdo_mysql && \
    pecl install -o -f amqp             && \
        docker-php-ext-enable amqp      && \
    pecl install -o -f memcached        && \
        docker-php-ext-enable memcached && \
    pecl install -o -f redis            && \
        docker-php-ext-enable redis     && \
    pecl install -o -f xdebug           && \
#    pecl install -o -f apcu      && docker-php-ext-enable apcu      && \
#    pecl install -o -f mongodb   && docker-php-ext-enable mongodb   && \
#    pecl install -o -f swoole    && docker-php-ext-enable swoole    && \
#
#   Cleaning up
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*
# END PHP Installation --------------------------


#
#--------------------------------------------------------------------------
# PHP Configuration AND COMPOSER
#--------------------------------------------------------------------------
#

ADD ./50-custom.ini         /usr/local/etc/php/conf.d/

# Installing Composer
RUN printf "\nInstalling Composer\n\n"; \
    EXPECTED_SIGNATURE=$(wget -q -O - https://composer.github.io/installer.sig); \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"; \
    ACTUAL_SIGNATURE=$(php -r "echo hash_file('SHA384', 'composer-setup.php');"); \
    if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; \
    then \
      >&2 echo 'ERROR: Invalid installer signature'; \
      exit 1; \
    else \
      php composer-setup.php --install-dir=/usr/local/bin --filename=composer.phar; \
    fi; \
    rm composer-setup.php

RUN composer.phar global require "hirak/prestissimo" && \
    rm -rf /root/.composer/cache

RUN wget --no-check-certificate -q -O /usr/local/bin/gosu "https://github.com/tianon/gosu/releases/download/1.10/gosu-amd64" \
    && chmod +x /usr/local/bin/gosu

RUN wget --no-check-certificate -q -O /usr/local/bin/php-cs-fixer https://cs.sensiolabs.org/download/php-cs-fixer-v2.phar \
    && chmod +x /usr/local/bin/php-cs-fixer

#
#--------------------------------------------------------------------------
# User www-data Configuration
#--------------------------------------------------------------------------
#
RUN cp -r /etc/skel /home/www-data \
    && cp -r /root/.composer /home/www-data/ \
    && chown -R www-data:www-data /home/www-data \
    && usermod -d /home/www-data www-data

ARG PUID=1000
ENV PUID ${PUID}
RUN if [ -n "${PUID}" ] && [ "${PUID%:*}" != 0 ]; then \
  usermod -u ${PUID} www-data >/dev/null 2>&1 \
;fi

# END PHP Configuration --------------------------


#####################################
# pgsql client
#####################################
RUN wget -q https://www.postgresql.org/media/keys/ACCC4CF8.asc -O - | apt-key add - && \
    sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt/ `lsb_release -cs`-pgdg main" >> /etc/apt/sources.list.d/pgdg.list' && \
    apt-get -qy update && apt-get -qy install --no-install-recommends \
        postgresql-client \
    && apt-get autoremove -qy && apt-get clean -qy && rm -rf /tmp/* /var/lib/apt/lists/*
# END pgsql client --------------------------

#
#--------------------------------------------------------------------------
# SSH and GIT Installation
#--------------------------------------------------------------------------
#
ARG INSTALL_GIT=true

RUN if [ ${INSTALL_GIT} = true ]; then \
    apt-get -qy update && apt-get -qy install --no-install-recommends libssl-dev tcl-dev gettext git && \
    apt-get autoremove -qy && apt-get clean -qy && rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/* && \
    git --version \
;fi

# Add default RSA
ADD ./id_rsa        /etc/ssh/gitlab.id_rsa
RUN chmod +r /etc/ssh/gitlab.id_rsa

ADD ./composer.sh        /usr/local/sbin/composer
RUN sed -i 's/\r//'  /usr/local/sbin/composer && \
    chmod +x         /usr/local/sbin/composer

# END SSH and GIT Configuration --------------------------


ARG PHP_INSTALL_XDEBUG=false

RUN if [ ${PHP_INSTALL_XDEBUG} = true ]; then \
    docker-php-ext-enable xdebug \
;fi


ARG PHP_XDEBUG_PORT=false

RUN if [ ${PHP_XDEBUG_PORT} != false ]; then \
    echo "xdebug.remote_port=${PHP_XDEBUG_PORT}" >> /usr/local/etc/php/conf.d/50-custom.ini \
;fi

#
#--------------------------------------------------------------------------
# Clear code
#--------------------------------------------------------------------------
#

RUN apt-get clean -y \
    && rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

ENV PATH /var/www:$PATH

WORKDIR /var/www

CMD ["php-fpm", "--allow-to-run-as-root", "--nodaemonize"]
