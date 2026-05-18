<?php
session_start();
require '../config/security.php';

if ($_SESSION['role'] !== 'nasabah') {
    header('Location: ../auth/login.php');
    exit;
}

include '../config/database.php';

// no kontrak = username
$no_kontrak = $_SESSION['username'] ?? null;

// kalau session username belum ada, ambil dari DB
if (!$no_kontrak) {
    $uid = $_SESSION['user_id'];
    $u = mysqli_fetch_assoc(mysqli_query($conn,"SELECT username FROM users WHERE id='$uid'"));
    $no_kontrak = $u['username'];
}

// QUERY RIWAYAT PEMBAYARAN (SESUAI STRUKTUR ASLI)
$data = mysqli_query($conn, "
    SELECT 
        pb.id,
        a.bulan_ke,
        a.jumlah AS jumlah_angsuran,
        pb.kode_unik,
        pb.status,
        pb.created_at AS waktu_upload,
        pb.validated_at
    FROM pembayaran pb
    JOIN angsuran a ON pb.angsuran_id = a.id
    JOIN penjualan p ON a.penjualan_id = p.id
    WHERE p.no_kontrak = '$no_kontrak'
    ORDER BY pb.created_at DESC
");

function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Riwayat Pembayaran</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">

<h4 class="mb-3">Riwayat Pembayaran</h4>

<table class="table table-bordered table-sm bg-white">
<thead class="table-light">
<tr>
<th>No</th>
<th>Bulan Ke</th>
<th>Tagihan</th>
<th>Kode Unik</th>
<th>Total Transfer</th>
<th>Status</th>
<th>Upload</th>
<th>Validasi</th>
</tr>
</thead>
<tbody>

<?php
if (mysqli_num_rows($data) == 0) {
    echo '<tr><td colspan="8" class="text-center text-muted">Belum ada pembayaran</td></tr>';
} else {
    $no=1;
    while($r=mysqli_fetch_assoc($data)){
        $total = $r['jumlah_angsuran'] + $r['kode_unik'];
?>
<tr class="<?= $r['status']=='VALID'?'table-success':'table-warning' ?>">
<td><?= $no++ ?></td>
<td><?= $r['bulan_ke'] ?></td>
<td><?= rupiah($r['jumlah_angsuran']) ?></td>
<td><?= $r['kode_unik'] ?></td>
<td><?= rupiah($total) ?></td>
<td>
<?php if($r['status']=='VALID'){ ?>
<span class="badge bg-success">VALID</span>
<?php } else { ?>
<span class="badge bg-warning text-dark">PENDING</span>
<?php } ?>
</td>
<td><?= date('d-m-Y H:i', strtotime($r['waktu_upload'])) ?></td>
<td><?= $r['validated_at'] ? date('d-m-Y H:i', strtotime($r['validated_at'])) : '-' ?></td>
</tr>
<?php }} ?>

</tbody>
</table>

<a href="dashboard.php" class="btn btn-outline-secondary w-100 mt-3">
Kembali ke Dashboard
</a>

</div>

</body>
</html>
