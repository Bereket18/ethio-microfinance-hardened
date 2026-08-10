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

## [Fixed] docker-compose.yml db healthcheck — 2026-08-10

**Bug found via live testing on a real machine** (first actual `docker
compose up` run, on Windows 11/Docker Desktop): the db service never
reported healthy, which blocked `app` and `nginx` from starting at all
(both correctly wait on `depends_on: condition: service_healthy`).

Root cause: the healthcheck command referenced `$MYSQL_ROOT_PASSWORD`
intending it to resolve inside the container at healthcheck-run time.
Docker Compose's own variable interpolation pass also matches bare `$VAR`
syntax (not just `${VAR}`) and runs *before* any container starts — since
no compose-level variable named `MYSQL_ROOT_PASSWORD` exists (only
`DB_ROOT_PASS` does), Compose silently substituted an empty string right
in the YAML. The healthcheck was actually running
`mysqladmin ping -u root -p"" ` — authenticating with a blank password
against a database whose real root password was whatever `.env` set,
so it failed every time.

Fix: escaped the dollar sign (`$$MYSQL_ROOT_PASSWORD`) so Compose leaves
it untouched at parse time, deferring resolution to the container's own
shell at runtime, where the real env var actually exists.

**Lesson for anywhere else `$VAR` appears inside a CMD-SHELL healthcheck
string in this file**: always double-check whether it needs `$$` escaping
— this class of bug is easy to introduce and the failure mode (silent
empty-string substitution, not a parse error) doesn't announce itself
clearly.

## [Fixed] .dockerignore excluded migrate_hash_passwords.php — 2026-08-10

**Bug found via live testing**: `.dockerignore` listed
`migrate_hash_passwords.php` under "never bake into the image" — but
`README.md` documents running it via
`docker compose exec app php migrate_hash_passwords.php`, which requires
the file to exist inside the running container. These two directly
contradicted each other; the exec command failed until the exclusion was
removed and the image rebuilt.

Fix: removed the exclusion. If a fully-migrated image should not carry
the script long-term, delete it from inside the running container
manually after use, rather than excluding it at build time and breaking
first-time setup.

## [Verified] Full stack confirmed working end-to-end — 2026-08-10

After three real bugs found and fixed via live testing (the `$$` escaping
issue, `mysqladmin` → `mariadb-admin` rename, and the `.dockerignore`
exclusion contradiction above), the complete stack was brought up clean
on Windows 11 + Docker Desktop and verified working for real:

- `docker compose ps` — all three services (`nginx`, `app`, `db`) report
  `healthy`
- `migrate_hash_passwords.php` ran successfully inside the container
- **Logged into the dashboard through the browser** at
  `http://localhost:8080`, through the full path: nginx's rate-limited
  reverse proxy → PHP-FPM (non-root, bounded worker pool) → prepared-
  statement login query → bcrypt password verification → least-privilege
  MariaDB user

This is the first genuine end-to-end confirmation that Parts 1-6 of the
Master Plan (threat modeling through detection/logging) work together as
an actual running system, not just as individually-reviewed files.

**Still to verify** (Part 9's self-test procedure hasn't been run yet):
the `nmap` scan confirming port 3306 is genuinely unreachable from
outside, the `?bypass=true` / default-credential / role=admin checks
against the live deployment, and a load test against the rate limiter.

## [Done] Remaining application modules — 2026-08-10

All modules flagged as open items are now hardened, following the same
pattern as everything else, and verified with live attack attempts
against a running instance (not just reviewed).

- **`modules/loans/approve.php`** — **found a second, independent admin
  bypass backdoor**: `?force=true` granted admin role to any visitor,
  completely separate from the `?bypass=true` one already removed from
  `auth.php`/`admin/dashboard.php`. Same severity, different file.
  Removed entirely. Also: raw SQL → prepared statements; approve/reject
  actions (state-changing, reachable via GET) now require the same CSRF
  token pattern already used elsewhere in the admin panel. **Verified
  live**: logged in as a customer, hit `?force=true`, got redirected
  away rather than granted admin.
- **`modules/loans/apply.php`** — previously had **no authentication
  check at all**, and defaulted to `user_id = 1` (the admin account) if
  no session existed, meaning an unauthenticated visitor's loan
  application would attach to the admin's account. Now requires login,
  uses prepared statements, validates the amount, adds CSRF, stops
  leaking raw `mysqli_error()` text to the browser. **Verified live**:
  application correctly attributed to the logged-in user's real ID.
- **`modules/loans/repay.php`** — **IDOR fixed**: the original query
  updated any loan by ID with no check it belonged to the logged-in
  user, so any customer could pay down (or manipulate) anyone else's
  loan. Added an ownership check (`AND user_id = ?`) and a bounds check
  (payment can't exceed the remaining balance). **Verified live**: a
  customer's attempt to repay another user's loan was rejected, target
  loan's balance confirmed unchanged.
- **`modules/loans/dashboard.php`** — raw SQL → prepared statements,
  all output escaped (was previously raw, including the username).
- **`modules/accounts/deposit.php`** and **`withdraw.php`** — wired up
  to the `process_transaction()` helper (built earlier in
  `includes/functions.php`, never actually used by these endpoints
  until now). **`withdraw.php` previously had no balance check at
  all** — could withdraw any amount, including far more than the
  account held, going negative with no limit. Also fixed a dead
  `$_SESSION['balance']` reference (that key is never set anywhere in
  this codebase, so the displayed balance was always $0.00 regardless
  of the real value) — now fetched fresh from the database. **Verified
  live**: an attempted $999,999,999 withdrawal against a $3,000 balance
  was rejected, balance confirmed unchanged.
- **`admin/manage_users.php`** — raw SQL → prepared statements. The
  make_admin/delete links generated by `admin/dashboard.php` already
  included a CSRF token, but this file never actually validated it —
  wired up `require_csrf_token()` so that token does real work. Added a
  small safety check preventing an admin from deleting their own
  currently-logged-in account. All output escaped.
- **`modules/accounts/create.php`** — confirmed empty (0 bytes), not
  linked from anywhere in the app. No action needed; noted here so it's
  clear this was checked, not missed.

## Not yet started

- Part 9's full self-test procedure against the live stack (nmap,
  bypass/default-credential checks, load test against rate limiter)
- Self-signed TLS on the nginx reverse proxy (still plain HTTP)
