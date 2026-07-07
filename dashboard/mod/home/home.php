<?php
$show_modal = true;
$latest_updated_at = null;
if (isset($connection) && $connection) {
  $q = $connection->query("SELECT MAX(updated_at) FROM pembaharuan");
  if ($q) {
    $row = $q->fetch_row();
    $latest_updated_at = $row ? $row[0] : null;
    // Fallback: if still null, try MAX(created_at)
    if (!$latest_updated_at) {
      $q2 = $connection->query("SELECT MAX(created_at) FROM pembaharuan");
      if ($q2) {
        $row2 = $q2->fetch_row();
        $latest_updated_at = $row2 ? $row2[0] : null;
      }
    }
  }
}

$updates = array();
if (isset($connection) && $connection) {
  $q = $connection->query("SELECT * FROM pembaharuan ORDER BY release_date DESC, id DESC");
  if ($q && $q->num_rows > 0) {
    while ($row = $q->fetch_assoc()) {
      $updates[] = $row;
    }
  }
}

// If user previously chose to hide updates for the current latest update, respect that
$cookie_hide_raw = isset($_COOKIE['hide_pembaharuan']) ? $_COOKIE['hide_pembaharuan'] : null;
$cookie_hide_decoded = $cookie_hide_raw !== null ? rawurldecode($cookie_hide_raw) : null;
$cookie_hide = null;
if ($cookie_hide_decoded !== null) {
  if (strpos($cookie_hide_decoded, 'v2:') === 0) {
    $cookie_hide = substr($cookie_hide_decoded, 3);
  } else {
    // legacy plain value (timestamp or '1') — accept it if it exactly equals latest
    $cookie_hide = $cookie_hide_decoded;
  }
}
if ($latest_updated_at && $cookie_hide !== null && $cookie_hide === $latest_updated_at) {
  $show_modal = false;
}

// Handle telp_ortu submission (save parent's phone number)
$telp_ortu_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['telp_ortu_submit'])) {
  $telp_raw = isset($_POST['telp_ortu']) ? $_POST['telp_ortu'] : '';
  $telp_digits = preg_replace('/\D/', '', $telp_raw);
  if (strlen($telp_digits) >= 9 && strlen($telp_digits) <= 13 && !empty($data_user['user_id']) && isset($connection) && $connection) {
    $user_id_safe = $connection->real_escape_string($data_user['user_id']);
    $telp_safe = $connection->real_escape_string($telp_digits);
    $connection->query("UPDATE `user` SET `telp_ortu` = '$telp_safe' WHERE `user_id` = '$user_id_safe' LIMIT 1");
    // Reload to reflect change and avoid resubmission
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
  } else {
    $telp_ortu_error = 'Nomor telepon orang tua harus berupa 9-13 digit angka.';
  }
}
?>

