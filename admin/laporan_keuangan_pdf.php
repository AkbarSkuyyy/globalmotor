<?php
ob_start();
session_start();
date_default_timezone_set('Asia/Jakarta');

require '../config/security.php';

if (!in_array($_SESSION['role'], ['admin','karyawan'])) {
    header('Location: ../auth/login.php');
    exit;
}

include '../config/database.php';
require('../fpdf/fpdf.php');
require('../phpqrcode/qrlib.php');

$tgl_awal  = $_GET['tgl_awal'] ?? '';
$tgl_akhir = $_GET['tgl_akhir'] ?? '';

$where = "WHERE pb.status='VALID'";
$title = "SEMUA PERIODE";

if ($tgl_awal && $tgl_akhir) {
    $where .= " AND DATE(pb.created_at) BETWEEN '$tgl_awal' AND '$tgl_akhir'";
    $title = "PERIODE $tgl_awal s/d $tgl_akhir";
}

$query = mysqli_query($conn, "
    SELECT pb.created_at, p.no_kontrak, c.nama, a.jumlah, pb.kode_unik
    FROM pembayaran pb
    JOIN angsuran a ON pb.angsuran_id = a.id
    JOIN penjualan p ON a.penjualan_id = p.id
    JOIN customers c ON p.customer_id = c.id
    $where
    ORDER BY pb.created_at ASC
");

class PDF extends FPDF {

    function Header(){
        $this->Image('../assets/logohitam.png',10,8,25);

        $this->SetFont('Arial','B',14);
        $this->Cell(0,7,'LAPORAN KEUANGAN GLOBAL MOTOR',0,1,'C');

        $this->SetFont('Arial','',9);
        $this->Cell(0,5,'Jl. Bakrie Entong Kec. Hanau Kel. Pembuang Hulu | Telp: 085252930293',0,1,'C');
        $this->Ln(5);

        $this->Line(10,32,200,32);
        $this->Ln(8);
    }

    function Footer(){
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(
            0,
            10,
            'Dicetak pada '.date('d-m-Y H:i:s').' WIB - Halaman '.$this->PageNo(),
            0,
            0,
            'C'
        );
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial','',10);

/* ================= INFO ================= */

$pdf->Cell(0,6,$title,0,1,'L');
$pdf->Cell(0,6,'Dicetak pada : '.date('d-m-Y H:i:s').' WIB',0,1,'L');
$pdf->Cell(0,6,'Dicetak oleh : '.$_SESSION['role'],0,1,'L');
$pdf->Ln(5);

/* ================= DATA ================= */

$total = 0;
$data = [];

while ($r = mysqli_fetch_assoc($query)) {
    $jumlah = $r['jumlah'] + $r['kode_unik'];
    $total += $jumlah;
    $data[] = $r;
}

/* ================= RINGKASAN ================= */

$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,'Ringkasan Keuangan',0,1);
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,6,'Total Transaksi : '.count($data),0,1);
$pdf->Cell(0,6,'Total Pemasukan : Rp '.number_format($total,0,',','.'),0,1);
$pdf->Ln(5);

/* ================= TABEL ================= */

$pdf->SetFont('Arial','B',9);
$pdf->SetFillColor(230,230,230);

$pdf->Cell(10,8,'No',1,0,'C',true);
$pdf->Cell(30,8,'Tanggal',1,0,'C',true);
$pdf->Cell(35,8,'No Kontrak',1,0,'C',true);
$pdf->Cell(55,8,'Nama',1,0,'C',true);
$pdf->Cell(35,8,'Jumlah',1,1,'C',true);

$pdf->SetFont('Arial','',9);

$no = 1;
foreach($data as $r){
    $jumlah = $r['jumlah'] + $r['kode_unik'];

    $pdf->Cell(10,8,$no++,1,0,'C');
    $pdf->Cell(30,8,date('d-m-Y',strtotime($r['created_at'])),1);
    $pdf->Cell(35,8,$r['no_kontrak'],1);
    $pdf->Cell(55,8,$r['nama'],1);
    $pdf->Cell(35,8,'Rp '.number_format($jumlah,0,',','.'),1,1,'R');
}

/* ================= TOTAL ================= */

$pdf->SetFont('Arial','B',9);
$pdf->Cell(130,8,'TOTAL',1);
$pdf->Cell(35,8,'Rp '.number_format($total,0,',','.'),1,1,'R');

/* ================= TANDA TANGAN ================= */

$pdf->Ln(15);
$pdf->Cell(0,6,'Mengetahui,',0,1,'R');
$pdf->Ln(15);
$pdf->Cell(0,6,'Manager Keuangan',0,1,'R');

/* ================= QR CODE ================= */

$qrText = "LAPORAN GLOBAL MOTOR\n".
          "Periode: $title\n".
          "Total: Rp ".number_format($total,0,',','.')."\n".
          "Dicetak: ".date('d-m-Y H:i:s')." WIB";

$qrFile = '../assets/qrcode_laporan.png';
QRcode::png($qrText, $qrFile, QR_ECLEVEL_L, 4);

// Ukuran QR
$qrSize = 28;

// Posisi dinamis dari bawah (lebih aman)
$marginBottom = 35; 
$posY = $pdf->GetPageHeight() - $marginBottom;
$posX = $pdf->GetPageWidth() - $qrSize - 20;

// Label di atas QR
$pdf->SetFont('Arial','I',8);
$pdf->SetXY($posX, $posY - 6);
$pdf->Cell($qrSize,5,'Scan Validasi',0,0,'C');

// QR
$pdf->Image($qrFile, $posX, $posY, $qrSize);


/* ================= OUTPUT ================= */

$pdf->Output('I','Laporan_Keuangan_GLOBAL_MOTOR.pdf');
ob_end_flush();
