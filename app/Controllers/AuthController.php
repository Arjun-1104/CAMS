<?php
/**
 * AuthController.php
 * Login form + checking password + logout.
 * After a successful login the user is sent to /admin, /teacher or /student.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\View;

final class AuthController
{
    public function showLogin(array $params = []): void
    {
        if (Auth::check()) {
            $this->home();
        }
        View::render('auth/login', [
            'title' => 'Sign in',
            'error' => App::flash('error'),
            'success' => App::flash('success'),
        ], 'layouts/auth');
    }

    public function login(array $params = []): void
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        App::setOld(['username' => $username]);

        if ($username === '' || $password === '') {
            App::flash('error', 'Enter username and password.');
            View::redirect('/login');
        }

        if (!Auth::attempt($username, $password)) {
            if (empty($_SESSION['_flash']['error'])) {
                App::flash('error', 'Invalid credentials or disabled account.');
            }
            View::redirect('/login');
        }

        App::clearOld();
        unset($_SESSION['_intended']);
        $this->home();
    }

    public function logout(array $params = []): void
    {
        Auth::logout();
        View::redirect('/login');
    }

    private function home(): void
    {
        $role = Auth::role();
        if ($role === 'ADMIN') {
            View::redirect('/admin');
        } elseif ($role === 'TEACHER') {
            View::redirect('/teacher');
        }
        View::redirect('/student');
    }
}
