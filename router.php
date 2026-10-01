<?php
/**
 * router.php
 * Used ONLY if you run: php -S localhost:8080 router.php
 * With XAMPP you do not need this file. Apache uses .htaccess + index.php instead.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$file = __DIR__ . $uri;
if ($uri !== '/' && is_file($file)) {
    return false; // let PHP serve CSS/JS files normally
}
require __DIR__ . '/index.php';
