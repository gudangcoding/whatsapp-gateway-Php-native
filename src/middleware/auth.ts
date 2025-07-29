import { Request, Response, NextFunction } from 'express';
import db from '../config/database';

export function validateApiKey(req: Request, res: Response, next: NextFunction) {
    const apiKey = req.headers['x-api-key'] as string || req.query.api_key as string;
    if (!apiKey) {
        return res.status(401).json({ success: false, error: 'API key required' });
    }
    db.query(
        'SELECT uak.*, u.status as user_status, u.package_type FROM user_api_keys uak INNER JOIN users u ON uak.user_id = u.id WHERE uak.api_key = ? AND uak.is_active = 1 AND u.status = "active"',
        [apiKey],
        (err: any, results: any[]) => {
            if (err) return res.status(500).json({ success: false, error: 'Database error' });
            if (results.length === 0) return res.status(401).json({ success: false, error: 'Invalid or expired API key' });
            (req as any).user = results[0];
            next();
        }
    );
}

export function validateDeviceOwnership(req: Request, res: Response, next: NextFunction) {
    const user = (req as any).user;
    const deviceNumber = req.body.nomor || req.query.nomor;
    if (!deviceNumber) return res.status(400).json({ success: false, error: 'Device number required' });
    db.query(
        'SELECT COUNT(*) as count FROM user_devices ud INNER JOIN nomor n ON ud.device_id = n.id WHERE ud.user_id = ? AND n.nomor = ? AND ud.status = "active"',
        [user.user_id, deviceNumber],
        (err: any, results: any[]) => {
            if (err) return res.status(500).json({ success: false, error: 'Database error' });
            if (results[0].count === 0) return res.status(403).json({ success: false, error: 'Device not found or access denied' });
            next();
        }
    );
}

export function checkUserLimits(req: Request, res: Response, next: NextFunction) {
    const user = (req as any).user;
    db.query(
        'SELECT ul.* FROM user_limits ul WHERE ul.user_id = ? ORDER BY ul.created_at DESC LIMIT 1',
        [user.user_id],
        (err: any, results: any[]) => {
            if (err) return res.status(500).json({ success: false, error: 'Database error' });
            if (results.length === 0) return res.status(403).json({ success: false, error: 'User limits not found' });
            (req as any).userLimits = results[0];
            next();
        }
    );
} 