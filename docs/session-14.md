# Session 14 – Docker, Horizon & Telescope

Running the Laravel API in Docker with Horizon (queue dashboard) and Telescope
(debug dashboard). MongoDB stays on Atlas (cloud), so there is no Mongo
container.

| Service   | Purpose                                  |
|-----------|------------------------------------------|
| `app`     | Laravel API (`php artisan serve`) on `127.0.0.1:8000` |
| `horizon` | Queue workers (`php artisan horizon`)    |
| `redis`   | Queue backend for Horizon                |
| `mysql`   | Telescope storage only (Telescope does not support MongoDB) |

## Why the extra steps?

The starting `docker/Dockerfile` and `compose.yaml` did not run Horizon or
Telescope out of the box:

1. Horizon and Telescope were not installed.
2. The PHP image lacked the `pcntl` extension Horizon needs.
3. `REDIS_HOST=127.0.0.1` points at the container itself, not the `redis` service.
4. `QUEUE_CONNECTION=database` sends jobs to MongoDB; Horizon only reads Redis.
5. Telescope stores to `DB_CONNECTION` (`mongodb`) by default, and the stock
   `mysql` connection shares `DB_*` env vars with MongoDB.

## Mac and Windows

Only Docker Desktop is needed on either OS. On Windows the repo can be cloned
anywhere (e.g. `C:\Users\you\code\...`); no WSL setup is required.

- **Run Composer in the container, not on the host.** Horizon requires
  `ext-pcntl` and `ext-posix`, which don't exist in PHP on Windows, so
  `composer install` on a Windows host fails. This works on any OS:

  ```bash
  docker compose run --rm --no-deps app composer install
  ```

- **Windows speed:** the project folder is bind-mounted into the containers.
  Mounts from a Windows drive are slower than on Mac, so the first requests and
  Horizon startup can lag. This is expected; it does not affect correctness.
- **Line endings:** `.gitattributes` has `* text=auto eol=lf`, so Windows
  checkouts keep LF endings and the Dockerfile and `artisan` work unchanged.
- **Docker-only settings live in `compose.yaml`** (see step 3), so no one has to
  edit `.env` differently for their OS.
- Images are multi-arch (`php`, `redis`, `mysql`), so Apple Silicon and
  x86 machines pull the right build automatically.

## Steps

### 1. Install the packages

```bash
composer require laravel/horizon laravel/telescope
```

- [x] Publish config, service providers and migration:

```bash
php artisan horizon:install
php artisan telescope:install
```

Files these commands created or changed:

| File | What it is |
|------|------------|
| `app/Providers/HorizonServiceProvider.php` | Horizon setup and the `viewHorizon` gate (who can open `/horizon` outside `local`) |
| `app/Providers/TelescopeServiceProvider.php` | Telescope filters and the `viewTelescope` gate (who can open `/telescope` outside `local`) |
| `config/horizon.php` | Horizon workers, queues and environments |
| `config/telescope.php` | Telescope storage connection and watchers |
| `database/migrations/…_create_telescope_entries_table.php` | Telescope tables (moved to `database/migrations/telescope/` and run against MySQL in step 5) |
| `bootstrap/providers.php` | Both new providers registered. The installer rewrote the file to use fully qualified class names instead of `use` imports |

### 2. Add `pcntl` to the Dockerfile

- [x] Horizon uses `pcntl` to start, signal and stop its worker processes. The
  official `php` image includes `posix` but not `pcntl`, so it has to be compiled in.

`docker/Dockerfile`:

```diff
-    && docker-php-ext-install pdo_mysql \
+    && docker-php-ext-install pdo_mysql pcntl \
```

Check the image has every extension the app needs:

```bash
docker build -f docker/Dockerfile -t saas-bed-php:check .
docker run --rm saas-bed-php:check php -m | grep -iE '^(pcntl|posix|mongodb|redis|pdo_mysql)$'
docker rmi saas-bed-php:check
```

Expected output: `mongodb`, `pcntl`, `pdo_mysql`, `posix`, `redis`.

> `docker compose build` fails with *"required variable TELESCOPE_DB_PASSWORD is
> missing a value"* until the step 6 `.env` values exist. Compose checks
> variables before it builds anything, which is why this check uses `docker build`
> directly.

### 3. Point Redis at the `redis` service

- [x] Inside a container `127.0.0.1` is the container itself, so `REDIS_HOST`
  must be the Compose service name `redis`.

Docker-only values go in a shared `environment` block in `compose.yaml`, not
`.env`. Real environment variables take precedence over `.env` in Laravel, so
the containers use `redis` while `php artisan` on the host still uses
`127.0.0.1`. This works the same on Mac and Windows.

```yaml
x-app-env: &app-env
  REDIS_HOST: redis

services:
  app:
    environment: *app-env
  horizon:
    environment: *app-env
```

