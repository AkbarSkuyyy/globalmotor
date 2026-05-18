<?php
session_start();
require '../config/security.php';

if (!in_array($_SESSION['role'], ['admin','karyawan'])) {
    exit;
}

include '../config/database.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Keuangan.xls");

$tgl_awal = $_GET['tgl_awal'] ?? '';
$tgl_akhir = $_GET['tgl_akhir'] ?? '';

$where = "WHERE pb.status='VALID'";
if ($tgl_awal && $tgl_akhir) {
    $where .= " AND DATE(pb.created_at) BETWEEN '$tgl_awal' AND '$tgl_akhir'";
}

$q = mysqli_query($conn,"
    SELECT pb.created_at, p.no_kontrak, c.nama, a.jumlah, pb.kode_unik
    FROM pembayaran pb
    JOIN angsuran a ON pb.angsuran_id=a.id
    JOIN penjualan p ON a.penjualan_id=p.id
    JOIN customers c ON p.customer_id=c.id
    $where
");
?>

<table border="1">
<tr>
<th>Tanggal</th>
<th>No Kontrak</th>
<th>Nama</th>
<th>Jumlah</th>
</tr>

<?php while($r=mysqli_fetch_assoc($q)){ ?>
<tr>
<td><?= $r['created_at'] ?></td>
<td><?= $r['no_kontrak'] ?></td>
<td><?= $r['nama'] ?></td>
<td><?= $r['jumlah'] + $r['kode_unik'] ?></td>
</tr>
<?php } ?>
</table>
