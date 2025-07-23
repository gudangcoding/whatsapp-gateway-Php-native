<h1 class="mb-4 fw-bold text-secondary">Daftar Device</h1>
<div class="bg-white shadow rounded p-4">
    <div class="d-flex align-items-center mb-3">
        <button id="tambah-device" class="btn btn-success me-3">Tambah Device</button>
        <span id="status" class="text-secondary fw-medium"></span>
    </div>

    <!-- Modal Tambah Device -->
    <div class="modal fade" id="modal-device" tabindex="-1" aria-labelledby="modalDeviceLabel" >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5 text-success" id="modalDeviceLabel">Tambah Device</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form id="form-device">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="pemilik" class="form-label">Pemilik</label>
                            <input type="text" class="form-control" id="pemilik" name="pemilik" required>
                        </div>
                        <div class="mb-3">
                            <label for="nomor" class="form-label">Nomor</label>
                            <input type="text" class="form-control" id="nomor" name="nomor" required>
                        </div>
                        <div class="mb-3">
                            <label for="link_webhook" class="form-label">Link Webhook</label>
                            <input type="text" class="form-control" id="link_webhook" name="link_webhook">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                            id="close-modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th scope="col">No</th>
                <th scope="col">Pemilik</th>
                <th scope="col">Nomor</th>
                <th scope="col">Link Webhook</th>
                <th scope="col">Status</th>
                <th scope="col">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            include 'helper/koneksi.php';
            // Ambil data dari tabel device
            $sql = "SELECT id, pemilik, nomor, link_webhook FROM device";
            $result = $conn->query($sql);
            ?>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php $no = 1;
                while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($row['pemilik'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['nomor']); ?></td>
                        <td><?php echo htmlspecialchars($row['link_webhook'] ?: '-'); ?></td>
                        <td id="status-<?php echo $row['nomor']; ?>">
                            <span class="badge bg-secondary">Loading...</span>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <!-- Edit Button -->
                                <button class="edit-btn btn btn-sm btn-primary" data-id="<?php echo $row['id']; ?>"
                                    data-pemilik="<?php echo htmlspecialchars($row['pemilik']); ?>"
                                    data-nomor="<?php echo htmlspecialchars($row['nomor']); ?>"
                                    data-link_webhook="<?php echo htmlspecialchars($row['link_webhook']); ?>">Edit</button>
                                <!-- Hapus Button -->
                                <button class="delete-btn btn btn-sm btn-danger"
                                    data-id="<?php echo $row['id']; ?>">Hapus</button>
                                <!-- Scan Button -->
                                <button type="button" class="scan-btn-row btn btn-sm btn-success"
                                    data-nomor="<?php echo htmlspecialchars($row['nomor']); ?>">Scan</button>
                                
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center text-secondary py-4">Belum ada data</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Modal QR Code -->
    <div class="modal fade" id="modal-qrcode" tabindex="-1" aria-labelledby="modalQrLabel">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalQrLabel">Scan QR Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <img id="qrcode-img" class="mb-3 rounded border" style="display:none; width:300px;" />
                    <div id="modal-status" class="mb-2 text-secondary fw-medium"></div>
                    <div id="modal-placeholder" class="text-secondary" style="display:block;">
                        Tidak ada data untuk ditampilkan.
                    </div>
                </div>
            </div>
        </div>
    </div>



    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
    <script>
        var socket = io('http://localhost:3000', { transports: ['websocket', 'polling', 'flashsocket'] });

        // Simpan nomor yang sedang di-scan
        var nomorScanAktif = null;

        $(function () {
            // Set semua status ke Loading saat halaman dibuka
            $('[id^=status-]').each(function() {
                $(this).html('<span class="badge bg-secondary">Loading...</span>');
            });
            // Modal Tambah Device
            $('#tambah-device').on('click', function () {
                $('#modal-device').modal('show');
            });
            $('#close-modal').on('click', function () {
                $('#modal-device').modal('hide');
            });
            $('#form-device').on('submit', function (e) {
                e.preventDefault();
                // TODO: Submit form via AJAX atau POST
                $('#modal-device').modal('hide');
            });

            // Handler tombol scan di setiap baris tabel
            // Fungsi untuk disconnect device
            function disconnectDevice(nomor) {
                if (typeof socket !== 'undefined' && socket && socket.connected) {
                    socket.emit('disconnect-device', { nomor: nomor });
                }
            }

            // Handler tombol scan/disconnect di setiap baris tabel
            $('.scan-btn-row').on('click', function () {
                var nomor = $(this).data('nomor');
                var $btn = $(this);

                // Jika tombol sudah dalam mode disconnect, lakukan disconnect
                if ($btn.data('connected') === true) {
                    disconnectDevice(nomor);
                    // Optional: disable tombol sementara
                    $btn.prop('disabled', true).text('Disconnecting...');
                    return;
                }

                nomorScanAktif = nomor;
                var $qrcodeImg = $('#qrcode-img');
                var $modalStatus = $('#modal-status');
                var $modalPlaceholder = $('#modal-placeholder');
                $qrcodeImg.hide().attr('src', '');
                $modalStatus.text('');
                $modalPlaceholder.show();
                $('#modal-qrcode').modal('show');
                if (typeof socket !== 'undefined' && socket && socket.connected) {
                    console.log('Emit request-qr', nomor);
                    socket.emit('request-qr', { nomor: nomor });
                } else {
                    $modalStatus.text('Tidak dapat terhubung ke server QR. Cek koneksi backend.');
                }
            });

            // Listen for connected event to update button to Disconnect
            if (typeof socket !== 'undefined' && socket) {
                socket.on('connected', function (data) {
                    if (data && data.nomor) {
                        var $btn = $('.scan-btn-row[data-nomor="' + data.nomor + '"]');
                        $btn.text('Disconnect');
                        $btn.data('connected', true);
                        $btn.prop('disabled', false);
                    }
                });
                socket.on('disconnected', function (data) {
                    if (data && data.nomor) {
                        var $btn = $('.scan-btn-row[data-nomor="' + data.nomor + '"]');
                        $btn.text('Scan');
                        $btn.data('connected', false);
                        $btn.prop('disabled', false);
                    }
                });
            }

            // Handler tombol close modal QR
            $('#modal-qrcode').on('hidden.bs.modal', function () {
                $('#qrcode-img').hide().attr('src', '');
                $('#modal-status').text('');
                $('#modal-placeholder').show();
                nomorScanAktif = null;
            });

            // Socket.io
            if (typeof socket !== 'undefined' && socket) {
                socket.on('connect', function () {
                    $('#status').text('Terhubung ke server QR');
                });
                socket.on('disconnect', function () {
                    $('#status').text('Terputus dari server QR');
                });
                socket.on('connect_error', function (err) {
                    $('#status').text('Gagal koneksi ke server QR');
                    console.error('Socket connect_error:', err);
                });
                socket.on('qr', function (data) {
                    // Tampilkan QR hanya jika nomor sesuai dengan yang sedang di-scan
                    if (data && data.qr && data.nomor === nomorScanAktif) {
                        var $qrcodeImg = $('#qrcode-img');
                        var $modalStatus = $('#modal-status');
                        var $modalPlaceholder = $('#modal-placeholder');
                        $qrcodeImg.attr('src', data.qr).show();
                        $modalPlaceholder.hide();
                        $modalStatus.text(data.nomor ? 'QR untuk: ' + data.nomor : '');
                    }
                });
                socket.on('connected', function (data) {
                    // Tampilkan status hanya jika nomor sesuai dengan yang sedang di-scan
                    if (data && data.nomor === nomorScanAktif) {
                        $('#modal-status').text(data.message);
                        $('#qrcode-img').hide();
                    }
                });
                socket.on('disconnected', function (data) {
                    // Tampilkan status hanya jika nomor sesuai dengan yang sedang di-scan
                    if (data && data.nomor === nomorScanAktif) {
                        $('#modal-status').text(data.message);
                        $('#qrcode-img').hide();
                    }
                });
                socket.on('device-status', function(data) {
                    if (data && data.nomor) {
                        var $statusCell = $('#status-' + data.nomor);
                        if ($statusCell.length) {
                            if (data.status === 'connected') {
                                $statusCell.html('<span class="badge bg-success">Connected</span>');
                            } else if (data.status === 'disconnected') {
                                $statusCell.html('<span class="badge bg-danger">Disconnected</span>');
                            } else {
                                $statusCell.html('<span class="badge bg-secondary">Loading...</span>');
                            }
                        }
                    }
                });
            } else {
                $('#status').text('Socket.IO client tidak tersedia.');
            }
        });
    </script>
</div>