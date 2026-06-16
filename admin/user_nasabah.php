<?php
require '../config/security.php';
if ($_SESSION['role'] !== 'admin') exit;

include '../config/database.php';

$script_alert = '';

// ====== PROSES UBAH PASSWORD MANUAL OLEH ADMIN ======
if (isset($_POST['ganti_password_admin'])) {
    $user_id = (int)$_POST['user_id'];
    $password_baru = mysqli_real_escape_string($conn, $_POST['password_baru']);
    $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
    
    $update = mysqli_query($conn, "UPDATE users SET password='$password_hash' WHERE id='$user_id'");
    if ($update) {
        $script_alert = "
            Swal.fire({
                title: 'Berhasil!',
                text: 'Password nasabah berhasil diperbarui secara manual.',
                icon: 'success',
                confirmButtonColor: '#3b82f6'
            }).then(() => {
                window.location='dashboard?page=user_nasabah';
            });
        ";
    } else {
        $error = mysqli_error($conn);
        $script_alert = "
            Swal.fire({
                title: 'Gagal!',
                text: 'Terjadi kesalahan sistem: $error',
                icon: 'error',
                confirmButtonColor: '#ef4444'
            });
        ";
    }
}

$keyword = $_GET['search'] ?? '';
$where = $keyword ? "AND (u.username LIKE '%$keyword%' OR np.nama LIKE '%$keyword%')" : "";

$data = mysqli_query($conn,"
    SELECT u.*, np.nama
    FROM users u
    LEFT JOIN nasabah_profile np ON np.no_kontrak = u.username
    WHERE u.role='nasabah' $where
    ORDER BY u.created_at DESC
");
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .table-premium th {
        font-weight: 600; text-transform: uppercase; font-size: 13px;
        background-color: #1e293b !important; color: #ffffff !important;
    }
    .badge-status { font-size: 11px; padding: 6px 12px; }
</style>

<div class="container-fluid mt-3 mb-5 px-3" style="max-width: 1100px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Data Nasabah</h3>
            <p class="text-secondary m-0">Daftar nasabah yang terdaftar dalam sistem.</p>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <form method="GET" class="row mb-4 g-2">
                <input type="hidden" name="page" value="user_nasabah">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control rounded-pill" 
                           placeholder="Cari Nama atau No Kontrak..." value="<?= htmlspecialchars($keyword) ?>">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary rounded-pill px-4"><i class="fa-solid fa-search me-2"></i>Cari</button>
                    <a href="dashboard?page=user_nasabah" class="btn btn-outline-secondary rounded-pill px-4">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover table-premium align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center">No</th>
                            <th>Nama Nasabah</th>
                            <th>No Kontrak</th>
                            <th class="text-center">Status</th>
                            <th>Tanggal</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no=1; while($u = mysqli_fetch_assoc($data)){ 
                        $status_class = ($u['status']=='AKTIF') ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary';
                        $nama_tampil = $u['nama'] ? $u['nama'] : $u['username'];
                    ?>
                        <tr>
                            <td class="text-center text-secondary"><?= $no++ ?></td>
                            <td class="fw-bold"><?= $u['nama'] ? htmlspecialchars($u['nama']) : '<span class="text-muted fst-italic">Belum diisi</span>' ?></td>
                            <td><?= $u['username'] ?></td>
                            <td class="text-center">
                                <span class="badge rounded-pill badge-status <?= $status_class ?> border"><?= $u['status'] ?></span>
                            </td>
                            <td class="small text-secondary"><?= date('d M Y, H:i', strtotime($u['created_at'])) ?></td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-warning shadow-sm" title="Ganti Password Manual" onclick="bukaModalPassword(<?= $u['id'] ?>, '<?= htmlspecialchars($nama_tampil) ?>')">
                                        <i class="fa-solid fa-key"></i>
                                    </button>
                                    <a href="dashboard?page=user_toggle&id=<?= $u['id'] ?>&aksi=<?= $u['status']=='AKTIF'?'nonaktif':'aktif' ?>" class="btn btn-sm btn-outline-danger shadow-sm" onclick="return confirm('Yakin?')" title="Toggle Status"><i class="fa-solid fa-power-off"></i></a>
                                    <a href="dashboard?page=nasabah_detail&no_kontrak=<?= $u['username'] ?>" class="btn btn-sm btn-outline-primary shadow-sm" title="Detail"><i class="fa-solid fa-eye"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalGantiPassword" tabindex="-1" aria-labelledby="modalGantiPasswordLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-dark" id="modalGantiPasswordLabel"><i class="fa-solid fa-key text-warning me-2"></i>Ganti Password Nasabah</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body px-4 pb-3">
                    <input type="hidden" name="user_id" id="modal_user_id">
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-semibold small">Nama Nasabah</label>
                        <input type="text" id="modal_nama_nasabah" class="form-control bg-light border-0 fw-bold py-2" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-semibold small">Ketik Password Baru</label>
                        <input type="text" name="password_baru" class="form-control py-2 text-dark fw-medium" placeholder="Contoh: motorbaru123" required minlength="4">
                        <small class="text-muted small mt-2 d-block">Admin bebas membuatkan kombinasi password apa saja sesuai request langsung dari nasabah.</small>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="ganti_password_admin" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">Simpan Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Jalankan eksekusi pop-up dari instruksi PHP jika form dikirim
    <?php if(!empty($script_alert)){ echo $script_alert; } ?>

    // Fungsi Javascript untuk memasukkan parameter ke dalam form modal
    function bukaModalPassword(id, nama) {
        document.getElementById('modal_user_id').value = id;
        document.getElementById('modal_nama_nasabah').value = nama;
        
        var myModal = new bootstrap.Modal(document.getElementById('modalGantiPassword'));
        myModal.show();
    }
</script>