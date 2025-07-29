const db = require('../config/database');
const cron = require('node-cron');

// --- SIMPAN RIWAYAT PESAN KE receive_chat ---
function simpanReceiveChat({id_pesan, nomor, pesan, from_me, nomor_saya, tanggal = null, user_id = null}) {
    const tgl = tanggal || new Date();
    const query = user_id ? 
        'INSERT INTO receive_chat (id_pesan, nomor, pesan, from_me, nomor_saya, tanggal, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)' :
        'INSERT INTO receive_chat (id_pesan, nomor, pesan, from_me, nomor_saya, tanggal) VALUES (?, ?, ?, ?, ?, ?)';
    
    const params = user_id ? 
        [id_pesan, nomor, pesan, from_me, nomor_saya, tgl, user_id] :
        [id_pesan, nomor, pesan, from_me, nomor_saya, tgl];
    
    db.query(query, params, (err) => {
        if (err) logError('Gagal simpan receive_chat: ' + err.message);
    });
}

// --- SETUP AUTO REPLY ---
function setupAutoReply(sock, nomorSaya) {
    sock.ev.on('messages.upsert', async (m) => {
        const msg = m.messages && m.messages[0];
        if (!msg) return;
        
        const messageType = Object.keys(msg.message)[0];
        if (messageType === 'protocolMessage' || messageType === 'senderKeyDistributionMessage') return;
        
        const messageContent = msg.message?.conversation || msg.message?.extendedTextMessage?.text || '';
        const remoteJid = msg.key.remoteJid;
        
        if (!remoteJid || remoteJid === 'status@broadcast') return;
        
        // Simpan pesan masuk
        simpanReceiveChat({
            id_pesan: msg.key.id,
            nomor: remoteJid.split('@')[0],
            pesan: messageContent,
            from_me: '0',
            nomor_saya: nomorSaya,
            tanggal: new Date()
        });
        
        // Cek auto reply berdasarkan device
        db.query(
            'SELECT ar.* FROM autoreply ar INNER JOIN nomor n ON ar.device_nomor = n.nomor WHERE n.nomor = ? AND ar.is_active = 1',
            [nomorSaya],
            (err, results) => {
                if (err) {
                    logError('Error checking auto reply: ' + err.message);
                    return;
                }
                
                results.forEach(async (autoReply) => {
                    const keyword = autoReply.keyword.toLowerCase();
                    const message = messageContent.toLowerCase();
                    
                    if (autoReply.case_sensitive === '1') {
                        if (message.includes(keyword)) {
                            await sendAutoReply(sock, remoteJid, autoReply.response, autoReply.media);
                        }
                    } else {
                        if (message.includes(keyword)) {
                            await sendAutoReply(sock, remoteJid, autoReply.response, autoReply.media);
                        }
                    }
                });
            }
        );
    });
}

async function sendAutoReply(sock, remoteJid, response, media) {
    try {
        if (media) {
            await sock.sendMessage(remoteJid, { 
                image: { url: media }, 
                caption: response 
            });
        } else {
            await sock.sendMessage(remoteJid, { text: response });
        }
        
        console.log('Auto reply sent to:', remoteJid);
    } catch (err) {
        logError('Error sending auto reply: ' + err.message);
    }
}

// --- CRON PESAN TERJADWAL ---
function startScheduledMessageCron() {
    cron.schedule('* * * * *', () => {
        const now = new Date();
        const nowStr = now.toISOString().slice(0, 19).replace('T', ' '); // yyyy-mm-dd HH:MM:SS
        
        db.query(
            'SELECT p.*, n.nomor as device_nomor FROM pesan p INNER JOIN nomor n ON p.nomor = n.nomor WHERE p.status = "MENUNGGU JADWAL" AND p.jadwal <= ?',
            [nowStr],
            (err, results) => {
                if (err) {
                    logError('Error cron pesan: ' + err.message);
                    return;
                }
                
                results.forEach(async (row) => {
                    const { getWaInstance } = require('./whatsapp');
                    const sock = getWaInstance(row.device_nomor);
                    
                    if (!sock || !sock.user || !sock.user.id) {
                        console.log('Device not connected for scheduled message:', row.device_nomor);
                        return;
                    }
                    
                    let nomorTujuan = row.nomor.replace(/[^0-9]/g, '');
                    if (nomorTujuan.startsWith('0')) nomorTujuan = '62' + nomorTujuan.slice(1);
                    if (!nomorTujuan.endsWith('@s.whatsapp.net')) nomorTujuan += '@s.whatsapp.net';
                    
                    try {
                        const sendResult = await sock.sendMessage(nomorTujuan, { text: row.pesan });
                        
                        // Update status pesan
                        db.query('UPDATE pesan SET status = "TERKIRIM" WHERE id = ?', [row.id]);
                        
                        // Simpan ke receive_chat
                        simpanReceiveChat({
                            id_pesan: sendResult.key.id,
                            nomor: row.nomor,
                            pesan: row.pesan,
                            from_me: '1',
                            nomor_saya: sock.user.id.split(':')[0],
                            tanggal: row.jadwal,
                            user_id: row.user_id
                        });
                        
                        console.log('Scheduled message sent:', row.id);
                    } catch (err) {
                        logError('Error sending scheduled message: ' + err.message);
                        db.query('UPDATE pesan SET status = "GAGAL" WHERE id = ?', [row.id]);
                    }
                });
            }
        );
    });
}

function logError(message) {
    console.error('[' + new Date().toISOString() + '] ' + message);
}

module.exports = {
    simpanReceiveChat,
    setupAutoReply,
    sendAutoReply,
    startScheduledMessageCron,
    logError
}; 