#!/usr/bin/env bash

wait-for-it.sh database:3306 --timeout=300

composer require apajo/symfony-multi-tenancy-bundle

echo -e "\nTESTS are ready to run...\n"

exec "$@"
