const { makeWASocket, useMultiFileAuthState, DisconnectReason } = require("baileys");
const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const qrcode = require('qrcode');
const path = require('path');
const db = require('./helper/db');
const { phoneNumberFormatter } = require('./helper/formatter');
const fs = require('fs');
const bodyParser = require('body-parser');
const rimraf = require('rimraf'); // pastikan sudah install: npm install rimraf
const cron = require('node-cron');
const cors = require('cors');

// Simpan instance WhatsApp per nomor
const waInstances = {};
const waWaiters = {}; // { nomor: [socket, ...] }
const waStatus = {}; // { nomor: 'connected' | 'disconnected' | 'connecting' }

const app = express();
const server = http.createServer(app);
const io = new Server(server);

const PORT = 3000;

app.use(express.static('public'));
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));
app.use(cors({
  origin: '*', // Ganti dengan domain spesifik jika ingin lebih aman
  methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization']
}));

app.get('/', (req, res) => {
    // Express (Node.js) tidak bisa langsung menjalankan file PHP karena PHP adalah bahasa yang berbeda dan butuh interpreter sendiri.
    // Solusi: 
    // 1. Jalankan server PHP (misal: Apache, Nginx, atau built-in PHP server) secara terpisah.
    // 2. Arahkan permintaan ke file PHP menggunakan reverse proxy (misal: dengan nginx atau http-proxy-middleware di Express).
    // 3. Atau, gunakan child_process di Node.js untuk menjalankan script PHP secara manual (tidak direkomendasikan untuk produksi).
    // Contoh reverse proxy sederhana (menggunakan http-proxy-middleware):
    // const { createProxyMiddleware } = require('http-proxy-middleware');
    // app.use('/php', createProxyMiddleware({ target: 'http://localhost:8000', changeOrigin: true }));
    // res.redirect('/php/index.php');
    res.sendFile(path.join(__dirname, 'index.html')); // Tetap gunakan HTML jika tidak ada server PHP
});

app.get('/api/numbers', (req, res) => {
  db.query('SELECT id, pemilik, nomor, link_webhook FROM device', (err, results) => {
    if (err) return res.status(500).json({ error: err.message });
    res.json(results);
  });
});

async function startSocketForNumber(nomor, socket) {
    if (waInstances[nomor]) {
        const sock = waInstances[nomor];
        if (sock.user && sock.user.id) {
            waStatus[nomor] = 'connected';
            console.log('Device already connected:', nomor); // Log debug
            socket.emit('connected', { nomor, message: 'WhatsApp Connected' });
        } else {
            if (!waWaiters[nomor]) waWaiters[nomor] = [];
            waWaiters[nomor].push(socket);
            waStatus[nomor] = waStatus[nomor] || 'disconnected';
            console.log('Device not connected yet, waiting for QR:', nomor); // Log debug
        }
        return;
    }
    const sessionPath = `sessions/${nomor}`;
    if (!fs.existsSync(sessionPath)) fs.mkdirSync(sessionPath, { recursive: true });
    const { state, saveCreds } = await useMultiFileAuthState(sessionPath);

    const sock = makeWASocket({
        auth: state,
        printQRInTerminal: false
    });

    waInstances[nomor] = sock;
    setupAutoReply(sock);
    waWaiters[nomor] = [socket];
    waStatus[nomor] = 'connecting';

    sock.ev.on('connection.update', async (update) => {
        const { connection, qr, lastDisconnect } = update;

        if (qr) {
            const qrImage = await qrcode.toDataURL(qr);
            console.log('Generate QR for nomor:', nomor); // Log debug
            (waWaiters[nomor] || []).forEach(s => {
                s.emit('qr', { qr: qrImage, nomor });
                console.log('QR sent to socket for nomor:', nomor); // Log debug
            });
        }

        if (connection === 'open') {
            waStatus[nomor] = 'connected';
            (waWaiters[nomor] || []).forEach(s => s.emit('connected', { nomor, message: 'WhatsApp Connected' }));
            io.emit('device-status', { nomor, status: 'connected' });
            waWaiters[nomor] = [];
            console.log('Device connected:', nomor); // Log debug
        }

        if (connection === 'close') {
            waStatus[nomor] = 'disconnected';
            (waWaiters[nomor] || []).forEach(s => s.emit('disconnected', { nomor, message: 'WhatsApp Disconnected' }));
            io.emit('device-status', { nomor, status: 'disconnected' });
            
            const shouldReconnect = lastDisconnect?.error?.output?.statusCode !== DisconnectReason.loggedOut;

            // Hapus instance yang rusak dari memori agar bisa dibuat ulang dari awal
            if (waInstances[nomor]) {
                delete waInstances[nomor];
            }
            if (waWaiters[nomor]) {
                delete waWaiters[nomor];
            }

            if (shouldReconnect) {
                console.log(`Connection closed for ${nomor}. Reason: ${lastDisconnect?.error?.message || 'Unknown'}. Reconnecting in 5 seconds...`);
                // Coba hubungkan kembali setelah 5 detik untuk pemulihan otomatis
                setTimeout(() => {
                    startSocketForNumber(nomor, socket);
                }, 5000);
            } else {
                console.log(`Connection closed permanently for ${nomor}. Not reconnecting.`);
            }
        }
    });

    sock.ev.on('creds.update', saveCreds);
}

