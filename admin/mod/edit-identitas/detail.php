<?php
// Detail perubahan data - dipanggil via AJAX dari proses.php case 'detail'
// File ini tidak standalone, tapi bagian dari response AJAX

// Pastikan variabel yang dibutuhkan tersedia dari proses.php:
// $data, $user, $keterangan, $connection, dll.

function formatFieldValue($value)
{
    // Normalize arrays or objects to a short JSON preview
    if (is_array($value) || is_object($value)) {
        $s = json_encode($value, JSON_UNESCAPED_UNICODE);
        if ($s === false) $s = print_r($value, true);
        if (strlen($s) > 200) $s = substr($s, 0, 200) . '...';
        return '<code>' . htmlspecialchars($s) . '</code>';
    }

    $v = trim((string)$value);
    if ($v === '' || $v === '0000-00-00' || $v === '0000-00-00 00:00:00') {
        return '<span class="no-change text-muted"><em>Belum diisi</em></span>';
    }
    return htmlspecialchars($v);
}

function compareFieldValues($oldValue, $newValue, $fieldName)
{
    // Normalize values to string-safe content before comparing/formatting
    $oldNorm = is_array($oldValue) || is_object($oldValue) ? json_encode($oldValue, JSON_UNESCAPED_UNICODE) : (string)$oldValue;
    $newNorm = is_array($newValue) || is_object($newValue) ? json_encode($newValue, JSON_UNESCAPED_UNICODE) : (string)$newValue;
    $oldFormatted = formatFieldValue($oldValue);
    $newFormatted = formatFieldValue($newValue);

    if (trim($oldNorm) !== trim($newNorm)) {
        return '<div class="d-flex align-items-center">
                  <span class="new-value changed flex-grow-1" title="' . htmlspecialchars($newValue) . '">' . $newFormatted . '</span>
                  <button type="button" class="btn btn-sm btn-outline-primary ml-2 btn-copy-data" 
                          data-field="' . htmlspecialchars($fieldName) . '" 
                                                    data-value="' . htmlspecialchars($newNorm) . '" 
                                                    title="Copy: ' . htmlspecialchars($newNorm) . '">
                    <i class="fas fa-copy"></i>
                  </button>
                </div>';
    }

    // Field tidak berubah
    $hasValue = !empty($oldValue) && $oldValue !== '0000-00-00' && $oldValue !== '0000-00-00 00:00:00';

    if ($hasValue) {
        return '<div class="d-flex align-items-center">
                  <span class="new-value no-change flex-grow-1" title="Tidak ada perubahan">' . $newFormatted . '</span>
                  <div class="ml-2 d-flex align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-copy-data mr-1" 
                            data-field="' . htmlspecialchars($fieldName) . '" 
                            data-value="' . htmlspecialchars($oldValue) . '" 
                            title="Copy: ' . htmlspecialchars($oldValue) . '">
                      <i class="fas fa-copy"></i>
                    </button>
                    <small class="text-muted"><i class="fas fa-check-circle"></i> Tidak berubah</small>
                  </div>
                </div>';
    } else {
        return '<div class="d-flex align-items-center">
                  <span class="new-value no-change flex-grow-1" title="Belum diisi">' . $newFormatted . '</span>
                  <small class="text-muted ml-2"><i class="fas fa-minus-circle"></i> Belum diisi</small>
                </div>';
    }
}

// Resolve keterangan entry into ['old'=>..., 'new'=>...]
function resolveKeteranganPair($keterangan, $fieldKey, $userValue = '')
{
    $old = '';
    $new = '';
    // If keterangan itself is a JSON string, attempt to decode
    if (!is_array($keterangan) && is_string($keterangan)) {
        $maybe = json_decode($keterangan, true);
        if (is_array($maybe)) $keterangan = $maybe;
    }

    if (is_array($keterangan) && array_key_exists($fieldKey, $keterangan)) {
        $entry = $keterangan[$fieldKey];
        // If entry is a JSON-encoded string (double-encoded), try decode
        if (is_string($entry)) {
            $try = json_decode($entry, true);
            if (is_array($try)) $entry = $try;
        }

        if (is_array($entry)) {
            $old = isset($entry['old']) ? $entry['old'] : '';
            $new = isset($entry['new']) ? $entry['new'] : '';
        } else {
            // legacy scalar -> treat as new value
            $old = $userValue;
            $new = $entry;
        }
    } else {
        $old = $userValue;
        $new = $userValue;
    }
    return ['old' => $old, 'new' => $new];
}

