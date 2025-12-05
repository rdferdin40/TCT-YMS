<?php
/**
 * TCT-YMS Guest Middleware
 *
 * Ensures user is NOT logged in (for login/register pages).
 */

namespace App\Middleware;

class GuestMiddleware
{
    /**
     * Handle the middleware
     */
    public function handle(): void
    {
        if (auth()) {
            redirect(url('/dashboard'));
        }
    }
}
