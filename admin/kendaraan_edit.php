<?php
require '../config/security.php';
if ($_SESSION['role'] !== 'admin') exit;

include '../config/database.php';

$id = $_GET['id'] ?? '';

$data = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT * FROM kendaraan WHERE id='$id'
"));

if(!$data){
    echo "<div class='alert alert-danger'>Data tidak ditemukan</div>";
    exit;
}

if($_SERVER['REQUEST_METHOD']=='POST'){

    $merk = $_POST['merk'];
    $tipe = $_POST['tipe'];
    $warna = $_POST['warna'];
    $no_polisi = $_POST['no_polisi'];
    $no_rangka = $_POST['no_rangka'];
    $no_mesin = $_POST['no_mesin'];
    $harga = preg_replace('/[^0-9]/','', $_POST['harga']);
    $status = $_POST['status'];

    mysqli_query($conn,"
        UPDATE kendaraan SET
        merk='$merk',
        tipe='$tipe',
        warna='$warna',
        no_polisi='$no_polisi',
        no_rangka='$no_rangka',
        no_mesin='$no_mesin',
        harga_cash='$harga',
        status='$status'
        WHERE id='$id'
    ");

    echo "<script>
    window.location='dashboard.php?page=stok_motor';
    </script>";
    exit;
}

function rupiah($a){
    return 'Rp '.number_format($a,0,',','.');
}
?>

<div class="container-fluid px-3 mt-4">

<h4 class="fw-bold mb-3">Edit Data Motor</h4>

<div class="card shadow-sm border-0">
<div class="card-body">

<form method="POST">

<div class="row">
<div class="col-md-6 mb-3">
<label>Merk</label>
<input type="text" name="merk" class="form-control"
value="<?= $data['merk'] ?>" required>
</div>

<div class="col-md-6 mb-3">
<label>Tipe</label>
<input type="text" name="tipe" class="form-control"
value="<?= $data['tipe'] ?>" required>
</div>
</div>

<div class="row">
<div class="col-md-6 mb-3">
<label>Warna</label>
<input type="text" name="warna" class="form-control"
value="<?= $data['warna'] ?>">
</div>

<div class="col-md-6 mb-3">
<label>No Polisi</label>
<input type="text" name="no_polisi" class="form-control"
value="<?= $data['no_polisi'] ?>">
</div>
</div>

<div class="row">
<div class="col-md-6 mb-3">
<label>No Rangka</label>
<input type="text" name="no_rangka" class="form-control"
value="<?= $data['no_rangka'] ?>">
</div>

<div class="col-md-6 mb-3">
<label>No Mesin</label>
<input type="text" name="no_mesin" class="form-control"
value="<?= $data['no_mesin'] ?>">
</div>
</div>

<div class="mb-3">
<label>Harga Cash</label>
<input type="text" name="harga" id="harga"
class="form-control"
value="<?= rupiah($data['harga_cash']) ?>">
</div>

<div class="mb-3">
<label>Status</label>
<select name="status" class="form-control">
<option value="READY" <?= $data['status']=='READY'?'selected':'' ?>>READY</option>
<option value="TERJUAL" <?= $data['status']=='TERJUAL'?'selected':'' ?>>TERJUAL</option>
</select>
</div>

<button class="btn btn-primary w-100 mb-2">Simpan Perubahan</button>
<a href="dashboard.php?page=stok_motor"
class="btn btn-secondary w-100">Kembali</a>

</form>

</div>
</div>

</div>

<script>
document.getElementById('harga').addEventListener('input', function(){
    let angka = this.value.replace(/[^0-9]/g,'');
    this.value = 'Rp ' + angka.replace(/\B(?=(\d{3})+(?!\d))/g,'.');
});
</script>