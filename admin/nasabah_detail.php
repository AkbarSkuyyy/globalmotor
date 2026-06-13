<?php
// File ini di-include dari dashboard.php, jadi tidak perlu tag <html>, <head>, dan <body>

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

/* ========================================================
   LOGIKA CERDAS: CEK STATUS GPS NASABAH
======================================================== */
$lat   = $profil['latitude'] ?? ''; 
$lng   = $profil['longitude'] ?? '';
$no_hp = aman($profil, 'no_hp');
$nama  = aman($profil, 'nama');

// Format nomor HP ke format internasional WhatsApp (08... jadi 628...)
$no_hp_wa = (substr($no_hp, 0, 1) == '0') ? '62' . substr($no_hp, 1) : $no_hp;

// Pesan otomatis untuk WhatsApp
$pesan_wa = urlencode("Halo Bpk/Ibu *$nama*,\n\nSistem *GLOBAL MOTOR* mendeteksi bahwa akses Lokasi (GPS) pada perangkat Anda saat ini *TIDAK AKTIF / TERBLOKIR*.\n\nMohon bantuannya untuk:\n1. Buka website Nasabah Global Motor.\n2. Klik ikon *Gembok 🔒* di pojok kiri atas (sebelah alamat web).\n3. Ubah Izin Lokasi menjadi *Izinkan (Allow)*.\n4. Refresh halamannya.\n\nTerima kasih atas kerjasamanya.");
$link_wa  = "https://wa.me/" . $no_hp_wa . "?text=" . $pesan_wa;

// Kondisi penentu apakah GPS mati
$gps_mati = empty($lat) || empty($lng) || $lat == '0' || $lat == '0.00000000';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .section-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .summary-box {
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        color: white;
        border-radius: 15px;
        padding: 25px;
    }
    .summary-box h2 {
        font-weight: 700;
    }
    .info-label {
        font-size: 13px;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 2px;
    }
    .info-value {
        font-weight: 600;
        font-size: 15px;
        color: #1e293b;
    }
</style>

