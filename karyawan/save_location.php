<?php
session_start();
include '../config/database.php';

if (isset($_POST['lat']) && isset($_POST['lng'])) {
    $lat = mysqli_real_escape_string($conn, $_POST['lat']);
    $lng = mysqli_real_escape_string($conn, $_POST['lng']);

    // Ambil no_kontrak dari session, jika kosong cari berdasarkan user_id
    $no_kontrak = $_SESSION['username'] ?? null;
    if (!$no_kontrak && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $query_user = mysqli_query($conn, "SELECT username FROM users WHERE id='$user_id'");
        $u = mysqli_fetch_assoc($query_user);
        $no_kontrak = $u['username'] ?? '';
    }

    // Jika nomor kontrak ditemukan, perbarui koordinat lokasi terakhirnya
    if (!empty($no_kontrak)) {
        mysqli_query($conn, "
            UPDATE nasabah_profile 
            SET latitude='$lat', longitude='$lng' 
            WHERE no_kontrak='$no_kontrak'
        ");
    }
}
?>