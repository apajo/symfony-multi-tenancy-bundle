#!/bin/bash

set -e

# Call the original entrypoint
echo "Starting MySQL..."
exec docker-entrypoint.sh "$@"
