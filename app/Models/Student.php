<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

class Student extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    /** Peran yang boleh membuka /overview - lihat visibleRegionIds(). */
    public const SUPERVISORY_ROLES = ['kormasit', 'korcam', 'dpl', 'admin_she', 'admin_lppm'];

    /** Peran yang tidak dibatasi wilayah sama sekali di overview. */
    public const FULL_ACCESS_ROLES = ['admin_she', 'admin_lppm'];

    protected $guard_name = 'web';

    protected $fillable = [
        'region_id', 'google_id', 'email', 'name', 'birth_place', 'birth_date',
        'faculty', 'study_program', 'phone', 'emergency_contact',
    ];

    protected $hidden = ['remember_token'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function logbooks(): HasMany
    {
        return $this->hasMany(Logbook::class);
    }

    public function dailyAttendances(): HasMany
    {
        return $this->hasMany(DailyAttendance::class);
    }

    public function assistAttendancesAsHelper(): HasMany
    {
        return $this->hasMany(AssistAttendance::class, 'helper_student_id');
    }

    public function assistAttendancesAsHost(): HasMany
    {
        return $this->hasMany(AssistAttendance::class, 'host_student_id');
    }

    public function todayAttendance(): ?DailyAttendance
    {
        return $this->dailyAttendances()->whereDate('attendance_date', today())->first();
    }

    /**
     * Region id yang boleh dilihat akun ini di /overview. `null` berarti akses
     * penuh (admin_she/admin_lppm); array kosong berarti belum ada wilayah
     * ditugaskan. Dipakai untuk menegakkan scope di query, bukan cuma UI.
     */
    public function visibleRegionIds(): ?array
    {
        if ($this->hasAnyRole(self::FULL_ACCESS_ROLES)) {
            return null;
        }

        return $this->region?->subtreeIds() ?? [];
    }

    /**
     * Akun supervisi (kormasit/korcam/dpl/admin) yang cakupan wilayahnya
     * meliputi mahasiswa ini - dipakai untuk menentukan penerima notifikasi
     * eskalasi kesehatan. Full-access role selalu ikut; role region-scoped
     * ikut hanya jika region_id mahasiswa ini ada di subtree wilayah mereka.
     */
    public static function supervisorsFor(self $student): Collection
    {
        return self::role(self::SUPERVISORY_ROLES)->get()->filter(function (self $supervisor) use ($student) {
            $scope = $supervisor->visibleRegionIds();

            return $scope === null || ($student->region_id && in_array($student->region_id, $scope, true));
        })->values();
    }
}
