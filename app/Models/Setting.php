<?php
/**
 * TCT-YMS Setting Model
 */

namespace App\Models;

class Setting extends Model
{
    protected static string $table = 'settings';

    protected static array $fillable = [
        'key', 'value', 'type', 'group', 'label', 'description', 'is_public'
    ];

    /**
     * Get typed value
     */
    public function getTypedValue(): mixed
    {
        return match ($this->type) {
            'integer' => (int) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Set typed value
     */
    public function setTypedValue(mixed $value): void
    {
        $this->value = match ($this->type) {
            'boolean' => $value ? 'true' : 'false',
            'json', 'array' => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Get setting value by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::first(['key' => $key]);

        if (!$setting) {
            return $default;
        }

        return $setting->getTypedValue();
    }

    /**
     * Set setting value by key
     */
    public static function set(string $key, mixed $value): bool
    {
        $setting = self::first(['key' => $key]);

        if ($setting) {
            $setting->setTypedValue($value);
            return $setting->save();
        }

        return false;
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup(string $group): array
    {
        return self::hydrate(
            self::query()
                ->where('group', $group)
                ->orderBy('key')
                ->get()
        );
    }

    /**
     * Get all public settings
     */
    public static function getPublic(): array
    {
        $settings = self::hydrate(
            self::query()
                ->where('is_public', 1)
                ->get()
        );

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->getTypedValue();
        }
        return $result;
    }

    /**
     * Get all settings as key-value array
     */
    public static function getAllAsArray(): array
    {
        $settings = self::all();
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->getTypedValue();
        }
        return $result;
    }

    /**
     * Get grouped settings (for admin panel)
     */
    public static function getGrouped(): array
    {
        $settings = self::all();
        $grouped = [];

        foreach ($settings as $setting) {
            $group = $setting->group ?? 'general';
            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }
            $grouped[$group][] = $setting;
        }

        return $grouped;
    }
}
