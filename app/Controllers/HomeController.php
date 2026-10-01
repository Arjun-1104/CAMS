<?php
/**
 * HomeController.php
 * Opening "/" sends the user to the right dashboard.
 * serverTime() returns the official server clock as JSON.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\View;

final class HomeController
{
    public function index(array $params = []): void
    {
        if (Auth::check()) {
            $role = Auth::role();
            if ($role === 'ADMIN') {
                View::redirect('/admin');
            } elseif ($role === 'TEACHER') {
                View::redirect('/teacher');
            }
            View::redirect('/student');
        }
        View::redirect('/login');
    }

    public function serverTime(array $params = []): void
    {
        Auth::requireRole('ADMIN', 'TEACHER', 'STUDENT');
        $now = App::now();
        View::json([
            'datetime' => $now->format('Y-m-d H:i:s'),
            'time' => $now->format('h:i:s A'),
            'date' => $now->format('D, d M Y'),
            'day' => $now->format('l'),
            'iso' => $now->format(DATE_ATOM),
        ]);
    }
}
