# Ethio Microfinance — Hardened Deployment

A microfinance web app hardened for a security-course attack/defense
exercise. See `CHANGELOG.md` for the full list of vulnerabilities found and
fixed, and the Master Plan document for the concepts behind each decision.

## Prerequisites

- Docker Desktop (or Docker Engine + Compose plugin) installed and running
- This exercise runs on a shared classroom LAN — build and pull any needed
  images *before* going offline, since `docker compose build` needs
  internet access the first time

## First-time setup

```bash
cp .env.example .env
```

Edit `.env` and set real values for `DB_PASS` and `DB_ROOT_PASS` — pick
strong, unique passwords. Do not commit this file (it's gitignored).

```bash
docker compose up -d --build
```

First run only — the schema imports automatically via
`docker-entrypoint-initdb.d` (see `docker/mysql/init/`), but **run the
password migration once the stack is up**, since the shipped schema seeds
demo accounts with plaintext passwords that need converting to real bcrypt
hashes:

```bash
docker compose exec app php migrate_hash_passwords.php
```

Then visit `http://localhost:8080/modules/users/login.php` (or whatever
`APP_PORT` you set in `.env`).

**Change the demo account passwords before the actual exercise starts** —
`admin/admin123` and the others are public knowledge from the shared
starter codebase every team began from.

## Architecture

```
LAN → [nginx: only published port] → [app: PHP-FPM, internal-only] → [db: internal-only, no published port]
```

Only nginx's port is reachable from the LAN. Verify this after first
bringing the stack up:

```bash
# From another machine on the LAN, replace <your-ip> with your actual IP
nmap -sV -p- <your-ip>
# Should show only the nginx port (default 8080). Port 3306 should not appear.
```

See the Master Plan document, Part 5, for the full reasoning behind every
piece of this setup (network segmentation, non-root containers, resource
limits, health checks).

## Common commands

```bash
docker compose logs -f app          # tail the app container's logs
docker compose logs -f nginx        # tail nginx's logs (includes rate-limit rejections)
docker compose ps                   # check health status of all three services
docker compose down                 # stop everything
docker compose down -v              # stop and WIPE the database volume (careful)
docker compose build app            # rebuild the app image after a code change
docker compose up -d                # apply a rebuilt image
```

## Rebuilding after a code change

The app image is self-contained (code is baked in via the Dockerfile, not
live-mounted), which means **code changes require a rebuild to take
effect**:

```bash
docker compose build app && docker compose up -d app
```

Re-run the self-test procedure (Master Plan Part 9) after any rebuild —
don't assume a previous clean test result still holds.

## Project structure

```
config/           DB + session configuration
includes/         Shared auth, validation, and security-logging functions
modules/          Application features (users, loans, accounts)
admin/            Admin-only panel
docker/           Docker Compose service configs (nginx, php-fpm, mysql init)
uploads/sql/      Original database schema (imported automatically)
migrate_hash_passwords.php   One-time password migration (see above)
CHANGELOG.md      Every security fix made, with what was verified and how
```
