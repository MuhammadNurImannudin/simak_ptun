-- =============================================
-- SIMAK PTUN Database Schema
-- Sistem Informasi Manajemen Arsip dan Korespondensi
-- Pengadilan Tata Usaha Negara Banjarmasin
-- Version: 2.0
-- =============================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- =============================================
-- Users Management Tables
-- =============================================

-- Users table
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `is_active` tinyint(1) DEFAULT 1,
  `foto_profile` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_username` (`username`),
  INDEX `idx_email` (`email`),
  INDEX `idx_role` (`role`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User sessions table
CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(128) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_session_id` (`session_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Surat Management Tables
-- =============================================

-- Surat Masuk table
CREATE TABLE `surat_masuk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nomor_surat` varchar(100) NOT NULL,
  `tanggal_diterima` date NOT NULL,
  `tanggal_surat` date DEFAULT NULL,
  `pengirim` varchar(255) NOT NULL,
  `perihal` text NOT NULL,
  `jenis_surat` varchar(100) DEFAULT NULL,
  `sifat` enum('biasa','penting','segera','rahasia') DEFAULT 'biasa',
  `status` enum('pending','diproses','selesai') DEFAULT 'pending',
  `disposisi` text DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `file_surat` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  INDEX `idx_nomor_surat` (`nomor_surat`),
  INDEX `idx_tanggal_diterima` (`tanggal_diterima`),
  INDEX `idx_pengirim` (`pengirim`),
  INDEX `idx_status` (`status`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_sifat` (`sifat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Surat Keluar table
CREATE TABLE `surat_keluar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nomor_surat` varchar(100) NOT NULL,
  `tanggal_surat` date NOT NULL,
  `tujuan` varchar(255) NOT NULL,
  `perihal` text NOT NULL,
  `jenis_surat` varchar(100) DEFAULT NULL,
  `sifat` enum('biasa','penting','segera','rahasia') DEFAULT 'biasa',
  `status` enum('draft','terkirim','arsip') DEFAULT 'draft',
  `catatan` text DEFAULT NULL,
  `file_surat` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  INDEX `idx_nomor_surat` (`nomor_surat`),
  INDEX `idx_tanggal_surat` (`tanggal_surat`),
  INDEX `idx_tujuan` (`tujuan`),
  INDEX `idx_status` (`status`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_sifat` (`sifat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- System Tables
-- =============================================

-- Settings table
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `action_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_is_read` (`is_read`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity logs table
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_activity_type` (`activity_type`),
  INDEX `idx_table_name` (`table_name`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backup logs table
CREATE TABLE `backup_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_name` varchar(255) NOT NULL,
  `backup_type` enum('full','database') DEFAULT 'full',
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `action` enum('backup','restore') DEFAULT 'backup',
  `status` enum('success','failed','in_progress') DEFAULT 'success',
  `error_message` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  INDEX `idx_backup_name` (`backup_name`),
  INDEX `idx_backup_type` (`backup_type`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_by` (`created_by`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- File attachments table
CREATE TABLE `file_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  INDEX `idx_table_record` (`table_name`, `record_id`),
  INDEX `idx_uploaded_by` (`uploaded_by`),
  INDEX `idx_uploaded_at` (`uploaded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Disposisi and Tracking Tables
-- =============================================

-- Disposisi tracking table
CREATE TABLE `disposisi_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `surat_masuk_id` int(11) NOT NULL,
  `from_user_id` int(11) DEFAULT NULL,
  `to_user_id` int(11) NOT NULL,
  `disposisi_text` text NOT NULL,
  `priority` enum('rendah','normal','tinggi','urgent') DEFAULT 'normal',
  `due_date` date DEFAULT NULL,
  `status` enum('pending','received','completed') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`surat_masuk_id`) REFERENCES `surat_masuk` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  INDEX `idx_surat_masuk_id` (`surat_masuk_id`),
  INDEX `idx_to_user_id` (`to_user_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Status history table
CREATE TABLE `status_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  INDEX `idx_table_record` (`table_name`, `record_id`),
  INDEX `idx_changed_by` (`changed_by`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Categories and Classifications
-- =============================================

-- Surat categories table
CREATE TABLE `surat_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `category_code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_category_code` (`category_code`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Klasifikasi surat table
CREATE TABLE `klasifikasi_surat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_klasifikasi` varchar(20) NOT NULL UNIQUE,
  `nama_klasifikasi` varchar(255) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `level` int(11) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`parent_id`) REFERENCES `klasifikasi_surat` (`id`) ON DELETE SET NULL,
  INDEX `idx_kode_klasifikasi` (`kode_klasifikasi`),
  INDEX `idx_parent_id` (`parent_id`),
  INDEX `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Default Data Insertion
-- =============================================

-- Default admin user (password: admin123)
INSERT INTO `users` (`username`, `email`, `nama_lengkap`, `password`, `role`, `is_active`) VALUES
('admin', 'admin@ptun-banjarmasin.go.id', 'Administrator SIMAK', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1),
('user', 'user@ptun-banjarmasin.go.id', 'User SIMAK', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 1);

-- Default settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
('app_name', 'SIMAK PTUN', 'Nama aplikasi'),
('app_description', 'Sistem Informasi Manajemen Arsip dan Korespondensi', 'Deskripsi aplikasi'),
('app_version', '2.0', 'Versi aplikasi'),
('institution_name', 'Pengadilan Tata Usaha Negara Banjarmasin', 'Nama instansi'),
('institution_address', 'Jl. Brig Jend H. Hasan Basry No.3, Pangeran, Banjarmasin Utara, Kota Banjarmasin, Kalimantan Selatan', 'Alamat instansi'),
('contact_phone', '(0511) 3252989', 'Telepon kontak'),
('contact_email', 'ptun.banjarmasin@go.id', 'Email kontak'),
('timezone', 'Asia/Jakarta', 'Timezone sistem'),
('session_timeout', '1800', 'Session timeout dalam detik'),
('max_login_attempts', '5', 'Maksimal percobaan login'),
('password_min_length', '6', 'Panjang minimum password'),
('allowed_file_types', 'pdf,doc,docx,jpg,jpeg,png', 'Tipe file yang diizinkan'),
('max_file_size', '5', 'Ukuran maksimal file dalam MB'),
('mail_host', 'smtp.gmail.com', 'SMTP host'),
('mail_port', '587', 'SMTP port'),
('mail_encryption', 'tls', 'SMTP encryption'),
('mail_from_name', 'SIMAK PTUN', 'Nama pengirim email');

-- Default surat categories
INSERT INTO `surat_categories` (`category_name`, `category_code`, `description`) VALUES
('Surat Keputusan', 'SK', 'Surat keputusan dari pimpinan'),
('Surat Edaran', 'SE', 'Surat edaran untuk pemberitahuan'),
('Surat Undangan', 'SU', 'Surat undangan rapat atau acara'),
('Surat Tugas', 'ST', 'Surat penugasan pegawai'),
('Surat Keterangan', 'SKET', 'Surat keterangan berbagai keperluan'),
('Surat Permohonan', 'SP', 'Surat permohonan dari pihak luar'),
('Surat Pengumuman', 'SPENG', 'Surat pengumuman resmi');

-- Default klasifikasi surat berdasarkan standar kearsipan
INSERT INTO `klasifikasi_surat` (`kode_klasifikasi`, `nama_klasifikasi`, `parent_id`, `level`) VALUES
('000', 'UMUM', NULL, 1),
('100', 'PEMERINTAHAN', NULL, 1),
('200', 'POLITIK', NULL, 1),
('300', 'KEAMANAN DAN KETERTIBAN', NULL, 1),
('400', 'KESEJAHTERAAN RAKYAT', NULL, 1),
('500', 'PEREKONOMIAN', NULL, 1),
('600', 'PEKERJAAN UMUM DAN KETENAGAKERJAAN', NULL, 1),
('700', 'PENGAWASAN', NULL, 1),
('800', 'KEPEGAWAIAN', NULL, 1),
('900', 'KEUANGAN', NULL, 1);

-- Sub klasifikasi untuk UMUM
INSERT INTO `klasifikasi_surat` (`kode_klasifikasi`, `nama_klasifikasi`, `parent_id`, `level`) VALUES
('001', 'Lambang', 1, 2),
('002', 'Tanda Kehormatan/Penghargaan', 1, 2),
('003', 'Hari Raya/Hari Besar', 1, 2),
('004', 'Ucapan', 1, 2),
('005', 'Kunjungan Kerja', 1, 2);

-- Sub klasifikasi untuk KEPEGAWAIAN
INSERT INTO `klasifikasi_surat` (`kode_klasifikasi`, `nama_klasifikasi`, `parent_id`, `level`) VALUES
('800', 'Formasi', 9, 2),
('810', 'Pengadaan', 9, 2),
('820', 'Mutasi', 9, 2),
('830', 'Kedudukan', 9, 2),
('840', 'Kesejahteraan Pegawai', 9, 2),
('850', 'Cuti', 9, 2),
('860', 'Penilaian', 9, 2),
('870', 'Tata Usaha Kepegawaian', 9, 2),
('880', 'Pembinaan Mental/Fisik', 9, 2);

-- Triggers for automatic logging
DELIMITER //

-- Trigger untuk log aktivitas surat masuk
CREATE TRIGGER `surat_masuk_insert_log` AFTER INSERT ON `surat_masuk`
FOR EACH ROW BEGIN
    INSERT INTO activity_logs (user_id, activity_type, description, table_name, record_id, new_values, ip_address)
    VALUES (NEW.user_id, 'CREATE', CONCAT('Menambah surat masuk: ', NEW.nomor_surat), 'surat_masuk', NEW.id, JSON_OBJECT('nomor_surat', NEW.nomor_surat, 'pengirim', NEW.pengirim, 'perihal', NEW.perihal), @user_ip);
END//

CREATE TRIGGER `surat_masuk_update_log` AFTER UPDATE ON `surat_masuk`
FOR EACH ROW BEGIN
    INSERT INTO activity_logs (user_id, activity_type, description, table_name, record_id, old_values, new_values, ip_address)
    VALUES (NEW.user_id, 'UPDATE', CONCAT('Mengubah surat masuk: ', NEW.nomor_surat), 'surat_masuk', NEW.id, 
            JSON_OBJECT('status', OLD.status, 'disposisi', OLD.disposisi), 
            JSON_OBJECT('status', NEW.status, 'disposisi', NEW.disposisi), @user_ip);
END//

-- Trigger untuk log aktivitas surat keluar
CREATE TRIGGER `surat_keluar_insert_log` AFTER INSERT ON `surat_keluar`
FOR EACH ROW BEGIN
    INSERT INTO activity_logs (user_id, activity_type, description, table_name, record_id, new_values, ip_address)
    VALUES (NEW.user_id, 'CREATE', CONCAT('Menambah surat keluar: ', NEW.nomor_surat), 'surat_keluar', NEW.id, JSON_OBJECT('nomor_surat', NEW.nomor_surat, 'tujuan', NEW.tujuan, 'perihal', NEW.perihal), @user_ip);
END//

CREATE TRIGGER `surat_keluar_update_log` AFTER UPDATE ON `surat_keluar`
FOR EACH ROW BEGIN
    INSERT INTO activity_logs (user_id, activity_type, description, table_name, record_id, old_values, new_values, ip_address)
    VALUES (NEW.user_id, 'UPDATE', CONCAT('Mengubah surat keluar: ', NEW.nomor_surat), 'surat_keluar', NEW.id, 
            JSON_OBJECT('status', OLD.status), 
            JSON_OBJECT('status', NEW.status), @user_ip);
END//

DELIMITER ;

-- =============================================
-- Views for reporting
-- =============================================

-- View for dashboard statistics
CREATE VIEW `dashboard_stats` AS
SELECT 
    (SELECT COUNT(*) FROM surat_masuk) as total_surat_masuk,
    (SELECT COUNT(*) FROM surat_keluar) as total_surat_keluar,
    (SELECT COUNT(*) FROM surat_masuk WHERE status = 'pending') as surat_pending,
    (SELECT COUNT(*) FROM surat_masuk WHERE status = 'diproses') as surat_diproses,
    (SELECT COUNT(*) FROM surat_masuk WHERE status = 'selesai') as surat_selesai,
    (SELECT COUNT(*) FROM users WHERE is_active = 1) as total_users_active,
    (SELECT COUNT(*) FROM surat_masuk WHERE DATE(created_at) = CURDATE()) as surat_masuk_hari_ini,
    (SELECT COUNT(*) FROM surat_keluar WHERE DATE(created_at) = CURDATE()) as surat_keluar_hari_ini;

-- View for monthly report
CREATE VIEW `monthly_report` AS
SELECT 
    YEAR(sm.tanggal_diterima) as tahun,
    MONTH(sm.tanggal_diterima) as bulan,
    COUNT(sm.id) as total_surat_masuk,
    COUNT(CASE WHEN sm.status = 'pending' THEN 1 END) as pending,
    COUNT(CASE WHEN sm.status = 'diproses' THEN 1 END) as diproses,
    COUNT(CASE WHEN sm.status = 'selesai' THEN 1 END) as selesai,
    (SELECT COUNT(*) FROM surat_keluar sk WHERE YEAR(sk.tanggal_surat) = YEAR(sm.tanggal_diterima) AND MONTH(sk.tanggal_surat) = MONTH(sm.tanggal_diterima)) as total_surat_keluar
FROM surat_masuk sm
GROUP BY YEAR(sm.tanggal_diterima), MONTH(sm.tanggal_diterima)
ORDER BY tahun DESC, bulan DESC;

-- =============================================
-- Indexes for optimization
-- =============================================

-- Additional indexes for better performance
ALTER TABLE `surat_masuk` ADD FULLTEXT(`perihal`, `disposisi`, `catatan`);
ALTER TABLE `surat_keluar` ADD FULLTEXT(`perihal`, `catatan`);
ALTER TABLE `activity_logs` ADD INDEX `idx_created_date` (DATE(`created_at`));
ALTER TABLE `notifications` ADD INDEX `idx_user_unread` (`user_id`, `is_read`);

-- =============================================
-- Final setup
-- =============================================

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- Success message
SELECT 'SIMAK PTUN Database Schema installed successfully!' as message;
