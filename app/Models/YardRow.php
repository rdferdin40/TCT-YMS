<?php
/**
 * TCT-YMS Yard Row Model
 */

namespace App\Models;

class YardRow extends Model
{
    protected static string $table = 'yard_rows';

    protected static array $fillable = [
        'zone_id', 'name', 'code', 'slots_count', 'grid_row', 'grid_col',
        'orientation', 'description', 'is_active'
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
     * Get slots
     */
    public function getSlots(): array
    {
        return YardSlot::hydrate(
            YardSlot::query()
                ->where('row_id', $this->id)
                ->orderBy('slot_number')
                ->get()
        );
    }

    /**
     * Get available slots
     */
    public function getAvailableSlots(): array
    {
        return YardSlot::hydrate(
            YardSlot::query()
                ->where('row_id', $this->id)
                ->where('is_available', 1)
                ->orderBy('slot_number')
                ->get()
        );
    }

    /**
     * Get occupied slots
     */
    public function getOccupiedSlots(): array
    {
        return YardSlot::hydrate(
            YardSlot::query()
                ->where('row_id', $this->id)
                ->where('is_available', 0)
                ->orderBy('slot_number')
                ->get()
        );
    }

    /**
     * Get availability stats
     */
    public function getStats(): array
    {
        $total = $this->slots_count;
        $available = YardSlot::query()
            ->where('row_id', $this->id)
            ->where('is_available', 1)
            ->count();

        return [
            'total' => $total,
            'available' => $available,
            'occupied' => $total - $available,
            'utilization' => $total > 0 ? round(($total - $available) / $total * 100) : 0,
        ];
    }

    /**
     * Generate slots for this row
     */
    public function generateSlots(): void
    {
        // Delete existing slots
        \App\Database::delete('yard_slots', 'row_id = ?', [$this->id]);

        // Create new slots
        for ($i = 1; $i <= $this->slots_count; $i++) {
            YardSlot::create([
                'row_id' => $this->id,
                'slot_number' => $i,
                'label' => $this->code . $i,
                'slot_type' => 'standard',
                'grid_x' => $i - 1,
                'grid_y' => $this->grid_row,
                'is_available' => 1,
            ]);
        }
    }

    /**
     * Get active rows
     */
    public static function getActive(): array
    {
        return self::hydrate(
            self::query()
                ->where('is_active', 1)
                ->orderBy('grid_row')
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
