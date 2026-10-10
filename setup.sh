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

# Demo data with the admin user, on a fresh database only: fixtures:load empties the tables first
users=$(docker compose exec -T database sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -tAc "SELECT count(*) FROM app_user"')
if [ "$users" = "0" ]; then
    docker compose exec -T php bin/console doctrine:fixtures:load --no-interaction
fi

cat <<'EOF'

Ready (all logins are listed in README.md, section "Доступы"):
  Site         http://localhost:5173  (admin: /admin, admin@portfolio.local / password)
  API          http://localhost:8080/api, Swagger UI at /api/doc
  RabbitMQ UI  http://localhost:15672  (app / app)
  Mailpit      http://localhost:8025
  Postgres     localhost:5432  (app / app, db portfolio)
EOF
