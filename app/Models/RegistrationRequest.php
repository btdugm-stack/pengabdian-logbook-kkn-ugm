<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Permintaan pendaftaran mandiri: dibuat oleh pemilik akun Google UGM yang
 * belum terdaftar, lalu disetujui atau ditolak admin di menu Permintaan
 * Pendaftaran. Satu email hanya punya satu baris; mendaftar ulang setelah
 * ditolak memperbarui baris yang sama.
 */
class RegistrationRequest extends Model
{
    public const STATUS_PENDING = 'Pending';

    public const STATUS_APPROVED = 'Approved';

    public const STATUS_REJECTED = 'Rejected';

    /** Kunci session yang menyimpan identitas Google terverifikasi selama mengisi form. */
    public const SESSION_KEY = 'registration_identity';

    protected $fillable = [
        'email', 'google_id', 'name', 'requested_role', 'region_path',
        'faculty', 'study_program', 'kkn_period', 'kkn_theme', 'note', 'status', 'rejection_reason',
        'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Permintaan yang masih perlu diputuskan admin. Email yang sementara itu
     * sudah didaftarkan lewat Kelola Peserta tidak ditampilkan lagi.
     */
    public function scopeAwaitingReview(Builder $query): void
    {
        $query->where('status', self::STATUS_PENDING)
            ->whereNotIn('email', Student::query()->select('email'));
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function requestedRoleLabel(): string
    {
        return Student::ROLE_LABELS[$this->requested_role] ?? $this->requested_role;
    }
}
