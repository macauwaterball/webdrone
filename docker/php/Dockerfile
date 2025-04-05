FROM php:8.1-fpm

# 安裝 PDO MySQL 擴展
RUN docker-php-ext-install pdo pdo_mysql

# 設置工作目錄
WORKDIR /app

# 複製應用程序文件
COPY . .

# 設置權限
RUN chown -R www-data:www-data /app && \
    chmod 755 /app && \
    find /app/public -type d -exec chmod 755 {} \; && \
    find /app/public -type f -exec chmod 644 {} \;

# 暴露端口
EXPOSE 9000

# 啟動PHP-FPM
CMD ["php-fpm"]
