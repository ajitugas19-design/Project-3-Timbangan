-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 23, 2026 at 03:10 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `penimbangan`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id_Customers` int(11) NOT NULL,
  `Customers` varchar(50) NOT NULL,
  `Keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id_Customers`, `Customers`, `Keterangan`) VALUES
(1, 'nafi', 'magang'),
(10, 'MIYA', 'STAF'),
(12, 'BIMA', 'HRDS');

-- --------------------------------------------------------

--
-- Table structure for table `kendaraan`
--

CREATE TABLE `kendaraan` (
  `id_Kendaraan` int(11) NOT NULL,
  `Nopol` varchar(50) NOT NULL,
  `Sopir` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kendaraan`
--

INSERT INTO `kendaraan` (`id_Kendaraan`, `Nopol`, `Sopir`) VALUES
(1, 'AB123CDE', 'ABI'),
(4, 'QWE098LKJ', 'ARIF');

-- --------------------------------------------------------

--
-- Table structure for table `material`
--

CREATE TABLE `material` (
  `id_Material` int(11) NOT NULL,
  `Kode` varchar(50) NOT NULL,
  `Material` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `material`
--

INSERT INTO `material` (`id_Material`, `Kode`, `Material`) VALUES
(1, 'XYZ', 'OBSIDIAN'),
(3, 'ABC', 'BERLIAN');

-- --------------------------------------------------------

--
-- Table structure for table `scale_logs`
--

CREATE TABLE `scale_logs` (
  `id` int(11) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `raw_data` varchar(255) DEFAULT NULL,
  `parsed_weight` decimal(10,2) DEFAULT NULL,
  `status` enum('success','error','timeout') DEFAULT 'success',
  `device_port` varchar(20) DEFAULT 'COM3',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scale_logs`
--

INSERT INTO `scale_logs` (`id`, `timestamp`, `raw_data`, `parsed_weight`, `status`, `device_port`, `created_at`) VALUES
(1, '2026-04-23 13:56:01', '123.45kg', 123.45, 'success', 'COM3', '2026-04-23 06:56:01');

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `id_Supplier` int(11) NOT NULL,
  `Nama_Supplier` varchar(50) NOT NULL,
  `Lokasi_Asal` varchar(50) NOT NULL,
  `Lokasi_Tujuan` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`id_Supplier`, `Nama_Supplier`, `Lokasi_Asal`, `Lokasi_Tujuan`) VALUES
(1, 'PT.SEJAHTERA', 'GRESIK', ''),
(9, 'PT INDAH JAYA ABADI', 'SULAWESI', '');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int(11) NOT NULL,
  `no_record` varchar(50) NOT NULL,
  `id_kendaraan` int(11) DEFAULT NULL,
  `id_supplier` int(11) DEFAULT NULL,
  `id_material` int(11) DEFAULT NULL,
  `id_customers` int(11) DEFAULT NULL,
  `id_in` int(11) NOT NULL,
  `id_out` int(11) NOT NULL,
  `bruto` decimal(10,2) DEFAULT NULL,
  `tara` decimal(10,2) DEFAULT NULL,
  `netto` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id_transaksi`, `no_record`, `id_kendaraan`, `id_supplier`, `id_material`, `id_customers`, `id_in`, `id_out`, `bruto`, `tara`, `netto`) VALUES
(8, 'TRAN202604230001', 4, 9, 3, 12, 6, 6, 23.00, 2323.00, -2300.00),
(9, 'TRAN202604230002', 1, 9, 3, 12, 7, 7, 311.00, 113.00, 198.00);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `user` varchar(100) NOT NULL DEFAULT '',
  `sebagai` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id_user`, `nama`, `user`, `sebagai`, `password`, `foto`, `keterangan`) VALUES
(1, 'Ahmat Panjau aji', 'aji', 'Admin', '827ccb0eea8a706c4c34a16891f84e7b', 'uploads/1776486925_6181421072931470825.jpg', 'User Aji'),
(10, 'Aji Ganteng', 'Aji Ganteng Sekali', 'manajer', '$2y$10$G0qL9S2As36klhgse4CdPeaMjBBZvVpoenlPINbWAuuBogwU9MuNK', '1776932808_WhatsApp Image 2025-11-29 at 04.47.43_ea474406.jpg', '');

-- --------------------------------------------------------

--
-- Table structure for table `waktu_in`
--

CREATE TABLE `waktu_in` (
  `id_in` int(11) NOT NULL,
  `jam_in` time NOT NULL,
  `tanggal_in` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `waktu_in`
--

INSERT INTO `waktu_in` (`id_in`, `jam_in`, `tanggal_in`) VALUES
(1, '24:07:46', '2026-04-15'),
(2, '12:22:00', '2026-04-18'),
(3, '11:11:00', '2026-04-18'),
(4, '11:11:00', '2026-04-18'),
(5, '11:11:00', '2026-04-18'),
(6, '12:03:00', '2026-04-23'),
(7, '22:22:00', '2026-04-23'),
(8, '11:11:00', '2026-04-23'),
(9, '11:11:00', '2026-04-23'),
(10, '11:11:00', '2026-04-23'),
(11, '11:11:00', '2026-04-23'),
(12, '11:11:00', '2026-04-23'),
(13, '11:11:00', '2026-04-23'),
(14, '00:00:00', '2026-04-23'),
(15, '03:12:00', '2026-04-23'),
(16, '11:11:00', '2026-04-23'),
(17, '11:11:00', '2026-04-23'),
(18, '11:11:00', '2026-04-23'),
(19, '00:00:00', '2026-04-23'),
(20, '00:00:00', '2026-04-23'),
(21, '00:00:00', '2026-04-23'),
(22, '11:11:00', '2026-04-23'),
(23, '11:11:00', '2026-04-23'),
(24, '11:11:00', '2026-04-23'),
(25, '11:11:00', '2026-04-23'),
(26, '11:11:00', '2026-04-23'),
(27, '00:00:00', '2026-04-23'),
(28, '00:00:00', '2026-04-23'),
(29, '00:00:00', '2026-04-23'),
(30, '00:00:00', '2026-04-23'),
(31, '00:00:00', '2026-04-23'),
(32, '00:00:00', '2026-04-23'),
(33, '00:00:00', '2026-04-23'),
(34, '00:00:00', '2026-04-23'),
(35, '00:00:00', '2026-04-23'),
(36, '00:00:00', '2026-04-23'),
(37, '00:00:00', '2026-04-23'),
(38, '00:00:00', '2026-04-23'),
(39, '00:00:00', '2026-04-23'),
(40, '11:01:00', '2026-04-23'),
(41, '00:00:00', '2026-04-23'),
(42, '00:00:00', '2026-04-23'),
(43, '00:00:00', '2026-04-23'),
(44, '00:00:00', '2026-04-23'),
(45, '00:00:00', '2026-04-23'),
(46, '00:00:00', '2026-04-23'),
(47, '00:00:00', '2026-04-23');

-- --------------------------------------------------------

--
-- Table structure for table `waktu_out`
--

CREATE TABLE `waktu_out` (
  `id_out` int(11) NOT NULL,
  `jam_out` time NOT NULL,
  `tanggal_out` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `waktu_out`
--

INSERT INTO `waktu_out` (`id_out`, `jam_out`, `tanggal_out`) VALUES
(1, '11:08:24', '2026-04-08'),
(2, '12:11:00', '2026-04-18'),
(3, '11:11:00', '2026-04-18'),
(4, '03:33:00', '2026-04-18'),
(5, '22:22:00', '2026-04-19'),
(6, '23:03:00', '2026-04-24'),
(7, '11:11:00', '2026-04-24'),
(8, '22:22:00', '2026-04-24'),
(9, '22:22:00', '2026-04-24'),
(10, '22:22:00', '2026-04-24'),
(11, '22:22:00', '2026-04-24'),
(12, '22:22:00', '2026-04-24'),
(13, '22:22:00', '2026-04-24'),
(14, '00:00:00', '2026-04-24'),
(15, '11:02:00', '2026-04-24'),
(16, '11:11:00', '2026-04-24'),
(17, '11:01:00', '2026-04-24'),
(18, '11:01:00', '2026-04-24'),
(19, '00:00:00', '2026-04-24'),
(20, '00:00:00', '2026-04-24'),
(21, '00:00:00', '2026-04-24'),
(22, '11:01:00', '2026-04-24'),
(23, '11:01:00', '2026-04-24'),
(24, '11:01:00', '2026-04-24'),
(25, '11:01:00', '2026-04-24'),
(26, '11:11:00', '2026-04-24'),
(27, '00:00:00', '2026-04-24'),
(28, '00:00:00', '2026-04-24'),
(29, '00:00:00', '2026-04-24'),
(30, '00:00:00', '2026-04-24'),
(31, '00:00:00', '2026-04-24'),
(32, '00:00:00', '2026-04-24'),
(33, '00:00:00', '2026-04-24'),
(34, '00:00:00', '2026-04-24'),
(35, '00:00:00', '2026-04-24'),
(36, '00:00:00', '2026-04-24'),
(37, '00:00:00', '2026-04-24'),
(38, '00:00:00', '2026-04-24'),
(39, '00:00:00', '2026-04-24'),
(40, '11:01:00', '2026-04-24'),
(41, '00:00:00', '2026-04-24'),
(42, '00:00:00', '2026-04-24'),
(43, '00:00:00', '2026-04-24'),
(44, '00:00:00', '2026-04-24'),
(45, '00:00:00', '2026-04-24'),
(46, '00:00:00', '2026-04-24'),
(47, '00:00:00', '2026-04-24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id_Customers`);

