# AUDIT REPORT — Logbook KKN (Laravel)

**Direktori:** `C:\laragon\www\logbook-kkn`
**Tanggal audit:** 4 September 2026
**Stack:** Laravel 13.17 · Livewire 4 · MySQL 8.4 (Laragon) · PHP 8.3.16 · Vite/Tailwind 4
**Metode:** static review seluruh kode (app/, routes/, resources/views, database/), run test suite (22 tes), probe HTTP langsung, dan QA browser end-to-end (login demo, presensi, logbook, notifikasi, RBAC overview, peta).

---

## 1. Ringkasan Eksekutif

| Severity | Jumlah | Ringkasan |
|---|---|---|
| **High** | 1 | Stored XSS di popup peta via nama master-data (program/lokasi) |
| **Medium** | 3 | Zona waktu UTC; data kesehatan publik di `/map/public`; pesan validasi mentah (`validation.required`) |
| **Low** | 4 | CSV injection export; wildcard `%` di pencarian publik; crash `ZipArchive` bila config-cache kosong + tanpa ext zip; menu logbook tampil utk supervisor tapi alur terblokir |

**Verdict: ✅ SIAP DIJALANKAN** — untuk lokal/demo (artisan serve `127.0.0.1:8010`, DB termigrasi & terseed, 22/22 tes PASS, 0 error JS). **Belum siap produksi** tanpa perbaikan: timezone WIB, terjemahan `lang/id`, sanitasi popup peta, dan kebijakan data kesehatan publik.

---

## 2. Temuan

### 🔴 HIGH — Stored XSS di popup peta (nama master-data bebas input)
- **Lokasi:** `resources/views/map.blade.php:46-53` (popup marker via template literal → `bindPopup()` = innerHTML).
- **Alur:** Mahasiswa bebas mengetik nama Tema/Program/Jenis/Lokasi baru (`LogbookForm.php:107-110`, `firstOrCreateFromName`). Nama tersimpan mentah di DB. `MapController` memasukkan `program->name` dkk ke JSON marker tanpa sanitasi, lalu dirender sebagai **HTML** saat popup dibuka.
- **Bukti live:** dibuat logbook dengan program bernama `XSS<img src=x onerror="window.__xss=1">probe-7f3` → tersimpan di DB → muncul mentah di `@json` marker `/map/public`. Nilai `\u003C` hanya melindungi inline-script, setelah `JSON.parse` string tetap berisi `<img onerror>` yang dieksekusi saat popup dibuka via innerHTML. (Row probe sudah dihapus.)
- **Dampak:** siapa pun (termasuk pengunjung publik `/map/public` dan `/overview`) yang membuka popup marker mengeksekusi skrip penyerang. Server-side penyimpanan dibuka utk semua mahasiswa login.
- **Fix:** (a) bangun konten popup via `document.createElement` + `textContent`, atau escape HTML sebelum dimasukkan ke template literal; (b) batasi panjang/karakter nama master-data saat `firstOrCreate` (mis. regex `[\p{L}\p{N} .,\-()&/]`).

### 🟠 MEDIUM — `/map/public` membocorkan kondisi kesehatan per-individu + koordinat
- **Lokasi:** `MapController.php:16-19` (public) — view `map.blade.php` popup menampilkan `Kondisi: {health}` + nama mahasiswa + desa.
- **Bukti live:** `GET /map/public` → marker `"student":"Nadia Salsabila", "location":"Desa Tirtonirmolo, Bantul", "health":"Sakit Ringan"` (dan entri lain). Padahal halaman publik `/search/logbook` justru menyembunyikan kolom Kondisi (`logbook-table.blade.php` `publicSafe`). **Inkonsistensi kebijakan privasi** — kondisi kesehatan adalah data pribadi.
- **Fix:** sembunyikan `health` pada mode publik (kosongkan `Kondisi`/warna netral di popup), atau tampilkan hanya agregat. Konsistenkan dgn `PublicSearchController` (komentar kode di sana menyebut PII sengaja tidak diselect).

