<h1 class="mb-4 fw-bold text-secondary">Riwayat Pesan</h1>
<div class="bg-white shadow rounded p-4">
    <table id="history-table" class="table table-bordered table-hover align-middle">
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
        <tbody>
            <tr><td colspan="7" class="text-center text-secondary py-4">Belum ada data</td></tr>
        </tbody>
    </table>
</div>
<script>
$(document).ready(function() {
    // Inisialisasi DataTable pada elemen <table>, bukan <tbody>
    $('#history-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'API/history.php',
            type: 'POST'
        },
        columns: [
            { 
                data: null,
                orderable: false,
                searchable: false,
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { data: 'id_pesan' },
            { data: 'nomor' },
            { data: 'pesan' },
            { 
                data: 'from_me',
                render: function(data, type, row) {
                    return data == 1 ? 'Ya' : 'Tidak';
                }
            },
            { data: 'nomor_saya' },
            { data: 'tanggal' }
        ],
        language: {
            emptyTable: "Belum ada data",
            processing: "Memuat...",
            zeroRecords: "Belum ada data",
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Berikutnya",
                previous: "Sebelumnya"
            }
        }
    });
});
</script>
