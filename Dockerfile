FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libyaml-dev \
    libonig-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install mbstring
RUN pecl install redis yaml && docker-php-ext-enable redis yaml

# Enable Apache modules
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install dependencies
RUN composer install --no-scripts --no-autoloader

# Copy the rest of the application
COPY . .

# Generate optimized autoload files
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Copy Apache configuration
COPY apache.conf /etc/apache2/sites-available/000-default.conf
