<?php
session_start();
require '../config/security.php';

if ($_SESSION['role'] !== 'nasabah') {
    // PERBAIKAN: Hilangkan .php pada URL redirect
    header('Location: ../auth/login');
    exit;
}

include '../config/database.php';

$user_id = $_SESSION['user_id'];
$query_user = mysqli_query($conn, "SELECT username FROM users WHERE id='$user_id'");
$user = mysqli_fetch_assoc($query_user);
$no_kontrak = $user['username'] ?? '';

$query_jual = mysqli_query($conn, "
    SELECT p.*, k.merk, k.tipe, k.warna
    FROM penjualan p
    JOIN kendaraan k ON p.kendaraan_id = k.id
    WHERE p.no_kontrak='$no_kontrak'
");
$jual = mysqli_fetch_assoc($query_jual);

$data = [];
$lunas = 0;
$total = 0;
$progress = 0;
$next = null;

if ($jual) {
    $angsuran = mysqli_query($conn, "
        SELECT * FROM angsuran
        WHERE penjualan_id='{$jual['id']}'
        ORDER BY bulan_ke ASC
    ");

    if ($angsuran) {
        while($a = mysqli_fetch_assoc($angsuran)){
            $data[] = $a;
            $total++;
            if($a['status'] == 'LUNAS' || $a['status'] == 'SUDAH LUNAS') $lunas++;
        }
    }

    $progress = $total > 0 ? round(($lunas / $total) * 100) : 0;

    $status_pending = false;
    foreach($data as $a){
        if($a['status'] == 'PENDING'){
            $next = $a;
            $status_pending = true;
            break;
        }
        if($a['status'] == 'BELUM' || $a['status'] == 'BELUM LUNAS'){
            $next = $a;
            break;
        }
    }
}

if (!function_exists('rupiah')) {
    function rupiah($a){
        return 'Rp ' . number_format((float)($a ?? 0), 0, ',', '.');
    }
}

date_default_timezone_set('Asia/Jakarta');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Nasabah</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">

    <style>
        body{
            background:#eef2f7;
            font-family:'Segoe UI',sans-serif;
        }

        /* HEADER */
        .header{
            background:linear-gradient(135deg,#2563eb,#1e40af);
            color:white;
            padding:40px 30px;
            border-radius:0 0 30px 30px;
            box-shadow:0 15px 35px rgba(0,0,0,0.15);
        }

        .progress{
            height:10px;
            border-radius:20px;
            overflow:hidden;
        }

        .progress-bar{
            background:linear-gradient(90deg,#22c55e,#16a34a);
            transition:width 1s ease-in-out;
        }

        /* CARD */
        .card-premium{
            border:none;
            border-radius:20px;
            box-shadow:0 15px 35px rgba(0,0,0,0.08);
        }

        .summary-card{
            border:none;
            border-radius:18px;
            box-shadow:0 10px 25px rgba(0,0,0,0.06);
        }

        /* MENU ICON */
        .menu-box{
            display:flex;
            justify-content:space-around;
            margin-top:30px;
            flex-wrap: wrap; 
            gap: 15px;
        }

        .menu-item{
            text-align:center;
            text-decoration:none;
            color:#374151;
            width: 70px;
        }

        .menu-circle{
            width:60px;
            height:60px;
            border-radius:50%;
            background:white;
            display:flex;
            align-items:center;
            justify-content:center;
            box-shadow:0 10px 25px rgba(0,0,0,0.08);
            font-size:24px;
            color:#2563eb;
            transition:all .3s ease;
            margin: 0 auto;
        }

        .menu-circle:hover{
            transform:translateY(-5px);
            background:#2563eb;
            color:white !important;
        }

        .menu-label{
            margin-top:8px;
            font-size:13px;
            font-weight:500;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="container">
            <?php if ($jual) { ?>
                <h5 class="fw-bold"><?php echo htmlspecialchars($jual['merk'] . ' ' . $jual['tipe'] . ' (' . $jual['warna'] . ')'); ?></h5>
                <small>No Kontrak: <?php echo htmlspecialchars($no_kontrak); ?></small>

                <div class="mt-4">
                    <small>Progress Pembayaran</small>
                    <div class="progress mt-2">
                        <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                    </div>
                    <small><?php echo $progress; ?>% Lunas (<?php echo $lunas; ?>/<?php echo $total; ?>)</small>
                </div>
            <?php } else { ?>
                <h5 class="fw-bold">Belum Ada Kontrak Aktif</h5>
                <small>No Kontrak / Username: <?php echo htmlspecialchars($no_kontrak); ?></small>
            <?php } ?>
        </div>
    </div>

    <div class="container mt-4 mb-5">

        <?php if ($jual) { ?>
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card summary-card text-center p-3">
                        <small class="text-muted">Total Angsuran</small>
                        <h5 class="fw-bold mt-1"><?php echo $total; ?></h5>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card summary-card text-center p-3">
                        <small class="text-muted">Lunas</small>
                        <h5 class="fw-bold text-success mt-1"><?php echo $lunas; ?></h5>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card summary-card text-center p-3">
                        <small class="text-muted">Belum</small>
                        <h5 class="fw-bold text-danger mt-1"><?php echo ($total - $lunas); ?></h5>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card summary-card text-center p-3">
                        <small class="text-muted">Angsuran / Bulan</small>
                        <h6 class="fw-bold text-primary mt-1"><?php echo rupiah($jual['angsuran'] ?? 0); ?></h6>
                    </div>
                </div>
            </div>

            <?php if($next){ ?>
                <div class="card card-premium text-center p-4 mb-4 bg-white">
                    <small class="text-muted">Tagihan Berikutnya</small>
                    <h3 class="fw-bold text-dark my-2"><?php echo rupiah($next['jumlah'] ?? 0); ?></h3>
                    <p class="mb-1 text-secondary">Bulan ke <?php echo htmlspecialchars($next['bulan_ke'] ?? ''); ?></p>
                    <p class="mb-0 text-secondary">Jatuh Tempo: <strong><?php echo isset($next['jatuh_tempo']) ? date('d M Y', strtotime($next['jatuh_tempo'])) : '-'; ?></strong></p>

                    <?php if ($status_pending) { ?>
                        <button class="btn btn-warning w-100 mt-3 rounded-4 fw-bold py-2" disabled>
                           ⏳ Menunggu Validasi Admin
                        </button>
                    <?php } else { ?>
                        <a href="upload_bayar?id=<?php echo urlencode($next['id'] ?? ''); ?>&jumlah=<?php echo urlencode($next['jumlah'] ?? 0); ?>"
                           class="btn btn-primary w-100 mt-3 rounded-4 py-2 fw-bold">
                           💳 Bayar Sekarang
                        </a>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="alert alert-success text-center rounded-4 p-3 mb-4 shadow-sm">
                    🎉 Semua tagihan angsuran Anda telah lunas atau belum tersedia tagihan baru.
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="alert alert-info text-center rounded-4 p-4 my-4 shadow-sm">
                Data kendaraan atau detail angsuran Anda belum tersedia di sistem.<br>
                <small class="text-muted">Silakan hubungi admin Global Motor untuk menginput data kontrak kredit Anda.</small>
            </div>
        <?php } ?>

        <div class="menu-box">

            <?php if ($jual) { ?>
                <a href="kartu_angsuran?id=<?= urlencode($jual['id']) ?>" class="menu-item">
                    <div class="menu-circle">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div class="menu-label">Kartu</div>
                </a>

                <a href="riwayat_pembayaran" class="menu-item">
                    <div class="menu-circle">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="menu-label">Riwayat</div>
                </a>
            <?php } ?>

            <a href="ubah_password" class="menu-item">
                <div class="menu-circle" style="color: #f59e0b;">
                    <i class="bi bi-key-fill"></i>
                </div>
                <div class="menu-label">Password</div>
            </a>

            <a href="../auth/logout" class="menu-item">
                <div class="menu-circle text-danger">
                    <i class="bi bi-box-arrow-right"></i>
                </div>
                <div class="menu-label">Logout</div>
            </a>

        </div>
        
    <div id="gps-alert-container" class="container mt-3" style="display: none;">
    <div class="alert alert-danger shadow-sm border-0 rounded-4 d-flex align-items-center justify-content-between p-3">
        <div class="d-flex align-items-center">
            <i class="fa-solid fa-location-slash fs-4 me-3"></i>
            <div>
                <span class="fw-bold d-block">Lokasi Belum Diizinkan!</span>
                <small class="small">Akses lokasi diperlukan untuk verifikasi pembayaran.</small>
            </div>
        </div>
        <button onclick="requestGPSAgain()" class="btn btn-danger btn-sm rounded-pill px-3 fw-bold">
            <i class="fa-solid fa-location-dot me-1"></i> Izinkan
        </button>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            // ==========================================
            // KONDISI 1: JIKA NASABAH MENGIZINKAN (ALLOW)
            // ==========================================
            function(position) {
                // Tembakkan data lokasi ke API save_location secara diam-diam
                fetch("save_location", {
                    method: "POST",
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: "lat=" + position.coords.latitude + "&lng=" + position.coords.longitude
                }).catch(err => console.log("Gagal mengirim lokasi:", err));
            }, 
            // ==========================================
            // KONDISI 2: JIKA LOKASI DITOLAK ATAU ERROR
            // ==========================================
            function(error) {
                if (error.code === error.PERMISSION_DENIED) {
                    // Nasabah memblokir atau menekan "Jangan Izinkan"
                    Swal.fire({
                        title: 'Akses Lokasi Diblokir!',
                        html: `
                            <div style="text-align: left; font-size: 14.5px; line-height: 1.6; color: #334155;">
                                Sepertinya Anda tidak sengaja menekan tombol <b>"Jangan Izinkan"</b> saat browser meminta akses lokasi.<br><br>
                                Sistem membutuhkan lokasi Anda untuk kelengkapan administrasi kontrak. <b>Cara memperbaikinya:</b>
                                <ol class="mt-3 text-start" style="padding-left: 20px;">
                                    <li class="mb-2">Klik ikon <b>Gembok 🔒</b> atau <b>Pengaturan</b> di sebelah alamat web (di bagian atas layar Anda).</li>
                                    <li class="mb-2">Pilih menu <b>Izin (Permissions)</b>.</li>
                                    <li class="mb-2">Cari opsi <b>Lokasi (Location)</b> dan ubah menjadi <b>Izinkan (Allow)</b>.</li>
                                    <li><b>Refresh (Muat Ulang)</b> halaman ini.</li>
                                </ol>
                            </div>
                        `,
                        icon: 'error',
                        confirmButtonText: '<i class="fa-solid fa-check me-1"></i> Saya Mengerti',
                        confirmButtonColor: '#3b82f6',
                        allowOutsideClick: false
                    });
                } else if (error.code === error.POSITION_UNAVAILABLE) {
                    console.log("GPS pada perangkat nasabah sedang dimatikan.");
                } else if (error.code === error.TIMEOUT) {
                    console.log("Sinyal GPS nasabah lemah, pencarian lokasi kehabisan waktu.");
                }
            },
            // ==========================================
            // Opsi Tambahan untuk Mempercepat Pencarian
            // ==========================================
            {
                enableHighAccuracy: true,
                timeout: 10000, // Menyerah setelah 10 detik jika sinyal jelek
                maximumAge: 0
            }
        );
    } else {
        console.log("Browser nasabah tidak mendukung fitur lokasi.");
    }
</script>

</body>
</html>