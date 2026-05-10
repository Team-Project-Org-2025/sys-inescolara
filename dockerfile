# syntax=docker/dockerfile:1
# 1. Usamos la imagen oficial de PHP con Apache
FROM php:8.2-apache

LABEL org.opencontainers.image.title="sys-inescolara" \
      org.opencontainers.image.description="Contenedor PHP Apache para sys-inescolara"

# 2. Instalamos dependencias del sistema y extensiones de PHP necesarias para MySQL
RUN apt-get update && apt-get install -y \
    --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd \
    && rm -rf /var/lib/apt/lists/*

# 3. Habilitamos el módulo rewrite de Apache (crucial para rutas en PHP/Laravel/proyectos web)
RUN a2enmod rewrite

# 4. Instalamos Composer desde una versión estable
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 5. Establecemos el directorio de trabajo
WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1

# 6. Copiamos el código de tu proyecto
COPY . .

# 7. Ajustamos permisos (Modificado para ser más robusto)
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

# 8. Exponemos el puerto 80
EXPOSE 80

# 9. Iniciamos Apache en el primer plano
CMD ["apache2-foreground"]
