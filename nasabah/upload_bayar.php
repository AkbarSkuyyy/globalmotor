<?php
session_start();
require '../config/security.php';

if ($_SESSION['role'] !== 'nasabah') {
    header('Location: ../auth/login.php');
    exit;
}

include '../config/database.php';
date_default_timezone_set('Asia/Jakarta');

$angsuran_id = $_GET['id'] ?? '';
$jumlah = $_GET['jumlah'] ?? 0;

$no_kontrak = $_SESSION['username'] ?? null;
if (!$no_kontrak) {
    $user_id = $_SESSION['user_id'];
    $query_user = mysqli_query($conn, "SELECT username FROM users WHERE id='$user_id'");
    $u = mysqli_fetch_assoc($query_user);
    $no_kontrak = $u['username'] ?? '';
}

$kode_unik = (abs(crc32($no_kontrak)) % 900) + 100;
$total_transfer = $jumlah + $kode_unik;

if (isset($_POST['upload'])) {
    $cek_bayar = mysqli_query($conn, "SELECT id FROM pembayaran WHERE angsuran_id='$angsuran_id' AND status='PENDING'");
    if (mysqli_num_rows($cek_bayar) > 0) {
        echo "<script>alert('Bukti pembayaran Anda sebelumnya sedang diproses. Mohon tunggu validasi admin.'); window.location.href='dashboard.php';</script>";
        exit;
    }

    $file = $_FILES['bukti']['name'];
    $tmp  = $_FILES['bukti']['tmp_name'];
    $allowed = ['jpg','jpeg','png'];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        die('File tidak diizinkan. Hanya JPG, JPEG, PNG.');
    }

    if ($_FILES['bukti']['size'] > 2000000) {
        die('Ukuran file terlalu besar (maks 2MB)');
    }

    $nama_file = time().'_'.$file;
    move_uploaded_file($tmp, '../assets/bukti/'.$nama_file);

    mysqli_query($conn, "INSERT INTO pembayaran (angsuran_id, bukti, status, created_at, kode_unik) VALUES ('$angsuran_id', '$nama_file', 'PENDING', NOW(), '$kode_unik')");
    mysqli_query($conn, "UPDATE angsuran SET status='PENDING' WHERE id='$angsuran_id'");

    // Notifikasi Telegram
    $data = mysqli_fetch_assoc(mysqli_query($conn,"SELECT p.no_kontrak, COALESCE(np.nama,'Nasabah') AS nama, a.jumlah, pb.kode_unik FROM pembayaran pb JOIN angsuran a ON pb.angsuran_id=a.id JOIN penjualan p ON a.penjualan_id=p.id LEFT JOIN nasabah_profile np ON np.no_kontrak=p.no_kontrak WHERE pb.angsuran_id='$angsuran_id' ORDER BY pb.id DESC LIMIT 1"));

    $token = "8531877183:AAEikf-y_E2ctxcznMtVakQcYKwg2kszp8g";
    $chat_id = "1151150926";
    $pesan = "💰 PEMBAYARAN MASUK\n👤 Nama: {$data['nama']}\n📄 No Kontrak: {$data['no_kontrak']}\n💵 Tagihan: Rp ".number_format($data['jumlah'],0,',','.')."\n🔢 Kode Unik: {$data['kode_unik']}\n💳 Total Transfer: Rp ".number_format($total_transfer,0,',','.')."\n⏳ Status: Menunggu Validasi";
    file_get_contents("https://api.telegram.org/bot$token/sendMessage?".http_build_query(['chat_id' => $chat_id, 'text' => $pesan]));

    header('Location: dashboard.php');
    exit;
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
            <div class="small opacity-75">Termasuk kode unik: <strong><?= $kode_unik ?></strong></div>
        </div>

        <form method="POST" enctype="multipart/form-data" onsubmit="document.getElementById('btnUpload').disabled=true; document.getElementById('btnUpload').innerHTML='<i class=\'bi bi-hourglass-split\'></i> Mengupload...';">
            <input type="hidden" name="upload" value="1">
            <div class="mb-4">
                <label class="form-label fw-semibold">Pilih File Bukti (JPG/PNG)</label>
                <input type="file" name="bukti" class="form-control rounded-pill" required accept=".jpg,.jpeg,.png">
            </div>
            
            <button id="btnUpload" type="submit" class="btn btn-primary w-100 rounded-pill py-2 shadow-sm mb-2">
                <i class="bi bi-send-fill me-2"></i>Kirim Bukti Pembayaran
            </button>
            <a href="dashboard.php" class="btn btn-outline-secondary w-100 rounded-pill py-2">
                <i class="bi bi-arrow-left me-2"></i>Kembali
            </a>
        </form>
    </div>
</div>

</body>
</html>