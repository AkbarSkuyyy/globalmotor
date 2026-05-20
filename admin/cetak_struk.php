<?php
require '../config/security.php';
if (!in_array($_SESSION['role'], ['admin', 'karyawan'])) exit;

include '../config/database.php';

$data = mysqli_query($conn, "
SELECT 
    pb.id,
    pb.created_at,
    p.no_kontrak,
    COALESCE(np.nama, '-') nama,
    a.bulan_ke,
    a.jumlah,
    pb.kode_unik
FROM pembayaran pb
JOIN angsuran a ON pb.angsuran_id = a.id
JOIN penjualan p ON a.penjualan_id = p.id
LEFT JOIN nasabah_profile np ON np.no_kontrak = p.no_kontrak
WHERE pb.status = 'VALID'
ORDER BY pb.created_at DESC
");
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="bi bi-printer-fill text-primary me-2"></i> Cetak Struk Pembayaran</h4>
            <p class="text-muted small">Daftar transaksi pembayaran yang sudah tervalidasi.</p>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3">No</th>
                            <th class="py-3">Tanggal</th>
                            <th class="py-3">Nama Nasabah</th>
                            <th class="py-3">No Kontrak</th>
                            <th class="py-3">Bulan</th>
                            <th class="py-3">Total Pembayaran</th>
                            <th class="pe-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($r = mysqli_fetch_assoc($data)) { 
                            $total = $r['jumlah'] + $r['kode_unik'];
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold text-secondary"><?= $no++ ?></td>
                            <td>
                                <div class="small fw-semibold"><?= date('d M Y', strtotime($r['created_at'])) ?></div>
                                <div class="text-muted small"><?= date('H:i', strtotime($r['created_at'])) ?> WIB</div>
                            </td>
                            <td class="fw-medium text-dark"><?= $r['nama'] ?></td>
                            <td><span class="badge bg-light text-dark border font-monospace"><?= $r['no_kontrak'] ?></span></td>
                            <td><span class="badge rounded-pill bg-info text-dark">Ke-<?= $r['bulan_ke'] ?></span></td>
                            <td><span class="text-success fw-bold">Rp <?= number_format($total, 0, ',', '.') ?></span></td>
                            <td class="pe-4 text-center">
                                <a href="struk_print.php?id=<?= $r['id'] ?>" 
                                   class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm"
                                   onclick="printStruk(this.href); return false;">
                                    <i class="bi bi-printer me-1"></i> Cetak
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

<script>
function printStruk(url) {
    var iframe = document.createElement('iframe');
    iframe.style.display = "none";
    iframe.src = url;
    document.body.appendChild(iframe);
}
</script>