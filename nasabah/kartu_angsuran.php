<?php
ob_start();
session_start();

date_default_timezone_set('Asia/Jakarta');

require '../config/security.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'nasabah') {
    header('Location: ../auth/login.php');
    exit;
}

require '../config/database.php';
require '../fpdf/fpdf.php';
require '../phpqrcode/qrlib.php';

$user_id = $_SESSION['user_id'];

/* ================= AMBIL DATA ================= */
$query_data = mysqli_query($conn,"
    SELECT 
        p.id,
        p.no_kontrak,
        p.tenor,
        p.angsuran,
        k.merk, k.tipe, k.warna
    FROM penjualan p
    JOIN kendaraan k ON p.kendaraan_id = k.id
    JOIN users u ON u.username = p.no_kontrak
    WHERE u.id = '$user_id'
");
$data = mysqli_fetch_assoc($query_data);

// PENGAMAN: Jika belum ada data transaksi, hentikan eksekusi FPDF dengan pesan ramah
if (!$data) {
    die("<h3>Data kredit tidak ditemukan.</h3><p>Akun ini belum memiliki transaksi aktif di Global Motor. Silakan kembali ke <a href='dashboard.php'>Dashboard</a>.</p>");
}

$angsuran = mysqli_query($conn,"
    SELECT bulan_ke, jatuh_tempo, jumlah, status
    FROM angsuran
    WHERE penjualan_id = '{$data['id']}'
    ORDER BY bulan_ke ASC
");

/* ================== MEMULAI PDF ================== */
$pdf = new FPDF('P','mm','A4');
$pdf->AddPage();

$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,8,'KARTU ANGSURAN GLOBAL MOTOR',0,1,'C');
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,6,'Jl. MT Haryono No. 123, Sampit, Kalimantan Tengah',0,1,'C');
$pdf->Ln(5);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(35,6,'No Kontrak',0,0);
$pdf->SetFont('Arial','',10);
$pdf->Cell(5,6,':',0,0);
$pdf->Cell(0,6,$data['no_kontrak'],0,1);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(35,6,'Kendaraan',0,0);
$pdf->SetFont('Arial','',10);
$pdf->Cell(5,6,':',0,0);
$pdf->Cell(0,6,$data['merk'].' '.$data['tipe'].' ('.$data['warna'].')',0,1);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(35,6,'Tenor',0,0);
$pdf->SetFont('Arial','',10);
$pdf->Cell(5,6,':',0,0);
$pdf->Cell(0,6,$data['tenor'].' Bulan',0,1);

$pdf->Ln(5);

$pdf->SetFont('Arial','B',10);
$pdf->SetFillColor(230,230,230);
$pdf->Cell(20,8,'Bulan',1,0,'C',true);
$pdf->Cell(40,8,'Jatuh Tempo',1,0,'C',true);
$pdf->Cell(45,8,'Jumlah',1,0,'C',true);
$pdf->Cell(40,8,'Status',1,1,'C',true);

$pdf->SetFont('Arial','',9);

$total_lunas = 0;

while($a = mysqli_fetch_assoc($angsuran)){

    if($a['status'] == 'SUDAH LUNAS' || $a['status'] == 'LUNAS'){
        $pdf->SetTextColor(0,128,0);
        $total_lunas++;
    }
    elseif($a['status'] == 'BELUM LUNAS' || $a['status'] == 'BELUM'){
        $pdf->SetTextColor(200,0,0);
    }
    else{
        $pdf->SetTextColor(0,0,0);
    }

    $pdf->Cell(20,8,$a['bulan_ke'],1,0,'C');
    $pdf->Cell(40,8,date('d-m-Y',strtotime($a['jatuh_tempo'])),1);
    $pdf->Cell(45,8,'Rp '.number_format((float)$a['jumlah'],0,',','.'),1,0,'R');
    $pdf->Cell(40,8,$a['status'],1,1,'C');
}

$pdf->SetTextColor(0,0,0);
$pdf->Ln(5);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,'Ringkasan: Lunas ('.$total_lunas.') / Total ('.$data['tenor'].')',0,1);

$pdf->Output('I', 'Kartu_Angsuran_'.$data['no_kontrak'].'.pdf');
ob_end_flush();
?>