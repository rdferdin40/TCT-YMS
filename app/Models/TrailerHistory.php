<?php
/**
 * TCT-YMS Trailer History Model
 */

namespace App\Models;

class TrailerHistory extends Model
{
    protected static string $table = 'trailer_history';
    protected static bool $timestamps = false;

    protected static array $fillable = [
        'trailer_id', 'event_type', 'from_status_id', 'to_status_id',
        'from_location', 'to_location', 'from_door_id', 'to_door_id',
        'notes', 'user_id'
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
     * Get user who made the change
     */
    public function getUser(): ?User
    {
        if (!$this->user_id) {
            return null;
        }
        return User::find($this->user_id);
    }

    /**
     * Get from status
     */
    public function getFromStatus(): ?TrailerStatus
    {
        if (!$this->from_status_id) {
            return null;
        }
        return TrailerStatus::find($this->from_status_id);
    }

    /**
     * Get to status
     */
    public function getToStatus(): ?TrailerStatus
    {
        if (!$this->to_status_id) {
            return null;
        }
        return TrailerStatus::find($this->to_status_id);
    }

    /**
     * Get event type display
     */
    public function getEventTypeDisplay(): string
    {
        return match ($this->event_type) {
            'status_change' => 'Status Changed',
            'location_change' => 'Moved',
            'door_assign' => 'Assigned to Door',
            'door_release' => 'Released from Door',
            'gate_in' => 'Checked In',
            'gate_out' => 'Checked Out',
            'update' => 'Updated',
            'note' => 'Note Added',
            default => $this->event_type,
        };
    }

    /**
     * Get event description
     */
    public function getDescription(): string
    {
        switch ($this->event_type) {
            case 'status_change':
                $from = $this->getFromStatus();
                $to = $this->getToStatus();
                return sprintf(
                    'Status changed from %s to %s',
                    $from ? $from->display_name : 'N/A',
                    $to ? $to->display_name : 'N/A'
                );

            case 'location_change':
                return sprintf(
                    'Moved from %s to %s',
                    $this->from_location ?? 'N/A',
                    $this->to_location ?? 'N/A'
                );

            case 'door_assign':
                return sprintf('Assigned to %s', $this->to_location ?? 'door');

            case 'door_release':
                return sprintf('Released from %s', $this->from_location ?? 'door');

            case 'gate_in':
                return 'Trailer arrived at facility';

            case 'gate_out':
                return 'Trailer departed from facility';

            default:
                return $this->notes ?? $this->event_type;
        }
    }

    /**
     * Get history for trailer
     */
    public static function getForTrailer(int $trailerId, int $limit = 50): array
    {
        return self::hydrate(
            self::query()
                ->where('trailer_id', $trailerId)
                ->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->get()
        );
    }

    /**
     * Get recent activity
     */
    public static function getRecent(int $limit = 50): array
    {
        return self::hydrate(
            self::query()
                ->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->get()
        );
    }
}
