FROM richarvey/nginx-php-fpm:latest

COPY . /var/www/html
COPY docker/start-render.sh /usr/local/bin/start-render.sh

WORKDIR /var/www/html

ENV WEBROOT=/var/www/html/public
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN composer install --no-dev --optimize-autoloader
RUN chmod +x /usr/local/bin/start-render.sh

CMD ["/usr/local/bin/start-render.sh"]
