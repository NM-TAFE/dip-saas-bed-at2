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

- [x] `compose.yaml` refuses to start until `TELESCOPE_DB_PASSWORD` and
  `MYSQL_ROOT_PASSWORD` are set. Compose reads them from `.env` for the `mysql`
  service, and Laravel reads the same `.env` for the `telescope` connection, so
  they always match.

`.env.example` (committed) has blank entries:

```dotenv
# Docker only: MySQL for Telescope (required by compose.yaml).
# Generate each with: openssl rand -hex 24
TELESCOPE_DB_PASSWORD=
MYSQL_ROOT_PASSWORD=
```

#### Generating the passwords

`openssl rand -hex 24` prints 24 random bytes as 48 hex characters. Hex is used
so the password has no characters that need quoting in `.env` or YAML.

Mac / Linux / Git Bash: append both to `.env` without showing them on screen:

```bash
printf '\n# Docker only: MySQL for Telescope (required by compose.yaml)\nTELESCOPE_DB_PASSWORD=%s\nMYSQL_ROOT_PASSWORD=%s\n' \
  "$(openssl rand -hex 24)" "$(openssl rand -hex 24)" >> .env
```

Windows PowerShell (no OpenSSL needed); paste each value into `.env`:

```powershell
$b = New-Object byte[] 24; [Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($b); -join ($b | ForEach-Object { '{0:x2}' -f $_ })
```

Check both are set without printing them (prints each name and length 48):

```bash
awk -F= '/^(TELESCOPE_DB_PASSWORD|MYSQL_ROOT_PASSWORD)=/{print $1, length($2)}' .env
```

> MySQL only applies these passwords the **first time** it creates the
> `mysql_data` volume. Changing `.env` later does nothing until the volume is
> recreated.

#### Changing the passwords

Needed if they are ever exposed (e.g. `docker compose config` output shared;
see 7d). Mac / Linux / Git Bash: replace both values in `.env` in place
without printing them:

```bash
sed -i.bak -E "s/^TELESCOPE_DB_PASSWORD=.*/TELESCOPE_DB_PASSWORD=$(openssl rand -hex 24)/; s/^MYSQL_ROOT_PASSWORD=.*/MYSQL_ROOT_PASSWORD=$(openssl rand -hex 24)/" .env && rm .env.bak
awk -F= '/^(TELESCOPE_DB_PASSWORD|MYSQL_ROOT_PASSWORD)=/{print $1, length($2)}' .env
```

(`-i.bak` works with both macOS and GNU `sed`.) On Windows, generate new values
with the PowerShell command above and paste them into `.env`.

Then recreate the volumes and the Telescope tables:

```bash
docker compose down -v      # deletes MySQL (Telescope) and Redis (Horizon) data
docker compose up -d --wait
docker compose exec app php artisan migrate --database=telescope --path=database/migrations/telescope --force
docker compose exec mysql sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" mysql -utelescope -e "select 1" >/dev/null && echo "telescope user login: ok"'
```

Expected result: both names print length 48, all services are healthy, the
migration reports `DONE`, and `telescope user login: ok`.

#### Fix found while testing: `php artisan serve --no-reload`

With the real `.env`, `/horizon` loaded but `/horizon/api/stats` returned 500
(Redis *Connection refused*) and `/telescope` returned 404.

Cause: `php artisan serve` handles web requests in a separate PHP server process.
Unless `--no-reload` is passed, it removes every environment variable from that
process except a short allow-list
(`vendor/laravel/framework/src/Illuminate/Foundation/Console/ServeCommand.php`,
`startProcess()`). Web requests therefore lost the step 3–5 Compose overrides
and fell back to `.env` (`REDIS_HOST=127.0.0.1`, Telescope off). Artisan
commands (Horizon, Tinker) were unaffected, which is why the earlier checks
passed.

`compose.yaml`:

```diff
-    command: php artisan serve --host=0.0.0.0 --port=8000
+    command: php artisan serve --host=0.0.0.0 --port=8000 --no-reload
```

Side effect: the server no longer restarts itself when `.env` changes. Run
`docker compose restart app horizon` after editing `.env`.

#### First full start

```bash
docker compose up -d --wait
docker compose exec app php artisan migrate --database=telescope --path=database/migrations/telescope --force
```

Then open <http://127.0.0.1:8000/horizon> and <http://127.0.0.1:8000/telescope>.

Check a queued job reaches Horizon end to end:

```bash
docker compose exec app php artisan tinker --execute 'Illuminate\Support\Facades\Artisan::queue("inspire");'
curl -s http://127.0.0.1:8000/horizon/api/jobs/completed
```

Expected result: the job `inspire` is listed with status `completed`. Queue a
built-in command rather than a closure: Tinker can't serialise closures written
in `--execute` code.

Results: `/up`, `/horizon`, `/horizon/api/stats` and `/telescope` all return
200, Telescope records requests in MySQL, and Horizon completes the queued job.

### 7. Restart policy, scheduler, `.dockerignore`, minor clean-ups

