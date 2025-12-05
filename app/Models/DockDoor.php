<?php
/**
 * TCT-YMS Dock Door Model
 */

namespace App\Models;

class DockDoor extends Model
{
    protected static string $table = 'dock_doors';

    protected static array $fillable = [
        'door_number', 'name', 'zone_id', 'door_type', 'grid_x', 'grid_y',
        'has_dock_leveler', 'has_dock_seal', 'max_trailer_height',
        'status', 'current_trailer_id', 'notes', 'is_active'
    ];

    /**
     * Get zone
     */
    public function getZone(): ?YardZone
    {
        if (!$this->zone_id) {
            return null;
        }
        return YardZone::find($this->zone_id);
    }

    /**
     * Get current trailer
     */
    public function getCurrentTrailer(): ?Trailer
    {
        if (!$this->current_trailer_id) {
            return null;
        }
        return Trailer::find($this->current_trailer_id);
    }

    /**
     * Check if door is available
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available' && $this->is_active;
    }

    /**
     * Check if door is occupied
     */
    public function isOccupied(): bool
    {
        return $this->status === 'occupied';
    }

    /**
     * Get display name
     */
    public function getDisplayName(): string
    {
        return $this->name ?: 'Door ' . $this->door_number;
    }

    /**
     * Get status color
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'available' => '#22C55E',
            'occupied' => '#F59E0B',
            'maintenance' => '#EF4444',
            'disabled' => '#6B7280',
            default => '#6B7280',
        };
    }

    /**
     * Get status background color
     */
    public function getStatusBgColor(): string
    {
        return match ($this->status) {
            'available' => '#F0FDF4',
            'occupied' => '#FFFBEB',
            'maintenance' => '#FEF2F2',
            'disabled' => '#F9FAFB',
            default => '#F9FAFB',
        };
    }

    /**
     * Assign trailer to door
     */
    public function assignTrailer(Trailer $trailer, ?int $userId = null): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        return $trailer->moveToDoor($this->id, $userId);
    }

    /**
     * Release trailer from door
     */
    public function releaseTrailer(?int $userId = null): bool
    {
        $trailer = $this->getCurrentTrailer();
        if (!$trailer) {
            return false;
        }

        return $trailer->releaseFromDoor($userId);
    }

    /**
     * Set to maintenance mode
     */
    public function setMaintenance(?string $notes = null): bool
    {
        if ($this->isOccupied()) {
            return false;
        }

        $this->status = 'maintenance';
        $this->notes = $notes;
        return $this->save();
    }

    /**
     * Set to available
     */
    public function setAvailable(): bool
    {
        $this->status = 'available';
        $this->current_trailer_id = null;
        return $this->save();
    }

    /**
     * Get active doors
     */
    public static function getActive(): array
    {
        return self::hydrate(
            self::query()
                ->where('is_active', 1)
                ->orderBy('door_number')
                ->get()
        );
    }

    /**
     * Get available doors
     */
    public static function getAvailable(): array
    {
        return self::hydrate(
            self::query()
                ->where('is_active', 1)
                ->where('status', 'available')
                ->orderBy('door_number')
                ->get()
        );
    }

    /**
     * Get occupied doors
     */
    public static function getOccupied(): array
    {
        return self::hydrate(
            self::query()
                ->where('is_active', 1)
                ->where('status', 'occupied')
                ->orderBy('door_number')
                ->get()
        );
    }

    /**
     * Get door statistics
     */
    public static function getStats(): array
    {
        $doors = self::getActive();
        $available = array_filter($doors, fn($d) => $d->status === 'available');
        $occupied = array_filter($doors, fn($d) => $d->status === 'occupied');
        $maintenance = array_filter($doors, fn($d) => $d->status === 'maintenance');

        return [
            'total' => count($doors),
            'available' => count($available),
            'occupied' => count($occupied),
            'maintenance' => count($maintenance),
            'utilization' => count($doors) > 0 ? round(count($occupied) / count($doors) * 100) : 0,
        ];
    }

    /**
     * Get doors by type
     */
    public static function getByType(string $type): array
    {
        return self::hydrate(
            self::query()
                ->where('is_active', 1)
                ->where('door_type', $type)
                ->orderBy('door_number')
                ->get()
        );
    }

    /**
     * Get door utilization over time (for charts)
     */
    public static function getUtilizationHistory(int $days = 7): array
    {
        $data = \App\Database::fetchAll(
            "SELECT DATE(created_at) as date,
                    AVG(trailers_at_doors / NULLIF(available_doors + trailers_at_doors, 0) * 100) as utilization
             FROM yard_snapshots
             WHERE snapshot_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)
             ORDER BY date",
            [$days]
        );

        return $data;
    }
}
