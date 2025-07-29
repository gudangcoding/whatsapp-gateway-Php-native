import { makeWASocket, useMultiFileAuthState, DisconnectReason, WASocket } from '@whiskeysockets/baileys';
import qrcode from 'qrcode';
import fs from 'fs';
import db from '../config/database';
import { simpanReceiveChat, setupAutoReply } from './messageService';
import { Socket } from 'socket.io';

interface WaInstances {
  [nomor: string]: WASocket;
}
interface WaWaiters {
  [nomor: string]: Socket[];
}
interface WaStatus {
  [nomor: string]: string;
}

const waInstances: WaInstances = {};
const waWaiters: WaWaiters = {};
const waStatus: WaStatus = {};

export async function startSocketForNumber(nomor: string, socket: Socket) {
  if (waInstances[nomor]) {
    const sock = waInstances[nomor];
    if (sock.user && sock.user.id) {
      waStatus[nomor] = 'connected';
      socket.emit('connected', { nomor, message: 'WhatsApp Connected' });
    } else {
      if (!waWaiters[nomor]) waWaiters[nomor] = [];
      waWaiters[nomor].push(socket);
      waStatus[nomor] = waStatus[nomor] || 'disconnected';
    }
    return;
  }
  const sessionPath = `sessions/${nomor}`;
  if (!fs.existsSync(sessionPath)) fs.mkdirSync(sessionPath, { recursive: true });
  const { state, saveCreds } = await useMultiFileAuthState(sessionPath);
  const sock = makeWASocket({ auth: state, printQRInTerminal: false });
  waInstances[nomor] = sock;
  setupAutoReply(sock, nomor);
  waWaiters[nomor] = [socket];
  waStatus[nomor] = 'connecting';
  sock.ev.on('connection.update', async (update: any) => {
    const { connection, lastDisconnect, qr } = update;
    if (qr) {
      const qrCode = await qrcode.toDataURL(qr);
      if (waWaiters[nomor]) {
        waWaiters[nomor].forEach(s => s.emit('qr', { nomor, qr: qrCode }));
      }
    }
    if (connection === 'close') {
      waStatus[nomor] = 'disconnected';
      if (waWaiters[nomor]) {
        waWaiters[nomor].forEach(s => s.emit('disconnected', { nomor, message: 'WhatsApp Disconnected' }));
      }
      const shouldReconnect = (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut;
      if (shouldReconnect) {
        startSocketForNumber(nomor, socket);
      }
    } else if (connection === 'open') {
      waStatus[nomor] = 'connected';
      if (waWaiters[nomor]) {
        waWaiters[nomor].forEach(s => s.emit('connected', { nomor, message: 'WhatsApp Connected' }));
      }
    }
  });
  sock.ev.on('creds.update', saveCreds);
}

export function getWaInstance(nomor: string): WASocket | undefined {
  return waInstances[nomor];
}
export function getWaStatus(nomor: string): string {
  return waStatus[nomor] || 'disconnected';
}
export function resetSession(nomor: string) {
  const sessionPath = `sessions/${nomor}`;
  if (fs.existsSync(sessionPath)) {
    const rimraf = require('rimraf');
    rimraf.sync(sessionPath);
  }
  delete waInstances[nomor];
  delete waWaiters[nomor];
  waStatus[nomor] = 'disconnected';
} 