<h1 class="mb-4 fw-bold text-secondary">Kirim Pesan</h1>
<div class="bg-white shadow rounded p-4">
    <form id="form-kirim-pesan">
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
</div>
<script>
$('#form-kirim-pesan').on('submit', function(e) {
    e.preventDefault();
    var nomor = $('#nomor').val();
    var pesan = $('#pesan').val();
    $('#hasil-kirim').html('<span class="text-secondary">Mengirim...</span>');
    $.post('/api/send-message', { nomor: nomor, pesan: pesan })
        .done(function(res) {
            $('#hasil-kirim').html('<span class="text-success">' + (res.message || 'Pesan berhasil dikirim!') + '</span>');
        })
        .fail(function(xhr) {
            $('#hasil-kirim').html('<span class="text-danger">' + (xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Gagal mengirim pesan!') + '</span>');
        });
});
</script> 