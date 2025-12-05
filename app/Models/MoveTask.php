<?php
/**
 * TCT-YMS Move Task Model
 */

namespace App\Models;

class MoveTask extends Model
{
    protected static string $table = 'move_tasks';

    protected static array $fillable = [
        'trailer_id', 'task_type', 'priority', 'status',
        'from_location_type', 'from_slot_id', 'from_door_id', 'from_location_text',
        'to_location_type', 'to_slot_id', 'to_door_id', 'to_location_text',
        'assigned_to', 'assigned_at', 'started_at', 'completed_at',
        'cancelled_at', 'cancelled_reason', 'estimated_minutes', 'actual_minutes',
        'instructions', 'notes', 'created_by'
    ];

    /**
     * Get trailer
     */
    public function getTrailer(): ?Trailer
    {
        if (!$this->trailer_id) {
            return null;
        }
        return Trailer::find($this->trailer_id);
    }

    /**
     * Get assigned user
     */
    public function getAssignedUser(): ?User
    {
        if (!$this->assigned_to) {
            return null;
        }
        return User::find($this->assigned_to);
    }

    /**
     * Get creator
     */
    public function getCreator(): ?User
    {
        if (!$this->created_by) {
            return null;
        }
        return User::find($this->created_by);
    }

    /**
     * Get from location string
     */
    public function getFromLocationString(): string
    {
        if ($this->from_location_text) {
            return $this->from_location_text;
        }

        switch ($this->from_location_type) {
            case 'yard_slot':
                $slot = YardSlot::find($this->from_slot_id);
                return $slot ? $slot->label : 'Unknown Slot';
            case 'dock_door':
                $door = DockDoor::find($this->from_door_id);
                return $door ? 'Door ' . $door->door_number : 'Unknown Door';
            default:
                return $this->from_location_type ?? 'Unknown';
        }
    }

    /**
     * Get to location string
     */
    public function getToLocationString(): string
    {
        if ($this->to_location_text) {
            return $this->to_location_text;
        }

        switch ($this->to_location_type) {
            case 'yard_slot':
                $slot = YardSlot::find($this->to_slot_id);
                return $slot ? $slot->label : 'Unknown Slot';
            case 'dock_door':
                $door = DockDoor::find($this->to_door_id);
                return $door ? 'Door ' . $door->door_number : 'Unknown Door';
            default:
                return $this->to_location_type ?? 'Unknown';
        }
    }

    /**
     * Get status color
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => '#F59E0B',
            'assigned' => '#3B82F6',
            'in_progress' => '#8B5CF6',
            'completed' => '#22C55E',
            'cancelled' => '#EF4444',
            default => '#6B7280',
        };
    }

    /**
     * Get priority color
     */
    public function getPriorityColor(): string
    {
        return match ($this->priority) {
            'low' => '#6B7280',
            'normal' => '#3B82F6',
            'high' => '#F59E0B',
            'urgent' => '#EF4444',
            default => '#3B82F6',
        };
    }

    /**
     * Check if task can be claimed
     */
    public function canBeClaimed(): bool
    {
        return $this->status === 'pending' && !$this->assigned_to;
    }

    /**
     * Check if task can be started
     */
    public function canBeStarted(): bool
    {
        return in_array($this->status, ['pending', 'assigned']);
    }

