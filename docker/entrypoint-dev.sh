#!/bin/sh
set -e

# Ensure Laravel writable directories exist in the storage volume
mkdir -p \
    storage/framework/views \
    storage/framework/cache \
    storage/framework/sessions \
    storage/logs \
    bootstrap/cache

# 777 : workers www-data doivent pouvoir écrire dans ces volumes (dev uniquement)
chmod -R 777 storage bootstrap/cache

exec "$@"
