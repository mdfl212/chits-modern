<?php
declare(strict_types=1);

$path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/';

// Shared local entry point: root demo, /pilot/ persistent workflow, /api/* API.
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

if (str_starts_with($path,'/api/')) {
    header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'; base-uri 'none'");
    require __DIR__.'/public/api.php';
    exit;
}

if ($path==='/' || $path==='/index.html') {
    // Legacy prototype uses inline scripts and CDN assets; keep this route local-demo only.
    header('Cache-Control: no-store');
    header('Content-Type: text/html; charset=utf-8');
    readfile(dirname(__DIR__).'/index.html');
    exit;
}

if (in_array($path,['/pilot','/pilot/','/pilot/index.html'],true)) {
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
    header('Cache-Control: no-store');
    header('Content-Type: text/html; charset=utf-8');
    readfile(__DIR__.'/public/index.html');
    exit;
}

if (in_array($path,['/pilot/app.js','/pilot/styles.css'],true)) {
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
    header('Cache-Control: no-store');
    if ($path==='/pilot/app.js') {
        header('Content-Type: application/javascript; charset=utf-8');
        readfile(__DIR__.'/public/app.js');
    } else {
        header('Content-Type: text/css; charset=utf-8');
        readfile(__DIR__.'/public/styles.css');
    }
    exit;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'Not found';
