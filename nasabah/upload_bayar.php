<?php
session_start();
require '../config/security.php';

// Proteksi akses hanya untuk Nasabah
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nasabah') {
    header('Location: ../auth/login');
    exit;
}

include '../config/database.php';
date_default_timezone_set('Asia/Jakarta');

$angsuran_id = $_GET['id'] ?? '';

$no_kontrak = $_SESSION['username'] ?? null;
if (!$no_kontrak) {
    $user_id = $_SESSION['user_id'];
    $query_user = mysqli_query($conn, "SELECT username FROM users WHERE id='$user_id'");
    $u = mysqli_fetch_assoc($query_user);
    $no_kontrak = $u['username'] ?? '';
}

/* =========================================================
   BLOKIR JIKA LOKASI BELUM DIIZINKAN (GPS LOCK)
   ========================================================= */
$cek_lokasi = mysqli_query($conn, "SELECT latitude, longitude FROM nasabah_profile WHERE no_kontrak='$no_kontrak'");
$lokasi = mysqli_fetch_assoc($cek_lokasi);

// Jika latitude kosong/null, lempar kembali ke dashboard dengan pesan error
if (empty($lokasi['latitude']) || empty($lokasi['longitude'])) {
    header('Location: dashboard?error=lokasi');
    exit;
}
/* ========================================================= */

/* =========================================================
   LOGIKA CERDAS: AMBIL SISA TAGIHAN ASLI DARI DATABASE
   ========================================================= */
$q_cek = mysqli_query($conn, "SELECT jumlah, sisa_tagihan FROM angsuran WHERE id='$angsuran_id'");
$d_cek = mysqli_fetch_assoc($q_cek);

if(!$d_cek) {
    echo "<script>alert('Data tagihan tidak ditemukan!'); window.location='dashboard';</script>";
    exit;
}

// Gunakan sisa tagihan jika ada, jika NULL (data lama) gunakan jumlah awal
$jumlah_tagihan_aktual = is_null($d_cek['sisa_tagihan']) ? $d_cek['jumlah'] : $d_cek['sisa_tagihan'];

// Cek apakah angka tagihan berakhiran '000'
if ($jumlah_tagihan_aktual % 1000 == 0) {
    // Jika kelipatan 1000 (berarti Kontrak Baru)
    // Buatkan kode unik acak seperti biasa
    $kode_unik = (abs(crc32($no_kontrak)) % 900) + 100;
} else {
    // Jika BUKAN kelipatan 1000 (berarti Kontrak Migrasi Lama atau Sisa Uang Kurang)
    // Set kode unik menjadi 0 agar tidak bertambah ke tagihan aslinya
    $kode_unik = 0; 
}

// Total tagihan yang harus ditransfer Nasabah
$total_transfer = $jumlah_tagihan_aktual + $kode_unik;

$script_alert = ''; // Variabel penampung notifikasi SweetAlert

