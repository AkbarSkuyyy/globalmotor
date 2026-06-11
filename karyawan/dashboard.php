<?php
session_start();
require '../config/security.php';

// 1. Proteksi Gerbang Karyawan (Konsisten dengan login & Tanpa .php)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'karyawan') {
    header('Location: ../auth/login');
    exit;
}

include '../config/database.php';

// Ambil data untuk widget statistik
$q_nasabah = mysqli_query($conn, "SELECT COUNT(id) AS total FROM nasabah_profile");
$tot_nasabah = mysqli_fetch_assoc($q_nasabah)['total'] ?? 0;

$q_pending = mysqli_query($conn, "SELECT COUNT(id) AS total FROM pembayaran WHERE status = 'PENDING'");
$tot_pending = mysqli_fetch_assoc($q_pending)['total'] ?? 0;

$bulan_ini = date('Y-m');
$q_sukses = mysqli_query($conn, "SELECT COUNT(id) AS total FROM pembayaran WHERE status = 'VALID' AND DATE_FORMAT(created_at, '%Y-%m') = '$bulan_ini'");
$tot_sukses = mysqli_fetch_assoc($q_sukses)['total'] ?? 0;

// 2. Perbaikan pada bagian LEFT JOIN nasabah_profile (np)
$q_terbaru = mysqli_query($conn, "
    SELECT 
        pb.created_at, 
        p.no_kontrak, 
        COALESCE(np.nama, '-') AS nama, 
        (a.jumlah + pb.kode_unik) AS total_bayar,
        pb.status
    FROM pembayaran pb
    JOIN angsuran a ON pb.angsuran_id = a.id
    JOIN penjualan p ON a.penjualan_id = p.id
    LEFT JOIN nasabah_profile np ON np.no_kontrak = p.no_kontrak 
    ORDER BY pb.created_at DESC LIMIT 5
");

/* CATATAN PENTING UNTUK QUERY DI ATAS: 
Jika di tabel nasabah_profile Anda tidak menggunakan kolom 'no_kontrak' melainkan 'user_id', 
silakan ubah baris LEFT JOIN di atas menjadi:
LEFT JOIN nasabah_profile np ON np.user_id = p.user_id (atau p.nasabah_id sesuai nama kolom Anda).
*/
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Karyawan - Global Motor</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9; 
            color: #334155;
        }
        h1, h2, h3, h4, h5 {
            font-family: 'Montserrat', sans-serif;
        }
        
        /* Kartu Statistik Biasa */
        .stat-card { border: none; border-radius: 12px; }
        
        /* Kartu Aksi Cepat */
        .action-card {
            border: none;
            border-radius: 16px;
            transition: all 0.3s ease;
            background: #ffffff;
            cursor: pointer;
            text-decoration: none !important;
        }
        .action-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
            background: #f8fafc;
        }
        .icon-action {
            width: 80px; 
            height: 80px; 
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px; 
            box-shadow: inset 0 2px 10px rgba(0,0,0,0.05); 
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="fw-bold text-dark">Menu Operasional Karyawan</h3>
                <p class="text-secondary">Pilih aksi cepat di bawah ini untuk mengelola data kendaraan dan transaksi hari ini.</p>
            </div>
        </div>

        <div class="row g-4 mb-5">
            
            <div class="col-md-4">
                <a href="tambah_motor" class="card action-card shadow-sm h-100 p-4 text-center d-block">
                    <div class="icon-action mx-auto mb-3 bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-motorcycle"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Tambah Motor</h5>
                    <p class="text-muted small m-0">Input data spesifikasi kendaraan bermotor yang baru masuk ke sistem.</p>
                </a>
            </div>

            <div class="col-md-4">
                <a href="tambah_kredit" class="card action-card shadow-sm h-100 p-4 text-center d-block">
                    <div class="icon-action mx-auto mb-3 bg-success bg-opacity-10 text-success">
                        <i class="fa-solid fa-file-contract"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Tambah Kredit</h5>
                    <p class="text-muted small m-0">Input data transaksi kontrak kredit baru untuk nasabah.</p>
                </a>
            </div>

            <div class="col-md-4">
                <a href="stok_motor" class="card action-card shadow-sm h-100 p-4 text-center d-block">
                    <div class="icon-action mx-auto mb-3 bg-warning bg-opacity-10 text-warning">
                        <i class="fa-solid fa-warehouse"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Stok Motor</h5>
                    <p class="text-muted small m-0">Lihat daftar ketersediaan motor, status unit ready, atau sudah terjual.</p>
                </a>
            </div>

        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card stat-card shadow-sm p-3 border-start border-primary border-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div><p class="text-muted fw-bold mb-0 small">TOTAL NASABAH</p></div>
                        <h4 class="fw-bold mb-0 text-dark"><?= $tot_nasabah ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card shadow-sm p-3 border-start border-warning border-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div><p class="text-muted fw-bold mb-0 small">MENUNGGU VALIDASI</p></div>
                        <h4 class="fw-bold mb-0 text-dark"><?= $tot_pending ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card shadow-sm p-3 border-start border-success border-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div><p class="text-muted fw-bold mb-0 small">TRANSAKSI SUKSES (BULAN INI)</p></div>
                        <h4 class="fw-bold mb-0 text-dark"><?= $tot_sukses ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="row pb-5">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="fw-bold m-0 text-dark">Aktivitas Transaksi Terbaru</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-3 px-4">Waktu</th>
                                    <th>No Kontrak</th>
                                    <th>Nama Nasabah</th>
                                    <th class="text-end">Jumlah Bayar</th>
                                    <th class="text-center px-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (mysqli_num_rows($q_terbaru) > 0) {
                                    while($row = mysqli_fetch_assoc($q_terbaru)){ 
                                        $status_badge = $row['status'] === 'VALID' ? 'bg-success text-white' : ($row['status'] === 'PENDING' ? 'bg-warning text-dark' : 'bg-danger text-white');
                                ?>
                                <tr>
                                    <td class="px-4 text-secondary small">
                                        <i class="fa-regular fa-clock me-1"></i> <?= date('d M Y, H:i', strtotime($row['created_at'])) ?>
                                    </td>
                                    <td class="fw-bold text-secondary"><?= htmlspecialchars($row['no_kontrak']) ?></td>
                                    <td class="fw-medium text-dark"><?= htmlspecialchars($row['nama']) ?></td>
                                    <td class="text-end fw-bold text-primary">Rp <?= number_format($row['total_bayar'], 0, ',', '.') ?></td>
                                    <td class="text-center px-4">
                                        <span class="badge rounded-pill border <?= $status_badge ?> px-3 py-1" style="font-size: 11px;">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php 
                                    } 
                                } else {
                                ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted fst-italic">
                                        <i class="fa-solid fa-inbox fs-3 mb-2 d-block opacity-50"></i> Belum ada data transaksi yang masuk.
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>