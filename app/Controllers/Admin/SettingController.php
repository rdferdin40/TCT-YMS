<?php
/**
 * TCT-YMS Admin Settings Controller
 */

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Setting;
use App\Database;

class SettingController extends Controller
{
    /**
     * Show settings page
     */
    public function index(): string
    {
        $this->requireRole(['admin']);

        $groupedSettings = Setting::getGrouped();

        return $this->view('admin.settings.index', [
            'title' => 'System Settings',
            'groupedSettings' => $groupedSettings,
        ]);
    }

    /**
     * Update settings
     */
    public function update(): never
    {
        $this->requireRole(['admin']);

        $settings = input('settings', []);

        foreach ($settings as $key => $value) {
            $setting = Setting::first(['key' => $key]);
            if ($setting) {
                $setting->setTypedValue($value);
                $setting->save();
            }
        }

        flash('success', 'Settings saved successfully');
        redirect(url('/admin/settings'));
    }

    /**
     * Show feature flags page
     */
    public function features(): string
    {
        $this->requireRole(['admin']);

        $features = Database::fetchAll("SELECT * FROM feature_flags ORDER BY name");

        return $this->view('admin.settings.features', [
            'title' => 'Feature Flags',
            'features' => $features,
        ]);
    }

    /**
     * Update feature flags
     */
    public function updateFeatures(): never
    {
        $this->requireRole(['admin']);

        $features = input('features', []);

        // Get all feature flag names
        $allFeatures = Database::fetchAll("SELECT name FROM feature_flags");

        foreach ($allFeatures as $feature) {
            $isEnabled = isset($features[$feature['name']]) ? 1 : 0;
            Database::update('feature_flags', ['is_enabled' => $isEnabled], 'name = ?', [$feature['name']]);
        }

        flash('success', 'Feature flags updated');
        redirect(url('/admin/settings/features'));
    }
}
