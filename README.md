# Pengabdian: Logbook KKN UGM

Aplikasi logbook, presensi, dan monitoring kesehatan peserta **KKN-PPM UGM** — rewrite Laravel dari PoC PHP (lihat `git log` untuk sejarah PoC asli).

## Stack

- **Laravel 13** (PHP 8.3+) · **Livewire 4** · MySQL 8 · Tailwind 4 + Vite
- SSO **Google** (domain `@ugm.ac.id`) dengan fallback demo login di env `local`
- RBAC: `mahasiswa`, `kormasit`, `korcam`, `dpl`, `admin_she`, `admin_lppm` (spatie/permission) — supervisi dibatasi scope wilayah (kabupaten → kecamatan → desa → sub-unit)
- Notifikasi eskalasi kesehatan (DB + mail), peta Leaflet, grafik Chart.js, backup (spatie/laravel-backup), Sentry (DSN kosong = nonaktif)

## Fitur utama

- **Presensi harian**: check-in/out + kondisi kesehatan (Sehat/Sakit Ringan/Sakit Berat/Izin/Alpha); 1 baris per mahasiswa per hari (unique constraint)
- **Logbook**: input kegiatan dengan master data dinamis (tema/program/jenis/lokasi baru otomatis jadi opsi dropdown); kondisi kesehatan diambil dari presensi hari itu; wajib presensi dulu sebelum submit
- **Presensi bantuan**: antar-mahasiswa, disetujui/ditolak oleh pemilik program (ditegakkan server-side)
- **Overview wilayah** (supervisi): KPI harian, tren 14 hari, peta, daftar status; scope query dibatasi cakupan akun
- **Publik tanpa login**: search mahasiswa & logbook (tanpa data kesehatan/PII), peta netral (kondisi kesehatan tidak ditampilkan)

## Setup lokal (Laragon)

```bash
composer install
cp .env.example .env          # lalu sesuaikan DB_*, APP_TIMEZONE (Asia/Jakarta)
php artisan key:generate
php artisan migrate --seed    # demo: 11 mahasiswa/supervisi + data contoh
npm install && npm run build
php artisan serve --port 8010
```

Demo login muncul di halaman `/login` hanya saat `APP_ENV=local` **dan** `DEMO_LOGIN_ENABLED=true`.
Akun contoh: mahasiswa `azmi@student.demo`; supervisi `kormasit.5a@demo.kkn`, `korcam.cangkringan@demo.kkn`, `dpl@demo.kkn`, `she@demo.kkn`, `lppm@demo.kkn`.

## Test

```bash
php artisan test   # 25 tes (SQLite :memory:) — RBAC, privasi publik, XSS, presensi
vendor/bin/pint    # code style
```

## Dokumentasi audit

Hasil audit keamanan + daftar perbaikan yang sudah dieksekusi & diverifikasi: **[AUDIT-REPORT.md](AUDIT-REPORT.md)**.

## Catatan produksi

Isi `GOOGLE_CLIENT_ID/SECRET`, set `APP_ENV=production`, `APP_DEBUG=false`, aktifkan `extension=zip`, lalu `php artisan config:cache`.
