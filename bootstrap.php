<?php
/**
 * TCT-YMS Bootstrap File
 *
 * This file initializes the application and loads all required components.
 */

// Define base path
define('BASE_PATH', __DIR__);

// Require autoloader
require_once BASE_PATH . '/app/Helpers/functions.php';

// Set error reporting based on environment
if (env('APP_DEBUG', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Set timezone
date_default_timezone_set(config('app.timezone', 'America/Chicago'));

// Set default character encoding
mb_internal_encoding('UTF-8');

// Start session with secure settings
$sessionConfig = config('app.session');
session_set_cookie_params([
    'lifetime' => $sessionConfig['lifetime'] * 60,
    'path' => $sessionConfig['path'],
    'domain' => $sessionConfig['domain'],
    'secure' => $sessionConfig['secure'] && isset($_SERVER['HTTPS']),
    'httponly' => $sessionConfig['http_only'],
    'samesite' => $sessionConfig['same_site'],
]);

session_start();

// Regenerate session ID periodically for security
if (!isset($_SESSION['_created'])) {
    $_SESSION['_created'] = time();
} elseif (time() - $_SESSION['_created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['_created'] = time();
}

// Simple class autoloader
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $prefix = 'App\\';
    $baseDir = BASE_PATH . '/app/';

    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Require core framework files
require_once BASE_PATH . '/app/Database.php';
require_once BASE_PATH . '/app/Router.php';

// Load routes
require_once BASE_PATH . '/routes/web.php';

// Custom exception handler
set_exception_handler(function (Throwable $e) {
    logMessage('error', $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);

    if (env('APP_DEBUG', false)) {
        echo '<div style="background:#fee;border:1px solid #f00;padding:1rem;margin:1rem;border-radius:0.5rem;">';
        echo '<h2 style="color:#c00;margin:0 0 1rem 0;">Exception: ' . get_class($e) . '</h2>';
        echo '<p><strong>Message:</strong> ' . e($e->getMessage()) . '</p>';
        echo '<p><strong>File:</strong> ' . e($e->getFile()) . ':' . $e->getLine() . '</p>';
        echo '<pre style="background:#f5f5f5;padding:1rem;overflow:auto;">' . e($e->getTraceAsString()) . '</pre>';
        echo '</div>';
    } else {
        abort(500, 'An unexpected error occurred');
    }
});

// Custom error handler
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
