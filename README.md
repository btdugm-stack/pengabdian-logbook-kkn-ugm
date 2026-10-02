# Pengabdian: Logbook KKN UGM

Aplikasi logbook, presensi, dan monitoring kesehatan peserta **KKN-PPM UGM** — rewrite Laravel dari PoC PHP (lihat `git log` untuk sejarah PoC asli).

## Stack

- **Laravel 13** (PHP 8.3+) · **Livewire 4** · MySQL 8 · Tailwind 4 + Vite
- SSO **Google** (domain `@ugm.ac.id`) dengan fallback demo login di env `local`
- RBAC (spatie/permission): `mahasiswa`, `kormasit` & `korcam` (ketua kelompok, tetap peserta), `dpl`, `fakultas`, `pimpinan`, `admin_lppm`, `super_admin`. Cakupan data ditegakkan di query: wilayah (ketua, DPL), unit fakultas (Fakultas), agregat saja (Pimpinan), semua (admin)
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
php artisan test   # SQLite :memory: — RBAC per role, privasi publik, XSS, presensi, draft, login Google, impor peserta
vendor/bin/pint    # code style
```

## Dokumentasi audit

Hasil audit keamanan + daftar perbaikan yang sudah dieksekusi & diverifikasi: **[AUDIT-REPORT.md](AUDIT-REPORT.md)**.

## Deploy (early access)

1. `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` lengkap dengan subpath (mis. `https://dts-lab.web.id/pengabdian-kkn`), kredensial DB, `GOOGLE_CLIENT_ID/SECRET`, `GOOGLE_ALLOWED_DOMAIN=ugm.ac.id,mail.ugm.ac.id`, `TRUSTED_PROXIES` bila di belakang reverse proxy, `FEEDBACK_URL` (opsional).
2. Daftarkan `${APP_URL}/auth/google/callback` di *Authorized redirect URIs* Google Cloud Console.
3. `composer install --no-dev --optimize-autoloader && npm ci && npm run build`
4. `php artisan migrate --force && php artisan db:seed --force` — di `production` seeder hanya membuat role, tanpa data demo.
5. Daftarkan akun peserta & pembimbing. Pertama kali (belum ada Admin LPPM) lewat CLI: `php artisan kkn:import-peserta peserta.csv` (cek dulu dengan `--dry-run`). Selanjutnya Admin LPPM bisa memakai menu **Administrasi → Kelola Peserta** (tambah satu akun, ubah peran/wilayah, atau impor CSV). Format CSV (pemisah `,` atau `;`):

   ```csv
   email,nama,peran,wilayah,fakultas,prodi
   azmi@mail.ugm.ac.id,M. Azmi,mahasiswa,Kabupaten Sleman / Kecamatan Ngaglik / Desa Candirejo / Sub-unit 5A,Fakultas Teknik,Teknik Informatika
   dosen@ugm.ac.id,Dr. Dosen,dpl,Kabupaten Sleman,,
   lppm@ugm.ac.id,Admin LPPM,admin_lppm,,,
   ```

   Peran: `mahasiswa`, `kormasit`, `korcam`, `dpl`, `fakultas`, `pimpinan`, `admin_lppm`, `super_admin`. Peserta dan DPL wajib punya `wilayah`; `fakultas` wajib mengisi kolom fakultas (unit yang dilihat); pimpinan dan admin tanpa wilayah. Impor bersifat all-or-nothing dan aman diulang (akun lama diperbarui).
6. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
7. Cron `* * * * * php artisan schedule:run` (backup harian) dan pastikan `extension=zip` aktif.
