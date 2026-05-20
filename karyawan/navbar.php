<?php
// Mendeteksi nama file yang sedang aktif secara dinamis
$current_page = basename($_SERVER['PHP_SELF']);
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    /* ================= NAVBAR LIGHT PREMIUM COMPONENT ================= */
    .navbar-light-premium {
        background-color: #ffffff;
        padding: 12px 0;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .nav-item {
        margin: 0 3px;
    }
    .nav-link {
        color: #64748b !important;
        font-size: 14px;
        padding: 10px 16px !important;
        border-radius: 10px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        font-weight: 600;
    }
    .nav-link i {
        margin-right: 8px;
        font-size: 15px;
        transition: transform 0.2s;
    }
    
    .nav-link:hover {
        background-color: #f8fafc;
        color: #3b82f6 !important; 
    }
    .nav-link:hover i {
        transform: scale(1.15);
    }
    
    /* Indikator Menu Aktif Otomatis */
    .nav-link.active {
        background-color: #3b82f6 !important; 
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); 
    }
    
    .dropdown-menu {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        margin-top: 15px;
    }
    .dropdown-item {
        padding: 10px 20px;
        font-size: 14px;
        font-weight: 600;
        transition: 0.2s;
    }
    .dropdown-item.text-danger:hover {
        background-color: #fef2f2;
        color: #ef4444 !important;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-light-premium shadow-sm sticky-top">
    <div class="container d-flex align-items-center">
        
        <a class="navbar-brand m-0 p-0 me-lg-4" href="dashboard">
            <img src="../assets/logohitam.png" alt="GLOBAL MOTOR" style="height: 42px; object-fit: contain;">
        </a>
        
        <button class="navbar-toggler border-0 shadow-none p-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <i class="fa-solid fa-bars fs-3 text-dark"></i>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mt-3 mt-lg-0 gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'dashboard.php' || $current_page == 'dashboard') ? 'active' : '' ?>" href="dashboard">
                        <i class="fa-solid fa-house"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'tambah_motor.php' || $current_page == 'tambah_motor') ? 'active' : '' ?>" href="tambah_motor">
                        <i class="fa-solid fa-box-open"></i> Input Motor
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'tambah_kredit.php' || $current_page == 'tambah_kredit') ? 'active' : '' ?>" href="tambah_kredit">
                        <i class="fa-solid fa-file-invoice-dollar"></i> Input Kredit
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'stok_motor.php' || $current_page == 'stok_motor') ? 'active' : '' ?>" href="stok_motor">
                        <i class="fa-solid fa-warehouse"></i> Stok Motor
                    </a>
                </li>
            </ul>
            
            <div class="d-flex align-items-center gap-3 ms-auto mt-3 mt-lg-0 border-start ps-lg-3">
                <div class="dropdown">
                    <a class="text-decoration-none text-dark fw-bold d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                        <span><?= htmlspecialchars($_SESSION['username'] ?? 'Karyawan') ?></span>
                        <i class="fa-solid fa-circle-user fs-4 text-primary"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
      <li>
        <a class="dropdown-item text-danger" href="../auth/logout">
            <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
        </a>
    </li>
</ul>
                </div>
            </div>

        </div>
    </div>
</nav>