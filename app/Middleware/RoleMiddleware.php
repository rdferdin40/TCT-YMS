<?php
/**
 * TCT-YMS Role Middleware
 *
 * Ensures user has specific role(s) before accessing route.
 */

namespace App\Middleware;

class RoleMiddleware
{
    /**
     * Handle the middleware
     *
     * @param string ...$roles Allowed roles (comma-separated in route definition)
     */
    public function handle(string ...$roles): void
    {
        $user = auth();

        if (!$user) {
            if (isAjax()) {
                jsonResponse(['error' => 'Unauthorized'], 401);
            }
            redirect(url('/login'));
        }

        if (!in_array($user['role'], $roles)) {
            if (isAjax()) {
                jsonResponse(['error' => 'Access denied'], 403);
            }
            abort(403, 'You do not have permission to access this resource');
        }
    }
}
