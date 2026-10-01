<?php
/**
 * Router.php
 * Matches the browser address to a controller method listed in index.php.
 * Example: GET /login → AuthController::showLogin()
 */

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, string $handler): void
    {
        $this->map('GET', $path, $handler);
    }

    public function post(string $path, string $handler): void
    {
        $this->map('POST', $path, $handler);
    }

    private function map(string $method, string $path, string $handler): void
    {
        $this->routes[] = compact('method', 'path', 'handler');
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptDir !== '/' && $scriptDir !== '.' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir)) ?: '/';
        }
        $uri = '/' . trim($uri, '/');
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';
            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                if ($method === 'POST') {
                    Csrf::verify();
                }
                [$class, $action] = explode('@', $route['handler']);
                $fqcn = 'App\\Controllers\\' . $class;
                $controller = new $fqcn();
                $controller->$action($params);
                return;
            }
        }

        http_response_code(404);
        echo 'Page not found.';
    }
}
