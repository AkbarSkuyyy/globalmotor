<?php
session_start();
require '../config/security.php';

if ($_SESSION['role'] !== 'nasabah') {
    header('Location: ../auth/login.php');
    exit;
}

include '../config/database.php';

$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT username FROM users WHERE id='$user_id'"));
$no_kontrak = $user['username'];

$jual = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT p.*, k.merk, k.tipe, k.warna
    FROM penjualan p
    JOIN kendaraan k ON p.kendaraan_id = k.id
    WHERE p.no_kontrak='$no_kontrak'
"));

$angsuran = mysqli_query($conn,"
    SELECT * FROM angsuran
    WHERE penjualan_id='{$jual['id']}'
    ORDER BY bulan_ke ASC
");

$data=[];
$lunas=0;
$total=0;

while($a=mysqli_fetch_assoc($angsuran)){
    $data[]=$a;
    $total++;
    if($a['status']=='LUNAS') $lunas++;
}

$progress=$total>0?round(($lunas/$total)*100):0;

$next=null;
foreach($data as $a){
    if($a['status']=='BELUM'){
        $next=$a;
        break;
    }
}

function rupiah($a){
    return 'Rp '.number_format($a,0,',','.');
}

date_default_timezone_set('Asia/Jakarta');
?>
<!DOCTYPE html>
<html>
<head>
<title>Dashboard Nasabah</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#eef2f7;
    font-family:'Segoe UI',sans-serif;
}

/* HEADER */
.header{
    background:linear-gradient(135deg,#2563eb,#1e40af);
    color:white;
    padding:40px 30px;
    border-radius:0 0 30px 30px;
    box-shadow:0 15px 35px rgba(0,0,0,0.15);
}

.progress{
    height:10px;
    border-radius:20px;
    overflow:hidden;
}

.progress-bar{
    background:linear-gradient(90deg,#22c55e,#16a34a);
    transition:width 1s ease-in-out;
}

/* CARD */
.card-premium{
    border:none;
    border-radius:20px;
    box-shadow:0 15px 35px rgba(0,0,0,0.08);
}

.summary-card{
    border:none;
    border-radius:18px;
    box-shadow:0 10px 25px rgba(0,0,0,0.06);
}

/* MENU ICON */
.menu-box{
    display:flex;
    justify-content:space-around;
    margin-top:30px;
}

.menu-item{
    text-align:center;
    text-decoration:none;
    color:#374151;
}

.menu-circle{
    width:60px;
    height:60px;
    border-radius:50%;
    background:white;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 10px 25px rgba(0,0,0,0.08);
    font-size:22px;
    transition:all .3s ease;
}

.menu-circle:hover{
    transform:translateY(-5px);
    background:#2563eb;
    color:white;
}

.menu-label{
    margin-top:8px;
    font-size:13px;
}
</style>
</head>
<body>

<!-- HEADER -->
<div class="header">
<div class="container">
    <h5 class="fw-bold"><?= $jual['merk'].' '.$jual['tipe'].' ('.$jual['warna'].')' ?></h5>
    <small>No Kontrak: <?= $no_kontrak ?></small>

    <div class="mt-4">
        <small>Progress Pembayaran</small>
        <div class="progress mt-2">
            <div class="progress-bar" style="width: <?= $progress ?>%"></div>
        </div>
        <small><?= $progress ?>% Lunas (<?= $lunas ?>/<?= $total ?>)</small>
    </div>
</div>
</div>

<div class="container mt-4 mb-5">

<!-- SUMMARY -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card summary-card text-center p-3">
            <small>Total Angsuran</small>
            <h5 class="fw-bold"><?= $total ?></h5>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card summary-card text-center p-3">
            <small>Lunas</small>
            <h5 class="fw-bold text-success"><?= $lunas ?></h5>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card summary-card text-center p-3">
            <small>Belum</small>
            <h5 class="fw-bold text-danger"><?= $total-$lunas ?></h5>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card summary-card text-center p-3">
            <small>Angsuran / Bulan</small>
            <h6 class="fw-bold"><?= rupiah($jual['angsuran']) ?></h6>
        </div>
    </div>
</div>

<!-- TAGIHAN -->
<?php if($next){ ?>
<div class="card card-premium text-center p-4 mb-4">
    <small class="text-muted">Tagihan Berikutnya</small>
    <h3 class="fw-bold"><?= rupiah($next['jumlah']) ?></h3>
    <p>Bulan ke <?= $next['bulan_ke'] ?></p>
    <p>Jatuh Tempo: <strong><?= date('d M Y',strtotime($next['jatuh_tempo'])) ?></strong></p>

    <a href="upload_bayar.php?id=<?= $next['id'] ?>&jumlah=<?= $next['jumlah'] ?>"
       class="btn btn-primary w-100 mt-3 rounded-4">
       💳 Bayar Sekarang
    </a>
</div>
<?php } ?>

<!-- MENU ICON -->
<div class="menu-box">

    <a href="kartu_angsuran.php" target="_blank" class="menu-item">
        <div class="menu-circle">📄</div>
        <div class="menu-label">Kartu</div>
    </a>

    <a href="riwayat_pembayaran.php" class="menu-item">
        <div class="menu-circle">📜</div>
        <div class="menu-label">Riwayat</div>
    </a>

    <a href="../auth/logout.php" class="menu-item">
        <div class="menu-circle">🚪</div>
        <div class="menu-label">Logout</div>
    </a>

</div>

</div>

<script>
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {

        fetch("save_location.php", {
            method: "POST",
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: "lat=" + position.coords.latitude +
                  "&lng=" + position.coords.longitude
        });

    });
}
</script>

</body>
</html>
