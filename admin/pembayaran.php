<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

include '../config/database.php';

$pembayaran = mysqli_query($conn,"
    SELECT pb.*, a.bulan_ke, a.jumlah, a.id AS angsuran_id,
           p.no_kontrak,
           np.nama
    FROM pembayaran pb
    JOIN angsuran a ON pb.angsuran_id = a.id
    JOIN penjualan p ON a.penjualan_id = p.id
    LEFT JOIN nasabah_profile np ON np.no_kontrak = p.no_kontrak
    WHERE pb.status='PENDING'
    ORDER BY pb.created_at ASC
");

function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>

<div class="container-fluid px-3 mt-4">

<h4 class="fw-bold mb-3">Validasi Pembayaran</h4>

<div class="card shadow-sm border-0">
<div class="card-body">

<div class="table-responsive">
<table class="table table-bordered table-hover table-sm align-middle bg-white">
<thead class="table-light">
<tr>
<th>No Kontrak</th>
<th>Nama</th>
<th>Bulan</th>
<th>Tagihan</th>
<th>Kode</th>
<th>Total</th>
<th>Bukti</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>

<?php if(mysqli_num_rows($pembayaran)==0){ ?>
<tr>
<td colspan="8" class="text-center text-muted">
Tidak ada pembayaran menunggu validasi
</td>
</tr>
<?php } ?>

<?php while ($p = mysqli_fetch_assoc($pembayaran)) { ?>
<tr>
<td><?= $p['no_kontrak'] ?></td>
<td><?= $p['nama'] ?></td>
<td><?= $p['bulan_ke'] ?></td>
<td><?= rupiah($p['jumlah']) ?></td>
<td><?= $p['kode_unik'] ?></td>
<td><?= rupiah($p['jumlah'] + $p['kode_unik']) ?></td>
<td>
<a href="../assets/bukti/<?= $p['bukti'] ?>" target="_blank">
Lihat
</a>
</td>
<td>
<a href="dashboard.php?page=pembayaran_valid&id=<?= $p['id'] ?>&angsuran=<?= $p['angsuran_id'] ?>"
class="btn btn-success btn-sm"
onclick="return confirm('Validasi pembayaran ini?')">
✔ Valid
</a>
</td>
</tr>
<?php } ?>

</tbody>
</table>
</div>

</div>
</div>

</div>
