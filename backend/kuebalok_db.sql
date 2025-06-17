-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 17, 2025 at 08:06 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kuebalok_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id_detail` bigint NOT NULL,
  `id_pesanan` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_produk` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jumlah` int NOT NULL,
  `harga_saat_transaksi` decimal(12,2) NOT NULL,
  `sub_total` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id_feedback` int NOT NULL,
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pesan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id_feedback`, `nama`, `email`, `pesan`, `tanggal`) VALUES
(1, 'dimas', 'dimas@gmail.com', 'gdvjdfghsrfdhsdfhr', '2025-06-15 09:51:20'),
(2, 'Karin', 'karin@gmail.com', 'Saran toping nya lebih banyak lagii', '2025-06-15 12:08:59');

-- --------------------------------------------------------

--
-- Table structure for table `karyawan`
--

CREATE TABLE `karyawan` (
  `id_karyawan` int NOT NULL,
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jabatan` enum('owner','admin','kasir') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `no_telepon` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `karyawan`
--

INSERT INTO `karyawan` (`id_karyawan`, `nama`, `username`, `password`, `jabatan`, `no_telepon`, `email`, `dibuat_pada`) VALUES
(1, 'Mang Wiro (Owner)', 'owner', '$2y$10$vlv298Xy.2SoBAIAEmtF0e3UBUdJnzhxphCaYIbj3pnm37Hk50Ywe', 'owner', '081122334455', 'owner@mangwiro.com', '2025-06-15 09:45:30'),
(2, 'Admin Utama', 'admin', '$2y$10$wI5zVXAiP/Bmfl7rK8HQ9OHfsosoLWl0WO71vXO9zI0TIaLtf.uCW', 'admin', '081234567890', 'admin@mangwiro.com', '2025-06-15 09:45:30'),
(3, 'Kasir Pagi', 'kasir', '$2y$10$f.mx05TXF2rviTb/MM4tV.D54cHgi/SreDa1w0Rz91wqD8UANaXCm', 'kasir', '089876543210', 'kasir@mangwiro.com', '2025-06-15 09:45:30');

-- --------------------------------------------------------

--
-- Table structure for table `kategori_stok`
--

