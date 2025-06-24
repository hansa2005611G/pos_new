<?php
session_start();
if (!isset($_SESSION['user_id'], $_SESSION['role']) || !in_array($_SESSION['role'], ['admin','manager'])) {
    echo json_encode(['success'=>false,'error'=>'Permission denied']); exit;
}
require_once 'C:\inetpub\wwwroot\pos_new\db.php';
require_once 'C:\inetpub\wwwroot\pos_new\includes\log_activity.php';

// Composer autoload for Excel
require_once __DIR__.'/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$type = $_POST['type'] ?? '';
if (!isset($_FILES['file']) || !$type) {
    echo json_encode(['success'=>false,'error'=>'No file or type specified']); exit;
}

$tmp = $_FILES['file']['tmp_name'];
$ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

$data = [];
$header = [];

// CSV import
if ($ext === 'csv') {
    $rows = array_map('str_getcsv', file($tmp));
    $header = array_map('strtolower', $rows[0]);
    $data = array_slice($rows,1);
}
// Excel import
elseif (in_array($ext, ['xls','xlsx'])) {
    $spreadsheet = IOFactory::load($tmp);
    $sheet = $spreadsheet->getActiveSheet()->toArray(NULL,true,true,true);
    $header = array_map('strtolower', array_values(array_shift($sheet)));
    $data = $sheet;
} else {
    echo json_encode(['success'=>false,'error'=>'Unsupported file type']); exit;
}

// Products import
if ($type === 'products') {
    $count = 0;
    foreach($data as $row) {
        if(is_array($row)) $row = array_values($row);
        [$name, $category, $stock, $unit_cost, $reorder_level] = array_slice($row, 0, 5);
        if (!$name) continue;
        // Insert or update by name
        $stmt = $pdo->prepare("SELECT id FROM products WHERE name=?");
        $stmt->execute([$name]);
        $exists = $stmt->fetchColumn();
        if ($exists) {
            $pdo->prepare("UPDATE products SET category_id=?, stock=?, unit_cost=?, reorder_level=? WHERE id=?")
                ->execute([$category, $stock, $unit_cost, $reorder_level, $exists]);
        } else {
            $pdo->prepare("INSERT INTO products (name, category_id, stock, unit_cost, reorder_level) VALUES (?, ?, ?, ?, ?)")
                ->execute([$name, $category, $stock, $unit_cost, $reorder_level]);
        }
        $count++;
    }
    echo json_encode(['success'=>true, 'message'=>"Imported $count products."]);
    exit;
}

// Stock update import
if ($type === 'stock') {
    $count = 0;
    foreach($data as $row) {
        if(is_array($row)) $row = array_values($row);
        [$name, $stock] = array_slice($row, 0, 2);
        if (!$name) continue;
        $pdo->prepare("UPDATE products SET stock=? WHERE name=?")->execute([$stock, $name]);
        $count++;
    }
    echo json_encode(['success'=>true, 'message'=>"Updated stock for $count products."]);
    exit;
}

echo json_encode(['success'=>false,'error'=>'Unknown import type']);