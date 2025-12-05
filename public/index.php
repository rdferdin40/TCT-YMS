<?php
/**
 * TCT-YMS Application Entry Point
 *
 * All requests are routed through this file.
 */

// Bootstrap the application
require_once dirname(__DIR__) . '/bootstrap.php';

// Dispatch the request to the router
try {
    echo \App\Router::dispatch();
} catch (Exception $e) {
    logMessage('critical', 'Routing error: ' . $e->getMessage());

    if (env('APP_DEBUG', false)) {
        throw $e;
    }

    abort(500, 'Application error');
}
