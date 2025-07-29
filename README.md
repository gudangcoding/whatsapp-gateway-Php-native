# WhatsApp Gateway Multi-Tenant

Sistem WhatsApp Gateway dengan fitur multi-tenant yang memungkinkan multiple user menggunakan platform yang sama dengan isolasi data yang aman.

## 🚀 Fitur Utama

- **Multi-tenant Architecture**: Setiap user memiliki isolasi data yang aman
- **API Key Authentication**: Sistem autentikasi berbasis API key
- **Device Management**: Manajemen multiple WhatsApp device per user
- **Message History**: Riwayat lengkap pesan masuk dan keluar
- **Auto Reply**: Sistem auto reply berdasarkan keyword
- **Scheduled Messages**: Pesan terjadwal dengan cron job
- **Real-time QR Code**: QR code real-time untuk koneksi WhatsApp
- **Usage Limits**: Pembatasan penggunaan berdasarkan package
- **Activity Logging**: Log aktivitas user untuk monitoring

## 📁 Struktur Folder

```
wa-gateway/
├── config/
│   └── database.js          # Konfigurasi database
├── middleware/
│   └── auth.js              # Middleware autentikasi
├── services/
│   ├── whatsapp.js          # Service WhatsApp connection
│   └── messageService.js    # Service pesan dan auto reply
├── routes/
│   └── api.js               # API endpoints
├── history/
│   ├── index.php            # Halaman riwayat pesan
│   ├── get_message_details.php
│   └── export_csv.php
├── module/
│   ├── home.php             # Dashboard utama
│   ├── kirim-pesan.php      # Halaman kirim pesan
│   ├── auto-reply.php       # Halaman auto reply
│   └── pesan-terjadwal.php  # Halaman pesan terjadwal
├── sessions/                # Folder session WhatsApp
├── server.js                # Server utama Node.js
├── DB.sql                   # Schema database
└── package.json
```

## 🛠️ Instalasi

### 1. Clone Repository
```bash
git clone <repository-url>
cd wa-gateway
```

### 2. Install Dependencies
```bash
npm install
```

### 3. Setup Database
```bash
# Import database schema
mysql -u root -p < DB.sql
```

### 4. Konfigurasi Environment
Buat file `.env` di root folder:
```env
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=your_password
DB_NAME=wa-gateway
PORT=3000
```

### 5. Jalankan Server
```bash
# Development mode
npm run dev

# Production mode
npm start
```

## 📊 Database Schema

### Tabel Utama

1. **users** - Data user multi-tenant
2. **user_api_keys** - API keys untuk setiap user
3. **user_limits** - Limit penggunaan per user
4. **nomor** - Device/nomor WhatsApp
5. **user_devices** - Relasi user dengan device
6. **receive_chat** - Riwayat pesan
7. **autoreply** - Auto reply rules
8. **pesan** - Pesan terjadwal
9. **user_activity_logs** - Log aktivitas
10. **webhook_logs** - Log webhook

## 🔌 API Endpoints

### Authentication
Semua endpoint memerlukan header `x-api-key` atau parameter `api_key`

### Device Management
- `GET /api/user/devices` - Daftar device user
- `GET /api/user/devices/status` - Status device
- `POST /api/reset-session` - Reset session device

### Messaging
- `POST /api/send-message` - Kirim pesan teks
- `POST /api/send-media` - Kirim media

### History & Data
- `GET /api/user/chat-history` - Riwayat chat
- `GET /api/user/auto-reply` - Daftar auto reply
- `GET /api/user/scheduled-messages` - Pesan terjadwal

### User Info
- `GET /api/user/info` - Informasi user
- `GET /api/user/limits` - Limit penggunaan

## 🎯 Package Types

### Basic
- 1 device
- 1,000 messages/month
- 10 auto replies
- 50 scheduled messages

### Premium
- 5 devices
- 10,000 messages/month
- 100 auto replies
- 500 scheduled messages

### Enterprise
- Unlimited devices
- Unlimited messages
- Unlimited auto replies
- Unlimited scheduled messages

## 🔐 Security Features

- **API Key Authentication**: Setiap request harus menyertakan API key valid
- **Device Ownership**: User hanya bisa mengakses device miliknya
- **Usage Limits**: Pembatasan penggunaan berdasarkan package
- **Activity Logging**: Semua aktivitas user dicatat
- **Input Validation**: Validasi input untuk mencegah injection

## 📱 WhatsApp Features

- **Multi-device Support**: Satu user bisa memiliki multiple device
- **Real-time QR Code**: QR code muncul secara real-time
- **Auto Reconnection**: Koneksi otomatis jika terputus
- **Session Management**: Manajemen session yang aman
- **Message History**: Riwayat lengkap pesan masuk/keluar

## 🎨 Frontend Features

- **Responsive Design**: Tampilan responsif untuk semua device
- **Real-time Updates**: Update status device secara real-time
- **Filter & Search**: Filter dan pencarian pesan
- **Export Data**: Export riwayat ke CSV
- **Modal Details**: Detail pesan dalam modal

## 🚀 Deployment

### Production Setup
1. Setup reverse proxy (Nginx/Apache)
2. Gunakan PM2 untuk process management
3. Setup SSL certificate
4. Konfigurasi firewall
5. Setup monitoring dan logging

### Environment Variables
```env
NODE_ENV=production
DB_HOST=your_db_host
DB_USER=your_db_user
DB_PASSWORD=your_db_password
DB_NAME=wa-gateway
PORT=3000
```

## 📝 Contoh Penggunaan API

### Kirim Pesan
```bash
curl -X POST http://localhost:3000/api/send-message \
  -H "x-api-key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "pengirim": "6281381830651",
    "nomor": "6281234567890",
    "pesan": "Hello World!"
  }'
```

### Dapatkan Riwayat Chat
```bash
curl -X GET "http://localhost:3000/api/user/chat-history?limit=50&offset=0" \
  -H "x-api-key: your-api-key"
```

## 🔧 Troubleshooting

### QR Code Tidak Muncul
1. Cek koneksi internet
2. Restart server
3. Hapus folder session
4. Cek log error

### Pesan Tidak Terkirim
1. Cek status device (harus connected)
2. Cek limit penggunaan
3. Cek format nomor
4. Cek log error

### Database Error
1. Cek koneksi database
2. Cek credentials
3. Cek schema database
4. Restart database service

## 📞 Support

Untuk bantuan dan support, silakan hubungi:
- Email: support@wa-gateway.com
- Documentation: https://docs.wa-gateway.com
- Issues: https://github.com/wa-gateway/issues

## 📄 License

MIT License - lihat file LICENSE untuk detail lebih lanjut. 