### 🟠 MEDIUM — Semua waktu berjalan di UTC (bukan WIB)
- **Lokasi:** `config/app.php:68` → `'timezone' => 'UTC'` (hardcode, tidak baca `APP_TIMEZONE`; tidak ada di `.env`).
- **Bukti live:** jam lokal WIB ±02:40 tanggal 4 Sep, aplikasi menampilkan presensi "hari ini" = **03 Sep** (`attendance_date=2026-09-03`), banner "Check-in 07.45". Artinya mahasiswa yang presensi 00:00–06:59 WIB akan tercatat di **tanggal kemarin**, dan batas "hari ini" (KPI overview, blokir logbook) bergeser.
- **Fix:** `config/app.php` → `'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta')`, set `.env` `APP_TIMEZONE=Asia/Jakarta`, lalu `php artisan config:clear` (+ `config:cache` di prod). Pertimbangkan juga sesuaikan `DB`/`Carbon` bila perlu presisi menit.

### 🟠 MEDIUM — Pesan validasi tampil sebagai key mentah (`validation.required`)
- **Lokasi:** `config/app.php:81` `APP_LOCALE=id`, tapi tidak ada `lang/id/` (hanya `lang/vendor/backup/*`). Laravel fallback hanya `en`.
- **Bukti live:** submit presensi Sakit Ringan tanpa catatan → UI menampilkan teks `validation.required` di bawah textarea.
- **Fix:** `composer require laravel-lang/common` lalu `php artisan lang:add id` (atau publish + terjemahan manual `lang/id/validation.php`). Berlaku utk semua form (presensi, logbook, bantuan, profil).

### 🟡 LOW — CSV formula injection pada export logbook
- **Lokasi:** `LogbookController.php:38-51`.
- **Detail:** sel CSV tidak disanitasi; isi user-controlled (`progress_note`, `personal_info`) yang diawali `=`, `+`, `-`, `@` bisa jadi formula saat dibuka di Excel/Sheets (mis. `=HYPERLINK(...)` / `=cmd|...`).
- **Fix:** prefix `'` atau tab pada sel teks yang diawali karakter berbahaya.

