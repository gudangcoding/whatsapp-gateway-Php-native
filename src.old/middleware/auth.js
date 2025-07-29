const db = require('../config/database');

// Middleware untuk validasi API key
function validateApiKey(req, res, next) {
    const apiKey = req.headers['x-api-key'] || req.query.api_key;
    
    if (!apiKey) {
        return res.status(401).json({ 
            success: false, 
            error: 'API key required' 
        });
    }
    
    // Validasi API key dari database
    db.query(
        'SELECT uak.*, u.status as user_status, u.package_type FROM user_api_keys uak INNER JOIN users u ON uak.user_id = u.id WHERE uak.api_key = ? AND uak.is_active = 1 AND u.status = "active"',
        [apiKey],
        (err, results) => {
            if (err) {
                console.error('Database error:', err);
                return res.status(500).json({ 
                    success: false, 
                    error: 'Database error' 
                });
            }
            
            if (results.length === 0) {
                return res.status(401).json({ 
                    success: false, 
                    error: 'Invalid or expired API key' 
                });
            }
            
            req.user = results[0];
            next();
        }
    );
}

// Middleware untuk validasi device ownership
function validateDeviceOwnership(req, res, next) {
    const { user } = req;
    const deviceNumber = req.body.nomor || req.query.nomor;
    
    if (!deviceNumber) {
        return res.status(400).json({ 
            success: false, 
            error: 'Device number required' 
        });
    }
    
    // Cek apakah device milik user ini
    db.query(
        'SELECT COUNT(*) as count FROM user_devices ud INNER JOIN nomor n ON ud.device_id = n.id WHERE ud.user_id = ? AND n.nomor = ? AND ud.status = "active"',
        [user.user_id, deviceNumber],
        (err, results) => {
            if (err) {
                console.error('Database error:', err);
                return res.status(500).json({ 
                    success: false, 
                    error: 'Database error' 
                });
            }
            
            if (results[0].count === 0) {
                return res.status(403).json({ 
                    success: false, 
                    error: 'Device not found or access denied' 
                });
            }
            
            next();
        }
    );
}

// Middleware untuk cek limit user
function checkUserLimits(req, res, next) {
    const { user } = req;
    
    db.query(
        'SELECT ul.* FROM user_limits ul WHERE ul.user_id = ? ORDER BY ul.created_at DESC LIMIT 1',
        [user.user_id],
        (err, results) => {
            if (err) {
                console.error('Database error:', err);
                return res.status(500).json({ 
                    success: false, 
                    error: 'Database error' 
                });
            }
            
            if (results.length === 0) {
                return res.status(403).json({ 
                    success: false, 
                    error: 'User limits not found' 
                });
            }
            
            req.userLimits = results[0];
            next();
        }
    );
}

module.exports = {
    validateApiKey,
    validateDeviceOwnership,
    checkUserLimits
}; 