<?php

namespace App\Models\Concerns;

trait FindableByName
{
    /**
     * Find a master-data row by name, or create it - mirrors the PoC's
     * get_or_create_master() so a new value typed on the logbook form
     * is saved once and becomes a dropdown option for the next entry.
     */
    public static function firstOrCreateFromName(string $name): self
    {
        // Audit §XSS: nama master-data dirender di berbagai tempat (termasuk popup
        // peta via JS). Buang tag HTML sejak awal supaya payload <img onerror=...>
        // tidak pernah tersimpan sebagai nama.
        $name = trim(strip_tags($name));

        return static::firstOrCreate(['name' => $name]);
    }
}
