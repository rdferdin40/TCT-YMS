<?php
/**
 * TCT-YMS Role Model
 */

namespace App\Models;

class Role extends Model
{
    protected static string $table = 'roles';

    protected static array $fillable = [
        'name', 'display_name', 'description', 'permissions', 'is_system'
    ];

    /**
     * Get permissions as array
     */
    public function getPermissions(): array
    {
        return json_decode($this->permissions, true) ?? [];
    }

    /**
     * Set permissions from array
     */
    public function setPermissions(array $permissions): void
    {
        $this->permissions = json_encode($permissions);
    }

    /**
     * Check if role has permission
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->getPermissions();

        if (in_array('*', $permissions)) {
            return true;
        }

        return in_array($permission, $permissions);
    }

    /**
     * Add permission
     */
    public function addPermission(string $permission): void
    {
        $permissions = $this->getPermissions();
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->setPermissions($permissions);
        }
    }

    /**
     * Remove permission
     */
    public function removePermission(string $permission): void
    {
        $permissions = $this->getPermissions();
        $permissions = array_filter($permissions, fn($p) => $p !== $permission);
        $this->setPermissions(array_values($permissions));
    }

    /**
     * Get users with this role
     */
    public function getUsers(): array
    {
        return User::hydrate(
            User::query()->where('role_id', $this->id)->get()
        );
    }

    /**
     * Get user count
     */
    public function getUserCount(): int
    {
        return User::query()->where('role_id', $this->id)->count();
    }

    /**
     * Find by name
     */
    public static function findByName(string $name): ?self
    {
        return self::first(['name' => $name]);
    }

    /**
     * Get all available permissions
     */
    public static function getAllPermissions(): array
    {
        return [
            'dashboard' => [
                'dashboard.view' => 'View Dashboard',
            ],
            'yard_map' => [
                'yard_map.view' => 'View Yard Map',
                'yard_map.edit' => 'Edit Yard Map (drag/drop)',
            ],
            'trailers' => [
                'trailers.view' => 'View Trailers',
                'trailers.create' => 'Create Trailers',
                'trailers.edit' => 'Edit Trailers',
                'trailers.delete' => 'Delete Trailers',
            ],
            'moves' => [
                'moves.view' => 'View Move Tasks',
                'moves.create' => 'Create Move Tasks',
                'moves.assign' => 'Assign Move Tasks',
                'moves.claim' => 'Claim Move Tasks',
                'moves.complete' => 'Complete Move Tasks',
            ],
            'dock_doors' => [
                'dock_doors.view' => 'View Dock Doors',
                'dock_doors.assign' => 'Assign Trailers to Doors',
                'dock_doors.manage' => 'Manage Dock Doors',
            ],
            'gate' => [
                'gate.view' => 'View Gate Activity',
                'gate.checkin' => 'Gate Check-In',
                'gate.checkout' => 'Gate Check-Out',
            ],
            'reports' => [
                'reports.view' => 'View Reports',
                'reports.export' => 'Export Reports',
            ],
            'users' => [
                'users.view' => 'View Users',
                'users.create' => 'Create Users',
                'users.edit' => 'Edit Users',
                'users.delete' => 'Delete Users',
            ],
            'admin' => [
                'admin.settings' => 'Manage Settings',
                'admin.yard_layout' => 'Manage Yard Layout',
                'admin.carriers' => 'Manage Carriers',
                'admin.statuses' => 'Manage Statuses',
            ],
        ];
    }
}