### 🟡 LOW — Wildcard `%`/`_` tidak di-escape pada pencarian publik
- **Lokasi:** `PublicSearchController.php:25-28, 51-57` (`like "%{$q}%"` — parameterized, bukan SQLi).
- **Detail:** input `%` atau `_` berfungsi sebagai wildcard (mis. `q=%` = semua baris); bisa membebani query di data besar.
- **Fix:** escape `%`, `_`, `\` pada `$q` sebelum `like`, atau pindah ke fulltext (saran jangka panjang).

### 🟡 LOW — Crash `ZipArchive` bila config cache kosong & PHP tanpa ext zip
- **Lokasi:** `config/backup.php:149` `'compression_method' => ZipArchive::CM_DEFAULT` — konstanta class memicu autoload **setiap request** selama config belum di-cache.
- **Bukti:** `storage/logs/laravel.log` — `Class "ZipArchive" not found` + `EMERGENCY ... public/index.php` (2×, via web request, sebelum `.env` LOG_CHANNEL diperbaiki). CLI PHP 8.3.16 Laragon punya `zip` → artisan serve aman; **SAPI lain tanpa `zip` (mis. Apache/mod_php berbeda) akan fatal di tiap request** walau fitur backup tidak dipakai.
- **Fix:** `php artisan config:cache` (evaluasi sekali saat cache dibuat), atau hapus baris opsi `ZipArchive` dari config bila backup tidak digunakan, dan pastikan `extension=zip` aktif di php.ini SAPI produksi.

### 🟡 LOW — Menu logbook tampil utk peran supervisi tapi alurnya mustahil dipakai
- **Lokasi:** `layouts/app.blade.php:58-64` (menu auth utk semua) vs aturan `LogbookForm.save` (wajib presensi hari ini — hanya mahasiswa yg bisa).
- **Detail:** kormasit/korcam/dpl masuk menu "Input Logbook"/"Logbook Saya", tapi tak bisa presensi → tidak akan pernah bisa submit; hanya pesan "belum presensi".
- **Fix (opsional):** sembunyikan menu tsb utk non-mahasiswa (`@hasrole('mahasiswa')`), atau izinkan supervisor menyimpan tanpa presensi.

---

## 3. Yang Sudah Benar (terverifikasi live)

- **RBAC ketat & teruji:** `/overview` 403 utk mahasiswa (live + test); kormasit sub-unit 5A hanya melihat 2 mahasiswanya (scope di query, bukan cuma UI); `AssistAttendanceList.decide()` hanya host pemilik program (test non-host → 403). Filter wilayah memakai `subtreeIds()` ∩ `visibleRegionIds()`.
- **Eskalasi kesehatan:** perubahan ke kondisi sakit mengirim notifikasi ke supervisor yg cakupannya meliputi mahasiswa tsb (live: azmi → kormasit 5A + dpl + 2 admin = 4 penerima, badge & halaman notifikasi, tandai-semua-dibaca jalan, email masuk mail-log). Sakit berulang tidak mengirim ulang (test). Kondisi Sehat tidak mengirim (test).
- **Presensi & logbook terkunci konsisten:** tanpa presensi hari ini → submit logbook diblok (UI disabled + guard server; live dgn akun nadi). Kondisi kesehatan logbook otomatis = kondisi presensi hari ini. Check-in dua kali di hari sama memperbarui baris sama (unique `student_id+attendance_date`), bukan duplikat (live + test).
- **Keamanan dasar:** semua query parameterized (tidak ada SQLi); tidak ada `{!! !!}`; escaping Blade konsisten di tabel/form; demo-login hanya aktif di env `local`/`testing` **dan** `DEMO_LOGIN_ENABLED` (fail-safe dobel, sengaja); PII sensitif (phone, emergency_contact, birth_date, kondisi) sudah di-strip dari pencarian mahasiswa publik; logout = invalidate + regenerate token.
- **Kualitas engineering:** 22 tes PASS (57 assertions) di SQLite :memory:; 0 error console JS di semua halaman; migrasi + 6 seeder konsisten (11 mahasiswa, 7 logbook, 29 presensi, 2 bantuan); master data reuse via `firstOrCreateFromName`; komentar audit terdahulu ditindaklanjuti.
- **Runtime:** Apache :80 & MySQL :3306 jalan; aplikasi live di `http://127.0.0.1:8010` (200 OK); CSV export valid (header + baris benar); pencarian publik mahasiswa & logbook berfungsi.

---

## 4. Catatan Lingkungan & Setup

- **Jalankan (dev):** `php artisan serve --port 8010` (CLI php Laragon 8.3.16 — pastikan di PATH: `C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64`). DB `logbook_kkn_v2` sudah migrated+seeded.
- **Akun demo:** mahasiswa `azmi@student.demo` dkk; supervisi `kormasit.5a@demo.kkn`, `korcam.cangkringan@demo.kkn`, `dpl@demo.kkn`, `she@demo.kkn`, `lppm@demo.kkn` (login demo hanya muncul di env local).
- **Catatan:** vhost Apache `pengabdian-logbook-kkn-ugm.test` di Laragon menunjuk ke **PoC lama** (`C:\laragon\www\pengabdian-logbook-kkn-ugm`), bukan project ini — jangan bingung saat membuka `.test`. Project ini dilayani lewat `127.0.0.1:8010`.
- **Kondisi DB pasca-audit:** dikembalikan ke state seed (azmi kembali `Sehat`, notifikasi 0, logbook 7, payload XSS dihapus). Hanya timestamp `updated_at` baris presensi azmi yang berubah.
- **Sebelum produksi:** perbaiki 3 temuan Medium (timezone, lang/id, peta publik) + XSS popup; `APP_DEBUG=false`; isi `GOOGLE_CLIENT_ID/SECRET` & verifikasi callback; `php artisan config:cache`; ekstensi zip aktif di SAPI produksi.

