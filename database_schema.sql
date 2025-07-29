-- Database Schema for WhatsApp Gateway with User Management and Payment

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    company VARCHAR(100),
    address TEXT,
    package_type ENUM('starter', 'business', 'enterprise') DEFAULT 'starter',
    status ENUM('pending', 'active', 'suspended', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    activated_at TIMESTAMP NULL,
    last_login TIMESTAMP NULL
);

-- User API Keys table
CREATE TABLE user_api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    api_key VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP NULL,
    last_used TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) UNIQUE NOT NULL,
    user_id INT,
    amount DECIMAL(10,2) NOT NULL,
    package_type ENUM('starter', 'business', 'enterprise') NOT NULL,
    snap_token VARCHAR(255),
    transaction_status VARCHAR(50),
    fraud_status VARCHAR(50),
    payment_type VARCHAR(50),
    signature_key VARCHAR(255),
    status ENUM('pending', 'success', 'failed', 'expired') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- User subscriptions table
CREATE TABLE user_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_type ENUM('starter', 'business', 'enterprise') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- User limits table (for tracking usage)
CREATE TABLE user_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_type ENUM('starter', 'business', 'enterprise') NOT NULL,
    max_devices INT NOT NULL,
    max_messages INT NOT NULL,
    used_messages INT DEFAULT 0,
    reset_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- User devices table (linked to existing nomor table)
CREATE TABLE user_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_id INT NOT NULL, -- References nomor.id
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (device_id) REFERENCES nomor(id) ON DELETE CASCADE
);

-- User activity logs
CREATE TABLE user_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Support tickets
CREATE TABLE support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    assigned_to INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Support ticket responses
CREATE TABLE ticket_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NULL, -- NULL for admin responses
    message TEXT NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Email templates
CREATE TABLE email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    variables JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Email logs
CREATE TABLE email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    template_id INT NULL,
    to_email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    error_message TEXT,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (template_id) REFERENCES email_templates(id) ON DELETE SET NULL
);

-- System settings
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Modify existing tables to support multi-tenant

-- Add user_id to autoreply table
ALTER TABLE autoreply ADD COLUMN user_id INT NULL;
ALTER TABLE autoreply ADD COLUMN device_nomor VARCHAR(20) NULL;
ALTER TABLE autoreply ADD COLUMN is_active BOOLEAN DEFAULT TRUE;
ALTER TABLE autoreply ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Add user_id to pesan table
ALTER TABLE pesan ADD COLUMN user_id INT NULL;
ALTER TABLE pesan ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Add user_id to receive_chat table
ALTER TABLE receive_chat ADD COLUMN user_id INT NULL;
ALTER TABLE receive_chat ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Add user_id to contacts table
ALTER TABLE contacts ADD COLUMN user_id INT NULL;
ALTER TABLE contacts ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Add user_id to blast table
ALTER TABLE blast ADD COLUMN user_id INT NULL;
ALTER TABLE blast ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description, is_public) VALUES
('site_name', 'WhatsApp Gateway', 'Nama website', TRUE),
('site_description', 'Solusi WhatsApp Gateway terbaik untuk bisnis Anda', 'Deskripsi website', TRUE),
('contact_email', 'support@wagateway.com', 'Email kontak support', TRUE),
('contact_phone', '+62 812-3456-7890', 'Nomor telepon kontak', TRUE),
('midtrans_client_key', 'YOUR-MIDTRANS-CLIENT-KEY', 'Midtrans Client Key', FALSE),
('midtrans_server_key', 'YOUR-MIDTRANS-SERVER-KEY', 'Midtrans Server Key', FALSE),
('midtrans_merchant_id', 'YOUR-MIDTRANS-MERCHANT-ID', 'Midtrans Merchant ID', FALSE),
('midtrans_production', 'false', 'Midtrans Production Mode', FALSE),
('package_starter_price', '99000', 'Harga paket starter', TRUE),
('package_business_price', '199000', 'Harga paket business', TRUE),
('package_enterprise_price', '499000', 'Harga paket enterprise', TRUE),
('trial_days', '7', 'Jumlah hari trial gratis', TRUE),
('max_login_attempts', '5', 'Maksimal percobaan login', FALSE),
('session_timeout', '3600', 'Timeout session dalam detik', FALSE);

