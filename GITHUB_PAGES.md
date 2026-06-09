# 🌐 GitHub Pages Setup & Static Demo

## Masalah & Solusi

### ❌ Masalah
GitHub Pages **hanya support file statis** (HTML, CSS, JS). File PHP seperti `index.php` tidak dapat dieksekusi.

### ✅ Solusi
Kami membuat **dokumentasi interaktif & demo statis** di folder `/docs` yang menampilkan:
1. 📖 Dokumentasi lengkap aplikasi
2. 🎯 Live demo dengan static HTML + CSS
3. 📚 Link ke repository GitHub
4. ⚙️ Installation & setup instructions
5. 🏗️ Architecture overview

---

## 📋 File Structure untuk GitHub Pages

```
pengabdian-logbook-kkn-ugm/
├── docs/                          ← GitHub Pages root folder
│   ├── index.html                ← Main demo page
│   ├── assets/
│   │   └── style.css             ← Styling
│   └── _config.yml               ← GitHub Pages config (optional)
├── index.php                      ← Main PHP application
├── database.sql                   ← Database setup
├── README.md                      ← Project overview
└── ...
```

---

## ✅ Setup GitHub Pages (3 Langkah)

### 1️⃣ Push Repository ke GitHub
```bash
git add .
git commit -m "feat: add GitHub Pages documentation and demo"
git push origin main
```

### 2️⃣ Aktifkan GitHub Pages
1. Pergi ke **Repository Settings**
2. Pilih **Pages** (di sidebar kiri)
3. Di **Source** pilih:
   - Branch: `main`
   - Folder: `/docs`
4. Klik **Save**

GitHub akan otomatis deploy dalam beberapa detik.

### 3️⃣ Akses GitHub Pages
Setelah deploy, dokumentasi akan tersedia di:
```
https://btdugm-stack.github.io/pengabdian-logbook-kkn-ugm/
```

---

## 📝 Konten GitHub Pages

### Main Page (`docs/index.html`)
- ✅ Overview aplikasi
- ✅ Feature showcase
- ✅ Test credentials
- ✅ Installation guide
- ✅ Documentation links
- ✅ Architecture diagram
- ✅ Technology stack

### Navigation Menu
- 🏠 Overview
- ✨ Fitur Aplikasi
- 🎯 Live Demo (static)
- ⚙️ Instalasi
- 📚 Dokumentasi
- 🏗️ Arsitektur

### Links ke GitHub
- GitHub Repository
- Issues & Bug Report
- Source Code
- Database Schema

---

## 🔧 Untuk Full Functionality

**GitHub Pages hanya menampilkan dokumentasi statis.**

Untuk menggunakan aplikasi dengan **penuh fungsi** (database, authentication, real-time logging):

### Option 1: Local Development (Recommended)
```bash
# Clone dari GitHub
git clone https://github.com/btdugm-stack/pengabdian-logbook-kkn-ugm.git
cd pengabdian-logbook-kkn-ugm

# Setup di Laragon
# 1. Copy folder ke C:\laragon\www\
# 2. Import database.sql via phpMyAdmin
# 3. Buka http://localhost/pengabdian-logbook-kkn-ugm
```

### Option 2: Cloud Deployment
Untuk production, deploy ke:
- **Heroku** (dengan add-on MySQL)
- **Railway.app** (dengan MySQL service)
- **Render** (dengan PostgreSQL/MySQL)
- **Vercel** (serverless, tapi memerlukan refactor)
- **DigitalOcean** (LAMP stack)

---

## 📱 GitHub Pages Features

✅ **Responsive Design** - Mobile, tablet, desktop  
✅ **Custom Styling** - Sama dengan aplikasi utama  
✅ **Smooth Navigation** - Anchor links dengan scroll smooth  
✅ **Code Snippets** - Installation & setup code  
✅ **Feature List** - Daftar lengkap capabilities  
✅ **Test Credentials** - Pre-seeded mahasiswa  
✅ **Architecture Overview** - Tech stack & DB schema  

---

## 🚀 Workflow untuk Update Dokumentasi

Jika ada perubahan di aplikasi utama, update dokumentasi:

```bash
# 1. Edit docs/index.html atau docs/assets/style.css
# 2. Commit & push
git add docs/
git commit -m "docs: update GitHub Pages documentation"
git push origin main

# 3. GitHub Pages otomatis rebuild dalam 1-2 menit
```

---

## 🔐 Privacy & Security

✅ Database credentials **tidak di-push** ke GitHub  
✅ `.gitignore` exclude `config.php`  
✅ GitHub Pages adalah read-only documentation  
✅ Aktual database hanya di Laragon local / production server  

---

## 📊 Google Analytics (Optional)

Untuk track page views di GitHub Pages, tambahkan ke `docs/index.html`:

```html
<script async src="https://www.googletagmanager.com/gtag/js?id=GA_ID"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'GA_ID');
</script>
```

---

## 🎨 Customize GitHub Pages (Optional)

### Gunakan GitHub Pages Theme
Edit `docs/_config.yml`:

```yaml
theme: jekyll-theme-cayman  # atau theme lain
title: Pengabdian Logbook KKN
description: PoC KKN Logbook Management
```

**Themes tersedia:**
- cayman, leap-day, merlot, midnight, minima, primer, slate, architect, hacker, dark-dimmed

### Custom Domain (Optional)
Jika punya domain sendiri:
1. GitHub Repo → Settings → Pages
2. Di "Custom domain" masukkan domain Anda
3. Setup DNS records di registrar domain

---

## ✨ Tips & Best Practices

1. **Keep docs/ updated** - Sinkron dengan fitur aplikasi terbaru
2. **Add screenshots** - Jika memungkinkan, tambahkan demo screenshots
3. **Link ke source** - Dokumentasi harus link ke code di GitHub
4. **Test locally** - `python -m http.server 8000` untuk test di browser
5. **Use semantic HTML** - Untuk accessibility & SEO
6. **Optimize images** - Jika ada screenshot/images
7. **Mobile first** - CSS responsif sudah siap

---

## 🐛 Troubleshooting

### ❌ GitHub Pages tidak update
- Push changes ke branch `main`
- Tunggu 1-2 menit untuk GitHub Action
- Clear browser cache atau buka in incognito

### ❌ CSS/JS tidak load
- Check file paths di HTML (harus relatif ke `/docs/`)
- Pastikan folder `/docs/assets/` sudah ada
- GitHub Pages case-sensitive untuk folder names

### ❌ Custom domain tidak work
- Check DNS records di registrar
- Verify CNAME file di `/docs/CNAME`
- Tunggu 24 jam untuk DNS propagation

---

**Status**: ✅ GitHub Pages siap  
**URL**: https://btdugm-stack.github.io/pengabdian-logbook-kkn-ugm/  
**Updated**: June 9, 2026