io.on('connection', (socket) => {
    console.log('Client connected');

    socket.on('request-qr', async (data) => {
        console.log('request-qr received:', data); // Log debug
        const nomor = data.nomor;
        // Jika instance sudah disconnected, hapus dari waInstances agar QR baru bisa di-generate
        if (waStatus[nomor] === 'disconnected' && waInstances[nomor]) {
            delete waInstances[nomor];
        }
        await startSocketForNumber(nomor, socket);
    });

    // Tambahkan event untuk disconnect-device dari frontend
    socket.on('disconnect-device', (data) => {
        const nomor = data.nomor;
        const sessionPath = `sessions/${nomor}`;
        if (fs.existsSync(sessionPath)) {
            rimraf.sync(sessionPath);
        }
        if (waInstances[nomor]) delete waInstances[nomor];
        if (waWaiters[nomor]) delete waWaiters[nomor];
        waStatus[nomor] = 'disconnected';
        socket.emit('disconnected', { nomor, message: 'WhatsApp Disconnected' });
        io.emit('device-status', { nomor, status: 'disconnected' });
    });
});

app.post('/api/send-message', async (req, res) => {
    if (!req.body) {
        logError('Body kosong pada /api/send-message');
        return res.status(400).json({ success: false, error: 'Body kosong' });
    }
    const { pengirim, nomor, pesan } = req.body;
    if (!pengirim || !nomor || !pesan) {
        logError('Field kosong pada /api/send-message');
        return res.status(400).json({ success: false, error: 'pengirim, nomor, dan pesan wajib diisi' });
    }
    const sock = waInstances[pengirim];
    if (!sock || !sock.user || !sock.user.id) {
        logError('Device pengirim belum connected pada /api/send-message');
        return res.status(400).json({ success: false, error: 'Device pengirim belum connected' });
    }
    try {
        let nomorTujuan = nomor.replace(/[^0-9]/g, '');
        if (nomorTujuan.startsWith('0')) nomorTujuan = '62' + nomorTujuan.slice(1);
        if (!nomorTujuan.endsWith('@s.whatsapp.net')) nomorTujuan += '@s.whatsapp.net';
        const sendResult = await sock.sendMessage(nomorTujuan, { text: pesan });
        simpanReceiveChat({
          id_pesan: sendResult.key.id,
          nomor: nomor,
          pesan: pesan,
          from_me: '1',
          nomor_saya: sock.user.id.split(':')[0],
          tanggal: new Date()
        });
        res.json({ success: true, message: 'Pesan berhasil dikirim!' });
    } catch (err) {
        logError('Error send-message: ' + (err && err.message ? err.message : err));
        res.status(500).json({ success: false, error: err.message || 'Gagal mengirim pesan' });
    }
});

