<?php
require '../config/security.php';
if ($_SESSION['role'] !== 'admin') exit;

include '../config/database.php';

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
                    <a href="dashboard.php?page=user_nasabah" class="btn btn-outline-secondary rounded-pill px-4">Reset</a>
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
                                    <a href="dashboard.php?page=user_reset&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-warning shadow-sm" title="Reset Password"><i class="fa-solid fa-key"></i></a>
                                    <a href="dashboard.php?page=user_toggle&id=<?= $u['id'] ?>&aksi=<?= $u['status']=='AKTIF'?'nonaktif':'aktif' ?>" class="btn btn-sm btn-outline-danger shadow-sm" onclick="return confirm('Yakin?')" title="Toggle Status"><i class="fa-solid fa-power-off"></i></a>
                                    <a href="dashboard.php?page=nasabah_detail&no_kontrak=<?= $u['username'] ?>" class="btn btn-sm btn-outline-primary shadow-sm" title="Detail"><i class="fa-solid fa-eye"></i></a>
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