# Dockerfile -- Ethio Microfinance app image (PHP-FPM)
#
# This container does NOT terminate the public port directly -- the nginx
# service (docker/nginx/) sits in front of it, per Part 5.5 / 7.2 of the
# Master Plan. This container only speaks FastCGI on port 9000, which is
# only reachable from other containers on the internal Docker network,
# never from the LAN directly (Part 5.2).

FROM php:8.3-fpm-alpine

# mysqli is the only DB extension this app uses (config/database.php).
# getimagesize() used for upload validation (includes/functions.php) is
# built into PHP core -- no extra extension needed for that.
RUN docker-php-ext-install mysqli

# fcgi provides cgi-fcgi, used only to let the container's own healthcheck
# (docker-compose.yml) speak to PHP-FPM's built-in /ping endpoint -- this
# is a standard Alpine package, not a third-party download.
RUN apk add --no-cache fcgi

# Defense-in-depth PHP hardening (Part 4.4) -- disables eval/system/exec
# class functions at the engine level, hides version info, disables
# verbose error display to visitors.
COPY docker/php/php-hardening.ini /usr/local/etc/php/conf.d/zz-hardening.ini

# Non-root PHP-FPM pool (Part 5.3).
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Create the non-root user the pool config above runs as.
RUN addgroup -g 1000 appuser && adduser -u 1000 -G appuser -D -h /var/www/html appuser

WORKDIR /var/www/html

# Copy application code. Ownership set to the non-root user so PHP-FPM
# (running as appuser per www.conf) can actually read it, while still
# being read-only from the container's own perspective at runtime
# (docker-compose.yml sets read_only: true with an explicit writable
# volume only for uploads/ -- Part 5.3).
COPY --chown=appuser:appuser . /var/www/html

# uploads/ is where user-submitted profile pictures land (includes/functions.php
# upload_file()) -- this is the one directory that must remain writable even
# under the read-only root filesystem set in docker-compose.yml.
RUN mkdir -p /var/www/html/uploads && chown appuser:appuser /var/www/html/uploads

USER appuser

EXPOSE 9000

CMD ["php-fpm"]
