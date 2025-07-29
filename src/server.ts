import express, { Request, Response } from 'express';
import http from 'http';
import { Server as SocketIOServer, Socket } from 'socket.io';
import path from 'path';
import cors from 'cors';
import bodyParser from 'body-parser';

import db from './config/database';
import { startSocketForNumber, resetSession } from './services/whatsapp';
import { startScheduledMessageCron } from './services/messageService';
import apiRoutes from './routes/api';

const app = express();
const server = http.createServer(app);
const io = new SocketIOServer(server);

const PORT = process.env.PORT || 3000;

app.use(express.static('public'));
app.use(cors());
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

app.use('/api', apiRoutes);

app.get('/', (req: Request, res: Response) => {
  res.sendFile(path.join(__dirname, 'public/index.html'));
});

io.on('connection', (socket: Socket) => {
  console.log('Client connected');
  socket.on('request-qr', async (data: { nomor: string }) => {
    await startSocketForNumber(data.nomor, socket);
  });
  socket.on('disconnect-device', (data: { nomor: string }) => {
    resetSession(data.nomor);
    socket.emit('disconnected', { nomor: data.nomor, message: 'WhatsApp Disconnected' });
    io.emit('device-status', { nomor: data.nomor, status: 'disconnected' });
  });
});

startScheduledMessageCron();

server.listen(PORT, () => {
  console.log(`Server berjalan di port ${PORT}`);
}); 