<?php

require '../config/security.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

include '../config/database.php';

// AMBIL SEMUA MOTOR (READY + TERJUAL)
$motor = mysqli_query($conn, "SELECT * FROM kendaraan ORDER BY merk, tipe");
$hasil = null;

if (isset($_POST['hitung'])) {
    $harga = $_POST['harga'];
    $dp    = $_POST['dp'];
    $tenor = $_POST['tenor'];

    $sisa = $harga - $dp;

    // bunga flat 2% / bulan
    $bunga = 0.02 * $sisa * $tenor;
    $total = $sisa + $bunga;
    $angsuran = round($total / $tenor);

    $hasil = compact('harga','dp','tenor','sisa','angsuran','total');
}

function rupiah($a){
    return 'Rp '.number_format($a,0,',','.');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simulasi Kredit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
<h4>Simulasi Kredit</h4>

<form method="POST" class="card p-3 mb-3">

<label>Motor</label>
<select name="harga" class="form-control mb-2" required>
    <option value="">-- Pilih Motor --</option>
    <?php while($m=mysqli_fetch_assoc($motor)){ ?>
        <option value="<?= $m['harga_cash'] ?>">
            <?= $m['merk'].' '.$m['tipe'].' ('.$m['status'].') - '.rupiah($m['harga_cash']) ?>
        </option>
    <?php } ?>
</select>

<label>DP (Uang Muka)</label>
<div class="input-group mb-2">
    <span class="input-group-text">Rp</span>
    <input type="text" id="dp_view" class="form-control" required>
    <input type="hidden" name="dp" id="dp">
</div>

<label>Tenor</label>
<select name="tenor" class="form-control mb-3" required>
    <option value="12">12 Bulan</option>
    <option value="24">24 Bulan</option>
    <option value="30">30 Bulan</option>
    <option value="36">36 Bulan</option>
</select>

<button name="hitung" class="btn btn-primary w-100">Hitung</button>
</form>

<?php if ($hasil) { ?>
<div class="card p-3">
<table class="table table-sm">
<tr><th>Harga</th><td><?= rupiah($hasil['harga']) ?></td></tr>
<tr><th>DP</th><td><?= rupiah($hasil['dp']) ?></td></tr>
<tr><th>Sisa Pinjaman</th><td><?= rupiah($hasil['sisa']) ?></td></tr>
<tr><th>Tenor</th><td><?= $hasil['tenor'] ?> Bulan</td></tr>
<tr class="table-success">
<th>Angsuran / Bulan</th>
<th><?= rupiah($hasil['angsuran']) ?></th>
</tr>
</table>
</div>
<?php } ?>

<a href="dashboard.php" class="btn btn-outline-secondary mt-3">Kembali</a>
</div>

<script>
const dpView = document.getElementById('dp_view');
const dpHidden = document.getElementById('dp');
dpView.addEventListener('input', function(){
    let v = this.value.replace(/[^0-9]/g,'');
    dpHidden.value = v;
    this.value = new Intl.NumberFormat('id-ID').format(v);
});
</script>

</body>
</html>
