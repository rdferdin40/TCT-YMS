<?php
/**
 * TCT-YMS Admin User Controller
 */

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\User;
use App\Models\Role;

class UserController extends Controller
{
    /**
     * List users
     */
    public function index(): string
    {
        $users = User::hydrate(
            User::query()
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'DESC')
                ->get()
        );

        $roles = Role::all();

        return $this->view('admin.users.index', [
            'title' => 'Manage Users',
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    /**
     * Show create form
     */
    public function create(): string
    {
        $this->requireRole(['admin']);

        $roles = Role::all();

        return $this->view('admin.users.create', [
            'title' => 'Create User',
            'roles' => $roles,
        ]);
    }

    /**
     * Store new user
     */
    public function store(): never
    {
        $this->requireRole(['admin']);

        $data = $this->validate([
            'username' => 'required|max:50|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'role_id' => 'required|integer',
        ]);

        $user = new User($data);
        $user->setPassword($data['password']);
        $user->phone = input('phone');
        $user->is_active = input('is_active', 1);
        $user->save();

        flash('success', "User {$user->username} created successfully");
        redirect(url('/admin/users'));
    }

    /**
     * Show edit form
     */
    public function edit(int $id): string
    {
        $user = User::findOrFail($id);
        $roles = Role::all();

        // Non-admins can only edit non-admin users
        if (!$this->hasRole('admin') && $user->hasRole('admin')) {
            abort(403, 'Cannot edit admin users');
        }

        return $this->view('admin.users.edit', [
            'title' => 'Edit User: ' . $user->username,
            'editUser' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Update user
     */
    public function update(int $id): never
    {
        $user = User::findOrFail($id);

        // Non-admins can only edit non-admin users
        if (!$this->hasRole('admin') && $user->hasRole('admin')) {
            abort(403, 'Cannot edit admin users');
        }

        $data = $this->validate([
            'email' => "required|email|unique:users,email,{$id}",
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
        ]);

        $user->fill($data);
        $user->phone = input('phone');

        // Only admin can change roles
        if ($this->hasRole('admin')) {
            $user->role_id = input('role_id', $user->role_id);
            $user->is_active = input('is_active', 1);
        }

        // Update password if provided
        $password = input('password');
        if ($password) {
            if (strlen($password) < 8) {
                $this->backWithError('Password must be at least 8 characters');
            }
            $user->setPassword($password);
        }

        $user->save();

        flash('success', 'User updated successfully');
        redirect(url('/admin/users'));
    }

    /**
     * Delete user (soft delete)
     */
    public function delete(int $id): never
    {
        $this->requireRole(['admin']);

        $user = User::findOrFail($id);

        // Cannot delete yourself
        if ($user->id === $this->user['id']) {
            if (isAjax()) {
                $this->error('Cannot delete your own account');
            }
            $this->backWithError('Cannot delete your own account');
        }

        $user->softDelete();

        if (isAjax()) {
            $this->success(null, 'User deleted');
        }

        flash('success', 'User deleted');
        redirect(url('/admin/users'));
    }

    /**
     * Toggle user active status
     */
    public function toggleActive(int $id): never
    {
        $this->requireRole(['admin']);

        $user = User::findOrFail($id);

        // Cannot deactivate yourself
        if ($user->id === $this->user['id']) {
            if (isAjax()) {
                $this->error('Cannot deactivate your own account');
            }
            $this->backWithError('Cannot deactivate your own account');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        if (isAjax()) {
            $this->success(['is_active' => $user->is_active], 'User ' . ($user->is_active ? 'activated' : 'deactivated'));
        }

        flash('success', 'User ' . ($user->is_active ? 'activated' : 'deactivated'));
        redirect(url('/admin/users'));
    }
}
