FROM php:8.1-cli

# Install dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mysqli \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /usr/src/myapp

# Copy application files (with permissions preserved from host - run prepare-build.sh first)
COPY myapp /usr/src/myapp

# Copy SQL restore file
COPY mysql-baked/classifieds_restore.sql /usr/src/myapp/

# Expose port
EXPOSE 9980

# Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:9980"]