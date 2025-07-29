# Midtrans Payment Gateway Setup

## 🔧 **Konfigurasi Midtrans**

### **1. Daftar Akun Midtrans**
- Kunjungi [Midtrans Dashboard](https://dashboard.midtrans.com)
- Daftar akun baru atau login jika sudah punya
- Dapatkan Server Key dan Client Key dari dashboard

### **2. Update File Konfigurasi**
Edit file `config/midtrans.php` dan ganti dengan credentials Anda:

```php
// Sandbox (Testing) Environment
define('MIDTRANS_SERVER_KEY_SANDBOX', 'YOUR-ACTUAL-SERVER-KEY-SANDBOX');
define('MIDTRANS_CLIENT_KEY_SANDBOX', 'YOUR-ACTUAL-CLIENT-KEY-SANDBOX');
define('MIDTRANS_MERCHANT_ID_SANDBOX', 'YOUR-ACTUAL-MERCHANT-ID-SANDBOX');

// Production Environment
define('MIDTRANS_SERVER_KEY_PRODUCTION', 'YOUR-ACTUAL-SERVER-KEY-PRODUCTION');
define('MIDTRANS_CLIENT_KEY_PRODUCTION', 'YOUR-ACTUAL-CLIENT-KEY-PRODUCTION');
define('MIDTRANS_MERCHANT_ID_PRODUCTION', 'YOUR-ACTUAL-MERCHANT-ID-PRODUCTION');

// Set ke true untuk production, false untuk sandbox
define('MIDTRANS_PRODUCTION', false);
```

### **3. Mode Demo (Tanpa Midtrans)**
Jika Anda belum punya akun Midtrans atau ingin testing dulu:

1. **Biarkan konfigurasi default** (dengan `YOUR-MIDTRANS-SERVER-KEY-SANDBOX`)
2. **Sistem akan berjalan dalam mode demo**
3. **Upgrade subscription akan langsung berhasil** tanpa payment gateway
4. **Cocok untuk development dan testing**

### **4. Testing Payment Flow**

#### **Mode Demo:**
- Klik "Upgrade" di halaman subscription
- Sistem akan langsung upgrade tanpa payment
- Muncul pesan "Demo payment successful"

#### **Mode Production:**
- Klik "Upgrade" di halaman subscription
- Redirect ke halaman payment Midtrans
- User melakukan pembayaran
- Setelah berhasil, subscription diupgrade

### **5. Environment Variables (Opsional)**
Alternatifnya, Anda bisa set environment variables:

```bash
# Di file .env atau environment
MIDTRANS_SERVER_KEY=your_server_key
MIDTRANS_CLIENT_KEY=your_client_key
MIDTRANS_MERCHANT_ID=your_merchant_id
```

### **6. Troubleshooting**

#### **Error: "Failed to generate Snap token"**
- Pastikan Server Key sudah benar
- Cek koneksi internet
- Pastikan URL callback sudah benar

#### **Error: "Invalid signature"**
- Pastikan Server Key untuk signature verification sudah benar
- Cek format data yang dikirim

#### **Demo Mode Tidak Berfungsi**
- Pastikan file `config/midtrans.php` sudah dibuat
- Cek apakah `MIDTRANS_DEMO_MODE` bernilai `true`
- Pastikan database migration sudah dijalankan

### **7. Logs dan Debugging**
- Cek error logs di `error_log` PHP
- Midtrans response akan di-log untuk debugging
- Gunakan browser developer tools untuk cek network requests

### **8. Production Checklist**
Sebelum deploy ke production:

- [ ] Ganti ke production keys
- [ ] Set `MIDTRANS_PRODUCTION = true`
- [ ] Update callback URLs
- [ ] Test payment flow end-to-end
- [ ] Setup webhook notification
- [ ] Monitor payment logs

## 🎯 **Quick Start**

1. **Jalankan database migration:**
   ```sql
   source database_migration.sql
   ```

2. **Test mode demo:**
   - Buka halaman subscription
   - Klik upgrade package
   - Lihat upgrade berhasil tanpa payment

3. **Setup Midtrans (opsional):**
   - Update `config/midtrans.php`
   - Test payment flow

## 📞 **Support**

Jika ada masalah dengan setup Midtrans:
- Dokumentasi: [Midtrans Docs](https://docs.midtrans.com)
- Support: support@midtrans.com
- WhatsApp: +62 812-3456-7890 