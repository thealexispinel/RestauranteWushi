# Dockerfile
# Basea tu nueva imagen en la que usaste en compose
FROM php:8.1-apache

# Ejecuta el script de instalación de extensiones de PHP,
# e instala la extensión pdo_mysql necesaria.
RUN docker-php-ext-install pdo pdo_mysql