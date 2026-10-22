# Full deployment plan: GitHub Pages + PHP API

## Status and safety

This repository is a development prototype, not a production electronic health record. Use synthetic data only. This setup separates the static interface from a PHP API, but it does **not** certify the app for real patient data or live RHU use.

GitHub Pages hosts only static files. The PHP API and database run on a separate service. Do not put passwords, database files, private keys, patient records, or API credentials in this repository or Pages artifacts.

## Recommended initial hosting

For a low-friction staging deployment, use **Railway** with the included Docker image and a persistent volume mounted at `/data`. The image runs PHP 8.4 + Apache + PDO SQLite; `CHITS_DB_PATH=/data/chits.sqlite` stores the database on the volume. This is suitable for a synthetic-data staging/demo only. A volume-backed SQLite database is not my recommendation for a multi-user production RHU deployment; before that, migrate to managed PostgreSQL/MySQL, implement and test migrations and backup/restore, and complete an independent security review.

Alternative: a managed PHP host with persistent storage. Verify PHP 8.2+, `pdo_sqlite`, `mbstring`, HTTPS, persistent disk semantics, scheduled encrypted backups, restore testing, logs, and access controls before selecting it. Never use PHP's built-in development server as public hosting.

## 1. Backend service

1. Create a Railway project from this repository and configure its service to build from `deploy/backend/Dockerfile` (Dockerfile path: `deploy/backend/Dockerfile`).
2. Attach a **persistent volume mounted at `/data`**. Without it, data may disappear on redeploy/restart.
3. Set service variables:
   - `CHITS_DB_PATH=/data/chits.sqlite`
   - `CHITS_COOKIE_SAMESITE=None`
   - `CHITS_ALLOWED_ORIGIN=https://mdfl212.github.io` (or the exact custom frontend origin you actually configure; no path or trailing slash)
4. Enable HTTPS and assign a stable backend domain. The app must not be considered ready until HTTPS and CORS have been verified.
5. Provision the first admin from a one-off container/CLI command after the database volume is attached: `php /var/www/pilot/bin/create-user.php admin-name admin`. Use an interactive secure console and a unique password of at least 14 characters. Do not put credentials in environment variables, workflow files, shell history, or chat.
6. Verify `https://YOUR-BACKEND-HOST/api/csrf` returns JSON over HTTPS. A request from an unapproved Origin must be rejected. Do not use real patient data.

## 2. GitHub Pages frontend

1. In repository **Settings → Pages**, select **GitHub Actions** as the build/deployment source.
2. In **Settings → Secrets and variables → Actions → Variables**, create repository variable `CHITS_API_BASE_URL` with the backend origin, e.g. `https://your-service.example.com` (origin only, no `/api` suffix).
3. Run the **Deploy CHITS Modern frontend to GitHub Pages** workflow manually, or push a change to the configured feature branch. The workflow packages only the pilot frontend, not the legacy mock dashboard, database, or PHP source.
4. If the API variable is empty, the page deliberately shows a configuration error rather than silently sending requests to GitHub Pages.

## 3. Cross-origin session caveat

The API uses an explicit single-origin allowlist, credentialed CORS, CSRF tokens, and secure session cookies. GitHub's default `github.io` domain and a typical `railway.app` domain are **cross-site**, and browser third-party-cookie protections may block session cookies even when CORS is correct. For a dependable browser session, use a custom domain with the frontend and API on the same registrable domain, such as `app.example.org` and `api.example.org`, and set `CHITS_ALLOWED_ORIGIN=https://app.example.org`. You must own/configure that domain; do not assume the example domains exist.

## 4. Verify before calling it functional

- Pages loads over HTTPS and its generated `config.js` contains only the public API origin.
- `GET /api/csrf` works from the deployed frontend; preflight responses include the exact allowed origin and credentials.
- Login, logout, CSRF rejection, patient/household create/search/detail, household profile save, and consultation history work across refreshes.
- A wrong Origin is rejected; unauthenticated API access returns 401; users cannot perform writes outside their role.
- Confirm records persist after a service restart/redeploy using synthetic test records.
- Test backup restore, database permissions, logs, error handling, rate limits, session expiry, and concurrent access before any pilot outside a sandbox.

## Important limitations

This configuration is a deployment scaffold, not production approval. The existing backend has no independent security assessment, MFA, rate limiting, tamper-evident audit logs, automated encrypted backups, recovery drills, validated DOH/FHSIS/PIDSR reports, or formal privacy/consent and retention workflows. SQLite on a persistent volume is intended for a low-volume staging demonstration only. Do not enter real patient information.