// Data fields mapping

// Sticky area: status badge + info header (placed above the form/tables)
// Render a compact flex row with Status | Siswa (name + NISN) | Tanggal Pengajuan
{
    $display_status = isset($display_status_pengajuan) && $display_status_pengajuan !== ''
        ? $display_status_pengajuan
        : $data['status_pengajuan'];

    $statusClass = '';
    switch ($display_status) {
        case 'Disetujui':
            $statusClass = 'success';
            break;
        case 'Ditolak':
            $statusClass = 'danger';
            break;
        case 'Dalam Proses':
            $statusClass = 'primary';
            break;
        default:
            $statusClass = 'warning';
            break;
    }

    echo '<div class="detail-sticky mb-3" style="position:sticky;top:0;z-index:1050;">
        <div class="sticky-inner" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem;background:#fff;border-radius:6px;box-shadow:0 6px 18px rgba(15,23,42,0.06);">
            <div style="display:flex;align-items:center;gap:1rem;flex:1;min-width:0;">
                <span class="badge badge-' . $statusClass . ' badge-lg px-4 py-2" style="font-size:1rem;white-space:nowrap;">' . htmlspecialchars($display_status) . '</span>
                <div style="min-width:0;">
                    <div class="text-muted" style="font-size:0.85rem;">Siswa</div>
                    <div style="font-weight:600;display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;min-width:0;">
                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:inline-block;max-width:40ch;">' . htmlspecialchars($user['nama_lengkap']) . '</span>
                        <small class="text-muted" style="white-space:nowrap;">NISN: <span id="header-nisn">' . htmlspecialchars($user['nisn']) . '</span></small>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-copy-data" data-value="' . htmlspecialchars($user['nisn']) . '" data-field="NISN" title="Copy NISN">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div style="min-width:160px;text-align:right;white-space:nowrap;flex-shrink:0;">
                <div class="text-muted" style="font-size:0.85rem;">Tanggal Pengajuan</div>
                <div style="font-weight:600;">' . date('d M Y', strtotime($data['date_submitted'])) . '</div>
                <small class="text-muted">' . date('H:i', strtotime($data['date_submitted'])) . ' WIB</small>
            </div>
        </div>
    </div>';
}

// Data Comparison Tables - render only fields that have proposed changes
foreach ($fieldGroups as $groupName => $fields) {
    $rows_html = '';
    foreach ($fields as $label => $fieldKey) {
        $userValue = isset($user[$fieldKey]) ? $user[$fieldKey] : '';
        $pair = resolveKeteranganPair($keterangan, $fieldKey, $userValue);
        $oldValue = $pair['old'];
        $newValue = $pair['new'];

        // Normalize values for reliable comparison
        $oldNorm = is_array($oldValue) || is_object($oldValue) ? json_encode($oldValue, JSON_UNESCAPED_UNICODE) : (string)$oldValue;
        $newNorm = is_array($newValue) || is_object($newValue) ? json_encode($newValue, JSON_UNESCAPED_UNICODE) : (string)$newValue;

        // Skip fields with no change (also covers absent keys in keterangan)
        if (trim($oldNorm) === trim($newNorm)) {
            continue;
        }

        // Build row for changed field
        $rows_html .= '<tr>' .
            '<td class="field-label">' . $label . '</td>' .
            '<td class="old-value">' . formatFieldValue($oldValue) . '</td>' .
            '<td>' . compareFieldValues($oldValue, $newValue, $fieldKey) . '</td>' .
            '</tr>';
    }

    // Only render section if there is at least one changed field
    if ($rows_html !== '') {
        echo '<div class="comparison-table mb-4">
                <h6 class="section-title p-3 mb-0 bg-primary text-white">
                    <i class="fas fa-edit mr-2"></i>' . $groupName . '
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th width="30%">Field</th>
                                <th width="35%">Nilai Saat Ini</th>
                                <th>Usulan Perubahan</th>
                            </tr>
                        </thead>
                        <tbody>' . $rows_html . '</tbody>
                    </table>
                </div>
            </div>';
    }
}
// (Sticky area already rendered above)

// Data Comparison Tables
// (Duplicate conditional rendering removed) The template renders full-field comparison tables above.

