FROM php:7.3-apache

# Install system dependencies and PHP extensions required by ThinkPHP
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    git \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install pdo_mysql bcmath gd zip xml

# Enable Apache mod_rewrite for ThinkPHP URL rewriting
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html

# Change ownership and permissions for ThinkPHP required directories
RUN chown -R www-data:www-data /var/www/html \
    && mkdir -p /var/www/html/runtime /var/www/html/web/uploads \
    && chmod -R 777 /var/www/html/runtime /var/www/html/web/uploads

# Configure Apache DocumentRoot to point to the 'web' public directory
RUN sed -ri -e 's!/var/www/html!/var/www/html/web!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/var/www/html/web!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

EXPOSE 80

CMD ["apache2-foreground"]
