<?php
if (!in_array($_SESSION['role'], ['admin','karyawan'])) exit;
include '../config/database.php';
date_default_timezone_set('Asia/Jakarta');

/*
|--------------------------------------------------------------------------
| FILTER TANGGAL SINKRONISASI
|--------------------------------------------------------------------------
*/
$tgl_awal  = !empty($_GET['tgl_awal']) ? mysqli_real_escape_string($conn, $_GET['tgl_awal']) : date('Y-m-01');
$tgl_akhir = !empty($_GET['tgl_akhir']) ? mysqli_real_escape_string($conn, $_GET['tgl_akhir']) : date('Y-m-t');

// Filter query sudah fix untuk ke-3 file
$where = "WHERE p.status='VALID' 
          AND p.created_at >= '$tgl_awal 00:00:00' 
          AND p.created_at <= '$tgl_akhir 23:59:59'";

$query = mysqli_query($conn,"
    SELECT 
        p.created_at AS tgl_bayar,
        p.kode_unik,
        pj.no_kontrak,
        COALESCE(np.nama, '-') AS nama_nasabah,
        a.bulan_ke,
        a.jumlah AS angsuran_pokok
    FROM pembayaran p
    JOIN angsuran a ON p.angsuran_id = a.id
    JOIN penjualan pj ON a.penjualan_id = pj.id
    LEFT JOIN nasabah_profile np ON np.no_kontrak = pj.no_kontrak
    $where
    ORDER BY p.created_at DESC
");

$total = 0;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold m-0 text-dark">Laporan Keuangan</h4>
</div>

<form method="GET" class="row g-3 mb-4 bg-white p-3 rounded-4 shadow-sm border-0">
    <input type="hidden" name="page" value="laporan_keuangan">
    
    <div class="col-md-4">
        <label class="form-label small fw-bold text-secondary">Dari Tanggal</label>
        <input type="date" name="tgl_awal" value="<?= htmlspecialchars($tgl_awal) ?>" class="form-control rounded-3 border-secondary" required>
    </div>
    
    <div class="col-md-4">
        <label class="form-label small fw-bold text-secondary">Sampai Tanggal</label>
        <input type="date" name="tgl_akhir" value="<?= htmlspecialchars($tgl_akhir) ?>" class="form-control rounded-3 border-secondary" required>
    </div>
    
    <div class="col-md-4 d-flex align-items-end gap-2">
        <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold py-2 shadow-sm">
            <i class="bi bi-funnel-fill"></i> Terapkan Filter
        </button>
    </div>
</form>

<div class="card shadow-sm border-0 rounded-4 mb-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0 align-middle bg-white">
            <thead class="table-dark">
                <tr class="text-center">
                    <th class="py-3" style="width: 5%;">No</th>
                    <th class="py-3">Tanggal Waktu</th>
                    <th class="py-3">No Kontrak</th>
                    <th class="py-3 text-start">Nama Nasabah</th>
                    <th class="py-3">Bulan Ke</th>
                    <th class="py-3 text-end">Nominal Pokok</th>
                    <th class="text-end py-3">Total Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                while($r = mysqli_fetch_assoc($query)){
                    $jumlah = $r['angsuran_pokok'] + $r['kode_unik'];
                    $total += $jumlah;
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td class="text-center"><?= date('d-m-Y H:i', strtotime($r['tgl_bayar'])) ?> WIB</td>
                    <td class="text-center"><span class="badge bg-secondary"><?= htmlspecialchars($r['no_kontrak']) ?></span></td>
                    <td class="fw-bold text-dark"><?= htmlspecialchars($r['nama_nasabah']) ?></td>
                    <td class="text-center"><?= $r['bulan_ke'] ?></td>
                    <td class="text-end">Rp <?= number_format($r['angsuran_pokok'], 0, ',', '.') ?></td>
                    <td class="text-end fw-bold text-success">Rp <?= number_format($jumlah, 0, ',', '.') ?></td>
                </tr>
                <?php } ?>

                <?php if($no == 1){ ?>
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted fst-italic">
                        📭 Belum ada transaksi kas pada periode ini.
                    </td>
                </tr>
                <?php } ?>
            </tbody>
            <tfoot class="bg-light border-top">
                <tr>
                    <th colspan="6" class="text-end py-3 fs-6">TOTAL PENDAPATAN :</th>
                    <th class="text-end py-3 fs-5 fw-bold text-primary">Rp <?= number_format($total, 0, ',', '.') ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="d-flex gap-3 mb-5">
    <a target="_blank"
       href="laporan_keuangan_pdf.php?tgl_awal=<?= urlencode($tgl_awal) ?>&tgl_akhir=<?= urlencode($tgl_akhir) ?>"
       class="btn btn-danger fw-bold rounded-3 px-4 py-2 shadow-sm">
       <i class="bi bi-file-earmark-pdf-fill me-1"></i> Download PDF
    </a>

    <a target="_blank"
       href="laporan_keuangan_excel.php?tgl_awal=<?= urlencode($tgl_awal) ?>&tgl_akhir=<?= urlencode($tgl_akhir) ?>"
       class="btn btn-success fw-bold rounded-3 px-4 py-2 shadow-sm">
       <i class="bi bi-file-earmark-excel-fill me-1"></i> Export Excel
    </a>
</div>