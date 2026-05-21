# Wisenomy — single-stage container for self-hosting.
# Defaults to SQLite (zero config). Point DATABASE_URL at PostgreSQL for production.

FROM php:8.4-cli-alpine

# Required PHP extensions: pdo_pgsql (production), curl (Resend), pdo_sqlite (default, bundled)
RUN apk add --no-cache postgresql-dev libcurl curl-dev \
    && docker-php-ext-install pdo_pgsql \
    && docker-php-ext-enable pdo_pgsql

WORKDIR /app
COPY . .

# data/ stores the SQLite DB when DATABASE_URL is not set
RUN mkdir -p data && chown -R www-data:www-data data
USER www-data

EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "."]
