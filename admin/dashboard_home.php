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

// ==========================================
// LOGIKA FILTER GRAFIK (HARIAN / BULANAN)
// ==========================================
$filter_grafik = $_GET['grafik'] ?? 'bulanan';
$labels = [];
$data_grafik = [];

if ($filter_grafik === 'harian') {
    // Tampilkan data per HARI untuk bulan ini
    $judul_grafik = "Grafik Pemasukan Harian (Bulan Ini)";
    $grafik = mysqli_query($conn,"
        SELECT 
            DATE(pb.created_at) AS tanggal,
            SUM(a.jumlah + pb.kode_unik) AS total
        FROM pembayaran pb
        JOIN angsuran a ON pb.angsuran_id=a.id
        WHERE pb.status='VALID' 
        AND DATE_FORMAT(pb.created_at, '%Y-%m') = '$bulan_ini'
        GROUP BY DATE(pb.created_at)
        ORDER BY tanggal ASC
    ");
    while($g = mysqli_fetch_assoc($grafik)){
        $labels[] = date('d M Y', strtotime($g['tanggal']));
        $data_grafik[] = (int)$g['total'];
    }
} else {
    // Tampilkan data per BULAN untuk tahun ini
    $tahun_ini = date('Y');
    $judul_grafik = "Grafik Pemasukan Bulanan (Tahun $tahun_ini)";
    $grafik = mysqli_query($conn,"
        SELECT 
            DATE_FORMAT(pb.created_at,'%Y-%m') AS bulan,
            SUM(a.jumlah + pb.kode_unik) AS total
        FROM pembayaran pb
        JOIN angsuran a ON pb.angsuran_id=a.id
        WHERE pb.status='VALID'
        AND YEAR(pb.created_at) = '$tahun_ini'
        GROUP BY DATE_FORMAT(pb.created_at,'%Y-%m')
        ORDER BY bulan ASC
    ");
    while($g = mysqli_fetch_assoc($grafik)){
        $labels[] = date('M Y', strtotime($g['bulan'].'-01'));
        $data_grafik[] = (int)$g['total'];
    }
}

// ===== AKTIVITAS =====
$log = mysqli_query($conn,"
    SELECT * FROM audit_logs
    ORDER BY created_at DESC
    LIMIT 5
");
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    /* Styling List Aktivitas */
    .activity-item {
        transition: all 0.2s ease-in-out;
        border-radius: 12px;
    }
    .activity-item:hover {
        background-color: #f8fafc;
        transform: translateX(5px);
    }
    .activity-icon {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    /* Styling Tombol Menu Aksi Cepat */
    .action-btn-custom {
        display: flex;
        align-items: center;
        padding: 16px;
        border-radius: 16px;
        text-decoration: none;
        transition: all 0.3s ease;
        background: #ffffff;
        border: 1px solid #e2e8f0;
    }
    .action-btn-custom:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.06);
        border-color: #cbd5e1;
    }
    .action-icon-box {
        width: 50px;
        height: 50px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        margin-right: 16px;
    }
    .action-btn-custom .fa-chevron-right {
        transition: transform 0.3s ease;
    }
    .action-btn-custom:hover .fa-chevron-right {
        transform: translateX(4px);
        color: #3b82f6 !important;
    }
    
    /* Styling Ikon Summary Box */
    .summary-icon {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
</style>

<div class="container-fluid pt-2">

    <div class="mb-4">
        <h3 class="fw-bold text-dark m-0">Selamat Datang, Admin 👋</h3>
        <p class="text-secondary small m-0">
            Ringkasan performa GLOBAL MOTOR bulan <?= date('F Y') ?>
        </p>
    </div>

    <div class="row g-4 mb-4">
        
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 rounded-4 border-start border-4 border-secondary">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted fw-bold mb-1 small text-uppercase">Motor Ready</p>
                        <h3 class="fw-bold m-0 text-dark"><?= number_format($total_motor, 0, ',', '.') ?></h3>
                    </div>
                    <div class="summary-icon bg-secondary bg-opacity-10 text-secondary">
                        <i class="fa-solid fa-motorcycle"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 rounded-4 border-start border-4 border-primary">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted fw-bold mb-1 small text-uppercase">Kredit Aktif</p>
                        <h3 class="fw-bold m-0 text-primary"><?= number_format($kredit_aktif, 0, ',', '.') ?></h3>
                    </div>
                    <div class="summary-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-file-contract"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 rounded-4 border-start border-4 border-success">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted fw-bold mb-1 small text-uppercase">Nasabah Aktif</p>
                        <h3 class="fw-bold m-0 text-success"><?= number_format($nasabah_aktif, 0, ',', '.') ?></h3>
                    </div>
                    <div class="summary-icon bg-success bg-opacity-10 text-success">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 rounded-4 border-start border-4 border-danger">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted fw-bold mb-1 small text-uppercase">Validasi Pending</p>
                        <h3 class="fw-bold m-0 text-danger"><?= number_format($pending, 0, ',', '.') ?></h3>
                    </div>
                    <div class="summary-icon bg-danger bg-opacity-10 text-danger">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                        <div>
                            <p class="text-muted fw-bold mb-1 small text-uppercase">Total Pemasukan</p>
                            <h2 class="text-success fw-bold m-0">Rp <?= number_format($pemasukan, 0, ',', '.') ?></h2>
                        </div>
                        
                        <form method="GET" action="" id="formGrafik">
                            <input type="hidden" name="page" value="<?= htmlspecialchars($_GET['page'] ?? 'home') ?>">
                            <div class="input-group shadow-sm" style="width: auto;">
                                <span class="input-group-text bg-white border-secondary"><i class="fa-solid fa-chart-line text-secondary"></i></span>
                                <select name="grafik" class="form-select border-secondary fw-medium" onchange="document.getElementById('formGrafik').submit()" style="cursor:pointer;">
                                    <option value="bulanan" <?= $filter_grafik == 'bulanan' ? 'selected' : '' ?>>Per Bulan (Tahun Ini)</option>
                                    <option value="harian" <?= $filter_grafik == 'harian' ? 'selected' : '' ?>>Per Hari (Bulan Ini)</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    
                    <div style="height: 350px; position: relative;">
                        <?php if (empty($data_grafik)) { ?>
                            <div class="position-absolute top-50 start-50 translate-middle text-center text-muted">
                                <i class="fa-solid fa-folder-open fs-1 mb-2 opacity-50"></i>
                                <div>Belum ada data pemasukan pada periode ini.</div>
                            </div>
                        <?php } ?>
                        <canvas id="grafikKeuangan"></canvas>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 pb-5">
        
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                        <h6 class="fw-bold m-0 text-dark text-uppercase">Aktivitas Terakhir</h6>
                        <a href="dashboard.php?page=audit_log" class="text-decoration-none small text-primary fw-medium">Lihat Semua</a>
                    </div>
                    
                    <div class="d-flex flex-column gap-2">
                        <?php 
                        if(mysqli_num_rows($log) > 0) {
                            while($a = mysqli_fetch_assoc($log)) { 
                        ?>
                            <div class="activity-item p-3 border border-light">
                                <div class="d-flex align-items-start">
                                    <div class="activity-icon bg-secondary bg-opacity-10 text-secondary me-3">
                                        <i class="fa-solid fa-bolt"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark" style="font-size: 14px;"><?= htmlspecialchars($a['aksi']) ?></div>
                                        <div class="text-muted mt-1" style="font-size: 12px;">
                                            <i class="fa-regular fa-clock me-1"></i> <?= date('d M Y, H:i', strtotime($a['created_at'])) ?> WIB
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            } 
                        } else { 
                        ?>
                            <div class="text-center py-5 text-muted fst-italic">Belum ada aktivitas sistem yang terekam.</div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-transparent">
                <div class="card-body p-0">
                    <h6 class="fw-bold mb-3 text-dark text-uppercase px-2">Aksi Cepat</h6>
                    
                    <div class="d-flex flex-column gap-3">
                        
                        <a href="dashboard.php?page=kredit_tambah" class="action-btn-custom">
                            <div class="action-icon-box bg-primary bg-opacity-10 text-primary shadow-sm">
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold m-0 text-dark">Kontrak Kredit Baru</h6>
                                <p class="text-muted m-0" style="font-size: 12px;">Input transaksi cicilan nasabah</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-muted fs-6"></i>
                        </a>

                        <a href="dashboard.php?page=kendaraan_tambah" class="action-btn-custom">
                            <div class="action-icon-box bg-success bg-opacity-10 text-success shadow-sm">
                                <i class="fa-solid fa-motorcycle"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold m-0 text-dark">Input Stok Motor</h6>
                                <p class="text-muted m-0" style="font-size: 12px;">Tambah data kendaraan baru</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-muted fs-6"></i>
                        </a>

                        <a href="dashboard.php?page=user_tambah" class="action-btn-custom">
                            <div class="action-icon-box bg-warning bg-opacity-10 text-warning shadow-sm">
                                <i class="fa-solid fa-user-plus"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold m-0 text-dark">Manajemen User</h6>
                                <p class="text-muted m-0" style="font-size: 12px;">Tambah akun karyawan & nasabah</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-muted fs-6"></i>
                        </a>

                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Cek jika data grafik kosong, jangan render chart agar tidak error
        const dataGrafik = <?= json_encode($data_grafik) ?>;
        if (dataGrafik.length === 0) return;

        const ctx = document.getElementById('grafikKeuangan').getContext('2d');
        
        let gradientFill = ctx.createLinearGradient(0, 0, 0, 400);
        gradientFill.addColorStop(0, 'rgba(16, 185, 129, 0.4)'); 
        gradientFill.addColorStop(1, 'rgba(16, 185, 129, 0.0)'); 

        new Chart(ctx, {
            type: 'line', 
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Pemasukan Kas',
                    data: dataGrafik,
                    borderColor: '#10b981', 
                    backgroundColor: gradientFill,
                    borderWidth: 3,
                    tension: 0.4, 
                    fill: true, 
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#10b981',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        titleFont: { size: 13, family: "'Inter', sans-serif" },
                        bodyFont: { size: 14, weight: 'bold', family: "'Inter', sans-serif" },
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: "'Inter', sans-serif" } }
                    },
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: '#e2e8f0' },
                        ticks: { 
                            font: { family: "'Inter', sans-serif" },
                            callback: function(value, index, values) {
                                if (value >= 1000000) return 'Rp ' + (value / 1000000) + ' Jt';
                                return value;
                            }
                        }
                    }
                }
            }
        });
    });
</script>