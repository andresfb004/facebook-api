# Usar la imagen oficial de PHP con FPM (8.5: la misma versión que usamos en local;
# Laravel 13 requiere PHP 8.3 o superior)
FROM php:8.5-fpm

# Instalar dependencias del sistema operativo y librerías de desarrollo para PostgreSQL
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip

# Limpiar caché de paquetes
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones de PHP necesarias para Laravel y PostgreSQL
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip

# Copiar el gestor de dependencias Composer desde su imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar el directorio de trabajo dentro del contenedor
WORKDIR /var/www

# Copiar el código fuente del proyecto
COPY . /var/www

# Instalar dependencias de producción de Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Asignar permisos correctos a las carpetas de almacenamiento y caché
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Copiar script de inicialización y otorgar permisos de ejecución
COPY docker-entrypoint.sh /var/www/docker-entrypoint.sh
RUN chmod +x /var/www/docker-entrypoint.sh

# Exponer el puerto predeterminado que utilizará Render
EXPOSE 10000

# Ejecutar el script de entrada al iniciar el contenedor
CMD ["/var/www/docker-entrypoint.sh"]