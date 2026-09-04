<?php

namespace App\Models;

use App\Support\ConditionPresentation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Logbook extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'theme_id', 'program_id', 'activity_type_id', 'location_id',
        'log_date', 'community_count', 'health_status', 'progress_note',
        'personal_info', 'documentation', 'status',
    ];

    protected function casts(): array
    {
        return [
            'log_date' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function healthPillClass(): string
    {
        return ConditionPresentation::pillClass($this->health_status);
    }

    public function statusPillClass(): string
    {
        return match ($this->status) {
            'Draft' => 'pill-info',
            'Reviewed' => 'pill-normal',
            default => 'pill-warn',
        };
    }

    public function markerColor(): string
    {
        return ConditionPresentation::markerColor($this->health_status);
    }
}
