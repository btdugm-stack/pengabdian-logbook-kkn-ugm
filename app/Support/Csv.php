<?php

namespace App\Support;

use Closure;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Csv
{
    /**
     * Audit §CSV-injection: sel teks yang diawali =, +, -, @ bisa dieksekusi
     * sebagai formula oleh Excel/Sheets, jadi diberi prefix apostrof.
     */
    public static function cell(mixed $value): string
    {
        return (is_string($value) && $value !== '' && str_contains('=+-@', $value[0]))
            ? "'".$value
            : (string) $value;
    }

    /**
     * Unduhan CSV ber-BOM (supaya Excel membaca UTF-8 dengan benar).
     *
     * @param  array<int, string>  $header
     * @param  Closure(): iterable<int, array<int, mixed>>  $rows
     */
    public static function download(string $filename, array $header, Closure $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header, ',', '"', '');

            foreach ($rows() as $row) {
                fputcsv($out, array_map(self::cell(...), $row), ',', '"', '');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
    }
}
