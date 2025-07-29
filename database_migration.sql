-- Database Migration for Subscription System
-- Add missing tables for subscription management

-- Add user_subscriptions table if not exists
CREATE TABLE IF NOT EXISTS `user_subscriptions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `package_type` ENUM('starter', 'business', 'enterprise') NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `status` ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add user_limits table if not exists
CREATE TABLE IF NOT EXISTS `user_limits` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `package_type` ENUM('starter', 'business', 'enterprise') NOT NULL,
    `max_devices` INT NOT NULL,
    `max_messages` INT NOT NULL,
    `used_messages` INT DEFAULT 0,
    `reset_date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add payments table if not exists
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` VARCHAR(50) UNIQUE NOT NULL,
    `user_id` INT,
    `amount` DECIMAL(10,2) NOT NULL,
    `package_type` ENUM('starter', 'business', 'enterprise') NOT NULL,
    `snap_token` VARCHAR(255),
    `transaction_status` VARCHAR(50),
    `fraud_status` VARCHAR(50),
    `payment_type` VARCHAR(50),
    `payment_method` VARCHAR(50),
    `signature_key` VARCHAR(255),
    `status` ENUM('pending', 'success', 'failed', 'expired') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_user_subscriptions_user_id` ON `user_subscriptions` (`user_id`);
CREATE INDEX IF NOT EXISTS `idx_user_subscriptions_status` ON `user_subscriptions` (`status`);
CREATE INDEX IF NOT EXISTS `idx_user_limits_user_id` ON `user_limits` (`user_id`);
CREATE INDEX IF NOT EXISTS `idx_payments_order_id` ON `payments` (`order_id`);
CREATE INDEX IF NOT EXISTS `idx_payments_user_id` ON `payments` (`user_id`);
CREATE INDEX IF NOT EXISTS `idx_payments_status` ON `payments` (`status`);

-- Add activated_at column to users table if not exists
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `activated_at` TIMESTAMP NULL AFTER `updated_at`;

-- Insert default limits for existing users (if they don't have any)
INSERT IGNORE INTO `user_limits` (`user_id`, `package_type`, `max_devices`, `max_messages`, `used_messages`, `reset_date`)
SELECT 
    `id` as `user_id`,
    COALESCE(`package_type`, 'starter') as `package_type`,
    CASE 
        WHEN COALESCE(`package_type`, 'starter') = 'starter' THEN 1
        WHEN COALESCE(`package_type`, 'starter') = 'business' THEN 5
        WHEN COALESCE(`package_type`, 'starter') = 'enterprise' THEN -1
        ELSE 1
    END as `max_devices`,
    CASE 
        WHEN COALESCE(`package_type`, 'starter') = 'starter' THEN 1000
        WHEN COALESCE(`package_type`, 'starter') = 'business' THEN 10000
        WHEN COALESCE(`package_type`, 'starter') = 'enterprise' THEN -1
        ELSE 1000
    END as `max_messages`,
    0 as `used_messages`,
    CURDATE() as `reset_date`
FROM `users`
WHERE `id` NOT IN (SELECT DISTINCT `user_id` FROM `user_limits`);

-- Create default subscriptions for existing users (if they don't have any)
INSERT IGNORE INTO `user_subscriptions` (`user_id`, `package_type`, `start_date`, `end_date`, `status`)
SELECT 
    `id` as `user_id`,
    COALESCE(`package_type`, 'starter') as `package_type`,
    CURDATE() as `start_date`,
    DATE_ADD(CURDATE(), INTERVAL 1 MONTH) as `end_date`,
    'active' as `status`
FROM `users`
WHERE `id` NOT IN (SELECT DISTINCT `user_id` FROM `user_subscriptions`);

-- Update users table to ensure package_type column exists
ALTER TABLE `users` MODIFY COLUMN `package_type` ENUM('starter', 'business', 'enterprise') DEFAULT 'starter';

-- Set default package_type for users who don't have one
UPDATE `users` SET `package_type` = 'starter' WHERE `package_type` IS NULL OR `package_type` = ''; 