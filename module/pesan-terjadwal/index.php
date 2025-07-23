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
                <th>Waktu Kirim</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody id="pesan-terjadwal-table">
            <tr><td colspan="5" class="text-center text-secondary py-4">Belum ada data</td></tr>
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
                        <label for="waktu" class="form-label">Waktu Kirim</label>
                        <input type="datetime-local" class="form-control" id="waktu" name="waktu" required>
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
$('#tambah-pesan').on('click', function() {
    $('#modal-pesan-terjadwal').modal('show');
});
$('#form-pesan-terjadwal').on('submit', function(e) {
    e.preventDefault();
    // TODO: Simpan pesan terjadwal ke backend
    $('#modal-pesan-terjadwal').modal('hide');
});
</script> 