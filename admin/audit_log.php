<?php
require '../config/security.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

include '../config/database.php';

date_default_timezone_set('Asia/Jakarta');

$data = mysqli_query($conn,"
SELECT a.*, u.username
FROM audit_logs a
LEFT JOIN users u ON a.user_id=u.id
ORDER BY a.created_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Audit Log</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container-fluid px-3 mt-4">

<h4 class="fw-bold mb-3">Audit Log Sistem</h4>

<div class="card shadow-sm border-0">
<div class="card-body">

<div class="table-responsive">
<table class="table table-bordered table-hover table-sm align-middle bg-white">

<thead class="table-light">
<tr>
<th width="160">Waktu</th>
<th width="120">User</th>
<th width="100">Role</th>
<th width="120">Aksi</th>
<th>Detail</th>
<th width="130">IP</th>
</tr>
</thead>

<tbody>

<?php while($r=mysqli_fetch_assoc($data)){ ?>

<tr>

<td>
<?= date('d-m-Y H:i', strtotime($r['created_at'])) ?>
</td>

<td>
<?= $r['username'] ? htmlspecialchars($r['username']) : '<span class="text-muted">Unknown</span>' ?>
</td>

<td>
<span class="badge bg-secondary">
<?= htmlspecialchars($r['role']) ?>
</span>
</td>

<td>
<span class="badge bg-primary">
<?= htmlspecialchars($r['aksi']) ?>
</span>
</td>

<td>
<?= htmlspecialchars($r['detail']) ?>
</td>

<td>
<?= htmlspecialchars($r['ip_address']) ?>
</td>

</tr>

<?php } ?>

</tbody>

</table>
</div>

</div>
</div>

<a href="dashboard.php" class="btn btn-secondary mt-3">Kembali</a>

</div>

</body>
</html>