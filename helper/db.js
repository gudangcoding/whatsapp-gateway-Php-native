const mysql = require('mysql');

const dbConfig = {
    host: 'localhost',
    user: 'root',
    password: '', // ganti dengan password mysql anda jika ada
    database: 'wa-gateway'
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


