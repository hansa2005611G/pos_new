<?php
session_start();
if (!isset($_SESSION['user_id'], $_SESSION['role']) || !in_array($_SESSION['role'], ['admin','manager'])) {
    http_response_code(403); exit;
}

require_once 'C:\inetpub\wwwroot\pos_new\db.php';
require_once 'C:\inetpub\wwwroot\pos_new\includes\log_activity.php';

// Composer autoload for Excel/PDF
require_once __DIR__.'/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use FPDF;

$type = $_GET['type'] ?? 'products';
$format = $_GET['format'] ?? 'csv';

// Prepare data
if ($type === 'products') {
    $data = $pdo->query("SELECT id, name, category_id, stock, unit_cost, reorder_level FROM products")->fetchAll(PDO::FETCH_ASSOC);
    $columns = ['ID','Name','Category','Stock','Unit Cost','Reorder Level'];
} elseif ($type === 'suppliers') {
    $data = $pdo->query("SELECT id, name, contact, email, address, active FROM suppliers")->fetchAll(PDO::FETCH_ASSOC);
    $columns = ['ID','Name','Contact','Email','Address','Active'];
} else {
    http_response_code(400); exit;
}

// Export as CSV
if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$type.'_export.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $columns);
    foreach ($data as $row) fputcsv($out, $row);
    fclose($out);
    exit;
}

// Export as XLSX (Excel)
if ($format === 'xlsx') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray($columns, NULL, 'A1');
    $sheet->fromArray($data, NULL, 'A2');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$type.'_export.xlsx"');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Export as PDF
if ($format === 'pdf') {
    // Make sure FPDF is loaded
    if (!class_exists('FPDF')) {
        require_once __DIR__.'/../vendor/setasign/fpdf/fpdf.php';
    }
    $pdf = new FPDF('L','mm','A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',12);
    foreach($columns as $col) $pdf->Cell(40,10,$col,1);
    $pdf->Ln();
    $pdf->SetFont('Arial','',10);
    foreach($data as $row){
        foreach($row as $cell) $pdf->Cell(40,10,mb_convert_encoding($cell, 'ISO-8859-1','UTF-8'),1);
        $pdf->Ln();
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="'.$type.'_export.pdf"');
    $pdf->Output('I');
    exit;
}
http_response_code(400); exit;