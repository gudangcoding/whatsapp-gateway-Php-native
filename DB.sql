-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 12, 2021 at 07:47 PM
-- Server version: 5.7.35-cll-lve
-- PHP Version: 7.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `xdcom_WALITE`
--

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE `account` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `level` enum('1','2') NOT NULL DEFAULT '2' COMMENT '1 = ADMIN\r\n2 = CS'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `account`
--

INSERT INTO `account` (`id`, `username`, `password`, `level`) VALUES
(5, 'admin', '7c4a8d09ca3762af61e59520943dc26494f8941b', '1');

-- --------------------------------------------------------

--
-- Table structure for table `autoreply`
--

CREATE TABLE `autoreply` (
  `id` int(11) NOT NULL,
  `keyword` varchar(255) CHARACTER SET utf8 NOT NULL,
  `response` varchar(255) CHARACTER SET utf8 NOT NULL,
  `media` varchar(200) NOT NULL,
  `case_sensitive` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `blast`
--

CREATE TABLE `blast` (
  `id` int(11) NOT NULL,
  `nomor` text NOT NULL,
  `pesan` text NOT NULL,
  `media` varchar(255) DEFAULT NULL,
  `jadwal` datetime NOT NULL,
  `make_by` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `number` varchar(111) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('Personal','Group') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `google_form`
--

CREATE TABLE `google_form` (
  `id` int(11) NOT NULL,
  `form_id` varchar(255) NOT NULL,
  `form_name` varchar(255) NOT NULL,
  `target` varchar(255) NOT NULL,
  `pesan` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `google_form_pesan`
--

CREATE TABLE `google_form_pesan` (
  `id` int(11) NOT NULL,
  `id_pesan` varchar(255) NOT NULL,
  `nomor` varchar(255) NOT NULL,
  `pesan` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nomor`
--

CREATE TABLE `nomor` (
  `id` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `nomor` varchar(255) NOT NULL,
  `pesan` text NOT NULL,
  `make_by` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL,
  `chunk` int(11) NOT NULL,
  `hook_group` varchar(255) NOT NULL,
  `nomor` varchar(255) NOT NULL,
  `api_key` varchar(255) NOT NULL DEFAULT '310ea2abbaafe1844ac63f57ff20860b78e77c40',
  `callback` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `pesan`
--

CREATE TABLE `pesan` (
  `id` int(11) NOT NULL,
  `id_blast` varchar(255) NOT NULL,
  `nomor` varchar(255) NOT NULL,
  `pesan` text NOT NULL,
  `media` varchar(255) DEFAULT NULL,
  `status` enum('MENUNGGU JADWAL','GAGAL','TERKIRIM') NOT NULL DEFAULT 'MENUNGGU JADWAL',
  `jadwal` datetime NOT NULL,
  `tiap_bulan` enum('0','1') NOT NULL DEFAULT '0',
  `last_month` varchar(255) DEFAULT NULL,
  `make_by` varchar(255) DEFAULT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `receive_chat`
--

CREATE TABLE `receive_chat` (
  `id` int(11) NOT NULL,
  `id_pesan` varchar(200) NOT NULL,
  `nomor` varchar(255) NOT NULL,
  `pesan` text NOT NULL,
  `from_me` enum('0','1') NOT NULL DEFAULT '0',
  `nomor_saya` varchar(255) DEFAULT NULL,
  `tanggal` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `autoreply`
--
ALTER TABLE `autoreply`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blast`
--
ALTER TABLE `blast`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `google_form`
--
ALTER TABLE `google_form`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `google_form_pesan`
--
ALTER TABLE `google_form_pesan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nomor`
--
ALTER TABLE `nomor`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pesan`
--
ALTER TABLE `pesan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `receive_chat`
--
ALTER TABLE `receive_chat`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account`
--
ALTER TABLE `account`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `autoreply`
--
ALTER TABLE `autoreply`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `blast`
--
ALTER TABLE `blast`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `google_form`
--
ALTER TABLE `google_form`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `google_form_pesan`
--
ALTER TABLE `google_form_pesan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nomor`
--
ALTER TABLE `nomor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pesan`
--
ALTER TABLE `pesan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `receive_chat`
--
ALTER TABLE `receive_chat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
