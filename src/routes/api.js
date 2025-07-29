const express = require('express');
const router = express.Router();
const { validateApiKey, validateDeviceOwnership, checkUserLimits } = require('../middleware/auth');
const { getWaInstance, getWaStatus, resetSession } = require('../services/whatsapp');
const { simpanReceiveChat, logError } = require('../services/messageService');
const db = require('../config/database');

// Function untuk update message count
function updateMessageCount(userId) {
    db.query(
        'UPDATE user_limits SET used_messages = used_messages + 1 WHERE user_id = ?',
        [userId],
        (err) => {
            if (err) {
                logError('Error updating message count: ' + err.message);
            }
        }
    );
}

// Function untuk log API activity
function logApiActivity(userId, action, details, req) {
    const ip = req.ip || req.connection.remoteAddress;
    const userAgent = req.headers['user-agent'] || '';
    
    db.query(
        'INSERT INTO user_activity_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)',
        [userId, action, JSON.stringify(details), ip, userAgent],
        (err) => {
            if (err) {
                logError('Error logging API activity: ' + err.message);
            }
        }
    );
}

// API endpoint untuk mendapatkan devices user
router.get('/user/devices', validateApiKey, (req, res) => {
    const { user } = req;
    
    db.query(
        'SELECT n.* FROM nomor n INNER JOIN user_devices ud ON n.id = ud.device_id WHERE ud.user_id = ? AND ud.status = "active"',
        [user.user_id],
        (err, results) => {
            if (err) {
                logError('Database error: ' + err.message);
                return res.status(500).json({ 
                    success: false, 
                    error: 'Database error' 
                });
            }
            
            res.json({ 
                success: true, 
                devices: results 
            });
        }
    );
});

// API endpoint untuk mendapatkan status devices
router.get('/user/devices/status', validateApiKey, (req, res) => {
    const { user } = req;
    
    db.query(
        'SELECT n.nomor, n.nama FROM nomor n INNER JOIN user_devices ud ON n.id = ud.device_id WHERE ud.user_id = ? AND ud.status = "active"',
        [user.user_id],
        (err, results) => {
            if (err) {
                logError('Database error: ' + err.message);
                return res.status(500).json({ 
                    success: false, 
                    error: 'Database error' 
                });
            }
            
            const deviceStatuses = results.map(device => ({
                nomor: device.nomor,
                nama: device.nama,
                status: getWaStatus(device.nomor)
            }));
            
            res.json({ 
                success: true, 
                devices: deviceStatuses 
            });
        }
    );
});

// API endpoint untuk kirim pesan dengan validasi multi-tenant
router.post('/send-message', validateApiKey, validateDeviceOwnership, checkUserLimits, async (req, res) => {
    const { pengirim, nomor, pesan } = req.body;
    const { user, userLimits } = req;
    
    if (!pengirim || !nomor || !pesan) {
        return res.status(400).json({ 
            success: false, 
            error: 'pengirim, nomor, dan pesan wajib diisi' 
        });
    }
    
    // Cek limit pesan
    if (userLimits.used_messages >= userLimits.max_messages) {
        return res.status(403).json({ 
            success: false, 
            error: 'Message limit exceeded' 
        });
    }
    
    const sock = getWaInstance(pengirim);
    if (!sock || !sock.user || !sock.user.id) {
        return res.status(400).json({ 
            success: false, 
            error: 'Device pengirim belum connected' 
        });
    }
    
    let nomorTujuan = nomor.replace(/[^0-9]/g, '');
    if (nomorTujuan.startsWith('0')) nomorTujuan = '62' + nomorTujuan.slice(1);
    if (!nomorTujuan.endsWith('@s.whatsapp.net')) nomorTujuan += '@s.whatsapp.net';
    
    try {
        const sendResult = await sock.sendMessage(nomorTujuan, { text: pesan });
        
        // Simpan ke database dengan user_id
        simpanReceiveChat({
            id_pesan: sendResult.key.id,
            nomor: nomor,
            pesan: pesan,
            from_me: '1',
            nomor_saya: sock.user.id.split(':')[0],
            tanggal: new Date(),
            user_id: user.user_id
        });
        
        // Update message count
        updateMessageCount(user.user_id);
        
        // Log activity
        logApiActivity(user.user_id, 'send_message', {
            device: pengirim,
            target: nomor,
            message_length: pesan.length
        }, req);
        
        res.json({ 
            success: true, 
            message: 'Pesan berhasil dikirim!',
            message_id: sendResult.key.id
        });
    } catch (err) {
        logError('Error send-message: ' + (err && err.message ? err.message : err));
        res.status(500).json({ 
            success: false, 
            error: err.message || 'Gagal mengirim pesan' 
        });
    }
});