    /**
     * Check if task can be completed
     */
    public function canBeCompleted(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Assign task to user
     */
    public function assignTo(int $userId): bool
    {
        if (!in_array($this->status, ['pending', 'assigned'])) {
            return false;
        }

        $this->assigned_to = $userId;
        $this->assigned_at = date('Y-m-d H:i:s');
        $this->status = 'assigned';

        return $this->save();
    }

    /**
     * Claim task (spotter claims from pool)
     */
    public function claim(int $userId): bool
    {
        if (!$this->canBeClaimed()) {
            return false;
        }

        return $this->assignTo($userId);
    }

    /**
     * Start task
     */
    public function start(?int $userId = null): bool
    {
        if (!$this->canBeStarted()) {
            return false;
        }

        if ($userId && !$this->assigned_to) {
            $this->assigned_to = $userId;
            $this->assigned_at = date('Y-m-d H:i:s');
        }

        $this->status = 'in_progress';
        $this->started_at = date('Y-m-d H:i:s');

        return $this->save();
    }

    /**
     * Complete task
     */
    public function complete(?string $notes = null): bool
    {
        if (!$this->canBeCompleted()) {
            return false;
        }

        $this->status = 'completed';
        $this->completed_at = date('Y-m-d H:i:s');

        if ($notes) {
            $this->notes = $notes;
        }

        // Calculate actual time
        if ($this->started_at) {
            $start = strtotime($this->started_at);
            $end = time();
            $this->actual_minutes = round(($end - $start) / 60);
        }

        if ($this->save()) {
            // Execute the actual move
            $trailer = $this->getTrailer();
            if ($trailer) {
                if ($this->to_location_type === 'yard_slot' && $this->to_slot_id) {
                    $trailer->moveToSlot($this->to_slot_id, $this->assigned_to, "Move task #{$this->id} completed");
                } elseif ($this->to_location_type === 'dock_door' && $this->to_door_id) {
                    $trailer->moveToDoor($this->to_door_id, $this->assigned_to, "Move task #{$this->id} completed");
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Cancel task
     */
    public function cancel(string $reason = ''): bool
    {
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        $this->status = 'cancelled';
        $this->cancelled_at = date('Y-m-d H:i:s');
        $this->cancelled_reason = $reason;

        return $this->save();
    }

    /**
     * Get pending tasks
     */
    public static function getPending(): array
    {
        return self::hydrate(
            self::query()
                ->where('status', 'pending')
                ->orderBy('priority', 'DESC')
                ->orderBy('created_at')
                ->get()
        );
    }

    /**
     * Get tasks assigned to user
     */
    public static function getForUser(int $userId): array
    {
        return self::hydrate(
            self::query()
                ->where('assigned_to', $userId)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->orderBy('priority', 'DESC')
                ->orderBy('created_at')
                ->get()
        );
    }

    /**
     * Get active tasks (not completed or cancelled)
     */
    public static function getActive(): array
    {
        return self::hydrate(
            self::query()
                ->whereIn('status', ['pending', 'assigned', 'in_progress'])
                ->orderBy('priority', 'DESC')
                ->orderBy('created_at')
                ->get()
        );
    }

    /**
     * Get tasks for spotter queue (pending + assigned to them)
     */
    public static function getSpotterQueue(int $userId): array
    {
        return self::hydrate(
            self::query()
                ->whereRaw('(status = ? OR (status IN (?, ?) AND assigned_to = ?))', ['pending', 'assigned', 'in_progress', $userId])
                ->orderBy('priority', 'DESC')
                ->orderBy('created_at')
                ->get()
        );
    }

    /**
     * Get task statistics
     */
    public static function getStats(): array
    {
        $pending = self::query()->where('status', 'pending')->count();
        $inProgress = self::query()->where('status', 'in_progress')->count();
        $completedToday = self::query()
            ->where('status', 'completed')
            ->whereRaw('DATE(completed_at) = CURDATE()')
            ->count();

        return [
            'pending' => $pending,
            'in_progress' => $inProgress,
            'completed_today' => $completedToday,
            'total_active' => $pending + $inProgress,
        ];
    }

    /**
     * Get spotter productivity (moves per spotter today)
     */
    public static function getSpotterProductivity(): array
    {
        return \App\Database::fetchAll(
            "SELECT u.id, u.first_name, u.last_name, u.username,
                    COUNT(mt.id) as completed_moves,
                    AVG(mt.actual_minutes) as avg_time
             FROM users u
             LEFT JOIN move_tasks mt ON mt.assigned_to = u.id
                AND mt.status = 'completed'
                AND DATE(mt.completed_at) = CURDATE()
             INNER JOIN roles r ON u.role_id = r.id
             WHERE r.name = 'spotter' AND u.is_active = 1
             GROUP BY u.id
             ORDER BY completed_moves DESC"
        );
    }
}
