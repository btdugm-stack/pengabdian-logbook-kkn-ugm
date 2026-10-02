<?php

namespace App\Models;

use App\Models\Concerns\FindableByName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use FindableByName, HasFactory;

    /** Awalan nama lokasi yang dibuat otomatis dari koordinat (lihat nearOrCreateFromCoordinates). */
    public const AUTO_NAME_PREFIX = 'Koordinat ';

    /** Jarak maksimum (meter) agar koordinat dianggap berada di lokasi yang sudah ada. */
    private const NEARBY_METERS = 50;

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

    /**
     * Lokasi untuk logbook yang hanya membawa koordinat (tombol "Lokasi saat
     * ini" tanpa memilih nama tempat): pakai lokasi terdekat yang sudah ada
     * dalam radius NEARBY_METERS, kalau tidak ada buat lokasi baru yang
     * dinamai dari koordinatnya.
     */
    public static function nearOrCreateFromCoordinates(float $lat, float $lng): self
    {
        // Kotak kasar ~110 m di tiap arah supaya tidak menghitung jarak ke semua lokasi.
        $nearest = static::query()
            ->whereBetween('latitude', [$lat - 0.001, $lat + 0.001])
            ->whereBetween('longitude', [$lng - 0.001, $lng + 0.001])
            ->get()
            ->map(fn (self $location) => [$location, self::metersBetween($lat, $lng, (float) $location->latitude, (float) $location->longitude)])
            ->filter(fn (array $pair) => $pair[1] <= self::NEARBY_METERS)
            ->sortBy(fn (array $pair) => $pair[1])
            ->first();

        if ($nearest) {
            return $nearest[0];
        }

        return static::firstOrCreate(
            ['name' => self::AUTO_NAME_PREFIX.number_format($lat, 5, '.', '').', '.number_format($lng, 5, '.', '')],
            ['latitude' => $lat, 'longitude' => $lng],
        );
    }

    /** Jarak garis lurus antar dua koordinat dalam meter (rumus haversine). */
    private static function metersBetween(float $latA, float $lngA, float $latB, float $lngB): float
    {
        $dLat = deg2rad($latB - $latA);
        $dLng = deg2rad($lngB - $lngA);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($latA)) * cos(deg2rad($latB)) * sin($dLng / 2) ** 2;

        return 6371000 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
