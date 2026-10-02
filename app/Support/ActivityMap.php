<?php

namespace App\Support;

use App\Models\Logbook;
use App\Models\Theme;
use Illuminate\Support\Collection;

/**
 * Data peta kegiatan untuk komponen <x-activity-map>: titik per logbook dan
 * daftar kategori legenda. Legenda sekaligus menjadi filter di browser, jadi
 * tiap titik membawa kunci kategorinya.
 *
 * Dua mode pewarnaan:
 * - kondisi kesehatan, untuk peta milik sendiri dan panel pembimbing;
 * - tema kegiatan, untuk peta publik dan peran agregat - di sana kondisi
 *   kesehatan dan nama mahasiswa (untuk agregat) tidak boleh ikut terkirim.
 */
class ActivityMap
{
    /** Kategori kondisi kesehatan, urut tetap. Warnanya sama dengan ConditionPresentation::markerColor(). */
    private const HEALTH_GROUPS = [
        'sehat' => ['label' => 'Sehat', 'color' => '#059669', 'conditions' => ['Sehat']],
        'sakit-ringan' => ['label' => 'Sakit Ringan', 'color' => '#E11D48', 'conditions' => ['Sakit Ringan']],
        'sakit-berat' => ['label' => 'Sakit Berat', 'color' => '#7F1D1D', 'conditions' => ['Sakit Berat']],
        'izin-alpha' => ['label' => 'Izin/Alpha', 'color' => '#D97706', 'conditions' => ['Izin', 'Alpha']],
        'tidak-tercatat' => ['label' => Logbook::HEALTH_UNKNOWN, 'color' => '#64748B', 'conditions' => [Logbook::HEALTH_UNKNOWN]],
    ];

    /**
     * Palet kategorikal untuk tema, urut tetap dan sudah divalidasi aman buta
     * warna. Warna mengikuti tema (urut id), bukan peringkatnya, supaya tema
     * yang sama selalu berwarna sama. Tema ke-9 dan seterusnya masuk "Tema lainnya".
     */
    private const THEME_PALETTE = ['#2A78D6', '#EB6834', '#1BAF7A', '#EDA100', '#E87BA4', '#008300', '#4A3AA7', '#E34948'];

    private const OTHER_THEMES = ['key' => 'tema-lainnya', 'label' => 'Tema lainnya', 'color' => '#6B7280'];

    /**
     * @param  Collection<int, Logbook>  $logbooks  dengan relasi student, theme, program, location
     * @return array{markers: array<int, array<string, mixed>>, groups: array<int, array{key: string, label: string, color: string}>, legendLabel: string}
     */
    public static function byHealth(Collection $logbooks): array
    {
        $markers = self::located($logbooks)->map(function (Logbook $l) {
            $key = self::healthGroupKey($l->health_status);

            return [
                ...self::baseMarker($l),
                'student' => $l->student->name,
                'health' => $l->health_status,
                'group' => $key,
                'color' => self::HEALTH_GROUPS[$key]['color'],
            ];
        })->values();

        $groups = collect(self::HEALTH_GROUPS)
            ->map(fn (array $group, string $key) => ['key' => $key, 'label' => $group['label'], 'color' => $group['color']])
            ->only($markers->pluck('group')->unique())
            ->values();

        return ['markers' => $markers->all(), 'groups' => $groups->all(), 'legendLabel' => 'Kondisi kesehatan'];
    }

    /**
     * @param  Collection<int, Logbook>  $logbooks  dengan relasi student, theme, program, location
     * @param  bool  $withStudent  false untuk peran agregat: nama mahasiswa tidak dikirim
     * @return array{markers: array<int, array<string, mixed>>, groups: array<int, array{key: string, label: string, color: string}>, legendLabel: string}
     */
    public static function byTheme(Collection $logbooks, bool $withStudent = true): array
    {
        // Slot warna dibagikan menurut seluruh tema yang ada (urut id), bukan hanya
        // yang sedang tampil: filter atau cakupan lain tidak mengubah warna sebuah tema.
        $slots = Theme::orderBy('id')->limit(count(self::THEME_PALETTE))->pluck('id')->flip();

        $groupFor = fn (Logbook $l): array => $slots->has($l->theme_id)
            ? ['key' => 'tema-'.$l->theme_id, 'label' => $l->theme->name, 'color' => self::THEME_PALETTE[$slots[$l->theme_id]]]
            : self::OTHER_THEMES;

        $located = self::located($logbooks);

        $markers = $located->map(function (Logbook $l) use ($groupFor, $withStudent) {
            $group = $groupFor($l);

            return [
                ...self::baseMarker($l),
                'student' => $withStudent ? $l->student->name : null,
                'health' => null,
                'group' => $group['key'],
                'color' => $group['color'],
            ];
        })->values();

        $groups = $located->map($groupFor)->unique('key')
            ->sortBy(fn (array $group) => $group['key'] === self::OTHER_THEMES['key'] ? PHP_INT_MAX : (int) substr($group['key'], 5))
            ->values();

        return ['markers' => $markers->all(), 'groups' => $groups->all(), 'legendLabel' => 'Tema kegiatan'];
    }

    /** @return Collection<int, Logbook> */
    private static function located(Collection $logbooks): Collection
    {
        return $logbooks->filter(fn (Logbook $l) => $l->location->latitude && $l->location->longitude);
    }

    /** @return array<string, mixed> */
    private static function baseMarker(Logbook $l): array
    {
        return [
            'lat' => (float) $l->location->latitude,
            'lng' => (float) $l->location->longitude,
            'title' => $l->program->name,
            'theme' => $l->theme->name,
            'location' => $l->location->name,
            'status' => $l->statusLabel(),
            'count' => $l->community_count,
        ];
    }

    private static function healthGroupKey(string $condition): string
    {
        foreach (self::HEALTH_GROUPS as $key => $group) {
            if (in_array($condition, $group['conditions'], true)) {
                return $key;
            }
        }

        // Nilai lama dari PoC (mis. "Normal") diperlakukan sebagai sehat, seperti ConditionPresentation.
        return 'sehat';
    }
}
