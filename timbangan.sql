-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 04, 2026 at 04:31 AM
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
-- Database: `timbangan`
--

-- --------------------------------------------------------

--
-- Table structure for table `data`
--

CREATE TABLE `data` (
  `id_data` int(11) NOT NULL,
  `isi_muatan` varchar(100) NOT NULL,
  `bruto` int(11) NOT NULL,
  `tara` int(11) NOT NULL,
  `netto` int(11) NOT NULL,
  `keterangan` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kendaraan`
--

CREATE TABLE `kendaraan` (
  `id_kendaraan` int(11) NOT NULL,
  `nopol` varchar(50) NOT NULL,
  `jenis_kendaraan` varchar(100) NOT NULL,
  `sopir` varchar(70) NOT NULL,
  `keterangan` varchar(70) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suplier`
--

CREATE TABLE `suplier` (
  `id_supplier` int(11) NOT NULL,
  `lokasi_asal` varchar(50) NOT NULL,
  `lokasi_Tujuan` varchar(50) NOT NULL,
  `keterangan` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_in`
--

CREATE TABLE `transaksi_in` (
  `id_in` int(11) NOT NULL,
  `jam_in` time NOT NULL,
  `tanggal_in` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_out`
--

CREATE TABLE `transaksi_out` (
  `id_out` int(11) NOT NULL,
  `jam_out` time NOT NULL,
  `tanggal_out` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `sebagai` varchar(90) NOT NULL,
  `username` varchar(80) NOT NULL,
  `password` varchar(70) NOT NULL,
  `foto` varchar(255) NOT NULL,
  `keterangan` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id_user`, `nama`, `sebagai`, `username`, `password`, `foto`, `keterangan`) VALUES
(1, 'Aji', 'Admin', 'aji123', '12345', 'aji.jpg', 'Aktif');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `data`
--
ALTER TABLE `data`
  ADD PRIMARY KEY (`id_data`);

--
-- Indexes for table `kendaraan`
--
ALTER TABLE `kendaraan`
  ADD PRIMARY KEY (`id_kendaraan`);

--
-- Indexes for table `suplier`
--
ALTER TABLE `suplier`
  ADD PRIMARY KEY (`id_supplier`);

--
-- Indexes for table `transaksi_in`
--
ALTER TABLE `transaksi_in`
  ADD PRIMARY KEY (`id_in`);

--
-- Indexes for table `transaksi_out`
--
ALTER TABLE `transaksi_out`
  ADD PRIMARY KEY (`id_out`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `nama` (`nama`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `data`
--
ALTER TABLE `data`
  MODIFY `id_data` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kendaraan`
--
ALTER TABLE `kendaraan`
  MODIFY `id_kendaraan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suplier`
--
ALTER TABLE `suplier`
  MODIFY `id_supplier` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaksi_in`
--
ALTER TABLE `transaksi_in`
  MODIFY `id_in` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaksi_out`
--
ALTER TABLE `transaksi_out`
  MODIFY `id_out` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
