# Портфолио

Backend: Symfony 7.4 (REST, ручные контроллеры) + PostgreSQL 17 + RabbitMQ (Symfony Messenger).
Frontend: отдельный SPA на Vue 3 + TypeScript (`frontend/`), публичный сайт и админка в одном приложении. Файлы — на локальном диске.

## Требования

- WSL2 с Ubuntu; проект лежит в файловой системе Linux (`~/projects/portfolio`), не на диске `C:`
- Docker Desktop с включённой интеграцией для Ubuntu (Settings → Resources → WSL integration)
- Git внутри Ubuntu

PHP и Composer локально не нужны — всё работает в контейнерах.

## Первый запуск

```bash
./setup.sh
```

Скрипт запишет в `.env` ваш UID/GID (контейнеры работают от вашего пользователя), соберёт образы, поставит зависимости, поднимет стек, сгенерирует ключи JWT (`backend/config/jwt`, не в Git), применит миграции и в пустую базу загрузит демо-данные с админом.

Потом проект запускается одной командой: `docker compose up -d` (через минуту после старта контейнер `frontend` поставит зависимости и поднимет Vite).

## Доступы

Все логины и пароли — только для локальной разработки.

| Что | Адрес | Логин / пароль | Примечание |
|---|---|---|---|
| Сайт | http://localhost:5173 | — | Vite dev server, изменения видны сразу |
| Админка | http://localhost:5173/admin | `admin@portfolio.local` / `password` | Пользователь из фикстур (`UserFactory::DEFAULT_PASSWORD`) |
| API | http://localhost:8080/api | JWT админа для `/api/admin/*` | Как получить токен — раздел «Авторизация админки» |
| Swagger UI | http://localhost:8080/api/doc | — | OpenAPI JSON — `/api/doc.json`; кнопка Authorize принимает JWT |
| RabbitMQ UI | http://localhost:15672 | `app` / `app` | AMQP — `localhost:5672`, очередь `messages` |
| Mailpit | http://localhost:8025 | — | Все письма приложения: админу и автоответы |
| PostgreSQL | `localhost:5432` | `app` / `app` | БД `portfolio`, тесты — `portfolio_test` |
| Redis | только внутри Docker, `redis:6379` | — | `docker compose exec redis redis-cli` |

Пароли PostgreSQL и RabbitMQ задаются в корневом `.env` (`POSTGRES_USER`, `POSTGRES_PASSWORD`, `POSTGRES_DB`, `RABBITMQ_USER`, `RABBITMQ_PASSWORD`); значения выше — по умолчанию.

## Авторизация админки

1. `POST /api/auth/login` с `{"email", "password"}` → `{"token", "expiresIn": 900}` и httpOnly-cookie `refresh_token` (30 дней, только для `/api/auth`).
2. Запросы к `/api/admin/*` — с заголовком `Authorization: Bearer <token>`.
3. Когда токен истёк — `POST /api/auth/refresh`, cookie браузер отправит сам (SPA и API на одном origin). Refresh-токен одноразовый: приходит новая cookie.
4. `POST /api/auth/logout` отзывает refresh-токен.

В базе хранится только SHA-256 refresh-токена. После 5 неудачных входов за 15 минут — 429.
Загруженные картинки лежат в `backend/public/uploads` (не в Git), nginx отдаёт их по `/uploads/...`.

## Frontend (SPA)

Vue 3 + TypeScript, Vite, Vue Router, Pinia. Контейнер `frontend` при старте ставит зависимости и запускает Vite на http://localhost:5173. Vite проксирует `/api` и `/uploads` в nginx, поэтому для браузера SPA и API — один origin: без CORS, refresh-cookie с `SameSite=Strict` работает.

| Раздел | Адрес |
|---|---|
| Главная, проекты, проект | `/`, `/projects` (фильтр `?technology=`, страницы `?page=`), `/projects/{slug}` |
| Обо мне, контакты | `/about`, `/contact` |
| Админка | `/admin` (вход — `/admin/login`): проекты с картинками, технологии, навыки, опыт, сообщения, аналитика |

- **Клиент API** — `openapi-fetch` с типами, сгенерированными из OpenAPI бэкенда (`src/api/schema.d.ts`). После изменения API перегенерируйте типы: `vue-tsc` сразу покажет, что сломалось.
- **Авторизация.** Access-токен хранится только в памяти; после перезагрузки страницы сессия восстанавливается через refresh-cookie. `adminCall()` подставляет токен, при 401 один раз обновляет его и повторяет запрос, без сессии — уводит на вход.
- **Ошибки API** (RFC 9457) превращаются в `ApiError`; 422 раскладывается по полям формы.
- **Аналитика.** При каждом переходе SPA отправляет `POST /api/page-views` (кроме `/admin`).
- **Тексты о себе** — `src/config/site.ts`; иконки технологий — с CDN Simple Icons по полю `icon`.

