#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if [[ ! -f .env.docker ]]; then
  cp .env.docker.example .env.docker
  echo "Created .env.docker from .env.docker.example"
fi

if ! grep -q '^APP_KEY=base64:' .env.docker; then
  echo "Generating APP_KEY..."
  docker compose --env-file .env.docker build app >/dev/null
  APP_KEY="$(docker compose --env-file .env.docker run --rm -e RUN_MIGRATIONS=0 app php artisan key:generate --show)"
  if [[ -z "$APP_KEY" ]]; then
    echo "Failed to generate APP_KEY"
    exit 1
  fi
  perl -i -pe "s/^APP_KEY=.*/APP_KEY=${APP_KEY}/" .env.docker
  echo "Updated APP_KEY in .env.docker"
fi

docker compose --env-file .env.docker up -d --build

echo "Ready: http://localhost:8000"