// API endpoint untuk kirim media dengan validasi multi-tenant
router.post('/send-media', validateApiKey, validateDeviceOwnership, checkUserLimits, async (req, res) => {
    const { pengirim, nomor, url, caption, filetype, filename } = req.body;
    const { user, userLimits } = req;
    
    if (!pengirim || !nomor || !url || !filetype) {
        return res.status(400).json({ 
            success: false, 
            error: 'pengirim, nomor, url, dan filetype wajib diisi' 
        });
    }
    
    // Cek limit pesan
    if (userLimits.used_messages >= userLimits.max_messages) {
        return res.status(403).json({ 
            success: false, 
            error: 'Message limit exceeded' 
        });
    }
    
    const sock = getWaInstance(pengirim);
    if (!sock || !sock.user || !sock.user.id) {
        return res.status(400).json({ 
            success: false, 
            error: 'Device pengirim belum connected' 
        });
    }
    
    let nomorTujuan = nomor.replace(/[^0-9]/g, '');
    if (nomorTujuan.startsWith('0')) nomorTujuan = '62' + nomorTujuan.slice(1);
    if (!nomorTujuan.endsWith('@s.whatsapp.net')) nomorTujuan += '@s.whatsapp.net';
    
    try {
        if (filetype === 'jpg' || filetype === 'png') {
            await sock.sendMessage(nomorTujuan, { 
                image: { url }, 
                caption, 
                mimetype: 'image/jpeg' 
            });
        } else if (filetype === 'pdf') {
            await sock.sendMessage(nomorTujuan, { 
                document: { url }, 
                mimetype: 'application/pdf', 
                fileName: filename ? filename + '.pdf' : undefined 
            });
        } else {
            return res.status(400).json({ 
                success: false, 
                error: 'Filetype tidak dikenal' 
            });
        }
        
        // Update message count
        updateMessageCount(user.user_id);
        
        // Log activity
        logApiActivity(user.user_id, 'send_media', {
            device: pengirim,
            target: nomor,
            filetype: filetype
        }, req);
        
        res.json({ 
            success: true, 
            message: 'Media berhasil dikirim!' 
        });
    } catch (err) {
        logError('Error send-media: ' + (err && err.message ? err.message : err));
        res.status(500).json({ 
            success: false, 
            error: err.message || 'Gagal mengirim media' 
        });
    }
});

// API endpoint untuk mendapatkan riwayat chat user
router.get('/user/chat-history', validateApiKey, (req, res) => {
    const { user } = req;
    const { nomor, limit = 50, offset = 0 } = req.query;
    
    let query = 'SELECT * FROM receive_chat WHERE user_id = ?';
    let params = [user.user_id];
    
    if (nomor) {
        query += ' AND nomor = ?';
        params.push(nomor);
    }
    
    query += ' ORDER BY tanggal DESC LIMIT ? OFFSET ?';
    params.push(parseInt(limit), parseInt(offset));
    
    db.query(query, params, (err, results) => {
        if (err) {
            logError('Database error: ' + err.message);
            return res.status(500).json({ 
                success: false, 
                error: 'Database error' 
            });
        }
        
        res.json({ 
            success: true, 
            messages: results 
        });
    });
});

// API endpoint untuk mendapatkan auto reply user
router.get('/user/auto-reply', validateApiKey, (req, res) => {
    const { user } = req;
    
    db.query(
        'SELECT * FROM autoreply WHERE user_id = ?',
        [user.user_id],
        (err, results) => {
            if (err) {
                logError('Database error: ' + err.message);
                return res.status(500).json({ 
                    success: false, 
                    error: 'Database error' 
                });
            }
            
            res.json({ 
                success: true, 
                auto_replies: results 
            });
        }
    );
});

