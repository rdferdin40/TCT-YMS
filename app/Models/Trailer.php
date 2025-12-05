<?php
/**
 * TCT-YMS Trailer Model
 *
 * Core entity for trailer tracking throughout the yard.
 */

namespace App\Models;

use App\Database;

class Trailer extends Model
{
    protected static string $table = 'trailers';

    protected static array $fillable = [
        'trailer_number', 'carrier_id', 'customer_id', 'trailer_type',
        'trailer_length', 'status_id', 'location_type', 'yard_slot_id',
        'dock_door_id', 'seal_number', 'is_loaded', 'load_type',
        'po_numbers', 'reference_numbers', 'temperature_setting',
        'arrival_time', 'departure_time', 'current_status_since',
        'priority', 'notes', 'created_by', 'updated_by'
    ];

    /**
     * Get trailer status
     */
    public function getStatus(): ?TrailerStatus
    {
        if (!$this->status_id) {
            return null;
        }
        return TrailerStatus::find($this->status_id);
    }

    /**
     * Get carrier
     */
    public function getCarrier(): ?Carrier
    {
        if (!$this->carrier_id) {
            return null;
        }
        return Carrier::find($this->carrier_id);
    }

    /**
     * Get customer
     */
    public function getCustomer(): ?Customer
    {
        if (!$this->customer_id) {
            return null;
        }
        return Customer::find($this->customer_id);
    }

    /**
     * Get yard slot
     */
    public function getYardSlot(): ?YardSlot
    {
        if (!$this->yard_slot_id) {
            return null;
        }
        return YardSlot::find($this->yard_slot_id);
    }

    /**
     * Get dock door
     */
    public function getDockDoor(): ?DockDoor
    {
        if (!$this->dock_door_id) {
            return null;
        }
        return DockDoor::find($this->dock_door_id);
    }

    /**
     * Get current location as string
     */
    public function getLocationString(): string
    {
        switch ($this->location_type) {
            case 'yard_slot':
                $slot = $this->getYardSlot();
                return $slot ? $slot->label : 'Unknown Slot';
            case 'dock_door':
                $door = $this->getDockDoor();
                return $door ? 'Door ' . $door->door_number : 'Unknown Door';
            case 'gate':
                return 'Gate';
            case 'external':
                return 'External';
            default:
                return 'Unknown';
        }
    }

    /**
     * Calculate dwell time in hours
     */
    public function getDwellTime(): float
    {
        $start = $this->arrival_time ?? $this->created_at;
        $end = $this->departure_time ?? date('Y-m-d H:i:s');
        return dwellTime($start, $end);
    }

    /**
     * Get dwell time in current status
     */
    public function getStatusDwellTime(): float
    {
        if (!$this->current_status_since) {
            return 0;
        }
        return dwellTime($this->current_status_since);
    }

    /**
     * Get dwell status (normal, warning, critical)
     */
    public function getDwellStatus(): string
    {
        $status = $this->getStatus();
        if (!$status) {
            return 'normal';
        }

        $dwell = $this->getStatusDwellTime();

        if ($status->dwell_critical_hours && $dwell >= $status->dwell_critical_hours) {
            return 'critical';
        }

        if ($status->dwell_warning_hours && $dwell >= $status->dwell_warning_hours) {
            return 'warning';
        }

        return 'normal';
    }

