<?php
require '../config/security.php';
if ($_SESSION['role'] !== 'admin') exit;

include '../config/database.php';

$keyword = $_GET['search'] ?? '';

$where = "";
if($keyword){
    $where = "WHERE 
        merk LIKE '%$keyword%' OR
        tipe LIKE '%$keyword%' OR
        warna LIKE '%$keyword%' OR
        no_rangka LIKE '%$keyword%' OR
        no_mesin LIKE '%$keyword%' OR
        no_polisi LIKE '%$keyword%'
    ";
}

$motor = mysqli_query($conn,"
    SELECT * FROM kendaraan
    $where
    ORDER BY status ASC, merk ASC, tipe ASC
");

function rupiah($a){
    return 'Rp '.number_format($a,0,',','.');
}
?>

<div class="container-fluid px-3 mt-4">

<h4 class="fw-bold mb-3">Stok Motor</h4>

<div class="card shadow-sm border-0">
<div class="card-body">

<!-- SEARCH -->
<form method="GET" class="row mb-3">
<input type="hidden" name="page" value="stok_motor">
<div class="col-md-4">
<input type="text" name="search" class="form-control"
placeholder="Cari Merk / Tipe / Rangka / Mesin..."
value="<?= htmlspecialchars($keyword) ?>">
</div>
<div class="col-md-2">
<button class="btn btn-primary w-100">Cari</button>
</div>
<div class="col-md-2">
<a href="dashboard.php?page=stok_motor"
class="btn btn-secondary w-100">Reset</a>
</div>
</form>

<div class="table-responsive">
<table class="table table-bordered table-hover align-middle bg-white">
<thead class="table-light">
<tr>
<th>No</th>
<th>Merk</th>
<th>Tipe</th>
<th>Warna</th>
<th>No Polisi</th>
<th>No Rangka</th>
<th>No Mesin</th>
<th>Harga</th>
<th>Status</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>
<?php 
$no=1; 
while($m=mysqli_fetch_assoc($motor)){ 
?>
<tr>
<td><?= $no++ ?></td>
<td><?= $m['merk'] ?></td>
<td><?= $m['tipe'] ?></td>
<td><?= $m['warna'] ?></td>
<td><?= $m['no_polisi'] ?: '-' ?></td>
<td><?= $m['no_rangka'] ?: '-' ?></td>
<td><?= $m['no_mesin'] ?: '-' ?></td>
<td><?= rupiah($m['harga_cash']) ?></td>
<td>
<span class="badge <?= $m['status']=='READY'?'bg-success':'bg-secondary' ?>">
<?= $m['status'] ?>
</span>
</td>
<td>
<a href="dashboard.php?page=kendaraan_edit&id=<?= $m['id'] ?>"
class="btn btn-sm btn-warning">Edit</a>
</td>
</tr>
<?php } ?>
</tbody>
</table>
</div>

</div>
</div>

</div>