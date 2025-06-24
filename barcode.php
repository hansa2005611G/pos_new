<?php
require_once __DIR__ . '/vendor/autoload.php';
use Picqer\Barcode\BarcodeGeneratorPNG;

$barcode = $_GET['barcode'] ?? '';
if (!$barcode) exit;
header('Content-Type: image/png');
$generator = new BarcodeGeneratorPNG();
echo $generator->getBarcode($barcode, $generator::TYPE_CODE_128, 2, 50);