--
-- Indexes for table `kendaraan`
--
ALTER TABLE `kendaraan`
  ADD PRIMARY KEY (`id_Kendaraan`);

--
-- Indexes for table `material`
--
ALTER TABLE `material`
  ADD PRIMARY KEY (`id_Material`);

--
-- Indexes for table `scale_logs`
--
ALTER TABLE `scale_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`id_Supplier`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_kendaraan` (`id_kendaraan`),
  ADD KEY `id_supplier` (`id_supplier`),
  ADD KEY `id_material` (`id_material`),
  ADD KEY `id_customers` (`id_customers`),
  ADD KEY `id_in` (`id_in`),
  ADD KEY `id_out` (`id_out`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `nama` (`nama`),
  ADD UNIQUE KEY `idx_user` (`user`);

--
-- Indexes for table `waktu_in`
--
ALTER TABLE `waktu_in`
  ADD PRIMARY KEY (`id_in`);

--
-- Indexes for table `waktu_out`
--
ALTER TABLE `waktu_out`
  ADD PRIMARY KEY (`id_out`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id_Customers` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `kendaraan`
--
ALTER TABLE `kendaraan`
  MODIFY `id_Kendaraan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `material`
--
ALTER TABLE `material`
  MODIFY `id_Material` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `scale_logs`
--
ALTER TABLE `scale_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `id_Supplier` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `waktu_in`
--
ALTER TABLE `waktu_in`
  MODIFY `id_in` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `waktu_out`
--
ALTER TABLE `waktu_out`
  MODIFY `id_out` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_kendaraan`) REFERENCES `kendaraan` (`id_Kendaraan`),
  ADD CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_Supplier`),
  ADD CONSTRAINT `transaksi_ibfk_3` FOREIGN KEY (`id_material`) REFERENCES `material` (`id_Material`),
  ADD CONSTRAINT `transaksi_ibfk_4` FOREIGN KEY (`id_customers`) REFERENCES `customers` (`id_Customers`),
  ADD CONSTRAINT `transaksi_ibfk_5` FOREIGN KEY (`id_in`) REFERENCES `waktu_in` (`id_in`),
  ADD CONSTRAINT `transaksi_ibfk_6` FOREIGN KEY (`id_out`) REFERENCES `waktu_out` (`id_out`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
