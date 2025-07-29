-- Database: wa-gateway
-- Multi-tenant WhatsApp Gateway Database Schema

-- Drop database if exists (optional)
-- DROP DATABASE IF EXISTS `wa-gateway`;

-- Create database
CREATE DATABASE IF NOT EXISTS `wa-gateway` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `wa-gateway`;

-- Table: users (multi-tenant users)
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `phone` varchar(20) DEFAULT NULL,
  `package_type` enum('basic','premium','enterprise') DEFAULT 'basic',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: user_api_keys (API keys untuk setiap user)
CREATE TABLE `user_api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `api_key` varchar(255) NOT NULL UNIQUE,
  `name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_api_key` (`api_key`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: user_limits (limit penggunaan untuk setiap user)
CREATE TABLE `user_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `max_devices` int(11) DEFAULT 1,
  `max_messages` int(11) DEFAULT 1000,
  `max_auto_replies` int(11) DEFAULT 10,
  `max_scheduled_messages` int(11) DEFAULT 50,
  `used_messages` int(11) DEFAULT 0,
  `reset_date` date DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: nomor (devices/nomor WhatsApp)
CREATE TABLE `nomor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nomor` varchar(20) NOT NULL UNIQUE,
  `nama` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nomor` (`nomor`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: user_devices (relasi user dengan device)
CREATE TABLE `user_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_device` (`user_id`, `device_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_device_id` (`device_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`device_id`) REFERENCES `nomor` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: receive_chat (riwayat pesan masuk dan keluar)
CREATE TABLE `receive_chat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_pesan` varchar(100) DEFAULT NULL,
  `nomor` varchar(20) NOT NULL,
  `pesan` text NOT NULL,
  `from_me` enum('0','1') DEFAULT '0',
  `nomor_saya` varchar(20) DEFAULT NULL,
  `tanggal` timestamp DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) DEFAULT NULL,
  `message_type` varchar(20) DEFAULT 'text',
  `media_url` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_nomor` (`nomor`),
  KEY `idx_tanggal` (`tanggal`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_from_me` (`from_me`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: autoreply (auto reply berdasarkan keyword)
CREATE TABLE `autoreply` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `device_nomor` varchar(20) NOT NULL,
  `keyword` varchar(255) NOT NULL,
  `response` text NOT NULL,
  `case_sensitive` enum('0','1') DEFAULT '0',
  `is_active` tinyint(1) DEFAULT 1,
  `media` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_device_nomor` (`device_nomor`),
  KEY `idx_is_active` (`is_active`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: pesan (pesan terjadwal)
CREATE TABLE `pesan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `nomor` varchar(20) NOT NULL,
  `pesan` text NOT NULL,
  `jadwal` datetime NOT NULL,
  `status` enum('MENUNGGU JADWAL','TERKIRIM','GAGAL') DEFAULT 'MENUNGGU JADWAL',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_nomor` (`nomor`),
  KEY `idx_jadwal` (`jadwal`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: user_activity_logs (log aktivitas user)
CREATE TABLE `user_activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: webhook_logs (log webhook untuk integrasi)
CREATE TABLE `webhook_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `device_nomor` varchar(20) DEFAULT NULL,
  `webhook_url` text NOT NULL,
  `payload` text NOT NULL,
  `response_code` int(11) DEFAULT NULL,
  `response_body` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_device_nomor` (`device_nomor`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user
INSERT INTO `users` (`username`, `password`, `full_name`, `email`, `package_type`, `status`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@wa-gateway.com', 'enterprise', 'active');

-- Insert default API key for admin
INSERT INTO `user_api_keys` (`user_id`, `api_key`, `name`) VALUES
(1, 'admin-api-key-123456789', 'Default Admin API Key');

-- Insert default limits for admin
INSERT INTO `user_limits` (`user_id`, `max_devices`, `max_messages`, `max_auto_replies`, `max_scheduled_messages`) VALUES
(1, 100, 100000, 1000, 10000);

-- Insert sample devices (optional)
INSERT INTO `nomor` (`nomor`, `nama`, `status`) VALUES
('6281381830651', 'Device Admin 1', 'active'),
('6281283804283', 'Device Admin 2', 'active');

-- Assign devices to admin
INSERT INTO `user_devices` (`user_id`, `device_id`, `status`) VALUES
(1, 1, 'active'),
(1, 2, 'active');

-- Create indexes for better performance
CREATE INDEX `idx_receive_chat_user_date` ON `receive_chat` (`user_id`, `tanggal`);
CREATE INDEX `idx_autoreply_device_active` ON `autoreply` (`device_nomor`, `is_active`);
CREATE INDEX `idx_pesan_user_status` ON `pesan` (`user_id`, `status`);
CREATE INDEX `idx_activity_logs_user_date` ON `user_activity_logs` (`user_id`, `created_at`);

-- Add comments for documentation
ALTER TABLE `users` COMMENT = 'Multi-tenant users table';
ALTER TABLE `user_api_keys` COMMENT = 'API keys for each user';
ALTER TABLE `user_limits` COMMENT = 'Usage limits for each user';
ALTER TABLE `nomor` COMMENT = 'WhatsApp devices/numbers';
ALTER TABLE `user_devices` COMMENT = 'User-device relationships';
ALTER TABLE `receive_chat` COMMENT = 'Message history (incoming/outgoing)';
ALTER TABLE `autoreply` COMMENT = 'Auto reply rules';
ALTER TABLE `pesan` COMMENT = 'Scheduled messages';
ALTER TABLE `user_activity_logs` COMMENT = 'User activity tracking';
ALTER TABLE `webhook_logs` COMMENT = 'Webhook integration logs';
