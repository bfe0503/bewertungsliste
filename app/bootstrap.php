<?php
declare(strict_types=1);

/**
 * Minimal bootstrap: autoloader + config init + local error reporting.
 */

// PSR-4 like autoloader for App\ namespace
spl_autoload_register(function (string $class): void {
    $prefix  = 'App\\';
    $baseDir = BASE_PATH . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// Load config and initialize holder
$config = require BASE_PATH . '/config/config.php';
\App\Core\Config::init($config);

// Local dev: verbose error reporting
if (($config['app_env'] ?? 'local') === 'local') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}
