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
           class="btn btn-sm btn-outline-danger shadow-sm" 
           onclick="return confirm('Yakin ingin mengubah status akun ini?')" title="Toggle Status"><i class="fa-solid fa-power-off"></i></a>
    </div>
</td>
    </tr>
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