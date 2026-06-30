<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login'); exit;
}
require_once '../../../library/config.php';
include('../../../library/function.php');
require_once '../../login/user.php';
require_once '../../assets/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$modul_id = 54;
include __DIR__ . '/../check_role.php';

function sk_check($type) {
  global $data_role;
  if (!isset($data_role[$type]) || $data_role[$type] !== 'Y') { echo 'Akses ditolak.'; exit; }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

  case 'datatable':
    header('Content-Type: application/json');
    $data = [];
    // Cari template yang punya indeks_id untuk menentukan apakah tombol generate perlu ditampilkan
    $templateMap = [];
    $tq = $connection->query("SELECT id, indeks_id FROM surat_template WHERE indeks_id IS NOT NULL");
    if ($tq) while ($tr = $tq->fetch_assoc()) {
      $templateMap[(int)$tr['indeks_id']] = (int)$tr['id'];
    }

    $q = $connection->query("SELECT sk.*, si.indeks FROM surat_keluar sk LEFT JOIN surat_index si ON sk.indeks_id=si.id ORDER BY sk.id DESC LIMIT 50");
    if ($q) {
      $no = 1;
      while ($sk = $q->fetch_assoc()) {
        $badge = $sk['status'] === 'Terkirim' ? 'success' : ($sk['status'] === 'Draf' ? 'warning' : 'secondary');
        $can_edit = (isset($data_role['modifikasi']) && $data_role['modifikasi'] == 'Y');
        $del = (isset($data_role['hapus']) && $data_role['hapus'] == 'Y') ? '<button class="table-action table-action-danger btn-delete-keluar" data-id="' . $sk['id'] . '" title="Hapus"><i class="fas fa-trash"></i></button>' : '';
        $editBtn = $can_edit ? '<button class="table-action table-action-primary btn-edit-surat" data-id="' . $sk['id'] . '" title="Edit"><i class="fas fa-edit"></i></button>' : '';
        $kirimBtn = ($can_edit && $sk['status'] === 'Draf') ? '<button class="table-action table-action-info btn-kirim-surat" data-id="' . $sk['id'] . '" title="Tandai Terkirim"><i class="fas fa-check"></i></button>' : '';
        // Generate button (hanya jika indeks memiliki template)
        $genBtn = '';
        $indeksId = (int)$sk['indeks_id'];
        if ($can_edit && isset($templateMap[$indeksId])) {
          $tmplId = $templateMap[$indeksId];
          $genBtn = '<button class="table-action table-action-warning btn-generate-surat" data-id="' . $sk['id'] . '" data-indeks-id="' . $indeksId . '" data-template-id="' . $tmplId . '" data-no-surat="' . htmlspecialchars($sk['no_surat']) . '" title="Generate PDF"><i class="fas fa-magic"></i></button>';
        }
        // PDF link — prioritaskan drive_view_link
        $pdfLink = '';
        if (!empty($sk['drive_view_link'])) {
          $pdfLink = '<a href="' . htmlspecialchars($sk['drive_view_link']) . '" target="_blank" class="table-action table-action-success" title="Lihat PDF (Google Drive)"><i class="fas fa-file-pdf"></i></a> ';
        } elseif (!empty($sk['template_path']) && strpos($sk['template_path'], 'drive:') !== 0) {
          $pdfUrl = htmlspecialchars('/saev5/' . $sk['template_path']);
          $pdfLink = '<a href="' . $pdfUrl . '" target="_blank" class="table-action table-action-success" title="Lihat PDF"><i class="fas fa-file-pdf"></i></a> ';
        }
        $data[] = [
          '<td class="text-center">' . $no++ . '</td>',
          '<td><code>' . htmlspecialchars($sk['no_surat']) . '</code></td>',
          '<td>' . htmlspecialchars($sk['indeks'] ?? '-') . '</td>',
          '<td><strong>' . htmlspecialchars(mb_substr($sk['perihal'], 0, 50)) . '</strong></td>',
          '<td><small>' . date('d/m/Y', strtotime($sk['tgl_surat'] ?? $sk['created_at'])) . '</small></td>',
          '<td><span class="badge badge-' . $badge . '">' . htmlspecialchars($sk['status']) . '</span></td>',
          '<td class="text-center">' . $pdfLink . $genBtn . ' ' . $editBtn . ' ' . $kirimBtn . ' ' . $del . '</td>'
        ];
      }
    }
    echo json_encode(['data' => $data]);
    exit;
    break;

  case 'gen_nomor':
    $indeks = preg_replace('/[^a-zA-Z0-9._-]/', '', $_GET['indeks'] ?? '');
    if (empty($indeks)) { echo '-'; exit; }
    $next = 1;
    $q = $connection->query("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(no_surat,'/',1) AS UNSIGNED)),0)+1 AS n FROM surat_keluar");
    if ($q) $next = (int)$q->fetch_row()[0];
    echo sprintf('%04d/%s-SMKN1PGL', $next, strtoupper($indeks));
    break;

  case 'load_surat':
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['status'=>'error','message'=>'ID tidak valid']); exit; }
    $q = $connection->query("SELECT sk.*, si.indeks, si.perihal AS ix_perihal FROM surat_keluar sk LEFT JOIN surat_index si ON sk.indeks_id=si.id WHERE sk.id=$id LIMIT 1");
    if (!$q || !($sk = $q->fetch_assoc())) { echo json_encode(['status'=>'error','message'=>'Surat tidak ditemukan']); exit; }
    // Decode template_fields_json untuk dikirim sebagai array
    if (!empty($sk['template_fields_json'])) {
      $sk['template_fields'] = json_decode($sk['template_fields_json'], true);
    } else {
      $sk['template_fields'] = null;
    }
    echo json_encode(['status'=>'success','data'=>$sk]);
    exit;
    break;

  case 'update_surat':
    sk_check('modifikasi');
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['status'=>'error','message'=>'ID tidak valid']); exit; }
    $indeks_id = (int)($_POST['indeks_id'] ?? 0);
    $no_surat = $connection->real_escape_string($_POST['no_surat'] ?? '');
    $perihal = $connection->real_escape_string($_POST['perihal'] ?? '');
    $tgl_surat = !empty($_POST['tgl_surat']) ? $_POST['tgl_surat'] : date('Y-m-d');
    $lampiran = '';
    $status = $connection->real_escape_string($_POST['status'] ?? 'Draf');
    $u = $connection->prepare("UPDATE surat_keluar SET indeks_id=?, no_surat=?, tgl_surat=?, perihal=?, lampiran=?, status=? WHERE id=?");
    $u->bind_param('isssssi', $indeks_id, $no_surat, $tgl_surat, $perihal, $lampiran, $status, $id);
    if (!$u->execute()) {
      echo json_encode(['status'=>'error','message'=>'Gagal: '.$connection->error]);
      $u->close(); exit;
    }
    $u->close();
    echo json_encode(['status'=>'success','message'=>'Surat diperbarui','id'=>$id]);
    exit;
    break;



  case 'kirim':
    sk_check('modifikasi');
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo 'ID tidak valid.'; exit; }
    $connection->query("UPDATE surat_keluar SET status='Terkirim' WHERE id=$id");
    echo $connection->affected_rows > 0 ? 'success' : 'Gagal.';
    exit;
    break;

  case 'buat':
    sk_check('modifikasi');
    $indeks_id = (int)($_POST['indeks_id'] ?? 0);
    $no_surat = $connection->real_escape_string($_POST['no_surat'] ?? '');
    $perihal = $connection->real_escape_string($_POST['perihal'] ?? '');
    $tgl_surat = !empty($_POST['tgl_surat']) ? $_POST['tgl_surat'] : date('Y-m-d');
    $lampiran = '';
    if ($indeks_id <= 0 || empty($no_surat)) {
      echo json_encode(['status' => 'error', 'message' => 'Lengkapi data.']); exit;
    }
    $s = $connection->prepare("INSERT INTO surat_keluar (indeks_id, no_surat, tgl_surat, perihal, lampiran, created_by, status) VALUES (?,?,?,?,?,'1','Draf')");
    $s->bind_param('issss', $indeks_id, $no_surat, $tgl_surat, $perihal, $lampiran);
    if (!$s->execute()) {
      echo json_encode(['status' => 'error', 'message' => 'Gagal: ' . $connection->error]);
      $s->close(); exit;
    }
    $surat_id = $connection->insert_id;
    $s->close();
    echo json_encode(['status' => 'success', 'id' => $surat_id]);
    break;



  case 'delete':
    sk_check('hapus');
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo 'ID tidak valid.'; exit; }
    
    // Ambil dulu template_path & drive_file_id sebelum hapus record
    $dq = $connection->query("SELECT template_path, drive_file_id FROM surat_keluar WHERE id=$id LIMIT 1");
    if ($dq && $dr = $dq->fetch_assoc()) {
      // Hapus dari Google Drive jika ada drive_file_id
      $driveFileId = $dr['drive_file_id'] ?? '';
      if (!empty($driveFileId)) {
        try {
          require_once __DIR__ . '/../../../library/google_drive_helper.php';
          $gdrive = new GoogleDriveHelper($creds_path);
          $gdrive->deleteFile($driveFileId);
        } catch (Exception $e) {
          // Abaikan error hapus Drive
        }
      }
      
      // Hapus file PDF lokal jika masih ada (legacy)
      $oldPath = $dr['template_path'] ?? '';
      if (!empty($oldPath) && strpos($oldPath, 'drive:') !== 0) {
        $fullPath = __DIR__ . '/../../../' . $oldPath;
        if (file_exists($fullPath)) {
          @unlink($fullPath);
        }
        $baseDir = dirname($fullPath);
        $baseName = pathinfo($fullPath, PATHINFO_FILENAME);
        if ($baseDir && is_dir($baseDir)) {
          $pattern = $baseDir . '/' . preg_replace('/_\d{8}_\d{6}$/', '', $baseName) . '_*.pdf';
          foreach (glob($pattern) as $oldFile) {
            if (file_exists($oldFile)) @unlink($oldFile);
          }
        }
      }
    }
    
    echo $connection->query("DELETE FROM surat_keluar WHERE id=$id") ? 'success' : 'Gagal.';
    break;

  case 'export_excel':
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1','No')->setCellValue('B1','No. Surat')->setCellValue('C1','Indeks')->setCellValue('D1','Perihal')->setCellValue('E1','Tanggal')->setCellValue('F1','Status');
    $no = 2;
    $q = $connection->query("SELECT sk.*, si.indeks FROM surat_keluar sk LEFT JOIN surat_index si ON sk.indeks_id=si.id ORDER BY sk.id DESC");
    if ($q) while ($r = $q->fetch_assoc()) {
      $sheet->setCellValue('A'.$no, $no-1)->setCellValue('B'.$no, $r['no_surat'])->setCellValue('C'.$no, $r['indeks']??'')->setCellValue('D'.$no, $r['perihal'])->setCellValue('E'.$no, $r['tgl_surat']??$r['created_at'])->setCellValue('F'.$no, $r['status']);
      $no++;
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="surat-keluar.xlsx"');
    (new Xlsx($spreadsheet))->save('php://output');
    exit;

  // ====== LOAD TEMPLATE TAGS — kirim fields dari variabel_tag ======
  case 'load_template_tags':
    header('Content-Type: application/json');
    $template_id = (int)($_GET['template_id'] ?? 0);
    if ($template_id <= 0) { echo json_encode(['status'=>'error','message'=>'Template tidak valid.']); exit; }

    $q = $connection->query("SELECT variabel_tag, indeks_surat, drive_file_id, link_dokumen FROM surat_template WHERE id=$template_id LIMIT 1");
    if (!$q || !($r = $q->fetch_assoc())) {
      echo json_encode(['status'=>'error','message'=>'Template tidak ditemukan.']); exit;
    }

    $variabel_tag = trim($r['variabel_tag'] ?? '');
    if (empty($variabel_tag)) {
      echo json_encode(['status'=>'error','message'=>'Template ini belum memiliki variabel tag. Silakan scan tags terlebih dahulu di menu Template Surat.']); exit;
    }

    // Parse {{tag}} from variabel_tag string — urut sesuai kemunculan di template
    preg_match_all('/\{\{(\w+)\}\}/', $variabel_tag, $matches);
    $seen = [];
    $tagNames = [];
    foreach ($matches[1] as $name) {
      if (!isset($seen[$name])) {
        $seen[$name] = true;
        $tagNames[] = $name;
      }
    }

    $fields = [];
    foreach ($tagNames as $name) {
      $label = ucwords(str_replace('_', ' ', $name));
      $type = 'text';
      if (preg_match('/(tanggal|tgl|date)/i', $name)) $type = 'date';
      if (preg_match('/(alamat|address|isi|keterangan|catatan)/i', $name)) $type = 'textarea';
      $fields[] = ['name' => $name, 'label' => $label, 'type' => $type];
    }

    echo json_encode(['status'=>'success','fields'=>$fields,'indeks_surat'=>$r['indeks_surat'],'drive_file_id'=>$r['drive_file_id']]);
    exit;
    break;

  // ====== GENERATE SURAT — copy template Google Docs, replace text via Docs API ======
  case 'generate_pdf':
    header('Content-Type: application/json');
    sk_check('modifikasi');

    // Bersihkan output buffer untuk mencegah warning/deprecated merusak JSON
    while (ob_get_level()) ob_end_clean();
    ob_start();

    // Set time limit tidak terbatas
    set_time_limit(0);

    $template_id = (int)($_POST['template_id'] ?? 0);
    $field_values = $_POST['field_values'] ?? []; // array asosiatif
    $no_surat = $connection->real_escape_string($_POST['no_surat'] ?? '');
    $surat_id_editing = (int)($_POST['surat_id'] ?? 0);

    if ($template_id <= 0) { echo json_encode(['status'=>'error','message'=>'Template tidak valid.']); exit; }

    // Ambil data template
    $q = $connection->query("SELECT st.*, si.indeks, si.perihal FROM surat_template st JOIN surat_index si ON st.indeks_id=si.id WHERE st.id=$template_id LIMIT 1");
    if (!$q || !($tmpl = $q->fetch_assoc())) {
      echo json_encode(['status'=>'error','message'=>'Template tidak ditemukan.']); exit;
    }

    $drive_file_id = $tmpl['drive_file_id'];
    if (empty($drive_file_id)) {
      echo json_encode(['status'=>'error','message'=>'Drive File ID tidak ditemukan pada template.']); exit;
    }

    // Ambil setting kredensial — dukung OAuth 2.0 dan Service Account
    $qs = $connection->query("SELECT google_credentials, drive_folder_id, oauth_client_id, oauth_client_secret, oauth_refresh_token FROM surat_setting WHERE id=1 LIMIT 1");
    $set = $qs ? $qs->fetch_assoc() : null;
    if (!$set) {
      echo json_encode(['status'=>'error','message'=>'Setting tidak ditemukan.']); exit;
    }

    try {
      // Load Google Drive Helper
      require_once __DIR__ . '/../../../library/google_drive_helper.php';

      $useOAuth = !empty($set['oauth_client_id']) && !empty($set['oauth_client_secret']) && !empty($set['oauth_refresh_token']);

      if ($useOAuth) {
        $creds_path = __DIR__ . '/../../../content/google-oauth-helper.json';
        if (!file_exists($creds_path)) {
          file_put_contents($creds_path, json_encode([
            'type' => 'service_account',
            'client_email' => 'oauth@dummy.com',
            'private_key' => 'dummy',
          ]));
        }
        $gdrive = new GoogleDriveHelper($creds_path);
        $gdrive->setOAuthCredentials($set['oauth_client_id'], $set['oauth_client_secret'], $set['oauth_refresh_token']);
      } else {
        if (empty($set['google_credentials'])) {
          echo json_encode(['status'=>'error','message'=>'Kredensial Google API belum dikonfigurasi.']); exit;
        }
        $creds_path = __DIR__ . '/../../../content/' . $set['google_credentials'];
        if (!file_exists($creds_path)) {
          echo json_encode(['status'=>'error','message'=>'File kredensial tidak ditemukan.']); exit;
        }
        $gdrive = new GoogleDriveHelper($creds_path);
      }

      // Decode JSON string if sent as string from JS
      if (is_string($field_values)) {
        $field_values = json_decode($field_values, true) ?? [];
      }

      // =================================================================
      // STRATEGI BARU: Copy template → Replace text via Docs API → Selesai
      // Tidak perlu mPDF, format template Google Docs terjaga 100%.
      // =================================================================

      // Folder tujuan
      $pdfFolderId = $set['drive_folder_id'] ?? null;
      if (empty($pdfFolderId)) {
        echo json_encode(['status'=>'error','message'=>'Folder ID Google Drive belum dikonfigurasi. Silakan isi Folder ID di menu Pengaturan Surat.']); exit;
      }

      // 1. Copy template — otomatis konversi ke Google Docs jika file Office
      $tmplTitle = $tmpl['indeks_surat'] ?? 'Surat';
      $noSuratSafe = preg_replace('/[^a-zA-Z0-9.-]/', '_', substr($no_surat, 0, 40));
      $timestamp = date('Ymd_His');
      $docTitle = $tmplTitle . '_' . $noSuratSafe . '_' . $timestamp;

      $newFileId = $gdrive->copyAsGoogleDoc($drive_file_id, $docTitle, $pdfFolderId);
      if (!$newFileId) {
        echo json_encode(['status'=>'error','message'=>'Gagal copy/konversi template. ' . $gdrive->getLastError()]); exit;
      }

      // 2. Siapkan replacement — ganti {{tags}} dengan nilai dari form
      $replacements = [];
      foreach ($field_values as $key => $val) {
        $replacements['{{' . $key . '}}'] = $val ?? '';
      }
      // Tambahkan nomor surat jika ada
      if (!empty($no_surat)) {
        $replacements['{{no_surat}}'] = $no_surat;
      }

      // 3. Lakukan batchReplaceText via Docs API
      if (!empty($replacements)) {
        $replaceResult = $gdrive->batchReplaceText($newFileId, $replacements);
        if (!$replaceResult) {
          // Hapus file yang sudah tercopy jika gagal replace
          $gdrive->deleteFile($newFileId);
          echo json_encode(['status'=>'error','message'=>'Gagal replace text di dokumen. ' . $gdrive->getLastError()]); exit;
        }
      }

      // 4. Set public (Anyone with the link can view)
      $gdrive->makePublic($newFileId);

      // 5. Dapatkan webViewLink untuk preview
      $webViewLink = $gdrive->getWebViewLink($newFileId);
      if (!$webViewLink) {
        $webViewLink = 'https://docs.google.com/document/d/' . $newFileId . '/edit';
      }

      // 6. Simpan info surat di surat_keluar
      if ($surat_id_editing > 0) {
        $fields_json = $connection->real_escape_string(json_encode($field_values));
        $efid = $connection->real_escape_string($newFileId);
        $elink = $connection->real_escape_string($webViewLink);
        $connection->query("UPDATE surat_keluar SET template_path='drive:$efid', template_fields_json='$fields_json', drive_file_id='$efid', drive_view_link='$elink', updated_at=NOW() WHERE id=$surat_id_editing");
      }

      echo json_encode([
        'status' => 'success',
        'pdf_url' => $webViewLink,
        'pdf_file_id' => $newFileId,
        'filename' => $docTitle,
      ]);
    } catch (Exception $e) {
      echo json_encode(['status'=>'error','message'=>'Exception: '.$e->getMessage()]);
    }
    exit;
    break;

  // ====== SAVE TO SPREADSHEET ======
  case 'save_to_spreadsheet':
    header('Content-Type: application/json');
    sk_check('modifikasi');

    $surat_id = (int)($_POST['surat_id'] ?? 0);
    $field_values = $_POST['field_values'] ?? [];

    // Decode JSON string if sent as string from JS
    if (is_string($field_values)) {
      $field_values = json_decode($field_values, true) ?? [];
    }

    if ($surat_id <= 0) {
      echo json_encode(['status'=>'error','message'=>'ID surat tidak valid.']); exit;
    }

    // Ambil data surat
    $q = $connection->query("SELECT sk.*, si.indeks FROM surat_keluar sk LEFT JOIN surat_index si ON sk.indeks_id=si.id WHERE sk.id=$surat_id LIMIT 1");
    if (!$q || !($surat = $q->fetch_assoc())) {
      echo json_encode(['status'=>'error','message'=>'Surat tidak ditemukan.']); exit;
    }

    // Ambil setting spreadsheet
    $qs = $connection->query("SELECT spreadsheet_id, spreadsheet_range, google_credentials FROM surat_setting WHERE id=1 LIMIT 1");
    $set = $qs ? $qs->fetch_assoc() : null;
    if (!$set || empty($set['spreadsheet_id'])) {
      echo json_encode(['status'=>'error','message'=>'Spreadsheet ID belum dikonfigurasi. Silakan atur di Pengaturan Surat.']); exit;
    }
    if (empty($set['google_credentials'])) {
      echo json_encode(['status'=>'error','message'=>'Kredensial Google belum dikonfigurasi.']); exit;
    }

    $creds_path = __DIR__ . '/../../../content/' . $set['google_credentials'];
    if (!file_exists($creds_path)) {
      echo json_encode(['status'=>'error','message'=>'File kredensial tidak ditemukan.']); exit;
    }

    try {
      require_once __DIR__ . '/../../../library/spreadsheet_helper.php';

      $sheets = new SpreadsheetHelper($creds_path);
      $spreadsheetId = trim($set['spreadsheet_id']);
      $range = trim($set['spreadsheet_range'] ?? 'Sheet1');

      // Jika field_values masih kosong, coba dari database
      if (empty($field_values) && !empty($surat['template_fields_json'])) {
        $field_values = json_decode($surat['template_fields_json'], true) ?? [];
      }

      // Kolom tetap: tanggal, no_surat, indeks, perihal, tujuan
      $row = [
        date('d/m/Y', strtotime($surat['tgl_surat'] ?? $surat['created_at'])),
        $surat['no_surat'],
        $surat['indeks'] ?? '-',
        $surat['perihal'],
        $surat['tujuan'] ?? '-',
      ];

      // Tambahkan nilai dari field_values sesuai urutan
      $fieldValuesParsed = $field_values; // simpan reference
      if (!empty($field_values)) {
        foreach ($field_values as $val) {
          $row[] = is_string($val) ? $val : (is_array($val) ? json_encode($val) : '-');
        }
      }

      // Coba ambil header untuk mapping kolom
      $headerRow = $sheets->getHeaderRow($spreadsheetId, $range);
      if ($headerRow === false) {
        // Jika tidak ada header, kirim dengan header otomatis
        $headerRow = ['Tanggal', 'No. Surat', 'Indeks', 'Perihal', 'Tujuan'];
        if (!empty($fieldValuesParsed) && is_array($fieldValuesParsed)) {
          foreach ($fieldValuesParsed as $key => $val) {
            $headerRow[] = is_string($key) ? ucwords(str_replace('_', ' ', $key)) : 'Field ' . ($key + 1);
          }
        }
        // Coba tulis header dulu
        $sheets->appendRows($spreadsheetId, $range, [$headerRow]);
      }

      // Append data
      $result = $sheets->appendRows($spreadsheetId, $range, [$row]);

      if ($result === false) {
        echo json_encode(['status'=>'error','message'=>'Gagal menyimpan ke spreadsheet: '.$sheets->getLastError()]); exit;
      }

      // Update status surat jadi Terkirim dan catat spreadsheet_ref
      $updatedRange = $result['updates']['updatedRange'] ?? '';
      $connection->query("UPDATE surat_keluar SET status='Terkirim', updated_at=NOW() WHERE id=$surat_id");

      echo json_encode([
        'status' => 'success',
        'message' => 'Data berhasil disimpan ke spreadsheet.',
        'spreadsheet_range' => $updatedRange,
        'spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/' . $spreadsheetId,
      ]);
    } catch (Exception $e) {
      echo json_encode(['status'=>'error','message'=>'Exception: '.$e->getMessage()]);
    }
    exit;
    break;

  default:
    echo 'Aksi tidak dikenali.';
    break;
}
