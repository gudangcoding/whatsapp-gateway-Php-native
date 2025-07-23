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
        await startSocketForNumber(nomor, socket);
    });
});

app.post('/api/send-message', async (req, res) => {
    const { pengirim, nomor, pesan } = req.body;
    if (!pengirim || !nomor || !pesan) {
        return res.status(400).json({ success: false, error: 'pengirim, nomor, dan pesan wajib diisi' });
    }
    const sock = waInstances[pengirim];
    if (!sock || !sock.user || !sock.user.id) {
        return res.status(400).json({ success: false, error: 'Device pengirim belum connected' });
    }
    try {
        // Format nomor tujuan ke format WhatsApp (jika perlu)
        let nomorTujuan = nomor.replace(/[^0-9]/g, '');
        if (nomorTujuan.startsWith('0')) nomorTujuan = '62' + nomorTujuan.slice(1);
        if (!nomorTujuan.endsWith('@s.whatsapp.net')) nomorTujuan += '@s.whatsapp.net';
        await sock.sendMessage(nomorTujuan, { text: pesan });
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

server.listen(PORT, () => {
    console.log('Server running on http://localhost:' + PORT);
    // startSocket(); // This line is removed as per the new_code, as the socket connection is now managed per number.
});
