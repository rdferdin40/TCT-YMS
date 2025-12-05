<?php
/**
 * TCT-YMS Auth Controller
 */

namespace App\Controllers;

use App\Models\User;
use App\Models\Role;

class AuthController extends Controller
{
    protected string $layout = 'layouts.auth';

    /**
     * Show login form
     */
    public function showLogin(): string
    {
        return $this->view('auth.login', [
            'title' => 'Sign In',
        ]);
    }

    /**
     * Handle login
     */
    public function login(): never
    {
        $data = $this->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // Find user by username or email
        $user = User::findByUsername($data['username']);
        if (!$user) {
            $user = User::findByEmail($data['username']);
        }

        // Verify credentials
        if (!$user || !$user->verifyPassword($data['password'])) {
            $this->backWithError('Invalid username or password', ['username' => $data['username']]);
        }

        // Check if user is active
        if (!$user->is_active) {
            $this->backWithError('Your account has been deactivated. Please contact an administrator.');
        }

        // Update last login
        $user->updateLastLogin();

        // Create session
        $_SESSION['user'] = $user->toSessionData();

        // Log activity
        logMessage('info', 'User logged in', ['user_id' => $user->id, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '']);

        // Redirect to intended URL or dashboard
        $intended = $_SESSION['_intended_url'] ?? url('/dashboard');
        unset($_SESSION['_intended_url']);

        // Redirect spotters to spotter interface
        if ($user->hasRole('spotter')) {
            $intended = url('/spotter');
        }

        flash('success', 'Welcome back, ' . $user->getFullName() . '!');
        redirect($intended);
    }

    /**
     * Handle logout
     */
    public function logout(): never
    {
        if (auth()) {
            logMessage('info', 'User logged out', ['user_id' => auth()['id']]);
        }

        // Clear session
        $_SESSION = [];
        session_destroy();

        // Redirect to login
        redirect(url('/login'));
    }

    /**
     * Show forgot password form
     */
    public function showForgotPassword(): string
    {
        return $this->view('auth.forgot-password', [
            'title' => 'Forgot Password',
        ]);
    }

    /**
     * Send password reset link
     */
    public function sendResetLink(): never
    {
        $data = $this->validate([
            'email' => 'required|email',
        ]);

        $user = User::findByEmail($data['email']);

        if ($user) {
            $token = $user->generatePasswordResetToken();

            // In a real app, send email here
            // For now, log the token (would be emailed in production)
            logMessage('info', 'Password reset requested', [
                'user_id' => $user->id,
                'email' => $user->email,
                'reset_url' => url("/reset-password/{$token}"),
            ]);

            // TODO: Send email with reset link
        }

        // Always show success to prevent email enumeration
        flash('success', 'If an account exists with that email, a password reset link will be sent.');
        redirect(url('/login'));
    }

    /**
     * Show reset password form
     */
    public function showResetPassword(string $token): string
    {
        $user = User::findByPasswordResetToken($token);

        if (!$user) {
            flash('error', 'Invalid or expired password reset link.');
            redirect(url('/login'));
        }

        return $this->view('auth.reset-password', [
            'title' => 'Reset Password',
            'token' => $token,
        ]);
    }

    /**
     * Handle password reset
     */
    public function resetPassword(): never
    {
        $data = $this->validate([
            'token' => 'required',
            'password' => 'required|min:8',
            'password_confirmation' => 'required',
        ]);

        if ($data['password'] !== $data['password_confirmation']) {
            $this->backWithError('Passwords do not match');
        }

        $user = User::findByPasswordResetToken($data['token']);

        if (!$user) {
            flash('error', 'Invalid or expired password reset link.');
            redirect(url('/login'));
        }

        // Update password
        $user->setPassword($data['password']);
        $user->clearPasswordResetToken();

        logMessage('info', 'Password reset completed', ['user_id' => $user->id]);

        flash('success', 'Your password has been reset. Please log in.');
        redirect(url('/login'));
    }
}
