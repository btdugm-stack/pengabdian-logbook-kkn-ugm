# 🚀 Setup GitHub Repository - Quick Start

## Status Saat Ini ✅

| Item | Status |
|------|--------|
| Git Repository | ✅ Initialized |
| Initial Commit | ✅ Created (65118e4) |
| .gitignore | ✅ Configured |
| AI Instructions | ✅ .github/copilot-instructions.md |
| Ready to Push | ✅ Yes |

## Commit History
```
56c0b5a (HEAD -> master) docs: add deployment instructions for GitHub
65118e4 Initial commit: PoC Pengabdian Logbook KKN with sidebar navigation
```

---

## 🔧 Cara Push ke GitHub (3 Langkah Mudah)

### Langkah 1️⃣: Buat Repository di GitHub
1. Buka https://github.com/new
2. **Repository name**: `pengabdian-logbook-kkn-ugm`
3. **Description** (optional): `PoC Logbook KKN with sidebar navigation - PHP Native + MySQL`
4. Pilih **Public** atau **Private**
5. **✋ PENTING: Jangan** centang "Initialize this repository with:"
6. Klik **"Create repository"**

### Langkah 2️⃣: Copy-Paste di PowerShell

Setelah repository dibuat, GitHub akan menampilkan instruksi. Gunakan perintah berikut:

**Untuk HTTPS** (lebih mudah, cukup input username + token):
```powershell
cd c:\laragon\www\pengabdian-logbook-kkn-ugm
git remote add origin https://github.com/USERNAME/pengabdian-logbook-kkn-ugm.git
git branch -M main
git push -u origin main
```

**Untuk SSH** (jika sudah setup SSH key):
```powershell
cd c:\laragon\www\pengabdian-logbook-kkn-ugm
git remote add origin git@github.com:USERNAME/pengabdian-logbook-kkn-ugm.git
git branch -M main
git push -u origin main
```

**Catatan:** Ganti `USERNAME` dengan username GitHub Anda.

### Langkah 3️⃣: Autentikasi (Jika HTTPS)

Saat `git push`, GitHub akan meminta credentials:
- **Username**: `your-github-username`
- **Password**: Gunakan **Personal Access Token** (bukan password biasa)

#### Cara buat Personal Access Token:
1. GitHub → Klik avatar → **Settings**
2. Pilih **Developer settings** (di bawah)
3. Pilih **Personal access tokens** → **Tokens (classic)**
4. Klik **Generate new token (classic)**
5. Di **Note**: ketik `git-push-local`
6. Di **Expiration**: pilih 90 days atau sesuai preferensi
7. Di **Scopes**: centang ✅ **repo** (full control of private repositories)
8. Klik **Generate token**
9. **Copy token** (hanya muncul sekali!)
10. Paste token saat git meminta "password"

---

## 📦 File Structure yang akan di-push

```
pengabdian-logbook-kkn-ugm/
├── .github/
│   └── copilot-instructions.md       [AI Agent Instructions]
├── .gitignore
├── assets/
│   └── style.css
├── database.sql
├── db.php
├── index.php
├── README.md
├── DEPLOYMENT.md
└── GITHUB_SETUP.md (file ini)
```

**Tidak akan di-push** (karena di-.gitignore):
- ❌ `config.php` (database credentials)
- ❌ `exports/` (generated files)
- ❌ `.qodo/` (local tools)

---

## ✨ Setelah Push Berhasil

### Repository URL Anda:
```
https://github.com/USERNAME/pengabdian-logbook-kkn-ugm
```

### Tips Berikutnya:
1. **Add Collaborators**: Settings → Collaborators → Add people
2. **Enable Issues**: untuk tracking bug/feature requests
3. **Create Branches**: `git checkout -b feature/nama-fitur`
4. **Pull Requests**: untuk review sebelum merge ke main
5. **GitHub Pages** (optional): Untuk dokumentasi website

---

## 🐛 Troubleshooting

### ❌ Error: "fatal: remote origin already exists"
```powershell
git remote remove origin
# Lalu ulangi langkah 2️⃣
```

### ❌ Error: "Authentication failed"
- Pastikan token tidak expired
- Generate token baru jika perlu
- Pastikan copy-paste token dengan benar

### ❌ Error: "Branch 'main' not found"
```powershell
git branch -M main
git push -u origin main
```

### ❌ Ingin reset dan mulai ulang?
```powershell
rm -r .git
git init
git add .
git commit -m "Initial commit"
# Ulangi dari Langkah 2️⃣
```

---

## 📚 Reference
- [GitHub Docs: Adding locally hosted repository](https://docs.github.com/en/get-started/importing-your-projects-to-github/importing-a-repository-with-github-importer)
- [Personal Access Tokens Guide](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens)
- [Git Basics](https://git-scm.com/book/en/v2/Git-Basics-Getting-a-Git-Repository)

---

**Created**: June 9, 2026  
**Project**: Pengabdian Logbook KKN PoC  
**Status**: Ready for GitHub ✅
