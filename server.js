require('dotenv').config();
const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const path = require('path');
const cors = require('cors');
const bodyParser = require('body-parser');

// Import services dan middleware
const db = require('./src/config/database');
const { startSocketForNumber, getWaStatus, resetSession } = require('./src/services/whatsapp');
const { startScheduledMessageCron } = require('./src/services/messageService');
const apiRoutes = require('./src/routes/api');

const app = express();
const server = http.createServer(app);
const io = new Server(server);

const PORT = process.env.PORT || 3000;

// Middleware
app.use(express.static('public'));
app.use(cors());
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

// Routes
app.use('/api', apiRoutes);

// Serve static files
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public/index.html'));
});

// Socket.IO connection handling
io.on('connection', (socket) => {
    console.log('Client connected');

    socket.on('request-qr', async (data) => {
        console.log('request-qr received:', data);
        const nomor = data.nomor;
        await startSocketForNumber(nomor, socket);
    });

    socket.on('disconnect-device', (data) => {
        const nomor = data.nomor;
        resetSession(nomor);
        socket.emit('disconnected', { nomor, message: 'WhatsApp Disconnected' });
        io.emit('device-status', { nomor, status: 'disconnected' });
    });
});

// Start scheduled message cron
startScheduledMessageCron();

// Start server
server.listen(PORT, () => {
    console.log(`Server berjalan di port ${PORT}`);
});
