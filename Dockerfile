FROM php:8.2-cli

# システムパッケージ
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_pgsql mbstring bcmath \
    && rm -rf /var/lib/apt/lists/*

# Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

# PHP依存関係（artisanコマンドは実行しない）
RUN php -d memory_limit=-1 /usr/bin/composer install --optimize-autoloader --no-dev --no-scripts

# フロントエンドビルド
RUN npm ci && npm run build

# 起動スクリプトに実行権限
RUN chmod +x /app/start.sh

EXPOSE 8000

CMD ["/app/start.sh"]
