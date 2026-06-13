<?php
session_start();

// Mengosongkan semua variabel sesi
session_unset();

// Menghancurkan sesi
session_destroy();

// Mengarahkan kembali ke halaman login (tanpa mengetik .php)
header('Location: login');
exit; // Pastikan untuk selalu menambahkan exit setelah header location
?>