Check it (the inline dummy passwords only satisfy Compose's variable check until step 6):

```bash
# Mac / Linux / Git Bash
export TELESCOPE_DB_PASSWORD=dummy MYSQL_ROOT_PASSWORD=dummy
# Windows PowerShell
# $env:TELESCOPE_DB_PASSWORD="dummy"; $env:MYSQL_ROOT_PASSWORD="dummy"

docker compose up -d redis
docker compose run --rm --no-deps app php artisan tinker --execute 'echo config("database.redis.default.host"), " -> ", Illuminate\Support\Facades\Redis::ping() ? "PONG" : "fail", PHP_EOL;'
docker compose down
docker volume rm dip-saas-bed-at2_mysql_data   # test run creates an empty one
```

Expected output: `redis -> PONG`.

> If you ever run `php artisan config:cache`, the cached config ignores
> environment variables. Run `php artisan config:clear` before using Docker.

### 4. Send queued jobs to Redis

- [x] `.env` has `QUEUE_CONNECTION=database`, which uses the MongoDB queue driver.
  Horizon only processes the `redis` connection (`config/horizon.php` supervisor
  `'connection' => 'redis'`), so in Docker jobs must go to Redis.

Added to the shared block in `compose.yaml`:

```yaml
x-app-env: &app-env
  REDIS_HOST: redis
  QUEUE_CONNECTION: redis
```

- Outside Docker, `.env` still uses the MongoDB queue (`php artisan queue:work`).
- Failed jobs are still recorded in MongoDB (`QUEUE_FAILED_DRIVER` `database-uuids`).
  Horizon also lists them on its *Failed Jobs* page.

Check a job lands on Redis (with the dummy passwords from step 3):

```bash
docker compose up -d redis
docker compose run --rm --no-deps app php artisan tinker --execute 'Illuminate\Support\Facades\Queue::push(fn () => null); echo config("queue.default"), " -> jobs on redis default queue: ", Illuminate\Support\Facades\Queue::size(), PHP_EOL;'
docker compose down -v   # -v throws away the test job and volumes
```

Expected output: `redis -> jobs on redis default queue: 1`. A
`file_get_contents(eval()'d code)` warning is harmless: it comes from Tinker
serialising a closure written in `--execute` code.

### 5. Give Telescope its own MySQL connection

- [x] Telescope can't store entries in MongoDB. Out of the box it uses
  `env('DB_CONNECTION')` (`mongodb` here), and the stock `mysql` connection reads
  the same `DB_DATABASE`/`DB_HOST` vars as MongoDB.

`config/database.php`: new `telescope` connection with its own variables:

```php
'telescope' => [
    'driver' => 'mysql',
    'host' => env('TELESCOPE_DB_HOST', '127.0.0.1'),
    'port' => env('TELESCOPE_DB_PORT', '3306'),
    'database' => env('TELESCOPE_DB_DATABASE', 'telescope'),
    'username' => env('TELESCOPE_DB_USERNAME', 'telescope'),
    'password' => env('TELESCOPE_DB_PASSWORD', ''),
    // charset, collation, strict… as the stock mysql connection
],
```

`config/telescope.php`:

```diff
-    'enabled' => env('TELESCOPE_ENABLED', true),
+    'enabled' => env('TELESCOPE_ENABLED', false),
 ...
-            'connection' => env('DB_CONNECTION', 'mysql'),
+            'connection' => env('TELESCOPE_DB_CONNECTION', 'telescope'),
```

`compose.yaml` shared block:

```yaml
  TELESCOPE_ENABLED: "true"
  TELESCOPE_DB_HOST: mysql
```

- Telescope is **off by default**, so the app runs outside Docker without MySQL.
  Compose turns it on in the containers. Tests already disable it in `phpunit.xml`.
- The database name and username match the `mysql` service (`telescope`), so only
  the password needs setting (step 6).

#### Running the Telescope migration

The Telescope migration was moved to its own folder:

```bash
mkdir -p database/migrations/telescope
git mv database/migrations/2026_10_26_050911_create_telescope_entries_table.php database/migrations/telescope/
```

A plain `php artisan migrate` only scans the top level of
`database/migrations/`, so it keeps running just the app's MongoDB migrations.
Otherwise it would try to create the Telescope tables too, failing on the host
(no MySQL) and in Docker (tables already exist).

Create the Telescope tables with its own command, tracked in MySQL:

```bash
docker compose run --rm app php artisan migrate --database=telescope --path=database/migrations/telescope
```

Check it (with dummy passwords from step 3; `-e` is needed until the password is in `.env`):

```bash
docker compose up -d --wait mysql redis
docker compose run --rm --no-deps -e TELESCOPE_DB_PASSWORD=dummy app sh -c "php artisan migrate --database=telescope --path=database/migrations/telescope --force && php artisan inspire"
docker compose run --rm --no-deps -e TELESCOPE_DB_PASSWORD=dummy app php artisan tinker --execute 'echo DB::connection("telescope")->table("telescope_entries")->count(), PHP_EOL;'
docker compose down -v
```

Expected result: tables `migrations`, `telescope_entries`,
`telescope_entries_tags` and `telescope_monitoring` exist in MySQL, and the entry
count is above 0.

### 6. Required `.env` values for Compose

- [ ] _pending_

### 7. Restart policy, scheduler, `.dockerignore`, minor clean-ups

- [ ] _pending_
