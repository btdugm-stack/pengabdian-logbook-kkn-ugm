<?php

namespace App\Support;

use App\Models\RegistrationRequest;
use App\Models\Student;
use App\Models\Theme;
use Illuminate\Support\Collection;

/**
 * Pilihan untuk kolom cari-pilih-tambah pada data akun (fakultas, program
 * studi, periode KKN, tema KKN). Tidak ada tabel master tersendiri: pilihannya
 * adalah nilai yang sudah pernah dipakai akun atau pendaftar, sehingga isian
 * baru otomatis muncul untuk pendaftar berikutnya.
 */
class ProfileOptions
{
    /** @return Collection<int, string> */
    public static function faculties(): Collection
    {
        return self::merge(config('kkn.faculties'), self::used('faculty'));
    }

    /** @return Collection<int, string> */
    public static function studyPrograms(): Collection
    {
        return self::merge(self::used('study_program'));
    }

    /** @return Collection<int, string> */
    public static function periods(): Collection
    {
        return self::merge(self::used('kkn_period'));
    }

    /** Tema KKN berbagi daftar dengan tema logbook, karena menjadi isian awal tema di form logbook. */
    public static function themes(): Collection
    {
        return self::merge(Theme::pluck('name'), self::used('kkn_theme'));
    }

    /**
     * Rapikan nilai yang diketik pengguna (spasi ganda, tag HTML) dan samakan
     * ejaannya dengan pilihan yang sudah ada bila hanya beda huruf besar/kecil.
     *
     * @param  Collection<int, string>  $options
     */
    public static function canonical(?string $value, Collection $options): string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $value)));

        return $options->first(fn (string $option) => mb_strtolower($option) === mb_strtolower($value)) ?? $value;
    }

    /** @return Collection<int, string> */
    private static function used(string $column): Collection
    {
        return Student::query()->whereNotNull($column)->distinct()->pluck($column)
            ->concat(RegistrationRequest::query()->whereNotNull($column)->distinct()->pluck($column));
    }

    /**
     * @param  iterable<int, string>  ...$lists
     * @return Collection<int, string>
     */
    private static function merge(iterable ...$lists): Collection
    {
        return collect($lists)
            ->flatMap(fn (iterable $list) => $list)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique(fn (string $value) => mb_strtolower($value))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }
}