CREATE TABLE `kategori_stok` (
  `id_kategori_stok` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama_kategori` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori_stok`
--

INSERT INTO `kategori_stok` (`id_kategori_stok`, `nama_kategori`) VALUES
('KAT-KB', 'Stok Adonan Kue Balok'),
('KAT-KS', 'Stok Adonan Ketan Susu');

-- --------------------------------------------------------

--
-- Table structure for table `log_stok`
--

CREATE TABLE `log_stok` (
  `id_log` int NOT NULL,
  `id_produk` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_kategori_stok` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jumlah_perubahan` int NOT NULL COMMENT 'Positif untuk penambahan, negatif untuk pengurangan',
  `jenis_aksi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_pesanan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `waktu_log` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `metode_pembayaran`
--

CREATE TABLE `metode_pembayaran` (
  `id_metode` int NOT NULL,
  `nama_metode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `atas_nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nomor_tujuan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `gambar_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('aktif','tidak_aktif') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `metode_pembayaran`
--

INSERT INTO `metode_pembayaran` (`id_metode`, `nama_metode`, `atas_nama`, `nomor_tujuan`, `gambar_path`, `status`) VALUES
(1, 'QRIS', 'PT Kue Balok Sejahtera', 'ID123456789QRIS', 'qris.png', 'aktif'),
(2, 'DANA', 'Mang Wiro', '085888788712', NULL, 'aktif'),
(3, 'Transfer BCA', 'Mang Wiro', '1234567890', NULL, 'aktif');

-- --------------------------------------------------------

--
-- Table structure for table `pesanan`
--

CREATE TABLE `pesanan` (
  `id_pesanan` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `midtrans_transaction_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `midtrans_payment_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `id_karyawan` int DEFAULT NULL,
  `tipe_pesanan` enum('kasir','online') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jenis_pesanan` enum('dine_in','take_away') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'dine_in',
  `nama_pemesan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Walk-in',
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pelanggan@example.com',
  `no_telepon` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tgl_pesanan` datetime NOT NULL,
  `total_harga` decimal(12,2) NOT NULL DEFAULT '0.00',
  `metode_pembayaran` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_pesanan` enum('menunggu_pembayaran','menunggu_konfirmasi','pending','diproses','siap_diambil','selesai','dibatalkan') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `bukti_pembayaran` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `id_produk` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama_produk` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `kategori` enum('makanan','minuman') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_kategori_stok` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `harga` decimal(12,2) NOT NULL DEFAULT '0.00',
  `poto_produk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'default.jpg',
  `status_produk` enum('aktif','tidak aktif') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'aktif',
  `dibuat_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`id_produk`, `nama_produk`, `kategori`, `id_kategori_stok`, `harga`, `poto_produk`, `status_produk`, `dibuat_pada`) VALUES
('KB001', 'Kue Balok Original ', 'makanan', NULL, 3000.00, 'kb-ori.jpg', 'aktif', '2025-06-17 07:53:00'),
('KB002', 'Kue Balok Coklat ', 'makanan', NULL, 3000.00, '13.jpg', 'aktif', '2025-06-17 07:53:46'),
('KB003', 'Kue Balok Coklat Keju', 'makanan', NULL, 3500.00, 'kb-mix.jpg', 'aktif', '2025-06-17 07:54:31'),
('KB004', 'Kue Balok Keju', 'makanan', NULL, 3000.00, '11.jpg', 'aktif', '2025-06-17 07:55:18'),
('KB005', 'Kue Balok Full Coklat', 'makanan', NULL, 4000.00, 'kb-coklat.jpg', 'aktif', '2025-06-17 07:55:58'),
('KB006', 'Kue Balok Greentea', 'makanan', NULL, 3500.00, 'kb-macha.jpg', 'aktif', '2025-06-17 07:56:35'),
('KB007', 'Kue Balok Tiramisu', 'makanan', NULL, 3500.00, '8.jpg', 'aktif', '2025-06-17 07:57:08'),
('KB008', 'Kue Balok Red Velvet ', 'makanan', NULL, 4000.00, '12.jpg', 'aktif', '2025-06-17 07:57:38'),
('KB009', 'Kue Balok Taro', 'makanan', NULL, 3000.00, '15.jpg', 'aktif', '2025-06-17 07:58:08'),
('KS001', 'Ketan Susu Coklat', 'makanan', NULL, 13000.00, '5.jpg', 'aktif', '2025-06-17 07:44:31'),
('KS002', 'Ketan Susu  Keju', 'makanan', NULL, 13000.00, '6.jpg', 'aktif', '2025-06-17 07:45:12'),
('KS003', 'Ketan Susu Red Velvet', 'makanan', NULL, 13000.00, '3.jpg', 'aktif', '2025-06-17 07:46:03'),
('KS004', 'Kue Balok Oreo Coklat', 'makanan', NULL, 16000.00, '2.jpg', 'aktif', '2025-06-17 07:46:50'),
('KS005', 'Ketan Susu Red Velvet Keju', 'makanan', NULL, 16000.00, '7.jpg', 'aktif', '2025-06-17 07:49:17'),
('KS006', 'Ketan Susu Choco Chushcy', 'makanan', NULL, 13000.00, 'ks-coklat.jpg', 'aktif', '2025-06-17 07:50:21'),
('KS007', 'Ketan Susu Coklat Keju', 'makanan', NULL, 16000.00, 'ks-kejucoklat.jpg', 'aktif', '2025-06-17 07:52:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_pesanan` (`id_pesanan`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id_feedback`);

--
-- Indexes for table `karyawan`
--
ALTER TABLE `karyawan`
  ADD PRIMARY KEY (`id_karyawan`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `kategori_stok`
--
ALTER TABLE `kategori_stok`
  ADD PRIMARY KEY (`id_kategori_stok`);

--
-- Indexes for table `log_stok`
--
ALTER TABLE `log_stok`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_produk` (`id_produk`),
  ADD KEY `id_kategori_stok` (`id_kategori_stok`);

--
-- Indexes for table `metode_pembayaran`
--
ALTER TABLE `metode_pembayaran`
  ADD PRIMARY KEY (`id_metode`);

--
-- Indexes for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD KEY `id_karyawan` (`id_karyawan`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id_produk`),
  ADD KEY `id_kategori_stok` (`id_kategori_stok`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id_detail` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id_feedback` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `karyawan`
--
ALTER TABLE `karyawan`
  MODIFY `id_karyawan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `log_stok`
--
ALTER TABLE `log_stok`
  MODIFY `id_log` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `metode_pembayaran`
--
ALTER TABLE `metode_pembayaran`
  MODIFY `id_metode` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `fk_detail_pesanan_order` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detail_pesanan_produk` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `log_stok`
--
ALTER TABLE `log_stok`
  ADD CONSTRAINT `fk_log_stok_kategori` FOREIGN KEY (`id_kategori_stok`) REFERENCES `kategori_stok` (`id_kategori_stok`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_log_stok_produk` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON DELETE CASCADE;

--
-- Constraints for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `fk_pesanan_karyawan` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan` (`id_karyawan`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `fk_produk_kategori_stok` FOREIGN KEY (`id_kategori_stok`) REFERENCES `kategori_stok` (`id_kategori_stok`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
