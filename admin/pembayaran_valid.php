<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../config/security.php';
include '../config/database.php';

if (!in_array($_SESSION['role'], ['admin','karyawan'])) {
    header('Location: ../auth/login.php');
    exit;
}

$pembayaran_id = $_GET['id'];
$angsuran_id   = $_GET['angsuran'];

/* =========================================
   Ambil data pembayaran dulu
=========================================*/
$data = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT pb.*, a.jumlah, p.no_kontrak
    FROM pembayaran pb
    JOIN angsuran a ON pb.angsuran_id = a.id
    JOIN penjualan p ON a.penjualan_id = p.id
    WHERE pb.id='$pembayaran_id'
"));

if(!$data){
    header("Location: dashboard.php?page=pembayaran");
    exit;
}

$total_transfer = $data['jumlah'] + $data['kode_unik'];
$no_kontrak     = $data['no_kontrak'];

/* =========================================
   Update pembayaran
=========================================*/
mysqli_query($conn,"
    UPDATE pembayaran
    SET status='VALID',
        validated_at=NOW()
    WHERE id='$pembayaran_id'
");

/* =========================================
   Update angsuran
=========================================*/
mysqli_query($conn,"
    UPDATE angsuran
    SET status='LUNAS'
    WHERE id='$angsuran_id'
");

/* =========================================
   Masuk ke jurnal kas
=========================================*/
mysqli_query($conn,"
    INSERT INTO jurnal_kas
    (tanggal,jenis,sumber,keterangan,jumlah)
    VALUES
    (NOW(),'MASUK','Angsuran','$no_kontrak','$total_transfer')
");

echo "<script>
window.location='dashboard.php?page=pembayaran';
</script>";
exit;
exit;