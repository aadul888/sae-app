<?php
/**
 * Generator QR Code untuk Buku Tamu.
 * Dipakai pada halaman sukses registrasi: tamu memindai QR ini saat keluar
 * untuk melakukan checkout + mengisi survey.
 *
 * Penggunaan: qr.php?data=<url-encoded text>
 */

$data = isset($_GET['data']) ? (string)$_GET['data'] : '';
$data = trim($data);

// Batasi panjang & karakter agar aman
if ($data === '' || strlen($data) > 600) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Parameter data tidak valid';
    exit;
}

require_once __DIR__ . '/../../library/phpqrcode/phpqrcode.php';

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');

// $filename = null -> stream langsung ke output; level M; ukuran modul 5; margin 2
QRcode::png($data, null, QR_ECLEVEL_M, 5, 2);
exit;
