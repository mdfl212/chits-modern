# CHITS Modern: patient-to-consultation pilot

This isolated vertical slice is for development and review only. It does not replace the existing CHITS interface and is **not approved for real patient data, clinical decisions, or official RHU reporting**.

## Included
- Session login with CLI-provisioned users and password hashes.
- CSRF protection for state-changing requests, same-origin requests, and no permissive CORS.
- Household / family-folder creation and registry, with patient-to-household linking and member lists.\n- Patient registration and name/ID search.
- Consultation creation with SOAP fields and basic vital input ranges.
- Persistent patient history using SQLite, prepared statements, foreign keys, and audit events.
- Dependency-free UI with HTML escaping and basic security headers.

## Requirements
- PHP 8.2+
- PHP extensions: pdo_sqlite and mbstring
- Writable database directory outside the web root.

## Run locally with synthetic data
From the repository root:
```sh
mkdir -p "$HOME/.local/share/chits-pilot"
chmod 700 "$HOME/.local/share/chits-pilot"
export CHITS_DB_PATH="$HOME/.local/share/chits-pilot/chits.sqlite"
php -S 127.0.0.1:8080 -t pilot/public pilot/router.php
```
In a second terminal, use the same database path and create an account:
```sh
export CHITS_DB_PATH="$HOME/.local/share/chits-pilot/chits.sqlite"
php pilot/bin/create-user.php demo-admin admin
```
Open http://127.0.0.1:8080/ for the original CHITS-inspired dashboard. Choose **Patient Care Pilot** in its sidebar to open the persistent workflow at `/pilot/`, or open http://127.0.0.1:8080/pilot/ directly. The pilot includes a link back to the dashboard. Use fictitious data only. PHP's built-in server is for local development and must not be exposed to a network.

## API
- GET /api/csrf
- POST /api/login
- POST /api/logout
- GET /api/me
- GET /api/patients?q=...
- POST /api/patients
- GET /api/patients/{uuid}
- POST /api/patients/{uuid}/consultations

There is no public user-registration endpoint. State-changing requests require the CSRF token returned by /api/csrf or login.

## Important limitations
This is a development scaffold, not a finished EHR. Missing: household editing and patient reassignment, rate limiting, MFA, production-grade/tamper-evident audit logging, formal migration versioning, encryption at rest, encrypted automated backups, recovery drills, duplicate patient matching/merge, amendment/signature workflows, privacy/consent workflows, validated FHSIS/PIDSR reports, interoperability, and independent security testing. SQLite is used to keep the first slice small; multi-user RHU deployment needs architecture review and likely a server database.

Do not infer clinical safety from input validation or a successful save. Clinical protocols, privacy policy, retention, access controls, and report definitions need review by responsible RHU/LGU, clinical, and privacy leads before use.

## Manual smoke test
1. Without a session, GET /api/patients should return 401.
2. Sign in with a CLI-provisioned account.
3. Register a fictitious patient.
4. Open the record and save a fictitious consultation.
5. Reload, sign in again, and verify patient and encounter persist.
6. Submit invalid dates, missing required fields, and out-of-range vitals; confirm errors and no false success.
7. Sign out and confirm protected endpoints return 401.

Automated tests and CI are the next step before this should be considered a pilot candidate.
