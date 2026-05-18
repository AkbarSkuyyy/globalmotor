<?php
require '../config/security.php';
if ($_SESSION['role'] !== 'admin') exit;

include '../config/database.php';

$keyword = $_GET['search'] ?? '';

$where = "";
if($keyword){
    $where = "AND (
        u.username LIKE '%$keyword%' OR
        np.nama LIKE '%$keyword%'
    )";
}

$data = mysqli_query($conn,"
    SELECT 
        u.*,
        np.nama
    FROM users u
    LEFT JOIN nasabah_profile np 
        ON np.no_kontrak = u.username
    WHERE u.role='nasabah'
    $where
    ORDER BY u.created_at DESC
");
?>

<div class="container-fluid px-3 mt-4">

<h4 class="fw-bold mb-3">Data Nasabah</h4>

<div class="card shadow-sm border-0">
<div class="card-body">

<!-- SEARCH -->
<form method="GET" class="row mb-3">
<input type="hidden" name="page" value="user_nasabah">
<div class="col-md-4">
<input type="text" name="search" class="form-control"
placeholder="Cari Nama / No Kontrak..."
value="<?= htmlspecialchars($keyword) ?>">
</div>
<div class="col-md-2">
<button class="btn btn-primary w-100">Cari</button>
</div>
<div class="col-md-2">
<a href="dashboard.php?page=user_nasabah"
class="btn btn-secondary w-100">Reset</a>
</div>
</form>

<div class="table-responsive">
<table class="table table-bordered table-hover align-middle bg-white">
<thead class="table-light">
<tr>
<th>No</th>
<th>Nama</th>
<th>No Kontrak</th>
<th>Status</th>
<th>Dibuat</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>
<?php 
$no=1; 
while($u=mysqli_fetch_assoc($data)){ 
?>
<tr>
<td><?= $no++ ?></td>

<td>
<?= $u['nama'] ? htmlspecialchars($u['nama']) : 
'<span class="text-muted">Belum diisi</span>' ?>
</td>

<td><?= $u['username'] ?></td>

<td>
<span class="badge <?= $u['status']=='AKTIF'?'bg-success':'bg-secondary' ?>">
<?= $u['status'] ?>
</span>
</td>

<td><?= date('d-m-Y H:i', strtotime($u['created_at'])) ?></td>

<td>
<a href="user_reset.php?id=<?= $u['id'] ?>" 
class="btn btn-sm btn-info">Reset</a>

<a href="user_toggle.php?id=<?= $u['id'] ?>&aksi=<?= $u['status']=='AKTIF'?'nonaktif':'aktif' ?>"
class="btn btn-sm btn-danger">Toggle</a>

<a href="dashboard.php?page=nasabah_detail&no_kontrak=<?= $u['username'] ?>"
class="btn btn-sm btn-primary">Detail</a>
</td>

</tr>
<?php } ?>
</tbody>
</table>
</div>

</div>
</div>

</div>