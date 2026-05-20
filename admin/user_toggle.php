<?php
require '../config/security.php';

// Memastikan hanya admin atau karyawan yang berwenang
if (!in_array($_SESSION['role'], ['admin', 'karyawan'])) {
    echo "<script>window.location='../auth/login.php';</script>";
    exit;
}

include '../config/database.php';
include '../config/audit_log.php';

// Validasi ID agar tidak error jika tidak ada di URL
$id   = $_GET['id'] ?? 0;
$aksi = $_GET['aksi'] ?? '';

if ($id > 0 && !empty($aksi)) {
    $status = ($aksi === 'aktif') ? 'AKTIF' : 'NONAKTIF';

    mysqli_query($conn, "
        UPDATE users SET status='$status'
        WHERE id='$id'
    ");

    // CATAT AUDIT
    audit_log($conn, 'UBAH STATUS USER', "User ID $id → $status");
}

// BALIK KE HALAMAN SEBELUMNYA MENGGUNAKAN JAVASCRIPT
// Menggunakan window.location agar tidak terkena error 'headers already sent'
$back = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php?page=user_karyawan';
echo "<script>window.location='$back';</script>";
exit;
?>