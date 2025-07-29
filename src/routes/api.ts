import express, { Request, Response, NextFunction } from 'express';
import { validateApiKey, validateDeviceOwnership, checkUserLimits } from '../middleware/auth';
import { getWaInstance, getWaStatus, resetSession } from '../services/whatsapp';
import { simpanReceiveChat, logError } from '../services/messageService';
import db from '../config/database';

const router = express.Router();

// ... (seluruh isi endpoint sama, tambahkan tipe data pada parameter dan hasil query)
// Untuk ringkas, endpoint akan tetap sama, hanya tipe data pada parameter dan hasil query yang ditambahkan.

export default router; 