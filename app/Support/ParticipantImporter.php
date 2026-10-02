<?php

namespace App\Support;

use App\Models\Region;
use App\Models\Student;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Pendaftaran akun peserta & supervisi KKN. Dipakai bersama oleh command
 * `kkn:import-peserta` dan menu admin "Kelola Peserta" supaya aturan validasi
 * dan cara membentuk hierarki wilayah selalu sama di kedua jalur.
 *
 * Satu baris = kolom COLUMNS (periode dan tema KKN opsional); `wilayah` berupa path "Kabupaten / Kecamatan / Desa / Sub-unit".
 */
class ParticipantImporter
{
    public const COLUMNS = ['email', 'nama', 'peran', 'wilayah', 'fakultas', 'prodi', 'periode', 'tema'];

    /** Level wilayah sesuai kedalaman path. */
    public const LEVELS = ['kabupaten', 'kecamatan', 'desa', 'sub_unit'];

    /** Nama kolom alternatif yang diterima -> nama kolom baku. */
    private const HEADER_ALIASES = [
        'name' => 'nama',
        'role' => 'peran',
        'region' => 'wilayah',
        'faculty' => 'fakultas',
        'study_program' => 'prodi',
        'program_studi' => 'prodi',
        'kkn_period' => 'periode',
        'periode_kkn' => 'periode',
        'kkn_theme' => 'tema',
        'tema_kkn' => 'tema',
    ];

    /**
     * Baca CSV jadi array baris ber-key nama kolom. Pemisah `;` (ekspor Excel
     * berlokal Indonesia) dan `,` dikenali otomatis; BOM UTF-8 dibuang.
     *
     * @return array<int, array<string, string>> key = nomor baris di file
     */
    public function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $firstLine = (string) fgets($handle);
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);

        $header = fgetcsv($handle, null, $delimiter, '"', '');
        if (! $header) {
            fclose($handle);

            return [];
        }

        $header = array_map(function ($column) {
            $column = strtolower(trim(str_replace("\u{FEFF}", '', (string) $column)));

            return self::HEADER_ALIASES[$column] ?? $column;
        }, $header);

        $rows = [];
        $line = 1;
        while (($values = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
            $line++;
            if ($values === [null] || implode('', $values) === '') {
                continue;
            }

            $values = array_pad(array_slice($values, 0, count($header)), count($header), '');
            $rows[$line] = array_combine($header, $values);
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public function normalize(array $input): array
    {
        $row = [];
        foreach (self::COLUMNS as $column) {
            $row[$column] = trim((string) ($input[$column] ?? ''));
        }

        $row['email'] = strtolower($row['email']);
        $row['peran'] = strtolower($row['peran']);

        return $row;
    }

    /** @param  array<string, string>  $row  baris yang sudah di-normalize() */
    public function validator(array $row): ValidatorContract
    {
        return Validator::make($row, [
            'email' => 'required|email|max:150',
            'nama' => 'required|string|max:150',
            'peran' => ['required', Rule::in(Student::ROLES)],
            'wilayah' => 'nullable|string|max:600',
            'fakultas' => 'nullable|string|max:150',
            'prodi' => 'nullable|string|max:150',
            'periode' => 'nullable|string|max:100',
            'tema' => 'nullable|string|max:150',
        ], [], [
            'peran' => 'peran ('.implode('/', Student::ROLES).')',
        ])->after(function ($validator) use ($row) {
            $parts = $this->regionParts($row['wilayah']);

            if ($row['peran'] !== '' && $this->needsRegion($row['peran']) && $parts === []) {
                $validator->errors()->add('wilayah', 'Wilayah wajib diisi untuk peran ini.');
            }
            if (in_array($row['peran'], Student::UNIT_ROLES, true) && $row['fakultas'] === '') {
                $validator->errors()->add('fakultas', 'Fakultas wajib diisi untuk peran ini: menjadi unit yang datanya boleh dilihat.');
            }
            if (count($parts) > count(self::LEVELS)) {
                $validator->errors()->add('wilayah', 'Wilayah maksimal 4 tingkat (Kabupaten / Kecamatan / Desa / Sub-unit).');
            }
            if (collect($parts)->contains(fn (string $part) => mb_strlen($part) > 150)) {
                $validator->errors()->add('wilayah', 'Nama setiap tingkat wilayah maksimal 150 karakter.');
            }
        });
    }

    /**
     * Validasi seluruh file lebih dulu supaya impor bersifat all-or-nothing.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<int, string>> key = nomor baris di file
     */
    public function validateRows(array $rows): array
    {
        $errors = [];
        $seenEmails = [];

        foreach ($rows as $line => $row) {
            $row = $this->normalize($row);
            $messages = $this->validator($row)->errors()->all();

            if ($row['email'] !== '' && isset($seenEmails[$row['email']])) {
                $messages[] = "Email duplikat dengan baris {$seenEmails[$row['email']]}.";
            }

            $seenEmails[$row['email']] ??= $line;

            if ($messages !== []) {
                $errors[$line] = $messages;
            }
        }

        return $errors;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows  baris yang sudah lolos validateRows()
     * @return array{created: int, updated: int}
     */
    public function import(array $rows): array
    {
        $created = $updated = 0;

        DB::transaction(function () use ($rows, &$created, &$updated) {
            foreach ($rows as $row) {
                $this->save($this->normalize($row))->wasRecentlyCreated ? $created++ : $updated++;
            }
        });

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * Buat atau perbarui satu akun berdasarkan email. Kolom fakultas, prodi,
     * periode, dan tema yang kosong tidak menimpa data yang sudah diisi, kecuali
     * $overwriteBlankBiodata (form edit admin, di mana mengosongkan memang disengaja).
     *
     * @param  array<string, string>  $row  baris yang sudah di-normalize() dan valid
     */
    public function save(array $row, bool $overwriteBlankBiodata = false): Student
    {
        $student = Student::firstOrNew(['email' => $row['email']]);

        $student->name = $row['nama'];
        $student->region_id = $this->needsRegion($row['peran'])
            ? $this->resolveRegion($row['wilayah'])?->id
            : null;

        foreach (['fakultas' => 'faculty', 'prodi' => 'study_program', 'periode' => 'kkn_period', 'tema' => 'kkn_theme'] as $column => $attribute) {
            if ($row[$column] !== '' || $overwriteBlankBiodata) {
                $student->{$attribute} = $row[$column] !== '' ? $row[$column] : null;
            }
        }

        $student->save();
        $student->syncRoles([$row['peran']]);

        return $student;
    }

    /**
     * Hanya peserta (mahasiswa dan ketua) yang terikat wilayah penempatan.
     * Cakupan DPL diatur lewat Penugasan DPL, fakultas lewat unitnya.
     */
    public function needsRegion(string $role): bool
    {
        return in_array($role, Student::FIELD_ROLES, true);
    }

    /** @return array<int, string> */
    public function regionParts(string $value): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/[\/>]/', $value) ?: []),
            fn (string $part) => $part !== '',
        ));
    }

    /** Cari atau buat wilayah sepanjang path, level mengikuti kedalamannya. */
    public function resolveRegion(string $value): ?Region
    {
        $region = null;

        foreach ($this->regionParts($value) as $depth => $name) {
            $region = Region::firstOrCreate(
                ['parent_id' => $region?->id, 'name' => $name],
                ['level' => self::LEVELS[$depth]],
            );
        }

        return $region;
    }
}
