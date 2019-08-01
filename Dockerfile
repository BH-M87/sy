# @description php image base on the debian 9.x
#
#                       Some Information
# ------------------------------------------------------------------------------------
# @link https://hub.docker.com/_/debian/      alpine image
# @link https://hub.docker.com/_/php/         php image
# @link https://github.com/docker-library/php php dockerfiles
# @see https://github.com/docker-library/php/tree/master/7.2/stretch/cli/Dockerfile
# ------------------------------------------------------------------------------------
#
FROM php:7.2

LABEL maintainer="zjy@zhujia360.com" version="1.0.0"

# Timezone
RUN /bin/cp /usr/share/zoneinfo/Asia/Shanghai /etc/localtime \
    && echo 'Asia/Shanghai' > /etc/timezone

# Version
ENV PHPREDIS_VERSION 4.3.0
ENV SWOOLE_VERSION 4.4.2

# Libs -y --no-install-recommends
RUN apt-get update \
    && apt-get install -y \
        curl wget git zip unzip less vim openssl \
        imagemagick \
        libz-dev \
        libssl-dev \
        libnghttp2-dev \
        libpcre3-dev \
        libjpeg-dev \
        libpng-dev \
        libfreetype6-dev \
# Install composer
    && curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && composer self-update --clean-backups \
# Install PHP extensions
    && docker-php-ext-install \
       curl bcmath gd pdo_mysql mbstring sockets zip opcache iconv sysvmsg sysvsem sysvshm \
# Install redis extension
    && wget http://pecl.php.net/get/redis-${PHPREDIS_VERSION}.tgz -O /tmp/redis.tar.tgz \
    && pecl install /tmp/redis.tar.tgz \
    && rm -rf /tmp/redis.tar.tgz \
    && docker-php-ext-enable redis \
# Install swoole extension
    && wget https://github.com/swoole/swoole-src/archive/v${SWOOLE_VERSION}.tar.gz -O swoole.tar.gz \
    && mkdir -p swoole \
    && tar -xf swoole.tar.gz -C swoole --strip-components=1 \
    && rm swoole.tar.gz \
    && ( \
        cd swoole \
        && phpize \
        && ./configure --enable-mysqlnd --enable-sockets --enable-openssl --enable-http2 \
        && make -j$(nproc) \
        && make install \
    ) \
    && rm -r swoole \
    && docker-php-ext-enable swoole \
# Clear dev deps
    && apt-get clean \
    && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false \

# Install composer deps
ADD . /var/www/yii2
RUN  cd /var/www/yii2 \
    && composer install \
    && composer clearcache

WORKDIR /var/www/yii2





