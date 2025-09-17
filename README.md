# SIMAK PTUN
## Sistem Informasi Manajemen Arsip dan Korespondensi Pengadilan Tata Usaha Negara Banjarmasin

### Deskripsi
SIMAK PTUN adalah aplikasi berbasis web untuk mengelola surat masuk dan surat keluar di Pengadilan Tata Usaha Negara Banjarmasin. Aplikasi ini dibuat untuk memenuhi kebutuhan laporan PKL (5 report) dan Skripsi (8 report).

### Fitur Utama
- **Dashboard** dengan statistik real-time
- **Manajemen Surat Masuk** (Input, Edit, Detail, Status)
- **Manajemen Surat Keluar** (Input, Edit, Detail, Status)
- **Sistem Notifikasi** real-time
- **5 Jenis Laporan PKL**:
  1. Laporan Bulanan
  2. Laporan Tahunan
  3. Statistik Surat
  4. Rekapitulasi
  5. Laporan Disposisi
- **3 Jenis Laporan Tambahan untuk Skripsi**:
  6. Analisis Trend
  7. Performa Penanganan
  8. Dashboard Eksekutif
- **Manajemen User** (Admin)
- **Profile Management**
- **Responsive Design**

### Teknologi yang Digunakan
- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework CSS**: Custom CSS dengan variabel CSS
- **Icons**: Font Awesome 6
- **Charts**: Chart.js
- **Server**: XAMPP (Apache + MySQL + PHP)

### Persyaratan Sistem
- XAMPP v3.3.0 atau lebih baru
- PHP 7.4 atau lebih baru
- MySQL 5.7 atau MariaDB 10.3
- Web browser modern (Chrome, Firefox, Safari, Edge)
- Visual Studio Code (untuk development)

### Instalasi

#### 1. Persiapan Environment
1. **Install XAMPP**
   - Download XAMPP dari https://www.apachefriends.org/
   - Install dan jalankan Apache + MySQL

2. **Install Visual Studio Code**
   - Download dari https://code.visualstudio.com/
   - Install ekstensi PHP, HTML, CSS

