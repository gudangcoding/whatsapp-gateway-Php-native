<h1 class="mb-4 fw-bold text-secondary">Auto Reply</h1>
<div class="bg-white shadow rounded p-4">
    <div class="d-flex align-items-center mb-3">
        <button id="tambah-auto-reply" class="btn btn-success me-3">Tambah Auto Reply</button>
    </div>
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Kata Kunci</th>
                <th>Balasan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody id="auto-reply-table">
            <tr><td colspan="4" class="text-center text-secondary py-4">Belum ada data</td></tr>
        </tbody>
    </table>
</div>
<!-- Modal Tambah Auto Reply -->
<div class="modal fade" id="modal-auto-reply" tabindex="-1" aria-labelledby="modalAutoReplyLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5 text-success" id="modalAutoReplyLabel">Tambah Auto Reply</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="form-auto-reply">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="keyword" class="form-label">Kata Kunci</label>
                        <input type="text" class="form-control" id="keyword" name="keyword" required>
                    </div>
                    <div class="mb-3">
                        <label for="reply" class="form-label">Balasan</label>
                        <textarea class="form-control" id="reply" name="reply" rows="2" required></textarea>
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
$('#tambah-auto-reply').on('click', function() {
    $('#modal-auto-reply').modal('show');
});
$('#form-auto-reply').on('submit', function(e) {
    e.preventDefault();
    // TODO: Simpan auto reply ke backend
    $('#modal-auto-reply').modal('hide');
});
</script> 