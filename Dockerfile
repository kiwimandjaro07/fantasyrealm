# Image PHP 8.3 + Apache pour l'application FantasyRealm
FROM php:8.3-apache

# Extensions nécessaires :
# - pdo_mysql : accès à MySQL via PDO (requêtes préparées)
# - mongodb   : pilote pour MongoDB (journal d'activité, messages)
# - zip/unzip : utilisés par Composer pour installer les dépendances
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev unzip libssl-dev \
    && docker-php-ext-install pdo_mysql zip \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && rm -rf /var/lib/apt/lists/*

# Composer, récupéré depuis l'image officielle
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# mod_rewrite : toutes les URL passent par public/index.php (front controller)
RUN a2enmod rewrite headers

# Seul le dossier public/ est exposé par Apache : le code (src/, .env...)
# reste hors de la racine web et n'est donc jamais téléchargeable.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

# Ne pas annoncer la version d'Apache/PHP dans les en-têtes HTTP
# (préfixe zz- : chargé après le security.conf de Debian, qui sinon l'écraserait)
RUN printf "ServerTokens Prod\nServerSignature Off\n" > /etc/apache2/conf-available/zz-security-hardening.conf \
    && a2enconf zz-security-hardening \
    && echo "expose_php = Off" > /usr/local/etc/php/conf.d/security.ini

WORKDIR /var/www/html
