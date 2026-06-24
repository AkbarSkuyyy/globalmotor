<?php
ob_start();
session_start();

date_default_timezone_set('Asia/Jakarta');

require '../config/security.php';
require '../config/database.php';
require '../fpdf/fpdf.php';

// Pastikan user adalah nasabah
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nasabah') {
    header('Location: ../auth/login');
    exit;
}

$user_id = $_SESSION['user_id'];

// AMBIL ID TRANSAKSI DARI URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("<h3>Akses Ditolak</h3><p>ID Transaksi tidak ditemukan. <a href='dashboard'>Kembali ke Dashboard</a></p>");
}
$id_penjualan = mysqli_real_escape_string($conn, $_GET['id']);

/* ================= AMBIL DATA DENGAN FILTER USER_ID & ID PENJUALAN ================= */
$query_string = "
    SELECT 
        p.id, p.no_kontrak, p.tenor, p.angsuran,
        k.merk, k.tipe, k.warna
    FROM penjualan p
    JOIN kendaraan k ON p.kendaraan_id = k.id
    JOIN users u ON u.username = p.no_kontrak
    WHERE u.id = '$user_id' AND p.id = '$id_penjualan'
    LIMIT 1
";

$query_data = mysqli_query($conn, $query_string);

if (!$query_data) {
    die("Query Error: " . mysqli_error($conn));
}

$data = mysqli_fetch_assoc($query_data);

if (!$data) {
    die("<h3>Data angsuran tidak ditemukan atau bukan milik Anda.</h3><p><a href='dashboard'>Kembali</a></p>");
}

// Ambil angsuran berdasarkan ID penjualan (Ditambahkan: kolom sisa_tagihan)
$angsuran = mysqli_query($conn, "
    SELECT bulan_ke, jatuh_tempo, jumlah, sisa_tagihan, status
    FROM angsuran
    WHERE penjualan_id = '{$data['id']}'
    ORDER BY bulan_ke ASC
");

/* ================== GENERATE PDF ================== */
$pdf = new FPDF('P','mm','A4');
$pdf->AddPage();

// Header Aplikasi
$pdf->SetFillColor(245, 247, 250);
$pdf->Rect(0, 0, 210, 45, 'F');

if (file_exists('../assets/logohitam.png')) {
    $pdf->Image('../assets/logohitam.png', 20, 10, 25);
}

$pdf->SetXY(50, 12);
$pdf->SetFont('Arial','B',20);
$pdf->SetTextColor(44, 62, 80);
$pdf->Cell(0, 10, 'GLOBAL MOTOR', 0, 1, 'L');
$pdf->SetX(50);
$pdf->SetFont('Arial','',10);
$pdf->Cell(0, 5, 'KARTU ANGSURAN RESMI', 0, 1, 'L');
$pdf->Ln(15);

// Informasi Utama
$pdf->SetFillColor(255, 255, 255);
$pdf->SetDrawColor(220, 220, 220);
$pdf->Rect(15, 50, 180, 25, 'FD');
$pdf->SetXY(20, 52);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(30, 7, 'No. Kontrak', 0, 0);
$pdf->Cell(5, 7, ':', 0, 0);
$pdf->SetFont('Arial','',10);
$pdf->Cell(0, 7, $data['no_kontrak'], 0, 1);
$pdf->SetX(20);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(30, 7, 'Unit Motor', 0, 0);
$pdf->Cell(5, 7, ':', 0, 0);
$pdf->SetFont('Arial','',10);
$pdf->Cell(0, 7, $data['merk'].' '.$data['tipe'].' - '.$data['warna'].' (Tenor: '.$data['tenor'].' Bulan)', 0, 1);

$pdf->Ln(15);

// Tabel Angsuran
$pdf->SetX(15); 
$pdf->SetFont('Arial','B',10);
$pdf->SetFillColor(59, 130, 246); // Warna Biru Modern
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(15, 10, 'Bln', 1, 0, 'C', true);
$pdf->Cell(35, 10, 'Jatuh Tempo', 1, 0, 'C', true);
$pdf->Cell(45, 10, 'Tagihan (Rp)', 1, 0, 'C', true);
$pdf->Cell(45, 10, 'Sisa Hutang (Rp)', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'Status', 1, 1, 'C', true);

$pdf->SetFont('Arial','',10);

while($a = mysqli_fetch_assoc($angsuran)){
    $status = strtoupper(trim($a['status']));
    
    // LOGIKA CERDAS: Mencegah nilai sisa_tagihan NULL dari data lama
    $sisa_tagihan_aktual = is_null($a['sisa_tagihan']) ? $a['jumlah'] : $a['sisa_tagihan'];
    
    // Pewarnaan baris dan Status Teks
    if($status == 'LUNAS' || $status == 'SUDAH LUNAS'){
        $status_label = 'LUNAS';
        $pdf->SetTextColor(21, 128, 61); // Hijau
        $sisa_tagihan_aktual = 0; // Pastikan sisa tagihan tercetak 0 jika sudah Lunas
    } elseif ($status == 'PENDING') {
        $status_label = 'DIPROSES';
        $pdf->SetTextColor(202, 138, 4); // Kuning/Orange
    } else {
        $status_label = 'BELUM BAYAR';
        $pdf->SetTextColor(220, 38, 38); // Merah
    }

    $pdf->SetX(15);
    $pdf->Cell(15, 10, $a['bulan_ke'], 1, 0, 'C');
    $pdf->Cell(35, 10, date('d/m/Y', strtotime($a['jatuh_tempo'])), 1, 0, 'C');
    $pdf->Cell(45, 10, number_format((float)$a['jumlah'], 0, ',', '.'), 1, 0, 'R');
    $pdf->Cell(45, 10, number_format((float)$sisa_tagihan_aktual, 0, ',', '.'), 1, 0, 'R');
    $pdf->Cell(40, 10, $status_label, 1, 1, 'C');
}

$pdf->SetTextColor(0, 0, 0); // Kembalikan ke warna hitam normal
$pdf->Ln(10);
$pdf->SetX(15);
$pdf->SetFont('Arial','I',8);
$pdf->Cell(0, 5, 'Dicetak pada: ' . date('d F Y, H:i') . ' WIB. Dokumen ini adalah laporan sistem yang sah.', 0, 1, 'L');

ob_end_clean(); 
$pdf->Output('I', 'Kartu_Angsuran_'.$data['no_kontrak'].'.pdf');
?>