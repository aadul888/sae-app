<?php
session_start();

// Require login (ADMIN_KEY or KEY) for all actions; token-only flows are not allowed here.
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
}

require_once '../../../library/config.php';
require_once '../../../library/function.php';
require_once '../../login/user.php';

switch (@$_GET['action']) {

  /* ----------- SETUJUI ----------- */
  case 'setujui':
    $id = anti_injection($_POST['id']);

    $level_id = $current_user['level_id'] ?? (isset($_COOKIE['level_id']) ? $_COOKIE['level_id'] : '');
    $admin_id = 0;
    if (isset($_COOKIE['ADMIN_KEY'])) $admin_id = (int) epm_decode($_COOKIE['ADMIN_KEY']);
    $admin_tugas = '';
    if ($admin_id > 0) {
      $stmtA = $connection->prepare("SELECT tugas_tambahan FROM admin WHERE admin_id = ? LIMIT 1");
      if ($stmtA) {
        $stmtA->bind_param('i', $admin_id);
        $stmtA->execute();
        $resA = $stmtA->get_result();
        if ($resA && $resA->num_rows > 0) {
          $rowA = $resA->fetch_assoc();
          $admin_tugas = $rowA['tugas_tambahan'] ?? '';
        }
        $stmtA->close();
      }
    }
    $tugas_arr = [];
    if ($admin_tugas !== '') $tugas_arr = array_map('trim', explode(',', $admin_tugas));
    $level_int = intval($level_id);
    // Wali/Guru: require BOTH level 3 AND tugas_tambahan 4
    $is_wali = ($level_int === 3) && (in_array('4', $tugas_arr) || in_array(4, $tugas_arr));
    // Admin/Petugas: level 2 with tugas_tambahan 6
    $is_admin_petugas = ($level_int === 2) && (in_array('6', $tugas_arr) || in_array(6, $tugas_arr));

    if ($is_wali) {
      $chk = $connection->prepare("SELECT id FROM e_izin WHERE id = ? AND (status_izin_wali = 'Menunggu' OR status_izin_wali = '' OR status_izin_wali IS NULL) LIMIT 1");
      if ($chk) {
        $chk->bind_param('i', $id);
        $chk->execute();
        $res_chk = $chk->get_result();
        if ($res_chk && $res_chk->num_rows > 0) {
          $up = $connection->prepare("UPDATE e_izin SET status_izin_wali = ?, alasan_penolakan_wali = '' WHERE id = ?");
          if ($up) {
            $status = 'Disetujui';
            $up->bind_param('si', $status, $id);
            $ok = $up->execute();
            $up->close();
            echo $ok ? "Izin berhasil disetujui oleh wali." : "Gagal memperbarui data.";
          } else {
            echo "Gagal mempersiapkan query update.";
          }
        } else {
          echo "Data tidak ditemukan atau sudah diproses oleh wali.";
        }
        $chk->close();
      } else {
        echo "Gagal mempersiapkan pengecekan data.";
      }
    } else {
      // Only allow admin/petugas (level 2 + tugas 6) to perform admin approvals
      if (!$is_admin_petugas) {
        echo "Anda tidak memiliki hak untuk melakukan approval admin.";
        break;
      }
      $q = $connection->prepare("SELECT id FROM e_izin WHERE id = ? AND status_izin = 'Menunggu' LIMIT 1");
      if ($q) {
        $q->bind_param('i', $id);
        $q->execute();
        $res_q = $q->get_result();
        if ($res_q && $res_q->num_rows > 0) {
          $up = $connection->prepare("UPDATE e_izin SET status_izin = ?, alasan_penolakan = '' WHERE id = ?");
          if ($up) {
            $status = 'Disetujui';
            $up->bind_param('si', $status, $id);
            $ok = $up->execute();
            $up->close();
            echo $ok ? "Izin berhasil disetujui." : "Gagal memperbarui data.";
          } else {
            echo "Gagal mempersiapkan query update.";
          }
        } else {
          echo "Data tidak ditemukan atau sudah diproses.";
        }
        $q->close();
      } else {
        echo "Gagal mempersiapkan pengecekan data.";
      }
    }
    break;

  /* ----------- TOLAK ----------- */
  case 'tolak':
    $id = anti_injection($_POST['id']);
    $alasan = anti_injection($_POST['alasan']);

    $level_id = $current_user['level_id'] ?? (isset($_COOKIE['level_id']) ? $_COOKIE['level_id'] : '');
    $admin_id = 0;
    if (isset($_COOKIE['ADMIN_KEY'])) $admin_id = (int) epm_decode($_COOKIE['ADMIN_KEY']);
    $admin_tugas = '';
    if ($admin_id > 0) {
      $stmtA = $connection->prepare("SELECT tugas_tambahan FROM admin WHERE admin_id = ? LIMIT 1");
      if ($stmtA) {
        $stmtA->bind_param('i', $admin_id);
        $stmtA->execute();
        $resA = $stmtA->get_result();
        if ($resA && $resA->num_rows > 0) {
          $rowA = $resA->fetch_assoc();
          $admin_tugas = $rowA['tugas_tambahan'] ?? '';
        }
        $stmtA->close();
      }
    }
    $tugas_arr = [];
    if ($admin_tugas !== '') $tugas_arr = array_map('trim', explode(',', $admin_tugas));
    $level_int = intval($level_id);
    // Wali/Guru: require BOTH level 3 AND tugas_tambahan 4
    $is_wali = ($level_int === 3) && (in_array('4', $tugas_arr) || in_array(4, $tugas_arr));
    // Admin/Petugas: level 2 with tugas_tambahan 6
    $is_admin_petugas = ($level_int === 2) && (in_array('6', $tugas_arr) || in_array(6, $tugas_arr));

    if ($is_wali) {
      $check = $connection->prepare("SELECT id FROM e_izin WHERE id = ? AND (status_izin_wali = 'Menunggu' OR status_izin_wali = '' OR status_izin_wali IS NULL) LIMIT 1");
      if ($check) {
        $check->bind_param('i', $id);
        $check->execute();
        $res_check = $check->get_result();
        if ($res_check && $res_check->num_rows > 0) {
          $up = $connection->prepare("UPDATE e_izin SET status_izin_wali = ?, alasan_penolakan_wali = ? WHERE id = ?");
          if ($up) {
            $status = 'Ditolak';
            $up->bind_param('ssi', $status, $alasan, $id);
            $ok = $up->execute();
            $up->close();
            echo $ok ? "Pengajuan izin ditolak oleh wali." : "Gagal memperbarui data.";
          } else {
            echo "Gagal mempersiapkan query update.";
          }
        } else {
          echo "Data tidak valid atau sudah diproses oleh wali.";
        }
        $check->close();
      } else {
        echo "Gagal mempersiapkan pengecekan data.";
      }
    } else {
      // Only allow admin/petugas (level 2 + tugas 6) to perform admin rejections
      if (!$is_admin_petugas) {
        echo "Anda tidak memiliki hak untuk melakukan penolakan admin.";
        break;
      }
      $check = $connection->prepare("SELECT id FROM e_izin WHERE id = ? AND status_izin = 'Menunggu' LIMIT 1");
      if ($check) {
        $check->bind_param('i', $id);
        $check->execute();
        $res_check = $check->get_result();
        if ($res_check && $res_check->num_rows > 0) {
          $up = $connection->prepare("UPDATE e_izin SET status_izin = ?, alasan_penolakan = ? WHERE id = ?");
          if ($up) {
            $status = 'Ditolak';
            $up->bind_param('ssi', $status, $alasan, $id);
            $ok = $up->execute();
            $up->close();
            echo $ok ? "Pengajuan izin ditolak." : "Gagal memperbarui data.";
          } else {
            echo "Gagal mempersiapkan query update.";
          }
        } else {
          echo "Data tidak valid atau sudah diproses.";
        }
        $check->close();
      } else {
        echo "Gagal mempersiapkan pengecekan data.";
      }
    }
    break;

  /* ----------- DETAIL IZIN ----------- */
  case 'detail':
    $id = anti_injection($_POST['id']);
    $query = $connection->query("SELECT e_izin.*, user.nama_lengkap, user.nisn, user.telp, kelas.nama_kelas FROM e_izin
        LEFT JOIN user ON e_izin.user_id = user.user_id
        LEFT JOIN kelas ON user.kelas = kelas.kelas_id
        WHERE e_izin.id='$id'");
    if ($query->num_rows > 0) {
      $data = $query->fetch_assoc();

      // prepare formatted times and duration
      $fmt = function ($dt) {
        if (empty($dt) || $dt === '0000-00-00 00:00:00') return '-';
        $t = strtotime($dt);
        if ($t === false) return '-';
        return date('d M Y H:i', $t);
      };
      $time_keluar = $fmt($data['time_keluar'] ?? '');
      $time_kembali = $fmt($data['time_kembali'] ?? '');
      $time_pulang = $fmt($data['time_pulang'] ?? '');
      $duration = '-';
      if (!empty($data['time_keluar']) && !empty($data['time_kembali']) && $data['time_keluar'] !== '0000-00-00 00:00:00' && $data['time_kembali'] !== '0000-00-00 00:00:00') {
        $t1 = strtotime($data['time_keluar']);
        $t2 = strtotime($data['time_kembali']);
        if ($t1 !== false && $t2 !== false && $t2 > $t1) {
          $secs = $t2 - $t1;
          $hours = floor($secs / 3600);
          $mins = floor(($secs % 3600) / 60);
          $duration = $hours > 0 ? ($hours . ' jam ' . $mins . ' mnt') : ($mins . ' mnt');
        }
      }

      // prepare avatar URL (fall back to default)
      $avatar_file = !empty($data['nisn']) ? preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $data['nisn']) : (!empty($data['user_id']) ? $data['user_id'] : 'default');
      $avatar_fs = __DIR__ . '/../../../content/avatar/' . $avatar_file . '.png';
      $avatar_def_fs = __DIR__ . '/../../../content/avatar/avatar.jpg';
      // If default avatar does not exist, try to generate a simple one (GD)
      if (!file_exists($avatar_def_fs) && function_exists('imagecreatetruecolor')) {
        try {
          $dir = dirname($avatar_def_fs);
          if (!is_dir($dir)) @mkdir($dir, 0755, true);
          // create default image at 3:4 ratio (150x200)
          $w = 150;
          $h = 200;
          $img = imagecreatetruecolor($w, $h);
          $bg = imagecolorallocate($img, 240, 240, 240);
          $fg = imagecolorallocate($img, 108, 117, 125);
          imagefilledrectangle($img, 0, 0, $w, $h, $bg);
          // initials from name
          $initials = '';
          if (!empty($data['nama_lengkap'])) {
            $parts = preg_split('/\s+/', trim($data['nama_lengkap']));
            if (count($parts) >= 2) {
              $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
            } else {
              $initials = strtoupper(substr($parts[0], 0, 2));
            }
          } else {
            $initials = 'AV';
          }
          // calculate text position roughly centered
          $fontSize = 5; // built-in font size
          $textW = imagefontwidth($fontSize) * strlen($initials);
          $textH = imagefontheight($fontSize);
          $x = intval(($w - $textW) / 2);
          $y = intval(($h - $textH) / 2);
          imagestring($img, $fontSize, $x, $y, $initials, $fg);
          imagejpeg($img, $avatar_def_fs, 85);
          imagedestroy($img);
        } catch (Exception $e) {
          // ignore generation errors
        }
      }
      // Compute absolute URLs based on application root so browser resolves correctly.
      $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
      $parts = explode('/', trim($script_name, '/'));
      $app_root = '';
      if (count($parts) >= 1) $app_root = '/' . $parts[0];
      // final public URLs for browser
      $nisn_rel = $app_root . '/content/avatar/' . $avatar_file . '.png';
      $def_rel = $app_root . '/content/avatar/avatar.jpg';
      // Prefer server-side check: if the user's avatar file exists, use it; otherwise use avatar.jpg as default.
      $img_src = file_exists($avatar_fs) ? $nisn_rel : $def_rel;
      $svg_inline = 'data:image/svg+xml;utf8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="60" height="80"><rect width="100%" height="100%" fill="#e9ecef"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#6c757d" font-size="20">AV</text></svg>');
      $img_html = '<img src="' . $img_src . '" alt="Avatar" style="width:60px;height:80px;object-fit:cover;border-radius:8px;border:2px solid #fff;" onerror="var el=this; if(!el.dataset.fallback){el.dataset.fallback=1; el.src=\'' . $svg_inline . '\';}">';

      // prepare contact/WhatsApp HTML (we'll show it under the name)
      $tel_raw = trim($data['telp'] ?? '');
      $contact_html = '';
      if ($tel_raw !== '') {
        $digits = preg_replace('/[^0-9]/', '', $tel_raw);
        if (strpos($digits, '0') === 0) {
          $digits = '62' . substr($digits, 1);
        } elseif (strpos($digits, '8') === 0) {
          $digits = '62' . $digits;
        }
        $msg = 'Halo ' . trim($data['nama_lengkap'] ?? '') . ', mengenai izin Anda.';
        $wa_url = 'https://wa.me/' . $digits . '?text=' . rawurlencode($msg);
        $contact_html = '<a href="' . $wa_url . '" target="_blank" rel="noopener" class="btn btn-sm btn-success" style="margin-top:6px;"><i class="fab fa-whatsapp mr-1"></i> ' . htmlspecialchars($tel_raw) . '</a>';
      }

      echo '
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <div class="d-flex align-items-start mb-3">
              <div class="mr-3">' . $img_html . '</div>
              <div class="flex-grow-1">
                <h5 class="mb-1 font-weight-bold">' . htmlspecialchars($data['nama_lengkap']) . '</h5>
                <div class="text-muted">NISN: ' . htmlspecialchars($data['nisn']) . ' &middot; Kelas: ' . htmlspecialchars($data['nama_kelas']) . '</div>
                ' . ($contact_html ? '<div>' . $contact_html . '</div>' : '') . '
                <div class="small text-muted mt-1">Diajukan: ' . htmlspecialchars($data['date_submitted']) . '</div>
              </div>
              <div class="text-right ml-3">
                <div class="mb-1"><span class="badge badge-pill badge-info">' . htmlspecialchars($data['jenis_izin']) . '</span></div>
                <small class="text-muted">' . htmlspecialchars($data['tanggal']) . '</small>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-12 col-md-4 text-muted">Keterangan</div>
              <div class="col-12 col-md-8">
                <div class="border rounded p-2" style="background:#f8f9fb;min-height:80px;">' . nl2br(htmlspecialchars($data['keterangan'])) . '</div>
              </div>
            </div>

            <div class="row mb-2">
              <div class="col-12 col-md-4 text-muted">Status Admin</div>
              <div class="col-12 col-md-8"><span class="badge badge-' . (($data['status_izin'] == 'Disetujui') ? 'success' : ($data['status_izin'] == 'Ditolak' ? 'danger' : 'warning')) . '">' . htmlspecialchars($data['status_izin']) . '</span></div>
            </div>

            <div class="row mb-3">
              <div class="col-12 col-md-4 text-muted">Status Wali</div>
              <div class="col-12 col-md-8"><span class="badge badge-' . (($data['status_izin_wali'] == 'Disetujui') ? 'success' : ($data['status_izin_wali'] == 'Ditolak' ? 'danger' : 'warning')) . '">' . htmlspecialchars($data['status_izin_wali']) . '</span></div>
            </div>

            <hr />
            <div class="row mb-2">
              <div class="col-12 col-md-4 text-muted">Konfirmasi</div>
              <div class="col-12 col-md-8"><strong>' . ($data['konfirmasi'] ? htmlspecialchars($data['konfirmasi']) : '-') . '</strong></div>
            </div>
            
            <div class="row mb-2">
              <div class="col-12 col-md-4 text-muted">Waktu Keluar</div>
              <div class="col-12 col-md-8">' . $time_keluar . '</div>
            </div>
            <div class="row mb-2">
              <div class="col-12 col-md-4 text-muted">Waktu Kembali</div>
              <div class="col-12 col-md-8">' . $time_kembali . '</div>
            </div>
            <div class="row mb-2">
              <div class="col-12 col-md-4 text-muted">Waktu Pulang</div>
              <div class="col-12 col-md-8">' . $time_pulang . '</div>
            </div>
            <div class="row mb-3">
              <div class="col-12 col-md-4 text-muted">Durasi (Keluar → Kembali)</div>
              <div class="col-12 col-md-8 text-primary"><strong>' . $duration . '</strong></div>
            </div>

            
            <div class="row mb-2">
              <div class="col-12 col-md-4 text-muted">Alasan Penolakan (Admin)</div>
              <div class="col-12 col-md-8">' . nl2br(htmlspecialchars($data['alasan_penolakan'] ?? '-')) . '</div>
            </div>
            <div class="row mb-2">
              <div class="col-12 col-md-4 text-muted">Alasan Penolakan (Wali)</div>
              <div class="col-12 col-md-8">' . nl2br(htmlspecialchars($data['alasan_penolakan_wali'] ?? '-')) . '</div>
            </div>

          </div>
        </div>';
    } else {
      echo '<div class="alert alert-warning">Data tidak ditemukan.</div>';
    }
    break;

  /* ----------- HAPUS ----------- */
  case 'hapus':
    $id = $_POST['id'];
    $stmt = $connection->prepare("SELECT id FROM e_izin WHERE id=?");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      $stmt->close();
      $stmt2 = $connection->prepare("DELETE FROM e_izin WHERE id=?");
      $stmt2->bind_param('s', $id);
      $delete = $stmt2->execute();
      $stmt2->close();
      echo $delete ? "Data izin berhasil dihapus." : "Gagal menghapus data.";
    } else {
      $stmt->close();
      echo "Data tidak ditemukan.";
    }
    break;

  /* ----------- EDIT CATATAN ----------- */
  case 'edit_catatan':
    $id = $_POST['id'];
    $catatan = $_POST['catatan'];
    $stmt = $connection->prepare("SELECT id FROM e_izin WHERE id=? AND status_izin='Ditolak'");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      $stmt->close();
      $stmt2 = $connection->prepare("UPDATE e_izin SET alasan_penolakan=? WHERE id=?");
      $stmt2->bind_param('ss', $catatan, $id);
      $update = $stmt2->execute();
      $stmt2->close();
      echo $update ? "Catatan berhasil diperbarui." : "Gagal memperbarui catatan.";
    } else {
      $stmt->close();
      echo "Data tidak ditemukan atau status tidak sesuai.";
    }
    break;

  /* ----------- KONFIRMASI (security: keluar/pulang/kembali) ----------- */
  case 'konfirmasi':
    header('Content-Type: application/json; charset=utf-8');
    $id = intval(anti_injection($_POST['id'] ?? 0));
    $konf = strtolower(trim(anti_injection($_POST['konfirmasi'] ?? '')));
    $allowed = ['keluar', 'kembali', 'pulang'];
    $resp = ['success' => false, 'message' => 'Aksi tidak valid.'];
    if ($id <= 0 || !in_array($konf, $allowed, true)) {
      echo json_encode($resp);
      break;
    }

    $level_id = $current_user['level_id'] ?? (isset($_COOKIE['level_id']) ? $_COOKIE['level_id'] : '');
    $admin_id = 0;
    if (isset($_COOKIE['ADMIN_KEY'])) $admin_id = (int) epm_decode($_COOKIE['ADMIN_KEY']);
    $admin_tugas = '';
    if ($admin_id > 0) {
      $stmtA = $connection->prepare("SELECT tugas_tambahan FROM admin WHERE admin_id = ? LIMIT 1");
      if ($stmtA) {
        $stmtA->bind_param('i', $admin_id);
        $stmtA->execute();
        $resA = $stmtA->get_result();
        if ($resA && $resA->num_rows > 0) {
          $rowA = $resA->fetch_assoc();
          $admin_tugas = $rowA['tugas_tambahan'] ?? '';
        }
        $stmtA->close();
      }
    }
    $tugas_arr = [];
    if ($admin_tugas !== '') $tugas_arr = array_map('trim', explode(',', $admin_tugas));
    $level_int = intval($level_id);
    $is_security = ($level_int === 2) && (in_array('7', $tugas_arr) || in_array(7, $tugas_arr));

    // Only allow konfirmasi if the current session belongs to a security officer.
    if (!$is_security) {
      // If debug requested, include session info to help diagnose why it's rejected
      if (!empty($_GET['debug']) && intval($_GET['debug']) === 1) {
        $resp['message'] = 'Anda tidak memiliki hak untuk melakukan tindakan keamanan.';
        $resp['debug'] = ['admin_id' => $admin_id, 'level_id' => $level_id, 'tugas' => $tugas_arr];
        echo json_encode($resp);
        break;
      }
      $resp['message'] = 'Anda tidak memiliki hak untuk melakukan tindakan keamanan.';
      echo json_encode($resp);
      break;
    }

    // Update konfirmasi and set the appropriate timestamp depending on action
    $ok = false;
    if ($konf === 'keluar') {
      $u = $connection->prepare("UPDATE e_izin SET konfirmasi = ?, time_keluar = NOW() WHERE id = ? LIMIT 1");
    } elseif ($konf === 'kembali') {
      $u = $connection->prepare("UPDATE e_izin SET konfirmasi = ?, time_kembali = NOW() WHERE id = ? LIMIT 1");
    } elseif ($konf === 'pulang') {
      $u = $connection->prepare("UPDATE e_izin SET konfirmasi = ?, time_pulang = NOW() WHERE id = ? LIMIT 1");
    } else {
      $u = $connection->prepare("UPDATE e_izin SET konfirmasi = ? WHERE id = ? LIMIT 1");
    }

    if ($u) {
      $u->bind_param('si', $konf, $id);
      $ok = $u->execute();
      $u->close();
    } else {
      $ok = false;
    }

    if (!$ok) {
      $resp['message'] = 'Gagal memperbarui status konfirmasi.';
      echo json_encode($resp);
      break;
    }

    // If this is a return/pulang confirmation, clear the security token so it cannot be reused.
    if (in_array($konf, ['kembali', 'pulang'], true)) {
      try {
        $clear = $connection->prepare("UPDATE e_izin SET token_security = NULL WHERE id = ? LIMIT 1");
        if ($clear) {
          $clear->bind_param('i', $id);
          $clear->execute();
          $clear->close();
        }
      } catch (Exception $e) {
        // ignore
      }
    }

    if ($konf === 'keluar') {
      try {
        $newTok = bin2hex(random_bytes(4));
        $up = $connection->prepare("UPDATE e_izin SET token_security = ? WHERE id = ? LIMIT 1");
        if ($up) {
          $up->bind_param('si', $newTok, $id);
          $up->execute();
          $up->close();
        }
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $script_name = $_SERVER['SCRIPT_NAME'];
        $app_root = '/' . explode('/', trim($script_name, '/'))[0];
        $return_link = rtrim($protocol . '://' . $host . $app_root, '/') . '/admin/mod/e-izin/approve.php?t=' . $newTok . '&role=security&action=return';
        $qr_img = rtrim($protocol . '://' . $host . $app_root, '/') . '/dashboard/mod/e-izin/qrcode.php?id=' . intval($id) . '&role=security&action=return';
        $resp = ['success' => true, 'message' => 'Konfirmasi berhasil.', 'return_link' => $return_link, 'qr_img' => $qr_img];
        echo json_encode($resp);
        break;
      } catch (Exception $e) {
      }
    }

    $resp = ['success' => true, 'message' => 'Konfirmasi berhasil.'];
    echo json_encode($resp);
    break;

  default:
    echo "Aksi tidak dikenali.";
    break;
}
