<?php
/**
 * TCT-YMS Profile Controller
 */

namespace App\Controllers;

use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Show profile page
     */
    public function show(): string
    {
        $user = User::findOrFail($this->user['id']);

        return $this->view('profile.show', [
            'title' => 'My Profile',
            'profile' => $user,
        ]);
    }

    /**
     * Update profile
     */
    public function update(): never
    {
        $user = User::findOrFail($this->user['id']);

        $data = $this->validate([
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'email' => "required|email|unique:users,email,{$user->id}",
            'phone' => 'max:20',
        ]);

        $user->fill($data);
        $user->save();

        // Update session
        $_SESSION['user'] = $user->toSessionData();

        flash('success', 'Profile updated successfully');
        redirect(url('/profile'));
    }

    /**
     * Update password
     */
    public function updatePassword(): never
    {
        $user = User::findOrFail($this->user['id']);

        $data = $this->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        // Verify current password
        if (!$user->verifyPassword($data['current_password'])) {
            $this->backWithError('Current password is incorrect');
        }

        $user->setPassword($data['password']);
        $user->save();

        flash('success', 'Password changed successfully');
        redirect(url('/profile'));
    }

    /**
     * Update preferences
     */
    public function updatePreferences(): never
    {
        $user = User::findOrFail($this->user['id']);

        $preferences = [
            'dark_mode' => input('dark_mode', false) ? true : false,
            'compact_view' => input('compact_view', false) ? true : false,
            'auto_refresh' => input('auto_refresh', true) ? true : false,
        ];

        $user->preferences = json_encode($preferences);
        $user->save();

        // Update session
        $_SESSION['user'] = $user->toSessionData();

        if (isAjax()) {
            $this->success($preferences, 'Preferences updated');
        }

        flash('success', 'Preferences saved');
        redirect(url('/profile'));
    }
}
