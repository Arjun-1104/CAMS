<?php
/**
 * Audit.php
 * Writes a line to audit_logs (who started a class, from which IP, at what server time).
 */

declare(strict_types=1);

namespace App\Core;

final class Audit
{
    public static function log(?int $userId, string $action, ?string $details = null): void
    {
        Database::query(
            'INSERT INTO audit_logs (user_id, action, details, ip_address, user_agent) VALUES (?,?,?,?,?)',
            [
                $userId,
                $action,
                $details,
                Auth::ip(),
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]
        );
    }
}
