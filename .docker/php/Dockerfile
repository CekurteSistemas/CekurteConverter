FROM php:7.3-cli

ENV EXIFTOOL_VERSION=11.33
ENV XDEBUG_VERSION=2.7.0

RUN pecl install xdebug-${XDEBUG_VERSION} \
    && docker-php-ext-enable xdebug

RUN cd /tmp \
	&& curl -L http://www.sno.phy.queensu.ca/~phil/exiftool/Image-ExifTool-${EXIFTOOL_VERSION}.tar.gz -o Image-ExifTool-${EXIFTOOL_VERSION}.tar.gz \
	&& tar -zxvf Image-ExifTool-${EXIFTOOL_VERSION}.tar.gz \
	&& cd Image-ExifTool-${EXIFTOOL_VERSION} \
	&& perl Makefile.PL \
	&& make test \
	&& make install \
	&& cd .. \
	&& rm -rf Image-ExifTool-${EXIFTOOL_VERSION}*

RUN apt-get update \
    && apt-get install ffmpeg -y

RUN curl -L https://cs.sensiolabs.org/download/php-cs-fixer-v2.phar -o php-cs-fixer && chmod +x php-cs-fixer && mv php-cs-fixer /usr/local/bin/php-cs-fixer
