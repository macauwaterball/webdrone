FROM php:7.4-fpm-alpine

# 安裝必要的依賴和PHP擴展
RUN apk update && apk add --no-cache \
    mysql-client \
    libzip-dev \
    zlib-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    libxslt-dev \
    icu-dev \
    oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        zip \
        gd \
        intl \
        mbstring \
        xml \
        xsl \
        opcache

# 配置PHP
COPY ./docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# 複製應用程序代碼
COPY . /app

# 設置工作目錄
WORKDIR /app

# 設置權限
RUN chown -R www-data:www-data /app

# 暴露端口
EXPOSE 9000

# 啟動PHP-FPM
CMD ["php-fpm"]
