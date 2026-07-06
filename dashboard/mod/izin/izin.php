<?php if (empty($connection)) {
  echo 'Koneksi tidak ditemukan';
  header('location:../');
  exit();
} else {
  if (isset($_COOKIE['siswa'])) {
    $user_id = $data_user['user_id'] ?? '';

    // GANTI: jangan proses POST di sini lagi; terima pesan dari proses.php via query string
    $message = '';
    if (!empty($_GET['msg']) && $_GET['msg'] === 'success') {
      $message = '<div class="alert alert-success">Permohonan izin berhasil dikirim. Status: Menunggu.</div>';
    } elseif (!empty($_GET['error'])) {
      $message = '<div class="alert alert-danger">' . htmlspecialchars($_GET['error']) . '</div>';
    }

    // Ambil daftar izin user (terbaru 20)
    $list_izin_html = '';
    if (!empty($user_id)) {
      $stmt2 = $connection->prepare("SELECT id, jenis_izin, tanggal_mulai, tanggal_selesai, keterangan, status_izin, date_submitted FROM izin WHERE user_id = ? ORDER BY date_submitted DESC LIMIT 20");
      if ($stmt2) {
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();
        $res = $stmt2->get_result();
        if ($res && $res->num_rows > 0) {
          $list_izin_html .= '<div class="card mt-4"><div class="card-header bg-gradient-primary"><h4 class="mb-0 text-white"><i class="fas fa-history"></i> Riwayat Pengajuan Izin</h4></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover table-bordered mb-0"><thead class="thead-light"><tr class="text-center"><th style="width: 50px;">#</th><th>Jenis Izin</th><th style="width: 130px;">Tanggal Mulai</th><th style="width: 130px;">Tanggal Selesai</th><th style="width: 120px;">Status</th><th style="width: 130px;">Diajukan</th></tr></thead><tbody>';
          $i = 1;
          while ($row = $res->fetch_assoc()) {
            // Format tanggal lebih readable
            $tgl_mulai = date('d M Y', strtotime($row['tanggal_mulai']));
            $tgl_selesai = date('d M Y', strtotime($row['tanggal_selesai']));
            $tgl_submitted = date('d M Y H:i', strtotime($row['date_submitted']));
            
            // Badge status dengan warna
            $status = strtolower($row['status_izin']);
            $status_badge = '';
            if ($status === 'menunggu' || $status === 'pending') {
              $status_badge = '<span class="badge badge-warning"><i class="fas fa-clock"></i> Menunggu</span>';
            } elseif ($status === 'disetujui' || $status === 'approved') {
              $status_badge = '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Disetujui</span>';
            } elseif ($status === 'ditolak' || $status === 'rejected') {
              $status_badge = '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Ditolak</span>';
            } else {
              $status_badge = '<span class="badge badge-secondary">' . htmlspecialchars($row['status_izin']) . '</span>';
            }
            
            $list_izin_html .= '<tr><td class="text-center font-weight-bold">' . $i++ . '</td><td>' . htmlspecialchars($row['jenis_izin']) . '</td><td class="text-center">' . $tgl_mulai . '</td><td class="text-center">' . $tgl_selesai . '</td><td class="text-center">' . $status_badge . '</td><td class="text-center"><small>' . $tgl_submitted . '</small></td></tr>';
          }
          $list_izin_html .= '</tbody></table></div></div></div>';
        } else {
          $list_izin_html = '<div class="card mt-4"><div class="card-body text-center text-muted py-5"><i class="fas fa-inbox fa-3x mb-3 d-block"></i><h5>Belum Ada Riwayat Izin</h5><p>Anda belum pernah mengajukan permohonan izin.</p></div></div>';
        }
        $stmt2->close();
      }
    }

    // --- NEW: Cek apakah ada permohonan yang masih 'Menunggu' ---
    $has_pending = false;
    $pending_html = '';
    if (!empty($user_id)) {
      $pstmt = $connection->prepare("SELECT id, jenis_izin, tanggal_mulai, tanggal_selesai, date_submitted FROM izin WHERE user_id = ? AND status_izin = 'Menunggu' ORDER BY date_submitted DESC LIMIT 1");
      if ($pstmt) {
        $pstmt->bind_param("i", $user_id);
        $pstmt->execute();
        $pres = $pstmt->get_result();
        if ($pres && $pres->num_rows > 0) {
          $prow = $pres->fetch_assoc();
          $has_pending = true;
          
          $tgl_mulai_fmt = date('d M Y', strtotime($prow['tanggal_mulai']));
          $tgl_selesai_fmt = date('d M Y', strtotime($prow['tanggal_selesai']));
          $tgl_submitted_fmt = date('d M Y H:i', strtotime($prow['date_submitted']));
          
          $pending_html = '
          <div class="alert alert-warning border-left-warning shadow-sm">
            <div class="d-flex align-items-center">
              <i class="fas fa-exclamation-triangle fa-2x mr-3"></i>
              <div>
                <h5 class="alert-heading mb-2"><i class="fas fa-clock"></i> Permohonan Sedang Diproses</h5>
                <p class="mb-2">Anda memiliki permohonan izin yang masih menunggu persetujuan:</p>
                <ul class="mb-2 pl-4">
                  <li><strong>Jenis:</strong> ' . htmlspecialchars($prow['jenis_izin']) . '</li>
                  <li><strong>Periode:</strong> ' . $tgl_mulai_fmt . ' s/d ' . $tgl_selesai_fmt . '</li>
                  <li><strong>Diajukan:</strong> ' . $tgl_submitted_fmt . '</li>
                </ul>
                <p class="mb-0"><i class="fas fa-info-circle"></i> Silakan tunggu proses validasi dari admin/guru sebelum mengajukan izin baru.</p>
              </div>
            </div>
          </div>';
        }
        $pstmt->close();
      }
    }

    // Siapkan atribut disabled untuk elemen form jika ada pending
    $form_disabled_attr = $has_pending ? ' disabled' : '';
    $submit_button_html = $has_pending
      ? '<button type="button" class="btn btn-secondary btn-block" disabled><i class="fas fa-hourglass-half"></i> Menunggu Persetujuan</button>'
      : '<button type="submit" class="btn btn-primary btn-block"><i class="fas fa-paper-plane"></i> Ajukan Izin</button>';

    // Tambahkan CSS khusus untuk styling yang lebih baik
    $extra_css = '<style>
      /* Pastikan ada ruang bawah agar konten tidak tertutup footer/navigation yang fixed */
      .izin-page-container { padding-bottom: 140px; }
      @media (min-width: 768px) { .izin-page-container { padding-bottom: 80px; } }
      
      /* Card styling */
      .izin-card { 
        position: relative; 
        z-index: 3; 
        box-shadow: 0 0 2rem 0 rgba(136,152,170,.15);
        border: none;
        border-radius: 0.375rem;
      }
      
      /* Form styling */
      .form-control:focus {
        border-color: #14b8a6;
        box-shadow: 0 0 0 0.2rem rgba(15, 118, 110, 0.25);
      }
      
      /* Alert styling */
      .alert {
        border-radius: 0.375rem;
        border: none;
      }
      .alert-warning {
        background-color: #fff3cd;
        color: #856404;
      }
      .alert-success {
        background-color: #d4edda;
        color: #155724;
      }
      .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
      }
      
      /* Table responsive */
      .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }
      
      /* Badge styling */
      .badge {
        font-weight: 500;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
      }
      .badge i {
        margin-right: 0.25rem;
      }
      
      /* Table hover effect */
      .table-hover tbody tr:hover {
        background-color: rgba(0,0,0,.02);
        cursor: default;
      }
      
      /* Empty state */
      .fa-inbox {
        color: #cbd5e0;
      }
      
      /* Button styling */
      .btn-block {
        padding: 0.75rem 1rem;
        font-weight: 600;
      }
      
      /* Mobile responsive */
      @media (max-width: 767.98px) {
        .table {
          font-size: 0.875rem;
        }
        .table th, .table td {
          padding: 0.5rem 0.3rem;
        }
        .badge {
          font-size: 0.75rem;
          padding: 0.35rem 0.5rem;
        }
      }
    </style>';

    // Tampilkan form input izin (ubah action ke proses.php?action=add_izin)
    // Gunakan $extra_css dan tambahkan class izin-page-container pada container serta izin-card pada card.
    echo $extra_css . '