if (isset($_POST['upload'])) {
    $cek_bayar = mysqli_query($conn, "SELECT id FROM pembayaran WHERE angsuran_id='$angsuran_id' AND status='PENDING'");
    
    if (mysqli_num_rows($cek_bayar) > 0) {
        // ALERT: Jika sudah pernah upload tapi masih pending
        $script_alert = "
            Swal.fire({
                title: 'Harap Tunggu!',
                text: 'Bukti pembayaran Anda sebelumnya sedang diproses. Mohon tunggu validasi admin.',
                icon: 'warning',
                confirmButtonColor: '#f59e0b'
            }).then(() => {
                window.location.href = 'dashboard';
            });
        ";
    } else {
        $file = $_FILES['bukti']['name'];
        $tmp  = $_FILES['bukti']['tmp_name'];
        $allowed = ['jpg','jpeg','png'];
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            // ALERT: Jika format file bukan gambar
            $script_alert = "
                Swal.fire({
                    title: 'Format Tidak Sesuai!',
                    text: 'File tidak diizinkan. Harap upload gambar dengan format JPG, JPEG, atau PNG.',
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            ";
        } elseif ($_FILES['bukti']['size'] > 2000000) {
            // ALERT: Jika ukuran file lebih dari 2MB
            $script_alert = "
                Swal.fire({
                    title: 'File Terlalu Besar!',
                    text: 'Ukuran file gambar maksimal adalah 2MB. Harap kompres/perkecil ukuran foto Anda.',
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            ";
        } else {
            // Proses Upload Gambar
            $nama_file = time().'_'.$file;
            move_uploaded_file($tmp, '../assets/bukti/'.$nama_file);

            mysqli_query($conn, "INSERT INTO pembayaran (angsuran_id, bukti, status, created_at, kode_unik) VALUES ('$angsuran_id', '$nama_file', 'PENDING', NOW(), '$kode_unik')");
            mysqli_query($conn, "UPDATE angsuran SET status='PENDING' WHERE id='$angsuran_id'");

            // Notifikasi Telegram untuk Admin (Menampilkan nilai aktual)
            $data = mysqli_fetch_assoc(mysqli_query($conn,"
                SELECT p.no_kontrak, COALESCE(np.nama,'Nasabah') AS nama, a.jumlah, a.sisa_tagihan, pb.kode_unik 
                FROM pembayaran pb 
                JOIN angsuran a ON pb.angsuran_id=a.id 
                JOIN penjualan p ON a.penjualan_id=p.id 
                LEFT JOIN nasabah_profile np ON np.no_kontrak=p.no_kontrak 
                WHERE pb.angsuran_id='$angsuran_id' ORDER BY pb.id DESC LIMIT 1
            "));
            
            $tagihan_tampil = is_null($data['sisa_tagihan']) ? $data['jumlah'] : $data['sisa_tagihan'];

            $token = "8531877183:AAEikf-y_E2ctxcznMtVakQcYKwg2kszp8g";
            $chat_id = "1151150926";
            $pesan = "💰 PEMBAYARAN MASUK\n👤 Nama: {$data['nama']}\n📄 No Kontrak: {$data['no_kontrak']}\n💵 Tagihan: Rp ".number_format($tagihan_tampil,0,',','.')."\n🔢 Kode Unik: {$data['kode_unik']}\n💳 Total Transfer: Rp ".number_format($total_transfer,0,',','.')."\n⏳ Status: Menunggu Validasi";
            file_get_contents("https://api.telegram.org/bot$token/sendMessage?".http_build_query(['chat_id' => $chat_id, 'text' => $pesan]));

            // ALERT: Jika upload dan notif berhasil semua
            $script_alert = "
                Swal.fire({
                    title: 'Berhasil Terkirim!',
                    text: 'Bukti pembayaran Anda sudah masuk dan menunggu proses validasi Admin.',
                    icon: 'success',
                    confirmButtonColor: '#10b981',
                    allowOutsideClick: false
                }).then(() => {
                    window.location.href = 'dashboard';
                });
            ";
        }
    }
}

function rupiah($angka) { return 'Rp ' . number_format((float)$angka, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Upload Bukti Pembayaran</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; }
        .card { border-radius: 1rem; }
    </style>
</head>
<body>

<div class="container mt-4 mb-5" style="max-width: 500px;">
    <div class="card shadow-sm border-0 rounded-4 p-4">
        <h4 class="fw-bold mb-3"><i class="bi bi-cloud-arrow-up-fill text-primary me-2"></i>Upload Bukti</h4>
        
        <div class="alert alert-primary border-0 rounded-3 shadow-sm" role="alert">
            <div class="small fw-bold">Transfer sebesar:</div>
            <h3 class="fw-bold my-2"><?= rupiah($total_transfer) ?></h3>
            <div class="small opacity-75">
                <?php if($kode_unik > 0): ?>
                    Termasuk kode unik: <strong><?= $kode_unik ?></strong>
                <?php else: ?>
                    <i class="bi bi-info-circle me-1"></i> Data Migrasi / Sisa Tagihan
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" onsubmit="tampilkanLoading()">
            <input type="hidden" name="upload" value="1">
            <div class="mb-4">
                <label class="form-label fw-semibold">Pilih File Bukti (JPG/PNG)</label>
                <input type="file" name="bukti" class="form-control rounded-pill" required accept=".jpg,.jpeg,.png">
                <small class="text-muted" style="font-size: 11px;">Maksimal ukuran file: 2MB.</small>
            </div>
            
            <button id="btnUpload" type="submit" class="btn btn-primary w-100 rounded-pill py-2 shadow-sm mb-2 fw-bold">
                <i class="bi bi-send-fill me-2"></i>Kirim Bukti Pembayaran
            </button>
            
            <a href="dashboard" class="btn btn-outline-secondary w-100 rounded-pill py-2 fw-bold">
                <i class="bi bi-arrow-left me-2"></i>Kembali
            </a>
        </form>
    </div>
</div>

<script>
    // Munculkan Alert Hasil Upload (Jika ada)
    <?php if(!empty($script_alert)){ echo $script_alert; } ?>

    // Fungsi mengubah tombol agar mencegah nasabah double click (klik dua kali)
    function tampilkanLoading() {
        let btn = document.getElementById('btnUpload');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Mengupload...';
    }
</script>

</body>
</html>