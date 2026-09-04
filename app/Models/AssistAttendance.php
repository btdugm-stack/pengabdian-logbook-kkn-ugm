<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'helper_student_id', 'host_student_id', 'program_id', 'assist_date',
        'hours', 'role_note', 'approval_status',
    ];

    protected function casts(): array
    {
        return [
            'assist_date' => 'date',
        ];
    }

    public function helper(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'helper_student_id');
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'host_student_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function statusPillClass(): string
    {
        return match ($this->approval_status) {
            'Disetujui' => 'pill-normal',
            'Ditolak' => 'pill-sick',
            default => 'pill-info',
        };
    }
}
