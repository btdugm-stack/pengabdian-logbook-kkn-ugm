<?php

namespace App\Models;

use App\Support\ConditionPresentation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Logbook extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'Draft';

    public const STATUS_SUBMITTED = 'Submitted';

    /** Kondisi kesehatan untuk logbook yang diinput admin pada tanggal tanpa presensi. */
    public const HEALTH_UNKNOWN = 'Tidak tercatat';

    /** Dikembalikan DPL dengan lembar revisi; mahasiswa memperbaiki lalu mengirim ulang. */
    public const STATUS_REVISION = 'Revision';

    public const STATUS_APPROVED = 'Approved';

    /** Ditolak DPL. Keputusan akhir: tidak bisa diperbaiki atau dikirim ulang. */
    public const STATUS_REJECTED = 'Rejected';

    /** Keputusan yang bisa diambil reviewer atas logbook berstatus Submitted. */
    public const REVIEW_DECISIONS = [self::STATUS_APPROVED, self::STATUS_REVISION, self::STATUS_REJECTED];

    /** Label status untuk pengguna (nilai di DB tetap bahasa Inggris). */
    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SUBMITTED => 'Terkirim',
        self::STATUS_REVISION => 'Perlu Revisi',
        self::STATUS_APPROVED => 'Disetujui',
        self::STATUS_REJECTED => 'Ditolak',
    ];

    public const STATUS_PILLS = [
        self::STATUS_DRAFT => 'pill-info',
        self::STATUS_SUBMITTED => 'pill-gold',
        self::STATUS_REVISION => 'pill-warn',
        self::STATUS_APPROVED => 'pill-normal',
        self::STATUS_REJECTED => 'pill-sick',
    ];

    protected $fillable = [
        'student_id', 'theme_id', 'program_id', 'activity_type_id', 'location_id',
        'log_date', 'kkn_period', 'community_count', 'health_status', 'progress_note',
        'personal_info', 'documentation', 'status', 'is_group', 'entered_by',
    ];

    protected function casts(): array
    {
        return [
            'log_date' => 'datetime',
            'is_group' => 'boolean',
        ];
    }

    /**
     * Logbook yang sudah pernah dikirim ke pembimbing, apa pun hasil reviunya.
     * Draft adalah catatan kerja pribadi mahasiswa dan tidak tampil di panel
     * supervisi.
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', '!=', self::STATUS_DRAFT);
    }

    /**
     * Logbook yang boleh tampil di halaman publik: yang menunggu reviu atau
     * sudah disetujui. Yang dikembalikan untuk revisi atau ditolak tidak
     * ditampilkan ke umum.
     */
    public function scopeVisibleToPublic(Builder $query): void
    {
        $query->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_APPROVED]);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function needsRevision(): bool
    {
        return $this->status === self::STATUS_REVISION;
    }

    /** Sudah diputuskan final oleh reviewer (disetujui atau ditolak): isinya terkunci. */
    public function isFinal(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_REJECTED], true);
    }

    /** Admin yang menginput atau terakhir mengoreksi logbook ini atas nama pemiliknya. */
    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'entered_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(LogbookReview::class);
    }

    /** Keputusan reviu yang sedang berlaku. */
    public function latestReview(): HasOne
    {
        return $this->hasOne(LogbookReview::class)->latestOfMany();
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
        return self::STATUS_PILLS[$this->status] ?? 'pill-info';
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function markerColor(): string
    {
        return ConditionPresentation::markerColor($this->health_status);
    }
}
