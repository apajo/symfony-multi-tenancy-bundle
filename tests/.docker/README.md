# Symfony Docker

## Requirements

- [Docker 20](https://www.docker.com/)
- [Docker Compose](https://docs.docker.com/compose/) (v2.10+)

## Quick start:

```shell
docker load -i .docker/php/tms-php-fpm.tar.gz; \
docker compose up --build --force-recreate --remove-orphans;
```

## Getting Started

1. Compile/load PHP FPM extensions
2. Build images
3. Run containers
4. Create certificates
5. Stop containers

### 1. Compile/load PHP FPM extensions

Load the image from filesystem:

```shell
docker load -i .docker/php/tms-php-fpm.tar.gz
```

or compile the image:

```shell
docker compose -f .docker/compose.php.yml build  --no-cache --force-rm;
docker save tms-php-fpm  | gzip > .docker/php/tms-php-fpm.tar.gz;
```

Another way: `docker import ./php/tms-php-fpm.tar.gz tms-php-fpm:latest`

### 2. Build images
 
```shell
docker compose build
```

### 3. Run containers

```shell
docker compose up
```

to see the logs, run:

```shell
docker compose logs -f
```

### 4. Create certificates

```shell
docker exec -it tms_nginx sh -c "/usr/bin/certbot --dry-run --nginx -d ${APP_DOMAIN} -d api.${APP_DOMAIN}"
```


### 5. Stop containers

```shell
docker compose down
```

```shell
docker compose down --remove-orphans --rmi=all
```

## Shortcuts

### Full-build

```shell
docker load -i php/tms-php-fpm.tar.gz; \
docker compose down --remove-orphans -v; \
docker compose rm -f -s -v; \
docker compose build --no-cache --force-rm; \
docker compose up --force-recreate --remove-orphans
```

```shell
docker compose rm -f; \
docker compose up --build --always-recreate-deps --remove-orphans
```

### Restart containers

```shell
docker compose rm -f; \
docker compose up --force-recreate --build;
```

### Clear

```shell
docker compose rm -f -s -v
```

## Database

### Importing database from remote host

Määra `database` konteinerile _extra_hosts_ `import_host` hosti IP aadress, millelt soovid andmeid importida.

```yaml
services:
  # ... #
  database:
    # ... #
    extra_hosts:
      - "import_host:192.168.0.18"
```

__NB!__ Kui `import_host` on määramata või phendust ei suudeta luua, siis jäetakse importimis cronjob katki.

Impordi host'i andmeid saab muuta ka otse command lines:

```shell
make db-import IMPORT_HOST_NAME=localhost IMPORT_HOST_PORT=1248 IMPORT_USER=apajo
```

## Troubleshooting

### MySQL: nonaggregated column ... incompatible with sql_mode=only_full_group_by

Run the following command to disable `ONLY_FULL_GROUP_BY`:

```mysql
SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));
```

```bash
docker exec -it tms_app 'mysql -proot SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));'
```


## Credits

This source has been ported from: https://github.com/ger86/symfony-docker
