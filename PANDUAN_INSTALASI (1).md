# 🚀 PANDUAN INSTALASI SIMAK PTUN

## 📋 Daftar File yang Telah Dibuat

Berikut adalah semua file yang telah dibuat dan tempat penempatannya:

### 📁 **Root Directory (`C:/xampp/htdocs/simak-ptun/`)**
- `index.php` ← Copy dari `index.php`
- `login.php` ← Copy dari `login.php`
- `logout.php` ← Copy dari `logout.php`
- `README.md` ← Copy dari `README.md`

### 📁 **config/ folder**
- `database.php` ← Copy dari `database.php`
- `config.php` ← Copy dari `config.php`

### 📁 **includes/ folder**
- `functions.php` ← Copy dari `functions.php`
- `header.php` ← Copy dari `header.php`
- `sidebar.php` ← Copy dari `sidebar.php`
- `footer.php` ← Copy dari `footer.php`

### 📁 **assets/css/ folder**
- `style.css` ← Copy dari `style.css`

### 📁 **assets/js/ folder**
- `main.js` ← Copy dari `main.js`
- `notifications.js` ← Copy dari `notifications.js`

### 📁 **database/ folder**
- `simak_ptun.sql` ← Copy dari `database_simak_ptun.sql`

### 📁 **api/ folder**
- `generate-nomor-surat.php` ← Copy dari `generate-nomor-surat-api.php`
- `get-notifications.php` ← Copy dari `get-notifications-api.php`

### 📁 **pages/surat-masuk/ folder**
- `index.php` ← Copy dari `surat-masuk-index.php`
- `tambah.php` ← Copy dari `tambah-surat-masuk.php`
- `edit.php` ← Copy dari `edit-surat-masuk.php`
- `detail.php` ← Copy dari `detail-surat-masuk.php`

### 📁 **pages/surat-keluar/ folder**
- `index.php` ← Copy dari `surat-keluar-index.php`
- `tambah.php` ← Copy dari `tambah-surat-keluar.php`

### 📁 **pages/reports/ folder**
- `laporan-bulanan.php` ← Copy dari `laporan-bulanan.php`
- `statistik-surat.php` ← Copy dari `statistik-surat.php`

### 📁 **pages/profile/ folder**
- `index.php` ← Copy dari `profile-index.php`

---

## 🛠️ **LANGKAH-LANGKAH INSTALASI**

### **1. Persiapan Environment**
```bash
# Install XAMPP
# Download: https://www.apachefriends.org/
# Jalankan Apache + MySQL
```

### **2. Setup Database**
1. Buka **phpMyAdmin** → http://localhost/phpmyadmin
2. Klik **"New"** untuk buat database baru
3. Nama database: `simak_ptun`
4. Klik **"Import"** → Pilih file `database_simak_ptun.sql`
5. Klik **"Go"** untuk import

### **3. Buat Struktur Folder**
Buat folder lengkap di `C:/xampp/htdocs/simak-ptun/`:

```
simak-ptun/
├── assets/
│   ├── css/
│   ├── js/
│   ├── img/
│   └── uploads/
│       ├── surat-masuk/
│       ├── surat-keluar/
│       └── profiles/
├── config/
├── includes/
├── pages/
│   ├── dashboard/
│   ├── surat-masuk/
│   ├── surat-keluar/
│   ├── reports/
│   ├── profile/
│   ├── users/
│   └── settings/
├── database/
├── api/
├── (file-file utama)
```

### **4. Copy File-File**

**📂 Root Files:**
```bash
# Copy ke: C:/xampp/htdocs/simak-ptun/
index.php
login.php  
logout.php
README.md
```

**📂 Config Files:**
```bash
# Copy ke: C:/xampp/htdocs/simak-ptun/config/
database.php
config.php
```

**📂 Includes Files:**
```bash
# Copy ke: C:/xampp/htdocs/simak-ptun/includes/
functions.php
header.php
sidebar.php
footer.php
```

**📂 Assets Files:**
```bash
# Copy ke: C:/xampp/htdocs/simak-ptun/assets/css/
style.css

# Copy ke: C:/xampp/htdocs/simak-ptun/assets/js/
main.js
notifications.js
```

**📂 Database Files:**
```bash
# Copy ke: C:/xampp/htdocs/simak-ptun/database/
# Rename: database_simak_ptun.sql → simak_ptun.sql
```

**📂 API Files:**
```bash
# Copy ke: C:/xampp/htdocs/simak-ptun/api/
# Rename: generate-nomor-surat-api.php → generate-nomor-surat.php
# Rename: get-notifications-api.php → get-notifications.php
```

**📂 Surat Masuk Files:**
```bash
# Copy ke: C:/xampp/htdocs/simak-ptun/pages/surat-masuk/
# Rename: surat-masuk-index.php → index.php
# Rename: tambah-surat-masuk.php → tambah.php
# Rename: edit-surat-masuk.php → edit.php
# Rename: detail-surat-masuk.php → detail.php
```

**📂 Surat Keluar Files:**
```bash
# Copy ke: C:/xampp/htdocs/simak-ptun/pages/surat-keluar/
# Rename: surat-keluar-index.php → index.php
# Rename: tambah-surat-keluar.php → tambah.php
```

