<?php
/**
 * App.php
 * Small helpers used everywhere: config values, website URL, today's date, flash messages.
 */

declare(strict_types=1);

namespace App\Core;

final class App
{
    private static array $config = [];

    public static function init(array $config): void
    {
        self::$config = $config;
    }

    public static function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return self::$config;
        }
        $parts = explode('.', $key);
        $value = self::$config;
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }

    public static function url(string $path = ''): string
    {
        $base = rtrim((string) self::config('app.base_url', ''), '/');
        $path = '/' . ltrim($path, '/');
        if ($base === '') {
            $script = dirname($_SERVER['SCRIPT_NAME'] ?? '');
            $script = str_replace('\\', '/', $script);
            if ($script === '/' || $script === '\\' || $script === '.') {
                $script = '';
            }
            $base = rtrim($script, '/');
        }
        if ($path === '/') {
            return $base === '' ? '/' : $base . '/';
        }
        return $base . $path;
    }

    public static function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone((string) self::config('app.timezone', 'Asia/Kolkata')));
    }

    public static function today(): string
    {
        return self::now()->format('Y-m-d');
    }

    public static function dayName(?\DateTimeImmutable $dt = null): string
    {
        return ($dt ?? self::now())->format('l');
    }

    public static function flash(string $key, ?string $message = null): ?string
    {
        if ($message !== null) {
            $_SESSION['_flash'][$key] = $message;
            return $message;
        }
        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public static function old(string $key, mixed $default = ''): mixed
    {
        $old = $_SESSION['_old'][$key] ?? $default;
        return $old;
    }

    public static function setOld(array $data): void
    {
        $_SESSION['_old'] = $data;
    }

    public static function clearOld(): void
    {
        unset($_SESSION['_old']);
    }
}
