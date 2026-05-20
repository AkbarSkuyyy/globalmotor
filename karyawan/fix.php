<?php
include '../config/database.php';

// Mengubah batas kolom status agar bisa menerima kata 'PENDING', 'VALID', dll
$perbaikan1 = mysqli_query($conn, "ALTER TABLE angsuran MODIFY COLUMN status VARCHAR(20) DEFAULT 'BELUM'");
$perbaikan2 = mysqli_query($conn, "ALTER TABLE pembayaran MODIFY COLUMN status VARCHAR(20) DEFAULT 'PENDING'");

if ($perbaikan1 && $perbaikan2) {
    echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>";
    echo "<h2 style='color:green;'>✅ Database Berhasil Diperbaiki!</h2>";
    echo "<p>Sistem sekarang sudah bisa menerima status PENDING.</p>";
    echo "<a href='dashboard.php' style='padding:10px 20px; background:blue; color:white; text-decoration:none; border-radius:5px;'>Kembali ke Dashboard</a>";
    echo "</div>";
} else {
    echo "Gagal memperbaiki database: " . mysqli_error($conn);
}
?>