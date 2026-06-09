# 📚 Pengabdian Logbook KKN - Documentation

> PoC Logbook KKN dengan PHP Native + MySQL & Sidebar Navigation

[![Status](https://img.shields.io/badge/status-PoC-blue)]() 
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)]() 
[![License](https://img.shields.io/badge/license-MIT-green)]()

## 🎯 Tentang Project

Aplikasi web untuk manajemen logbook KKN (Kuliah Kerja Nyata) mahasiswa UGM dengan fitur:

- ✅ **Dual-Role Access**: Authenticated students (mahasiswa) untuk input, Public users untuk search/view
- ✅ **Logbook Management**: Input progress, kesehatan, lokasi, dokumentasi
- ✅ **Dynamic Master Data**: Data baru → Auto-save → Jadi dropdown
- ✅ **Geospatial Mapping**: Leaflet.js integration dengan marker color-coded
- ✅ **Export Feature**: CSV export untuk reporting
- ✅ **Sidebar Navigation**: Clean, responsive UI dengan role-aware menus

## 🚀 Quick Start

### Requirements
- **PHP 7.4+**
- **MySQL 5.7+**
- **Apache** (Laragon recommended)
- **Browser modern** (Chrome, Firefox, Safari, Edge)

### Setup di Laragon (30 detik)

```bash
# 1. Clone atau extract ke folder www
cd C:\laragon\www
git clone https://github.com/btdugm-stack/pengabdian-logbook-kkn-ugm.git
cd pengabdian-logbook-kkn-ugm

# 2. Setup database
# - Buka http://localhost/phpmyadmin
# - Import database.sql

# 3. Konfigurasi (opsional, sudah ada default)
# - Edit config.php jika perlu ganti database credentials
# - DB_HOST: 127.0.0.1
# - DB_NAME: logbook_kkn
# - DB_USER: root
# - DB_PASS: (kosong untuk Laragon)

# 4. Buka aplikasi
# - http://localhost/pengabdian-logbook-kkn-ugm
```

## 📖 Dokumentasi

### User Guide
- **[🔐 Login & Authentication](./docs/guides/authentication.md)** - Simulasi Google SSO
- **[📝 Input Logbook](./docs/guides/logbook-input.md)** - Cara input data logbook
- **[🔎 Search Features](./docs/guides/search.md)** - Public search mahasiswa & logbook
- **[🗺️ Mapping](./docs/guides/mapping.md)** - Viewing locational data di peta

### Developer Guide
- **[🏗️ Architecture](./docs/dev/architecture.md)** - Single-file MVC pattern
- **[💻 API Reference](./docs/dev/api-reference.md)** - Helper functions & query builders
- **[🔧 Development](./docs/dev/development.md)** - Setup development environment
- **[📦 Deployment](./docs/dev/deployment.md)** - Production deployment

### Quick Reference
- **[Pages & Routes](./docs/reference/pages.md)** - All available routes
- **[Database Schema](./docs/reference/schema.md)** - Table structures & relationships
- **[Form Patterns](./docs/reference/forms.md)** - Common form handling patterns
- **[CSS Classes](./docs/reference/css.md)** - Available UI components

## 🎨 Pages Overview

| Page | Route | Access | Purpose |
|------|-------|--------|---------|
| **Home** | `/` | Public | Landing page dengan overview |
| **Login** | `?page=login` | Public | Simulasi Google SSO login |
| **Dashboard** | `?page=dashboard` | Student | Dashboard ringkasan logbook |
| **Data KKN** | `?page=data_kkn` | Student | Edit biodata mahasiswa |
| **Input Logbook** | `?page=logbook_form` | Student | Form input logbook baru |
| **Logbook Saya** | `?page=my_logbooks` | Student | Daftar logbook personal |
| **Peta Saya** | `?page=map` | Student | Peta lokasi personal |
| **Search Mahasiswa** | `?page=public_students` | Public | Search keseluruhan data mahasiswa |
| **Search Logbook** | `?page=public_logbooks` | Public | Search keseluruhan data logbook |
| **View Peta** | `?page=public_map` | Public | Peta sebaran lokasi semua logbook |
| **Export** | `?page=export` | Student | Download CSV report |

## 🗂️ Project Structure

```
pengabdian-logbook-kkn-ugm/
├── docs/                           # GitHub Pages Documentation
│   ├── index.md                   # (this file)
│   ├── guides/
│   │   ├── authentication.md
│   │   ├── logbook-input.md
│   │   ├── search.md
│   │   └── mapping.md
│   ├── dev/
│   │   ├── architecture.md
│   │   ├── api-reference.md
│   │   ├── development.md
│   │   └── deployment.md
│   └── reference/
│       ├── pages.md
│       ├── schema.md
│       ├── forms.md
│       └── css.md
│
├── .github/
│   └── copilot-instructions.md    # AI Agent Instructions
│
├── assets/
│   └── style.css                   # Main stylesheet
│
├── config.php                      # Configuration (git-ignored)
├── db.php                          # Database utilities
├── index.php                       # Main application (796 lines)
├── database.sql                    # DB schema + seed data
│
├── README.md                       # Project README
├── GITHUB_SETUP.md                # GitHub setup guide
├── DEPLOYMENT.md                  # Deployment documentation
└── DEPLOYMENT_CHECKLIST.md        # Deployment checklist
```

## 🧪 Test Credentials

6 demo accounts sudah pre-seeded dalam `database.sql`:

```
azmi@student.demo        (M. Azmi)
alya@student.demo        (Alya Putri)
nadi@student.demo        (Nadia Salsabila)
rafi@student.demo        (Rafi Pratama)
dimas@student.demo       (Dimas Arya)
mira@student.demo        (Mira Lestari)
```

**Catatan**: Password tidak dibutuhkan untuk simulasi SSO. Pilih akun dari dropdown saat login.

## 🎯 Key Features

### Dynamic Master Data
Ketika user menginput data baru (tema, program, lokasi, dll):
1. Data disimpan ke master table
2. Langsung muncul di dropdown berikutnya
3. Tidak perlu admin setup

```php
// Contoh: Input lokasi baru
$locationId = get_or_create_master('locations', 'Lokasi Baru', [
    'latitude' => -7.7956,
    'longitude' => 110.3695
]);
```

### Geospatial Data
- Koordinat GPS disimpan (latitude, longitude)
- Leaflet.js render peta interaktif
- Marker color-coded by health status
- Responsive design mobile-friendly

### Dual-Role Access Control
```php
// Public pages (tanpa login)
$public_pages = ['home', 'login', 'public_students', 'public_logbooks', 'public_map'];

// Protected pages (memerlukan login)
require_login(); // Redirect ke login jika belum auth
```

## 📊 Database Tables

| Table | Purpose |
|-------|---------|
| **students** | User accounts & biodata |
| **logbooks** | Core records logbook |
| **themes** | Master tema KKN |
| **programs** | Master program kerja |
| **activity_types** | Master jenis kegiatan |
| **locations** | Master lokasi + coordinates |

Foreign keys menjamin data integrity. UNIQUE constraints pada master tables.

## ⚙️ Configuration

Edit `config.php` untuk customize:

```php
define('APP_NAME', 'Pengabdian: Logbook KKN');
define('APP_BASE_URL', 'http://localhost/poc-logbook-kkn-laragon-v2-sidebar');

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'logbook_kkn');
define('DB_USER', 'root');
define('DB_PASS', '');

define('GOOGLE_SSO_SIMULATION', true); // Set false saat production
```

## 🔒 Security Notes

| Item | Status | Note |
|------|--------|------|
| CSRF Protection | ❌ Not implemented | PoC scope - add untuk production |
| SQL Injection | ✅ Parameterized queries | PDO prepared statements |
| XSS Protection | ✅ HTML sanitized | `e()` helper function |
| Credentials | ✅ Git-ignored | `config.php` di .gitignore |
| Password Hashing | ⚠️ Not used | SSO simulation - implement saat production |

## 🐛 Known Issues & Limitations

| Issue | Workaround |
|-------|-----------|
| Master data UNIQUE on name | Delete dari DB jika duplikate name |
| No pagination on tables | Performance OK untuk <1000 records |
| Mobile sidebar unsticky | Expected behavior untuk responsive |
| Datetime timezone | Set di PHP atau MySQL config |

## 📦 Dependencies

### External
- **Leaflet.js** (v1.9.4) - Peta interaktif
- **OpenStreetMap Tiles** - Map layer

### PHP Built-in
- PDO (Database)
- Session (Authentication)
- Date/Time functions

### CSS
- Custom CSS variables
- Grid layout (CSS Grid)
- Responsive design (980px breakpoint)

## 🚀 Deployment

### Laragon (Development)
```bash
# 1. Extract ke C:\laragon\www
# 2. Import database.sql via phpMyAdmin
# 3. Update config.php jika perlu
# 4. Visit http://localhost/pengabdian-logbook-kkn-ugm
```

### Production (VPS/Shared Hosting)
```bash
# 1. Upload files via FTP/SFTP
# 2. Buat database & import SQL
# 3. Update config.php dengan credentials production
# 4. Set environment variables untuk credentials
# 5. Update APP_BASE_URL
```

Lihat [Deployment Guide](./docs/dev/deployment.md) untuk detil lebih.

## 🤝 Contributing

Kontribusi welcome! Untuk kontribusi:

1. **Fork** repository
2. **Create branch** untuk feature: `git checkout -b feature/nama-feature`
3. **Commit changes**: `git commit -m "feat: deskripsi feature"`
4. **Push ke branch**: `git push origin feature/nama-feature`
5. **Open Pull Request**

Baca [CONTRIBUTING.md](./CONTRIBUTING.md) untuk detil lebih.

## 📝 Changelog

### v2 (Sidebar Navigation) - Jun 2026
- ✅ Sidebar navigation added
- ✅ Role-aware menus
- ✅ Responsive design improved
- ✅ GitHub Pages documentation

### v1 (Initial) - Jun 2026
- ✅ Core logbook functionality
- ✅ Dual-role access (students + public)
- ✅ Geospatial mapping
- ✅ Master data management

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/btdugm-stack/pengabdian-logbook-kkn-ugm/issues)
- **Discussions**: [GitHub Discussions](https://github.com/btdugm-stack/pengabdian-logbook-kkn-ugm/discussions)
- **Documentation**: Lihat folder `docs/`

## 📄 License

MIT License - Lihat [LICENSE](./LICENSE) file untuk detil.

---

**Last Updated**: June 9, 2026  
**Status**: PoC | Production Ready: No  
**Contributors**: btdugm-stack  
**Repository**: [GitHub](https://github.com/btdugm-stack/pengabdian-logbook-kkn-ugm)

## Quick Links

- 🌐 [Live Demo](https://localhost/pengabdian-logbook-kkn-ugm) (Local only)
- 📖 [Full Documentation](https://github.com/btdugm-stack/pengabdian-logbook-kkn-ugm/tree/main/docs)
- 🐙 [Source Code](https://github.com/btdugm-stack/pengabdian-logbook-kkn-ugm)
- 💬 [GitHub Discussions](https://github.com/btdugm-stack/pengabdian-logbook-kkn-ugm/discussions)
