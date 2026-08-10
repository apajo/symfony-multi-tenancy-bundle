#!/bin/sh
#
# The project is bind-mounted, so vendor/ comes from the host. Install it on
# first use rather than baking it into the image, which would be shadowed by
# the mount anyway.
set -e

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --no-progress
fi

exec "$@"
