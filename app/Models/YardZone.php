<?php
/**
 * TCT-YMS Yard Zone Model
 */

namespace App\Models;

class YardZone extends Model
{
    protected static string $table = 'yard_zones';

    protected static array $fillable = [
        'name', 'code', 'description', 'color', 'sort_order', 'is_active'
    ];

    /**
     * Get rows in this zone
     */
    public function getRows(): array
    {
        return YardRow::hydrate(
            YardRow::query()
                ->where('zone_id', $this->id)
                ->where('is_active', 1)
                ->orderBy('grid_row')
                ->get()
        );
    }

    /**
     * Get total slot count
     */
    public function getTotalSlots(): int
    {
        return \App\Database::fetchColumn(
            "SELECT COUNT(*) FROM yard_slots ys
             INNER JOIN yard_rows yr ON ys.row_id = yr.id
             WHERE yr.zone_id = ?",
            [$this->id]
        );
    }

    /**
     * Get available slot count
     */
    public function getAvailableSlots(): int
    {
        return \App\Database::fetchColumn(
            "SELECT COUNT(*) FROM yard_slots ys
             INNER JOIN yard_rows yr ON ys.row_id = yr.id
             WHERE yr.zone_id = ? AND ys.is_available = 1",
            [$this->id]
        );
    }

    /**
     * Get active zones
     */
    public static function getActive(): array
    {
        return self::hydrate(
            self::query()
                ->where('is_active', 1)
                ->orderBy('sort_order')
                ->get()
        );
    }

    /**
     * Find by code
     */
    public static function findByCode(string $code): ?self
    {
        return self::first(['code' => $code]);
    }
}
