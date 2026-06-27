<?php
// Debug: langsung test import seperti yang dilakukan AJAX
$_COOKIE['ADMIN_KEY'] = 'dGVzdA=='; // dummy
$_GET['action'] = 'import_excel';
$_POST['action'] = 'import_excel';
$_FILES['file_excel'] = [
  'name' => 'test.xlsx',
  'type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  'tmp_name' => __DIR__ . '/../../../content/templates/test_import.xlsx',
  'error' => 0,
  'size' => 5000,
];

echo "=== Debug Import ===\n";
echo "1. File EXISTS: " . (file_exists($_FILES['file_excel']['tmp_name']) ? 'YES' : 'NO') . "\n";
echo "2. Extension: " . strtolower(pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION)) . "\n";

require_once '../../../library/config.php';
require_once '../../assets/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

try {
  $spreadsheet = IOFactory::load($_FILES['file_excel']['tmp_name']);
  $rows = $spreadsheet->getActiveSheet()->toArray();
  echo "3. Rows loaded: " . count($rows) . "\n";
  echo "4. Header: " . print_r($rows[0], true) . "\n";
} catch (Exception $e) {
  echo "3. ERROR load: " . $e->getMessage() . "\n";
}

// Test query
$r = $connection->query("SELECT COUNT(*) as c FROM surat_index");
if ($r) echo "5. Rows in DB: " . $r->fetch_assoc()['c'] . "\n";

echo "=== DONE ===\n";
