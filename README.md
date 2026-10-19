# CHITS Modern — Community Health Information Tracking System

A modernization/reverse-engineering project inspired by the original Community Health Information Tracking System (CHITS) by Herman Tolentino MD, for learning, interface development, and community-health workflow prototyping.

> **Safety notice:** This repository is not a production electronic health record. Do not enter real patient information or use it for clinical decisions, official RHU reporting, or live service delivery. Use synthetic data only until formal clinical, privacy, security, and operational reviews have been completed.

## Project status

The repository currently contains two separate experiences. They are not yet integrated with each other.

| Component | Purpose | Status |
|---|---|---|
| Root index.html | Original CHITS-inspired, self-contained UI with dashboard, program modules, sample data, and browser PDF demonstrations | Prototype/demo |
| pilot/ | Separate PHP workflow for login, patient registration/search, SOAP-style consultation entry, and persistent patient history | Development pilot; smoke-tested in GitHub Actions |
| docs/ | Architecture notes, original schema reference, API contract, and setup guide | Planning/reference documents; not all endpoints are implemented |

The pilot smoke test checks PHP/JavaScript syntax and exercises login, CSRF protection, patient creation, consultation creation/history, invalid vital-sign rejection, and logout using fictional data. This is not a security audit, clinical validation, or production-readiness certification.

## Existing prototype

The root index.html is designed to open directly in a browser for UI demonstrations. It includes a CHITS-inspired dashboard, clinical and public-health program screens, charts, and client-side PDF examples.

Some screens, records, reports, and actions are illustrative. A displayed button or generated PDF does not imply that a corresponding backend record was saved or that a report has been validated for official use.

### Run the prototype

Open index.html in a modern browser, or serve the repository root locally:

~~~sh
python3 -m http.server 8000
~~~

Then visit http://127.0.0.1:8000/. This serves the demo UI only; it does not enable the separate PHP pilot's database or authentication.

## Patient-to-consultation pilot

The isolated pilot is under pilot/. It currently supports:
- Session-based login for users provisioned from the command line
- Patient registration and search
- Patient detail and consultation history
- SOAP-style consultation entry and basic vital-sign input validation
- SQLite persistence, prepared statements, foreign keys, and basic audit events
- A manual smoke-test guide and GitHub Actions checks

### Requirements

- PHP 8.2 or newer
- PHP extensions: pdo_sqlite and mbstring
- Python 3 for the end-to-end smoke-test script
- A writable data directory outside the web root

### Install and run locally

See **[Pilot installation and testing guide](docs/INSTALLATION.md)** or [pilot/README.md](pilot/README.md).

Quick start on Linux/macOS, from the repository root:

~~~sh
mkdir -p "$HOME/.local/share/chits-pilot"
chmod 700 "$HOME/.local/share/chits-pilot"
export CHITS_DB_PATH="$HOME/.local/share/chits-pilot/chits.sqlite"
php -S 127.0.0.1:8080 -t pilot/public pilot/router.php
~~~

In a second terminal, set the same database path and provision an account:

~~~sh
export CHITS_DB_PATH="$HOME/.local/share/chits-pilot/chits.sqlite"
php pilot/bin/create-user.php demo-admin admin
~~~

Open http://127.0.0.1:8080. The account creation script prompts for a password. Use fictional data only. The PHP built-in server is for local development and must not be exposed to a network.

## Repository layout

~~~text
.
├── index.html                  # Existing standalone UI prototype
├── src/
│   ├── chits.css               # Extracted UI styles
│   ├── chits.js                # Extracted prototype logic and PDF examples
│   └── api-stub.php            # Earlier, explicitly incomplete API stub
├── pilot/
│   ├── public/                 # Pilot UI and API entry point
│   ├── src/bootstrap.php       # SQLite setup, session helpers, validation
│   ├── bin/create-user.php     # CLI-only account provisioning
│   ├── tests/smoke.py          # End-to-end smoke test
│   ├── router.php              # Local development router and security headers
│   └── README.md               # Pilot-specific instructions and limitations
├── docs/
│   ├── API.md                  # Intended wider API contract; not fully implemented
│   ├── ARCHITECTURE.md         # Proposed backend migration architecture
│   ├── DATABASE.md             # Original schema reference
│   └── INSTALLATION.md         # Setup and testing instructions
└── .github/workflows/
    └── pilot-checks.yml        # Automated syntax and smoke checks
~~~

## Tests

GitHub Actions runs syntax checks and an end-to-end smoke test for the isolated pilot when relevant files change. View [workflow runs](https://github.com/mdfl212/chits-modern/actions/workflows/pilot-checks.yml).

To run the smoke test locally, first configure PHP with pdo_sqlite and mbstring, create a private database directory, provision a test user, start the local server as described in pilot/README.md, then run:

~~~sh
python3 pilot/tests/smoke.py
~~~

The smoke test expects the local server to be running at 127.0.0.1:8080 and a fictional test account named ci-user to exist. Do not reuse production credentials or data.

## Before any real-world deployment

This project is **not ready for production clinical use**. Work still required includes, at minimum:
- Independent security testing, threat modelling, rate limiting, and stronger account protections such as MFA
- Production-grade access control and audit-log protection
- Encryption at rest and in transit, secure backups, and tested recovery procedures
- Formal database migrations, data retention rules, privacy/consent workflows, and incident response
- Duplicate patient matching, record correction/amendment workflows, and concurrency handling
- Clinical validation of forms and vital-sign rules, and validation of official DOH/FHSIS/PIDSR reports
- Accessibility/usability testing with intended users and a supervised RHU pilot
- Deployment architecture review; the pilot's SQLite setup is for initial local development, not a recommendation for multi-user production hosting

A successful automated smoke test only shows that the tested paths worked in that test environment. It does not establish that the system is safe, legally compliant, clinically valid, or production-ready.

## Credits and licensing

- Original CHITS: Herman Tolentino MD, UP Manila
- This repository: modernization and reverse-engineering project for community-health workflow prototyping
- The README of the original project describes GPLv2; verify the provenance and licensing of all source assets before redistribution or deployment.
