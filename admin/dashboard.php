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
    'nasabah_detail','nasabah_edit',
    'user_edit', 
    'user_reset',      
    'user_toggle',
    'user_hapus',    
    'audit_log',
    'pengaturan_umum','pengaturan_user','pengaturan_backup'
];

if (!in_array($page, $allowed)) {
    $page = 'home';
}

$file = $page . '.php';

// Fungsi penanda menu aktif
function active($p){
    return (($_GET['page'] ?? 'home') === $p) ? 'active' : '';
}

// Fungsi pembuka submenu
function openMenu($arr){
    return in_array($_GET['page'] ?? 'home', $arr) ? 'show' : '';
}

// Fungsi cek aria-expanded untuk animasi panah
function isExpanded($arr){
    return in_array($_GET['page'] ?? 'home', $arr) ? 'true' : 'false';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - GLOBAL MOTOR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --sidebar-bg: #1e293b; /* Slate 800 */
            --sidebar-hover: #334155; /* Slate 700 */
            --sidebar-text: #94a3b8; /* Slate 400 */
            --primary-color: #3b82f6; /* Blue 500 */
        }

        body {
            background: #f1f5f9;
            overflow-x: hidden;
            font-family: 'Inter', sans-serif;
        }

        /* ===== SIDEBAR PREMIUM ===== */
        .sidebar {
            width: 280px;
            min-height: 100vh;
            background: var(--sidebar-bg);
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1050;
            transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 15px rgba(0,0,0,0.05);
        }

        .sidebar.hide {
            left: -280px;
        }

        .sidebar-scroll {
            overflow-y: auto;
            flex-grow: 1;
            padding-bottom: 30px;
        }
        
        /* Custom Scrollbar Sidebar */
        .sidebar-scroll::-webkit-scrollbar { width: 5px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: #475569; border-radius: 10px; }

        /* ===== LOGO AREA ===== */
        .logo-box {
            text-align: center;
            padding: 30px 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            margin-bottom: 15px;
        }
        .logo-box img {
            width: 100px;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));
        }
        .logo-text {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 1.5px;
            margin-top: 12px;
            font-size: 15px;
        }

        /* ===== MENU LINKS ===== */
        .nav-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 15px 25px 5px;
        }

        .sidebar a {
            color: var(--sidebar-text);
            text-decoration: none;
            padding: 12px 20px;
            margin: 4px 16px;
            display: flex;
            align-items: center;
            border-radius: 10px;
            transition: all 0.2s ease;
            font-weight: 500;
            font-size: 14.5px;
        }

        .sidebar a i.menu-icon {
            width: 24px;
            font-size: 16px;
            text-align: center;
            margin-right: 12px;
            transition: 0.2s;
        }

        .sidebar a:hover {
            background: var(--sidebar-hover);
            color: #ffffff;
        }
        .sidebar a:hover i.menu-icon {
            transform: scale(1.1);
        }

        /* Active State */
        .sidebar a.active {
            background: var(--primary-color);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
            font-weight: 600;
        }

        /* Dropdown Chevron Animasi */
        .sidebar a .chevron-icon {
            margin-left: auto;
            font-size: 12px;
            transition: transform 0.3s ease;
        }
        .sidebar a[aria-expanded="true"] .chevron-icon {
            transform: rotate(90deg);
        }
        .sidebar a[aria-expanded="true"] {
            color: #ffffff;
        }

        /* Submenu */
        .submenu {
            margin-bottom: 5px;
        }
        .submenu a {
            padding: 10px 20px 10px 50px;
            margin: 2px 16px;
            font-size: 13.5px;
            background: transparent !important;
            box-shadow: none !important;
        }
        .submenu a:hover {
            color: #ffffff;
        }
        .submenu a.active {
            color: var(--primary-color);
            font-weight: 600;
        }
        .submenu a.active::before {
            content: '•';
            color: var(--primary-color);
            position: absolute;
            left: 32px;
            font-size: 20px;
            line-height: 10px;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: 280px;
            transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-content.full {
            margin-left: 0;
        }

        /* ===== TOPBAR ===== */
        .topbar {
            height: 70px;
            background: #ffffff;
            box-shadow: 0 1px 10px rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .btn-toggle {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
            border-radius: 8px;
            padding: 6px 12px;
            transition: 0.2s;
        }
        .btn-toggle:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .bell-wrapper {
            position: relative;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            transition: 0.2s;
            border: 1px solid #e2e8f0;
        }
        .bell-wrapper:hover {
            background: #e2e8f0;
        }
        .bell-wrapper i {
            font-size: 18px;
        }
        .bell-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: bold;
            padding: 3px 6px;
            border-radius: 50%;
            border: 2px solid #ffffff;
        }

        /* ===== OVERLAY MOBILE ===== */
        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(2px);
            z-index: 1040;
            display: none;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .overlay.show {
            display: block;
            opacity: 1;
        }

      /* ===== MOBILE MODE ===== */
        @media (max-width: 992px){
            .sidebar { left: -280px; }
            .sidebar.show { left: 0; }
            .main-content { margin-left: 0; }

            /* 1. Sembunyikan area background Topbar agar konten terkesan full */
            .topbar {
                position: absolute;
                width: 100%;
                background: transparent !important;
                box-shadow: none !important;
                pointer-events: none; /* Memastikan area kosong tembus klik ke konten di bawahnya */
                z-index: 1040;
            }

            /* 2. Jadikan tombol Hamburger & Notifikasi melayang (Floating Button) */
            .topbar .btn-toggle, 
            .topbar .bell-wrapper {
                pointer-events: auto; /* Mengaktifkan kembali sentuhan khusus untuk tombol */
                background: rgba(255, 255, 255, 0.9) !important; /* Putih sedikit transparan */
                backdrop-filter: blur(5px); /* Efek kaca kekinian */
                box-shadow: 0 4px 15px rgba(0,0,0,0.12) !important;
                border: 1px solid rgba(226, 232, 240, 0.8) !important;
            }

            /* 3. Beri sedikit jarak atas agar judul konten tidak persis tertutup tombol */
            .main-content .container-fluid {
                padding-top: 80px !important; 
            }
        }
    </style>
</head>

<body>

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<div class="sidebar" id="sidebar">

    <div class="logo-box">
        <img src="../assets/logoputih.png" alt="Logo">
        <div class="logo-text">GLOBAL MOTOR</div>
    </div>

    <div class="sidebar-scroll">
        <div class="nav-label">Menu Utama</div>
        
        <a href="dashboard.php" class="<?= active('home') ?>">
            <i class="fa-solid fa-house menu-icon"></i> Dashboard
        </a>

        <a data-bs-toggle="collapse" href="#master" aria-expanded="<?= isExpanded(['kendaraan_tambah','stok_motor','kredit_tambah']) ?>" role="button">
            <i class="fa-solid fa-box-open menu-icon"></i> Master Data
            <i class="fa-solid fa-chevron-right chevron-icon"></i>
        </a>
        <div class="collapse submenu <?= openMenu(['kendaraan_tambah','stok_motor','kredit_tambah']) ?>" id="master">
            <a href="dashboard.php?page=kredit_tambah" class="<?= active('kredit_tambah') ?>">Tambah Kredit</a>
            <a href="dashboard.php?page=stok_motor" class="<?= active('stok_motor') ?>">Stok Motor</a>
        </div>

        <a data-bs-toggle="collapse" href="#trx" aria-expanded="<?= isExpanded(['pembayaran','cetak_struk']) ?>" role="button">
            <i class="fa-solid fa-file-invoice-dollar menu-icon"></i> Transaksi
            <i class="fa-solid fa-chevron-right chevron-icon"></i>
        </a>
        <div class="collapse submenu <?= openMenu(['pembayaran','cetak_struk']) ?>" id="trx">
            <a href="dashboard.php?page=pembayaran" class="<?= active('pembayaran') ?>">Validasi Pembayaran</a>
            <a href="dashboard.php?page=cetak_struk" class="<?= active('cetak_struk') ?>">Cetak Struk</a>
        </div>

        <a data-bs-toggle="collapse" href="#keu" aria-expanded="<?= isExpanded(['laporan_keuangan','simulasi_kredit']) ?>" role="button">
            <i class="fa-solid fa-wallet menu-icon"></i> Keuangan
            <i class="fa-solid fa-chevron-right chevron-icon"></i>
        </a>
        <div class="collapse submenu <?= openMenu(['laporan_keuangan','simulasi_kredit']) ?>" id="keu">
            <a href="dashboard.php?page=laporan_keuangan" class="<?= active('laporan_keuangan') ?>">Laporan Keuangan</a>
            <a href="dashboard.php?page=simulasi_kredit" class="<?= active('simulasi_kredit') ?>">Simulasi Kredit</a>
        </div>

        <div class="nav-label">Sistem & Akses</div>

        <a data-bs-toggle="collapse" href="#user" aria-expanded="<?= isExpanded(['user_tambah','user_karyawan','user_nasabah','nasabah_detail','nasabah_edit']) ?>" role="button">
            <i class="fa-solid fa-users-gear menu-icon"></i> Manajemen User
            <i class="fa-solid fa-chevron-right chevron-icon"></i>
        </a>
        <div class="collapse submenu <?= openMenu(['user_tambah','user_karyawan','user_nasabah','nasabah_detail','nasabah_edit']) ?>" id="user">
            <a href="dashboard.php?page=user_tambah" class="<?= active('user_tambah') ?>">Tambah User</a>
            <a href="dashboard.php?page=user_karyawan" class="<?= active('user_karyawan') ?>">Data Karyawan</a>
            <a href="dashboard.php?page=user_nasabah" class="<?= active('user_nasabah') ?>">Data Nasabah</a>
        </div>

        <a href="dashboard.php?page=audit_log" class="<?= active('audit_log') ?>">
            <i class="fa-solid fa-shield-halved menu-icon"></i> Audit Log
        </a>

        <div class="mx-3 my-3 border-top" style="border-color: rgba(255,255,255,0.05) !important;"></div>

        <a href="../auth/logout.php" class="text-danger fw-bold hover-danger">
            <i class="fa-solid fa-right-from-bracket menu-icon"></i> Logout
        </a>
    </div>
</div>

<div class="main-content" id="mainContent">

    <div class="topbar">
        <button class="btn-toggle" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="bell-wrapper shadow-sm" onclick="location.href='dashboard.php?page=pembayaran'" title="Notifikasi Validasi Pembayaran">
            <i class="fa-regular fa-bell"></i>
            <?php if($jumlah_notif > 0){ ?>
                <span class="bell-badge"><?= $jumlah_notif ?></span>
            <?php } ?>
        </div>
    </div>

    <div class="container-fluid p-4">
    <?php
    $file = $page . '.php';
    
    // Pastikan file benar-benar ada di folder tersebut
    if (file_exists($file)) {
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
        // Mode Mobile
        sidebar.classList.toggle('show');
        if(sidebar.classList.contains('show')){
            overlay.classList.add('show');
        } else {
            overlay.classList.remove('show');
        }
    } else {
        // Mode Desktop
        sidebar.classList.toggle('hide');
        document.getElementById('mainContent').classList.toggle('full');
    }
}
</script>

</body>
</html>