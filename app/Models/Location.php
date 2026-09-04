<?php

namespace App\Models;

use App\Models\Concerns\FindableByName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use FindableByName, HasFactory;

    protected $fillable = ['name', 'latitude', 'longitude'];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function logbooks(): HasMany
    {
        return $this->hasMany(Logbook::class);
    }

    /**
     * Find or create by name, updating coordinates when new ones are given -
     * mirrors the PoC's behaviour of refreshing a location's pin when a
     * student submits a corrected coordinate for an existing place name.
     */
    public static function firstOrCreateWithCoordinates(string $name, ?float $lat, ?float $lng): self
    {
        $location = static::firstOrCreateFromName($name);

        if ($lat !== null && $lng !== null) {
            $location->update(['latitude' => $lat, 'longitude' => $lng]);
        }

        return $location;
    }
}
