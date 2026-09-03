<?php
/**
 * Ensure the QR code directory exists.
 *
 * @return string The absolute path to the QR code directory.
 */
function ensure_qrcode_directory_exists()
{
  $qrcode_dir = __DIR__ . '/../content/qrcode';
  if (!is_dir($qrcode_dir)) {
    if (!mkdir($qrcode_dir, 0775, true)) {
      // Handle error: directory could not be created
      return '';
    }
  }
  return $qrcode_dir;
}

/**
 * Generate a QR code for a given user's NISN.
 *
 * @param string $nisn The user's NISN.
 * @param string $public_base The base URL for the QR code content.
 * @return string The path to the generated QR code image, or empty string on failure.
 */
function generate_user_qrcode($nisn, $public_base)
{
  $qrcode_dir = ensure_qrcode_directory_exists();
  if (empty($qrcode_dir)) {
    return '';
  }

  $filename = strip_tags($nisn) . '.jpg';
  $filepath = $qrcode_dir . '/' . $filename;

  // Include the QR code library
  if (!class_exists('QRcode')) {
    require_once __DIR__ . '/phpqrcode/qrlib.php';
  }

  // Data for the QR code
  $data = $public_base . '/' . strip_tags($nisn);

  // Generate the QR code
  try {
    QRcode::png($data, $filepath, QR_ECLEVEL_L, 10, 2);
    return '../content/qrcode/' . $filename;
  } catch (Exception $e) {
    // Handle error: QR code could not be generated
    error_log('QR code generation failed: ' . $e->getMessage());
    return '';
  }
}
