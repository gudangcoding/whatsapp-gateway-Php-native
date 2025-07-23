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

app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public/index.html'));
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
        return res.status(400).json({ success: false, error: 'Body kosong' });
    }
    const { pengirim, nomor, pesan } = req.body;
    if (!pengirim || !nomor || !pesan) {
        return res.status(400).json({ success: false, error: 'pengirim, nomor, dan pesan wajib diisi' });
    }
    const sock = waInstances[pengirim];
    if (!sock || !sock.user || !sock.user.id) {
        return res.status(400).json({ success: false, error: 'Device pengirim belum connected' });
    }
    try {
        let nomorTujuan = nomor.replace(/[^0-9]/g, '');
        if (nomorTujuan.startsWith('0')) nomorTujuan = '62' + nomorTujuan.slice(1);
        if (!nomorTujuan.endsWith('@s.whatsapp.net')) nomorTujuan += '@s.whatsapp.net';
        const sendResult = await sock.sendMessage(nomorTujuan, { text: pesan });
        // Simpan ke receive_chat sebagai pesan keluar
        simpanReceiveChat({
          id_pesan: sendResult.key.id,
          nomor: nomor,
          pesan: pesan,
          from_me: 1,
          nomor_saya: sock.user.id.split(':')[0],
          tanggal: new Date()
        });
        res.json({ success: true, message: 'Pesan berhasil dikirim!' });
    } catch (err) {
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
  db.query('SELECT * FROM auto_reply', (err, results) => {
    if (err) return res.status(500).json({ error: err.message });
    res.json(results);
  });
});

app.post('/api/auto-reply', (req, res) => {
  const { keyword, reply } = req.body;
  if (!keyword || !reply) {
    return res.status(400).json({ error: 'Kata kunci dan balasan wajib diisi' });
  }
  db.query('INSERT INTO auto_reply (keyword, reply) VALUES (?, ?)', [keyword, reply], (err) => {
    if (err) return res.status(500).json({ error: err.message });
    res.json({ success: true });
  });
});

// --- PESAN TERJADWAL ENDPOINTS ---
app.get('/api/pesan-terjadwal', (req, res) => {
  db.query('SELECT * FROM pesan_terjadwal', (err, results) => {
    if (err) return res.status(500).json({ error: err.message });
    res.json(results);
  });
});

app.post('/api/pesan-terjadwal', (req, res) => {
  const { nomor, pesan, waktu } = req.body;
  if (!nomor || !pesan || !waktu) {
    return res.status(400).json({ error: 'Nomor, pesan, dan waktu wajib diisi' });
  }
  db.query('INSERT INTO pesan_terjadwal (nomor, pesan, waktu) VALUES (?, ?, ?)', [nomor, pesan, waktu], (err) => {
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
    if (err) console.error('Gagal simpan receive_chat:', err.message);
  });
}

// --- CRON PESAN TERJADWAL ---
cron.schedule('* * * * *', () => {
  const now = new Date();
  const nowStr = now.toISOString().slice(0, 16).replace('T', ' ');
  db.query("SELECT * FROM pesan_terjadwal WHERE waktu <= ? AND (status IS NULL OR status != 'sent')", [nowStr], (err, rows) => {
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
            from_me: 1,
            nomor_saya: sock.user.id.split(':')[0],
            tanggal: row.waktu
          });
          db.query("UPDATE pesan_terjadwal SET status='sent' WHERE id=?", [row.id]);
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
        from_me: 0,
        nomor_saya: sock.user.id.split(':')[0],
        tanggal: new Date(msg.messageTimestamp * 1000)
      });
    }
    if (!isiPesan) return;
    db.query('SELECT * FROM auto_reply', (err, rows) => {
      if (err) return;
      rows.forEach(row => {
        if (isiPesan.toLowerCase().includes(row.keyword.toLowerCase())) {
          sock.sendMessage(msg.key.remoteJid, { text: row.reply }).then((sendResult) => {
            simpanReceiveChat({
              id_pesan: sendResult.key.id,
              nomor: nomorPengirim,
              pesan: row.reply,
              from_me: 1,
              nomor_saya: sock.user.id.split(':')[0],
              tanggal: new Date()
            });
          });
        }
      });
    });
  });
}

server.listen(PORT, () => {
    console.log('Server running on http://localhost:' + PORT);
    // startSocket(); // This line is removed as per the new_code, as the socket connection is now managed per number.
});
