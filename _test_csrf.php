<?php
$_SERVER['HTTP_HOST'] = 'sae-app.test';
$_SERVER['REQUEST_URI'] = '/admin/lisensi_pembaruan';
require 'library/config.php';
echo $_SESSION['csrf_token'] ?? 'none';