-- Insert default email templates
INSERT INTO email_templates (name, subject, body, variables) VALUES
('welcome', 'Selamat Datang di WhatsApp Gateway!', 
'<h2>Selamat Datang di WhatsApp Gateway!</h2>
<p>Halo {{full_name}},</p>
<p>Terima kasih telah mendaftar di WhatsApp Gateway. Akun Anda telah berhasil diaktifkan.</p>
<p>Detail akun Anda:</p>
<ul>
    <li>Username: {{username}}</li>
    <li>Email: {{email}}</li>
    <li>Paket: {{package_type}}</li>
</ul>
<p>Anda sekarang dapat:</p>
<ul>
    <li>Login ke dashboard admin</li>
    <li>Setup device WhatsApp Anda</li>
    <li>Mulai kirim pesan otomatis</li>
    <li>Menggunakan fitur auto-reply</li>
</ul>
<p>Jika ada pertanyaan, silakan hubungi support kami.</p>
<p>Best regards,<br>Tim WhatsApp Gateway</p>',
'["full_name", "username", "email", "package_type"]'),

('payment_success', 'Pembayaran Berhasil - WhatsApp Gateway',
'<h2>Pembayaran Berhasil!</h2>
<p>Halo {{full_name}},</p>
<p>Pembayaran Anda untuk paket {{package_type}} telah berhasil diproses.</p>
<p>Detail pembayaran:</p>
<ul>
    <li>Order ID: {{order_id}}</li>
    <li>Jumlah: Rp {{amount}}</li>
    <li>Metode Pembayaran: {{payment_type}}</li>
    <li>Tanggal: {{payment_date}}</li>
</ul>
<p>Akun Anda telah diaktifkan dan siap digunakan.</p>
<p>Best regards,<br>Tim WhatsApp Gateway</p>',
'["full_name", "package_type", "order_id", "amount", "payment_type", "payment_date"]'),

('payment_failed', 'Pembayaran Gagal - WhatsApp Gateway',
'<h2>Pembayaran Gagal</h2>
<p>Halo {{full_name}},</p>
<p>Maaf, pembayaran Anda untuk paket {{package_type}} tidak dapat diproses.</p>
<p>Detail pembayaran:</p>
<ul>
    <li>Order ID: {{order_id}}</li>
    <li>Jumlah: Rp {{amount}}</li>
    <li>Alasan: {{reason}}</li>
</ul>
<p>Silakan coba lagi atau hubungi support kami untuk bantuan.</p>
<p>Best regards,<br>Tim WhatsApp Gateway</p>',
'["full_name", "package_type", "order_id", "amount", "reason"]'),

('subscription_expiring', 'Langganan Akan Berakhir - WhatsApp Gateway',
'<h2>Langganan Akan Berakhir</h2>
<p>Halo {{full_name}},</p>
<p>Langganan Anda untuk paket {{package_type}} akan berakhir pada {{expiry_date}}.</p>
<p>Untuk melanjutkan layanan, silakan perpanjang langganan Anda.</p>
<p>Best regards,<br>Tim WhatsApp Gateway</p>',
'["full_name", "package_type", "expiry_date"]');

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_user_api_keys_user_id ON user_api_keys(user_id);
CREATE INDEX idx_user_api_keys_api_key ON user_api_keys(api_key);
CREATE INDEX idx_payments_order_id ON payments(order_id);
CREATE INDEX idx_payments_user_id ON payments(user_id);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_user_subscriptions_user_id ON user_subscriptions(user_id);
CREATE INDEX idx_user_subscriptions_status ON user_subscriptions(status);
CREATE INDEX idx_user_limits_user_id ON user_limits(user_id);
CREATE INDEX idx_user_devices_user_id ON user_devices(user_id);
CREATE INDEX idx_activity_logs_user_id ON user_activity_logs(user_id);
CREATE INDEX idx_activity_logs_created_at ON user_activity_logs(created_at);
CREATE INDEX idx_support_tickets_user_id ON support_tickets(user_id);
CREATE INDEX idx_support_tickets_status ON support_tickets(status);
CREATE INDEX idx_email_logs_user_id ON email_logs(user_id);
CREATE INDEX idx_email_logs_status ON email_logs(status);

-- Multi-tenant indexes
CREATE INDEX idx_autoreply_user_id ON autoreply(user_id);
CREATE INDEX idx_pesan_user_id ON pesan(user_id);
CREATE INDEX idx_receive_chat_user_id ON receive_chat(user_id);
CREATE INDEX idx_contacts_user_id ON contacts(user_id);
CREATE INDEX idx_blast_user_id ON blast(user_id);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, full_name, email, package_type, status, activated_at) VALUES
('admin', SHA1('admin123'), 'Administrator', 'admin@wagateway.com', 'enterprise', 'active', NOW());

-- Insert default user limits for each package
INSERT INTO user_limits (user_id, package_type, max_devices, max_messages, reset_date) VALUES
(1, 'starter', 1, 1000, DATE_ADD(CURDATE(), INTERVAL 1 MONTH)),
(1, 'business', 3, 5000, DATE_ADD(CURDATE(), INTERVAL 1 MONTH)),
(1, 'enterprise', 10, 999999, DATE_ADD(CURDATE(), INTERVAL 1 MONTH)); 