<?php
declare(strict_types=1);

/**
 * Front controller for all HTTP requests.
 * PHP 8.2.12
 */

// --- Robust session boot (subfolder-safe) ---
$rootPath   = dirname(__DIR__);
$scriptDir  = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/'); // e.g. /bewertung/public
$cookiePath = $scriptDir !== '' ? $scriptDir . '/' : '/';

// Use a dedicated session cookie + scoped path
session_name('bewertung_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => $cookiePath,
    'domain'   => '',
    'secure'   => false, // set true on HTTPS in production
    'httponly' => true,
    'samesite' => 'Lax',
]);

// Persist sessions in project folder
$savePath = $rootPath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'sessions';
if (!is_dir($savePath)) { @mkdir($savePath, 0777, true); }
ini_set('session.save_path', $savePath);

session_start();

// --- Bootstrap ---
define('BASE_PATH', $rootPath);
require BASE_PATH . '/app/bootstrap.php';

// --- Define routes ---
$router = new \App\Core\Router();

// Home
$router->get('/', static function (array $params = []): void {
    \App\Core\View::render('home/index', ['title' => 'Bewertung – Home']);
});

// Lists (controller)
$router->get('/lists', static function (array $params = []): void {
    (new \App\Controllers\ListController())->index();
});
$router->post('/lists', static function (array $params = []): void {
    (new \App\Controllers\ListController())->create();
});
$router->get('/lists/{id}', static function (array $params): void {
    (new \App\Controllers\ListController())->show($params);
});

// NEW: owner list edit/update/delete
$router->get('/lists/{id}/edit', static function (array $params): void {
    (new \App\Controllers\ListController())->edit($params);
});
$router->post('/lists/{id}/update', static function (array $params): void {
    (new \App\Controllers\ListController())->update($params);
});
$router->post('/lists/{id}/delete', static function (array $params): void {
    (new \App\Controllers\ListController())->delete($params);
});

// Auth (controller)
$router->get('/register', static function (array $params = []): void {
    (new \App\Controllers\AuthController())->showRegister();
});
$router->post('/register', static function (array $params = []): void {
    (new \App\Controllers\AuthController())->register();
});
$router->get('/login', static function (array $params = []): void {
    (new \App\Controllers\AuthController())->showLogin();
});
$router->post('/login', static function (array $params = []): void {
    (new \App\Controllers\AuthController())->login();
});
$router->post('/logout', static function (array $params = []): void {
    (new \App\Controllers\AuthController())->logout();
});

// Items (create + rate via AJAX)
$router->post('/lists/{id}/items', static function (array $params): void {
    (new \App\Controllers\ItemController())->create($params);
});
$router->post('/items/{id}/rate', static function (array $params): void {
    (new \App\Controllers\ItemController())->rate($params);
});

// Admin
$router->get('/admin', static function (array $params = []): void {
    (new \App\Controllers\AdminController())->dashboard();
});
$router->get('/admin/users', static function (array $params = []): void {
    (new \App\Controllers\AdminController())->users();
});
$router->post('/admin/users/{id}/reset-password', static function (array $params): void {
    (new \App\Controllers\AdminController())->resetPassword($params);
});
$router->post('/admin/users/{id}/delete', static function (array $params): void {
    (new \App\Controllers\AdminController())->deleteUser($params);
});
$router->get('/admin/lists', static function (array $params = []): void {
    (new \App\Controllers\AdminController())->lists();
});
$router->post('/admin/lists/{id}/delete', static function (array $params): void {
    (new \App\Controllers\AdminController())->deleteList($params);
});

// --- Normalize path & dispatch ---
$rawPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($rawPath, $scriptDir)) {
    $rawPath = substr($rawPath, strlen($scriptDir));
}
$path = $rawPath === '' ? '/' : $rawPath;
$path = rtrim($path, '/') ?: '/';

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
