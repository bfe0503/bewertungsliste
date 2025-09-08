<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

/**
 * Stub home controller.
 */
final class HomeController
{
    public function index(): void
    {
        View::render('home/index', ['title' => 'Bewertung – Home']);
    }
}
