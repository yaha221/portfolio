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

## Очереди (RabbitMQ + Symfony Messenger)

Всё медленное и ненадёжное выполняет контейнер `worker`; API только сохраняет данные и ставит задачу в очередь.

| Событие | Откуда | Что делает воркер |
|---|---|---|
| `ContactMessageReceived` | `POST /api/contact` (202) | Письмо админу (Reply-To — посетитель), автоответ посетителю, сообщение в Telegram |
| `ProjectImageUploaded` | загрузка картинки в галерею | WebP-превью 480 и 1280 px по ширине, с учётом EXIF-поворота |
| `ProjectPublished` | черновик → опубликован | Сообщение в Telegram со ссылкой на проект |
| `PageViewed` | `POST /api/page-views` от SPA (202) | Запись просмотра; статистика — `GET /api/admin/analytics` |

- Каждый обработчик независим: при сбое повторяется только упавший (3 повтора: через 1, 3 и 9 секунд).
- Письма в разработке ловит Mailpit: http://localhost:8025.
- Telegram по умолчанию выключен (`TELEGRAM_DSN=null://null`). Чтобы включить, добавьте в `backend/.env.local`:
  `TELEGRAM_DSN=telegram://<токен бота>@default?channel=<chat id>` и перезапустите `php` и `worker`.
- Если RabbitMQ недоступен, запрос всё равно выполняется (данные сохранены), ошибка пишется в лог.

Сообщения, исчерпавшие повторы, лежат в PostgreSQL (`messenger_messages`, очередь `failed`). Сообщение формы, письмо по которому так и не ушло, получает статус `failed` в админке.

```bash
docker compose exec php bin/console messenger:stats                # сколько сообщений в очередях
docker compose exec php bin/console messenger:failed:show          # неудачные сообщения с текстом ошибки
docker compose exec php bin/console messenger:failed:retry <id>    # повторить одно (без id — все по очереди)
docker compose exec php bin/console messenger:failed:remove <id>   # удалить
docker compose restart worker                                      # после изменения кода обработчиков
```

## Производительность

**Кеш ответов.** Публичные GET (`/api/projects`, `/api/projects/{slug}`, `/api/technologies`, `/api/skills`, `/api/experience`) отдают готовый JSON из Redis (пул `cache.public_api`). Кеш не живёт по таймеру: `PublicApiCacheInvalidator` на каждой записи в БД (админка, воркер, фикстуры) снимает теги `projects` / `technologies` / `skills` / `experience`, так что изменения видны сразу. Если Redis недоступен, API работает без кеша (тайм-аут 0,3 с).

**HTTP-кеш.** `Cache-Control: public, max-age=60` и `ETag`; при совпадении `If-None-Match` — пустой `304`. nginx сжимает JSON (gzip), загруженные картинки отдаются с `Cache-Control: immutable` на год (имена — UUID).

**Запросы и индексы.** Проверено `EXPLAIN ANALYZE` на 20 тыс. проектов и 1 млн просмотров:

| Запрос | Было | Стало | Что сделано |
|---|---|---|---|
| Страница проектов | 95 мс | 0,1 мс + 0,5 мс | Без Doctrine Paginator: страница без JOIN по частичному индексу `(sort_order, id) WHERE is_published`, технологии страницы — одним запросом |
| Число проектов | 37 мс | 1,8 мс | Простой `COUNT` |
| Аналитика по дням | 248 мс | 100 мс | Группировка по (день, посетитель) вместо `COUNT(DISTINCT)` |
| Сообщения в админке | 0,17 мс | 0,07 мс | Индекс `(created_at, id)` под `ORDER BY` |

Тест `PublicApiCacheTest` следит, чтобы список проектов делал ровно 3 запроса независимо от числа проектов и технологий, карточка проекта — 1, а повторный запрос — 0.

```bash
docker compose exec php bin/console cache:pool:clear cache.public_api   # сбросить кеш ответов вручную
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
