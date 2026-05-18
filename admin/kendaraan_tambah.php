<?php
// FILE KONTEN (DI-INCLUDE KE DASHBOARD)
// TIDAK BOLEH ADA <html>, <body>, container

if (!isset($_SESSION['login']) || !in_array($_SESSION['role'], ['admin','karyawan'])) {
    exit;
}

include '../config/database.php';

if (isset($_POST['simpan'])) {

    $merk       = $_POST['merk'];
    $tipe       = $_POST['tipe'];
    $warna      = $_POST['warna'];
    $no_polisi  = $_POST['no_polisi'];
    $no_rangka  = $_POST['no_rangka'];
    $no_mesin   = $_POST['no_mesin'];
    $harga_cash = $_POST['harga_cash'];

    mysqli_query($conn, "
        INSERT INTO kendaraan
        (merk, tipe, warna, no_polisi, no_rangka, no_mesin, harga_cash, status)
        VALUES
        ('$merk','$tipe','$warna','$no_polisi','$no_rangka','$no_mesin','$harga_cash','READY')
    ");

    echo "<script>
        alert('Data motor berhasil ditambahkan');
        window.location.href='dashboard.php?page=stok_motor';
    </script>";
    exit;
}
?>

<h4 class="mb-3">Tambah Data Motor</h4>

<div class="card">
<div class="card-body">

<form method="POST">

<input name="merk" class="form-control mb-2" placeholder="Merk Motor" required>
<input name="tipe" class="form-control mb-2" placeholder="Tipe Motor" required>
<input name="warna" class="form-control mb-2" placeholder="Warna" required>
<input name="no_polisi" class="form-control mb-2" placeholder="No Polisi">
<input name="no_rangka" class="form-control mb-2" placeholder="No Rangka" required>
<input name="no_mesin" class="form-control mb-3" placeholder="No Mesin" required>

<label class="fw-bold">Harga Cash</label>
<div class="input-group mb-3">
    <span class="input-group-text">Rp</span>
    <input type="text" id="harga_view" class="form-control" placeholder="0" required>
    <input type="hidden" name="harga_cash" id="harga_cash">
</div>

<button name="simpan" class="btn btn-success">
    💾 Simpan Motor
</button>

</form>

</div>
</div>

<script>
const hargaView  = document.getElementById('harga_view');
const hargaInput = document.getElementById('harga_cash');

hargaView.addEventListener('input', function () {
    let val = this.value.replace(/[^0-9]/g,'');
    hargaInput.value = val;
    this.value = new Intl.NumberFormat('id-ID').format(val);
});
</script>
