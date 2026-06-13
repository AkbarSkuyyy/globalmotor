<?php
session_start();
require '../config/security.php';

// 1. Perbaikan Keamanan Sesi dan Clean URL
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nasabah') {
    header('Location: ../auth/login');
    exit;
}

include '../config/database.php';

$no_kontrak = $_SESSION['username'] ?? null;

if (!$no_kontrak) {
    $uid = $_SESSION['user_id'];
    $u = mysqli_fetch_assoc(mysqli_query($conn,"SELECT username FROM users WHERE id='$uid'"));
    $no_kontrak = $u['username'];
}

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
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Riwayat Pembayaran</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    
    <style>
        body { 
            background-color: #f1f5f9; 
            font-family: 'Inter', sans-serif; 
            color: #334155;
        }
        h4 { font-family: 'Montserrat', sans-serif; }
        .card { border-radius: 1rem; }
    </style>
</head>
<body>

<div class="container mt-4 mb-5" style="max-width: 1000px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0"><i class="bi bi-clock-history text-primary me-2"></i>Riwayat Pembayaran</h4>
        <a href="dashboard" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-2"></i>Kembali
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-4 p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 py-3">Bulan</th>
                        <th>Tagihan</th>
                        <th>Kode Unik</th>
                        <th>Total Transfer</th>
                        <th>Status</th>
                        <th class="pe-4">Waktu</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ($data && mysqli_num_rows($data) > 0) {
                    while($r = mysqli_fetch_assoc($data)){
                        $total = $r['jumlah_angsuran'] + $r['kode_unik'];
                        
                        // 2. Logika Status Cerdas (Termasuk Status Ditolak)
                        if ($r['status'] == 'VALID') {
                            $badge = '<span class="badge bg-success rounded-pill px-3 py-2"><i class="bi bi-check-circle me-1"></i>VALID</span>';
                        } elseif ($r['status'] == 'DITOLAK') {
                            $badge = '<span class="badge bg-danger rounded-pill px-3 py-2"><i class="bi bi-x-circle me-1"></i>DITOLAK</span>';
                        } else {
                            $badge = '<span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="bi bi-hourglass-split me-1"></i>PENDING</span>';
                        }
                ?>
                    <tr>
                        <td class="ps-4">
                            <span class="badge bg-info bg-opacity-10 text-info border border-info rounded-pill px-3 py-2">
                                Ke-<?= htmlspecialchars($r['bulan_ke']) ?>
                            </span>
                        </td>
                        <td class="text-muted fw-medium"><?= rupiah($r['jumlah_angsuran']) ?></td>
                        <td class="text-secondary font-monospace">
                            <?= $r['kode_unik'] == 0 ? '-' : htmlspecialchars($r['kode_unik']) ?>
                        </td>
                        <td class="fw-bold text-primary"><?= rupiah($total) ?></td>
                        <td><?= $badge ?></td>
                        <td class="pe-4 text-secondary small">
                            <div class="fw-semibold text-dark"><?= date('d M Y', strtotime($r['waktu_upload'])) ?></div>
                            <div><?= date('H:i', strtotime($r['waktu_upload'])) ?> WIB</div>
                        </td>
                    </tr>
                <?php 
                    } 
                } else {
                    echo '<tr><td colspan="6" class="text-center py-5 text-muted fst-italic"><i class="bi bi-receipt fs-2 d-block mb-2 opacity-50"></i>Belum ada riwayat pembayaran yang diupload.</td></tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>