<?php
require '../config/security.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

include '../config/database.php';

date_default_timezone_set('Asia/Jakarta');

// ==========================================
// LOGIKA PAGINASI (PAGINATION)
// ==========================================
$batas = 15; // Jumlah baris data per halaman
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

// Menghitung total seluruh data di tabel audit_logs
$query_total = mysqli_query($conn, "SELECT COUNT(id) AS total FROM audit_logs");
$total_data = mysqli_fetch_assoc($query_total)['total'];
$total_halaman = ceil($total_data / $batas);

// Menarik data sesuai batas halaman (menggunakan LIMIT)
$data = mysqli_query($conn,"
    SELECT a.*, u.username
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id=u.id
    ORDER BY a.created_at DESC
    LIMIT $halaman_awal, $batas
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log - Global Motor</title>
    
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
            font-size: 13px;
            vertical-align: middle;
        }
        .pagination .page-link {
            color: #1e293b;
            border-radius: 8px;
            margin: 0 3px;
            border: 1px solid #e2e8f0;
            font-weight: 500;
        }
        .pagination .page-item.active .page-link {
            background-color: #3b82f6;
            border-color: #3b82f6;
            color: white;
        }
    </style>
</head>
<body>

    <div class="container-fluid mt-4 mb-5 px-4" style="max-width: 1200px;">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark m-0">Audit Log Sistem</h3>
                <p class="text-secondary m-0">Rekam jejak aktivitas seluruh pengguna di dalam sistem.</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="fa-solid fa-arrow-left me-2"></i>Kembali
            </a>
        </div>

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-premium mb-0">
                        <thead>
                            <tr>
                                <th class="py-3 px-4" style="width: 160px;">Waktu</th>
                                <th class="py-3" style="width: 150px;">User</th>
                                <th class="py-3 text-center" style="width: 120px;">Role</th>
                                <th class="py-3 text-center" style="width: 150px;">Aksi</th>
                                <th class="py-3">Detail Keterangan</th>
                                <th class="py-3 text-center" style="width: 140px;">Alamat IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (mysqli_num_rows($data) > 0) {
                                while($r = mysqli_fetch_assoc($data)){ 
                            ?>
                                <tr>
                                    <td class="px-4 text-secondary">
                                        <i class="fa-regular fa-clock me-1"></i> <?= date('d M Y, H:i', strtotime($r['created_at'])) ?>
                                    </td>
                                    
                                    <td class="fw-bold text-dark">
                                        <?= $r['username'] ? htmlspecialchars($r['username']) : '<span class="text-muted fw-normal fst-italic">Sistem</span>' ?>
                                    </td>
                                    
                                    <td class="text-center">
                                        <?php 
                                            // Warna badge menyesuaikan role
                                            $role = strtolower($r['role']);
                                            $badgeRole = 'bg-secondary';
                                            if ($role == 'admin') $badgeRole = 'bg-danger';
                                            if ($role == 'karyawan') $badgeRole = 'bg-primary';
                                            if ($role == 'nasabah') $badgeRole = 'bg-success';
                                        ?>
                                        <span class="badge rounded-pill <?= $badgeRole ?> px-3 py-1">
                                            <?= htmlspecialchars(strtoupper($r['role'])) ?>
                                        </span>
                                    </td>
                                    
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border border-secondary px-3 py-1 text-wrap" style="font-size: 11px;">
                                            <?= htmlspecialchars($r['aksi']) ?>
                                        </span>
                                    </td>
                                    
                                    <td class="text-secondary">
                                        <?= htmlspecialchars($r['detail']) ?>
                                    </td>
                                    
                                    <td class="text-center">
                                        <span class="badge bg-light text-secondary border px-2 py-1" style="font-family: monospace;">
                                            <i class="fa-solid fa-network-wired me-1"></i> <?= htmlspecialchars($r['ip_address']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php 
                                } 
                            } else { 
                            ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted fst-italic">
                                        Belum ada rekaman log aktivitas.
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if ($total_halaman > 1) { ?>
            <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
                <div class="text-secondary small ms-2">
                    Menampilkan Halaman <strong><?= $halaman ?></strong> dari <strong><?= $total_halaman ?></strong>
                </div>
                
                <nav>
                    <ul class="pagination pagination-sm m-0 pe-2">
                        
                        <li class="page-item <?= ($halaman <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=audit_log&halaman=<?= $halaman - 1 ?>"><i class="fa-solid fa-chevron-left"></i></a>
                        </li>

                        <?php
                        // Menentukan batasan angka yang tampil di pagination
                        $start_number = ($halaman > 3) ? $halaman - 2 : 1;
                        $end_number = ($halaman < ($total_halaman - 2)) ? $halaman + 2 : $total_halaman;
                        
                        for ($i = $start_number; $i <= $end_number; $i++) { 
                            $active = ($i == $halaman) ? 'active' : '';
                        ?>
                            <li class="page-item <?= $active ?>">
                                <a class="page-link" href="?page=audit_log&halaman=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php } ?>

                        <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=audit_log&halaman=<?= $halaman + 1 ?>"><i class="fa-solid fa-chevron-right"></i></a>
                        </li>
                        
                    </ul>
                </nav>
            </div>
            <?php } ?>
            </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>