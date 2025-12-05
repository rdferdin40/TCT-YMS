<?php
/**
 * TCT-YMS Global Helper Functions
 *
 * These functions are available throughout the application.
 */

/**
 * Get environment variable with optional default
 */
function env(string $key, mixed $default = null): mixed
{
    static $env = null;

    if ($env === null) {
        $envFile = dirname(__DIR__, 2) . '/.env';
        $env = [];

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) {
                    continue;
                }
                if (strpos($line, '=') !== false) {
                    [$name, $value] = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value, " \t\n\r\0\x0B\"'");

                    // Handle variable substitution
                    $value = preg_replace_callback('/\$\{([^}]+)\}/', function ($matches) use ($env) {
                        return $env[$matches[1]] ?? '';
                    }, $value);

                    $env[$name] = $value;
                }
            }
        }
    }

    $value = $env[$key] ?? $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null) {
        return $default;
    }

    // Type casting
    return match (strtolower($value)) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'null', '(null)' => null,
        'empty', '(empty)' => '',
        default => is_numeric($value) ? (strpos($value, '.') !== false ? (float)$value : (int)$value) : $value,
    };
}

/**
 * Get configuration value
 */
function config(string $key, mixed $default = null): mixed
{
    static $config = [];

    $parts = explode('.', $key);
    $file = array_shift($parts);

    if (!isset($config[$file])) {
        $path = dirname(__DIR__, 2) . "/config/{$file}.php";
        if (file_exists($path)) {
            $config[$file] = require $path;
        } else {
            return $default;
        }
    }

    $value = $config[$file];
    foreach ($parts as $part) {
        if (!is_array($value) || !isset($value[$part])) {
            return $default;
        }
        $value = $value[$part];
    }

    return $value;
}

/**
 * Get the application base path
 */
function base_path(string $path = ''): string
{
    $basePath = dirname(__DIR__, 2);
    return $path ? $basePath . '/' . ltrim($path, '/') : $basePath;
}

/**
 * Get the public path
 */
function public_path(string $path = ''): string
{
    return base_path('public' . ($path ? '/' . ltrim($path, '/') : ''));
}

/**
 * Get the storage path
 */
function storage_path(string $path = ''): string
{
    return base_path('storage' . ($path ? '/' . ltrim($path, '/') : ''));
}

/**
 * Generate URL
 */
function url(string $path = ''): string
{
    $baseUrl = rtrim(config('app.url', ''), '/');
    return $path ? $baseUrl . '/' . ltrim($path, '/') : $baseUrl;
}

/**
 * Generate asset URL
 */
function asset(string $path): string
{
    return url($path);
}

/**
 * Redirect to URL
 */
function redirect(string $url, int $status = 302): never
{
    header("Location: {$url}", true, $status);
    exit;
}

/**
 * Redirect back to previous page
 */
function back(): never
{
    $referer = $_SERVER['HTTP_REFERER'] ?? url('/');
    redirect($referer);
}

/**
 * Get old form input value
 */
function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old_input'][$key] ?? $default;
}

/**
 * Flash data to session
 */
function flash(string $key, mixed $value): void
{
    $_SESSION['_flash'][$key] = $value;
}

/**
 * Get flashed data
 */
function getFlash(string $key, mixed $default = null): mixed
{
    $value = $_SESSION['_flash'][$key] ?? $default;
    unset($_SESSION['_flash'][$key]);
    return $value;
}

/**
 * Check if user has flash message
 */
function hasFlash(string $key): bool
{
    return isset($_SESSION['_flash'][$key]);
}

/**
 * Escape HTML entities
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8', false);
}

/**
 * Generate CSRF token
 */