#### 7a. Restart policy

- [x] `restart: unless-stopped` on `app` and `horizon` in `compose.yaml`.

Horizon exits when it crashes or receives `horizon:terminate`, and nothing
started it again. Horizon workers also keep old code in memory, so after
changing jobs or app code:

```bash
docker compose exec horizon php artisan horizon:terminate
```

Docker restarts the container with the new code. `docker compose stop` still
stops it for good, since `unless-stopped` respects manual stops.

Check it:

```bash
docker inspect -f '{{.State.StartedAt}}' dip-saas-bed-at2-horizon-1
docker compose exec horizon php artisan horizon:terminate
docker inspect -f '{{.State.StartedAt}} restarts: {{.RestartCount}}' dip-saas-bed-at2-horizon-1
docker compose exec horizon php artisan horizon:status
```

Expected result: a new `StartedAt`, `restarts: 1`, and `Horizon is running.`

#### 7b. Scheduler

- [x] New `scheduler` service in `compose.yaml` running `php artisan schedule:work`,
  with the same build, volume, environment, `depends_on` and restart policy as `horizon`.

`routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

// Run by the `scheduler` service in compose.yaml.
Schedule::command('horizon:snapshot')->everyFiveMinutes();
Schedule::command('telescope:prune --hours=48')->daily();
```

- `horizon:snapshot`: Horizon's *Metrics* page (job throughput and runtime) stays
  empty without it.
- `telescope:prune --hours=48`: deletes Telescope entries older than 48 hours so
  MySQL doesn't keep growing.

Check it:

```bash
docker compose up -d --wait scheduler
docker compose exec scheduler php artisan schedule:list
docker compose exec scheduler php artisan horizon:snapshot
docker compose exec scheduler php artisan telescope:prune --hours=48
docker compose logs scheduler | grep horizon:snapshot   # after the next 5-minute mark
```

Expected result: `schedule:list` shows both tasks, the snapshot reports
*Metrics snapshot stored successfully*, prune reports *N entries pruned*, and
the log shows `Running ['artisan' horizon:snapshot] … DONE`.
#### 7c. Build the image once and share it

- [x] `app`, `horizon` and `scheduler` each had their own `build:` block, so
  Compose built three identical images (`dip-saas-bed-at2-app`, `-horizon`,
  `-scheduler`) and the same settings were repeated three times.

`compose.yaml` now has a shared `x-app` block merged into each service with `<<: *app`:

```yaml
# Shared by app, horizon and scheduler. Only app builds the image;
# the others reuse it by name (never pulled from Docker Hub).
x-app: &app
  image: dip-saas-bed-php
  pull_policy: never
  working_dir: /var/www/html
  volumes:
    - ./:/var/www/html
  environment: *app-env
  depends_on:
    redis:
      condition: service_healthy
    mysql:
      condition: service_healthy
  restart: unless-stopped

services:
  app:
    <<: *app
    build:
      context: .
      dockerfile: docker/Dockerfile
    ports:
      - "127.0.0.1:8000:8000"
    command: php artisan serve --host=0.0.0.0 --port=8000 --no-reload

  horizon:
    <<: *app
    command: php artisan horizon

  scheduler:
    <<: *app
    command: php artisan schedule:work
```

- `build:` is on `app` only. If every service had `build:`, Compose would still
  run the build three times, even with a shared `image:` name.
- `pull_policy: never` is needed on a fresh machine. Without it, Compose first
  tries to pull `dip-saas-bed-php` from Docker Hub for `horizon` and `scheduler`
  and prints *"pull access denied"* errors before it builds.
- A service's own key replaces the shared one entirely (e.g. `environment`), so
  extra env vars for one service must merge `*app-env` too (see 7d).
- After changing the Dockerfile: `docker compose build && docker compose up -d`.

Check it:

```bash
docker compose config                       # resolved services match the old file, plus image:
docker compose down && docker rmi dip-saas-bed-php
docker compose up -d --wait                 # one "Building … Built", no "Pulling"
docker compose ps --format 'table {{.Service}}\t{{.Image}}'
docker images | grep dip-saas-bed
```

Expected result: `app`, `horizon` and `scheduler` all show `dip-saas-bed-php`,
there is only one project image, and `/up`, `/horizon/api/stats` and
`/telescope` return 200.

> Old per-service images from before this change can be removed with
> `docker rmi dip-saas-bed-at2-app dip-saas-bed-at2-horizon dip-saas-bed-at2-scheduler`.
#### 7d. `PHP_CLI_SERVER_WORKERS`

- [x] `php artisan serve` handles one request at a time by default. The Horizon
  and Telescope dashboards poll their APIs every few seconds, so their requests
  queue behind API calls, which is very noticeable on Windows (slower mounts).

`compose.yaml` (`app` only):

```yaml
  app:
    <<: *app
    environment:
      <<: *app-env
      # Parallel request workers so dashboard polling doesn't queue behind
      # API calls. Only honoured with --no-reload.
      PHP_CLI_SERVER_WORKERS: 4
```

