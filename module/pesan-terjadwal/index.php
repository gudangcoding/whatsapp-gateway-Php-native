<h1 class="mb-4 fw-bold text-secondary">Pesan Terjadwal</h1>
<div class="bg-white shadow rounded p-4">
    <div class="d-flex align-items-center mb-3">
        <button id="tambah-pesan" class="btn btn-success me-3">Tambah Pesan</button>
    </div>
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Nomor Tujuan</th>
                <th>Pesan</th>
                <th>Jadwal</th>
                <th>Interval</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody id="pesan-terjadwal-table">
            <tr><td colspan="7" class="text-center text-secondary py-4">Belum ada data</td></tr>
        </tbody>
    </table>
</div>
<!-- Modal Tambah Pesan Terjadwal -->
<div class="modal fade" id="modal-pesan-terjadwal" tabindex="-1" aria-labelledby="modalPesanLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5 text-success" id="modalPesanLabel">Tambah Pesan Terjadwal</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="form-pesan-terjadwal">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nomor" class="form-label">Nomor Tujuan</label>
                        <input type="text" class="form-control" id="nomor" name="nomor" required>
                    </div>
                    <div class="mb-3">
                        <label for="pesan" class="form-label">Pesan</label>
                        <textarea class="form-control" id="pesan" name="pesan" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="jadwal" class="form-label">Jadwal Kirim</label>
                        <input type="datetime-local" class="form-control" id="jadwal" name="jadwal" required>
                    </div>
                    <div class="mb-3">
                        <label for="interval" class="form-label">Interval Pengulangan</label>
                        <select class="form-control" id="interval" name="interval">
                            <option value="">Sekali</option>
                            <option value="60s">Setiap 60 Detik</option>
                            <option value="daily">Harian</option>
                            <option value="weekly">Mingguan</option>
                            <option value="monthly">Bulanan</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function loadPesanTerjadwal() {
    $('#pesan-terjadwal-table').html('<tr><td colspan="7" class="text-center text-secondary py-4">Memuat...</td></tr>');
    $.get('API/pesan-terjadwal.php', function(data) {
        if (data.length === 0) {
            $('#pesan-terjadwal-table').html('<tr><td colspan="7" class="text-center text-secondary py-4">Belum ada data</td></tr>');
            return;
        }
        var html = '';
        data.forEach(function(row, i) {
            html += '<tr>' +
                '<td>' + (i+1) + '</td>' +
                '<td>' + row.nomor + '</td>' +
                '<td>' + row.pesan + '</td>' +
                '<td>' + row.jadwal + '</td>' +
                '<td>' + (row.interval || '-') + '</td>' +
                '<td>' + (row.status || '-') + '</td>' +
                '<td>-</td>' +
                '</tr>';
        });
        $('#pesan-terjadwal-table').html(html);
    });
}

$('#tambah-pesan').on('click', function() {
    $('#modal-pesan-terjadwal').modal('show');
});
$('#form-pesan-terjadwal').on('submit', function(e) {
    e.preventDefault();
    var nomor = $('#nomor').val();
    var pesan = $('#pesan').val();
    var jadwal = $('#jadwal').val();
    var interval = $('#interval').val();
    $.post('API/pesan-terjadwal.php', { nomor: nomor, pesan: pesan, jadwal: jadwal, interval: interval })
        .done(function(res) {
            $('#modal-pesan-terjadwal').modal('hide');
            $('#form-pesan-terjadwal')[0].reset();
            loadPesanTerjadwal();
            alert('Pesan terjadwal berhasil disimpan!');
        })
        .fail(function(xhr) {
            alert(xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Gagal menyimpan pesan terjadwal!');
        });
});

$(function() {
    loadPesanTerjadwal();
});
</script> 