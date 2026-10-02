<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Region extends Model
{
    use HasFactory;

    protected $fillable = ['parent_id', 'level', 'name'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Region::class, 'parent_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * All descendant region ids (including this region's own id), used to scope
     * an overview query to everything under a kormasit/DPL's assigned region.
     *
     * @return array<int>
     */
    public function subtreeIds(): array
    {
        $ids = [$this->id];
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->subtreeIds());
        }

        return $ids;
    }

    /**
     * Semua path wilayah yang sudah ada ("Kabupaten / Kecamatan / ..."), untuk
     * saran isian form. Dihitung dari satu query supaya tidak memanggil
     * fullPath() (query per level) untuk tiap wilayah.
     *
     * @return Collection<int, string>
     */
    public static function paths(): Collection
    {
        $regions = self::all(['id', 'parent_id', 'name'])->keyBy('id');

        return $regions->map(function (self $region) use ($regions) {
            $names = [];
            $current = $region;
            while ($current) {
                array_unshift($names, $current->name);
                $current = $regions->get($current->parent_id);
            }

            return implode(' / ', $names);
        })->sort()->values();
    }

    public function fullPath(): string
    {
        return $this->parent ? $this->parent->fullPath().' / '.$this->name : $this->name;
    }
}
