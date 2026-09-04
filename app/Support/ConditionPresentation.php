<?php

namespace App\Support;

/**
 * Satu tempat untuk memetakan kondisi kesehatan (Sehat/Sakit Ringan/Sakit
 * Berat/Izin/Alpha) ke warna & pill - dipakai oleh Logbook dan
 * DailyAttendance supaya keduanya tidak bisa lagi drift seperti bug lama
 * (marker peta biru padahal legenda hijau).
 */
class ConditionPresentation
{
    public static function pillClass(string $condition): string
    {
        return match ($condition) {
            'Sakit Ringan', 'Sakit Berat' => 'pill-sick',
            'Izin', 'Alpha' => 'pill-warn',
            default => 'pill-normal',
        };
    }

    public static function markerColor(string $condition): string
    {
        return match ($condition) {
            'Sakit Berat' => '#7F1D1D',
            'Sakit Ringan' => '#E11D48',
            'Izin', 'Alpha' => '#D97706',
            default => '#059669',
        };
    }
}