<?php if ($show_modal && count($updates) > 0): ?>
  <div class="modal fade" id="modalPembaharuan" tabindex="-1" role="dialog" data-latest="<?php echo htmlspecialchars(addslashes($latest_updated_at ?? '')); ?>">
    <div class="modal-dialog modal-md pembaharuan-modal-dialog" role="document">
      <div class="modal-content pembaharuan-modal-content">
        <div class="modal-header bg-gradient-primary text-white pembaharuan-modal-header">
          <h6 class="modal-title text-white d-flex align-items-center pembaharuan-modal-title">
            <i class="fas fa-bell me-2 pembaharuan-modal-icon"></i>Informasi Pembaharuan
          </h6>
          <button type="button" class="btn-close btn-close-white pembaharuan-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body pembaharuan-modal-body">
          <?php
          $grouped = array();
          foreach ($updates as $row) {
            $key = $row['version'] . '|' . $row['release_date'];
            if (!isset($grouped[$key])) {
              $grouped[$key] = array();
            }
            $grouped[$key][] = $row;
          }
          foreach ($grouped as $key => $items):
            $first = $items[0];
          ?>
            <div class="mb-3 pb-2 border-bottom border-light">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0 pembaharuan-version-title">
                  versi <?php echo htmlspecialchars($first['version']); ?>
                </h6>
                <small class="text-muted pembaharuan-date">
                  <?php echo date('d M Y', strtotime($first['release_date'])); ?>
                </small>
              </div>
              <?php
              echo '<div class="update-list">';
              foreach ($items as $row) {
                // pembaharuan
                if (isset($row['pembaharuan']) && trim($row['pembaharuan']) !== '') {
                  $lines_p = preg_split('/\r?\n/', $row['pembaharuan']);
                  foreach ($lines_p as $desc_raw) {
                    $desc = trim($desc_raw);
                    if ($desc === '') continue;
                    echo '<div class="update-item d-flex align-items-start mb-2">';
                    echo '<span class="badge bg-success me-2 mt-1 pembaharuan-badge">PEMBAHARUAN</span>';
                    echo '<span class="update-text pembaharuan-update-text">' . htmlspecialchars($desc) . '</span>';
                    echo '</div>';
                  }
                }
                // perbaikan
                if (isset($row['perbaikan']) && trim($row['perbaikan']) !== '') {
                  $lines_r = preg_split('/\r?\n/', $row['perbaikan']);
                  foreach ($lines_r as $desc_raw) {
                    $desc = trim($desc_raw);
                    if ($desc === '') continue;
                    echo '<div class="update-item d-flex align-items-start mb-2">';
                    echo '<span class="badge bg-info me-2 mt-1 pembaharuan-badge">PERBAIKAN</span>';
                    echo '<span class="update-text pembaharuan-update-text">' . htmlspecialchars($desc) . '</span>';
                    echo '</div>';
                  }
                }
              }
              echo '</div>';
              ?>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="modal-footer border-0 pembaharuan-modal-footer">
          <div class="d-flex gap-2 w-100">
            <button type="button" id="hidePembaharuan" class="btn btn-outline-secondary btn-sm flex-fill pembaharuan-action-btn">
              <i class="fas fa-eye-slash me-1 pembaharuan-action-icon"></i>Jangan Tampilkan Lagi
            </button>
            <button type="button" class="btn btn-primary btn-sm flex-fill pembaharuan-action-btn" data-bs-dismiss="modal">
              <i class="fas fa-check me-1 pembaharuan-action-icon"></i>Mengerti
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
<?php
// If logged-in siswa and parent phone not set, show enforced modal
if (isset($_COOKIE['siswa']) && empty($data_user['telp_ortu'])): ?>
  <div class="modal fade" id="modalTelpOrtu" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
      <div class="modal-content" style="border-radius:8px;overflow:hidden;">
        <div class="modal-header bg-gradient-primary text-white" style="padding:10px 12px;border:none;">
          <h6 class="modal-title">Isi Nomor Telepon Orang Tua/Wali</h6>
        </div>
        <form method="post" id="formTelpOrtu" novalidate>
          <div class="modal-body" style="padding:12px;">
            <div class="mb-2">
              <label for="telpOrtuInput" class="form-label">Nomor Telepon (9-13 digit)</label>
              <input type="tel" name="telp_ortu" id="telpOrtuInput" class="form-control" maxlength="13" inputmode="numeric" autocomplete="tel" required />
              <div id="telpOrtuHelp" class="form-text text-danger" style="display:none;margin-top:6px;font-size:13px;"></div>
              <?php if (!empty($telp_ortu_error)): ?>
                <div class="form-text text-danger" style="margin-top:6px;"><?php echo htmlspecialchars($telp_ortu_error); ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="modal-footer" style="padding:8px 12px;border-top:none;">
            <input type="hidden" name="telp_ortu_submit" value="1">
            <button type="submit" id="submitTelpOrtu" class="btn btn-primary w-100" disabled>Simpan & Lanjutkan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>
<?php
if (empty($connection)) {
  echo 'Koneksi tidak ditemukan';
  header('Location: ../');
  exit();
}

