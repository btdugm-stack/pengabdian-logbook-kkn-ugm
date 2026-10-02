<?php

namespace App\Livewire\Concerns;

use Closure;

trait HandlesCoordinate
{
    /**
     * Koordinat opsional, tapi kalau diisi harus "lintang, bujur" yang valid -
     * sebelumnya input rusak diam-diam disimpan sebagai null tanpa pesan.
     *
     * @return array<int, mixed>
     */
    protected function coordinateRules(): array
    {
        return ['nullable', 'string', function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->parseCoordinate((string) $value) === [null, null]) {
                $fail('Format koordinat harus "lintang, bujur", misalnya -7.7956, 110.3695.');
            }
        }];
    }

    /** @return array{0: ?float, 1: ?float} */
    protected function parseCoordinate(string $coordinate): array
    {
        $parts = array_map('trim', explode(',', trim($coordinate)));

        if (count($parts) !== 2 || ! is_numeric($parts[0]) || ! is_numeric($parts[1])) {
            return [null, null];
        }

        [$lat, $lng] = [(float) $parts[0], (float) $parts[1]];

        if (abs($lat) > 90 || abs($lng) > 180) {
            return [null, null];
        }

        return [$lat, $lng];
    }
}
