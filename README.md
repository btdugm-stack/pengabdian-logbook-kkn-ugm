# PoC Pengabdian: Logbook KKN v2 Sidebar

Perbaikan:
- Menu utama menjadi sidebar.
- Role umum tanpa login memiliki menu:
  - Search Mahasiswa
  - Search Logbook
  - View Peta
- Search Mahasiswa membaca keseluruhan data mahasiswa dari database.
- Search Logbook membaca keseluruhan data logbook dan relasi mahasiswa.

## Cara Deploy di Laragon

1. Extract folder `poc-logbook-kkn-laragon-v2-sidebar` ke:
   `C:\laragon\www\`

2. Jalankan Apache dan MySQL di Laragon.

3. Import `database.sql` ke MySQL/phpMyAdmin.

4. Buka:
   `http://localhost/poc-logbook-kkn-laragon-v2-sidebar`

## Login Simulasi SSO Google

- azmi@student.demo
- alya@student.demo
- nadi@student.demo
- rafi@student.demo
- dimas@student.demo
- mira@student.demo

## Catatan
SSO Google masih simulasi agar bisa langsung digunakan. Untuk production, integrasikan Google OAuth 2.0.
