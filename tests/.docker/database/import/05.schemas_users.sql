# create other users
CREATE USER 'tms'@'%' IDENTIFIED BY 'tms';
GRANT ALL PRIVILEGES ON *.* TO 'tms'@'%';

# create other users
CREATE USER 'tenants'@'%' IDENTIFIED BY 'tenants';
GRANT ALL PRIVILEGES ON *.* TO 'tenants'@'%';
GRANT GRANT OPTION ON *.* TO 'tenants'@'%';

# create databases
CREATE DATABASE IF NOT EXISTS `tms`;
CREATE DATABASE IF NOT EXISTS `tenants`;

FLUSH PRIVILEGES;