<div class="container-fluid mt-4 mb-5" style="max-width: 1000px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0 text-dark"><i class="fa-solid fa-user-tie text-primary me-2"></i>Detail Profil Nasabah</h4>
        <a href="dashboard?page=user_nasabah" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="fa-solid fa-arrow-left me-2"></i>Kembali
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-4 mb-4 bg-white">
        <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h6 class="fw-bold mb-1 text-secondary text-uppercase" style="font-size: 11px; letter-spacing: 1px;">
                    Status Lokasi (GPS) Terakhir
                </h6>
                
                <?php if ($gps_mati): ?>
                    <div class="d-flex align-items-center mt-2">
                        <i class="fa-solid fa-location-crosshairs fs-2 text-danger me-3 opacity-75"></i>
                        <div>
                            <h5 class="fw-bold text-dark m-0">GPS Tidak Terdeteksi</h5>
                            <p class="text-muted small m-0">Nasabah menolak akses lokasi atau GPS HP dimatikan.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="d-flex align-items-center mt-2">
                        <i class="fa-solid fa-map-location-dot fs-2 text-success me-3 opacity-75"></i>
                        <div>
                            <h5 class="fw-bold text-dark m-0">GPS Aktif & Terlacak</h5>
                            <p class="text-muted font-monospace small m-0"><?= htmlspecialchars($lat) ?>, <?= htmlspecialchars($lng) ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div>
                <?php if ($gps_mati): ?>
                    <button type="button" class="btn btn-danger rounded-pill px-4 py-2 fw-bold shadow-sm" onclick="ingatkanGPS('<?= $link_wa ?>', '<?= htmlspecialchars($nama) ?>')">
                        <i class="fa-brands fa-whatsapp fs-5 me-2 align-middle"></i> Ingatkan Nasabah
                    </button>
                <?php else: ?>
                    <a href="http://googleusercontent.com/maps.google.com/maps?q=<?= $lat ?>,<?= $lng ?>" target="_blank" class="btn btn-outline-success rounded-pill px-4 py-2 fw-bold shadow-sm">
                        <i class="fa-solid fa-location-arrow me-2"></i> Buka di Maps
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <div class="col-md-6">
            <div class="card section-card p-4 h-100 bg-white">
                <h6 class="mb-4 fw-bold text-primary border-bottom pb-2"><i class="fa-regular fa-address-card me-2"></i>Data Identitas</h6>

                <div class="mb-3">
                    <div class="info-label">No Kontrak</div>
                    <div class="info-value font-monospace text-primary"><?php echo htmlspecialchars($no_kontrak); ?></div>
                </div>

                <div class="mb-3">
                    <div class="info-label">Nama Lengkap</div>
                    <div class="info-value"><?php echo htmlspecialchars(aman($profil,'nama')); ?></div>
                </div>

                <div class="mb-3">
                    <div class="info-label">Alamat</div>
                    <div class="info-value"><?php echo htmlspecialchars(aman($profil,'alamat')); ?></div>
                </div>

                <div class="mb-4">
                    <div class="info-label">No WhatsApp/HP</div>
                    <div class="info-value"><?php echo htmlspecialchars(aman($profil,'no_hp')); ?></div>
                </div>

                <div class="mt-auto">
                    <a href="dashboard?page=nasabah_edit&no_kontrak=<?php echo urlencode($no_kontrak); ?>" 
                       class="btn btn-light border rounded-pill px-4 w-100 fw-medium shadow-sm">
                        <i class="fa-solid fa-pen-to-square me-2 text-warning"></i> Lengkapi / Edit Profil
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card section-card p-4 h-100 bg-white">
                <h6 class="mb-4 fw-bold text-primary border-bottom pb-2"><i class="fa-solid fa-motorcycle me-2"></i>Informasi Motor</h6>

                <div class="mb-3">
                    <div class="info-label">Unit Kendaraan</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars(aman($kredit,'merk').' '.aman($kredit,'tipe')); ?> 
                        <span class="text-secondary fw-normal">(<?php echo htmlspecialchars(aman($kredit,'warna')); ?>)</span>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="info-label">No Rangka</div>
                    <div class="info-value font-monospace"><?php echo htmlspecialchars(aman($kredit,'no_rangka')); ?></div>
                </div>

                <div class="mb-3">
                    <div class="info-label">No Mesin</div>
                    <div class="info-value font-monospace"><?php echo htmlspecialchars(aman($kredit,'no_mesin')); ?></div>
                </div>

                <div class="mb-3 mt-auto">
                    <div class="info-label">Harga OTR</div>
                    <div class="info-value text-success"><?php echo rupiah($harga_otr); ?></div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="summary-box shadow-lg mt-2">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="text-white-50 small text-uppercase mb-1 fw-bold">Total DP</div>
                        <h5><?php echo rupiah($dp); ?></h5>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-white-50 small text-uppercase mb-1 fw-bold">Angsuran / Bulan</div>
                        <h5><?php echo rupiah($angsuran); ?></h5>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-white-50 small text-uppercase mb-1 fw-bold">Lama Kredit</div>
                        <h5><?php echo htmlspecialchars($tenor); ?> Bulan</h5>
                    </div>
                    <div class="col-6 col-md-3 text-md-end">
                        <div class="text-white-50 small text-uppercase mb-1 fw-bold">Total Kredit</div>
                        <h4><?php echo rupiah($total_kredit); ?></h4>
                    </div>
                </div>
                <hr style="border-color: rgba(255,255,255,0.2); margin: 20px 0;">
                <div class="text-end">
                    <div style="font-size:14px; color: #94a3b8; font-weight: bold; letter-spacing: 1px;">TOTAL KEWAJIBAN (DP + KREDIT)</div>
                    <h2 class="mb-0 text-warning"><?php echo rupiah($total_bayar); ?></h2>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// Fungsi untuk memanggil konfirmasi SweetAlert sebelum pindah ke WhatsApp
function ingatkanGPS(linkWA, namaNasabah) {
    Swal.fire({
        title: 'Kirim Peringatan GPS?',
        html: `Anda akan diarahkan ke WhatsApp untuk mengirim pesan kepada <b>${namaNasabah}</b> agar mengizinkan akses Lokasi (GPS).`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#25d366', 
        cancelButtonColor: '#94a3b8',
        confirmButtonText: '<i class="fa-brands fa-whatsapp me-1"></i> Buka WhatsApp',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.open(linkWA, '_blank');
        }
    });
}
</script>