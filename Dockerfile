FROM ericaslab.azurecr.io/laravel-image:php8.3.laravel10.2.9

WORKDIR /var/www/laravel

# Clear out the public directory
RUN rm -rf /var/www/laravel/public

# Copy the repo into the public directory
COPY . /var/www/laravel/public

# Fixup perms
RUN chmod -R u+rwX,go+rX /var/www/laravel/ && chown -R www-data:www-data /var/www/laravel/

# Set the app to start in production mode
#RUN sed -i "s|'ENVIRONMENT', 'development'|'ENVIRONMENT', 'production'|" /var/www/laravel/public/index.php

# Copy in our config files
ADD ./config/database.php /var/www/laravel/public/application/config/database.php
ADD ./config/config.php /var/www/laravel/public/application/config/config.php