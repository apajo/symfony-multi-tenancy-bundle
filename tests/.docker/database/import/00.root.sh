#!/usr/bin/env bash

export MYSQL_PWD=$MYSQL_ROOT_PASSWORD;
mysql -u root -h localhost -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION; FLUSH PRIVILEGES;";

