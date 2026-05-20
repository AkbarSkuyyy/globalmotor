<?php
require '../config/security.php';
if (!in_array($_SESSION['role'], ['admin', 'karyawan'])) {
    header('Location: ../auth/login.php');
    exit;
}

include '../config/database.php';

$motor = mysqli_query($conn, "SELECT * FROM kendaraan ORDER BY merk, tipe");
$hasil = null;

if (isset($_POST['hitung'])) {
    // Pastikan data yang diambil adalah angka murni
    $harga = (float)str_replace(['.', 'Rp ', ','], '', $_POST['harga_raw']);
    $dp    = (float)str_replace(['.', 'Rp ', ','], '', $_POST['dp']);
    $tenor = (int)$_POST['tenor'];

    $sisa = $harga - $dp;
    // Bunga flat 2% / bulan
    $bunga = 0.02 * $sisa * $tenor;
    $total = $sisa + $bunga;
    $angsuran = round($total / $tenor);

    $hasil = compact('harga','dp','tenor','sisa','angsuran','total');
}

function rupiah($a){
    return 'Rp '.number_format((float)($a ?? 0), 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Simulasi Kredit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; }
        .card { border-radius: 1rem; }
    </style>
</head>
<body>

<div class="container mt-4 mb-5" style="max-width: 600px;">
    <h4 class="fw-bold mb-4"><i class="bi bi-calculator me-2"></i>Simulasi Kredit</h4>

    <form method="POST" class="bg-white p-4 shadow-sm border-0 rounded-4 mb-4">
        <div class="mb-3">
            <label class="form-label fw-semibold">Pilih Kendaraan</label>
            <select id="select_motor" class="form-select mb-2" onchange="updateHarga(this.value)">
                <option value="">-- Pilih Motor --</option>
                <?php while($m = mysqli_fetch_assoc($motor)){ 
                    $harga_motor = isset($m['harga']) ? $m['harga'] : 0;
                ?>
                    <option value="<?= $harga_motor ?>">
                        <?= htmlspecialchars(($m['merk'] ?? '') . ' ' . ($m['tipe'] ?? '')) ?> - <?= rupiah($harga_motor) ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Harga OTR</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0">Rp</span>
                <input type="text" id="otr_view" class="form-control border-start-0" placeholder="0" required value="<?= isset($_POST['harga_raw']) ? number_format((float)$_POST['harga_raw'], 0, ',', '.') : '' ?>">
                <!-- Input hidden ini yang akan dikirim ke server -->
                <input type="hidden" name="harga_raw" id="otr_hidden" value="<?= $_POST['harga_raw'] ?? '' ?>">
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label fw-semibold">DP (Uang Muka)</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0">Rp</span>
                <input type="text" id="dp_view" class="form-control border-start-0" placeholder="0" required value="<?= isset($_POST['dp']) ? number_format((float)$_POST['dp'], 0, ',', '.') : '' ?>">
                <input type="hidden" name="dp" id="dp" value="<?= $_POST['dp'] ?? '' ?>">
            </div>
        </div>
        
        <div class="mb-4">
            <label class="form-label fw-semibold">Tenor (Bulan)</label>
            <select name="tenor" class="form-select" required>
                <?php 
                $pilihan_tenor = [12, 24, 30, 36];
                foreach ($pilihan_tenor as $t) {
                    $selected = (isset($_POST['tenor']) && $_POST['tenor'] == $t) ? 'selected' : '';
                    echo "<option value='$t' $selected>$t Bulan</option>";
                }
                ?>
            </select>
        </div>
        
        <button name="hitung" class="btn btn-primary w-100 rounded-pill py-2 shadow-sm">
            <i class="bi bi-calculator-fill me-2"></i> Hitung Simulasi
        </button>
    </form>

    <?php if ($hasil) { ?>
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white py-3 rounded-top-4 border-0">
            <h6 class="mb-0 fw-bold">Hasil Simulasi</h6>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <tr><td class="ps-4 text-muted">Harga OTR</td><td class="text-end pe-4 fw-bold"><?= rupiah($hasil['harga']) ?></td></tr>
                <tr><td class="ps-4 text-muted">DP</td><td class="text-end pe-4 fw-bold"><?= rupiah($hasil['dp']) ?></td></tr>
                <tr><td class="ps-4 text-muted">Sisa Pinjaman</td><td class="text-end pe-4 fw-bold"><?= rupiah($hasil['sisa']) ?></td></tr>
                <tr><td class="ps-4 text-muted">Tenor</td><td class="text-end pe-4 fw-bold"><?= $hasil['tenor'] ?> Bulan</td></tr>
                <tr class="table-success">
                    <td class="ps-4 fw-bold text-success border-0">Angsuran / Bulan</td>
                    <td class="text-end pe-4 fw-bold text-success fs-5 border-0"><?= rupiah($hasil['angsuran']) ?></td>
                </tr>
            </table>
        </div>
    </div>
    <?php } ?>
</div>

<script>
    function updateHarga(val) {
        let otrView = document.getElementById('otr_view');
        let otrHidden = document.getElementById('otr_hidden');
        otrView.value = formatRupiah(val);
        otrHidden.value = val;
    }

    let dpView = document.getElementById('dp_view');
    let dpHidden = document.getElementById('dp');
    dpView.addEventListener('keyup', function(e){
        this.value = formatRupiah(this.value);
        dpHidden.value = this.value.replace(/[^0-9]/g, '');
    });

    let otrView = document.getElementById('otr_view');
    let otrHidden = document.getElementById('otr_hidden');
    otrView.addEventListener('keyup', function(e){
        this.value = formatRupiah(this.value);
        otrHidden.value = this.value.replace(/[^0-9]/g, '');
    });

    function formatRupiah(angka){
        let number_string = angka.toString().replace(/[^,\d]/g, '').toString(),
            split = number_string.split(','),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);
        if(ribuan){
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        return split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
    }
</script>
</body>
</html>