- Laravel only passes `PHP_CLI_SERVER_WORKERS` to PHP's built-in server when
  `--no-reload` is set (step 6). Otherwise it warns and starts a single worker.
- `environment` must merge `<<: *app-env`, because a service's own `environment`
  replaces the one from `x-app` entirely (7c).

Check it:

```bash
docker compose up -d --wait app
docker compose exec app printenv PHP_CLI_SERVER_WORKERS
docker compose exec app sh -c 'for p in /proc/[0-9]*; do tr "\0" " " < $p/cmdline 2>/dev/null; echo; done | grep -c "^/usr/local/bin/php -S"'
```

Expected result: `4`, and 5 `php -S` processes (1 main process + 4 workers).

> **Careful with `docker compose config`.** It prints the fully resolved file,
> including `MYSQL_PASSWORD` and `MYSQL_ROOT_PASSWORD` from `.env`. Don't paste
> its output into chats, issues or screenshots. To check one value, use
> `docker compose exec <service> printenv <NAME>`.
#### 7e. `.dockerignore`

- [x] Added as a **safeguard**, not for speed.

The planned reason (faster builds) turned out not to apply. With BuildKit (the
default in Docker Desktop), a build only sends files that a `COPY`/`ADD` asks
for. `docker/Dockerfile` copies no project files, since the code is bind-mounted,
so the build context was already **2 bytes**:

```bash
docker builder prune -f
docker compose build --progress=plain app 2>&1 | grep 'transferring context'
# transferring context: 2B done
```

The risk is a later `COPY . .` (e.g. a production image), which would bake
`.env` (Atlas URI, MySQL passwords), `vendor` and `node_modules` into the image.
`.dockerignore` excludes:

| Pattern | Why |
|---------|-----|
| `.env`, `.env.*` (keeps `!.env.example`) | Secrets |
| `vendor`, `node_modules` | ~316 MB; host-built, install inside the image instead |
| `.git` | History, not needed in an image |
| `storage/app/*`, `storage/logs/*`, `storage/framework/{cache,sessions,views}/*`, `bootstrap/cache/*.php` | Local uploads, logs and caches |
| `.DS_Store`, `.idea`, `.vscode` | Editor / OS clutter |

Check it with a throwaway `COPY . .` build (Dockerfile kept outside the repo):

```bash
printf 'FROM alpine:3.20\nCOPY . /ctx\nRUN cd /ctx && for f in .env .env.testing .env.example vendor node_modules .git artisan; do [ -e "$f" ] && echo "present  $f" || echo "excluded $f"; done && du -sh /ctx\n' > /tmp/Dockerfile.ignore-test
docker build --no-cache --progress=plain -f /tmp/Dockerfile.ignore-test -t ignore-test . 2>&1 | grep -E 'transferring context|present|excluded|/ctx$'
docker rmi ignore-test && rm /tmp/Dockerfile.ignore-test
```

Expected result: `.env`, `.env.testing`, `vendor`, `node_modules` and `.git`
are *excluded*, while `.env.example` and `artisan` are *present*. The context is
under 1 MB, compared with ~340 MB without the file.

Windows: run the check from Git Bash, or use `$env:TEMP\Dockerfile.ignore-test`
in PowerShell.
#### 7f. `zip` extension

- [x] The Dockerfile installed `libzip-dev` (the zip library headers) but never
  built the PHP `zip` extension, so Composer fell back to the `unzip` command.

`docker/Dockerfile`:

```diff
-    && docker-php-ext-install pdo_mysql pcntl \
+    && docker-php-ext-install pdo_mysql pcntl zip \
```

Rebuild and check:

```bash
docker compose build app && docker compose up -d --wait
docker compose run --rm --no-deps app php -m | grep -iE '^(zip|pcntl|posix|mongodb|redis|pdo_mysql)$'
docker compose run --rm --no-deps app composer diagnose | grep -i zip
```

Expected result: all six extensions are listed, and `composer diagnose` reports
`zip: extension present` (previously `zip: extension not loaded`).

> Changing the `RUN` line rebuilds the whole layer, including compiling the
> `mongodb` PECL extension, so expect a rebuild of about 2–3 minutes. Later
> builds are cached.

## Not done

- **Expose MySQL on `127.0.0.1:3306`:** skipped. It is only needed to browse
  Telescope tables in a GUI client, and it would clash with any MySQL already
  running locally on 3306. To add it: `ports: ["127.0.0.1:3306:3306"]` on `mysql`.
- **"3 high vulnerabilities" warning on `FROM php:8.5-cli`:** reported by the
  editor's Docker extension for the official base image, not for anything added
  here. It is acceptable for a local dev image. Run `docker compose build --pull`
  now and then to pick up patched base images.

## Final check

```bash
docker compose up -d --wait
docker compose ps
curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8000/up
curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8000/horizon/api/stats
curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8000/telescope
```

Expected result: five services up (`app`, `horizon`, `scheduler`, `mysql`,
`redis`), and each URL returns `200`.
