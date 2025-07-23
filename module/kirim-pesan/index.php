<h1 class="mb-4 fw-bold text-secondary">Kirim Pesan</h1>
<div class="bg-white shadow rounded p-4">
    <form id="form-kirim-pesan">
        <?php
        // Ambil list device yang terkoneksi dari database
        include_once(__DIR__ . '/../../helper/koneksi.php');
        $devices = [];
        $result = $conn->query("SELECT nomor FROM device");
        while ($row = $result->fetch_assoc()) {
            $devices[] = $row['nomor'];
        }
        ?>
        <div class="mb-3">
            <label for="pengirim" class="form-label">Nomor Saya (Pengirim)</label>
            <select class="form-control" id="pengirim" name="pengirim" required>
                <option value="">-- Pilih Nomor Pengirim --</option>
                <?php foreach ($devices as $nomor): ?>
                    <option value="<?= htmlspecialchars($nomor) ?>"><?= htmlspecialchars($nomor) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Pastikan nomor ini sudah terhubung/terkoneksi di menu Device.</div>
        </div>
        <div class="mb-3">
            <label for="nomor" class="form-label">Nomor Tujuan</label>
            <input type="text" class="form-control" id="nomor" name="nomor" required placeholder="628xxxxxxx">
        </div>
        <div class="mb-3">
            <label for="pesan" class="form-label">Pesan</label>
            <textarea class="form-control" id="pesan" name="pesan" rows="3" required></textarea>
        </div>
        <button type="submit" class="btn btn-success">Kirim</button>
    </form>
    <div id="hasil-kirim" class="mt-3"></div>
    <div class="alert alert-info mt-3" id="info-gagal-kirim" style="display:none"></div>
</div>
<script>
$('#form-kirim-pesan').on('submit', function(e) {
    e.preventDefault();
    var pengirim = $('#pengirim').val();
    var nomor = $('#nomor').val();
    var pesan = $('#pesan').val();
    $('#hasil-kirim').html('<span class="text-secondary">Mengirim...</span>');
    $('#info-gagal-kirim').hide().html('');
    $.ajax({
        url: '/api/send-message',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ pengirim: pengirim, nomor: nomor, pesan: pesan }),
        dataType: 'json'
    })
        .done(function(res) {
            $('#hasil-kirim').html('<span class="text-success">' + (res.message || 'Pesan berhasil dikirim!') + '</span>');
        })
        .fail(function(xhr) {
            var errorMsg = (xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Gagal mengirim pesan!');
            $('#hasil-kirim').html('<span class="text-danger">' + errorMsg + '</span>');
            // Tampilkan info tambahan jika error karena device belum connected
            if (errorMsg.includes('Device pengirim belum connected')) {
                $('#info-gagal-kirim').show().html(
                    '<b>Kenapa pesan gagal dikirim?</b><br>' +
                    'Pastikan nomor pengirim sudah <b>terhubung/terkoneksi</b> di menu <b>Device</b>.<br>' +
                    'Jika belum, silakan hubungkan terlebih dahulu dengan scan QR di menu Device.<br>' +
                    'Setelah terhubung, coba kirim ulang pesan.'
                );
            }
        });
});
</script> 