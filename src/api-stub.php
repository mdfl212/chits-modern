<?php
/**
 * CHITS 2.0 — API Bootstrap Stub
 * PHP 8.3 + PDO
 * 
 * This is the entry point for the backend API.
 * Replace each route handler with real DB queries.
 * 
 * Usage: php -S localhost:8080 api-stub.php
 *        or drop into Laravel/Slim routes.
 */

declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

// ── Database connection (PDO — replaces mysql_connect) ──────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $pdo = new PDO(
        dsn: 'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost')
            . ';dbname=' . ($_ENV['DB_NAME'] ?? 'chits')
            . ';charset=utf8mb4',
        username: $_ENV['DB_USER'] ?? 'chits',
        password: $_ENV['DB_PASS'] ?? '',
        options: [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    return $pdo;
}

// ── Router ───────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts  = explode('/', trim($path, '/'));
// $parts[0] = 'api', $parts[1] = 'v1', $parts[2] = resource, $parts[3] = id

$resource = $parts[2] ?? '';
$id       = isset($parts[3]) ? (int)$parts[3] : null;
$body     = json_decode(file_get_contents('php://input'), true) ?? [];

// ── Auth middleware (stub — replace with Sanctum or JWT) ─────────────
function authGuard(): array {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    // TODO: validate Bearer token, return user array
    // For now: allow all (dev mode)
    return ['user_id' => 1, 'role' => 'admin'];
}

$user = authGuard();

// ── Routes ───────────────────────────────────────────────────────────
$response = match(true) {

    // Patients
    $resource === 'patients' && $method === 'GET' && !$id => (function() {
        $db = getDB();
        $q  = $_GET['q'] ?? '';
        $stmt = $db->prepare(
            "SELECT p.*, b.name AS barangay_name
             FROM patient p
             LEFT JOIN barangay b ON p.barangay_id = b.barangay_id
             WHERE p.lname LIKE :q OR p.fname LIKE :q OR p.patient_id = :exact
             ORDER BY p.lname, p.fname LIMIT 100"
        );
        $stmt->execute(['q' => "%$q%", 'exact' => $q ?: -1]);
        return ['patients' => $stmt->fetchAll()];
    })(),

    $resource === 'patients' && $method === 'POST' => (function() use ($body) {
        $db = getDB();
        $stmt = $db->prepare(
            "INSERT INTO patient (lname,fname,mname,dob,sex,mothers_name,barangay_id,address,philhealth_id,family_id,date_registered)
             VALUES (:lname,:fname,:mname,:dob,:sex,:mothers_name,:barangay_id,:address,:philhealth_id,:family_id,CURDATE())"
        );
        $stmt->execute($body);
        return ['patient_id' => $db->lastInsertId(), 'status' => 'created'];
    })(),

    // Consultations
    $resource === 'consultations' && $method === 'POST' => (function() use ($body, $user) {
        $db = getDB();
        $body['provider_id'] = $user['user_id'];
        $stmt = $db->prepare(
            "INSERT INTO notes (patient_id,consult_date,visit_type,location,complaint,hx,pe,diagnosis,icd10_code,plan,
             bp,temp,hr,rr,weight,spo2,program_tag,provider_id)
             VALUES (:patient_id,:consult_date,:visit_type,:location,:complaint,:hx,:pe,:diagnosis,:icd10_code,:plan,
             :bp,:temp,:hr,:rr,:weight,:spo2,:program_tag,:provider_id)"
        );
        $stmt->execute($body);
        return ['notes_id' => $db->lastInsertId(), 'status' => 'created'];
    })(),

    // Notifiable diseases
    $resource === 'notifiable' && $method === 'GET' => (function() {
        $db = getDB();
        $stmt = $db->query(
            "SELECT n.*, d.disease_name, b.name AS barangay_name
             FROM notifiable n
             JOIN m_lib_disease d ON n.disease_id = d.disease_id
             JOIN barangay b ON n.barangay_id = b.barangay_id
             ORDER BY n.report_date DESC LIMIT 200"
        );
        return ['cases' => $stmt->fetchAll()];
    })(),

    // Auth
    $resource === 'auth' && ($parts[3] ?? '') === 'login' && $method === 'POST' => (function() use ($body) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM game_user WHERE login = :login AND is_active = 1");
        $stmt->execute(['login' => $body['login'] ?? '']);
        $user = $stmt->fetch();
        if ($user && password_verify($body['password'] ?? '', $user['password'])) {
            // TODO: generate real Sanctum / JWT token
            $token = bin2hex(random_bytes(32));
            return ['token' => $token, 'role' => $user['role'], 'name' => $user['fname'].' '.$user['lname']];
        }
        http_response_code(401);
        return ['error' => 'Invalid credentials'];
    })(),

    default => (function() {
        http_response_code(404);
        return ['error' => 'Route not found'];
    })(),
};

echo json_encode($response, JSON_PRETTY_PRINT);
