const mysql = require('mysql');
const dbConfig = {
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'wa-gateway',
  charset: 'utf8mb4',
  timezone: '+07:00'
};
const connection = mysql.createConnection(dbConfig);
connection.connect((err) => {
  if (err) {
    console.error('Koneksi ke database gagal:', err.stack);
    return;
  }
  console.log('Terkoneksi ke database wa-gateway sebagai id ' + connection.threadId);
});
module.exports = connection; 