function csrf_token(): string
{
    if (!isset($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Generate CSRF input field
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . csrf_token() . '">';
}

/**
 * Verify CSRF token
 */
function verify_csrf(string $token): bool
{
    return isset($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], $token);
}

/**
 * Check if current user is authenticated
 */
function auth(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Check if user has specific role
 */
function hasRole(string $role): bool
{
    $user = auth();
    return $user && isset($user['role']) && $user['role'] === $role;
}

/**
 * Check if user has any of the specified roles
 */
function hasAnyRole(array $roles): bool
{
    $user = auth();
    return $user && isset($user['role']) && in_array($user['role'], $roles);
}

/**
 * Check if user has permission
 */
function can(string $permission): bool
{
    $user = auth();
    if (!$user) {
        return false;
    }

    // Admin has all permissions
    if ($user['role'] === 'admin') {
        return true;
    }

    return isset($user['permissions']) && in_array($permission, $user['permissions']);
}

/**
 * Format date for display
 */
function formatDate(?string $date, string $format = null): string
{
    if (!$date) {
        return '';
    }
    $format = $format ?? config('app.formats.display_date', 'M d, Y');
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime(?string $datetime, string $format = null): string
{
    if (!$datetime) {
        return '';
    }
    $format = $format ?? config('app.formats.display_datetime', 'M d, Y g:i A');
    return date($format, strtotime($datetime));
}

/**
 * Format time for display
 */
function formatTime(?string $time, string $format = null): string
{
    if (!$time) {
        return '';
    }
    $format = $format ?? config('app.formats.display_time', 'g:i A');
    return date($format, strtotime($time));
}

/**
 * Calculate dwell time in hours
 */
function dwellTime(string $startTime, ?string $endTime = null): float
{
    $start = strtotime($startTime);
    $end = $endTime ? strtotime($endTime) : time();
    return round(($end - $start) / 3600, 1);
}

/**
 * Format dwell time for display
 */
function formatDwellTime(float $hours): string
{
    if ($hours < 1) {
        return round($hours * 60) . 'm';
    } elseif ($hours < 24) {
        return round($hours, 1) . 'h';
    } else {
        $days = floor($hours / 24);
        $remainingHours = round($hours % 24);
        return $days . 'd ' . $remainingHours . 'h';
    }
}

/**
 * Generate a random string
 */
function randomString(int $length = 32): string
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Validate email format
 */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize filename
 */
function sanitizeFilename(string $filename): string
{
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
    return preg_replace('/_+/', '_', $filename);
}

/**
 * Get file extension
 */
function getExtension(string $filename): string
{
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Convert bytes to human readable format
 */
function formatBytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
}

/**
 * JSON response helper
 */
function jsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Log message to file
 */
function logMessage(string $level, string $message, array $context = []): void
{
    $levels = ['debug' => 0, 'info' => 1, 'notice' => 2, 'warning' => 3, 'error' => 4, 'critical' => 5];
    $configLevel = config('app.log.level', 'warning');

    if ($levels[$level] < $levels[$configLevel]) {
        return;
    }

    $logFile = base_path(config('app.log.file', 'storage/logs/app.log'));
    $dir = dirname($logFile);

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $contextStr = $context ? ' ' . json_encode($context) : '';
    $entry = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;

    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

/**
 * Debug dump and die
 */
function dd(...$vars): never
{
    echo '<pre style="background:#1a1a1a;color:#fff;padding:1rem;margin:1rem;border-radius:0.5rem;overflow:auto;">';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    exit;
}

/**
 * Get request method
 */
function requestMethod(): string
{
    return $_SERVER['REQUEST_METHOD'] ?? 'GET';
}

/**
 * Check if request is AJAX
 */
function isAjax(): bool
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get request input
 */
function input(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

/**
 * Get all request input
 */
function allInput(): array
{
    return array_merge($_GET, $_POST);
}

/**
 * Check if input exists
 */
function hasInput(string $key): bool
{
    return isset($_POST[$key]) || isset($_GET[$key]);
}

/**
 * Get current URL path
 */
function currentPath(): string
{
    return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
}

/**
 * Check if current path matches
 */
function isCurrentPath(string $path): bool
{
    return currentPath() === $path;
}

/**
 * Check if current path starts with
 */
function pathStartsWith(string $prefix): bool
{
    return str_starts_with(currentPath(), $prefix);
}

/**
 * Render view
 */
function view(string $view, array $data = []): string
{
    $viewPath = base_path('resources/views/' . str_replace('.', '/', $view) . '.php');

    if (!file_exists($viewPath)) {
        throw new Exception("View not found: {$view}");
    }

    extract($data);

    ob_start();
    require $viewPath;
    return ob_get_clean();
}

/**
 * Include partial view
 */
function partial(string $view, array $data = []): void
{
    echo view($view, $data);
}

/**
 * Abort with HTTP status code
 */
function abort(int $code, string $message = ''): never
{
    http_response_code($code);
    $message = $message ?: match ($code) {
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        500 => 'Internal Server Error',
        default => 'Error',
    };

    if (isAjax()) {
        jsonResponse(['error' => $message], $code);
    }

    echo view('errors.error', ['code' => $code, 'message' => $message]);
    exit;
}
