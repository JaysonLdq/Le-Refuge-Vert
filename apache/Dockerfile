# Récupération de l'image php:8.2-apache
FROM php:8.2-apache

# Installation de git, unzip et autres dépendances
RUN apt-get update && apt-get install -y \
  git \
  unzip \
  wget \
  libpng-dev \
  libjpeg-dev \
  libfreetype6-dev \
  curl \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install gd

# Installation de Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Activation le module mysqli, pdo et pdo_mysql
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Configurer Apache pour utiliser /var/www/html/public comme racine du document
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Activer les modules Apache nécessaires
RUN a2enmod rewrite

# Installation de Symfony CLI
RUN wget https://get.symfony.com/cli/installer -O - | bash \
  && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Copie de la configuration PHP personnalisée
COPY custom-php.ini /usr/local/etc/php/conf.d/

# Installation de Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Installation de NVM
RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash

# Ajouter NVM à la variable d'environnement PATH
ENV NVM_DIR=/root/.nvm
ENV NODE_VERSION=20
ENV PATH=$NVM_DIR/versions/node/v$NODE_VERSION/bin:$PATH

# Chargement du script NVM et installation de Node.js
RUN /bin/bash -c "source $NVM_DIR/nvm.sh && nvm install $NODE_VERSION && nvm alias default $NODE_VERSION && nvm use default"


# Configuration des permissions pour le répertoire web
RUN chown -R www-data:www-data /var/www/html

# Exposer le port 80
EXPOSE 80

