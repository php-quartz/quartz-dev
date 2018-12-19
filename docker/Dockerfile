FROM formapro/nginx-php-fpm:latest-all-exts

COPY ./cli.ini /etc/php/7.2/cli/conf.d/1-dev_cli.ini
COPY ./entrypoiny.sh /usr/local/bin/entrypoint.sh
RUN chmod u+x /usr/local/bin/entrypoint.sh

RUN mkdir -p /app
WORKDIR /app

CMD /usr/local/bin/entrypoint.sh
