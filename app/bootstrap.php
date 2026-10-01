<?php
/**
 * bootstrap.php
 * Runs before every page.
 * - Loads config/config.php (database username, timezone)
 * - Loads helper functions (status badges)
 * - Auto-loads PHP classes from the app/ folder
 * - Starts the login session
 */

declare(strict_types=1);

$config = require dirname(__DIR__) . '/config/config.php';

// India time so class start/end is not based on the computer clock of the teacher
date_default_timezone_set($config['app']['timezone'] ?? 'Asia/Kolkata');

require __DIR__ . '/helpers.php';

// If code says "new Router()", PHP will automatically load app/Core/Router.php
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = __DIR__ . '/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

\App\Core\App::init($config);
