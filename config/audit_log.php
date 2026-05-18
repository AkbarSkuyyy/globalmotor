<?php
/**
 * AUDIT LOG GLOBAL
 * Digunakan untuk mencatat aktivitas user:
 * - login
 * - edit data
 * - validasi pembayaran
 * - dll
 */

function audit_log($conn, $aksi, $detail = '')
{
    // pastikan session ada
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        return;
    }

    $user_id = $_SESSION['user_id'];
    $role    = $_SESSION['role'];
    $ip      = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

    // sanitasi
    $aksi   = mysqli_real_escape_string($conn, $aksi);
    $detail = mysqli_real_escape_string($conn, $detail);

    mysqli_query($conn, "
        INSERT INTO audit_logs (user_id, role, aksi, detail, ip_address, created_at)
        VALUES ('$user_id', '$role', '$aksi', '$detail', '$ip', NOW())
    ");
}
