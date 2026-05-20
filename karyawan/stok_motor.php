<?php
session_start();
require '../config/security.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'karyawan') {
    header('Location: ../auth/login.php');
    exit;
}

include '../config/database.php';

// =========================================================================
// LOGIKA FILTER PENCARIAN
// =========================================================================
$search        = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_merk   = isset($_GET['merk']) ? mysqli_real_escape_string($conn, $_GET['merk']) : '';
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

$where_clauses = [];

// Jika ada teks pencarian (mencari tipe, warna, atau pelat nomor)
if (!empty($search)) {
    $where_clauses[] = "(tipe LIKE '%$search%' OR warna LIKE '%$search%' OR no_polisi LIKE '%$search%')";
}
// Jika difilter berdasarkan merek
if (!empty($filter_merk)) {
    $where_clauses[] = "merk = '$filter_merk'";
}
// Jika difilter berdasarkan status
if (!empty($filter_status)) {
    $where_clauses[] = "status = '$filter_status'";
}

// Menggabungkan semua filter jika ada
$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Menarik data kendaraan sesuai filter, diurutkan dari yang READY terlebih dahulu
$query_motor = "SELECT * FROM kendaraan $where_sql ORDER BY status ASC, merk ASC";
$motor = mysqli_query($conn, $query_motor);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Stok Motor - Global Motor</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f1f5f9; 
            color: #334155; 
        }
        h3, h4, h5, h6 { 
            font-family: 'Montserrat', sans-serif; 
        }
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
        .form-control, .form-select {
            border-radius: 8px;
            font-size: 14px;
        }
        .form-control:focus, .form-select:focus { 
            border-color: #3b82f6; 
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25); 
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container mt-5 mb-5" style="max-width: 1100px;">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark m-0">Data Stok Kendaraan</h3>
                <p class="text-secondary m-0">Pantau ketersediaan unit motor di sistem Global Motor.</p>
            </div>
            <a href="dashboard" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="fa-solid fa-arrow-left me-2"></i> Kembali
            </a>
        </div>

        <form method="GET" action="" class="card shadow-sm border-0 rounded-4 p-3 mb-4 bg-white">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-secondary">Cari Tipe / Warna</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-secondary"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" name="search" class="form-control border-secondary" value="<?= htmlspecialchars($search) ?>" placeholder="Ketik kata kunci...">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary">Filter Merek</label>
                    <select name="merk" class="form-select border-secondary">
                        <option value="">Semua Merek</option>
                        <option value="Honda" <?= $filter_merk == 'Honda' ? 'selected' : '' ?>>Honda</option>
                        <option value="Yamaha" <?= $filter_merk == 'Yamaha' ? 'selected' : '' ?>>Yamaha</option>
                        <option value="Suzuki" <?= $filter_merk == 'Suzuki' ? 'selected' : '' ?>>Suzuki</option>
                        <option value="Kawasaki" <?= $filter_merk == 'Kawasaki' ? 'selected' : '' ?>>Kawasaki</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary">Filter Status</label>
                    <select name="status" class="form-select border-secondary">
                        <option value="">Semua Status</option>
                        <option value="READY" <?= $filter_status == 'READY' ? 'selected' : '' ?>>READY (Tersedia)</option>
                        <option value="TERJUAL" <?= $filter_status == 'TERJUAL' ? 'selected' : '' ?>>TERJUAL (Habis)</option>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold rounded-3 shadow-sm">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <a href="stok_motor" class="btn btn-outline-danger rounded-3" title="Reset Filter">
                        <i class="fa-solid fa-arrow-rotate-right"></i>
                    </a>
                </div>
            </div>
        </form>

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover table-premium mb-0 bg-white align-middle">
                    <thead>
                        <tr>
                            <th class="text-center py-3" style="width: 5%;">No</th>
                            <th class="py-3">Unit Motor</th>
                            <th class="py-3">Warna</th>
                            <th class="py-3 text-center">No. Polisi</th>
                            <th class="py-3 text-end">Harga (Rp)</th>
                            <th class="py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1; 
                        if (mysqli_num_rows($motor) > 0) {
                            while ($m = mysqli_fetch_assoc($motor)) { 
                                // Menentukan warna badge berdasarkan status
                                $isReady = (strtoupper($m['status'] ?? '') === 'READY');
                                $badgeClass = $isReady ? 'bg-success bg-opacity-10 text-success border border-success' : 'bg-secondary bg-opacity-10 text-secondary border border-secondary';
                        ?>
                            <tr>
                                <td class="text-center text-secondary"><?= $no++ ?></td>
                                
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($m['merk'] ?? '') ?></div>
                                    <div class="text-secondary small"><?= htmlspecialchars($m['tipe'] ?? '') ?></div>
                                </td>
                                
                                <td><?= htmlspecialchars($m['warna'] ?? '') ?></td>
                                
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border border-secondary px-3 py-1" style="font-family: monospace; font-size: 13px;">
                                        <?= !empty($m['no_polisi']) ? htmlspecialchars($m['no_polisi']) : '-' ?>
                                    </span>
                                </td>
                                
                                <td class="text-end fw-semibold text-primary">
                                    <?= number_format($m['harga_cash'] ?? 0, 0, ',', '.') ?>
                                </td>
                                
                                <td class="text-center">
                                    <span class="badge rounded-pill badge-status <?= $badgeClass ?>">
                                        <?= strtoupper($m['status'] ?? '') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php 
                            } 
                        } else { 
                        ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted fst-italic">
                                    <i class="fa-solid fa-magnifying-glass fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                    Tidak ada data motor yang sesuai dengan pencarian.
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>