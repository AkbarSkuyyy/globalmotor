<?php
session_start();
require '../config/security.php';

if (!in_array($_SESSION['role'], ['admin','karyawan'])) {
    header('Location: ../auth/login.php');
    exit;
}

include '../config/database.php';
// Memanggil library QR Code bawaan Anda
require '../phpqrcode/qrlib.php'; 

date_default_timezone_set('Asia/Jakarta');

$tgl_awal  = isset($_GET['tgl_awal']) ? mysqli_real_escape_string($conn, $_GET['tgl_awal']) : '';
$tgl_akhir = isset($_GET['tgl_akhir']) ? mysqli_real_escape_string($conn, $_GET['tgl_akhir']) : '';

$where_clause = " WHERE p.status='VALID' ";
$title = "SEMUA PERIODE";

if (!empty($tgl_awal) && !empty($tgl_akhir)) {
    $where_clause .= " AND DATE(p.created_at) BETWEEN '$tgl_awal' AND '$tgl_akhir' ";
    $title = "PERIODE " . date('d-m-Y', strtotime($tgl_awal)) . " s/d " . date('d-m-Y', strtotime($tgl_akhir));
}

$query = mysqli_query($conn, "
    SELECT 
        p.created_at AS tgl_bayar,
        p.kode_unik,
        pj.no_kontrak,
        COALESCE(np.nama, '-') AS nama,
        a.bulan_ke,
        a.jumlah AS angsuran_pokok
    FROM pembayaran p
    JOIN angsuran a ON p.angsuran_id = a.id
    JOIN penjualan pj ON a.penjualan_id = pj.id
    LEFT JOIN nasabah_profile np ON pj.no_kontrak = np.no_kontrak
    $where_clause
    ORDER BY p.created_at ASC
");

function format_uang($angka) {
    return number_format((float)$angka, 0, ',', '.');
}

$filename = "Laporan_Keuangan_GlobalMotor_" . date('Ymd_His') . ".pdf";

$data_transaksi = [];
$total_pendapatan = 0;
while ($r = mysqli_fetch_assoc($query)) {
    $jumlah = $r['angsuran_pokok'] + $r['kode_unik'];
    $total_pendapatan += $jumlah;
    $r['total_transfer'] = $jumlah;
    $data_transaksi[] = $r;
}

/* ================= GENERATE QR CODE LOKAL (BASE64) ================= */
$qrText = "LAPORAN GLOBAL MOTOR\n" .
          "Periode: $title\n" .
          "Total: Rp " . format_uang($total_pendapatan) . "\n" .
          "Dicetak: " . date('d-m-Y H:i:s') . " WIB";

ob_start();
QRcode::png($qrText, null, QR_ECLEVEL_L, 4, 0);
$qrImage = ob_get_contents();
ob_end_clean();

$qrDataUri = 'data:image/png;base64,' . base64_encode($qrImage);
/* =================================================================== */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan Global Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #000;
            background: #e2e8f0; 
            padding: 20px 0;
        }
        
        h2, h3, h4, h5 {
            font-family: 'Montserrat', sans-serif;
        }
        
        #area-cetak {
            background: #ffffff;
            width: 210mm;          
            min-height: 297mm;     
            padding: 15mm 20mm; 
            margin: 0 auto;        
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); 
            box-sizing: border-box;
            position: relative;
        }
        
        .print-header {
            display: flex;
            align-items: center;
            border-bottom: 3px solid #000; 
            padding-bottom: 12px;
            margin-bottom: 20px;
            position: relative;
        }
        
        .print-header::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: -5px; 
            width: 100%;
            border-bottom: 1px solid #000;
        }

        .header-logo {
            flex: 0 0 90px; 
        }

        .header-logo img {
            width: 100%;
            height: auto;
            object-fit: contain;
        }

        .header-text {
            flex: 1;
            text-align: center;
            padding-right: 90px; 
        }
        
        .info-box {
            font-size: 13px;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .table-premium {
            font-size: 13px; 
            width: 100%;
            border-collapse: collapse;
        }
        .table-premium th {
            background-color: #e6e6e6 !important;
            color: #000 !important;
            border: 1px solid #000;
            padding: 8px;
        }
        .table-premium td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: middle;
        }
        .table-premium tr {
            page-break-inside: avoid;
        }
        
        #loading-screen {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255,255,255,0.97);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
    </style>
