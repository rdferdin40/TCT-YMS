<?php
/**
 * TCT-YMS Carrier Model
 */

namespace App\Models;

class Carrier extends Model
{
    protected static string $table = 'carriers';

    protected static array $fillable = [
        'name', 'code', 'mc_number', 'dot_number', 'scac_code',
        'contact_name', 'contact_phone', 'contact_email',
        'address', 'city', 'state', 'zip', 'country', 'notes', 'is_active'
    ];

    /**
     * Get full address
     */
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->zip,
            $this->country
        ]);
        return implode(', ', $parts);
    }

    /**
     * Get trailers for this carrier
     */
    public function getTrailers(): array
    {
        return Trailer::hydrate(
            Trailer::query()
                ->where('carrier_id', $this->id)
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'DESC')
                ->get()
        );
    }

    /**
     * Get trailers currently in yard
     */
    public function getTrailersInYard(): array
    {
        return Trailer::hydrate(
            Trailer::query()
                ->where('carrier_id', $this->id)
                ->whereNull('deleted_at')
                ->whereNull('departure_time')
                ->orderBy('arrival_time', 'DESC')
                ->get()
        );
    }

    /**
     * Get trailer count in yard
     */
    public function getTrailerCountInYard(): int
    {
        return Trailer::query()
            ->where('carrier_id', $this->id)
            ->whereNull('deleted_at')
            ->whereNull('departure_time')
            ->count();
    }

    /**
     * Get active carriers
     */
    public static function getActive(): array
    {
        return self::hydrate(
            self::query()
                ->where('is_active', 1)
                ->orderBy('name')
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

    /**
     * Find by SCAC
     */
    public static function findByScac(string $scac): ?self
    {
        return self::first(['scac_code' => $scac]);
    }

    /**
     * Search carriers
     */
    public static function search(string $query): array
    {
        return self::hydrate(
            self::query()
                ->whereRaw(
                    "(name LIKE ? OR code LIKE ? OR scac_code LIKE ? OR mc_number LIKE ?)",
                    ["%{$query}%", "%{$query}%", "%{$query}%", "%{$query}%"]
                )
                ->where('is_active', 1)
                ->orderBy('name')
                ->get()
        );
    }
}
