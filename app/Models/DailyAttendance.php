<?php

namespace App\Models;

use App\Support\ConditionPresentation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'attendance_date', 'check_in_time', 'check_out_time',
        'check_in_lat', 'check_in_lng', 'condition', 'condition_note', 'region_id',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in_time' => 'datetime',
            'check_out_time' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function conditionPillClass(): string
    {
        return ConditionPresentation::pillClass($this->condition);
    }

    public function markerColor(): string
    {
        return ConditionPresentation::markerColor($this->condition);
    }
}
