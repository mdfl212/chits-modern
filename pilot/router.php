<?php
declare(strict_types=1);
$path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/';
header('X-Content-Type-Options: nosniff'); header('Referrer-Policy: no-referrer'); header('X-Frame-Options: DENY'); header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
if (str_starts_with($path,'/api/')) { require __DIR__.'/public/api.php'; exit; }
if ($path==='/'||$path==='/index.html') { header('Cache-Control: no-store'); readfile(__DIR__.'/public/index.html'); exit; }
if (in_array($path,['/app.js','/styles.css'],true)) { $f=__DIR__.'/public'.$path; if (!is_file($f)) { http_response_code(404); exit; } header($path==='/app.js'?'Content-Type: application/javascript; charset=utf-8':'Content-Type: text/css; charset=utf-8'); header('Cache-Control: no-store'); readfile($f); exit; }
http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); echo 'Not found';