```bash
docker compose exec frontend npm run api:types         # перегенерировать типы из /api/doc.json
docker compose exec frontend npm run verify            # oxlint, ESLint, Prettier, vue-tsc, Vitest
docker compose exec frontend npm install <package>
docker compose logs -f frontend                        # вывод Vite
```

## Полезные команды

```bash
docker compose up -d                                   # поднять стек
docker compose exec php bin/console doctrine:migrations:migrate
docker compose exec php bin/console doctrine:fixtures:load   # тестовые данные для разработки (очищает БД)
docker compose exec php composer test                  # все тесты бэкенда, база portfolio_test создаётся сама
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

## Тесты

| Набор | Где | Что проверяет |
|---|---|---|
| Unit | `backend/tests/Unit` | Обработчики очереди, сервисы, кеш, слушатели, сущности — без ядра и БД: репозитории заменены моками, файлы лежат во Flysystem в памяти, почта и Telegram уходят в шпионы из `tests/Support` |
| Integration | `backend/tests/Integration` | Код, которому нужен контейнер Symfony: фикстуры, репозиторий пользователей, отметка недоставленных сообщений |
| Functional | `backend/tests/Functional` | Каждый эндпоинт API на тестовой БД: ответы, ошибки problem+json, доступ, лимиты, очередь, сброс кеша, число SQL-запросов |
| Frontend | `frontend/src/__tests__` | Vitest + Vue Test Utils: API-клиент и токены, composables, роутер (вход, аналитика), формы входа, контактов и проекта, список проектов |

```bash
docker compose exec php composer test                  # все тесты бэкенда (172)
docker compose exec php composer test:unit             # только unit, пара секунд
docker compose exec php composer test:coverage         # покрытие (pcov) → backend/reports/coverage/index.html
docker compose exec php composer test:mutation         # мутационное тестирование, ~4 мин → backend/reports/mutation.html
docker compose exec frontend npm run test:coverage     # покрытие фронтенда → frontend/coverage/index.html
```

**Покрытие.** Бэкенд — 97,7 % строк (фабрики, Story и фикстуры в расчёт не входят). Фронтенд (64 теста) — composables 100 %, API-клиент 91 %, стор авторизации 89 %, роутер 74 %; страницы, где в основном разметка, покрыты меньше, а логика из них (фильтр в URL, автослаг, разбор ошибок) проверена отдельно.

**Мутационное тестирование.** Покрытие говорит только о том, что строка выполнилась. [Infection](https://infection.github.io) вносит в доменный код (`Entity`, `Service`, `Security`, `Cache`, `MessageHandler`, `EventListener`, `Messenger`, `Validation`) маленькие ошибки — меняет `>` на `>=`, убирает вызов, подменяет значение — и проверяет, что какой-то тест это заметит. Сейчас ловится 392 из 409 мутантов (MSI 95,8 %); оставшиеся 17 эквивалентны исходному коду (например, `(string)` у результата, который и так строка, или EXIF-ориентация по умолчанию 1 вместо 0 — обе означают «не поворачивать»). Порог в `infection.json5` — 90 %: если тесты перестанут что-то проверять, команда упадёт. Мутанты на первом прогоне нашли непроверенное сохранение полей при обновлении проекта, очистку cookie при выходе, удаление просроченных refresh-токенов и мёртвый код в сбросе кеша.

Infection работает в один поток: тесты делят Redis и хранилище лимитов, параллельные процессы мешали бы друг другу.

## Качество кода

```bash
docker compose exec php composer lint                  # стиль + PHPStan
docker compose exec php composer cs-fix                # автоматически исправить стиль
docker compose exec frontend npm run verify            # проверки фронтенда
docker compose exec frontend npm run format            # Prettier для src/
```

Pre-commit хук (`.githooks`, подключает `setup.sh`) запускает `composer lint`, если в коммите есть PHP-код, и `npm run verify`, если есть файлы `frontend/`.

- PHP-CS-Fixer: наборы `@Symfony`, `@Symfony:risky`, `@PHP8x4Migration`, плюс `declare(strict_types=1)` и строгие сравнения — `backend/.php-cs-fixer.dist.php`
- PHPStan level 8 с расширениями Symfony и Doctrine — `backend/phpstan.dist.neon`

## IDE

- VS Code: расширение WSL, затем `code .` из папки проекта в Ubuntu
- PhpStorm: открыть `\\wsl$\Ubuntu\home\<user>\projects\portfolio`, PHP-интерпретатор — из Docker Compose (сервис `php`)

## Структура

```
backend/       Symfony API
frontend/      SPA на Vue 3 (публичный сайт + админка)
docker/        Dockerfile php, конфиг nginx
```
