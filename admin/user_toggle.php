<?php

require '../config/security.php';

if (!in_array($_SESSION['role'], ['admin','karyawan'])) {
    header('Location: ../auth/login.php');
    exit;
}


include '../config/database.php';
include '../config/audit_log.php';

$id   = $_GET['id'];
$aksi = $_GET['aksi'];

$status = ($aksi === 'aktif') ? 'AKTIF' : 'NONAKTIF';

mysqli_query($conn,"
    UPDATE users SET status='$status'
    WHERE id='$id'
");

// CATAT AUDIT
audit_log($conn, 'UBAH STATUS USER', "User ID $id → $status");

// BALIK KE HALAMAN SEBELUMNYA
$back = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
header("Location: $back");
exit;
