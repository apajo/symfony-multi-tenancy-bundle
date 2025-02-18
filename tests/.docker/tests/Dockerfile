# Use an official PHP CLI image
FROM php:8.2-cli

# Install required PHP extensions and dependencies
RUN apt-get update && apt-get install -y \
    unzip git libicu-dev libonig-dev libzip-dev zip \
    && docker-php-ext-install intl pdo pdo_mysql zip

# Install Composer 2
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer;

# Install Symfony CLI globally
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

# Set working directory
WORKDIR /var/www

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Create a new Symfony CLI project
RUN symfony new app --no-git

# Set working directory to the Symfony project
WORKDIR /var/www/app

# Install project dependencies
RUN composer install

COPY --chmod=755 .docker/tests/scripts/ /usr/local/bin

COPY .docker/tests/app/ /var/www/app

# Set entrypoint for CLI commands
ENTRYPOINT ["entrypoint.sh"]
