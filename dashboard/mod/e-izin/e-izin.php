<?php if (empty($connection)) {
  echo 'Koneksi tidak ditemukan';
  header('location:../');
  exit();
} else {

  if (!empty($_GET['ajax_check_status']) && isset($_GET['id'])) {
    $check_id = intval($_GET['id']);
    header('Content-Type: application/json; charset=utf-8');
    $out = ['ok' => false, 'session' => isset($_COOKIE['siswa']) && !empty($_COOKIE['siswa'])];
    if ($check_id > 0) {
      $sstmt = $connection->prepare("SELECT status_izin, status_izin_wali, konfirmasi, token_security, time_keluar, time_kembali, time_pulang FROM e_izin WHERE id = ? LIMIT 1");
      if ($sstmt) {
        $sstmt->bind_param('i', $check_id);
        $sstmt->execute();
        $sres = $sstmt->get_result();
        if ($sres && $sres->num_rows > 0) {
          $r = $sres->fetch_assoc();
          $out['ok'] = true;
          $out['status_izin'] = (string)($r['status_izin'] ?? '');
          $out['status_izin_wali'] = (string)($r['status_izin_wali'] ?? '');
          $out['konfirmasi'] = (string)($r['konfirmasi'] ?? '');
          $out['token_security'] = (string)($r['token_security'] ?? '');
          $out['time_keluar'] = (string)($r['time_keluar'] ?? '');
          $out['time_kembali'] = (string)($r['time_kembali'] ?? '');
          $out['time_pulang'] = (string)($r['time_pulang'] ?? '');
          // Compute an explicit state so client can make deterministic UI decisions
          $konf = strtolower(trim($r['konfirmasi'] ?? ''));
          $has_time_kembali = !empty($r['time_kembali'] ?? '') && ($r['time_kembali'] !== '0000-00-00 00:00:00');
          $has_time_pulang = !empty($r['time_pulang'] ?? '') && ($r['time_pulang'] !== '0000-00-00 00:00:00');
          $both_approved = preg_match('/disetuju|setuju|approved/i', $r['status_izin'] ?? '') && preg_match('/disetuju|setuju|approved/i', $r['status_izin_wali'] ?? '');
          if (!$both_approved) {
            $state = 'pending';
          } else {
            // Simpler semantics: when both approved and konfirmasi is empty -> 'out_available'
            // when konfirmasi == 'keluar' -> student has been approved to return -> 'returned'
            $konf_norm = trim(strtolower($r['konfirmasi'] ?? ''));
            if ($konf_norm === '') {
              $state = 'out_available';
            } elseif ($konf_norm === 'keluar') {
              $state = 'returned';
            } else {
              $state = 'approved';
            }
          }
          $out['state'] = $state;
          // Stable version string so client can detect changes reliably
          $out['version'] = md5(($out['status_izin'] ?? '') . '|' . ($out['status_izin_wali'] ?? '') . '|' . ($out['konfirmasi'] ?? '') . '|' . ($out['state'] ?? ''));
        }
        $sstmt->close();
      }

      // Ensure explicit visual: if the label indicates 'Keluar', force red color and
      // add an inline style override to work around theme overrides.
      $btn_style = '';
      if (isset($btn_label) && $btn_label === 'Kartu Izin Keluar') {
        $btn_class = 'btn-danger';
        $btn_style = ' style="background-color:#dc3545;border-color:#dc3545;color:#fff"';
      }
    }
    echo json_encode($out);
    exit;
  }

  if (isset($_COOKIE['siswa'])) {
    $user_id = $data_user['user_id'] ?? '';

    $message = '';
    if (!empty($_GET['msg']) && $_GET['msg'] === 'success') {
      $message = '<div class="alert alert-success">Permohonan izin berhasil dikirim. Status: Menunggu.</div>';
    } elseif (!empty($_GET['error'])) {
      $message = '<div class="alert alert-danger">' . htmlspecialchars($_GET['error']) . '</div>';
    }

    $list_izin_html = '';
    $approved_kartu_html = '';
    $has_approved = false;
    $approved_entry = null;
    if (!empty($user_id)) {
      $aq = $connection->prepare("SELECT id, jenis_izin, tanggal, keterangan, status_izin, status_izin_wali FROM e_izin WHERE user_id = ? ORDER BY date_submitted DESC LIMIT 20");
      if ($aq) {
        $aq->bind_param("i", $user_id);
        $aq->execute();
        $ares = $aq->get_result();
        if ($ares && $ares->num_rows > 0) {
          while ($ar = $ares->fetch_assoc()) {
            if (preg_match('/disetuju|setuju|approved/i', $ar['status_izin'] ?? '') && preg_match('/disetuju|setuju|approved/i', $ar['status_izin_wali'] ?? '')) {
              $avatar_file = htmlspecialchars($data_user['nisn'] ?? $data_user['user_id'] ?? 'default');
              $avatar_src = '../content/avatar/' . $avatar_file . '.png';
              $qr_src_top = 'mod/e-izin/qrcode.php?id=' . intval($ar['id']) . '&role=admin&_=' . time();
              $token_top = md5($ar['id'] . '|' . $ar['tanggal'] . '|' . ($data_user['nama_lengkap'] ?? $data_user['nama'] ?? '') . '|APPROVE2025');
              $site_url_top = '';
              $sres_top = $connection->query("SELECT site_url FROM setting LIMIT 1");
              if ($sres_top) {
                $srow_top = $sres_top->fetch_assoc();
                if (!empty($srow_top['site_url'])) $site_url_top = trim($srow_top['site_url']);
              }
              if (!empty($site_url_top)) {
                if (!preg_match('#^https?://#i', $site_url_top)) {
                  $protocol_top = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                  $site_url_top = $protocol_top . '://' . $site_url_top;
                }
                $base_url_top = rtrim($site_url_top, '/');
                $admin_base_top = $base_url_top;
              } else {
                $protocol_top = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                $host_top = $_SERVER['HTTP_HOST'];
                $script_name_top = $_SERVER['SCRIPT_NAME'];
                $path_parts_top = explode('/', trim($script_name_top, '/'));
                $first_top = $path_parts_top[0] ?? '';
                $root_prefix_top = ($first_top === 'dashboard' || $first_top === 'admin' || $first_top === '') ? '' : '/' . $first_top;
                $admin_base_top = rtrim($protocol_top . '://' . $host_top . $root_prefix_top, '/');
              }
              $approve_url_top = $admin_base_top . '/admin/mod/e-izin/approve.php?id=' . intval($ar['id']) . '&token=' . $token_top;

              // determine button style/label for this approved entry by fetching token/konfirmasi/time fields
              $kartu_btn_label = 'Lihat Kartu Izin';
              $kartu_btn_class = 'btn-secondary';
              $kartu_btn_style = '';
              $aid_tmp2 = intval($ar['id']);
              $aq3 = $connection->prepare("SELECT jenis_izin, konfirmasi, token_security, time_keluar, time_kembali, time_pulang FROM e_izin WHERE id = ? LIMIT 1");
              if ($aq3) {
                $aq3->bind_param('i', $aid_tmp2);
                $aq3->execute();
                $ares3 = $aq3->get_result();
                if ($ares3 && $ares3->num_rows > 0) {
                  $row3 = $ares3->fetch_assoc();
                  $jenis3 = strtolower(trim($row3['jenis_izin'] ?? ''));
                  $konf3 = strtolower(trim($row3['konfirmasi'] ?? ''));
                  $has_time_kembali3 = !empty($row3['time_kembali'] ?? '') && ($row3['time_kembali'] !== '0000-00-00 00:00:00');
                  $is_tokened3 = !empty($row3['token_security'] ?? '');
                  if ($jenis3 === 'pulang') {
                    $kartu_btn_class = 'btn-warning';
                    $kartu_btn_label = 'Lihat Kartu Izin';
                  } else {
                    // New rule: when konfirmasi == 'keluar' treat as Kartu Izin Kembali (green)
                    if ($konf3 === 'keluar') {
                      $kartu_btn_class = 'btn-success';
                      $kartu_btn_label = 'Lihat Kartu Izin (Kembali)';
                    } else {
                      // default: approved but not yet confirmed by security => Keluar (red)
                      $kartu_btn_class = 'btn-danger';
                      $kartu_btn_label = 'Lihat Kartu Izin';
                    }
                  }
                }
                $aq3->close();
              }

              $approved_kartu_html = '<div class="card mb-4 shadow-sm" style="max-width:720px;margin:0 auto;border-radius:10px;overflow:hidden;">'
                . '<div style="display:flex;align-items:center;padding:12px;background:linear-gradient(90deg,#6a11cb,#2575fc);color:#fff;">'
                . '<img src="' . $avatar_src . '" alt="Foto" style="width:56px;height:72px;object-fit:cover;border-radius:6px;border:2px solid rgba(255,255,255,0.15);margin-right:12px;" />'
                . '<div style="flex:1 1 auto;min-width:0;">'
                . '<div style="font-weight:700;font-size:1rem;white-space:nowrap;text-overflow:ellipsis;overflow:hidden;">' . htmlspecialchars($data_user['nama_lengkap'] ?? $data_user['nama'] ?? '') . '</div>'
                . '<div style="opacity:0.9;font-size:0.85rem;">' . htmlspecialchars($data_user['nama_kelas'] ?? $data_user['kelas'] ?? '') . '</div>'
                . '</div>'
                . '<div style="flex:0 0 auto;margin-left:12px;">'
                . '<button type="button" class="btn ' . $kartu_btn_class . ' btn-sm btn-show-kartu" data-toggle="modal" data-target="#modal-kartu-izin" '
                . 'data-id="' . intval($ar['id']) . '" '
                . 'data-jenis="' . htmlspecialchars($ar['jenis_izin']) . '" '
                . 'data-tanggal="' . htmlspecialchars($ar['tanggal']) . '" '
                . 'data-keterangan="' . htmlspecialchars($ar['keterangan'] ?? '') . '" '
                . 'data-nama="' . htmlspecialchars($data_user['nama_lengkap'] ?? $data_user['nama'] ?? '') . '" '
                . 'data-kelas="' . htmlspecialchars($data_user['nama_kelas'] ?? $data_user['kelas'] ?? '') . '" '
                . 'data-avatar="' . $avatar_src . '" '
                . 'data-approve-url="' . $approve_url_top . '">' . htmlspecialchars($kartu_btn_label) . '</button>'
                . '</div>'
                . '</div>'
                . '</div>';
              $has_approved = true;
              $approved_entry = $ar;
              break;
            }
          }
        }
        $aq->close();
        $has_active_approved = false;
        $active_approved_entry = null;

        // Also fetch timestamp fields so we can determine whether the student has actually returned.
        // Include 'kembali' so that returned (confirmed) izin are considered active for rendering
        // the Kartu Izin Masuk state (green) immediately after security confirms.
        // Only consider konfirmasi empty or 'keluar' as active approved entry per simplified rule
        $active_q = $connection->prepare("SELECT id, jenis_izin, tanggal, keterangan, konfirmasi, token_security, time_keluar, time_kembali, time_pulang FROM e_izin WHERE user_id = ? AND (LOWER(status_izin) IN ('disetujui','approved')) AND (LOWER(status_izin_wali) IN ('disetujui','approved')) AND (konfirmasi IS NULL OR LOWER(konfirmasi) IN ('keluar')) ORDER BY date_submitted DESC LIMIT 1");
        if ($active_q) {
          $active_q->bind_param('i', $user_id);
          $active_q->execute();
          $ares2 = $active_q->get_result();
          if ($ares2 && $ares2->num_rows > 0) {
            $has_active_approved = true;
            $active_approved_entry = $ares2->fetch_assoc();
          }
          $active_q->close();
        }
        $approved_konfirmasi = '';
        $approved_token_security = '';
        if ($has_approved && !empty($approved_entry)) {
          $aq2 = $connection->prepare("SELECT konfirmasi, token_security FROM e_izin WHERE id = ? LIMIT 1");
          if ($aq2) {
            $aid_tmp = intval($approved_entry['id']);
            $aq2->bind_param('i', $aid_tmp);
            $aq2->execute();
            $res_tmp = $aq2->get_result();
            if ($res_tmp && $res_tmp->num_rows > 0) {
              $r2 = $res_tmp->fetch_assoc();
              $approved_konfirmasi = strtolower(trim($r2['konfirmasi'] ?? ''));
              $approved_token_security = $r2['token_security'] ?? '';
            }
            $aq2->close();
          }
        }
      }
    }
    if (!empty($user_id)) {
      $stmt2 = $connection->prepare("SELECT id, jenis_izin, tanggal, keterangan, status_izin, status_izin_wali, date_submitted FROM e_izin WHERE user_id = ? ORDER BY date_submitted DESC LIMIT 20");
      if ($stmt2) {
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();
        $res = $stmt2->get_result();
        if ($res && $res->num_rows > 0) {
          $list_izin_html .= '<div class="card mt-4"><div class="card-header bg-gradient-primary"><h4 class="mb-0 text-white"><i class="fas fa-history"></i> Riwayat Pengajuan e-Izin</h4></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover table-bordered mb-0"><thead class="thead-light"><tr class="text-center"><th style="width: 50px;">#</th><th>Jenis Izin</th><th style="width: 130px;">Tanggal / Waktu</th><th style="width: 120px;">Petugas Piket</th><th style="width: 120px;">Wali Kelas</th></tr></thead><tbody>';
          $i = 1;
          while ($row = $res->fetch_assoc()) {
            $tgl = date('d M Y H:i', strtotime($row['date_submitted'] ?? $row['tanggal']));
            $tgl_submitted = date('d M Y H:i', strtotime($row['date_submitted']));

            $status = strtolower($row['status_izin']);
            $status_badge = '';
            if ($status === 'menunggu' || $status === 'pending') {
              $status_badge = '<span class="badge badge-warning"><i class="fas fa-clock"></i> Menunggu</span>';
            } elseif ($status === 'disetujui' || $status === 'approved') {
              $status_badge = '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Disetujui</span>';
            } elseif ($status === 'ditolak' || $status === 'rejected') {
              $reason = htmlspecialchars($row['alasan_penolakan'] ?? '');
              $status_badge = '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Ditolak</span>';
              if ($reason !== '') {
                $status_badge .= '<div class="small text-muted mt-1">Alasan: ' . $reason . '</div>';
              }
            } else {
              $status_badge = '<span class="badge badge-secondary">' . htmlspecialchars($row['status_izin']) . '</span>';
            }

            $status_wali = strtolower($row['status_izin_wali'] ?? 'menunggu');
            $status_wali_badge = '';
            if ($status_wali === 'menunggu' || $status_wali === 'pending') {
              $status_wali_badge = '<span class="badge badge-warning"><i class="fas fa-clock"></i> Menunggu</span>';
            } elseif ($status_wali === 'disetujui' || $status_wali === 'approved') {
              $status_wali_badge = '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Disetujui</span>';
            } elseif ($status_wali === 'ditolak' || $status_wali === 'rejected') {
              $reason_wali = htmlspecialchars($row['alasan_penolakan_wali'] ?? '');
              $status_wali_badge = '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Ditolak</span>';
              if ($reason_wali !== '') {
                $status_wali_badge .= '<div class="small text-muted mt-1">Alasan Wali: ' . $reason_wali . '</div>';
              }
            } else {
              $status_wali_badge = '<span class="badge badge-secondary">' . htmlspecialchars($row['status_izin_wali'] ?? 'N/A') . '</span>';
            }

            $admin_qr_html = '';
            $wali_qr_html = '';

            $script_name = $_SERVER['SCRIPT_NAME'];
            $parts = explode('/', trim($script_name, '/'));
            $root = '';
            if (count($parts) >= 2) {
              $root = '/' . $parts[0] . '/' . $parts[1];
            } elseif (count($parts) === 1) {
              $root = '/' . $parts[0];
            }

            if ($status === 'menunggu') {
              $admin_qr_html = '<div class="mt-2 text-center">'
                . '<button type="button" class="btn btn-outline-primary btn-sm btn-show-qrcode" data-id="' . intval($row['id']) . '" data-role="admin" title="QR Approve (Petugas)" style="font-size:0.95rem;">'
                . '<i class="fas fa-qrcode mr-1"></i>QR'
                . '</button>'
                . '</div>';
            }

            if ($status_wali === 'menunggu') {
              $wali_qr_html = '<div class="mt-2 text-center">'
                . '<button type="button" class="btn btn-outline-primary btn-sm btn-show-qrcode" data-id="' . intval($row['id']) . '" data-role="wali" title="QR Approve (Wali Kelas)" style="font-size:0.95rem;">'
                . '<i class="fas fa-qrcode mr-1"></i>QR'
                . '</button>'
                . '</div>';
            }

            $is_admin_approved = preg_match('/disetuju|setuju|approved/i', $row['status_izin'] ?? '');
            $is_wali_approved = preg_match('/disetuju|setuju|approved/i', $row['status_izin_wali'] ?? '');

            $student_name = htmlspecialchars($data_user['nama_lengkap'] ?? $data_user['nama'] ?? '');
            $student_kelas = htmlspecialchars($data_user['nama_kelas'] ?? $data_user['kelas'] ?? '');
            $avatar_file = htmlspecialchars($data_user['nisn'] ?? $data_user['user_id'] ?? 'default');
            $avatar_src = '../content/avatar/' . $avatar_file . '.png';

            $token = md5($row['id'] . '|' . $row['tanggal'] . '|' . ($data_user['nama_lengkap'] ?? $data_user['nama'] ?? '') . '|APPROVE2025');
            $site_url = '';
            $sres = $connection->query("SELECT site_url FROM setting LIMIT 1");
            if ($sres) {
              $srow = $sres->fetch_assoc();
              if (!empty($srow['site_url'])) $site_url = trim($srow['site_url']);
            }
            if (!empty($site_url)) {
              if (!preg_match('#^https?://#i', $site_url)) {
                $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                $site_url = $protocol . '://' . $site_url;
              }
              $base_url = rtrim($site_url, '/');
              $admin_base = $base_url;
            } else {
              $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
              $host = $_SERVER['HTTP_HOST'];
              $script_name = $_SERVER['SCRIPT_NAME'];
              $path_parts = explode('/', trim($script_name, '/'));
              $first = $path_parts[0] ?? '';
              $root_prefix = ($first === 'dashboard' || $first === 'admin' || $first === '') ? '' : '/' . $first;
              $admin_base = rtrim($protocol . '://' . $host . $root_prefix, '/');
            }
            $approve_url = $admin_base . '/admin/mod/e-izin/approve.php?id=' . intval($row['id']) . '&token=' . $token;

            $qr_src = 'mod/e-izin/qrcode.php?id=' . intval($row['id']) . '&role=admin&_=' . time();
            $diajukan_cell = '<div class="card kartu-izin-inline p-2" style="max-width:320px;margin:0 auto;border:1px solid #eef2f6;border-radius:8px;">'
              . '<div class="d-flex align-items-center">'
              . '<img src="' . $avatar_src . '" alt="Foto" style="width:64px;height:80px;object-fit:cover;border-radius:6px;border:1px solid #e6e6e6;margin-right:10px;" />'
              . '<div class="flex-fill">'
              . '<div class="font-weight-bold text-truncate" style="max-width:150px;">' . $student_name . '</div>'
              . '<div class="small text-muted">' . $student_kelas . '</div>'
              . '</div></div>'
              . '<div class="mt-2 small"><strong>Jenis:</strong> ' . htmlspecialchars($row['jenis_izin']) . '</div>'
              . '<div class="small"><strong>Tanggal:</strong> ' . htmlspecialchars($row['tanggal']) . '</div>'
              . '<div class="text-center mt-2">'
              . '<img src="' . $qr_src . '" alt="QR Kartu Izin" style="width:110px;height:110px;border:1px solid #eee;padding:6px;background:#fff;border-radius:8px;" />'
              . '</div>'
              . '</div>';

            $list_izin_html .= '<tr><td class="text-center font-weight-bold">' . $i++ . '</td><td>' . htmlspecialchars($row['jenis_izin']) . '</td><td class="text-center">' . $tgl . '</td><td class="text-center">' . $status_badge . $admin_qr_html . '</td><td class="text-center">' . $status_wali_badge . $wali_qr_html . '</td></tr>';
          }
          $list_izin_html .= '</tbody></table></div></div></div>';

          $list_izin_html .= '
<div class="modal fade" id="modal-qrcode" tabindex="-1" role="dialog" aria-labelledby="modalQrcodeLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalQrcodeLabel">QR Code Approval</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <div style="display:inline-block;background:#fff;padding:8px;border-radius:8px;border:1px solid #e6e6e6;">
          <img id="img-qrcode" src="" alt="QR Code Izin" style="display:block;max-width:220px;max-height:220px;" />
        </div>
        <div class="mt-2"><small id="qr-note" class="text-muted">Tunjukkan QR ini ke admin untuk proses approve</small></div>
        <!-- removed manual open-in-new-tab button for security; remote approve uses encrypted link -->
          <div class="mt-3 text-center">
          <!-- Hidden field stores encrypted approve URL; not shown to user -->
          <input type="hidden" id="qr-approve-link" value="" />
          <div id="qr-link-visible" class="mb-2" style="display:none;word-break:break-all;font-size:0.9rem;"></div>
          <button class="btn btn-outline-primary btn-sm" type="button" id="btn-copy-approve"><i class="fas fa-copy"></i> Salin Link Approve</button>
          <div id="qr-wali-helper" class="small text-muted mt-2" style="display:none;">Klik tombol untuk menampilkan QR; gunakan "Salin Link Approve" jika wali melakukan approval jarak jauh.</div>
        </div>
      </div>
    </div>
  </div>
</div>';

          $list_izin_html .= '
      <div class="modal fade" id="modal-kartu-izin" tabindex="-1" role="dialog" aria-labelledby="modalKartuLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
              <h5 class="modal-title" id="modalKartuLabel">Kartu Izin</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body text-center">
              <div style="display:inline-block;background:#fff;padding:8px;border-radius:8px;border:1px solid #e6e6e6;">
                <div class="mt-1"><img id="kartu-qrcode" src="" alt="QR" style="display:block;max-width:260px;max-height:260px;border:1px solid #eee;padding:8px;background:#fff;border-radius:10px;margin:6px auto 0;" /></div>
              </div>
              <div class="mt-2"><small class="text-muted">Tunjukkan QR ini kepada petugas untuk verifikasi.</small></div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
          </div>
        </div>
      </div>';
        } else {
          $list_izin_html = '<div class="card mt-4"><div class="card-body text-center text-muted py-5"><i class="fas fa-inbox fa-3x mb-3 d-block"></i><h5>Belum Ada Riwayat Izin</h5><p>Anda belum pernah mengajukan permohonan izin.</p></div></div>';
        }
        $stmt2->close();
      }
    }

    $has_pending = false;
    $pending_html = '';
    if (!empty($user_id)) {
      $pstmt = $connection->prepare("SELECT id, jenis_izin, tanggal, date_submitted FROM e_izin WHERE user_id = ? AND (LOWER(status_izin) IN ('menunggu','pending') OR LOWER(status_izin_wali) IN ('menunggu','pending')) ORDER BY date_submitted DESC LIMIT 1");
      if ($pstmt) {
        $pstmt->bind_param("i", $user_id);
        $pstmt->execute();
        $pres = $pstmt->get_result();
        if ($pres && $pres->num_rows > 0) {
          $prow = $pres->fetch_assoc();
          $has_pending = true;

          $tgl_fmt = date('d M Y', strtotime($prow['tanggal']));
          $tgl_submitted_fmt = date('d M Y H:i', strtotime($prow['date_submitted']));

          $pending_html = '
          <div class="alert alert-warning border-left-warning shadow-sm">
            <div class="d-flex align-items-center">
              <i class="fas fa-exclamation-triangle fa-2x mr-3"></i>
              <div>
                <h5 class="alert-heading mb-2"><i class="fas fa-clock"></i> Permohonan Sedang Diproses</h5>
                <p class="mb-2">Anda memiliki permohonan e-izin yang masih menunggu persetujuan:</p>
                <ul class="mb-2 pl-4">
                  <li><strong>Jenis:</strong> ' . htmlspecialchars($prow['jenis_izin']) . '</li>
                  <li><strong>Tanggal:</strong> ' . $tgl_fmt . '</li>
                  <li><strong>Diajukan:</strong> ' . $tgl_submitted_fmt . '</li>
                </ul>
                <p class="mb-0"><i class="fas fa-info-circle"></i> Silakan tunggu proses validasi dari wali kelas dan admin/guru sebelum mengajukan e-izin baru.</p>
              </div>
            </div>
          </div>';
        }
        $pstmt->close();
      }
    }

    $form_disabled_attr = ($has_pending || (!empty($has_active_approved) && $has_active_approved)) ? ' disabled' : '';

    if ($has_pending) {
      $submit_button_html = '<button type="button" class="btn btn-secondary btn-block" disabled><i class="fas fa-hourglass-half"></i> Menunggu Persetujuan</button>';
    } elseif (!empty($has_active_approved) && $has_active_approved && !empty($active_approved_entry)) {
      $aid = intval($active_approved_entry['id']);
      $ajenis = htmlspecialchars($active_approved_entry['jenis_izin']);
      $atanggal = htmlspecialchars($active_approved_entry['tanggal']);
      $aketerangan = htmlspecialchars($active_approved_entry['keterangan'] ?? '');
      $aname = htmlspecialchars($data_user['nama_lengkap'] ?? $data_user['nama'] ?? '');
      $akelas = htmlspecialchars($data_user['nama_kelas'] ?? $data_user['kelas'] ?? '');
      $avatar_file = htmlspecialchars($data_user['nisn'] ?? $data_user['user_id'] ?? 'default');
      $avatar_src = '../content/avatar/' . $avatar_file . '.png';

      $token = md5($aid . '|' . $active_approved_entry['tanggal'] . '|' . ($data_user['nama_lengkap'] ?? $data_user['nama'] ?? '') . '|APPROVE2025');
      $site_url = '';
      $sres = $connection->query("SELECT site_url FROM setting LIMIT 1");
      if ($sres) {
        $srow = $sres->fetch_assoc();
        if (!empty($srow['site_url'])) $site_url = trim($srow['site_url']);
      }
      if (!empty($site_url)) {
        if (!preg_match('#^https?://#i', $site_url)) {
          $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
          $site_url = $protocol . '://' . $site_url;
        }
        $base_url = rtrim($site_url, '/');
        $admin_base = $base_url;
      } else {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $script_name = $_SERVER['SCRIPT_NAME'];
        $path_parts = explode('/', trim($script_name, '/'));
        $first = $path_parts[0] ?? '';
        $root_prefix = ($first === 'dashboard' || $first === 'admin' || $first === '') ? '' : '/' . $first;
        $admin_base = rtrim($protocol . '://' . $host . $root_prefix, '/');
      }
      $approve_url = $admin_base . '/admin/mod/e-izin/approve.php?id=' . $aid . '&token=' . $token;

      $jenis_lower = strtolower(trim($ajenis));
      // Determine actual return/exit status from konfirmasi and timestamps
      $konf_lower = strtolower(trim($active_approved_entry['konfirmasi'] ?? ''));
      $has_time_keluar = !empty($active_approved_entry['time_keluar'] ?? '') && ($active_approved_entry['time_keluar'] !== '0000-00-00 00:00:00');
      $has_time_kembali = !empty($active_approved_entry['time_kembali'] ?? '') && ($active_approved_entry['time_kembali'] !== '0000-00-00 00:00:00');
      $has_time_pulang = !empty($active_approved_entry['time_pulang'] ?? '') && ($active_approved_entry['time_pulang'] !== '0000-00-00 00:00:00');
      $is_tokened = !empty($active_approved_entry['token_security'] ?? '');

      if ($jenis_lower === 'pulang') {
        $btn_class = 'btn-warning';
        $btn_label = 'Kartu Izin Pulang';
      } else {
        // Simplified: Kartu Izin Kembali is shown when konfirmasi == 'keluar'.
        if ($konf_lower === 'keluar') {
          $btn_class = 'btn-success';
          $btn_label = 'Kartu Izin Kembali';
        } else {
          // Not yet returned: label stays as Keluar. Do NOT mark green just because a token exists.
          // Keep the button red until a real 'kembali' konfirmasi or time_kembali is present.
          $btn_class = 'btn-danger';
          $btn_label = 'Kartu Izin Keluar';
        }
      }

      $submit_button_html = '<button type="button" class="btn ' . $btn_class . ' btn-block btn-show-kartu"' . $btn_style . ' data-toggle="modal" data-target="#modal-kartu-izin" '
        . 'data-id="' . $aid . '" '
        . 'data-jenis="' . $ajenis . '" '
        . 'data-tanggal="' . $atanggal . '" '
        . 'data-keterangan="' . $aketerangan . '" '
        . 'data-nama="' . $aname . '" '
        . 'data-kelas="' . $akelas . '" '
        . 'data-avatar="' . $avatar_src . '" '
        . 'data-approve-url="' . $approve_url . '">'
        . '<i class="fas fa-id-card"></i> ' . $btn_label . '</button>';
    } else {
      $submit_button_html = '<button type="submit" class="btn btn-primary btn-block"><i class="fas fa-paper-plane"></i> Ajukan Izin</button>';
    }

    echo '
<!-- Header -->
<div class="header bg-primary pb-6">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-4">
        <div class="col-lg-6 col-7">
          <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
            <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
              <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
              <li class="breadcrumb-item active" aria-current="page">Pengajuan e-Izin</li>
            </ol>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Page content -->
<div class="container-fluid mt--6 izin-page-container">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card mb-4 izin-card">
        <div class="card-header">
          <h3 class="mb-0">Form Pengajuan e-Izin</h3>
        </div>
        <div class="card-body">
          ' . ($message ?? '') . '
          ' . $pending_html . '
          
          <div class="bg-light p-4 rounded mb-4">
            <h5 class="mb-3"><i class="fas fa-info-circle text-primary"></i> Panduan Pengajuan e-Izin</h5>
            <ul class="mb-0 pl-4">
              <li>Pilih jenis izin (Keluar-Masuk atau Pulang)</li>
              <li>Pilih tanggal izin (e-izin hanya berlaku untuk 1 hari)</li>
              <li>Tambahkan keterangan atau alasan (wajib diisi)</li>
              <li>Tunggu persetujuan dari wali kelas dan admin/guru</li>
            </ul>
          </div>
          
          <form method="post" action="mod/e-izin/proses.php?action=add_izin">
            <div class="form-group">
              <label class="font-weight-bold">
                <i class="fas fa-tag text-primary"></i> Jenis Izin <span class="text-danger">*</span>
              </label>
              <div>
                <div class="custom-control custom-radio custom-control-inline">
                  <input type="radio" id="jenis_pulang_kembali" name="jenis_izin" value="Keluar-Masuk" class="custom-control-input" required' . $form_disabled_attr . '>
                  <label class="custom-control-label" for="jenis_pulang_kembali">Keluar-Masuk</label>
                </div>
                <div class="custom-control custom-radio custom-control-inline">
                  <input type="radio" id="jenis_pulang" name="jenis_izin" value="Pulang" class="custom-control-input"' . $form_disabled_attr . '>
                  <label class="custom-control-label" for="jenis_pulang">Pulang</label>
                </div>
              </div>
              <small class="form-text text-muted">Pilih salah satu jenis izin.</small>
            </div>
            
            <div class="form-row">
              <div class="form-group col-md-12">
                <label for="tanggal" class="font-weight-bold">
                  <i class="fas fa-calendar-day text-success"></i> Tanggal e-Izin <span class="text-danger">*</span>
                </label>
                <!-- Tanggal dikunci: kirim sebagai hidden, tampilkan sebagai teks tidak dapat diubah -->
                <input type="hidden" name="tanggal" value="' . date('Y-m-d') . '">
                <input type="text" class="form-control form-control-lg" value="' . date('d M Y') . '" disabled autocomplete="off">
                <small class="form-text text-muted"><i class="fas fa-info-circle"></i> Tanggal tidak dapat diubah — e-Izin hanya berlaku untuk hari ini.</small>
              </div>
            </div>
            
            <div class="form-group">
              <label for="keterangan" class="font-weight-bold">
                <i class="fas fa-comment-alt text-warning"></i> Keterangan / Catatan <span class="text-danger">*</span>
              </label>
              <textarea id="keterangan" name="keterangan" class="form-control" rows="4" placeholder="Tambahkan keterangan atau alasan lebih detail... (Wajib)" required' . $form_disabled_attr . ' maxlength="500"></textarea>
              <small class="form-text text-muted text-danger">Wajib diisi. Maksimal 500 karakter</small>
            </div>
            
            <hr class="my-4">
            
            ' . $submit_button_html . '
          </form>

          ' . $list_izin_html . '
        </div>
      </div>
    </div>
  </div>

  <!-- Spacer tambahan sebagai fallback agar konten tidak tertutup footer (tinggi disesuaikan) -->
  <div style="height:140px" aria-hidden="true"></div>
</div>';

    $has_returned = false;
    $rq = $connection->prepare("SELECT id FROM e_izin WHERE user_id = ? AND (LOWER(status_izin) IN ('disetujui','approved')) AND (LOWER(status_izin_wali) IN ('disetujui','approved')) AND LOWER(konfirmasi) IN ('kembali','pulang') LIMIT 1");
    if ($rq) {
      $rq->bind_param('i', $user_id);
      $rq->execute();
      $rres = $rq->get_result();
      if ($rres && $rres->num_rows > 0) $has_returned = true;
      $rq->close();
    }
    if ($has_returned) {
      echo "<script>document.addEventListener('DOMContentLoaded',function(){try{var el=document.querySelector('.izin-card'); if(el){el.scrollIntoView({behavior:'smooth',block:'center'});} var first=document.querySelector('#keterangan'); if(first){first.focus();} }catch(e){} });</script>";
    }
    // If there is a pending or active approved izin, expose its id to the client
    // so the client-side script can start background polling and autoreload.
    if (!empty($has_pending) && !empty($prow) && !empty($prow['id'])) {
      echo "<script>window._eizin_watch_id = " . intval($prow['id']) . ";</script>";
    } elseif (!empty($has_active_approved) && !empty($active_approved_entry) && !empty($active_approved_entry['id'])) {
      echo "<script>window._eizin_watch_id = " . intval($active_approved_entry['id']) . ";</script>";
    }
  }
}
