<h1 class="mb-4 fw-bold text-secondary">Riwayat Pesan</h1>
<div class="bg-white shadow rounded p-4">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>ID Pesan</th>
                <th>Nomor</th>
                <th>Pesan</th>
                <th>Dari Saya</th>
                <th>Nomor Saya</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody id="history-table">
            <tr><td colspan="7" class="text-center text-secondary py-4">Belum ada data</td></tr>
        </tbody>
    </table>
</div>
<script>
function loadHistory() {
    $('#history-table').html('<tr><td colspan="7" class="text-center text-secondary py-4">Memuat...</td></tr>');
    $.get('API/history.php', function(data) {
        if (data.length === 0) {
            $('#history-table').html('<tr><td colspan="7" class="text-center text-secondary py-4">Belum ada data</td></tr>');
            return;
        }
        var html = '';
        data.forEach(function(row, i) {
            html += '<tr>' +
                '<td>' + (i+1) + '</td>' +
                '<td>' + row.id_pesan + '</td>' +
                '<td>' + row.nomor + '</td>' +
                '<td>' + row.pesan + '</td>' +
                '<td>' + (row.from_me == 1 ? 'Ya' : 'Tidak') + '</td>' +
                '<td>' + row.nomor_saya + '</td>' +
                '<td>' + row.tanggal + '</td>' +
                '</tr>';
        });
        $('#history-table').html(html);
    }).fail(function() {
        $('#history-table').html('<tr><td colspan="7" class="text-danger text-center py-4">Gagal memuat data</td></tr>');
    });
}
$(function() {
    loadHistory();
});
</script>
