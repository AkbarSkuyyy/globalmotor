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

// Ambil no_kontrak dari session, jika kosong ambil dari database
$no_kontrak = $_SESSION['username'] ?? null;
if (!$no_kontrak) {
    $user_id = $_SESSION['user_id'];
    $query_user = mysqli_query($conn, "SELECT username FROM users WHERE id='$user_id'");
    $u = mysqli_fetch_assoc($query_user);
    $no_kontrak = $u['username'] ?? '';
}

/*
|--------------------------------------------------------------------------
| KODE UNIK PERMANEN PER NASABAH
|--------------------------------------------------------------------------
| Menggunakan rumus CRC32 untuk mengubah Nomor Kontrak (misal: GM-2026-001) 
| menjadi angka 3 digit (100-999) yang tidak akan pernah berubah untuk nasabah tersebut.
*/
$kode_unik = (abs(crc32($no_kontrak)) % 900) + 100;

$total_transfer = $jumlah + $kode_unik;

if (isset($_POST['upload'])) {

    // [ANTI-DOUBLE INPUT]
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

    mysqli_query($conn, "
        INSERT INTO pembayaran 
        (angsuran_id, bukti, status, created_at, kode_unik)
        VALUES 
        ('$angsuran_id', '$nama_file', 'PENDING', NOW(), '$kode_unik')
    ");

    mysqli_query($conn, "
        UPDATE angsuran 
        SET status='PENDING'
        WHERE id='$angsuran_id'
    ");

    /* ===== TELEGRAM ===== */
    $data = mysqli_fetch_assoc(mysqli_query($conn,"
        SELECT 
            p.no_kontrak,
            COALESCE(np.nama,'Tidak diketahui') AS nama,
            a.jumlah,
            pb.kode_unik
        FROM pembayaran pb
        JOIN angsuran a ON pb.angsuran_id=a.id
        JOIN penjualan p ON a.penjualan_id=p.id
        LEFT JOIN nasabah_profile np ON np.no_kontrak=p.no_kontrak
        WHERE pb.angsuran_id='$angsuran_id'
        ORDER BY pb.id DESC
        LIMIT 1
    "));

    $token = "8531877183:AAEikf-y_E2ctxcznMtVakQcYKwg2kszp8g";
    $chat_id = "1151150926";

    $pesan = "💰 PEMBAYARAN MASUK\n".
             "━━━━━━━━━━━━━━━\n".
             "👤 Nama: ".$data['nama']."\n".
             "📄 No Kontrak: ".$data['no_kontrak']."\n".
             "💵 Tagihan: Rp ".number_format($data['jumlah'],0,',','.')."\n".
             "🔢 Kode Unik: ".$data['kode_unik']."\n".
             "💳 Total Transfer: Rp ".number_format($total_transfer,0,',','.')."\n".
             "⏳ Status: Menunggu Validasi\n".
             "🕒 Waktu: ".date('d-m-Y H:i:s')." WIB";

    $url = "https://api.telegram.org/bot$token/sendMessage";

    file_get_contents($url.'?'.http_build_query([
        'chat_id' => $chat_id,
        'text' => $pesan
    ]));

    header('Location: dashboard.php');
    exit;
}

function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka,0,',','.');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Bukti Pembayaran</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4 mb-5">

<h5>Upload Bukti Pembayaran</h5>

<div class="alert alert-info">
    Transfer sebesar:<br>
    <strong><?php echo rupiah($total_transfer); ?></strong><br>
    <small>Termasuk kode unik permanen <?php echo htmlspecialchars($kode_unik); ?></small>
</div>

<form method="POST" enctype="multipart/form-data" onsubmit="document.getElementById('btnUpload').disabled = true; document.getElementById('btnUpload').innerHTML = '⏳ Mengupload... Mohon Tunggu';">
    <input type="hidden" name="upload" value="1"> 
    <input type="file" name="bukti" class="form-control mb-3" required accept=".jpg,.jpeg,.png">
    <button id="btnUpload" type="submit" class="btn btn-success w-100 py-2">
        🚀 Upload Bukti
    </button>
    <a href="dashboard.php" class="btn btn-outline-secondary w-100 mt-2 py-2">Kembali</a>
</form>

</body>
</html>