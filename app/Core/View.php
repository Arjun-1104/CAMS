<?php
/**
 * View.php
 * Loads an HTML file from views/ and wraps it in a layout (sidebar or login card).
 * View::e() escapes text so students cannot inject HTML.
 */

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        $viewFile = dirname(__DIR__, 2) . '/views/' . $view . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException('View not found: ' . $view);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        $layoutFile = dirname(__DIR__, 2) . '/views/' . $layout . '.php';
        require $layoutFile;
    }

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . App::url($path));
        exit;
    }
}
