const { makeWASocket, useMultiFileAuthState, DisconnectReason } = require("baileys");
const qrcode = require('qrcode');
const fs = require('fs');
const db = require('../config/database');
const { simpanReceiveChat, setupAutoReply } = require('./messageService');

// Simpan instance WhatsApp per nomor
const waInstances = {};
const waWaiters = {}; // { nomor: [socket, ...] }
const waStatus = {}; // { nomor: 'connected' | 'disconnected' | 'connecting' }

async function startSocketForNumber(nomor, socket) {
    if (waInstances[nomor]) {
        const sock = waInstances[nomor];
        if (sock.user && sock.user.id) {
            waStatus[nomor] = 'connected';
            console.log('Device already connected:', nomor);
            socket.emit('connected', { nomor, message: 'WhatsApp Connected' });
        } else {
            if (!waWaiters[nomor]) waWaiters[nomor] = [];
            waWaiters[nomor].push(socket);
            waStatus[nomor] = waStatus[nomor] || 'disconnected';
            console.log('Device not connected yet, waiting for QR:', nomor);
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
    setupAutoReply(sock, nomor);
    waWaiters[nomor] = [socket];
    waStatus[nomor] = 'connecting';

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;
        
        if (qr) {
            const qrCode = await qrcode.toDataURL(qr);
            console.log('Generate QR for nomor:', nomor);
            if (waWaiters[nomor]) {
                waWaiters[nomor].forEach(s => {
                    s.emit('qr', { nomor, qr: qrCode });
                    console.log('QR sent to socket for nomor:', nomor);
                });
            }
        }
        
        if (connection === 'close') {
            waStatus[nomor] = 'disconnected';
            if (waWaiters[nomor]) {
                waWaiters[nomor].forEach(s => s.emit('disconnected', { nomor, message: 'WhatsApp Disconnected' }));
            }
            
            const shouldReconnect = (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut;
            console.log('Connection closed for', nomor, ', reason:', lastDisconnect?.error, ', reconnecting:', shouldReconnect);
            
            if (shouldReconnect) {
                startSocketForNumber(nomor, socket);
            }
        } else if (connection === 'open') {
            console.log('Connection opened for', nomor);
            waStatus[nomor] = 'connected';
            if (waWaiters[nomor]) {
                waWaiters[nomor].forEach(s => s.emit('connected', { nomor, message: 'WhatsApp Connected' }));
            }
        }
    });

    sock.ev.on('creds.update', saveCreds);
}

function getWaInstance(nomor) {
    return waInstances[nomor];
}

function getWaStatus(nomor) {
    return waStatus[nomor] || 'disconnected';
}

function getAllWaStatus() {
    return waStatus;
}

function resetSession(nomor) {
    const sessionPath = `sessions/${nomor}`;
    if (fs.existsSync(sessionPath)) {
        const rimraf = require('rimraf');
        rimraf.sync(sessionPath);
    }
    delete waInstances[nomor];
    delete waWaiters[nomor];
    waStatus[nomor] = 'disconnected';
}

module.exports = {
    startSocketForNumber,
    getWaInstance,
    getWaStatus,
    getAllWaStatus,
    resetSession
}; 