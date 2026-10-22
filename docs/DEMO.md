# CHITS Modern demos

This repository has two different demo modes. Both are for UI/workflow evaluation only, not a production EHR. Use synthetic/fictitious data only.

## 1. Open the browser mock (GitHub Pages)

Expected URL after the Pages workflow is enabled and the changes are merged to `main`:

https://mdfl212.github.io/chits-modern/

This static site requires no install, account, PHP runtime, or database. It uses the existing dashboard and routes patient/consultation navigation to its illustrative mock screens. Buttons and sample data are for demonstration; changes are not saved to a server.

**GitHub Pages serves static files and cannot execute PHP.** This browser mock does not connect to the PHP API or the SQLite database. Never enter real patient information or use it for clinical decisions or official reporting.

The workflow is `.github/workflows/pages-demo.yml`. Repository Pages settings must use **GitHub Actions** as the build/deployment source. The deployment runs from `main`; pull requests validate the static build but do not publish it.

## 2. Run the PHP-backed local demo

This mode runs the dashboard, PHP API, authentication, and SQLite database on your computer. Docker Compose keeps the PHP runtime consistent and stores demo records in a named local volume. Do not expose this demo to your LAN or the internet.

## Requirements

- Docker Engine or Docker Desktop with the Docker Compose plugin
- Git checkout of this repository

### Start the local demo

From the repository root:

```sh
docker compose up --build -d
```

Open **http://127.0.0.1:8080/** for the CHITS-inspired dashboard. Use **Patient Care Workspace** in the sidebar or go directly to **http://127.0.0.1:8080/pilot/**.

The legacy dashboard remains an illustrative UI. The persistent workflow currently supports login, household/family folders, patient registration/search, and SOAP-style consultation records. Other legacy modules may remain mock-only.

### Create the first local demo account

Run this from the repository root:

```sh
docker compose run --rm chits-demo php pilot/bin/create-user.php demo-admin admin
```

The command prompts for a password. Use a unique password of at least 14 characters. This account is for your local demo only. Do not store credentials in source control.

Then sign in at **http://127.0.0.1:8080/pilot/**.

### Data persistence and reset

SQLite is stored in the named Docker volume `chits-demo-data` mounted at `/data`, outside the web root. Records should remain after container recreation:

```sh
docker compose down
docker compose up -d
```

To deliberately delete all demo records and accounts, remove the volume:

```sh
docker compose down -v
```

**Warning:** `docker compose down -v` permanently deletes the demo database.

### Stop the local demo

```sh
docker compose down
```

No cloud account, public domain, GitHub Pages setup, hosted backend, or remote database is required.

### How this supports future implementation

The UI calls the existing PHP API through same-origin `/api/*` routes. The same PHP handlers, validation, session logic, and database layer are used locally; this avoids building a separate mock API that would need to be replaced later. The current SQLite schema is appropriate for a small local demo, not a production multi-user RHU system. A future deployment will need a separately reviewed hosting architecture, production database strategy, privacy/security controls, backups, and clinical/operational validation.

### Troubleshooting

- **Port 8080 is busy:** change the host-side port in `docker-compose.yml`, for example `127.0.0.1:8090:8080`, then open port 8090.
- **Login fails:** confirm the user was created with `docker compose run --rm chits-demo php pilot/bin/create-user.php ...` and that the demo container uses the same Compose volume.
- **Inspect logs:** `docker compose logs -f chits-demo`.
- **Rebuild after code changes:** `docker compose up --build -d`.

This is a development/demo scaffold only. It is not approved for real patient information, clinical decisions, or official RHU reporting.
