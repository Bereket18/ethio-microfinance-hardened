# Ethio Microfinance — Security Hardening Changelog

Every entry below is a change actually made and verified against a live
PHP + MariaDB instance (not just written and assumed correct). See the
Master Plan document for the full concept explanations behind each fix.

---

## [Done] config/database.php
- Reads DB credentials from environment variables (Docker-ready), falls back
  to local defaults.
- Generic error message on connection failure — no longer leaks connection
  internals to the browser.

## [Done] config/session.php
- Removed predictable `md5(IP + time())` session ID generator.
- Cookies now set `HttpOnly` and `SameSite=Lax`.

## [Done] includes/auth.php
- Removed the `?bypass=true` admin authentication backdoor entirely.
- `check_auth()` is now the single, no-exceptions gate for both login and
  role checks.

## [Done] includes/validation.php
- `validate_csrf_token()` now does real work (was previously `return true`
  unconditionally). Tokens generated with `random_bytes()`, compared with
  `hash_equals()`.

## [Done] includes/functions.php
- Removed `eval()`-based interest calculation, `system()`-based command
  execution, and `unserialize()`-based data processing — all three were
  unauthenticated remote-code-execution paths.
- Added a real `upload_file()` helper: validates actual image content via
  `getimagesize()`, random filename, 2MB cap.

## [Done] includes/security_log.php (new file)
- Wires up the previously-unused `login_attempts` and `audit_log` tables.
- `is_rate_limited()` — brute-force throttle (5 failed attempts / 5 min).
- `detect_and_log_suspicious_input()` — signature-based tripwire for
  SQLi/XSS-looking input.

## [Done] modules/users/login.php
- Raw SQL → prepared statement.
- Plaintext password comparison → `password_verify()`.
- Added CSRF token requirement, `session_regenerate_id()` on login.

## [Done] modules/users/register.php
- Raw SQL → prepared statement, `password_hash()` on signup.
- **Privilege escalation fix**: `role` was read from client POST data
  (`role=admin` in a raw request made you an admin instantly) — now
  hardcoded server-side to `customer` for every self-registration.

