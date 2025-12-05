<?php
/**
 * TCT-YMS Customer Model (for 3PL operations)
 */

namespace App\Models;

class Customer extends Model
{
    protected static string $table = 'customers';

    protected static array $fillable = [
        'name', 'code', 'contact_name', 'contact_phone', 'contact_email',
        'address', 'city', 'state', 'zip', 'country', 'notes', 'is_active'
    ];

    /**
     * Get trailers for this customer
     */
    public function getTrailers(): array
    {
        return Trailer::hydrate(
            Trailer::query()
                ->where('customer_id', $this->id)
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
                ->where('customer_id', $this->id)
                ->whereNull('deleted_at')
                ->whereNull('departure_time')
                ->orderBy('arrival_time', 'DESC')
                ->get()
        );
    }

    /**
     * Get active customers
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
}