// Endpoint untuk reset session device tertentu
app.post('/api/reset-session', (req, res) => {
    const { nomor } = req.body;
    console.log('Reset session requested for:', nomor); // Tambahkan log ini
    if (!nomor) return res.status(400).json({ success: false, error: 'Nomor wajib diisi' });
    const sessionPath = `sessions/${nomor}`;
    if (fs.existsSync(sessionPath)) {
        rimraf.sync(sessionPath);
        delete waInstances[nomor];
        delete waWaiters[nomor];
        return res.json({ success: true, message: 'Session dihapus' });
    } else {
        return res.json({ success: true, message: 'Session tidak ditemukan' });
    }
});

// Endpoint cek status device
app.get('/api/device-status/:nomor', (req, res) => {
    const nomor = req.params.nomor;
    res.json({ status: waStatus[nomor] || 'disconnected' });
});

// --- AUTO REPLY ENDPOINTS ---
app.get('/api/auto-reply', (req, res) => {
  db.query('SELECT * FROM autoreply', (err, results) => {
    if (err) return res.status(500).json({ error: err.message });
    res.json(results);
  });
});

app.post('/api/auto-reply', (req, res) => {
  const { keyword, response, media, case_sensitive } = req.body;
  if (!keyword || !response) {
    return res.status(400).json({ error: 'Kata kunci dan balasan wajib diisi' });
  }
  db.query('INSERT INTO autoreply (keyword, response, media, case_sensitive) VALUES (?, ?, ?, ?)', [keyword, response, media || '', case_sensitive || '0'], (err) => {
    if (err) return res.status(500).json({ error: err.message });
    res.json({ success: true });
  });
});

// --- PESAN TERJADWAL ENDPOINTS ---
app.get('/api/pesan-terjadwal', (req, res) => {
  db.query("SELECT * FROM pesan WHERE status='MENUNGGU JADWAL' ORDER BY jadwal ASC", (err, results) => {
    if (err) return res.status(500).json({ error: err.message });
    res.json(results);
  });
});

app.post('/api/pesan-terjadwal', (req, res) => {
  const { nomor, pesan, jadwal } = req.body;
  if (!nomor || !pesan || !jadwal) {
    return res.status(400).json({ error: 'Nomor, pesan, dan jadwal wajib diisi' });
  }
  db.query('INSERT INTO pesan (nomor, pesan, jadwal, status) VALUES (?, ?, ?, ?)', [nomor, pesan, jadwal, 'MENUNGGU JADWAL'], (err) => {
    if (err) return res.status(500).json({ error: err.message });
    res.json({ success: true });
  });
});

// --- HISTORY ENDPOINT ---
app.get('/api/history', (req, res) => {
  db.query('SELECT id, id_pesan, nomor, pesan, from_me, nomor_saya, tanggal FROM receive_chat ORDER BY tanggal DESC', (err, results) => {
    if (err) return res.status(500).json({ error: err.message });
    res.json(results);
  });
});

// --- SIMPAN RIWAYAT PESAN KE receive_chat ---
function simpanReceiveChat({id_pesan, nomor, pesan, from_me, nomor_saya, tanggal = null}) {
  const tgl = tanggal || new Date();
  db.query('INSERT INTO receive_chat (id_pesan, nomor, pesan, from_me, nomor_saya, tanggal) VALUES (?, ?, ?, ?, ?, ?)',
    [id_pesan, nomor, pesan, from_me, nomor_saya, tgl], (err) => {
    if (err) logError('Gagal simpan receive_chat: ' + err.message);
  });
}

