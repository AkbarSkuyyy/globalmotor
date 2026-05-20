<?php
require '../config/security.php';
if ($_SESSION['role'] !== 'admin') exit;

include '../config/database.php';

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $role = isset($_GET['role']) ? $_GET['role'] : 'karyawan';

    // Proses hapus akun
    $hapus = mysqli_query($conn, "DELETE FROM users WHERE id = '$id'");

    if ($hapus) {
        echo "<script>
            alert('Akun berhasil dihapus secara permanen!');
            window.location.href = 'dashboard.php?page=user_" . $role . "';
        </script>";
    } else {
        echo "<script>
            alert('Gagal menghapus akun! Pastikan tidak ada data penting yang masih terikat dengan akun ini.');
            window.location.href = 'dashboard.php?page=user_" . $role . "';
        </script>";
    }
} else {
    echo "<script>window.location.href = 'dashboard.php';</script>";
}
?>