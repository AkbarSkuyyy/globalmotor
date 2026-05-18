<?php

require '../config/security.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

include '../config/database.php';

/* ===== NOTIF PEMBAYARAN ===== */
$notif = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT COUNT(*) AS total
    FROM pembayaran
    WHERE status='PENDING'
"));
$jumlah_notif = $notif['total'] ?? 0;

/* ===== ROUTING ===== */
$page = $_GET['page'] ?? 'home';

$allowed = [
    'home',
    'kendaraan_tambah','stok_motor','kendaraan_edit','kredit_tambah',
    'pembayaran','pembayaran_valid','cetak_struk',
    'laporan_keuangan','simulasi_kredit',
    'user_tambah','user_karyawan','user_nasabah',
    'nasabah_detail',
    'nasabah_edit',
    'user_edit',
    'audit_log',
    'pengaturan_umum','pengaturan_user','pengaturan_backup'
];

if (!in_array($page, $allowed)) {
    $page = 'home';
}

$file = $page . '.php';

function active($p){
    return (($_GET['page'] ?? 'home') === $p) ? 'active' : '';
}

function openMenu($arr){
    return in_array($_GET['page'] ?? 'home', $arr) ? 'show' : '';
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Dashboard Admin - GLOBAL MOTOR</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f1f5f9;
    overflow-x:hidden;
    font-family:'Segoe UI',sans-serif;
}

/* ===== SIDEBAR ===== */
.sidebar{
    width:260px;
    min-height:100vh;
    overflow-y:auto;
    background:#111827;
    position:fixed;
    left:0;
    top:0;
    z-index:1050;
    transition:0.3s;
}

.sidebar.hide{
    left:-260px;
}

.sidebar a{
    color:#d1d5db;
    text-decoration:none;
    padding:12px 20px;
    display:block;
    transition:0.2s;
}

.sidebar a:hover{
    background:#1f2937;
    padding-left:28px;
    color:#fff;
}

.sidebar a.active{
    background:#1e40af;
    color:#fff;
    font-weight:500;
}

.submenu a{
    padding-left:40px;
    font-size:14px;
}

/* ===== LOGO ===== */
.logo-box{
    text-align:center;
    padding:20px;
    border-bottom:1px solid #1f2937;
}
.logo-box img{
    width:120px;
}

/* ===== MAIN ===== */
.main-content{
    margin-left:260px;
    transition:0.3s;
}

.main-content.full{
    margin-left:0;
}

/* ===== TOPBAR ===== */
.topbar{
    height:60px;
    background:#fff;
    border-bottom:1px solid #e5e7eb;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 20px;
    position:sticky;
    top:0;
    z-index:1000;
}

.bell{
    position:relative;
    font-size:22px;
    cursor:pointer;
}
.bell span{
    position:absolute;
    top:-6px;
    right:-8px;
    background:red;
    color:white;
    font-size:12px;
    padding:2px 6px;
    border-radius:50%;
}

/* ===== OVERLAY MOBILE ===== */
.overlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.4);
    z-index:1040;
    display:none;
}
.overlay.show{
    display:block;
}

/* ===== MOBILE MODE ===== */
@media (max-width:992px){
    .sidebar{
        left:-260px;
    }
    .sidebar.show{
        left:0;
    }
    .main-content{
        margin-left:0;
    }
}
</style>
</head>

<body>

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">

    <div class="logo-box">
        <img src="../assets/logoputih.png" alt="Logo">
        <div class="text-white mt-2 small">GLOBAL MOTOR</div>
    </div>

    <a href="dashboard.php" class="<?= active('home') ?>">🏠 Dashboard</a>

    <a data-bs-toggle="collapse" href="#master">📦 Master Data</a>
    <div class="collapse submenu <?= openMenu(['kendaraan_tambah','stok_motor']) ?>" id="master">
        <a href="dashboard.php?page=kredit_tambah" class="<?= active('kredit_tambah') ?>">Tambah Kredit</a>
      <!--  <a href="dashboard.php?page=kendaraan_tambah" class="<?= active('kendaraan_tambah') ?>">Tambah Motor</a> -->
        <a href="dashboard.php?page=stok_motor" class="<?= active('stok_motor') ?>">Stok Motor</a>
    </div>

    <a data-bs-toggle="collapse" href="#trx">💳 Transaksi</a>
    <div class="collapse submenu <?= openMenu(['kredit_tambah','pembayaran']) ?>" id="trx">
        
        <a href="dashboard.php?page=pembayaran" class="<?= active('pembayaran') ?>">Validasi Pembayaran</a>
        <a href="dashboard.php?page=cetak_struk" class="<?= active('cetak_struk') ?>">Cetak Struk</a>
    </div>

    <a data-bs-toggle="collapse" href="#keu">📊 Keuangan</a>
    <div class="collapse submenu <?= openMenu(['laporan_keuangan','simulasi_kredit']) ?>" id="keu">
        <a href="dashboard.php?page=laporan_keuangan" class="<?= active('laporan_keuangan') ?>">Laporan Keuangan</a>
        <a href="dashboard.php?page=simulasi_kredit" class="<?= active('simulasi_kredit') ?>">Simulasi Kredit</a>
    </div>

    <a data-bs-toggle="collapse" href="#user">👤 Manajemen User</a>
    <div class="collapse submenu <?= openMenu(['user_tambah','user_karyawan','user_nasabah','nasabah_detail','nasabah_edit']) ?>" id="user">
        <a href="dashboard.php?page=user_tambah" class="<?= active('user_tambah') ?>">Tambah User</a>
        <a href="dashboard.php?page=user_karyawan" class="<?= active('user_karyawan') ?>">Data Karyawan</a>
        <a href="dashboard.php?page=user_nasabah" class="<?= active('user_nasabah') ?>">Data Nasabah</a>
    </div>

    <a href="dashboard.php?page=audit_log" class="<?= active('audit_log') ?>">🕵️ Audit Log</a>
    <a href="../auth/logout.php" class="text-danger">🚪 Logout</a>
</div>

<!-- MAIN -->
<div class="main-content" id="mainContent">

    <div class="topbar">
        <button class="btn btn-outline-secondary btn-sm" onclick="toggleSidebar()">☰</button>

        <div class="bell" onclick="location.href='dashboard.php?page=pembayaran'">
            🔔
            <?php if($jumlah_notif>0){ ?>
                <span><?= $jumlah_notif ?></span>
            <?php } ?>
        </div>
    </div>

    <div class="container-fluid p-4">
        <?php
        if ($page === 'home') {
            include 'dashboard_home.php';
        } elseif (file_exists($file)) {
            include $file;
        } else {
            include 'dashboard_home.php';
        }
        ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function toggleSidebar(){
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');

    if(window.innerWidth < 992){
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
    } else {
        sidebar.classList.toggle('hide');
        document.getElementById('mainContent').classList.toggle('full');
    }
}
</script>

</body>
</html>
