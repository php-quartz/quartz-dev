FROM ubuntu:16.04

## libs
RUN set -x && \
    apt-get update && \
    apt-get install -y --no-install-recommends wget curl openssl ca-certificates nano netcat && \
    apt-get install -y --no-install-recommends php php-mongodb php-curl php-intl php-mbstring php-zip php-mcrypt php-xdebug php-bcmath php-xml

## confis

RUN rm -f /etc/php/7.0/cli/conf.d/*xdebug.ini

COPY ./cli.ini /etc/php/7.0/cli/conf.d/1-dev_cli.ini
COPY ./entrypoiny.sh /usr/local/bin/entrypoint.sh
RUN chmod u+x /usr/local/bin/entrypoint.sh

RUN mkdir -p /app
WORKDIR /app

CMD /usr/local/bin/entrypoint.sh
