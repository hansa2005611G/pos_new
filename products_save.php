<?php
session_start();
require_once 'C:\inetpub\wwwroot\pos_new\db.php';

$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$sku = trim($_POST['sku'] ?? '');
$category = intval($_POST['category'] ?? 0);
$unit = trim($_POST['unit'] ?? '');
$stock = intval($_POST['stock'] ?? 0);
$reorder_level = intval($_POST['reorder_level'] ?? 0);
$desc = trim($_POST['description'] ?? '');

if(!$name || !$category) {
    echo json_encode(['success'=>false,'error'=>'Product name and category required']); exit;
}

$image_name = '';
if(isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $image_name = uniqid('pimg_').'.'.$ext;
    move_uploaded_file($_FILES['image']['tmp_name'], "../../uploads/".$image_name);
}

try {
    if($id) {
        // Edit
        $q = "UPDATE products SET name=?, sku=?, category_id=?, unit=?, stock=?, reorder_level=?, description=?";
        $params = [$name, $sku, $category, $unit, $stock, $reorder_level, $desc];
        if($image_name) {
            $q .= ", image=?";
            $params[] = $image_name;
        }
        $q .= " WHERE id=?";
        $params[] = $id;
        $stmt = $pdo->prepare($q);
        $stmt->execute($params);
        echo json_encode(['success'=>true, 'message'=>'Product updated']);
    } else {
        // Add
        $stmt = $pdo->prepare("INSERT INTO products (name, sku, category_id, unit, stock, reorder_level, description, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $sku, $category, $unit, $stock, $reorder_level, $desc, $image_name]);
        echo json_encode(['success'=>true, 'message'=>'Product added']);
    }
} catch(Exception $e) {
    echo json_encode(['success'=>false, 'error'=>'DB error: '.$e->getMessage()]);
}