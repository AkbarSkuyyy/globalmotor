<?php
session_start();
require '../config/security.php';

if ($_SESSION['role'] !== 'nasabah') {
    header('Location: ../auth/login.php');
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
    <style>
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; }
        .card { border-radius: 1rem; }
    </style>
</head>
<body>

<div class="container mt-4 mb-5" style="max-width: 1000px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0"><i class="bi bi-clock-history text-primary me-2"></i>Riwayat Pembayaran</h4>
        <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-2"></i>Kembali
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-4 p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Bulan</th>
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
                ?>
                    <tr>
                        <td class="ps-4">
                            <span class="badge bg-info text-dark rounded-pill px-3">Ke-<?= htmlspecialchars($r['bulan_ke']) ?></span>
                        </td>
                        <td class="text-muted"><?= rupiah($r['jumlah_angsuran']) ?></td>
                        <td class="text-secondary"><?= htmlspecialchars($r['kode_unik']) ?></td>
                        <td class="fw-bold text-primary"><?= rupiah($total) ?></td>
                        <td>
                            <?php if($r['status'] == 'VALID'){ ?>
                                <span class="badge bg-success rounded-pill px-3"><i class="bi bi-check-circle me-1"></i>VALID</span>
                            <?php } else { ?>
                                <span class="badge bg-warning text-dark rounded-pill px-3"><i class="bi bi-hourglass-split me-1"></i>PENDING</span>
                            <?php } ?>
                        </td>
                        <td class="pe-4 text-secondary small">
                            <div class="fw-semibold"><?= date('d M Y', strtotime($r['waktu_upload'])) ?></div>
                            <div><?= date('H:i', strtotime($r['waktu_upload'])) ?> WIB</div>
                        </td>
                    </tr>
                <?php 
                    } 
                } else {
                    echo '<tr><td colspan="6" class="text-center py-5 text-muted">Belum ada riwayat pembayaran yang diupload.</td></tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>