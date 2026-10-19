# CHITS 2.0 — Architecture & Backend Migration Guide

## Current state (this prototype)

The prototype is a **self-contained HTML5 SPA** — zero server dependencies.
All data is in-memory JS objects. Great for:
- Offline demos / field presentations
- UI development and UX testing
- Copilot-assisted feature building

## Target architecture (production)

```
Browser (index.html + chits.css + chits.js)
        ↕  fetch('/api/v1/...')  JSON
Laravel 11 / Slim 4 (PHP 8.3)
        ↕  PDO / Eloquent
MySQL 8 or PostgreSQL 15
```

---

## Phase 1 — Replace mysql_* with PDO

Every call in `class.mysqldb.php`:
```php
// OLD (broken on PHP 7+)
$conn = mysql_connect($host, $user, $pass);
mysql_select_db($db);
$rs   = mysql_query("SELECT * FROM patient WHERE id=$id");

// NEW
$pdo  = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
$stmt = $pdo->prepare("SELECT * FROM patient WHERE id = :id");
$stmt->execute(['id' => $id]);
$rs   = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

## Phase 2 — REST API endpoints

Map each original class to an API controller:

| Original class              | API route                          | Methods         |
|-----------------------------|------------------------------------|-----------------|
| `class.patient.php`         | `/api/v1/patients`                 | GET POST PUT    |
| `class.family.php`          | `/api/v1/families`                 | GET POST PUT    |
| `class.notes.php`           | `/api/v1/consultations`            | GET POST        |
| `class.mc.php`              | `/api/v1/maternal`                 | GET POST PUT    |
| `class.fp.php`              | `/api/v1/fp`                       | GET POST PUT    |
| `class.ntp.php`             | `/api/v1/ntp`                      | GET POST PUT    |
| `class.notifiable.php`      | `/api/v1/notifiable`               | GET POST        |
| `class.drug.php`            | `/api/v1/drugs`                    | GET POST PUT    |
| `class.lab.php`             | `/api/v1/lab`                      | GET POST PUT    |
| `class.appointment.php`     | `/api/v1/appointments`             | GET POST PUT    |
| `class.user.php`            | `/api/v1/users`                    | GET POST PUT    |
| `class.barangay.php`        | `/api/v1/barangays`                | GET             |

## Phase 3 — Auth

Replace original PIN + `old_password()` (MySQL 3 hash — broken):
```php
// Registration
$hash = password_hash($pin, PASSWORD_BCRYPT, ['cost' => 12]);

// Login
if (password_verify($input_pin, $stored_hash)) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role']    = $user['role'];
}
```

Use Laravel Sanctum for API token auth (mobile-friendly).

## Phase 4 — SMS (replace GSM modem)

Original: writes text files to `/gsm/out/` — hardware-bound.

Replace with Semaphore API (PH standard):
```php
Http::post('https://api.semaphore.co/api/v4/messages', [
    'apikey'  => env('SEMAPHORE_KEY'),
    'number'  => $patient->cellular,
    'message' => "Appointment reminder: {$appt->date} at RHU. Reply CONFIRM.",
]);
```

## Phase 5 — Cron jobs

Original `cron/` folder maps to Laravel Artisan commands:

| Original cron file              | Laravel command                    |
|---------------------------------|------------------------------------|
| `cronsmsdigest.php`             | `php artisan sms:digest`           |
| `cronappointmentreminder.php`   | `php artisan sms:appointment-reminders` |
| `crondbbackup.php`              | `php artisan db:backup`            |
| `cronsmsqueue.php`              | `php artisan sms:process-queue`    |

## Frontend wiring example

```javascript
// Replace in-memory data with real API calls
async function loadPatients() {
  const res  = await fetch('/api/v1/patients?barangay=3&limit=50');
  const data = await res.json();
  renderPatientTable(data.patients);
}

async function saveConsultation(formData) {
  const res = await fetch('/api/v1/consultations', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
    body: JSON.stringify(formData)
  });
  return res.json();
}
```
