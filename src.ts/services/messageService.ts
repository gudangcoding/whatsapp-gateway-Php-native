import db from '../config/database';
import cron from 'node-cron';
import { WASocket } from '@whiskeysockets/baileys';

export interface ReceiveChat {
  id_pesan: string;
  nomor: string;
  pesan: string;
  from_me: '0' | '1';
  nomor_saya: string;
  tanggal?: Date;
  user_id?: number;
}

export function simpanReceiveChat(data: ReceiveChat) {
  const tgl = data.tanggal || new Date();
  const query = data.user_id ?
    'INSERT INTO receive_chat (id_pesan, nomor, pesan, from_me, nomor_saya, tanggal, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)' :
    'INSERT INTO receive_chat (id_pesan, nomor, pesan, from_me, nomor_saya, tanggal) VALUES (?, ?, ?, ?, ?, ?)';
  const params = data.user_id ?
    [data.id_pesan, data.nomor, data.pesan, data.from_me, data.nomor_saya, tgl, data.user_id] :
    [data.id_pesan, data.nomor, data.pesan, data.from_me, data.nomor_saya, tgl];
  db.query(query, params, (err: any) => {
    if (err) logError('Gagal simpan receive_chat: ' + err.message);
  });
}

export function setupAutoReply(sock: WASocket, nomorSaya: string) {
  sock.ev.on('messages.upsert', async (m: any) => {
    const msg = m.messages && m.messages[0];
    if (!msg) return;
    const messageType = Object.keys(msg.message)[0];
    if (messageType === 'protocolMessage' || messageType === 'senderKeyDistributionMessage') return;
    const messageContent = msg.message?.conversation || msg.message?.extendedTextMessage?.text || '';
    const remoteJid = msg.key.remoteJid;
    if (!remoteJid || remoteJid === 'status@broadcast') return;
    simpanReceiveChat({
      id_pesan: msg.key.id,
      nomor: remoteJid.split('@')[0],
      pesan: messageContent,
      from_me: '0',
      nomor_saya: nomorSaya,
      tanggal: new Date()
    });
    db.query(
      'SELECT ar.* FROM autoreply ar INNER JOIN nomor n ON ar.device_nomor = n.nomor WHERE n.nomor = ? AND ar.is_active = 1',
      [nomorSaya],
      (err: any, results: any[]) => {
        if (err) return logError('Error checking auto reply: ' + err.message);
        results.forEach(async (autoReply: any) => {
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

export async function sendAutoReply(sock: WASocket, remoteJid: string, response: string, media?: string) {
  try {
    if (media) {
      await sock.sendMessage(remoteJid, { image: { url: media }, caption: response });
    } else {
      await sock.sendMessage(remoteJid, { text: response });
    }
  } catch (err: any) {
    logError('Error sending auto reply: ' + err.message);
  }
}

export function startScheduledMessageCron() {
  cron.schedule('* * * * *', () => {
    const now = new Date();
    const nowStr = now.toISOString().slice(0, 19).replace('T', ' ');
    db.query(
      'SELECT p.*, n.nomor as device_nomor FROM pesan p INNER JOIN nomor n ON p.nomor = n.nomor WHERE p.status = "MENUNGGU JADWAL" AND p.jadwal <= ?',
      [nowStr],
      (err: any, results: any[]) => {
        if (err) return logError('Error cron pesan: ' + err.message);
        results.forEach(async (row: any) => {
          const { getWaInstance } = require('./whatsapp');
          const sock: WASocket = getWaInstance(row.device_nomor);
          if (!sock || !sock.user || !sock.user.id) return;
          let nomorTujuan = row.nomor.replace(/[^0-9]/g, '');
          if (nomorTujuan.startsWith('0')) nomorTujuan = '62' + nomorTujuan.slice(1);
          if (!nomorTujuan.endsWith('@s.whatsapp.net')) nomorTujuan += '@s.whatsapp.net';
          try {
            const sendResult = await sock.sendMessage(nomorTujuan, { text: row.pesan });
            db.query('UPDATE pesan SET status = "TERKIRIM" WHERE id = ?', [row.id]);
            simpanReceiveChat({
              id_pesan: sendResult.key.id,
              nomor: row.nomor,
              pesan: row.pesan,
              from_me: '1',
              nomor_saya: sock.user.id.split(':')[0],
              tanggal: row.jadwal,
              user_id: row.user_id
            });
          } catch (err: any) {
            logError('Error sending scheduled message: ' + err.message);
            db.query('UPDATE pesan SET status = "GAGAL" WHERE id = ?', [row.id]);
          }
        });
      }
    );
  });
}

export function logError(message: string) {
  console.error('[' + new Date().toISOString() + '] ' + message);
} 