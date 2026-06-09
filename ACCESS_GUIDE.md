# 📚 Panduan Lengkap: 3 Cara Mengakses Aplikasi

## 🌐 Pilihan Akses Aplikasi

Aplikasi ini dapat diakses dengan 3 cara berbeda sesuai kebutuhan Anda:

---

## Option 1️⃣: **GitHub Pages (Dokumentasi & Demo)**

### URL
```
https://btdugm-stack.github.io/pengabdian-logbook-kkn-ugm/
```

### Konten
✅ Dokumentasi lengkap aplikasi  
✅ Feature showcase  
✅ Architecture overview  
✅ Installation guide  
✅ Technology stack  
✅ Links ke semua resources  

### Kekurangan
❌ Tidak bisa login (hanya static HTML)  
❌ Tidak ada database operations  
❌ Tidak bisa input logbook  
❌ Hanya untuk dokumentasi & info  

### Kapan Digunakan
- **First time visitors** - Lihat overview aplikasi
- **Documentation purpose** - Baca fitur & architecture
- **Demo untuk clients** - Show dokumentasi interaktif
- **Public sharing** - Share link dokumentasi

---

## Option 2️⃣: **Local Development (Full Functionality)**

### Requirements
- **Laragon** (Windows) atau **XAMPP** (Mac/Linux)
- **PHP 7.4+** dengan PDO MySQL
- **MySQL 5.7+**
- **Git**

### Setup Steps

#### 1. Clone Repository
```bash
git clone https://github.com/btdugm-stack/pengabdian-logbook-kkn-ugm.git
cd pengabdian-logbook-kkn-ugm
```

#### 2. Setup Database
```bash
# Buka phpMyAdmin di http://localhost/phpmyadmin
# 1. Create database: logbook_kkn
# 2. Import database.sql
```

#### 3. Configure Application
Edit `config.php`:
```php
define('APP_BASE_URL', 'http://localhost/pengabdian-logbook-kkn-ugm');
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'logbook_kkn');
define('DB_USER', 'root');
define('DB_PASS', ''); // Kosong untuk Laragon
```

#### 4. Access Application
```
http://localhost/pengabdian-logbook-kkn-ugm
```

### URL Structure
| Path | Purpose |
|------|---------|
| `/` | Home page |
| `?page=login` | Student login |
| `?page=dashboard` | Student dashboard |
| `?page=logbook_form` | Input logbook |
| `?page=my_logbooks` | View my logbooks |
| `?page=map` | My location map |
| `?page=export` | Export CSV |
| `?page=public_students` | Search students (public) |
| `?page=public_logbooks` | Search logbooks (public) |
| `?page=public_map` | View location map (public) |

### Test Credentials
```
azmi@student.demo
alya@student.demo
nadi@student.demo
rafi@student.demo
dimas@student.demo
mira@student.demo
```

### Fitur yang Tersedia
✅ Full authentication (SSO simulasi)  
✅ Database operations (CRUD)  
✅ Real-time dropdown data creation  
✅ Logbook input dengan location mapping  
✅ CSV export  
✅ Advanced search & filtering  
✅ Interactive maps dengan Leaflet.js  

### Kapan Digunakan
- **Development** - Modifying code & features
- **Testing** - Testing semua functionality
- **Internal demo** - Demo untuk tim/client dengan full features
- **Local deployment** - Run di intranet company

---

## Option 3️⃣: **Cloud Production Deployment**

### Cloud Platforms

#### **Railway.app** ⭐ (Recommended - Most Beginner Friendly)
```bash
# 1. Install Railway CLI
npm i -g railway

# 2. Login
railway login

# 3. Connect GitHub
railway link

# 4. Deploy
railway up
```
✅ Easiest setup  
✅ Free tier available  
✅ Auto MySQL service  
✅ GitHub integration  

#### **Heroku** (Classic)
```bash
# 1. Install Heroku CLI
# 2. Login
heroku login

# 3. Create app
heroku create pengabdian-logbook-kkn

# 4. Add MySQL
heroku addons:create cleardb:ignite

# 5. Deploy
git push heroku main
```

#### **DigitalOcean App Platform**
```
1. Push code ke GitHub
2. DigitalOcean App Platform → Create App
3. Connect GitHub repo
4. Add MySQL service
5. Deploy
```

#### **Google Cloud Run** (Serverless)
```bash
gcloud app deploy
```

### Konfigurasi Production
Untuk production, buat `config.prod.php`:
```php
// Use environment variables
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));
define('GOOGLE_SSO_SIMULATION', false); // Setup real Google OAuth
```

### Environment Variables (Cloud)
```
DB_HOST=cloud-mysql-host
DB_NAME=logbook_kkn
DB_USER=root
DB_PASS=secure-password
APP_BASE_URL=https://your-domain.com
```

### Kapan Digunakan
- **Production deployment** - Live aplikasi untuk end users
- **Multiple users** - Collaborative access untuk team
- **High availability** - Redundancy & backup
- **Auto-scaling** - Handle traffic spikes
- **SSL/HTTPS** - Secure communication
- **Domain custom** - Professional URL

---

## 📊 Comparison Table

| Fitur | GitHub Pages | Local Dev | Cloud |
|-------|--------------|-----------|-------|
| Dokumentasi | ✅ | ✅ | ✅ |
| Database | ❌ | ✅ | ✅ |
| Login/Auth | ❌ | ✅ | ✅ |
| Input Logbook | ❌ | ✅ | ✅ |
| Search/Maps | ❌ | ✅ | ✅ |
| Export | ❌ | ✅ | ✅ |
| Public Access | ✅ | ✅ | ✅ |
| Cost | Free | Free | Mulai $5/month |
| Setup Effort | 5 min | 30 min | 1 hour |
| Performance | Fast | Depends | High |
| SSL/HTTPS | ✅ | ❌ | ✅ |
| Custom Domain | Optional | ❌ | ✅ |

---

## 🎯 Recommendation Flowchart

```
Start
  │
  ├─ Ingin lihat dokumentasi saja?
  │  └─ → GitHub Pages (Option 1)
  │
  ├─ Ingin test aplikasi di komputer?
  │  └─ → Local Development (Option 2)
  │
  └─ Ingin deploy live untuk banyak users?
     └─ → Cloud Deployment (Option 3)
```

---

## 🔧 Quick Reference Commands

### Untuk GitHub Pages
```bash
git add docs/
git commit -m "update docs"
git push origin main
# Auto deploy dalam 1-2 menit
```

### Untuk Local Development
```bash
# Setup
php -S localhost:8000

# Database operations
# Gunakan phpMyAdmin di http://localhost/phpmyadmin

# Testing
# Buka http://localhost/pengabdian-logbook-kkn-ugm
```

### Untuk Cloud Deployment
```bash
git push heroku main        # Heroku
railway up                  # Railway
gcloud app deploy           # Google Cloud
```

---

## 📞 Support & Resources

- **GitHub Issues**: Report bugs & request features
- **README.md**: Project overview
- **GITHUB_PAGES.md**: GitHub Pages setup guide
- **DEPLOYMENT_CHECKLIST.md**: Deployment checklist
- **.github/copilot-instructions.md**: Code architecture

---

**Choose your path based on your needs!** 🚀
