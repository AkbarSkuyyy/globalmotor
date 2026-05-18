<?php
session_start();
require '../config/security.php';

if ($_SESSION['role'] !== 'karyawan') {
    header('Location: ../auth/login.php');
    exit;
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Karyawan - GLOBAL MOTOR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f6f9;
        }

        .card-menu {
            border-radius: 18px;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: #000;
        }

        .card-menu:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .icon-box {
            font-size: 38px;
        }
    </style>
</head>
<body>

<div class="container mt-5">

    <h3 class="mb-4 fw-bold">Dashboard Karyawan</h3>

    <div class="row g-4">

        <!-- TAMBAH MOTOR -->
        <div class="col-md-4">
            <a href="../admin/kendaraan_tambah.php" class="card card-menu shadow-sm">
                <div class="card-body text-center py-4">
                    <div class="icon-box mb-3">🚗</div>
                    <h5>Tambah Motor</h5>
                    <p class="text-muted mb-0">
                        Input data kendaraan
                    </p>
                </div>
            </a>
        </div>

        <!-- TAMBAH KREDIT -->
        <div class="col-md-4">
            <a href="../admin/kredit_tambah.php" class="card card-menu shadow-sm">
                <div class="card-body text-center py-4">
                    <div class="icon-box mb-3">➕</div>
                    <h5>Tambah Kredit</h5>
                    <p class="text-muted mb-0">
                        Input transaksi kredit
                    </p>
                </div>
            </a>
        </div>

        <!-- LIHAT STOK MOTOR -->
        <div class="col-md-4">
            <a href="stok_motor.php" class="card card-menu shadow-sm">
                <div class="card-body text-center py-4">
                    <div class="icon-box mb-3">📦</div>
                    <h5>Stok Motor</h5>
                    <p class="text-muted mb-0">
                        Lihat motor ready & terjual
                    </p>
                </div>
            </a>
        </div>

    </div>

    <a href="../auth/logout.php" class="btn btn-outline-secondary mt-4">
        Logout
    </a>

</div>

</body>
</html>