    /**
     * Change status with validation and history logging
     */
    public function changeStatus(int $newStatusId, ?int $userId = null, ?string $notes = null): bool
    {
        $oldStatusId = $this->status_id;

        // Validate transition
        if (!$this->canTransitionTo($newStatusId)) {
            return false;
        }

        $this->status_id = $newStatusId;
        $this->current_status_since = date('Y-m-d H:i:s');
        $this->updated_by = $userId;

        if ($this->save()) {
            // Log history
            TrailerHistory::create([
                'trailer_id' => $this->id,
                'event_type' => 'status_change',
                'from_status_id' => $oldStatusId,
                'to_status_id' => $newStatusId,
                'notes' => $notes,
                'user_id' => $userId,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Check if can transition to status
     */
    public function canTransitionTo(int $toStatusId): bool
    {
        if (!$this->status_id) {
            return true;
        }

        return Database::exists(
            'status_transitions',
            'from_status_id = ? AND to_status_id = ? AND is_active = 1',
            [$this->status_id, $toStatusId]
        );
    }

    /**
     * Get allowed next statuses
     */
    public function getAllowedTransitions(): array
    {
        if (!$this->status_id) {
            return TrailerStatus::all();
        }

        $rows = Database::fetchAll(
            "SELECT ts.* FROM trailer_statuses ts
             INNER JOIN status_transitions st ON ts.id = st.to_status_id
             WHERE st.from_status_id = ? AND st.is_active = 1 AND ts.is_active = 1
             ORDER BY ts.sort_order",
            [$this->status_id]
        );

        return TrailerStatus::hydrate($rows);
    }

    /**
     * Move to yard slot
     */
    public function moveToSlot(int $slotId, ?int $userId = null, ?string $notes = null): bool
    {
        $oldLocation = $this->getLocationString();
        $oldSlotId = $this->yard_slot_id;
        $oldDoorId = $this->dock_door_id;

        // Release current location
        $this->releaseCurrentLocation();

        // Assign to new slot
        $slot = YardSlot::find($slotId);
        if (!$slot || !$slot->is_available) {
            return false;
        }

        $this->location_type = 'yard_slot';
        $this->yard_slot_id = $slotId;
        $this->dock_door_id = null;
        $this->updated_by = $userId;

        if ($this->save()) {
            // Mark slot as occupied
            Database::update('yard_slots', ['is_available' => 0], 'id = ?', [$slotId]);

            // Log history
            TrailerHistory::create([
                'trailer_id' => $this->id,
                'event_type' => 'location_change',
                'from_location' => $oldLocation,
                'to_location' => $slot->label,
                'notes' => $notes,
                'user_id' => $userId,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Move to dock door
     */
    public function moveToDoor(int $doorId, ?int $userId = null, ?string $notes = null): bool
    {
        $oldLocation = $this->getLocationString();

        // Release current location
        $this->releaseCurrentLocation();

        // Assign to door
        $door = DockDoor::find($doorId);
        if (!$door || $door->status !== 'available') {
            return false;
        }

        $this->location_type = 'dock_door';
        $this->yard_slot_id = null;
        $this->dock_door_id = $doorId;
        $this->updated_by = $userId;

        if ($this->save()) {
            // Mark door as occupied
            Database::update('dock_doors', [
                'status' => 'occupied',
                'current_trailer_id' => $this->id
            ], 'id = ?', [$doorId]);

            // Log history
            TrailerHistory::create([
                'trailer_id' => $this->id,
                'event_type' => 'door_assign',
                'from_location' => $oldLocation,
                'to_location' => 'Door ' . $door->door_number,
                'to_door_id' => $doorId,
                'notes' => $notes,
                'user_id' => $userId,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Release from dock door
     */
    public function releaseFromDoor(?int $userId = null, ?string $notes = null): bool
    {
        if ($this->location_type !== 'dock_door' || !$this->dock_door_id) {
            return false;
        }

        $door = $this->getDockDoor();
        $oldDoorId = $this->dock_door_id;

        // Clear door assignment
        Database::update('dock_doors', [
            'status' => 'available',
            'current_trailer_id' => null
        ], 'id = ?', [$this->dock_door_id]);

        $this->dock_door_id = null;
        $this->location_type = 'yard_slot'; // Will need a slot assignment
        $this->updated_by = $userId;

        if ($this->save()) {
            TrailerHistory::create([
                'trailer_id' => $this->id,
                'event_type' => 'door_release',
                'from_door_id' => $oldDoorId,
                'from_location' => $door ? 'Door ' . $door->door_number : null,
                'notes' => $notes,
                'user_id' => $userId,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Release current location (slot or door)
     */
    private function releaseCurrentLocation(): void
    {
        if ($this->location_type === 'yard_slot' && $this->yard_slot_id) {
            Database::update('yard_slots', ['is_available' => 1], 'id = ?', [$this->yard_slot_id]);
        } elseif ($this->location_type === 'dock_door' && $this->dock_door_id) {
            Database::update('dock_doors', [
                'status' => 'available',
                'current_trailer_id' => null
            ], 'id = ?', [$this->dock_door_id]);
        }
    }

    /**
     * Mark as departed
     */
    public function depart(?int $userId = null, ?string $notes = null): bool
    {
        $this->releaseCurrentLocation();

        $departedStatus = TrailerStatus::first(['name' => 'departed']);
        if (!$departedStatus) {
            return false;
        }

        $this->departure_time = date('Y-m-d H:i:s');
        $this->status_id = $departedStatus->id;
        $this->location_type = 'external';
        $this->yard_slot_id = null;
        $this->dock_door_id = null;
        $this->updated_by = $userId;

        if ($this->save()) {
            TrailerHistory::create([
                'trailer_id' => $this->id,
                'event_type' => 'gate_out',
                'to_status_id' => $departedStatus->id,
                'notes' => $notes,
                'user_id' => $userId,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Get history
     */
    public function getHistory(): array
    {
        return TrailerHistory::hydrate(
            TrailerHistory::query()
                ->where('trailer_id', $this->id)
                ->orderBy('created_at', 'DESC')
                ->get()
        );
    }

    /**
     * Get pending move tasks
     */
    public function getPendingTasks(): array
    {
        return MoveTask::hydrate(
            MoveTask::query()
                ->where('trailer_id', $this->id)
                ->whereIn('status', ['pending', 'assigned', 'in_progress'])
                ->orderBy('created_at', 'DESC')
                ->get()
        );
    }

    /**
     * Get trailers in yard (not departed)
     */
    public static function getInYard(): array
    {
        $departedStatus = TrailerStatus::first(['name' => 'departed']);
        $query = self::query()
            ->whereNull('deleted_at')
            ->whereNull('departure_time');

        if ($departedStatus) {
            $query->where('status_id', '!=', $departedStatus->id);
        }

        return self::hydrate($query->orderBy('arrival_time', 'DESC')->get());
    }

    /**
     * Get trailers by status
     */
    public static function getByStatus(int $statusId): array
    {
        return self::hydrate(
            self::query()
                ->where('status_id', $statusId)
                ->whereNull('deleted_at')
                ->whereNull('departure_time')
                ->orderBy('arrival_time', 'DESC')
                ->get()
        );
    }

    /**
     * Get trailers at doors
     */
    public static function getAtDoors(): array
    {
        return self::hydrate(
            self::query()
                ->where('location_type', 'dock_door')
                ->whereNotNull('dock_door_id')
                ->whereNull('deleted_at')
                ->orderBy('arrival_time', 'DESC')
                ->get()
        );
    }

    /**
     * Get trailers with dwell warnings
     */
    public static function getWithDwellWarnings(): array
    {
        $trailers = self::getInYard();
        return array_filter($trailers, fn($t) => $t->getDwellStatus() !== 'normal');
    }

    /**
     * Search trailers
     */
    public static function search(string $query, array $filters = []): array
    {
        $builder = self::query()->whereNull('deleted_at');

        if ($query) {
            $builder->whereRaw(
                "(trailer_number LIKE ? OR seal_number LIKE ? OR po_numbers LIKE ?)",
                ["%{$query}%", "%{$query}%", "%{$query}%"]
            );
        }

        if (!empty($filters['status_id'])) {
            $builder->where('status_id', $filters['status_id']);
        }

        if (!empty($filters['carrier_id'])) {
            $builder->where('carrier_id', $filters['carrier_id']);
        }

        if (!empty($filters['location_type'])) {
            $builder->where('location_type', $filters['location_type']);
        }

        if (!empty($filters['in_yard'])) {
            $builder->whereNull('departure_time');
        }

        $builder->orderBy('arrival_time', 'DESC');

        return self::hydrate($builder->get());
    }

    /**
     * Get yard statistics
     */
    public static function getYardStats(): array
    {
        $inYard = self::getInYard();
        $atDoors = array_filter($inYard, fn($t) => $t->location_type === 'dock_door');
        $inSlots = array_filter($inYard, fn($t) => $t->location_type === 'yard_slot');
        $withWarnings = array_filter($inYard, fn($t) => $t->getDwellStatus() === 'warning');
        $withCritical = array_filter($inYard, fn($t) => $t->getDwellStatus() === 'critical');

        // Today's arrivals and departures
        $today = date('Y-m-d');
        $arrivals = Database::fetchColumn(
            "SELECT COUNT(*) FROM trailers WHERE DATE(arrival_time) = ?",
            [$today]
        );
        $departures = Database::fetchColumn(
            "SELECT COUNT(*) FROM trailers WHERE DATE(departure_time) = ?",
            [$today]
        );

        // Average dwell
        $avgDwell = 0;
        if (count($inYard) > 0) {
            $totalDwell = array_sum(array_map(fn($t) => $t->getDwellTime(), $inYard));
            $avgDwell = round($totalDwell / count($inYard), 1);
        }

        return [
            'total_in_yard' => count($inYard),
            'at_doors' => count($atDoors),
            'in_slots' => count($inSlots),
            'arrivals_today' => $arrivals,
            'departures_today' => $departures,
            'dwell_warnings' => count($withWarnings),
            'dwell_critical' => count($withCritical),
            'avg_dwell_hours' => $avgDwell,
        ];
    }
}
