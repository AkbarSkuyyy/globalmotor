<?php
require '../config/security.php';
if ($_SESSION['role'] !== 'admin') exit;

include '../config/database.php';

$data = mysqli_query($conn,"
    SELECT * FROM users
    WHERE role='karyawan'
    ORDER BY created_at DESC
");
?>

<style>
    .table-premium th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 0.5px;
        background-color: #1e293b !important;
        color: #ffffff !important;
    }
    .table-premium td {
        font-size: 14px;
        vertical-align: middle;
    }
    .badge-status {
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 6px 12px;
    }
</style>

<div class="container-fluid mt-3 mb-5 px-3" style="max-width: 1100px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0" style="font-family: 'Montserrat', sans-serif;">Data Karyawan</h3>
            <p class="text-secondary m-0">Daftar akun karyawan yang terdaftar dalam sistem.</p>
        </div>
        <a href="dashboard.php?page=user_tambah" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
            <i class="fa-solid fa-user-plus me-2"></i> Tambah Karyawan
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover table-premium mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 5%;">No</th>
                        <th>Username</th>
                        <th class="text-center">Status</th>
                        <th>Tanggal Dibuat</th>
                        <th class="text-center" style="width: 25%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
    <?php 
    $no = 1; 
    if (mysqli_num_rows($data) > 0) {
        while($u = mysqli_fetch_assoc($data)){ 
            $status_class = ($u['status'] == 'AKTIF') ? 'bg-success bg-opacity-10 text-success border border-success' : 'bg-secondary bg-opacity-10 text-secondary border border-secondary';
    ?>
    <tr>
        <td class="text-center text-secondary"><?= $no++ ?></td>
        <td class="fw-bold text-dark"><?= htmlspecialchars($u['username']) ?></td>
        <td class="text-center">
            <span class="badge rounded-pill badge-status <?= $status_class ?>">
                <?= $u['status'] ?>
            </span>
        </td>
        <td class="text-secondary small">
            <i class="fa-regular fa-calendar-days me-1"></i> <?= date('d M Y, H:i', strtotime($u['created_at'])) ?>
        </td>
        <td class="text-center">
            <div class="btn-group" role="group">
                <a href="dashboard.php?page=user_edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary shadow-sm" title="Edit"><i class="fa-solid fa-user-pen"></i></a>
                
                <a href="dashboard.php?page=user_reset&id=<?= $u['id'] ?>&role=karyawan" class="btn btn-sm btn-outline-warning shadow-sm" title="Reset Password"><i class="fa-solid fa-key"></i></a>
                
                <a href="dashboard.php?page=user_toggle&id=<?= $u['id'] ?>&aksi=<?= $u['status']=='AKTIF'?'nonaktif':'aktif' ?>&role=karyawan" 
                   class="btn btn-sm btn-outline-secondary shadow-sm" 
                   onclick="return confirm('Yakin ingin mengubah status akun ini?')" title="Toggle Status"><i class="fa-solid fa-power-off"></i></a>
                   
                <button type="button" class="btn btn-sm btn-outline-danger shadow-sm" data-bs-toggle="modal" data-bs-target="#hapusModal<?= $u['id'] ?>" title="Hapus Akun">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </td>
    </tr>

    <div class="modal fade" id="hapusModal<?= $u['id'] ?>" tabindex="-1" aria-labelledby="hapusModalLabel<?= $u['id'] ?>" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header bg-danger text-white border-0 rounded-top-4">
            <h5 class="modal-title fw-bold" id="hapusModalLabel<?= $u['id'] ?>">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> Konfirmasi Hapus Akun
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center py-4 px-4">
            <div class="mb-3">
                <i class="fa-solid fa-circle-xmark text-danger" style="font-size: 60px;"></i>
            </div>
            <h5 class="text-dark fw-bold mb-2">Apakah Anda Yakin?</h5>
            <p class="text-secondary mb-1">
                Anda akan menghapus akun karyawan <strong><?= htmlspecialchars($u['username']) ?></strong> secara permanen.
            </p>
            <div class="alert alert-warning mt-3 mb-0 text-start small border-warning bg-warning bg-opacity-10">
                <i class="fa-solid fa-circle-info me-1"></i> Data yang terkait dengan karyawan ini mungkin akan ikut terhapus atau kehilangan relasinya.
            </div>
          </div>
          <div class="modal-footer border-0 d-flex justify-content-center bg-light rounded-bottom-4">
            <button type="button" class="btn btn-secondary px-4 rounded-pill fw-bold" data-bs-dismiss="modal">Batalkan</button>
            <a href="dashboard.php?page=user_hapus&id=<?= $u['id'] ?>&role=karyawan" class="btn btn-danger px-4 rounded-pill fw-bold shadow-sm">
                Ya, Hapus Sekarang
            </a>
          </div>
        </div>
      </div>
    </div>
    <?php 
        } 
    } else { 
    ?>
    <tr>
        <td colspan="5" class="text-center py-5 text-muted fst-italic">Data tidak ditemukan.</td>
    </tr>
    <?php } ?>
</tbody>
            </table>
        </div>
    </div>
</div>