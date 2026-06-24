<?php
// admin/kendaraan_edit.php (Di-include dari dashboard.php)

$id_kendaraan = $_GET['id'] ?? '';
if (empty($id_kendaraan)) {
    echo "<script>window.location='dashboard';</script>";
    exit;
}

// Proses Update Data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_motor'])) {
    $merk       = mysqli_real_escape_string($conn, $_POST['merk']);
    $tipe       = mysqli_real_escape_string($conn, $_POST['tipe']);
    $warna      = mysqli_real_escape_string($conn, $_POST['warna']);
    $no_rangka  = mysqli_real_escape_string($conn, $_POST['no_rangka']);
    $no_mesin   = mysqli_real_escape_string($conn, $_POST['no_mesin']);
    // Filter hanya mengambil angka (mengabaikan 'Rp' dan titik)
    $harga_cash = preg_replace('/[^0-9]/', '', $_POST['harga_cash']);

    mysqli_query($conn, "
        UPDATE kendaraan 
        SET merk='$merk', tipe='$tipe', warna='$warna', no_rangka='$no_rangka', no_mesin='$no_mesin', harga_cash='$harga_cash' 
        WHERE id='$id_kendaraan'
    ");

    echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Berhasil Diperbarui!',
                text: 'Data unit kendaraan berhasil diubah.',
                icon: 'success',
                confirmButtonColor: '#10b981',
                allowOutsideClick: false
            }).then(() => {
                // Redirect agar aman dari Error Resubmission Browser
                window.location = document.referrer ? document.referrer : 'dashboard'; 
            });
        });
    </script>
    ";
}

// Ambil data saat ini
$q = mysqli_query($conn, "SELECT * FROM kendaraan WHERE id='$id_kendaraan'");
$motor = mysqli_fetch_assoc($q);
if (!$motor) {
    echo "<script>window.location='dashboard';</script>";
    exit;
}
?>

<div class="container-fluid mt-4 mb-5" style="max-width: 700px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0 text-dark"><i class="fa-solid fa-pen-to-square text-primary me-2"></i>Edit Data Motor</h4>
        <a href="dashboard" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="fa-solid fa-arrow-left me-2"></i>Batal
        </a>
    </div>

    <form method="POST" action="" class="card shadow-sm border-0 rounded-4 p-4 bg-white needs-validation" novalidate>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">Merek</label>
                <input type="text" name="merk" class="form-control" value="<?= htmlspecialchars($motor['merk']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">Tipe/Model</label>
                <input type="text" name="tipe" class="form-control" value="<?= htmlspecialchars($motor['tipe']) ?>" required>
            </div>
            <div class="col-md-12">
                <label class="form-label fw-bold small text-secondary">Warna</label>
                <input type="text" name="warna" class="form-control" value="<?= htmlspecialchars($motor['warna']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">No. Rangka</label>
                <input type="text" name="no_rangka" class="form-control text-uppercase font-monospace" value="<?= htmlspecialchars($motor['no_rangka']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">No. Mesin</label>
                <input type="text" name="no_mesin" class="form-control text-uppercase font-monospace" value="<?= htmlspecialchars($motor['no_mesin']) ?>" required>
            </div>
            <div class="col-md-12 mt-4">
                <label class="form-label fw-bold small text-secondary">Harga OTR (Rp)</label>
                <input type="text" name="harga_cash" id="harga_cash" class="form-control form-control-lg fw-bold text-success" value="Rp <?= number_format($motor['harga_cash'],0,',','.') ?>" required>
            </div>
        </div>

        <div class="mt-4 text-end border-top pt-4">
            <button type="submit" name="update_motor" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm">
                <i class="fa-solid fa-save me-2"></i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>

<script>
    // JS Format disamakan dengan modul tambah kredit
    const hargaInput = document.getElementById('harga_cash');
    hargaInput.addEventListener('input', function() {
        let angka = this.value.replace(/[^0-9]/g, '');
        if (angka === '') { this.value = ''; return; }
        this.value = 'Rp ' + angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    });

    // Validasi form bootstrap
    (() => {
        'use strict'
        const forms = document.querySelectorAll('.needs-validation')
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })();
</script>