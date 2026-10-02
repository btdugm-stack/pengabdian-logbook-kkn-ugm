<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu keputusan reviu atas sebuah logbook. Logbook bisa direviu lebih dari
 * sekali (revisi lalu kirim ulang), jadi tiap keputusan disimpan sebagai baris
 * tersendiri: baris terakhir adalah yang berlaku, sisanya riwayat bimbingan.
 */
class LogbookReview extends Model
{
    protected $fillable = ['logbook_id', 'reviewer_id', 'decision', 'note'];

    public function logbook(): BelongsTo
    {
        return $this->belongsTo(Logbook::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'reviewer_id');
    }

    public function decisionLabel(): string
    {
        return Logbook::STATUS_LABELS[$this->decision] ?? $this->decision;
    }

    public function decisionPillClass(): string
    {
        return Logbook::STATUS_PILLS[$this->decision] ?? 'pill-info';
    }
}
