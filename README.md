# CHITS 2.0 — Community Health Information Tracking System

A modern reverse-engineering of the original CHITS system by Herman Tolentino MD,
developed for Philippine Rural Health Units (RHU) and barangay health centers.

---

## What this is

CHITS 2.0 is a single-file, offline-capable HTML5 web app that replaces the original
PHP 4 / mysql_* / JpGraph stack with:

| Original (2004–2015)          | Modern (2.0)                      |
|-------------------------------|-----------------------------------|
| PHP 4 with `mysql_connect()`  | Pure HTML5 + JS (no server needed)|
| JpGraph (server-side PNG)     | Chart.js 4.4 (client-side SVG)    |
| FPDF (server PHP→PDF)         | jsPDF 2.5 (browser PDF)           |
| `eval()` module dispatch      | DOM navigation + event handlers   |
| Inline table layouts          | CSS Grid + Flexbox                |
| Plain-text passwords          | Ready for bcrypt (PHP 8 backend)  |
| GSM modem flat files          | SMS hook (Semaphore API ready)    |
| Apache + MySQL required       | Runs from any folder / USB drive  |

---

## Modules covered

### Clinical
- Dashboard — morbidity trend chart, disease alerts, queue, appointments
- Today's Queue — patient flow tracking
- Patient Records — searchable, filterable
- Patient Record — 7-tab view: Consultations, Vital signs, Maternal, FP, Labs, Notifiable, Appointments
- Consultation entry — full SOAP form, ICD-10, vitals, program tag, SMS appointment reminder
- Family Folders — household registry
- Appointments — scheduler with SMS reminder hooks

### Labs & Pharmacy
- Laboratory — requests, results, flags (CBC, Urinalysis, Sputum, CXR, OGTT…)
- Drug Inventory — 13 categories from original `m_lib_drug_category` seeds, stock/reorder alerts

### Health Programs
- Family Planning — method mix chart, CPR, TCL, dropout tracking
- Maternal Care — prenatal masterlist, risk flagging, visit tracking
- TB / NTP — full register, DOTS phase, sputum grading, cohort outcomes
- Immunization (EPI) — per-antigen tracking (BCG, HepB, DPT, OPV, Measles)
- Dental Services — extractions, fillings, oral exams
- Leprosy — MDT register, disability grading

### Surveillance
- Notifiable Diseases — all 33 DOH-EB reportable conditions with threshold alerts
- Epi Reports — weekly bar chart with threshold overlay, quarterly summary
- Injury Surveillance — road, violence, occupational

### Reports & PDF (real generation via jsPDF)
| Report                        | Original PHP file                    |
|-------------------------------|--------------------------------------|
| Daily Service Report          | `daily_service_report.php`           |
| Morbidity Report (FHSIS M1)  | `morbidity_report.php`               |
| Epi Weekly Monitoring         | `epi_weekly_monitoring_report.php`   |
| FP Monthly                    | `fp_monthly.php`                     |
| FP Target Client List         | `fp_tcl.php`                         |
| Prenatal TCL                  | `mc_prenatal.php`                    |
| TB Register                   | `tb_register.php`                    |
| TB Summary / Cohort           | `tb_summary.php`                     |
| Child Care Dev Under-One      | `ccdev_underone.php`                 |
| Dental Quarterly              | `dental_quarterly.php`               |
| Demographic Profile           | `demographic.php`                    |
| Consultation print            | `notes_print.php`                    |

### Admin
- Users — role-based access (Admin / Physician / Midwife / Nurse / Encoder)
- Health center config — mirrors `config.xml` fields
- Barangay registry — area codes, population
- CSV import — patient batch upload

---

## File structure

```
chits-modern/
├── index.html          ← Full self-contained app (open this)
├── src/
│   ├── chits.css       ← Extracted design system & component styles
│   └── chits.js        ← Extracted application logic & PDF generators
├── docs/
│   ├── ARCHITECTURE.md ← How to wire a real backend (PHP 8 / Laravel)
│   ├── DATABASE.md     ← Schema reference from original SQL
│   └── API.md          ← REST API contract for backend integration
├── assets/             ← (placeholder for logos, fonts if needed offline)
└── README.md           ← This file
```

---

## Running it

**No server required for the prototype:**
```bash
# Option 1: just open in browser
open index.html

# Option 2: local server (avoids any CORS issues with future fetch() calls)
npx serve .
# or
php -S localhost:8080
```

---

## Next steps (backend wiring)

See `docs/ARCHITECTURE.md` for the full PHP 8 + PDO migration plan.

Key: replace the in-memory JS data objects with `fetch('/api/...')` calls to a
Laravel or Slim PHP 8 backend. Every module has a 1:1 mapping to the original
`class.*.php` files.

---

## Credits

- Original CHITS: Herman Tolentino MD, UP Manila
- Modernization: Reverse-engineered for community-based health surveillance research
- License: GPLv2 (same as original CHITS)
