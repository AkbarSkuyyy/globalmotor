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

<div class="container-fluid mt-4">
    <div class="d-flex align-items-center mb-4">
        <h4 class="fw-bold m-0"><i class="bi bi-shield-check text-warning me-2"></i>Validasi Pembayaran</h4>
        <span class="badge bg-warning text-dark ms-3 rounded-pill"><?= mysqli_num_rows($pembayaran) ?> Menunggu</span>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">No Kontrak</th>
                            <th>Nasabah</th>
                            <th>Bulan</th>
                            <th>Tagihan</th>
                            <th>Kode Unik</th>
                            <th>Total Transfer</th>
                            <th>Bukti</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($pembayaran) == 0): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-check-circle fs-2 d-block mb-2 text-success"></i>
                                Semua pembayaran sudah divalidasi.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php while ($p = mysqli_fetch_assoc($pembayaran)): 
                            $total = $p['jumlah'] + $p['kode_unik'];
                        ?>
                        <tr>
                            <td class="ps-4 font-monospace fw-bold text-secondary"><?= $p['no_kontrak'] ?></td>
                            <td class="fw-medium"><?= $p['nama'] ?></td>
                            <td><span class="badge bg-info text-dark rounded-pill px-3">Ke-<?= $p['bulan_ke'] ?></span></td>
                            <td class="text-muted"><?= rupiah($p['jumlah']) ?></td>
                            <td class="text-secondary"><?= $p['kode_unik'] ?></td>
                            <td class="fw-bold text-primary"><?= rupiah($total) ?></td>
                            <td>
                                <a href="../assets/bukti/<?= $p['bukti'] ?>" target="_blank" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                                    <i class="bi bi-image me-1"></i> Lihat
                                </a>
                            </td>
                            <td class="text-center pe-4">
                                <a href="dashboard.php?page=pembayaran_valid&id=<?= $p['id'] ?>&angsuran=<?= $p['angsuran_id'] ?>"
                                   class="btn btn-success btn-sm rounded-pill px-4 shadow-sm"
                                   onclick="return confirm('Validasi pembayaran ini?')">
                                    <i class="bi bi-check2-circle me-1"></i> Valid
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>