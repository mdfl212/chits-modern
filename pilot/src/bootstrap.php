<?php
declare(strict_types=1);
function json_response(array $data, int $status = 200): never { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); exit; }
function db(): PDO {
 static $pdo = null; if ($pdo instanceof PDO) return $pdo;
 $path = getenv('CHITS_DB_PATH');
 if (!$path || $path[0] !== '/') json_response(['error'=>'Server database is not configured. Set CHITS_DB_PATH to an absolute path outside the web root.'],503);
 $publicRoot = realpath(dirname(__DIR__).'/public'); $dbParent = realpath(dirname($path));
 if ($publicRoot && $dbParent && str_starts_with($dbParent.DIRECTORY_SEPARATOR,$publicRoot.DIRECTORY_SEPARATOR)) json_response(['error'=>'Database path must be outside the public web root.'],503);
 if (!$dbParent || !is_writable($dbParent)) json_response(['error'=>'Database directory must exist and be writable by the application user.'],503);
 try { $pdo = new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]); $pdo->exec('PRAGMA foreign_keys = ON'); $pdo->exec('PRAGMA busy_timeout = 5000'); $pdo->exec('PRAGMA journal_mode = WAL'); migrate($pdo); return $pdo; }
 catch (Throwable $e) { error_log('CHITS DB initialization failed: '.$e->getMessage()); json_response(['error'=>'Database initialization failed. Check server configuration.'],503); }
}
function migrate(PDO $pdo): void {
 $pdo->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, role TEXT NOT NULL CHECK(role IN ('admin','provider','encoder','viewer')), active INTEGER NOT NULL DEFAULT 1 CHECK(active IN (0,1)), created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
 $pdo->exec("CREATE TABLE IF NOT EXISTS patients (id TEXT PRIMARY KEY, first_name TEXT NOT NULL, last_name TEXT NOT NULL, date_of_birth TEXT NOT NULL, sex TEXT NOT NULL CHECK(sex IN ('female','male','intersex','unknown')), phone TEXT, barangay TEXT NOT NULL, created_by INTEGER NOT NULL REFERENCES users(id), created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
 $pdo->exec('CREATE INDEX IF NOT EXISTS idx_patients_name ON patients(last_name,first_name)');
 $pdo->exec("CREATE TABLE IF NOT EXISTS households (id TEXT PRIMARY KEY, household_code TEXT NOT NULL UNIQUE, household_head TEXT NOT NULL, barangay TEXT NOT NULL, address TEXT NOT NULL DEFAULT '', created_by INTEGER NOT NULL REFERENCES users(id), created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
 $columns=$pdo->query('PRAGMA table_info(patients)')->fetchAll();
 $hasHousehold=false; foreach ($columns as $column) { if ($column['name']==='household_id') $hasHousehold=true; }
 if (!$hasHousehold) $pdo->exec('ALTER TABLE patients ADD COLUMN household_id TEXT REFERENCES households(id) ON DELETE SET NULL');
 $pdo->exec('CREATE INDEX IF NOT EXISTS idx_patients_household ON patients(household_id)');
 $pdo->exec("CREATE TABLE IF NOT EXISTS consultations (id TEXT PRIMARY KEY, patient_id TEXT NOT NULL REFERENCES patients(id) ON DELETE RESTRICT, visit_date TEXT NOT NULL, chief_complaint TEXT NOT NULL, subjective TEXT NOT NULL DEFAULT '', objective TEXT NOT NULL DEFAULT '', assessment TEXT NOT NULL DEFAULT '', plan TEXT NOT NULL DEFAULT '', temperature_c REAL, bp_systolic INTEGER, bp_diastolic INTEGER, pulse INTEGER, respiratory_rate INTEGER, spo2 INTEGER, provider_id INTEGER NOT NULL REFERENCES users(id), created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
 $pdo->exec('CREATE INDEX IF NOT EXISTS idx_consults_patient_date ON consultations(patient_id,visit_date DESC)');
 $pdo->exec("CREATE TABLE IF NOT EXISTS household_health_profiles (household_id TEXT PRIMARY KEY REFERENCES households(id) ON DELETE CASCADE, water_source TEXT NOT NULL DEFAULT 'unknown' CHECK(water_source IN ('piped','protected_well','unprotected_well','surface_water','delivered','other','unknown')), sanitation TEXT NOT NULL DEFAULT 'unknown' CHECK(sanitation IN ('flush_septic','flush_sewer','pit_latrine','open_defecation','none','other','unknown')), cooking_fuel TEXT NOT NULL DEFAULT 'unknown' CHECK(cooking_fuel IN ('electricity','lpg','biomass','kerosene','other','unknown')), risk_factors TEXT NOT NULL DEFAULT '[]', contextual_notes TEXT NOT NULL DEFAULT '', updated_by INTEGER NOT NULL REFERENCES users(id), updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
 $pdo->exec("CREATE TABLE IF NOT EXISTS audit_events (id INTEGER PRIMARY KEY AUTOINCREMENT, actor_id INTEGER REFERENCES users(id), action TEXT NOT NULL, entity_type TEXT NOT NULL, entity_id TEXT, occurred_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
}
function current_user(): ?array { if (empty($_SESSION['user_id'])) return null; $s=db()->prepare('SELECT id,username,role FROM users WHERE id=:id AND active=1'); $s->execute([':id'=>(int)$_SESSION['user_id']]); return $s->fetch() ?: null; }
function require_user(): array { $u=current_user(); if (!$u) json_response(['error'=>'Authentication required.'],401); return $u; }
function require_role(array $u,array $roles): void { if (!in_array($u['role'],$roles,true)) json_response(['error'=>'You are not authorized to perform this action.'],403); }
function require_csrf(): void { $a=$_SESSION['csrf_token']??''; $b=$_SERVER['HTTP_X_CSRF_TOKEN']??''; if (!$a||!$b||!hash_equals($a,$b)) json_response(['error'=>'Invalid or missing CSRF token. Refresh and try again.'],419); }
function request_json(): array { $type=strtolower(trim(explode(';',$_SERVER['CONTENT_TYPE']??'')[0])); if ($type!=='application/json') json_response(['error'=>'Content-Type must be application/json.'],415); $raw=file_get_contents('php://input'); if (strlen($raw)>65536) json_response(['error'=>'Request body is too large.'],413); $d=json_decode($raw?:'',true); if (!is_array($d)) json_response(['error'=>'A valid JSON object is required.'],400); return $d; }
function required_string(array $d,string $k,int $max,string $label): string { $v=trim((string)($d[$k]??'')); if ($v===''||mb_strlen($v)>$max) json_response(['error'=>$label.' is required and must be at most '.$max.' characters.'],422); return $v; }
function optional_string(array $d,string $k,int $max,string $label): ?string { $v=trim((string)($d[$k]??'')); if ($v==='') return null; if (mb_strlen($v)>$max) json_response(['error'=>$label.' must be at most '.$max.' characters.'],422); return $v; }
function new_uuid(): string { $b=random_bytes(16); $b[6]=chr((ord($b[6])&15)|64); $b[8]=chr((ord($b[8])&63)|128); $h=bin2hex($b); return substr($h,0,8).'-'.substr($h,8,4).'-'.substr($h,12,4).'-'.substr($h,16,4).'-'.substr($h,20); }
function audit(PDO $p,int $actor,string $action,string $type,?string $id): void { $s=$p->prepare('INSERT INTO audit_events(actor_id,action,entity_type,entity_id) VALUES (:actor,:action,:type,:id)'); $s->execute([':actor'=>$actor,':action'=>$action,':type'=>$type,':id'=>$id]); }
function valid_date(string $v): bool { $d=DateTimeImmutable::createFromFormat('!Y-m-d',$v); return $d!==false&&$d->format('Y-m-d')===$v; }
