<?php
/**
 * TCT-YMS Yard Slot Model
 */

namespace App\Models;

class YardSlot extends Model
{
    protected static string $table = 'yard_slots';

    protected static array $fillable = [
        'row_id', 'slot_number', 'label', 'slot_type',
        'grid_x', 'grid_y', 'is_available', 'notes'
    ];

    /**
     * Get row
     */
    public function getRow(): ?YardRow
    {
        if (!$this->row_id) {
            return null;
        }
        return YardRow::find($this->row_id);
    }

    /**
     * Get trailer in this slot
     */
    public function getTrailer(): ?Trailer
    {
        $row = Trailer::query()
            ->where('yard_slot_id', $this->id)
            ->whereNull('deleted_at')
            ->whereNull('departure_time')
            ->first();

        return $row ? new Trailer($row) : null;
    }

    /**
     * Check if slot is available
     */
    public function isAvailable(): bool
    {
        return (bool) $this->is_available;
    }

    /**
     * Get slot type display
     */
    public function getSlotTypeDisplay(): string
    {
        return match ($this->slot_type) {
            'standard' => 'Standard',
            'oversized' => 'Oversized',
            'hazmat' => 'Hazmat',
            'reefer' => 'Reefer',
            'reserved' => 'Reserved',
            default => 'Standard',
        };
    }

    /**
     * Get slot type color
     */
    public function getSlotTypeColor(): string
    {
        return match ($this->slot_type) {
            'standard' => '#6B7280',
            'oversized' => '#8B5CF6',
            'hazmat' => '#EF4444',
            'reefer' => '#06B6D4',
            'reserved' => '#F59E0B',
            default => '#6B7280',
        };
    }

    /**
     * Get full location string (Row + Slot)
     */
    public function getFullLocation(): string
    {
        $row = $this->getRow();
        return $row ? "{$row->name} - {$this->label}" : $this->label;
    }

    /**
     * Find by label
     */
    public static function findByLabel(string $label): ?self
    {
        return self::first(['label' => $label]);
    }

    /**
     * Get available slots
     */
    public static function getAvailable(): array
    {
        return self::hydrate(
            self::query()
                ->where('is_available', 1)
                ->orderBy('row_id')
                ->orderBy('slot_number')
                ->get()
        );
    }

    /**
     * Get slots by type
     */
    public static function getByType(string $type): array
    {
        return self::hydrate(
            self::query()
                ->where('slot_type', $type)
                ->orderBy('row_id')
                ->orderBy('slot_number')
                ->get()
        );
    }

    /**
     * Get yard capacity statistics
     */
    public static function getCapacityStats(): array
    {
        $total = self::query()->count();
        $available = self::query()->where('is_available', 1)->count();
        $occupied = $total - $available;

        return [
            'total' => $total,
            'available' => $available,
            'occupied' => $occupied,
            'utilization' => $total > 0 ? round($occupied / $total * 100) : 0,
        ];
    }
}
