<?php
include '../config/database.php';

date_default_timezone_set('Asia/Jakarta');

// ===== SUMMARY DATA =====
$total_motor = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) total FROM kendaraan WHERE status='READY'"
))['total'];

$kredit_aktif = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) total FROM penjualan"
))['total'];

$nasabah_aktif = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) total FROM users WHERE role='nasabah' AND status='AKTIF'"
))['total'];

$pending = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) total FROM pembayaran WHERE status='PENDING'"
))['total'];

// ===== TOTAL PEMASUKAN BULAN INI =====
$bulan_ini = date('Y-m');
$pemasukan = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT SUM(a.jumlah + pb.kode_unik) total
    FROM pembayaran pb
    JOIN angsuran a ON pb.angsuran_id=a.id
    WHERE pb.status='VALID'
    AND DATE_FORMAT(pb.created_at,'%Y-%m')='$bulan_ini'
"))['total'] ?? 0;

// ===== DATA GRAFIK REAL =====
$grafik = mysqli_query($conn,"
    SELECT 
        DATE_FORMAT(pb.created_at,'%Y-%m') AS bulan,
        SUM(a.jumlah + pb.kode_unik) AS total
    FROM pembayaran pb
    JOIN angsuran a ON pb.angsuran_id=a.id
    WHERE pb.status='VALID'
    GROUP BY DATE_FORMAT(pb.created_at,'%Y-%m')
    ORDER BY bulan ASC
");

$labels = [];
$data_grafik = [];

while($g = mysqli_fetch_assoc($grafik)){
    $labels[] = date('M Y', strtotime($g['bulan'].'-01'));
    $data_grafik[] = (int)$g['total'];
}

// ===== AKTIVITAS =====
$log = mysqli_query($conn,"
    SELECT * FROM audit_logs
    ORDER BY created_at DESC
    LIMIT 5
");
?>

<div class="container-fluid">

<div class="mb-4">
    <h4 class="fw-bold">Selamat Datang 👋</h4>
    <small class="text-muted">
        Ringkasan performa GLOBAL MOTOR bulan <?= date('F Y') ?>
    </small>
</div>

<div class="row g-4 mb-4">

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Motor Ready</small>
                <h3 class="fw-bold"><?= $total_motor ?></h3>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Kredit Aktif</small>
                <h3 class="fw-bold text-primary"><?= $kredit_aktif ?></h3>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Nasabah Aktif</small>
                <h3 class="fw-bold text-success"><?= $nasabah_aktif ?></h3>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Pembayaran Pending</small>
                <h3 class="fw-bold text-danger"><?= $pending ?></h3>
            </div>
        </div>
    </div>

</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3">Total Pemasukan Bulan Ini</h6>
        <h3 class="text-success fw-bold">
            Rp <?= number_format($pemasukan,0,',','.') ?>
        </h3>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3">Grafik Pemasukan (Real Data)</h6>
        <canvas id="grafikKeuangan" height="90"></canvas>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
<div class="card-body">
<h6 class="fw-bold mb-3">Aktivitas Terakhir</h6>

<div class="list-group list-group-flush">
<?php while($a = mysqli_fetch_assoc($log)) { ?>
    <div class="list-group-item border-0 px-0">
        <div class="d-flex justify-content-between">
            <div>
                <strong><?= $a['aksi'] ?></strong>
            </div>
            <small class="text-muted">
                <?= date('d M Y H:i', strtotime($a['created_at'])) ?>
            </small>
        </div>
    </div>
<?php } ?>
</div>

</div>
</div>

<div class="card border-0 shadow-sm">
<div class="card-body">
<h6 class="fw-bold mb-3">Aksi Cepat</h6>

<a href="dashboard.php?page=kredit_tambah"
   class="btn btn-primary me-2 mb-2">
    ➕ Tambah Kredit
</a>

<a href="dashboard.php?page=kendaraan_tambah"
   class="btn btn-success me-2 mb-2">
    🏍️ Tambah Motor
</a>

<a href="dashboard.php?page=user_tambah"
   class="btn btn-secondary mb-2">
    👤 Tambah User
</a>

</div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('grafikKeuangan');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Pemasukan',
            data: <?= json_encode($data_grafik) ?>,
            borderWidth: 3,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        }
    }
});
</script>