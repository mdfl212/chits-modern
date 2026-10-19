# Installation and testing guide

This guide covers the integrated local entry point for the CHITS Modern demo and patient-to-consultation pilot. **Use fictional data only.** Neither part is approved for real patient records, clinical decisions, or official RHU reporting.

## 1. Existing UI prototype

Requirements: a modern browser. Python 3 is optional if you prefer serving files locally.

From the repository root:

~~~sh
python3 -m http.server 8000
~~~

Open http://127.0.0.1:8000/. Alternatively, open the root index.html directly. This is demo-only and does not enable the pilot backend.

For the integrated experience, use the PHP router in Section 2. It serves the legacy dashboard at `/` and the persistent patient workflow at `/pilot/`. In the legacy sidebar, **Patients (Demo)** remains illustrative; use **Patient Care Pilot** for the authenticated, persistent workflow.

## 2. Patient-to-consultation pilot

### Requirements

- PHP 8.2+
- PHP extensions pdo_sqlite and mbstring
- Python 3 for the smoke test
- Linux/macOS shell examples below; adapt paths for other systems

Check your PHP extensions:

~~~sh
php -v
php -m
~~~

Confirm that pdo_sqlite and mbstring appear in the module list.

### Configure a private database location

From the repository root, create a data directory outside the public web root:

~~~sh
mkdir -p "$HOME/.local/share/chits-pilot"
chmod 700 "$HOME/.local/share/chits-pilot"
export CHITS_DB_PATH="$HOME/.local/share/chits-pilot/chits.sqlite"
~~~

Keep the same CHITS_DB_PATH in each terminal used by the pilot. The application intentionally refuses to initialize if the path is missing, relative, or located under the pilot's public directory.

### Create a user

In a second terminal, from the repository root:

~~~sh
export CHITS_DB_PATH="$HOME/.local/share/chits-pilot/chits.sqlite"
php pilot/bin/create-user.php demo-admin admin
~~~

The command prompts for a password. Use a unique password of at least 14 characters. Available roles are admin, provider, encoder, and viewer. There is no public account-registration endpoint.

### Start the local server

From the repository root, with the database path configured:

~~~sh
php -S 127.0.0.1:8080 -t . pilot/router.php
~~~

Open http://127.0.0.1:8080/ for the CHITS dashboard, then select **Patient Care Pilot** in the sidebar. You can also open http://127.0.0.1:8080/pilot/ directly. Sign in using the account you just created. Use only fictitious records.

The PHP built-in server is for local development only. It must not be exposed to the internet or used as production hosting.

## 3. Manual functional smoke test

1. Open the pilot while signed out. The patient API should require authentication.
2. Sign in with the CLI-provisioned account.
3. Create a fictional household, then register a fictional patient and optionally link them to that household. Open the household registry to verify the linked member.
4. Open the patient's record and create a fictional consultation.
5. Refresh the page and confirm the patient and consultation remain stored.
6. Try a future date, missing required fields, and out-of-range vital signs; confirm the application rejects invalid input.
7. Sign out and confirm protected routes require authentication again.

## 4. Automated smoke test

The repository's GitHub Actions workflow installs the required PHP extensions and runs syntax checks and the end-to-end smoke test automatically.

To run the Python smoke test locally, first configure a clean test database, provision a test user named ci-user with the password ci-test-password-12345, and start the server at 127.0.0.1:8080. Then run:

~~~sh
python3 pilot/tests/smoke.py
~~~

**Only use that example credential in a disposable test environment. Never use it for a real account.** The smoke test creates a fictitious patient and consultation in the configured database.

## Troubleshooting

- **Missing extension:** enable/install pdo_sqlite and mbstring for the PHP version you are using.
- **Database configuration error:** ensure CHITS_DB_PATH is an absolute path and the parent directory exists and is writable by the current user.
- **Login rejected:** verify you created the account using the same CHITS_DB_PATH used by the server.
- **Port in use:** select another local port and update the smoke-test script's BASE URL before running it.
- **Session or CSRF error:** reload the page to fetch a fresh CSRF token, then sign in again.

## Limitations

The pilot is a development scaffold. It does not yet include household editing/reassignment, rate limiting, MFA, production-grade audit-log protection, encryption at rest, automated encrypted backups, recovery drills, formal migration versioning, duplicate patient merge workflows, clinical signatures/amendments, validated official reporting, or independent security testing.

Do not use real patient data or deploy this for live clinical service until clinical, privacy, security, operational, and legal requirements have been assessed and addressed by the responsible organization.