// Berkas Section
echo '<div class="berkas-section">
    <h5 class="berkas-section-title">
        <i class="fas fa-folder-open"></i>
        Berkas Siswa
    </h5>';

$berkas_query = $connection->query("SELECT * FROM berkas WHERE user_id='" . $data['user_id'] . "'");
if ($berkas_query && $berkas_query->num_rows > 0) {
    $berkas_data = $berkas_query->fetch_assoc();
    if (function_exists('sae_evaluate_berkas_validation')) {
        $berkas_eval = sae_evaluate_berkas_validation($berkas_data);
    } else {
        $berkas_eval = [
            'has_per_item' => false,
            'uploaded_count' => 0,
            'overall' => strtolower(trim((string)($berkas_data['validasi_berkas'] ?? 'belum'))),
            'items' => []
        ];
    }

    $validasi_status = $berkas_eval['overall'] ?? 'belum';
    $status_badge = '';
    $status_icon = '';

    switch ($validasi_status) {
        case 'valid':
            $status_badge = '<span class="badge badge-success">Valid</span>';
            $status_icon = '<i class="fas fa-check-circle text-success"></i>';
            break;
        default:
            $status_badge = '<span class="badge badge-danger">Tidak Valid</span>';
            $status_icon = '<i class="fas fa-exclamation-triangle text-danger"></i>';
            break;
    }

    echo '<div class="mb-3 p-3 bg-light rounded">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">' . $status_icon . ' Status Validasi Berkas</h6>
            ' . $status_badge . '
        </div>
        <small class="text-muted d-block mt-2">Mode validasi: ' . (($berkas_eval['has_per_item'] ?? false) ? 'Per item dokumen' : 'Global (legacy)') . '</small>
    </div>';

    $berkas_fields = [
        'Kartu Keluarga' => 'kk',
        'Akta Kelahiran' => 'akte',
        'Ijazah/SKHUN' => 'ijazah',
        'KIP (Kartu Indonesia Pintar)' => 'kip',
        'KKS (Kartu Keluarga Sejahtera)' => 'kks',
        'KIS (Kartu Indonesia Sehat)' => 'kis'
    ];

    echo '<div class="berkas-grid">';

    foreach ($berkas_fields as $label => $field) {
        $filename = $berkas_data[$field] ?? '';
        $item_eval = $berkas_eval['items'][$field] ?? null;
        $item_status = $item_eval['status'] ?? 'belum';
        $item_note = trim((string)($item_eval['keterangan'] ?? ''));
        $status = '';
        $statusClass = '';

        if (empty($filename)) {
            $status = 'Tidak Valid';
            $statusClass = 'status-invalid';
        } else {
            // Check if file exists using relative path like berkas module
            $filepath = '../../../content/berkas/' . $filename;

            if (file_exists($filepath) && $item_status === 'valid') {
                $status = 'Valid';
                $statusClass = 'status-valid';
            } else {
                $status = 'Tidak Valid';
                $statusClass = 'status-invalid';
            }
        }
        echo '<div class="berkas-item">
            <div class="berkas-item-header">
                <span class="berkas-label">' . $label . '</span>
                <span class="berkas-status ' . $statusClass . '">' . $status . '</span>
            </div>';

        if (!empty($filename)) {
            // Extract timestamp from filename if exists (format: userid_type_timestamp.ext)
            $upload_info = '';
            if (preg_match('/(\d+)_([^_]+)_(\d{14})\./', $filename, $matches)) {
                $timestamp = $matches[3];
                $upload_date = DateTime::createFromFormat('YmdHis', $timestamp);
                if ($upload_date) {
                    $upload_info = '<small class="text-muted d-block">Upload: ' . $upload_date->format('d/m/Y H:i') . '</small>';
                }
            }
            echo $upload_info;
            if ($item_note !== '') {
                echo '<small class="text-muted d-block">Catatan validasi: ' . htmlspecialchars($item_note) . '</small>';
            }
        }

        // Only show file preview when status is truly valid.
        if (!empty($filename) && isset($filepath) && file_exists($filepath) && $item_status === 'valid') {
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            // For URL in browser, use path relative to webroot
            $berkas_url = '../content/berkas/' . $filename;
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                echo '<div class="image-container" onclick="openZoomModal(\'' . htmlspecialchars($berkas_url) . '\')">
                    <img src="' . htmlspecialchars($berkas_url) . '" alt="' . $label . '" loading="lazy">
                    <div class="zoom-overlay">
                        <i class="fas fa-search-plus"></i>
                    </div>
                </div>';
            } elseif ($extension === 'pdf') {
                echo '<div class="pdf-container" onclick="window.open(\'' . htmlspecialchars($berkas_url) . '\', \'_blank\')">
                    <i class="fas fa-file-pdf"></i>
                    <div>Klik untuk membuka PDF</div>
                </div>';
            } else {
                echo '<div class="pdf-container" onclick="window.open(\'' . htmlspecialchars($berkas_url) . '\', \'_blank\')">
                    <i class="fas fa-file"></i>
                    <div>Klik untuk download</div>
                </div>';
            }
        } else {
            echo '<div class="image-container" style="background: #f1f5f9; color: #64748b;">
                <i class="fas fa-lock" style="font-size: 2rem; opacity: 0.55;"></i>
                <div class="mt-2" style="font-size:0.85rem; text-align:center;">Dokumen ditampilkan setelah status valid</div>
            </div>';
        }

        echo '</div>';
    }

    echo '</div>';
} else {
    echo '<div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        Belum ada berkas yang diupload
    </div>';
}

