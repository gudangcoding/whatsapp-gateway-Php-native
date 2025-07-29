<h1 class="mb-4 fw-bold text-secondary">Riwayat Pesan</h1>
<div class="bg-white shadow rounded p-4">
    <div class="row mb-3">
        <div class="col-md-4 mb-2">
            <input type="text" id="search-history" class="form-control" placeholder="Cari nomor atau pesan...">
        </div>
        <div class="col-md-2 mb-2">
            <input type="date" id="filter-dari" class="form-control" placeholder="Dari tanggal">
        </div>
        <div class="col-md-2 mb-2">
            <input type="date" id="filter-sampai" class="form-control" placeholder="Sampai tanggal">
        </div>
        <div class="col-md-2 mb-2">
            <select id="filter-from-me" class="form-control">
                <option value="">Semua Pesan</option>
                <option value="1">Dari Saya</option>
                <option value="0">Bukan Dari Saya</option>
            </select>
        </div>
    </div>
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
    var q = $('#search-history').val();
    var dari = $('#filter-dari').val();
    var sampai = $('#filter-sampai').val();
    var from_me = $('#filter-from-me').val();
    var params = {};
    if(q) params.q = q;
    if(dari) params.dari = dari;
    if(sampai) params.sampai = sampai;
    if(from_me !== '') params.from_me = from_me;
    $('#history-table').html('<tr><td colspan="7" class="text-center text-secondary py-4">Memuat...</td></tr>');
    $.get('API/history.php', params, function(data) {
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
    $('#search-history, #filter-dari, #filter-sampai, #filter-from-me').on('input change', function() {
        loadHistory();
    });
});
</script> 