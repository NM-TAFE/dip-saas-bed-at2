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
| `database/migrations/…_create_telescope_entries_table.php` | Telescope tables (run against MySQL in step 5) |
| `bootstrap/providers.php` | Both new providers registered. The installer rewrote the file to use fully qualified class names instead of `use` imports |

### 2. Add `pcntl` to the Dockerfile

- [ ] _pending_

### 3. Point Redis at the `redis` service

- [ ] _pending_

### 4. Send queued jobs to Redis

- [ ] _pending_

### 5. Give Telescope its own MySQL connection

- [ ] _pending_

### 6. Required `.env` values for Compose

- [ ] _pending_

### 7. Restart policy, scheduler, `.dockerignore`, minor clean-ups

- [ ] _pending_