if (isset($_COOKIE['siswa'])) {
  $data_kelas = null;
  $query_kelas = "SELECT nama_kelas FROM kelas WHERE kelas_id='" . $data_user['kelas'] . "' LIMIT 1";
  $result_kelas = $connection->query($query_kelas);
  if ($result_kelas && $result_kelas->num_rows > 0) {
    $data_kelas = $result_kelas->fetch_assoc();
    $nama_kelas = htmlspecialchars($data_kelas['nama_kelas']);
  }
  // Ambil tahun pelajaran aktif dari tabel `tahun_pelajaran`
  $tahun_ajaran_display = date('Y');
  $semester_display = '';
  if (isset($connection) && $connection) {
    $q_tahun = $connection->query("SELECT tahun, semester FROM tahun_pelajaran WHERE aktif='Y' LIMIT 1");
    if ($q_tahun && $q_tahun->num_rows > 0) {
      $r_t = $q_tahun->fetch_assoc();
      if (!empty($r_t['tahun'])) {
        $tahun_ajaran_display = $r_t['tahun'];
      }
      if (!empty($r_t['semester'])) {
        $semester_display = $r_t['semester'];
      }
    }
  }
?>
  <!-- Dashboard Home Container -->
  <div class="home-dashboard-container">
    <!-- Welcome Header -->
    <div class="welcome-header" style="position:relative;">
      <?php if ($poin_total >= 100) { ?>
      <!-- Warning: Poin floating notification -->
      <div style="position:absolute;top:8px;left:12px;right:12px;z-index:10;">
        <a href="poin" style="text-decoration:none;">
          <div style="background:linear-gradient(135deg,#ff6f00,#e65100);color:#fff;border-radius:12px;padding:10px 14px;display:flex;align-items:center;gap:10px;box-shadow:0 4px 12px rgba(230,81,0,0.4);">
            <div style="background:rgba(255,255,255,0.2);border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fas fa-exclamation-triangle" style="font-size:14px;"></i>
            </div>
            <div>
              <div style="font-weight:600;font-size:13px;">Peringatan Poin!</div>
              <div style="font-size:11px;opacity:0.9;">Total poin kamu <?= $poin_total ?> (&ge;100). Orang tua/wali akan dipanggil.</div>
            </div>
          </div>
        </a>
      </div>
      <?php } elseif ($poin_notif_baru > 0) { ?>
      <!-- Notification: Pelanggaran Baru floating -->
      <div style="position:absolute;top:8px;left:12px;right:12px;z-index:10;">
        <a href="poin" style="text-decoration:none;">
          <div style="background:linear-gradient(135deg,#e53935,#c62828);color:#fff;border-radius:12px;padding:10px 14px;display:flex;align-items:center;gap:10px;box-shadow:0 4px 12px rgba(229,57,53,0.4);">
            <div style="background:rgba(255,255,255,0.2);border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fas fa-bell" style="font-size:14px;"></i>
            </div>
            <div>
              <div style="font-weight:600;font-size:13px;">Pelanggaran Baru!</div>
              <div style="font-size:11px;opacity:0.9;">Kamu memiliki <?= $poin_notif_baru ?> catatan pelanggaran baru. Ketuk untuk melihat.</div>
            </div>
            <i class="fas fa-chevron-right ml-auto" style="opacity:0.7;font-size:12px;"></i>
          </div>
        </a>
      </div>
      <?php } ?>
      <div class="container-fluid">
        <div class="welcome-content">
          <div class="welcome-text">
            <h5>Selamat datang,</h5>
            <h3><?= htmlspecialchars($data_user['nama_lengkap'] ?? 'Siswa') ?></h3>
          </div>
          <div class="datetime-info">
            <?php if (!empty($semester_display)): ?>
              <div id="currentSemester">Semester <?= htmlspecialchars($semester_display) ?></div>
            <?php endif; ?>
            <div id="currentAcademic">Tahun Pelajaran <?= htmlspecialchars($tahun_ajaran_display) ?></div>
          </div>
        </div>
        <!-- Main Menu -->
        <div class="main-menu-card">
          <div class="menu-grid">
            <!-- Identitas Menu -->
            <div class="menu-item">
              <a href="identitas">
                <div class="menu-icon">
                  <i class="fas fa-id-card text-primary"></i>
                  <?php if (isset($data_user['konfirmasi']) && ($data_user['konfirmasi'] === 'Belum Konfirmasi' || $data_user['konfirmasi'] === 'Belum Sesuai')): ?>
                    <span class="notification-badge">!</span>
                  <?php endif; ?>
                </div>
                <div class="menu-text">Identitas</div>
              </a>
            </div>
            <!-- Berkas Menu -->
            <div class="menu-item">
              <a href="berkas">
                <div class="menu-icon">
                  <i class="fas fa-folder-open text-info"></i>
                  <?php
                  $notif_berkas = false;
                  $user_id = $data_user['user_id'] ?? '';
                  if (!empty($user_id)) {
                    $q_berkas = $connection->query("SELECT kk, ijazah FROM berkas WHERE user_id='" . $connection->real_escape_string($user_id) . "' LIMIT 1");
                    if ($q_berkas && $q_berkas->num_rows > 0) {
                      $berkas = $q_berkas->fetch_assoc();
                      if (empty($berkas['kk']) || empty($berkas['ijazah'])) {
                        $notif_berkas = true;
                      }
                    } else {
                      $notif_berkas = true;
                    }
                  }
                  if ($notif_berkas): ?>
                    <span class="notification-badge">!</span>
                  <?php endif; ?>
                </div>
                <div class="menu-text">Berkas</div>
              </a>
            </div>
            <!-- Absensi Menu -->
            <div class="menu-item">
              <a href="absensi">
                <div class="menu-icon">
                  <i class="fas fa-calendar-check text-success"></i>
                </div>
                <div class="menu-text">Absensi</div>
              </a>
            </div>
            <!-- Izin Menu -->
            <div class="menu-item">
              <a href="izin">
                <div class="menu-icon">
                  <i class="fas fa-envelope-open-text text-warning"></i>
                </div>
                <div class="menu-text">Izin</div>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Secondary Menu -->
    <div class="secondary-menu-section">
      <div class="secondary-menu-card">
        <h6 class="secondary-menu-title">Menu Lainnya</h6>
        <div class="secondary-menu-grid">
          <?php
          $notif_edit_identitas = false;
          if (!empty($user_id)) {
            $q_pengajuan = $connection->query("SELECT status_pengajuan FROM perubahan WHERE user_id='" . $connection->real_escape_string($user_id) . "' AND status_pengajuan='Ditolak' LIMIT 1");
            if ($q_pengajuan && $q_pengajuan->num_rows > 0) {
              $notif_edit_identitas = true;
            }
          }
          ?>
          <div class="menu-item">
            <?php if (isset($data_user['konfirmasi']) && $data_user['konfirmasi'] === 'Belum Sesuai'): ?>
              <a href="edit-identitas">
              <?php else: ?>
                <a href="#" class="disabled" tabindex="-1" aria-disabled="true" style="pointer-events:none;opacity:0.5;" onclick="return false;">
                <?php endif; ?>
                <div class="menu-icon">
                  <i class="fas fa-user-edit text-danger"></i>
                  <?php if ($notif_edit_identitas): ?>
                    <span class="notification-badge">!</span>
                  <?php endif; ?>
                </div>
                <div class="menu-text">Edit Data</div>
                </a>
          </div>
          <div class="menu-item">
            <a href="tata-tertib">
              <div class="menu-icon">
                <i class="fas fa-book text-primary"></i>
              </div>
              <div class="menu-text">Tata Tertib</div>
            </a>
          </div>
          <div class="menu-item">
            <a href="poin">
              <div class="menu-icon">
                <i class="fas fa-exclamation-triangle text-warning"></i>
              </div>
              <div class="menu-text">Poin</div>
            </a>
          </div>
          <div class="menu-item">
            <a href="usulan-pip">
              <div class="menu-icon">
                <i class="fas fa-money-bill-wave text-success"></i>
              </div>
              <div class="menu-text">Usulan PIP</div>
            </a>
          </div>
          <div class="menu-item">
            <a href="kelas-q">
              <div class="menu-icon">
                <i class="fas fa-users text-info"></i>
              </div>
              <div class="menu-text">Kelas Q</div>
            </a>
          </div>
          <div class="menu-item">
            <a href="e-izin">
              <div class="menu-icon">
                <i class="fas fa-envelope text-warning"></i>
              </div>
              <div class="menu-text">e-Izin</div>
            </a>
          </div>
          <div class="menu-item">
            <a href="ekpd">
              <div class="menu-icon">
                <i class="fas fa-id-card text-primary"></i>
              </div>
              <div class="menu-text">e-KPD</div>
            </a>
          </div>
          <div class="menu-item">
            <a href="applain">
              <div class="menu-icon">
                <i class="fas fa-th-large" style="color:#FFD93D;"></i>
              </div>
              <div class="menu-text">Lainnya</div>
            </a>
          </div>
        </div>
      </div>
    </div>
    <?php
    // --- Poin Pelanggaran Data ---
    $poin_total = 0; $poin_notif_baru = 0;
    if (isset($connection) && $connection && !empty($data_user['user_id'])) {
        $uid_poin = intval($data_user['user_id']);
        // Get semester aktif
        $sem_aktif_id = 0;
        $qsa = $connection->query("SELECT semester_id FROM poin_semester WHERE is_aktif='Y' LIMIT 1");
        if ($qsa && $qsa->num_rows > 0) $sem_aktif_id = intval($qsa->fetch_assoc()['semester_id']);
        $sem_filter = ($sem_aktif_id > 0) ? " AND semester_id=$sem_aktif_id" : "";
        $qpt = $connection->query("SELECT COALESCE(SUM(poin_diberikan),0) AS total FROM poin_pelanggaran WHERE user_id=$uid_poin AND status='Aktif'" . $sem_filter);
        if ($qpt) $poin_total = intval($qpt->fetch_assoc()['total']);
        $qpn = $connection->query("SELECT COUNT(*) c FROM poin_pelanggaran WHERE user_id=$uid_poin AND notif_dibaca='N' AND status='Aktif'");
        if ($qpn) $poin_notif_baru = intval($qpn->fetch_assoc()['c']);
    }
    $poin_persen = max(0, min(100, intval($poin_total)));

    // --- Ringkasan absensi semester aktif ---
    $semester_start_date = date('Y-01-01');
    $semester_end_date = date('Y-12-31');
    $semester_label = !empty($semester_display) ? $semester_display : 'Aktif';

    $tapel_tahun = '';
    $tapel_semester = '';
    $qtapel = $connection->query("SELECT tahun, semester FROM tahun_pelajaran WHERE aktif='Y' LIMIT 1");
    if ($qtapel && $qtapel->num_rows > 0) {
      $rtapel = $qtapel->fetch_assoc();
      $tapel_tahun = trim((string)($rtapel['tahun'] ?? ''));
      $tapel_semester = trim((string)($rtapel['semester'] ?? ''));
      if ($tapel_semester !== '') {
        $semester_label = $tapel_semester;
      }
    }

    $year_start = intval(date('Y'));
    $year_end = $year_start + 1;
    if (preg_match('/(\d{4})\D+(\d{4})/', $tapel_tahun, $m_tahun)) {
      $year_start = intval($m_tahun[1]);
      $year_end = intval($m_tahun[2]);
    } elseif (preg_match('/(\d{4})/', $tapel_tahun, $m_tahun_single)) {
      $year_start = intval($m_tahun_single[1]);
      $year_end = $year_start + 1;
    }

    $semester_raw = strtolower($tapel_semester);
    $is_genap = ($semester_raw === '2' || strpos($semester_raw, 'genap') !== false || strpos($semester_raw, 'even') !== false);

    if ($is_genap) {
      $semester_start_date = sprintf('%04d-01-01', $year_end);
      $semester_end_date = sprintf('%04d-06-30', $year_end);
    } else {
      $semester_start_date = sprintf('%04d-07-01', $year_start);
      $semester_end_date = sprintf('%04d-12-31', $year_start);
    }

    $hari_aktif_map = [];
    $qjadwal = $connection->query("SELECT hari, status FROM jadwal");
    if ($qjadwal) {
      while ($rjadwal = $qjadwal->fetch_assoc()) {
        $hari_key = trim((string)($rjadwal['hari'] ?? ''));
        $status_key = strtolower(trim((string)($rjadwal['status'] ?? '')));
        if ($hari_key !== '') {
          $hari_aktif_map[$hari_key] = ($status_key === 'y' || $status_key === 'aktif');
        }
      }
    }
    if (empty($hari_aktif_map)) {
      $hari_aktif_map = ['Senin' => true, 'Selasa' => true, 'Rabu' => true, 'Kamis' => true, 'Jumat' => true];
    }

    $hari_libur_semester = [];
    $stmt_hlibur = $connection->prepare("SELECT tanggal_mulai, tanggal_selesai FROM hari_libur WHERE tanggal_mulai <= ? AND tanggal_selesai >= ?");
    if ($stmt_hlibur) {
      $stmt_hlibur->bind_param('ss', $semester_end_date, $semester_start_date);
      $stmt_hlibur->execute();
      $res_hlibur = $stmt_hlibur->get_result();
      while ($rhl = $res_hlibur->fetch_assoc()) {
        $hari_libur_semester[] = $rhl;
      }
      $stmt_hlibur->close();
    }

    $absensi_semester = [];
    if (!empty($data_user['user_id'])) {
      $uid_absensi_sem = intval($data_user['user_id']);
      $stmt_abs_sem = $connection->prepare("SELECT tanggal, status_masuk, kehadiran FROM absensi WHERE user_id = ? AND tanggal BETWEEN ? AND ? ORDER BY tanggal ASC, id ASC");
      if ($stmt_abs_sem) {
        $stmt_abs_sem->bind_param('iss', $uid_absensi_sem, $semester_start_date, $semester_end_date);
        $stmt_abs_sem->execute();
        $res_abs_sem = $stmt_abs_sem->get_result();
        while ($ras = $res_abs_sem->fetch_assoc()) {
          $absensi_semester[$ras['tanggal']] = $ras;
        }
        $stmt_abs_sem->close();
      }
    }

    $hari_efektif_semester = 0;
    $kehadiran_semester = 0;
    $izin_semester = 0;
    $alpha_semester = 0;
    $hari_map_en_id = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'];

    $date_cursor = strtotime($semester_start_date);
    $date_end = strtotime($semester_end_date);
    while ($date_cursor <= $date_end) {
      $tgl = date('Y-m-d', $date_cursor);
      $hari_en = date('l', $date_cursor);
      $hari_id = $hari_map_en_id[$hari_en] ?? $hari_en;

      if (empty($hari_aktif_map[$hari_id])) {
        $date_cursor = strtotime('+1 day', $date_cursor);
        continue;
      }

      $is_holiday = false;
      foreach ($hari_libur_semester as $hl_sem) {
        if ($tgl >= $hl_sem['tanggal_mulai'] && $tgl <= $hl_sem['tanggal_selesai']) {
          $is_holiday = true;
          break;
        }
      }
      if ($is_holiday) {
        $date_cursor = strtotime('+1 day', $date_cursor);
        continue;
      }

      $hari_efektif_semester++;
      if (!empty($absensi_semester[$tgl])) {
        $row_abs = $absensi_semester[$tgl];
        $status_masuk_l = strtolower(trim((string)($row_abs['status_masuk'] ?? '')));
        $kehadiran_l = strtolower(trim((string)($row_abs['kehadiran'] ?? '')));

        if ($kehadiran_l === 'izin' || $kehadiran_l === 'sakit' || $status_masuk_l === 'izin') {
          $izin_semester++;
        } elseif (in_array($status_masuk_l, ['tepat waktu', 'tepatwaktu', 'tepat', 'hadir', 'terlambat'], true) || $kehadiran_l === 'hadir') {
          $kehadiran_semester++;
        } else {
          $alpha_semester++;
        }
      } else {
        $alpha_semester++;
      }

      $date_cursor = strtotime('+1 day', $date_cursor);
    }

    $kehadiran_persen_semester = ($hari_efektif_semester > 0) ? round(($kehadiran_semester / $hari_efektif_semester) * 100, 1) : 0;
    $kehadiran_persen_display = rtrim(rtrim(number_format($kehadiran_persen_semester, 1, '.', ''), '0'), '.');
    if ($kehadiran_persen_display === '') {
      $kehadiran_persen_display = '0';
    }

    // --- Riwayat absensi terbaru (real data) ---
    $riwayat_absensi = [];
    if (!empty($data_user['user_id'])) {
      $uid_riwayat = intval($data_user['user_id']);
      $stmt_riwayat = $connection->prepare("SELECT tanggal, jam_masuk, jam_pulang, kehadiran, status_masuk, status_pulang FROM absensi WHERE user_id = ? ORDER BY tanggal DESC, id DESC LIMIT 5");
      if ($stmt_riwayat) {
        $stmt_riwayat->bind_param('i', $uid_riwayat);
        $stmt_riwayat->execute();
        $res_riwayat = $stmt_riwayat->get_result();
        while ($rri = $res_riwayat->fetch_assoc()) {
          $riwayat_absensi[] = $rri;
        }
        $stmt_riwayat->close();
      }
    }
    ?>
    <!-- Statistics Section -->
    <div class="statistics-section">
      <div class="stats-grid">
        <!-- Attendance Stats -->
        <div class="stats-card">
          <div class="stats-chart">
            <canvas id="chartKehadiran" width="80" height="80" data-persen="<?= htmlspecialchars((string)$kehadiran_persen_semester) ?>"></canvas>
          </div>
          <div class="stats-value"><?= htmlspecialchars($kehadiran_persen_display) ?>%</div>
          <div class="stats-label">Persentase Kehadiran</div>
          <div class="stats-detail">Semester <?= htmlspecialchars($semester_label) ?>: <?= intval($kehadiran_semester) ?> dari <?= intval($hari_efektif_semester) ?> hari</div>
        </div>
        <!-- Point Stats -->
        <div class="stats-card">
          <div class="stats-chart">
            <canvas id="chartPoint" width="80" height="80" data-persen="<?= $poin_persen ?>"></canvas>
          </div>
          <div class="stats-value stats-value-poin <?= $poin_total >= 100 ? 'stats-value-poin-danger' : ($poin_total >= 70 ? 'stats-value-poin-warn' : 'stats-value-poin-safe') ?>"><?= $poin_persen ?>%</div>
          <div class="stats-label">Persentase Point</div>
          <div class="stats-detail"><?= $poin_total ?> dari 100 point maksimal</div>
        </div>
      </div>
    </div>
    <!-- Info Banner -->
    <div class="info-banner-section">
      <div class="info-banner">
        <div class="info-banner-content">
          <div class="info-banner-text">
            <h6><i class="fas fa-info-circle me-2"></i>Informasi Penting</h6>
            <p>Selalu update data dan cek absensi Anda secara berkala untuk kelancaran proses akademik.</p>
          </div>
          <div class="info-banner-action">
            <a href="informasi" class="btn">
              <i class="fas fa-arrow-right me-1"></i>Lihat Semua
            </a>
          </div>
        </div>
      </div>
    </div>
    <!-- Attendance History -->
    <div class="attendance-section">
      <div class="attendance-header">
        <h5><i class="fas fa-history me-2"></i>Riwayat Absensi Terakhir</h5>
        <p>5 data absensi terakhir</p>
      </div>
      <div class="attendance-table">
        <div class="table-responsive">
          <table class="table table-hover align-middle text-center mb-0">
            <thead>
              <tr>
                <th><i class="fas fa-calendar me-1"></i>Tanggal</th>
                <th><i class="fas fa-clock me-1"></i>Hari</th>
                <th><i class="fas fa-sign-in-alt me-1"></i>Masuk</th>
                <th><i class="fas fa-sign-out-alt me-1"></i>Pulang</th>
                <th><i class="fas fa-check-circle me-1"></i>Status</th>
                <th><i class="fas fa-stopwatch me-1"></i>Waktu</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $status_config = [
                'colors' => [
                  'Hadir' => 'success',
                  'Izin' => 'info',
                  'Sakit' => 'warning',
                  'Alpa' => 'danger',
                  'Alpha' => 'danger'
                ],
                'waktu_colors' => [
                  'Tepat Waktu' => 'success',
                  'Telat' => 'danger',
                  'Terlambat' => 'danger',
                  'Pulang Cepat' => 'warning'
                ],
                'icons' => [
                  'Hadir' => 'fa-check',
                  'Izin' => 'fa-file-alt',
                  'Sakit' => 'fa-thermometer-half',
                  'Alpa' => 'fa-times',
                  'Alpha' => 'fa-times'
                ]
              ];
              if (!empty($riwayat_absensi)):
                $hari_nama = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
                foreach ($riwayat_absensi as $absen):
                  $tanggal_raw = (string)($absen['tanggal'] ?? '');
                  $tanggal_fmt = $tanggal_raw !== '' ? date('d/m/Y', strtotime($tanggal_raw)) : '-';
                  $hari_en = $tanggal_raw !== '' ? date('l', strtotime($tanggal_raw)) : '';
                  $hari_fmt = $hari_nama[$hari_en] ?? $hari_en;
                  $masuk = !empty($absen['jam_masuk']) ? $absen['jam_masuk'] : '-';
                  $pulang = !empty($absen['jam_pulang']) ? $absen['jam_pulang'] : '-';
                  $status = trim((string)($absen['kehadiran'] ?? ''));
                  if ($status === '') {
                    $status = trim((string)($absen['status_masuk'] ?? '-'));
                  }
                  $waktu = trim((string)($absen['status_masuk'] ?? ''));
                  if ($waktu === '') {
                    $waktu = trim((string)($absen['status_pulang'] ?? '-'));
                  }

                  $status_color = $status_config['colors'][$status] ?? 'secondary';
                  $waktu_color = $status_config['waktu_colors'][$waktu] ?? 'secondary';
                  $status_icon = $status_config['icons'][$status] ?? 'fa-question';
              ?>
                  <tr>
                    <td class="fw-bold text-primary"><?= htmlspecialchars($tanggal_fmt) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($hari_fmt) ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($masuk) ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($pulang) ?></td>
                    <td>
                      <span class="status-badge bg-<?= htmlspecialchars($status_color) ?> text-white">
                        <i class="fas <?= htmlspecialchars($status_icon) ?> me-1"></i><?= strtoupper(htmlspecialchars($status)) ?>
                      </span>
                    </td>
                    <td>
                      <span class="status-badge bg-<?= htmlspecialchars($waktu_color) ?> text-white">
                        <?= htmlspecialchars($waktu) ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">
                    <i class="fas fa-inbox me-2"></i>Belum ada data absensi.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <!-- Footer Credits -->
    <div class="created-by">
      <img src="../content/smakpalapik.png" alt="SmakpalApik Logo" style="height:32px;width:32px;object-fit:contain;">
      <div class="fw-bold">&copy; Smart Apps Education</div>
      <div class="small">Developed by <a href="https://s.id/smakpalapik" target="_blank">SmakpalApik</a></div>
    </div>
  </div>

<?php
} else {
  echo '<div class="container mt-5">
            <div class="alert alert-warning text-center">
                <h4><i class="fas fa-exclamation-triangle"></i> Akses Ditolak</h4>
                <p>Silakan login untuk mengakses dashboard.</p>
                <a href="../" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login Sekarang
                </a>
            </div>
        </div>';
}
?>

