<?php

namespace App\Models;

use App\Models\Concerns\FindableByName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityType extends Model
{
    use FindableByName, HasFactory;

    protected $fillable = ['name'];

    public function logbooks(): HasMany
    {
        return $this->hasMany(Logbook::class);
    }
}
