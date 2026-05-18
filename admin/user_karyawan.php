<?php

require '../config/security.php';
if ($_SESSION['role'] !== 'admin') exit;

include '../config/database.php';

$data = mysqli_query($conn,"
    SELECT * FROM users
    WHERE role='karyawan'
    ORDER BY created_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Data Karyawan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
<h4>Data Karyawan</h4>

<table class="table table-bordered bg-white">
<thead class="table-light">
<tr>
<th>No</th>
<th>Username</th>
<th>Status</th>
<th>Dibuat</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>
<?php $no=1; while($u=mysqli_fetch_assoc($data)){ ?>
<tr>
<td><?= $no++ ?></td>
<td><?= $u['username'] ?></td>
<td>
<span class="badge <?= $u['status']=='AKTIF'?'bg-success':'bg-secondary' ?>">
<?= $u['status'] ?>
</span>
</td>
<td><?= $u['created_at'] ?></td>
<td>
<a href="dashboard.php?page=user_edit&id=<?= $u['id'] ?>"
class="btn btn-sm btn-primary">Detail</a>
<a href="user_reset.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-info">Reset PW</a>
<a href="user_toggle.php?id=<?= $u['id'] ?>&aksi=<?= $u['status']=='AKTIF'?'nonaktif':'aktif' ?>"
   class="btn btn-sm btn-danger">Toggle</a>
</td>
</tr>
<?php } ?>
</tbody>
</table>

<a href="dashboard.php" class="btn btn-secondary">Kembali</a>
</div>
</body>
</html>