## [Done] modules/users/admin_login.php
- Raw SQL → prepared statement.
- Rate-limited and logged via `security_log.php`.
- No bypass path (removed with `auth.php`'s bypass).

## [Done] admin/dashboard.php
- **Deduplicated** — the entire file was accidentally duplicated
  top-to-bottom in the original; the admin bypass existed independently in
  *both* copies.
- Removed a dead, wired-to-nothing "Command Executor" / "Load Page" admin
  UI element.
- All output escaped (`htmlspecialchars`) — previously printed usernames/
  emails raw (stored XSS risk).
- Added a live "Recent Security Events" panel reading from `audit_log`.

## [Done] modules/users/profile.php — 2025-08-05
- **IDOR fixed**: `?view=<id>` previously returned *any* user's full
  record (including balance, email) to any logged-in visitor, no ownership
  check at all. Now: viewing your own ID always works; viewing anyone
  else's requires `is_admin()`; anything else is denied with a clear
  message, not a silent data leak.
  **Verified live**: logged in as a customer (`betty`), confirmed
  `?view=1` (admin's record) is denied; logged in as `admin`, confirmed
  `?view=3` (betty's record) succeeds and is labeled "Viewing as admin."
- **SQL injection fixed**: all three queries (own profile, customer_info,
  viewed profile) converted from raw string concatenation to prepared
  statements. `?view=` value is also cast to `(int)` as defense in depth.
  **Verified live**: `?view=1 OR 1=1` was neutralized by the int cast
  before it ever reached SQL.
- **Unrestricted file upload fixed**: the endpoint now requires a valid
  CSRF token and routes through the `upload_file()` helper (real image
  content validation, random filename, 2MB cap) instead of trusting the
  client-supplied filename directly.
  **Verified live**: a PHP webshell renamed `.jpg` was rejected
  ("File is not a valid image"); a genuine PNG was accepted and saved
  under a random generated name; a request with a missing/invalid CSRF
  token was rejected regardless of file validity.
- All output escaped with `htmlspecialchars()` (was previously raw).

## [New] migrate_hash_passwords.php — 2025-08-05
- **Bug found during verification, not in the original audit**: the
  seeded demo accounts (`admin/admin123`, etc.) have plaintext passwords
  in the SQL dump. Once `password_verify()` went live in `login.php`,
  these accounts would silently fail to log in (correct security
  behavior, but breaks the demo).
- One-time migration script: rehashes any non-bcrypt password with
  `password_hash()`. Idempotent — detects and skips already-hashed values,
  safe to re-run. **Verified live**: ran against all 8 seed accounts,
  confirmed all now store real `$2y$...` bcrypt hashes, confirmed login
  works end-to-end afterward.

---

## [New] Full Docker environment — 2026-08-09

Built out everything Part 5 of the Master Plan specified but hadn't yet
been implemented, plus closed two Part 12 open items along the way:

- **`Dockerfile`** — PHP-FPM (Alpine), non-root `appuser`, mysqli extension,
  `fcgi` package for healthchecks. Code is baked in via `COPY` (image is
  self-contained; see note on `docker-compose.yml` below).
- **`docker/php/php-hardening.ini`** — defense-in-depth: disables
  `eval`/`system`/`exec`-class functions at the PHP engine level (belt and
  suspenders on top of already removing them from our own code), hides
  `X-Powered-By`, disables `display_errors`.
- **`docker/php/www.conf`** — PHP-FPM pool runs as non-root, bounded worker
  pool (`pm.max_children=12`) for graceful degradation under load, exposes
  a native `/ping` endpoint for healthchecks.
- **`docker/nginx/nginx.conf`** — reverse proxy, the *only* published port
  in the whole stack. Implements Part 7.2's rate limiting: general traffic
  capped at 10 req/s, login endpoints specifically capped at 2 req/s
  (brute-force mitigation at the edge, before requests even reach PHP).
  Also blocks direct access to `config/`, `includes/`, `.git/`, `docker/`,
  and PHP execution inside `uploads/`.
- **`docker/mysql/init/01-restrict-privileges.sql`** — closes the "add a
  least-privilege DB user" open item: the app's DB user gets exactly
  `SELECT, INSERT, UPDATE, DELETE` on the one database it needs, not
  `ALL PRIVILEGES` and not root.
- **`docker-compose.yml`** — wires all three services together with a
  two-network design: `edge` (carries the published port to nginx only)
  and `internal` (genuinely `internal: true` — app and db have no outbound
  route at all, real defense in depth beyond just "port not published").
  Health checks and `restart: unless-stopped` on every service (Part 7.4).
  Resource limits on every service (Part 5.4/7). Non-root + read-only
  root filesystem on app and nginx, with `tmpfs` mounts for the specific
  paths that need to stay writable (PHP sessions, nginx's own runtime
  files).
- **`.env.example`**, **`.dockerignore`**, **`README.md`** — setup docs and
  making sure secrets/dev files never end up baked into the image.

**Verified so far**: YAML syntax (`python3 -c "import yaml..."`), nginx
config syntax (real `nginx -t` against the actual config — clean except for
one expected DNS-resolution message for the Docker-internal hostname
`app`, which only resolves inside the Compose network). **Not yet
verified**: an actual `docker compose up` end-to-end run — this sandbox has
no Docker daemon available, so that step needs to happen on your machine.

**Design correction made during review**: the app service originally both
baked code into the image (`Dockerfile COPY`) *and* bind-mounted the same
path from the host at runtime — the mount would have silently made the
`COPY` pointless. Removed the redundant bind mount; the app image is now
genuinely self-contained (code changes require `docker compose build app`
to take effect, which `README.md` documents).

---

## Not yet started

- `modules/loans/{apply,approve,dashboard,repay}.php`
- `modules/accounts/{create,deposit,withdraw}.php`
- `admin/manage_users.php`
- An actual `docker compose up` end-to-end test run (needs to happen on
  a machine with a real Docker daemon — see README.md)
- Self-signed TLS on the nginx reverse proxy (still plain HTTP)
