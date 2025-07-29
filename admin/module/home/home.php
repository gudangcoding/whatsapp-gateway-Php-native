    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
    <script>
    var socket = io('http://localhost:3000', { transports: ['websocket', 'polling', 'flashsocket'] });
    var nomorScanAktif = null;
        $(function () {
        // ... kode lain ...
            $('.scan-btn-row').on('click', function () {
                var nomor = $(this).data('nomor');
            nomorScanAktif = nomor;
                var $qrcodeImg = $('#qrcode-img');
                var $modalStatus = $('#modal-status');
                var $modalPlaceholder = $('#modal-placeholder');
                $qrcodeImg.hide().attr('src', '');
                $modalStatus.text('');
                $modalPlaceholder.show();
                $('#modal-qrcode').modal('show');
                if (typeof socket !== 'undefined' && socket && socket.connected) {
                // Cek status device dulu
                $.get('/api/device-status/' + nomor, function(res) {
                    if (res.status !== 'connected') {
                        // Hapus session dulu, baru request QR
                        $.post('/api/reset-session', { nomor: nomor }, function() {
                    socket.emit('request-qr', { nomor: nomor });
                        });
                    } else {
                        socket.emit('request-qr', { nomor: nomor });
                    }
                });
            } else {
                $modalStatus.text('Tidak dapat terhubung ke server QR. Cek koneksi backend.');
            }
        });
        // ... kode lain ...
        });
    </script>