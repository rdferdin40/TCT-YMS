<?php
/**
 * TCT-YMS Trailer Status Model
 */

namespace App\Models;

class TrailerStatus extends Model
{
    protected static string $table = 'trailer_statuses';

    protected static array $fillable = [
        'name', 'display_name', 'color', 'bg_color', 'icon', 'description',
        'is_yard_status', 'is_door_status', 'is_final_status',
        'dwell_warning_hours', 'dwell_critical_hours', 'sort_order', 'is_active'
    ];

    /**
     * Get CSS classes for status badge
     */
    public function getBadgeClasses(): string
    {
        return "background-color: {$this->bg_color}; color: {$this->color}; border-color: {$this->color};";
    }

    /**
     * Get trailers with this status
     */
    public function getTrailers(): array
    {
        return Trailer::getByStatus($this->id);
    }

    /**
     * Get trailer count
     */
    public function getTrailerCount(): int
    {
        return Trailer::query()
            ->where('status_id', $this->id)
            ->whereNull('deleted_at')
            ->whereNull('departure_time')
            ->count();
    }

    /**
     * Get allowed transitions from this status
     */
    public function getAllowedTransitions(): array
    {
        $rows = \App\Database::fetchAll(
            "SELECT ts.* FROM trailer_statuses ts
             INNER JOIN status_transitions st ON ts.id = st.to_status_id
             WHERE st.from_status_id = ? AND st.is_active = 1 AND ts.is_active = 1
             ORDER BY ts.sort_order",
            [$this->id]
        );

        return self::hydrate($rows);
    }

    /**
     * Find by name
     */
    public static function findByName(string $name): ?self
    {
        return self::first(['name' => $name]);
    }

    /**
     * Get active statuses
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
     * Get yard statuses (for yard map)
     */
    public static function getYardStatuses(): array
    {
        return self::hydrate(
            self::query()
                ->where('is_active', 1)
                ->where('is_yard_status', 1)
                ->orderBy('sort_order')
                ->get()
        );
    }

    /**
     * Get door statuses
     */
    public static function getDoorStatuses(): array
    {
        return self::hydrate(
            self::query()
                ->where('is_active', 1)
                ->where('is_door_status', 1)
                ->orderBy('sort_order')
                ->get()
        );
    }

    /**
     * Get status breakdown for dashboard
     */
    public static function getBreakdown(): array
    {
        $statuses = self::getActive();
        $breakdown = [];

        foreach ($statuses as $status) {
            if (!$status->is_final_status) {
                $breakdown[] = [
                    'id' => $status->id,
                    'name' => $status->name,
                    'display_name' => $status->display_name,
                    'color' => $status->color,
                    'count' => $status->getTrailerCount(),
                ];
            }
        }

        return $breakdown;
    }
}