// --- CRON PESAN TERJADWAL ---
cron.schedule('* * * * *', () => {
  const now = new Date();
  const nowStr = now.toISOString().slice(0, 19).replace('T', ' '); // yyyy-mm-dd HH:MM:SS
  db.query("SELECT * FROM pesan WHERE status='MENUNGGU JADWAL' AND jadwal <= ?", [nowStr], (err, rows) => {
    if (err) return;
    rows.forEach(row => {
      const pengirim = Object.keys(waInstances)[0];
      const sock = waInstances[pengirim];
      if (sock && sock.user && sock.user.id) {
        let nomorTujuan = row.nomor.replace(/[^0-9]/g, '');
        if (nomorTujuan.startsWith('0')) nomorTujuan = '62' + nomorTujuan.slice(1);
        if (!nomorTujuan.endsWith('@s.whatsapp.net')) nomorTujuan += '@s.whatsapp.net';
        sock.sendMessage(nomorTujuan, { text: row.pesan }).then((sendResult) => {
          simpanReceiveChat({
            id_pesan: sendResult.key.id,
            nomor: row.nomor,
            pesan: row.pesan,
            from_me: '1',
            nomor_saya: sock.user.id.split(':')[0],
            tanggal: row.jadwal
          });
          // Logika pengulangan otomatis
          let nextJadwal = null;
          if (row.interval === '60s') {
            nextJadwal = new Date(new Date(row.jadwal).getTime() + 60 * 1000);
          } else if (row.interval === 'daily') {
            nextJadwal = new Date(new Date(row.jadwal).getTime() + 24 * 60 * 60 * 1000);
          } else if (row.interval === 'weekly') {
            nextJadwal = new Date(new Date(row.jadwal).getTime() + 7 * 24 * 60 * 60 * 1000);
          } else if (row.interval === 'monthly') {
            let d = new Date(row.jadwal);
            d.setMonth(d.getMonth() + 1);
            nextJadwal = d;
          }
          if (nextJadwal) {
            const nextJadwalStr = nextJadwal.toISOString().slice(0, 19).replace('T', ' ');
            db.query("UPDATE pesan SET jadwal=?, status='MENUNGGU JADWAL' WHERE id=?", [nextJadwalStr, row.id]);
          } else {
            db.query("UPDATE pesan SET status='TERKIRIM' WHERE id=?", [row.id]);
          }
        });
      }
    });
  });
});

// --- AUTO REPLY ON MESSAGE ---
function setupAutoReply(sock) {
  sock.ev.on('messages.upsert', async (m) => {
    const msg = m.messages && m.messages[0];
    if (!msg || !msg.message || !msg.key) return;
    const nomorPengirim = msg.key.remoteJid.split('@')[0];
    let isiPesan = '';
    if (msg.message.conversation) isiPesan = msg.message.conversation;
    else if (msg.message.extendedTextMessage && msg.message.extendedTextMessage.text) isiPesan = msg.message.extendedTextMessage.text;
    // Simpan pesan masuk ke receive_chat
    if (!msg.key.fromMe) {
      simpanReceiveChat({
        id_pesan: msg.key.id,
        nomor: nomorPengirim,
        pesan: isiPesan,
        from_me: '0',
        nomor_saya: sock.user.id.split(':')[0],
        tanggal: new Date(msg.messageTimestamp * 1000)
      });
    }
    if (!isiPesan) return;
    db.query('SELECT * FROM autoreply', (err, rows) => {
      if (err) return;
      rows.forEach(row => {
        let match = false;
        if (row.case_sensitive === '1') {
          match = isiPesan.includes(row.keyword);
        } else {
          match = isiPesan.toLowerCase().includes(row.keyword.toLowerCase());
        }
        if (match) {
          if (row.media) {
            sock.sendMessage(msg.key.remoteJid, { text: row.response, image: { url: row.media } }).then((sendResult) => {
              simpanReceiveChat({
                id_pesan: sendResult.key.id,
                nomor: nomorPengirim,
                pesan: row.response,
                from_me: '1',
                nomor_saya: sock.user.id.split(':')[0],
                tanggal: new Date()
              });
            });
          } else {
            sock.sendMessage(msg.key.remoteJid, { text: row.response }).then((sendResult) => {
              simpanReceiveChat({
                id_pesan: sendResult.key.id,
                nomor: nomorPengirim,
                pesan: row.response,
                from_me: '1',
                nomor_saya: sock.user.id.split(':')[0],
                tanggal: new Date()
              });
            });
          }
        }
      });
    });
  });
}

server.listen(PORT, () => {
    console.log('Server running on http://localhost:' + PORT);
    // startSocket(); // This line is removed as per the new_code, as the socket connection is now managed per number.
});

const logError = (msg) => {
  const fs = require('fs');
  const logMsg = `[${new Date().toISOString()}] ${msg}\n`;
  fs.appendFile('log_error.txt', logMsg, (err) => {
    if (err) console.error('Gagal menulis log error:', err.message);
  });
};
