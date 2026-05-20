<?php
require '../config/security.php';
if ($_SESSION['role'] !== 'admin') exit;

include '../config/database.php';

// PERBAIKAN KEAMANAN: Mencegah SQL Injection pada fitur pencarian
$keyword = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$where = "";
if($keyword){
    $where = "WHERE 
        merk LIKE '%$keyword%' OR
        tipe LIKE '%$keyword%' OR
        warna LIKE '%$keyword%' OR
        no_rangka LIKE '%$keyword%' OR
        no_mesin LIKE '%$keyword%' OR
        no_polisi LIKE '%$keyword%'
    ";
}

$motor = mysqli_query($conn,"
    SELECT * FROM kendaraan
    $where
    ORDER BY status ASC, merk ASC, tipe ASC
");

function rupiah($a){
    return 'Rp '.number_format($a, 0, ',', '.');
}
?>

<style>
    .table-premium th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 0.5px;
        background-color: #1e293b !important; /* Warna Slate 800 */
        color: #ffffff !important;
    }
    .table-premium td {
        font-size: 13.5px;
        vertical-align: middle;
    }
    .search-input-group {
        border: 1px solid #cbd5e1;
        transition: all 0.3s ease;
    }
    .search-input-group:focus-within {
        border-color: #3b82f6;
        box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
    }
    .search-input-group input {
        border: none;
        box-shadow: none;
    }
    .search-input-group input:focus {
        box-shadow: none;
    }
</style>

<div class="container-fluid mt-3 mb-5 px-3">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0" style="font-family: 'Montserrat', sans-serif;">Data Stok Kendaraan</h3>
            <p class="text-secondary m-0">Kelola dan pantau seluruh unit motor di sistem.</p>
        </div>
        <a href="dashboard.php?page=kendaraan_tambah" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
            <i class="fa-solid fa-plus me-2"></i> Tambah Motor
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-4 mb-4 bg-white p-3">
        <form method="GET" class="row g-2 align-items-center m-0">
            <input type="hidden" name="page" value="stok_motor">
            
            <div class="col-md-6 col-lg-5">
                <div class="input-group search-input-group rounded-3 overflow-hidden bg-white">
                    <span class="input-group-text bg-white border-0 text-muted ps-3">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-0 bg-transparent"
                           placeholder="Cari Merk, Tipe, No. Rangka, atau Mesin..."
                           value="<?= htmlspecialchars($keyword) ?>">
                </div>
            </div>
            
            <div class="col-md-4 col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-dark fw-bold px-4 rounded-3 shadow-sm w-100">
                    Cari
                </button>
                <?php if($keyword): ?>
                    <a href="dashboard.php?page=stok_motor" class="btn btn-outline-danger px-3 rounded-3 shadow-sm" title="Reset Pencarian">
                        <i class="fa-solid fa-arrow-rotate-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover table-premium mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="text-center py-3" style="width: 5%;">No</th>
                        <th class="py-3">Detail Unit</th>
                        <th class="py-3">Warna</th>
                        <th class="py-3 text-center">No. Polisi</th>
                        <th class="py-3">No. Rangka / Mesin</th>
                        <th class="py-3 text-end">Harga (Rp)</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3 text-center" style="width: 10%;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php 
                    $no = 1; 
                    if (mysqli_num_rows($motor) > 0) {
                        while($m = mysqli_fetch_assoc($motor)){ 
                            // Menentukan warna badge status
                            $isReady = (strtoupper($m['status']) === 'READY');
                            $badgeClass = $isReady ? 'bg-success bg-opacity-10 text-success border-success' : 'bg-secondary bg-opacity-10 text-secondary border-secondary';
                    ?>
                        <tr>
                            <td class="text-center text-secondary"><?= $no++ ?></td>
                            
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($m['merk']) ?></div>
                                <div class="text-secondary small"><?= htmlspecialchars($m['tipe']) ?></div>
                            </td>
                            
                            <td><?= htmlspecialchars($m['warna']) ?></td>
                            
                            <td class="text-center">
                                <span class="badge bg-light text-dark border px-2 py-1" style="font-family: monospace;">
                                    <?= !empty($m['no_polisi']) ? htmlspecialchars($m['no_polisi']) : '-' ?>
                                </span>
                            </td>
                            
                            <td>
                                <div class="small text-secondary"><span class="fw-semibold">Rangka:</span> <?= !empty($m['no_rangka']) ? htmlspecialchars($m['no_rangka']) : '-' ?></div>
                                <div class="small text-secondary mt-1"><span class="fw-semibold">Mesin:</span> <?= !empty($m['no_mesin']) ? htmlspecialchars($m['no_mesin']) : '-' ?></div>
                            </td>
                            
                            <td class="text-end fw-bold text-primary">
                                <?= rupiah($m['harga_cash']) ?>
                            </td>
                            
                            <td class="text-center">
                                <span class="badge rounded-pill border <?= $badgeClass ?> px-3 py-1" style="font-size: 11px;">
                                    <?= strtoupper($m['status']) ?>
                                </span>
                            </td>
                            
                            <td class="text-center">
                                <a href="dashboard.php?page=kendaraan_edit&id=<?= $m['id'] ?>" 
                                   class="btn btn-sm btn-outline-primary fw-medium rounded-3 px-3 shadow-sm" title="Edit Data">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </a>
                            </td>
                        </tr>
                    <?php 
                        } 
                    } else { 
                    ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted fst-italic">
                                <i class="fa-solid fa-inbox fs-2 d-block mb-3 opacity-50"></i>
                                <?php if($keyword): ?>
                                    Tidak ada data motor yang cocok dengan pencarian "<strong><?= htmlspecialchars($keyword) ?></strong>".
                                <?php else: ?>
                                    Belum ada data kendaraan di dalam sistem.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

</div>