</head>
<body>

    <div id="loading-screen">
        <div class="spinner-border text-dark mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
        <h3 class="fw-bold text-dark mb-2">Mempersiapkan Dokumen...</h3>
        <p class="text-secondary">File PDF akan otomatis ter-download.</p>
    </div>

    <div id="area-cetak">
        
        <div class="print-header">
            <div class="header-logo">
                <img src="../assets/logohitam.png" alt="Logo">
            </div>
            <div class="header-text">
                <h3 class="fw-bold m-0" style="letter-spacing: 1px;">LAPORAN KEUANGAN GLOBAL MOTOR</h3>
                <p class="m-0 mt-2" style="font-size: 12px;">Jl. Bakrie Entong Kec. Hanau Kel. Pembuang Hulu | Telp: 085252930293</p>
            </div>
        </div>

        <div class="info-box">
            <div><?php echo $title; ?></div>
            <div>Dicetak pada : <?php echo date('d-m-Y H:i:s'); ?> WIB</div>
            <div>Dicetak oleh : <?php echo htmlspecialchars(strtoupper($_SESSION['role'])); ?></div>
            
            <div class="mt-3 fw-bold">Ringkasan Keuangan</div>
            <div>Total Transaksi : <?php echo count($data_transaksi); ?></div>
            <div>Total Pemasukan : Rp <?php echo format_uang($total_pendapatan); ?></div>
        </div>

        <table class="table-premium mt-3">
            <thead>
                <tr class="text-center fw-bold">
                    <th style="width: 5%;">No</th>
                    <th style="width: 20%;">Tanggal</th>
                    <th style="width: 20%;">No Kontrak</th>
                    <th>Nama</th>
                    <th style="width: 20%;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                foreach ($data_transaksi as $r) {
                ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td class="text-center"><?php echo date('d-m-Y', strtotime($r['tgl_bayar'])); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($r['no_kontrak']); ?></td>
                        <td><?php echo htmlspecialchars($r['nama']); ?></td>
                        <td class="text-end">Rp <?php echo format_uang($r['total_transfer']); ?></td>
                    </tr>
                <?php } ?>
                
                <tr class="fw-bold bg-light">
                    <td colspan="4" class="text-start">TOTAL</td>
                    <td class="text-end">Rp <?php echo format_uang($total_pendapatan); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="d-flex justify-content-end mt-5 pt-3 pe-3">
            <div class="text-center" style="min-width: 180px; font-size: 13px;">
                <p class="m-0">Mengetahui,</p>
                <div class="my-2">
                    <img src="<?php echo $qrDataUri; ?>" alt="QR Code" style="width: 80px; height: 80px;">
                </div>
                <p class="fw-bold m-0" style="text-decoration: underline;">Manager Keuangan</p>
            </div>
        </div>

    </div>

    <script>
        window.onload = function() {
            const element = document.getElementById('area-cetak');
            
            const opt = {
                margin:       0, 
                filename:     '<?php echo $filename; ?>',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { 
                    scale: 2,         
                    useCORS: true,      
                    allowTaint: true    
                },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(function() {
                document.getElementById('loading-screen').innerHTML = `
                    <div class="text-center bg-white p-5 rounded-4 shadow" style="max-width: 500px; margin: 100px auto;">
                        <h1 style="font-size: 55px;">💾</h1>
                        <h4 class="fw-bold text-success mt-3 mb-2">PDF Berhasil Dibuat!</h4>
                        <p class="text-secondary small">Berkas laporan ukuran A4 sudah di-download dan siap dicetak.</p>
                        <hr class="my-4" style="opacity:0.1">
                        <button onclick="window.close();" class="btn btn-sm btn-dark px-4 rounded-3">Tutup Halaman Ini</button>
                    </div>
                `;
            });
        };
    </script>

</body>
</html>