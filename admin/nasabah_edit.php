<?php
require '../config/security.php';
if ($_SESSION['role'] !== 'admin') exit;

include '../config/database.php';

$no_kontrak = $_GET['no_kontrak'] ?? '';

if(!$no_kontrak){
    header('Location: dashboard.php?page=user_nasabah');
    exit;
}

// ambil data jika sudah ada
$q = mysqli_query($conn,"
    SELECT * FROM nasabah_profile WHERE no_kontrak='$no_kontrak'
");
$data = mysqli_fetch_assoc($q);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $no_kontrak = $_POST['no_kontrak'];

    $nama = $_POST['nama'];
    $alamat = $_POST['alamat'];
    $rt_rw = $_POST['rt_rw'];
    $kelurahan = $_POST['kelurahan'];
    $kecamatan = $_POST['kecamatan'];
    $no_hp = $_POST['no_hp'];
    $jk = $_POST['jenis_kelamin'];
    $pekerjaan = $_POST['pekerjaan'];

    $cek = mysqli_query($conn,"
        SELECT id FROM nasabah_profile WHERE no_kontrak='$no_kontrak'
    ");

    if (mysqli_num_rows($cek) > 0) {
        mysqli_query($conn,"
            UPDATE nasabah_profile SET
            nama='$nama',
            alamat='$alamat',
            rt_rw='$rt_rw',
            kelurahan='$kelurahan',
            kecamatan='$kecamatan',
            no_hp='$no_hp',
            jenis_kelamin='$jk',
            pekerjaan='$pekerjaan'
            WHERE no_kontrak='$no_kontrak'
        ");
    } else {
        mysqli_query($conn,"
            INSERT INTO nasabah_profile
            (no_kontrak,nama,alamat,rt_rw,kelurahan,kecamatan,no_hp,jenis_kelamin,pekerjaan)
            VALUES
            ('$no_kontrak','$nama','$alamat','$rt_rw','$kelurahan','$kecamatan','$no_hp','$jk','$pekerjaan')
        ");
    }

    echo "<script>
    window.location='dashboard.php?page=nasabah_detail&no_kontrak=".$no_kontrak."';
    </script>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Profil Nasabah</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container-fluid px-3 mt-4">

<h4 class="fw-bold mb-3">Lengkapi Profil Nasabah</h4>

<div class="card shadow-sm border-0">
<div class="card-body">

<form method="POST">

<input type="hidden" name="no_kontrak" value="<?= $no_kontrak ?>">

<input class="form-control mb-2" name="nama" placeholder="Nama Lengkap"
value="<?= $data['nama'] ?? '' ?>" required>

<textarea class="form-control mb-2" name="alamat" placeholder="Alamat"><?= $data['alamat'] ?? '' ?></textarea>

<input class="form-control mb-2" name="rt_rw" placeholder="RT/RW"
value="<?= $data['rt_rw'] ?? '' ?>">

<input class="form-control mb-2" name="kelurahan" placeholder="Kelurahan"
value="<?= $data['kelurahan'] ?? '' ?>">

<input class="form-control mb-2" name="kecamatan" placeholder="Kecamatan"
value="<?= $data['kecamatan'] ?? '' ?>">

<input class="form-control mb-2" name="no_hp" placeholder="No HP"
value="<?= $data['no_hp'] ?? '' ?>">

<select class="form-control mb-2" name="jenis_kelamin">
    <option value="">Jenis Kelamin</option>
    <option value="L" <?= ($data['jenis_kelamin'] ?? '')=='L'?'selected':'' ?>>Laki-laki</option>
    <option value="P" <?= ($data['jenis_kelamin'] ?? '')=='P'?'selected':'' ?>>Perempuan</option>
</select>

<input class="form-control mb-3" name="pekerjaan" placeholder="Pekerjaan"
value="<?= $data['pekerjaan'] ?? '' ?>">

<button class="btn btn-primary w-100 mb-2">Simpan</button>


</form>

</div>
</div>

</div>

</body>
</html>