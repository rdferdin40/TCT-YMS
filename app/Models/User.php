<?php
/**
 * TCT-YMS User Model
 */

namespace App\Models;

use App\Database;

class User extends Model
{
    protected static string $table = 'users';

    protected static array $fillable = [
        'username', 'email', 'password', 'first_name', 'last_name',
        'role_id', 'phone', 'avatar', 'is_active', 'preferences'
    ];

    protected static array $hidden = ['password', 'password_reset_token'];

    /**
     * Get user's full name
     */
    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name) ?: $this->username;
    }

    /**
     * Get user's role
     */
    public function getRole(): ?Role
    {
        if (!$this->role_id) {
            return null;
        }
        return Role::find($this->role_id);
    }

    /**
     * Get role name
     */
    public function getRoleName(): string
    {
        $role = $this->getRole();
        return $role ? $role->display_name : 'Unknown';
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $roleName): bool
    {
        $role = $this->getRole();
        return $role && $role->name === $roleName;
    }

    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole(array $roleNames): bool
    {
        $role = $this->getRole();
        return $role && in_array($role->name, $roleNames);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user has permission
     */
    public function can(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->getRole();
        if (!$role) {
            return false;
        }

        $permissions = json_decode($role->permissions, true) ?? [];

        // Check for wildcard
        if (in_array('*', $permissions)) {
            return true;
        }

        return in_array($permission, $permissions);
    }

    /**
     * Set password with hashing
     */
    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    /**
     * Generate password reset token
     */
    public function generatePasswordResetToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->password_reset_token = hash('sha256', $token);
        $this->password_reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $this->save();
        return $token;
    }

    /**
     * Verify password reset token
     */
    public function verifyPasswordResetToken(string $token): bool
    {
        if (!$this->password_reset_token || !$this->password_reset_expires) {
            return false;
        }

        if (strtotime($this->password_reset_expires) < time()) {
            return false;
        }

        return hash_equals($this->password_reset_token, hash('sha256', $token));
    }

    /**
     * Clear password reset token
     */
    public function clearPasswordResetToken(): void
    {
        $this->password_reset_token = null;
        $this->password_reset_expires = null;
        $this->save();
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(): void
    {
        $this->last_login_at = date('Y-m-d H:i:s');
        $this->save();
    }

    /**
     * Get user preferences
     */
    public function getPreference(string $key, mixed $default = null): mixed
    {
        $preferences = json_decode($this->preferences, true) ?? [];
        return $preferences[$key] ?? $default;
    }

    /**
     * Set user preference
     */
    public function setPreference(string $key, mixed $value): void
    {
        $preferences = json_decode($this->preferences, true) ?? [];
        $preferences[$key] = $value;
        $this->preferences = json_encode($preferences);
        $this->save();
    }

    /**
     * Get session data for authentication
     */
    public function toSessionData(): array
    {
        $role = $this->getRole();
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->getFullName(),
            'role' => $role ? $role->name : null,
            'role_display' => $role ? $role->display_name : null,
            'permissions' => $role ? json_decode($role->permissions, true) : [],
            'avatar' => $this->avatar,
            'preferences' => json_decode($this->preferences, true) ?? [],
        ];
    }

    /**
     * Find by email
     */
    public static function findByEmail(string $email): ?self
    {
        return self::first(['email' => $email]);
    }

    /**
     * Find by username
     */
    public static function findByUsername(string $username): ?self
    {
        return self::first(['username' => $username]);
    }

    /**
     * Find by password reset token
     */
    public static function findByPasswordResetToken(string $token): ?self
    {
        $hashed = hash('sha256', $token);
        $row = self::query()
            ->where('password_reset_token', $hashed)
            ->whereNotNull('password_reset_expires')
            ->whereRaw('password_reset_expires > NOW()')
            ->first();

        return $row ? new self($row) : null;
    }

    /**
     * Get active users
     */
    public static function getActive(): array
    {
        return self::hydrate(
            self::query()->where('is_active', 1)->whereNull('deleted_at')->get()
        );
    }

    /**
     * Get spotters
     */
    public static function getSpotters(): array
    {
        $spotterRole = Role::first(['name' => 'spotter']);
        if (!$spotterRole) {
            return [];
        }

        return self::hydrate(
            self::query()
                ->where('role_id', $spotterRole->id)
                ->where('is_active', 1)
                ->get()
        );
    }
}
