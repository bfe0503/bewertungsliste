<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Very small view renderer using PHP templates.
 */
final class View
{
    public static function render(string $__view, array $data = []): void
    {
        $viewPath = BASE_PATH . '/app/Views/' . $__view . '.php';
        if (!is_file($viewPath)) {
            http_response_code(500);
            echo 'View not found: ' . htmlspecialchars($__view, ENT_QUOTES, 'UTF-8');
            return;
        }
        // Make variables available to views/layout
        extract($data, EXTR_SKIP);
        $title = $data['title'] ?? 'Bewertung';
        require BASE_PATH . '/app/Views/layout.php';
    }

    public static function include(string $view, array $data = []): void
    {
        $viewPath = BASE_PATH . '/app/Views/' . $view . '.php';
        if (is_file($viewPath)) {
            extract($data, EXTR_SKIP);
            require $viewPath;
        }
    }
}
