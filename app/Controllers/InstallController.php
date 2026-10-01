<?php
/**
 * InstallController.php
 * One-time setup: creates empty MySQL tables and the first HOD account.
 * If you already imported database/schema.sql + seed.sql, you can skip this page.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\View;

final class InstallController
{
    public function show(array $params = []): void
    {
        View::render('install/index', [
            'title' => 'Install CAMS',
            'error' => App::flash('error'),
            'success' => App::flash('success'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
        ], 'layouts/auth');
    }

    public function run(array $params = []): void
    {
        if (!extension_loaded('pdo_mysql')) {
            App::flash('error', 'PHP extension pdo_mysql is missing. Enable it in php.ini (XAMPP: Apache PHP).');
            View::redirect('/install');
        }

        $host = trim((string) $_POST['host']);
        $port = trim((string) $_POST['port']);
        $name = trim((string) $_POST['name']);
        $user = trim((string) $_POST['user']);
        $pass = (string) $_POST['pass'];
        $adminUsername = trim((string) ($_POST['admin_username'] ?? ''));
        $adminName = trim((string) ($_POST['admin_name'] ?? ''));
        $adminEmail = trim((string) ($_POST['admin_email'] ?? ''));
        $adminPassword = (string) ($_POST['admin_password'] ?? '');

        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)
            || $adminUsername === ''
            || $adminName === ''
            || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)
            || strlen($adminPassword) < 12) {
            App::flash('error', 'Enter a valid HOD username, name, email, and password with at least 12 characters.');
            View::redirect('/install');
        }

        try {
            $pdo = new \PDO(
                "mysql:host={$host};port={$port};charset=utf8mb4",
                $user,
                $pass,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            $tableCount = (int) $pdo->query(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = " . $pdo->quote($name)
            )->fetchColumn();
            $userTableCount = (int) $pdo->query(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = " . $pdo->quote($name) . " AND table_name = 'users'"
            )->fetchColumn();
            if ($tableCount > 0 && $userTableCount > 0) {
                $userCount = (int) $pdo->query(
                    'SELECT COUNT(*) FROM `' . str_replace('`', '``', $name) . '`.users'
                )->fetchColumn();
                if ($userCount > 0) {
                    throw new \RuntimeException('This database already contains user accounts. Setup will not overwrite existing data; import the upgrade migration instead.');
                }
            }
            $this->runSqlFile($pdo, dirname(__DIR__, 2) . '/database/schema.sql');
            $this->runSqlFile($pdo, dirname(__DIR__, 2) . '/database/seed.sql');
            $statement = $pdo->prepare(
                'INSERT INTO users (username, password_hash, role, status) VALUES (?, ?, ?, ?)'
            );
            $statement->execute([$adminUsername, password_hash($adminPassword, PASSWORD_DEFAULT), 'ADMIN', 'ACTIVE']);
            $adminId = (int) $pdo->lastInsertId();
            $statement = $pdo->prepare(
                'INSERT INTO admins (user_id, name, email, designation, department) VALUES (?, ?, ?, ?, ?)'
            );
            $statement->execute([$adminId, $adminName, $adminEmail, 'HOD', 'MCA']);
        } catch (\Throwable $e) {
            App::flash('error', 'Install failed: ' . $e->getMessage());
            View::redirect('/install');
        }

        $configPath = dirname(__DIR__, 2) . '/config/config.php';
        $export = var_export([
            'app' => [
                'name' => 'CAMS',
                'title' => 'Smart College Class & Attendance Management System',
                'timezone' => 'Asia/Kolkata',
                'base_url' => '',
            ],
            'db' => [
                'host' => $host,
                'port' => (int) $port,
                'name' => $name,
                'user' => $user,
                'pass' => $pass,
                'charset' => 'utf8mb4',
            ],
            'security' => [
                'max_login_attempts' => 8,
                'lockout_minutes' => 15,
            ],
        ], true);

        file_put_contents($configPath, "<?php\n\nreturn {$export};\n");
        file_put_contents(dirname(__DIR__, 2) . '/config/installed.lock', date('c'));
        App::flash('success', 'Database installed. Sign in with the HOD username and password you created.');
        View::redirect('/login');
    }

    private function runSqlFile(\PDO $pdo, string $path): void
    {
        $sql = (string) file_get_contents($path);
        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }
    }
}
