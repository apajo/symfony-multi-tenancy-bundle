FROM mysql:8.0-debian

COPY ./import/* /docker-entrypoint-initdb.d/
COPY --chmod=755 ./scripts/* /usr/local/bin/

ENV MYSQL_ROOT_PASSWORD="root"
ENV MYSQL_ROOT_HOST="%"
ENV MYSQL_PORT="3306"
#ENV MYSQL_DATABASE="tms"
#ENV MYSQL_USER="tms"
#ENV MYSQL_PASSWORD="tms"

# Set the custom entrypoint
ENTRYPOINT ["entrypoint.sh"]

CMD ["mysqld"]