// API endpoint untuk mendapatkan scheduled messages user
router.get('/user/scheduled-messages', validateApiKey, (req, res) => {
    const { user } = req;
    
    db.query(
        'SELECT * FROM pesan WHERE user_id = ? ORDER BY jadwal ASC',
        [user.user_id],
        (err, results) => {
            if (err) {
                logError('Database error: ' + err.message);
                return res.status(500).json({ 
                    success: false, 
                    error: 'Database error' 
                });
            }
            
            res.json({ 
                success: true, 
                scheduled_messages: results 
            });
        }
    );
});

// API endpoint untuk mendapatkan user info
router.get('/user/info', validateApiKey, (req, res) => {
    const { user } = req;
    
    db.query(
        'SELECT id, username, full_name, email, package_type, status, created_at FROM users WHERE id = ?',
        [user.user_id],
        (err, results) => {
            if (err) {
                logError('Database error: ' + err.message);
                return res.status(500).json({ 
                    success: false, 
                    error: 'Database error' 
                });
            }
            
            if (results.length === 0) {
                return res.status(404).json({ 
                    success: false, 
                    error: 'User not found' 
                });
            }
            
            res.json({ 
                success: true, 
                user: results[0] 
            });
        }
    );
});

// API endpoint untuk mendapatkan user limits
router.get('/user/limits', validateApiKey, (req, res) => {
    const { user } = req;
    
    db.query(
        'SELECT * FROM user_limits WHERE user_id = ? ORDER BY created_at DESC LIMIT 1',
        [user.user_id],
        (err, results) => {
            if (err) {
                logError('Database error: ' + err.message);
                return res.status(500).json({ 
                    success: false, 
                    error: 'Database error' 
                });
            }
            
            res.json({ 
                success: true, 
                limits: results[0] || null 
            });
        }
    );
});

// Endpoint untuk reset session device tertentu
router.post('/reset-session', validateApiKey, validateDeviceOwnership, (req, res) => {
    const { nomor } = req.body;
    const { user } = req;
    
    console.log('Reset session requested for:', nomor, 'by user:', user.user_id);
    
    if (!nomor) return res.status(400).json({ 
        success: false, 
        error: 'Nomor wajib diisi' 
    });
    
    resetSession(nomor);
    
    // Log activity
    logApiActivity(user.user_id, 'reset_session', {
        device: nomor
    }, req);
    
    return res.json({ 
        success: true, 
        message: 'Session dihapus' 
    });
});

// Endpoint untuk mendapatkan semua nomor (hanya untuk admin)
router.get('/numbers', (req, res) => {
    const apiKey = req.headers['x-api-key'] || req.query.api_key;
    
    if (!apiKey) {
        return res.status(401).json({ 
            success: false, 
            error: 'API key required' 
        });
    }
    
    // Cek apakah admin
    db.query(
        'SELECT uak.*, u.status as user_status FROM user_api_keys uak INNER JOIN users u ON uak.user_id = u.id WHERE uak.api_key = ? AND uak.is_active = 1 AND u.status = "active"',
        [apiKey],
        (err, results) => {
            if (err || results.length === 0) {
                return res.status(401).json({ 
                    success: false, 
                    error: 'Invalid API key' 
                });
            }
            
            const user = results[0];
            
            // Jika bukan admin, hanya tampilkan device milik user
            if (user.package_type !== 'enterprise') {
                db.query(
                    'SELECT n.* FROM nomor n INNER JOIN user_devices ud ON n.id = ud.device_id WHERE ud.user_id = ? AND ud.status = "active"',
                    [user.user_id],
                    (err, results) => {
                        if (err) {
                            logError('Database error: ' + err.message);
                            return res.status(500).json({ 
                                success: false, 
                                error: 'Database error' 
                            });
                        }
                        
                        res.json({ 
                            success: true, 
                            numbers: results 
                        });
                    }
                );
            } else {
                // Admin bisa lihat semua
                db.query('SELECT * FROM nomor', (err, results) => {
                    if (err) {
                        logError('Database error: ' + err.message);
                        return res.status(500).json({ 
                            success: false, 
                            error: 'Database error' 
                        });
                    }
                    
                    res.json({ 
                        success: true, 
                        numbers: results 
                    });
                });
            }
        }
    );
});

// Endpoint untuk cek status device
router.get('/device-status/:nomor', (req, res) => {
    const nomor = req.params.nomor;
    res.json({ status: getWaStatus(nomor) });
});

module.exports = router; 