**📂 Reports Files:**
```bash
# Copy ke: C:/xampp/htdocs/simak-ptun/pages/reports/
laporan-bulanan.php
statistik-surat.php
```

**📂 Profile Files:**
```bash
# Copy ke: C:/xampp/htdocs/simak-ptun/pages/profile/
# Rename: profile-index.php → index.php
```

### **5. Setup Permissions (Windows)**
Pastikan folder `uploads` dapat di-write:
```bash
# Klik kanan folder: C:/xampp/htdocs/simak-ptun/assets/uploads/
# Properties → Security → Edit → Everyone → Full control
```

### **6. Download Logo (Opsional)**
- Download logo PTUN atau buat placeholder
- Simpan sebagai: `C:/xampp/htdocs/simak-ptun/assets/img/logo-ptun.png`
- Ukuran: 40x40px (PNG format)

---

## 🔗 **Testing & Akses**

### **1. Akses Aplikasi**
```
URL: http://localhost/simak-ptun/
```

### **2. Login Default**
```
Admin:
- Username: admin
- Password: password

User:
- Username: user1  
- Password: password
```

### **3. Test Fitur**
- ✅ Login/Logout
- ✅ Dashboard dengan statistik
- ✅ Tambah surat masuk
- ✅ Tambah surat keluar  
- ✅ Laporan bulanan
- ✅ Statistik surat
- ✅ Sistem notifikasi
- ✅ Edit profile

---

## 🚨 **Troubleshooting**

### **Error "Database connection failed"**
```php
// Cek file: config/database.php
// Pastikan setting:
private $host = 'localhost';
private $username = 'root'; 
private $password = '';
private $database = 'simak_ptun';
```

### **Error "File not found"**
- Pastikan semua file ada di folder yang tepat
- Cek nama file (case-sensitive di Linux/Mac)
- Pastikan ekstensi file `.php`

### **CSS/JS tidak load**
- Cek path file di browser (F12 → Network)
- Clear browser cache (Ctrl+F5)
- Pastikan file `style.css` dan `main.js` ada

### **Upload error**
- Pastikan folder `uploads` ada dan writable
- Cek ukuran file maksimal di `php.ini`
- Restart Apache setelah ubah setting

---

## 📁 **File Struktur Lengkap**

```
C:/xampp/htdocs/simak-ptun/
├── assets/
│   ├── css/
│   │   ├── style.css ✅
│   │   └── responsive.css (opsional)
│   ├── js/
│   │   ├── main.js ✅
│   │   └── notifications.js ✅
│   ├── img/
│   │   └── logo-ptun.png (download manual)
│   └── uploads/
│       ├── surat-masuk/ (buat folder kosong)
│       ├── surat-keluar/ (buat folder kosong)
│       └── profiles/ (buat folder kosong)
├── config/
│   ├── database.php ✅
│   └── config.php ✅
├── includes/
│   ├── header.php ✅
│   ├── sidebar.php ✅
│   ├── footer.php ✅
│   └── functions.php ✅
├── pages/
│   ├── surat-masuk/
│   │   ├── index.php ✅
│   │   ├── tambah.php ✅
│   │   ├── edit.php ✅
│   │   └── detail.php ✅
│   ├── surat-keluar/
│   │   ├── index.php ✅
│   │   ├── tambah.php ✅
│   │   ├── edit.php (buat sendiri)
│   │   └── detail.php (buat sendiri)
│   ├── reports/
│   │   ├── laporan-bulanan.php ✅
│   │   ├── laporan-tahunan.php (buat sendiri)
│   │   ├── statistik-surat.php ✅
│   │   ├── rekapitulasi.php (buat sendiri)
│   │   └── laporan-disposisi.php (buat sendiri)
│   └── profile/
│       └── index.php ✅
├── database/
│   └── simak_ptun.sql ✅
├── api/
│   ├── generate-nomor-surat.php ✅
│   └── get-notifications.php ✅
├── index.php ✅
├── login.php ✅
├── logout.php ✅
└── README.md ✅
```

**Status:**
- ✅ = File sudah dibuat
- (buat sendiri) = Perlu dibuat manual sesuai pola yang ada
- (download manual) = Download dari internet

---

## 🎯 **Tahap Selanjutnya**

### **Untuk PKL (5 Report) - Yang Belum Ada:**
1. `pages/reports/laporan-tahunan.php`
2. `pages/reports/rekapitulasi.php` 
3. `pages/reports/laporan-disposisi.php`

### **Untuk Skripsi (+3 Report):**
4. `pages/reports/analisis-trend.php`
5. `pages/reports/performa-penanganan.php`
6. `pages/reports/dashboard-eksekutif.php`

### **File CRUD yang Belum Ada:**
- `pages/surat-keluar/edit.php`
- `pages/surat-keluar/detail.php`

**Tip:** Copy dari surat-masuk dan sesuaikan untuk surat-keluar.

---

## 🎉 **Selesai!**

Setelah semua file ditempatkan dengan benar:

1. **Akses:** http://localhost/simak-ptun/
2. **Login:** admin / password  
3. **Test semua fitur yang sudah ada**
4. **Lanjutkan development untuk file yang belum ada**

**Good luck dengan PKL dan Skripsi Anda! 🎓**

---

*Developed with ❤️ for Pengadilan Tata Usaha Negara Banjarmasin*
