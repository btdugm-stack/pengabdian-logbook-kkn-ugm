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
        $name = trim((string) preg_replace('/\s+/u', ' ', strip_tags($name)));

        // LIKE tanpa wildcard = cocok persis tapi mengabaikan huruf besar/kecil di
        // MySQL maupun SQLite, jadi "balai desa" tidak menduplikasi "Balai Desa".
        return static::where('name', 'like', addcslashes($name, '%_\\'))->first()
            ?? static::create(['name' => $name]);
    }
}
