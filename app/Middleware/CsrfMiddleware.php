<?php
/**
 * TCT-YMS CSRF Middleware
 *
 * Validates CSRF tokens for non-GET requests.
 */

namespace App\Middleware;

class CsrfMiddleware
{
    /**
     * Handle the middleware
     */
    public function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];

        // Skip for GET, HEAD, OPTIONS
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            return;
        }

        $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (!verify_csrf($token)) {
            if (isAjax()) {
                jsonResponse(['error' => 'Invalid CSRF token'], 419);
            }

            flash('error', 'Your session has expired. Please try again.');
            back();
        }
    }
}