<script>
  // Handle "Jangan Tampilkan Lagi" button
  document.addEventListener('DOMContentLoaded', function() {
    const hidePembaharuanBtn = document.getElementById('hidePembaharuan');
    const modalPembaharuan = document.getElementById('modalPembaharuan');

    if (hidePembaharuanBtn && modalPembaharuan) {
      hidePembaharuanBtn.addEventListener('click', function() {
        const latest = modalPembaharuan.getAttribute('data-latest');
        if (latest) {
          // Set cookie to hide this update version
          const cookieValue = 'v2:' + encodeURIComponent(latest);
          const expiryDate = new Date();
          expiryDate.setFullYear(expiryDate.getFullYear() + 1); // 1 year
          document.cookie = 'hide_pembaharuan=' + encodeURIComponent(cookieValue) + '; expires=' + expiryDate.toUTCString() + '; path=/';
        }

        // Close modal using Bootstrap 5 API
        const modal = bootstrap.Modal.getInstance(modalPembaharuan);
        if (modal) {
          modal.hide();
        }
      });
    }

    // Auto show modal after page load (if not hidden)
    if (modalPembaharuan) {
      setTimeout(function() {
        // Create modal instance
        const modal = new bootstrap.Modal(modalPembaharuan, {
          backdrop: true,
          keyboard: true
        });
        // Show modal
        // Mark handled (avoid duplicate initializers in external scripts)
        try {
          window.__pembaharuan_handled_by_inline = true;
        } catch (err) {}
        modal.show();

        // Defensive: allow clicking the backdrop area (modal root) to close
        modalPembaharuan.addEventListener('click', function(e) {
          if (e.target === modalPembaharuan) {
            try {
              modal.hide();
            } catch (err) {
              /* ignore */
            }
          }
        });

        // Defensive: if modal still leaves a stray backdrop, remove it when hidden
        modalPembaharuan.addEventListener('hidden.bs.modal', function() {
          // remove any leftover backdrop elements
          document.querySelectorAll('.modal-backdrop').forEach(function(el) {
            el.parentNode && el.parentNode.removeChild(el);
          });
        });

        // Also allow ESC key closing fallback (in case keyboard option doesn't work)
        document.addEventListener('keydown', function onEsc(e) {
          if (e.key === 'Escape') {
            try {
              modal.hide();
            } catch (err) {}
            document.removeEventListener('keydown', onEsc);
          }
        });
      }, 1000); // Show after 1 second
    }
  });
</script>