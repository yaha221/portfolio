#!/usr/bin/env bash
# Bootstrap after clone: builds images, installs backend dependencies, starts the stack.
set -euo pipefail
cd "$(dirname "$0")"

# Containers run with the host user's UID/GID so files they create stay editable
if [ ! -f .env ]; then
    printf 'HOST_UID=%s\nHOST_GID=%s\n' "$(id -u)" "$(id -g)" > .env
fi

# Versioned git hooks (pre-commit runs `composer lint`)
git config core.hooksPath .githooks

docker compose build
docker compose run --rm --no-deps php composer install
docker compose up -d
# JWT signing keys (backend/config/jwt, not committed)
docker compose exec -T php bin/console lexik:jwt:generate-keypair --skip-if-exists
chmod 600 backend/config/jwt/private.pem
docker compose exec -T php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

cat <<'EOF'

Ready:
  API          http://localhost:8080
  RabbitMQ UI  http://localhost:15672  (app / app)
  Mailpit      http://localhost:8025
  Postgres     localhost:5432  (app / app, db portfolio)
EOF
