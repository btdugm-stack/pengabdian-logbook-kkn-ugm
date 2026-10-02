<?php

namespace App\Console\Commands;

use App\Support\ParticipantImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('kkn:import-peserta {file : Path file CSV} {--dry-run : Validasi saja tanpa menyimpan apa pun}')]
#[Description('Impor atau perbarui akun peserta & supervisi KKN dari file CSV (email,nama,peran,wilayah,fakultas,prodi)')]
class ImportParticipants extends Command
{
    public function handle(ParticipantImporter $importer): int
    {
        $path = (string) $this->argument('file');

        if (! is_readable($path)) {
            $this->error("File tidak bisa dibaca: {$path}");

            return self::FAILURE;
        }

        $rows = $importer->readCsv($path);

        if ($rows === []) {
            $this->error('File kosong atau tidak memiliki baris data.');

            return self::FAILURE;
        }

        $errors = $importer->validateRows($rows);

        if ($errors !== []) {
            $this->error('Impor dibatalkan, tidak ada data yang disimpan. Perbaiki baris berikut:');
            foreach ($errors as $line => $messages) {
                $this->line("  Baris {$line}: ".implode(' ', $messages));
            }

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info(count($rows).' baris valid. Mode --dry-run: tidak ada data yang disimpan.');

            return self::SUCCESS;
        }

        ['created' => $created, 'updated' => $updated] = $importer->import($rows);

        $this->info("Impor selesai: {$created} akun baru, {$updated} akun diperbarui.");

        return self::SUCCESS;
    }
}
