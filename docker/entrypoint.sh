#!/bin/sh
set -e
# Créer .env à partir de .env.example si absent (évite PathException au démarrage)
if [ ! -f .env ]; then
    cp .env.example .env
fi
exec "$@"