<!-- Header -->
<div class="header bg-primary pb-6">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-4">
        <div class="col-lg-6 col-7">
          <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
            <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
              <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
              <li class="breadcrumb-item active" aria-current="page">Pengajuan Izin</li>
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
          <h3 class="mb-0">Form Pengajuan Izin</h3>
        </div>
        <div class="card-body">
          ' . ($message ?? '') . '
          ' . $pending_html . '
          
          <div class="bg-light p-4 rounded mb-4">
            <h5 class="mb-3"><i class="fas fa-info-circle text-primary"></i> Panduan Pengajuan Izin</h5>
            <ul class="mb-0 pl-4">
              <li>Isi jenis izin dengan jelas (contoh: Sakit, Keperluan Keluarga, dll)</li>
              <li>Pilih tanggal mulai dan selesai izin</li>
              <li>Jika izin hanya 1 hari, kosongkan tanggal selesai</li>
              <li>Tambahkan keterangan jika diperlukan</li>
              <li>Tunggu persetujuan dari admin/guru</li>
            </ul>
          </div>
          
          <form method="post" action="mod/izin/proses.php?action=add_izin">
            <div class="form-group">
              <label for="jenis_izin" class="font-weight-bold">
                <i class="fas fa-tag text-primary"></i> Jenis Izin <span class="text-danger">*</span>
              </label>
              <input type="text" id="jenis_izin" name="jenis_izin" class="form-control form-control-lg" placeholder="Contoh: Sakit, Keperluan Keluarga, Acara Keluarga" required' . $form_disabled_attr . ' maxlength="100">
              <small class="form-text text-muted">Masukkan jenis/alasan izin Anda</small>
            </div>
            
            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="tanggal_mulai" class="font-weight-bold">
                  <i class="fas fa-calendar-day text-success"></i> Tanggal Mulai <span class="text-danger">*</span>
                </label>
                <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="form-control form-control-lg" required' . $form_disabled_attr . ' min="' . date('Y-m-d') . '">
              </div>
              <div class="form-group col-md-6">
                <label for="tanggal_selesai" class="font-weight-bold">
                  <i class="fas fa-calendar-check text-info"></i> Tanggal Selesai
                </label>
                <input type="date" id="tanggal_selesai" name="tanggal_selesai" class="form-control form-control-lg"' . $form_disabled_attr . ' min="' . date('Y-m-d') . '">
                <small class="form-text text-muted"><i class="fas fa-lightbulb"></i> Kosongkan jika izin hanya 1 hari</small>
              </div>
            </div>
            
            <div class="form-group">
              <label for="keterangan" class="font-weight-bold">
                <i class="fas fa-comment-alt text-warning"></i> Keterangan / Catatan (Opsional)
              </label>
              <textarea id="keterangan" name="keterangan" class="form-control" rows="4" placeholder="Tambahkan keterangan atau alasan lebih detail jika diperlukan..."' . $form_disabled_attr . ' maxlength="500"></textarea>
              <small class="form-text text-muted">Maksimal 500 karakter</small>
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
  }
}