---

## 5. Perbaikan Dieksekusi (4 September 2026 — status: SELESAI & TERVERIFIKASI)

| Temuan | Fix | Verifikasi |
|---|---|---|
| 🔴 Stored XSS popup peta | 1) `map.blade.php` & `overview/index.blade.php`: popup dibangun via helper `esc()` (textContent → innerHTML) sehingga nilai DB tidak lagi masuk popup sebagai HTML mentah. 2) `FindableByName::firstOrCreateFromName`: `strip_tags()` — tag HTML tidak pernah tersimpan sebagai nama master-data. | Live: nama program `<img onerror=alert(1)>XSS-PROBE` tersimpan sebagai `XSS-PROBE` polos (9 char), halaman peta tanpa tag `<img>`. Regression test `MapPublicPrivacyTest::test_master_name_strips_html_tags`. |
| 🟠 Data kesehatan di peta publik | `MapController`: mode `public: true` → `health: null` di marker + warna netral `#3B82F6` (bukan warna berbasis kondisi); legenda peta publik diganti catatan netral. Peta milik sendiri tetap menampilkan kondisi. | Live `/map/public`: 7/7 marker `"health":null`, 0 teks Sakit/Sehat/Izin/Alpha, legenda "Marker netral…". Test `test_public_map_does_not_expose_health_condition` + `test_authenticated_own_map_still_shows_health_condition`. |
| 🟠 Timezone UTC | `config/app.php`: `'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta')`; `.env` & `.env.example`: `APP_TIMEZONE=Asia/Jakarta`; DB di-reseed agar tanggal demo konsisten. | Live: "hari ini" presensi = **04 Sep 2026** (sebelumnya 03 Sep), datetime input logbook = `2026-09-04T10:19` WIB. |
| 🟠 Pesan validasi mentah | `lang/id/validation.php` + `lang/id/pagination.php` (lengkap, termasuk `attributes` utk nama field). | Live: submit presensi Sakit Ringan tanpa catatan → **"catatan kondisi wajib diisi."** |
| 🟡 CSV injection | `LogbookController::export`: sel teks berawalan `= + - @` diberi prefix `'`. | — (logika sel, terformat Pint) |
| 🟡 Wildcard `%`/`_` | `PublicSearchController`: helper `likePattern()` dgn `addcslashes`. | — |
| 🟡 Menu logbook utk supervisor | `layouts/app.blade.php`: menu Input Logbook/Logbook Saya/Peta Saya & tombol topbar "+ Input Logbook" hanya utk role `mahasiswa`. | Live: sidebar kormasit hanya Beranda/Dashboard/Data KKN + Panel Supervisi. |
| 🟡 Crash ZipArchive (config) | `config/backup.php:149`: `class_exists('ZipArchive') ? ZipArchive::CM_DEFAULT : -1` — config aman dievaluasi di SAPI mana pun. | Server + config load normal di CLI (dgn & tanpa cache). |

**State akhir:** 25/25 tes PASS (66 assertions; +3 regression test baru); Pint bersih; server dev berjalan di `http://127.0.0.1:8010`; MySQL Laragon aktif; DB `logbook_kkn_v2` = seed segar konsisten WIB (11 mahasiswa, 7 logbook, 29 presensi, 2 bantuan, 0 notifikasi).

**Sisa utk produksi (bukan bug kode):** isi kredensial Google OAuth, `APP_DEBUG=false` (+ env `production`), pastikan `extension=zip` di SAPI produksi, dan pertimbangkan `php artisan config:cache`.