#### 2. Setup Database
1. Buka phpMyAdmin (http://localhost/phpmyadmin)
2. Buat database baru dengan nama `simak_ptun`
3. Import file `database/simak_ptun.sql`

#### 3. Setup Aplikasi
1. **Buat Struktur Folder**
   ```
   C:/xampp/htdocs/simak-ptun/
   ├── assets/
   │   ├── css/
   │   ├── js/
   │   ├── img/
   │   └── uploads/
   ├── config/
   ├── includes/
   ├── pages/
   │   ├── dashboard/
   │   ├── surat-masuk/
   │   ├── surat-keluar/
   │   ├── reports/
   │   └── profile/
   ├── database/
   └── (file-file utama)
   ```

2. **Copy File-file**
   - `database.php` → `config/database.php`
   - `config.php` → `config/config.php`
   - `functions.php` → `includes/functions.php`
   - `style.css` → `assets/css/style.css`
   - `main.js` → `assets/js/main.js`
   - `notifications.js` → `assets/js/notifications.js`
   - `header.php` → `includes/header.php`
   - `sidebar.php` → `includes/sidebar.php`
   - `footer.php` → `includes/footer.php`
   - `login.php` → `login.php`
   - `index.php` → `index.php`
   - `logout.php` → `logout.php`
   - `database_simak_ptun.sql` → `database/simak_ptun.sql`

3. **Setup Logo**
   - Download logo PTUN atau gunakan logo placeholder
   - Simpan sebagai `assets/img/logo-ptun.png` (40x40px recommended)

#### 4. Konfigurasi
1. Buka `config/database.php`
2. Sesuaikan konfigurasi database jika diperlukan:
   ```php
   private $host = 'localhost';
   private $username = 'root';
   private $password = '';
   private $database = 'simak_ptun';
   ```

### Akses Aplikasi

1. **Buka browser** dan akses: `http://localhost/simak-ptun/`

2. **Login dengan akun default**:
   - **Admin**: 
     - Username: `admin`
     - Password: `password`
   - **User**:
     - Username: `user1`
     - Password: `password`

### Struktur Menu

#### Sidebar Navigation
- **Dashboard** - Halaman utama dengan statistik
- **Surat Masuk** - Manajemen surat masuk
- **Surat Keluar** - Manajemen surat keluar
- **Laporan** (5 untuk PKL):
  - Laporan Bulanan
  - Laporan Tahunan
  - Statistik Surat
  - Rekapitulasi
  - Laporan Disposisi
- **Laporan Lanjutan** (3 tambahan untuk Skripsi - Admin only):
  - Analisis Trend
  - Performa Penanganan
  - Dashboard Eksekutif
- **Pengaturan** (Admin only):
  - Manajemen User
  - Pengaturan Sistem
- **Profile** - Manajemen profil user

#### Header Features
- **Breadcrumb Navigation**
- **Search Function** (pada halaman tertentu)
- **Notifications Dropdown** dengan real-time updates
- **User Profile Dropdown**

### Fitur Utama

#### 1. Dashboard
- Statistik total surat masuk/keluar
- Status breakdown (pending, diproses, selesai)
- Chart trend 6 bulan terakhir
- Surat terbaru
- Quick actions

#### 2. Manajemen Surat
- **Surat Masuk**: Input, edit, detail, ubah status, disposisi
- **Surat Keluar**: Input, edit, detail, ubah status
- Upload file attachment (PDF, DOC, DOCX, JPG, PNG)
- Generate nomor surat otomatis
- Search dan filter

#### 3. Sistem Notifikasi
- Real-time notifications
- Toast notifications
- Email notifications (dapat dikembangkan)
- Notification history

#### 4. Laporan
Tersedia 8 jenis laporan untuk kebutuhan PKL dan Skripsi:
- Export ke PDF/Excel
- Filter berdasarkan periode
- Visualisasi chart dan grafik

#### 5. Security Features
- Session management
- Role-based access control
- Input validation dan sanitization
- File upload validation
- CSRF protection (dapat ditambahkan)

### Pengembangan Lebih Lanjut

#### Untuk PKL (5 Report)
File-file yang perlu dibuat di `pages/reports/`:
1. `laporan-bulanan.php`
2. `laporan-tahunan.php`
3. `statistik-surat.php`
4. `rekapitulasi.php`
5. `laporan-disposisi.php`

#### Untuk Skripsi (8 Report)
Tambahan 3 report:
6. `analisis-trend.php`
7. `performa-penanganan.php`
8. `dashboard-eksekutif.php`

#### Pages yang Perlu Dibuat
1. **Surat Masuk**:
   - `pages/surat-masuk/index.php`
   - `pages/surat-masuk/tambah.php`
   - `pages/surat-masuk/edit.php`
   - `pages/surat-masuk/detail.php`

2. **Surat Keluar**:
   - `pages/surat-keluar/index.php`
   - `pages/surat-keluar/tambah.php`
   - `pages/surat-keluar/edit.php`
   - `pages/surat-keluar/detail.php`

3. **Profile**:
   - `pages/profile/index.php`

4. **Admin** (jika diperlukan):
   - `pages/users/index.php`
   - `pages/settings/index.php`

### Keyboard Shortcuts
- `Ctrl + Shift + D` - Dashboard
- `Ctrl + Shift + I` - Surat Masuk
- `Ctrl + Shift + O` - Surat Keluar
- `Ctrl + Shift + R` - Reports
- `Ctrl + Shift + P` - Profile
- `Ctrl + Shift + L` - Logout
- `Escape` - Close modals/dropdowns

### Troubleshooting

#### Error Database Connection
1. Pastikan MySQL sudah running di XAMPP
2. Cek konfigurasi di `config/database.php`
3. Pastikan database `simak_ptun` sudah dibuat

#### Error File Not Found
1. Pastikan semua file sudah ditempatkan di folder yang benar
2. Cek case-sensitive pada nama file (Linux/Mac)

#### Error Permission Denied
1. Pastikan folder `assets/uploads/` memiliki permission write
2. Pada Linux/Mac: `chmod 755 assets/uploads/`

#### Styling Tidak Muncul
1. Cek path ke file CSS di `assets/css/style.css`
2. Clear browser cache
3. Cek console browser untuk error 404

### Support
Untuk bantuan lebih lanjut:
- Email: support@ptun-banjarmasin.go.id
- Documentation: Lihat komentar di dalam kode
- Issues: Catat masalah yang ditemukan untuk perbaikan

### License
Aplikasi ini dibuat untuk keperluan akademik (PKL dan Skripsi) di Pengadilan Tata Usaha Negara Banjarmasin.

### Changelog
- **v1.0.0** - Initial release dengan fitur dasar
- **v1.1.0** - Penambahan sistem notifikasi
- **v1.2.0** - Penambahan 5 report untuk PKL
- **v2.0.0** - Penambahan 3 report tambahan untuk Skripsi

---
*Dikembangkan dengan ❤️ untuk Pengadilan Tata Usaha Negara Banjarmasin*
