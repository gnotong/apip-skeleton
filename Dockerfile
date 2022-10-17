FROM europe-west1-docker.pkg.dev/ubitransport-tools/services/nginx-php8.1-server:9n611vlmtux50g1dk672fcv9cfm6kftwzxd5bg3f

COPY --chown=www-data:www-data . /app

USER www-data:www-data

ENV APP_ENV dev
ENV CORS_ALLOW_ORIGIN ^https?://localhost(:[0-9]+)?$

RUN composer install --no-dev --optimize-autoloader

USER root
