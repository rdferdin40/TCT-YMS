<?php
/**
 * TCT-YMS Gate Event Model
 */

namespace App\Models;

class GateEvent extends Model
{
    protected static string $table = 'gate_events';
    protected static bool $timestamps = false;

    protected static array $fillable = [
        'trailer_id', 'event_type', 'driver_name', 'driver_license',
        'driver_phone', 'tractor_number', 'carrier_id', 'seal_number',
        'is_loaded', 'load_description', 'photo_path', 'destination',
        'appointment_time', 'gate_lane', 'notes', 'processed_by'
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
     * Get processed by user
     */
    public function getProcessedBy(): ?User
    {
        if (!$this->processed_by) {
            return null;
        }
        return User::find($this->processed_by);
    }

    /**
     * Check if check-in
     */
    public function isCheckIn(): bool
    {
        return $this->event_type === 'check_in';
    }

    /**
     * Check if check-out
     */
    public function isCheckOut(): bool
    {
        return $this->event_type === 'check_out';
    }

    /**
     * Get today's events
     */
    public static function getToday(): array
    {
        return self::hydrate(
            self::query()
                ->whereRaw('DATE(created_at) = CURDATE()')
                ->orderBy('created_at', 'DESC')
                ->get()
        );
    }

    /**
     * Get today's check-ins
     */
    public static function getTodayCheckIns(): array
    {
        return self::hydrate(
            self::query()
                ->where('event_type', 'check_in')
                ->whereRaw('DATE(created_at) = CURDATE()')
                ->orderBy('created_at', 'DESC')
                ->get()
        );
    }

    /**
     * Get today's check-outs
     */
    public static function getTodayCheckOuts(): array
    {
        return self::hydrate(
            self::query()
                ->where('event_type', 'check_out')
                ->whereRaw('DATE(created_at) = CURDATE()')
                ->orderBy('created_at', 'DESC')
                ->get()
        );
    }

    /**
     * Get events for date range
     */
    public static function getForDateRange(string $startDate, string $endDate): array
    {
        return self::hydrate(
            self::query()
                ->whereRaw('DATE(created_at) BETWEEN ? AND ?', [$startDate, $endDate])
                ->orderBy('created_at', 'DESC')
                ->get()
        );
    }

    /**
     * Get gate statistics
     */
    public static function getStats(): array
    {
        $checkInsToday = self::query()
            ->where('event_type', 'check_in')
            ->whereRaw('DATE(created_at) = CURDATE()')
            ->count();

        $checkOutsToday = self::query()
            ->where('event_type', 'check_out')
            ->whereRaw('DATE(created_at) = CURDATE()')
            ->count();

        return [
            'check_ins_today' => $checkInsToday,
            'check_outs_today' => $checkOutsToday,
            'total_today' => $checkInsToday + $checkOutsToday,
        ];
    }
}