// Close the scrollable wrapper
echo '</div>'; // .detail-wrapper

echo '</div>'; // .detail-content

// Action Buttons (only show if status allows)
if (in_array($data['status_pengajuan'], ['Berhasil Dikirim', 'Dalam Proses'])) {
    echo '<div class="action-buttons">
        <div class="mb-3">
            <h6 class="text-muted mb-2">Tindakan yang Tersedia:</h6>
            <div class="d-flex flex-wrap justify-content-center">
                <button type="button" class="btn btn-approve" id="btn-setujui" data-id="' . $data['id'] . '">
                    <i class="fas fa-check mr-2"></i>Setujui Perubahan
                </button>
                <button type="button" class="btn btn-reject" onclick="showRejectForm()">
                    <i class="fas fa-times mr-2"></i>Tolak Perubahan
                </button>
            </div>
        </div>
        
        <div id="reject-form" class="rejection-note" style="display: none;">
            <h6 class="mb-3"><i class="fas fa-exclamation-triangle mr-2"></i>Alasan Penolakan:</h6>
            <textarea class="form-control" id="catatan-penolakan" placeholder="Masukkan alasan penolakan secara detail..." required></textarea>
            <div class="mt-3 text-right">
                <button type="button" class="btn btn-sm btn-secondary mr-2" onclick="hideRejectForm()">
                    <i class="fas fa-arrow-left mr-1"></i>Batal
                </button>
                <button type="button" class="btn btn-sm btn-danger" id="btn-tolak" data-id="' . $data['id'] . '">
                    <i class="fas fa-paper-plane mr-1"></i>Kirim Penolakan
                </button>
            </div>
        </div>
    </div>';
} else {
    // Show status info for processed items
    echo '<div class="action-buttons">
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle mr-2"></i>
            Pengajuan ini sudah diproses dengan status: <strong>' . htmlspecialchars($data['status_pengajuan']) . '</strong>
        </div>';

    if (!empty($data['date_processed'])) {
        echo '<div class="text-center text-muted">
            <small>Diproses pada: ' . date('d M Y, H:i', strtotime($data['date_processed'])) . ' WIB</small>';
        if (!empty($data['processed_by'])) {
            echo '<br><small>Oleh: ' . htmlspecialchars($data['processed_by']) . '</small>';
        }
        echo '</div>';
    }

    echo '</div>';
}

echo '</div>';

// Add JavaScript variables for proper path handling
echo '<script>
window.BERKAS_PATH = "../content/berkas/";
</script>';

// Zoom Modal (will be added to body)
echo '<div id="zoom-modal" class="zoom-modal">
    <div class="zoom-modal-content">
        <button class="zoom-modal-close" onclick="closeZoomModal()">
            <i class="fas fa-times"></i>
        </button>
        <img id="zoom-image" src="" alt="Zoomed Image">
        <div class="zoom-modal-controls">
            <button class="zoom-control-btn" onclick="zoomIn()">
                <i class="fas fa-plus"></i>
            </button>
            <button class="zoom-control-btn" onclick="zoomOut()">
                <i class="fas fa-minus"></i>
            </button>
            <button class="zoom-control-btn" onclick="resetZoom()">
                <i class="fas fa-expand-arrows-alt"></i>
            </button>
        </div>
    </div>
</div>';
