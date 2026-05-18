<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'karyawan') {
    header('Location: ../auth/login.php');
    exit;
}

include '../config/database.php';

$motor = mysqli_query($conn, "SELECT * FROM kendaraan ORDER BY status, merk");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Stok Motor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
    <h4>Stok Motor</h4>

    <table class="table table-bordered table-sm bg-white mt-3">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Motor</th>
                <th>Warna</th>
                <th>No Polisi</th>
                <th>Harga</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php $no=1; while ($m = mysqli_fetch_assoc($motor)) { ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= $m['merk'].' '.$m['tipe'] ?></td>
                <td><?= $m['warna'] ?></td>
                <td><?= $m['no_polisi'] ?></td>
                <td>Rp <?= number_format($m['harga_cash'],0,',','.') ?></td>
                <td>
                    <span class="badge <?= $m['status']=='READY'?'bg-success':'bg-secondary' ?>">
                        <?= $m['status'] ?>
                    </span>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

    <a href="dashboard.php" class="btn btn-outline-secondary mt-3">
        Kembali
    </a>
</div>

</body>
</html>
