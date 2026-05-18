<?php

require '../config/security.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

include '../config/database.php';

// ambil semua user kecuali admin
$users = mysqli_query($conn, "
    SELECT id, username, role, status, created_at
    FROM users
    WHERE role != 'admin'
    ORDER BY role ASC, created_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manajemen User</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">

    <h4 class="mb-3">Manajemen User</h4>

    <table class="table table-bordered table-sm bg-white">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Username</th>
                <th>Role</th>
                <th>Status</th>
                <th>Tanggal Dibuat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php $no = 1; while ($u = mysqli_fetch_assoc($users)) { ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= $u['username'] ?></td>
                <td>
                    <span class="badge <?= $u['role']=='karyawan'?'bg-primary':'bg-success' ?>">
                        <?= strtoupper($u['role']) ?>
                    </span>
                </td>
                <td>
                    <span class="badge <?= $u['status']=='AKTIF'?'bg-success':'bg-secondary' ?>">
                        <?= $u['status'] ?>
                    </span>
                </td>
                <td><?= $u['created_at'] ?></td>
                <td>
                    <?php if ($u['status'] == 'AKTIF') { ?>
                        <a href="user_toggle.php?id=<?= $u['id'] ?>&aksi=nonaktif"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Nonaktifkan user ini?')">
                            Nonaktifkan
                        </a>
                    <?php } else { ?>
                        <a href="user_toggle.php?id=<?= $u['id'] ?>&aksi=aktif"
                           class="btn btn-sm btn-success"
                           onclick="return confirm('Aktifkan user ini?')">
                            Aktifkan
                        </a>
                    <?php } ?>
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
