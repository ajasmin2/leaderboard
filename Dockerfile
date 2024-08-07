FROM dunglas/frankenphp:1.2.2

RUN install-php-extensions \
    pcntl \
    pdo_mysql \
	gd \
	intl \
	zip \
	opcache \
	redis
 
COPY ./leaderboard /app
 
ENTRYPOINT ["php", "artisan", "octane:frankenphp"]