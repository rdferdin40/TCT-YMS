<?php
/**
 * TCT-YMS Authentication Middleware
 *
 * Ensures user is logged in before accessing protected routes.
 */

namespace App\Middleware;

class AuthMiddleware
{
    /**
     * Handle the middleware
     */
    public function handle(): void
    {
        if (!auth()) {
            if (isAjax()) {
                jsonResponse(['error' => 'Unauthorized'], 401);
            }

            // Store intended URL for redirect after login
            $_SESSION['_intended_url'] = $_SERVER['REQUEST_URI'];

            flash('error', 'Please log in to continue');
            redirect(url('/login'));
        }
    }
}
