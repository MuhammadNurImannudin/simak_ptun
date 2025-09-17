-- Database: simak_ptun
-- Buat database baru di phpMyAdmin dengan nama 'simak_ptun'

CREATE DATABASE IF NOT EXISTS `simak_ptun` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `simak_ptun`;

-- Tabel users
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel surat_masuk
CREATE TABLE `surat_masuk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nomor_surat` varchar(100) NOT NULL,
  `tanggal_surat` date NOT NULL,
  `tanggal_diterima` date NOT NULL,
  `pengirim` varchar(255) NOT NULL,
  `perihal` text NOT NULL,
  `lampiran` varchar(255) DEFAULT NULL,
  `file_surat` varchar(255) DEFAULT NULL,
  `status` enum('pending','diproses','selesai') NOT NULL DEFAULT 'pending',
  `disposisi` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nomor_surat` (`nomor_surat`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel surat_keluar
CREATE TABLE `surat_keluar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nomor_surat` varchar(100) NOT NULL,
  `tanggal_surat` date NOT NULL,
  `tujuan` varchar(255) NOT NULL,
  `perihal` text NOT NULL,
  `lampiran` varchar(255) DEFAULT NULL,
  `file_surat` varchar(255) DEFAULT NULL,
  `status` enum('draft','terkirim','arsip') NOT NULL DEFAULT 'draft',
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nomor_surat` (`nomor_surat`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel notifications
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger') NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert data default
INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `email`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator PTUN', 'admin@ptun-banjarmasin.go.id', 'admin'),
('user1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Petugas Administrasi', 'petugas@ptun-banjarmasin.go.id', 'user');

-- Insert sample notifications
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`) VALUES
(1, 'Selamat Datang', 'Selamat datang di SIMAK PTUN Banjarmasin', 'success'),
(1, 'Surat Masuk Baru', 'Ada 3 surat masuk yang belum diproses', 'warning'),
(2, 'Sistem Update', 'Sistem telah diperbarui ke versi terbaru', 'info');

-- Insert sample data surat masuk
INSERT INTO `surat_masuk` (`nomor_surat`, `tanggal_surat`, `tanggal_diterima`, `pengirim`, `perihal`, `status`, `user_id`) VALUES
('001/SM/2024', '2024-01-15', '2024-01-16', 'Pengadilan Negeri Banjarmasin', 'Permohonan Data Perkara', 'pending', 1),
('002/SM/2024', '2024-01-20', '2024-01-21', 'Kejaksaan Negeri Banjarmasin', 'Koordinasi Penanganan Perkara', 'diproses', 1),
('003/SM/2024', '2024-01-25', '2024-01-26', 'BPN Kota Banjarmasin', 'Sengketa Pertanahan', 'selesai', 2);

-- Insert sample data surat keluar
INSERT INTO `surat_keluar` (`nomor_surat`, `tanggal_surat`, `tujuan`, `perihal`, `status`, `user_id`) VALUES
('001/SK/PTUN-BJM/2024', '2024-01-17', 'Pengadilan Negeri Banjarmasin', 'Jawaban Permohonan Data Perkara', 'terkirim', 1),
('002/SK/PTUN-BJM/2024', '2024-01-22', 'Mahkamah Agung RI', 'Laporan Bulanan Januari 2024', 'terkirim', 1),
('003/SK/PTUN-BJM/2024', '2024-01-28', 'Pemkot Banjarmasin', 'Undangan Rapat Koordinasi', 'draft', 2);
