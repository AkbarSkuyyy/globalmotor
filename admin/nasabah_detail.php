<?php
require '../config/security.php';
if ($_SESSION['role'] !== 'admin') exit;

include '../config/database.php';

$no_kontrak = $_GET['no_kontrak'] ?? '';

function aman($arr, $key){
    return (is_array($arr) && isset($arr[$key]) && $arr[$key] !== '') ? $arr[$key] : '-';
}
function rupiah($a){
    return 'Rp ' . number_format((float)($a ?? 0), 0, ',', '.');
}

$profil = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT * FROM nasabah_profile
    WHERE no_kontrak='$no_kontrak'
"));

$kredit = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT p.*, k.merk, k.tipe, k.warna,
           k.no_rangka, k.no_mesin, k.harga_cash
    FROM penjualan p
    JOIN kendaraan k ON p.kendaraan_id = k.id
    WHERE p.no_kontrak='$no_kontrak'
"));

$harga_otr   = $kredit['harga_cash'] ?? 0;
$dp          = $kredit['dp'] ?? 0;
$tenor       = $kredit['tenor'] ?? 0;
$angsuran    = $kredit['angsuran'] ?? 0;

$total_kredit = $angsuran * $tenor;
$total_bayar  = $total_kredit + $dp;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profil Nasabah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <style>
        body{background:#f4f6f9}
        .section-card{
            border:none;
            border-radius:12px;
            box-shadow:0 4px 15px rgba(0,0,0,0.05);
        }
        .summary-box{
            background:linear-gradient(135deg,#1e3c72,#2a5298);
            color:white;
            border-radius:15px;
            padding:25px;
        }
        .summary-box h2{
            font-weight:700;
        }
        .info-label{
            font-size:13px;
            color:#6c757d;
        }
        .info-value{
            font-weight:600;
        }
    </style>
</head>
<body>

<div class="container mt-4 mb-5">

    <h4 class="mb-4">Profil Nasabah</h4>

    <div class="row g-4">

        <div class="col-md-6">
            <div class="card section-card p-4 bg-white">
                <h6 class="mb-3 fw-bold">Data Nasabah</h6>

                <div class="mb-2">
                    <div class="info-label">No Kontrak</div>
                    <div class="info-value"><?php echo htmlspecialchars($no_kontrak); ?></div>
                </div>

                <div class="mb-2">
                    <div class="info-label">Nama</div>
                    <div class="info-value"><?php echo htmlspecialchars(aman($profil,'nama')); ?></div>
                </div>

                <div class="mb-2">
                    <div class="info-label">Alamat</div>
                    <div class="info-value"><?php echo htmlspecialchars(aman($profil,'alamat')); ?></div>
                </div>

                <div class="mb-2">
                    <div class="info-label">No HP</div>
                    <div class="info-value"><?php echo htmlspecialchars(aman($profil,'no_hp')); ?></div>
                </div>

                <div class="mb-3">
                    <div class="info-label">Lokasi Terakhir HP (Akses Web)</div>
                    <div class="info-value">
                        <?php if(!empty($profil['latitude']) && !empty($profil['longitude'])){ ?>
                            <a href="https://www.google.com/maps?q=<?php echo $profil['latitude'] . ',' . $profil['longitude']; ?>" 
                               target="_blank" class="btn btn-sm btn-outline-primary mt-1 px-3 py-1 fw-bold rounded-3">
                                <i class="bi bi-geo-alt-fill"></i> Buka di Google Maps
                            </a>
                        <?php } else { ?>
                            <span class="text-muted small italic">Belum ada data lokasi (GPS mati / belum masuk dashboard)</span>
                        <?php } ?>
                    </div>
                </div>

                <a href="dashboard.php?page=nasabah_edit&no_kontrak=<?php echo urlencode($no_kontrak); ?>" 
                   class="btn btn-sm btn-warning rounded-3 mt-2">
                    Lengkapi / Edit Profil
                </a>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card section-card p-4 bg-white">
                <h6 class="mb-3 fw-bold">Informasi Motor</h6>

                <div class="mb-2">
                    <div class="info-label">Motor</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars(aman($kredit,'merk').' '.aman($kredit,'tipe').' ('.aman($kredit,'warna').')'); ?>
                    </div>
                </div>

                <div class="mb-2">
                    <div class="info-label">No Rangka</div>
                    <div class="info-value"><?php echo htmlspecialchars(aman($kredit,'no_rangka')); ?></div>
                </div>

                <div class="mb-2">
                    <div class="info-label">No Mesin</div>
                    <div class="info-value"><?php echo htmlspecialchars(aman($kredit,'no_mesin')); ?></div>
                </div>

                <div class="mb-2">
                    <div class="info-label">Harga OTR</div>
                    <div class="info-value"><?php echo rupiah($harga_otr); ?></div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="summary-box shadow">
                <div class="row">
                    <div class="col-md-3">
                        <div>Total DP</div>
                        <h5><?php echo rupiah($dp); ?></h5>
                    </div>
                    <div class="col-md-3">
                        <div>Angsuran / Bulan</div>
                        <h5><?php echo rupiah($angsuran); ?></h5>
                    </div>
                    <div class="col-md-3">
                        <div>Lama Kredit</div>
                        <h5><?php echo htmlspecialchars($tenor); ?> Bulan</h5>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <div>Total Kredit</div>
                        <h4><?php echo rupiah($total_kredit); ?></h4>
                    </div>
                </div>
                <hr style="opacity:0.2">
                <div class="text-end">
                    <div style="font-size:14px;opacity:0.8">TOTAL KEWAJIBAN</div>
                    <h2><?php echo rupiah($total_bayar); ?></h2>
                </div>
            </div>
        </div>

    </div>

    <a href="dashboard.php?page=user_nasabah" class="btn btn-secondary mt-4 px-4 rounded-3">
        Kembali
    </a>

</div>
</body>
</html>