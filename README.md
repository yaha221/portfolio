# Портфолио

Backend: Symfony 7.4 (REST, ручные контроллеры) + PostgreSQL 17 + RabbitMQ (Symfony Messenger).
Frontend: отдельный SPA на Vue 3 (`frontend/`, будет позже). Файлы — на локальном диске.

## Требования

- WSL2 с Ubuntu; проект лежит в файловой системе Linux (`~/projects/portfolio`), не на диске `C:`
- Docker Desktop с включённой интеграцией для Ubuntu (Settings → Resources → WSL integration)
- Git внутри Ubuntu

PHP и Composer локально не нужны — всё работает в контейнерах.

## Первый запуск

```bash
./setup.sh
```

Скрипт запишет в `.env` ваш UID/GID (контейнеры работают от вашего пользователя), соберёт образы, поставит зависимости, поднимет стек, сгенерирует ключи JWT (`backend/config/jwt`, не в Git) и применит миграции.

| Сервис | Адрес |
|---|---|
| API | http://localhost:8080/api |
| Документация API (Swagger UI) | http://localhost:8080/api/doc, OpenAPI JSON — `/api/doc.json` |
| RabbitMQ UI | http://localhost:15672 (app / app) |
| Mailpit | http://localhost:8025 |
| PostgreSQL | localhost:5432 (app / app, БД `portfolio`) |

Админ из фикстур: `admin@portfolio.local`, пароль — `UserFactory::DEFAULT_PASSWORD` в `backend/src/Factory/UserFactory.php` (только dev/test).

## Авторизация админки

1. `POST /api/auth/login` с `{"email", "password"}` → `{"token", "expiresIn": 900}` и httpOnly-cookie `refresh_token` (30 дней, только для `/api/auth`).
2. Запросы к `/api/admin/*` — с заголовком `Authorization: Bearer <token>`.
3. Когда токен истёк — `POST /api/auth/refresh` (SPA шлёт cookie через `credentials: 'include'`). Refresh-токен одноразовый: приходит новая cookie.
4. `POST /api/auth/logout` отзывает refresh-токен.

В базе хранится только SHA-256 refresh-токена. После 5 неудачных входов за 15 минут — 429.
Загруженные картинки лежат в `backend/public/uploads` (не в Git), nginx отдаёт их по `/uploads/...`.

## Полезные команды

```bash
docker compose up -d                                   # поднять стек
docker compose exec php bin/console doctrine:migrations:migrate
docker compose exec php bin/console doctrine:fixtures:load   # тестовые данные для разработки (очищает БД)
docker compose exec php composer test                  # PHPUnit, база portfolio_test создаётся сама
docker compose exec php composer require <package>
docker compose logs -f worker                          # логи воркера очереди
docker compose down                                    # остановить
```

## Качество кода

```bash
docker compose exec php composer lint                  # стиль + PHPStan (то же запускает pre-commit хук)
docker compose exec php composer cs-fix                # автоматически исправить стиль
```

- PHP-CS-Fixer: наборы `@Symfony`, `@Symfony:risky`, `@PHP8x4Migration`, плюс `declare(strict_types=1)` и строгие сравнения — `backend/.php-cs-fixer.dist.php`
- PHPStan level 8 с расширениями Symfony и Doctrine — `backend/phpstan.dist.neon`

## IDE

- VS Code: расширение WSL, затем `code .` из папки проекта в Ubuntu
- PhpStorm: открыть `\\wsl$\Ubuntu\home\<user>\projects\portfolio`, PHP-интерпретатор — из Docker Compose (сервис `php`)

## Структура

```
backend/       Symfony API
frontend/      SPA (позже)
docker/        Dockerfile php, конфиг nginx
```
