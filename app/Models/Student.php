<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

class Student extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    /** Peran tertinggi: semua hak admin, dan satu-satunya yang boleh memberi/mengubah akun super admin. */
    public const SUPER_ADMIN_ROLE = 'super_admin';

    /**
     * Seluruh peran yang dikenal aplikasi (di-seed oleh RoleSeeder), mengikuti
     * matriks hak akses proses bisnis:
     *   Mahasiswa = mahasiswa; Ketua = kormasit & korcam; DPL = dpl;
     *   Fakultas = fakultas; Pimpinan = pimpinan; Admin = admin_lppm & super_admin.
     */
    public const ROLES = ['mahasiswa', 'kormasit', 'korcam', 'dpl', 'fakultas', 'pimpinan', 'admin_lppm', self::SUPER_ADMIN_ROLE];

    /**
     * Peserta KKN: punya presensi dan logbook pribadi. Kormasit dan Korcam
     * adalah ketua kelompok, jadi tetap mahasiswa peserta.
     */
    public const FIELD_ROLES = ['mahasiswa', 'kormasit', 'korcam'];

    /** Ketua kelompok: selain logbook pribadi, boleh mengisi logbook kelompok dan memantau kelompoknya. */
    public const GROUP_LEADER_ROLES = ['kormasit', 'korcam'];

    /** Peran yang boleh membuka /overview - cakupannya lihat supervisedStudents(). */
    public const SUPERVISORY_ROLES = ['kormasit', 'korcam', 'dpl', 'fakultas', 'pimpinan', 'admin_lppm', self::SUPER_ADMIN_ROLE];

    /** Peran yang tidak dibatasi wilayah sama sekali. */
    public const FULL_ACCESS_ROLES = ['pimpinan', 'admin_lppm', self::SUPER_ADMIN_ROLE];

    /** Peran yang cakupannya satu unit akademik (fakultas di akunnya), bukan wilayah. Hanya melihat. */
    public const UNIT_ROLES = ['fakultas'];

    /** Peran yang cakupannya daftar mahasiswa yang ditugaskan kepadanya (menu Penugasan DPL), bukan wilayah. */
    public const ASSIGNED_SCOPE_ROLES = ['dpl'];

    /** Peran yang hanya melihat angka agregat/strategis, tanpa data per mahasiswa. */
    public const AGGREGATE_ONLY_ROLES = ['pimpinan'];

    /** Peran yang boleh mereviu logbook mahasiswa: setujui, minta revisi, atau tolak. */
    public const REVIEWER_ROLES = ['dpl', 'admin_lppm', self::SUPER_ADMIN_ROLE];

    /** Admin: kelola akun, master data, dan input/koreksi logbook atas nama mahasiswa. */
    public const ACCOUNT_MANAGER_ROLES = ['admin_lppm', self::SUPER_ADMIN_ROLE];

    /** Peran yang boleh melihat master data (Fakultas hanya baca; mengubah khusus admin). */
    public const MASTER_DATA_VIEWER_ROLES = ['fakultas', 'admin_lppm', self::SUPER_ADMIN_ROLE];

    /** Peran yang boleh diminta lewat pendaftaran mandiri; peran lain hanya lewat Kelola Peserta. */
    public const SELF_REGISTRABLE_ROLES = ['mahasiswa', 'kormasit', 'korcam', 'dpl'];

    /** Label peran untuk tampilan. */
    public const ROLE_LABELS = [
        'mahasiswa' => 'Mahasiswa',
        'kormasit' => 'Kormasit',
        'korcam' => 'Korcam',
        'dpl' => 'DPL',
        'fakultas' => 'Fakultas/Prodi',
        'pimpinan' => 'Pimpinan',
        'admin_lppm' => 'Admin LPPM',
        self::SUPER_ADMIN_ROLE => 'Super Admin',
    ];

    protected $guard_name = 'web';

    protected $fillable = [
        'region_id', 'google_id', 'email', 'name', 'birth_place', 'birth_date',
        'faculty', 'study_program', 'kkn_period', 'kkn_theme', 'phone', 'emergency_contact',
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

    /** Mahasiswa bimbingan akun DPL ini. */
    public function advisees(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'dpl_student', 'dpl_id', 'student_id')->withTimestamps();
    }

    /** DPL pembimbing peserta ini. */
    public function advisors(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'dpl_student', 'student_id', 'dpl_id')->withTimestamps();
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

    /** Peserta KKN (mahasiswa atau ketua kelompok): punya presensi dan logbook pribadi. */
    public function isParticipant(): bool
    {
        return $this->hasAnyRole(self::FIELD_ROLES);
    }

    /** Semua peserta KKN, termasuk ketua kelompok. */
    public function scopeParticipants(Builder $query): void
    {
        $query->role(self::FIELD_ROLES);
    }

    public function isGroupLeader(): bool
    {
        return $this->hasAnyRole(self::GROUP_LEADER_ROLES);
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole(self::ACCOUNT_MANAGER_ROLES);
    }

    /** Hanya boleh melihat angka agregat: tidak ada nama, kontak, atau kondisi per mahasiswa. */
    public function seesAggregateOnly(): bool
    {
        return $this->hasAnyRole(self::AGGREGATE_ONLY_ROLES);
    }

    /**
     * Wilayah yang diawasi akun ini. Sama dengan wilayah di akunnya, kecuali
     * Korcam: ia ditempatkan di satu sub-unit tetapi mengoordinasikan satu
     * kecamatan, jadi cakupannya naik ke kecamatan yang menaungi wilayahnya.
     */
    public function supervisionRegion(): ?Region
    {
        $region = $this->region;

        if (! $region || ! $this->hasRole('korcam')) {
            return $region;
        }

        for ($current = $region; $current; $current = $current->parent) {
            if ($current->level === 'kecamatan') {
                return $current;
            }
        }

        return $region;
    }

    /**
     * Region id yang boleh dilihat akun ini di /overview. `null` berarti tidak
     * dibatasi wilayah (admin, pimpinan, fakultas yang dibatasi lewat unit, dan
     * DPL yang dibatasi lewat penugasan);
     * array kosong berarti belum ada wilayah ditugaskan. Dipakai untuk
     * menegakkan scope di query, bukan cuma UI.
     */
    public function visibleRegionIds(): ?array
    {
        if ($this->hasAnyRole([...self::FULL_ACCESS_ROLES, ...self::UNIT_ROLES, ...self::ASSIGNED_SCOPE_ROLES])) {
            return null;
        }

        return $this->supervisionRegion()?->subtreeIds() ?? [];
    }

    /** Akun supervisi yang cakupannya belum diisi: wilayah, fakultas (Fakultas), atau mahasiswa bimbingan (DPL). */
    public function hasNoSupervisionScope(): bool
    {
        if ($this->hasAnyRole(self::UNIT_ROLES)) {
            return blank($this->faculty);
        }

        if ($this->hasAnyRole(self::ASSIGNED_SCOPE_ROLES)) {
            return ! $this->advisees()->exists();
        }

        return $this->visibleRegionIds() === [];
    }

    /**
     * Query peserta yang berada dalam cakupan akun ini - satu-satunya sumber
     * aturan cakupan untuk overview, pencarian logbook, peta, dan export:
     * admin & pimpinan semua; fakultas satu unit; DPL mahasiswa bimbingannya;
     * ketua satu wilayah.
     *
     * @return Builder<Student>
     */
    public function supervisedStudents(): Builder
    {
        $query = self::participants();

        if ($this->hasAnyRole(self::UNIT_ROLES)) {
            return filled($this->faculty) ? $query->where('faculty', $this->faculty) : $query->whereRaw('1 = 0');
        }

        if ($this->hasAnyRole(self::ASSIGNED_SCOPE_ROLES)) {
            return $query->whereIn('students.id', DB::table('dpl_student')->where('dpl_id', $this->id)->select('student_id'));
        }

        $scope = $this->visibleRegionIds();

        return $query->when($scope !== null, fn (Builder $q) => $q->whereIn('region_id', $scope));
    }

    /**
     * Apakah akun ini boleh melihat data PER ORANG peserta tersebut (detail,
     * kontak, kondisi kesehatan, reviu, notifikasi eskalasi). Pimpinan selalu
     * tidak: ia hanya melihat agregat.
     */
    public function canSupervise(self $student): bool
    {
        if (! $this->hasAnyRole(self::SUPERVISORY_ROLES) || $this->seesAggregateOnly() || ! $student->isParticipant()) {
            return false;
        }

        if ($this->hasAnyRole(self::UNIT_ROLES)) {
            return filled($this->faculty) && $student->faculty === $this->faculty;
        }

        if ($this->hasAnyRole(self::ASSIGNED_SCOPE_ROLES)) {
            return $this->advisees()->whereKey($student->id)->exists();
        }

        $scope = $this->visibleRegionIds();

        return $scope === null || ($student->region_id && in_array((int) $student->region_id, $scope, true));
    }

    /** Akun supervisi lain yang cakupannya meliputi peserta ini (ketua tidak menerima eskalasi dirinya sendiri). */
    public static function supervisorsFor(self $student): Collection
    {
        return self::role(self::SUPERVISORY_ROLES)->get()
            ->filter(fn (self $supervisor) => ! $supervisor->is($student) && $supervisor->canSupervise($student))
            ->values();
    }

    /** Domain email akun demo. Login Google tidak pernah menghasilkan email di domain ini. */
    public const DEMO_EMAIL_DOMAIN = 'demo.kkn';

    /** Akun uji coba buatan DemoAccountSeeder - satu-satunya yang boleh masuk lewat login demo berkode. */
    public function isDemoAccount(): bool
    {
        return str_ends_with($this->email, '@'.self::DEMO_EMAIL_DOMAIN);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::SUPER_ADMIN_ROLE);
    }

    /**
     * Peran yang boleh diberikan akun ini lewat Kelola Peserta. Hanya super
     * admin yang boleh memberi peran super admin - tanpa ini Admin LPPM bisa
     * menaikkan akun mana pun (termasuk miliknya lewat akun kedua) ke peran tertinggi.
     *
     * @return array<int, string>
     */
    public function assignableRoles(): array
    {
        return $this->isSuperAdmin()
            ? self::ROLES
            : array_values(array_diff(self::ROLES, [self::SUPER_ADMIN_ROLE]));
    }

    /** Akun super admin hanya boleh diubah oleh super admin lain. */
    public function canManageAccount(self $account): bool
    {
        return $this->hasAnyRole(self::ACCOUNT_MANAGER_ROLES)
            && ($this->isSuperAdmin() || ! $account->isSuperAdmin());
    }

    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->getRoleNames()->first()] ?? 'Belum ada peran';
    }

    public function initials(): string
    {
        return strtoupper(collect(explode(' ', trim($this->name)))
            ->map(fn ($word) => mb_substr($word, 0, 1))
            ->take(2)
            ->implode(''));
    }
}
