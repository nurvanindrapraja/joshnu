<?php

/**
 * Router untuk PHP built-in server (jangan dihapus).
 *
 * Jalankan dari ROOT proyek:
 *   php -S localhost:8000 server.php
 * atau lebih mudah:
 *   php artisan serve
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// Serve static file (css/js/gambar) dari public/ bila ada
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
