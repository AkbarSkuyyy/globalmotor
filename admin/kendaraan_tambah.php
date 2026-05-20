<?php
// FILE KONTEN (DI-INCLUDE KE DASHBOARD)
if (!isset($_SESSION['login']) || !in_array($_SESSION['role'], ['admin','karyawan'])) {
    exit;
}

include '../config/database.php';

if (isset($_POST['simpan'])) {
    // Sanitasi input
    $merk       = mysqli_real_escape_string($conn, $_POST['merk']);
    $tipe       = mysqli_real_escape_string($conn, $_POST['tipe']);
    $warna      = mysqli_real_escape_string($conn, $_POST['warna']);
    $no_polisi  = mysqli_real_escape_string($conn, $_POST['no_polisi']);
    $no_rangka  = mysqli_real_escape_string($conn, $_POST['no_rangka']);
    $no_mesin   = mysqli_real_escape_string($conn, $_POST['no_mesin']);
    $harga_cash = preg_replace('/[^0-9]/', '', $_POST['harga_cash']);

    // Pastikan harga tidak kosong, default ke 0
    $harga_cash = $harga_cash == '' ? 0 : $harga_cash;

    $query = "INSERT INTO kendaraan (merk, tipe, warna, no_polisi, no_rangka, no_mesin, harga_cash, status)
              VALUES ('$merk','$tipe','$warna','$no_polisi','$no_rangka','$no_mesin','$harga_cash','READY')";

    if (mysqli_query($conn, $query)) {
        echo "<script>
            alert('Data motor berhasil ditambahkan');
            window.location.href='dashboard.php?page=stok_motor';
        </script>";
    } else {
        echo "<script>alert('Gagal menambahkan data: " . mysqli_error($conn) . "');</script>";
    }
    exit;
}
?>

<div class="container-fluid px-0">
    <div class="d-flex align-items-center mb-4">
        <h4 class="fw-bold m-0"><i class="bi bi-plus-circle-fill text-primary me-2"></i>Tambah Data Motor</h4>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Merk Motor</label>
                        <input name="merk" class="form-control rounded-pill" placeholder="Contoh: Yamaha" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Tipe Motor</label>
                        <input name="tipe" class="form-control rounded-pill" placeholder="Contoh: NMAX 155" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Warna</label>
                        <input name="warna" class="form-control rounded-pill" placeholder="Contoh: Hitam" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">No. Polisi</label>
                        <input name="no_polisi" class="form-control rounded-pill" placeholder="Contoh: B 1234 ABC">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Harga Cash</label>
                        <div class="input-group">
                            <span class="input-group-text rounded-start-pill border-0 bg-light">Rp</span>
                            <input type="text" id="harga_view" class="form-control border-0 bg-light" placeholder="0" required>
                            <input type="hidden" name="harga_cash" id="harga_cash">
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">No. Rangka</label>
                        <input name="no_rangka" class="form-control rounded-pill" placeholder="Masukkan nomor rangka" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">No. Mesin</label>
                        <input name="no_mesin" class="form-control rounded-pill" placeholder="Masukkan nomor mesin" required>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <button type="button" onclick="window.history.back()" class="btn btn-outline-secondary rounded-pill px-4 me-2">Batal</button>
                    <button name="simpan" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="bi bi-save me-2"></i> Simpan Motor
                    </button>
                </div>
            </form>
        </div>
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