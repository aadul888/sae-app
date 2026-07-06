<?php
/**
 * MODUL: SURAT ARSIP — Placeholder data dummy
 */
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) { header('location:./login'); exit; }
$modul_id = 55; include __DIR__ . '/../check_role.php'; if (!$has_access) { hak_akses(); return; }
$can_edit = (isset($data_role['modifikasi']) && $data_role['modifikasi'] == 'Y');
?>
<div class="header bg-primary pb-4 user-page-header-compact"><div class="container-fluid"><div class="header-body"><div class="row align-items-center py-3"></div></div></div></div>
<div class="container-fluid mt--6 user-module-page surat-arsip-page">
  <div class="card user-table-panel module-table-card pb-2">
    <div class="card-header py-3 px-3 user-table-header module-table-header">
      <div class="user-table-head-row module-header-row" style="gap:10px;">
        <div><h4 class="mb-1">Arsip Surat</h4><small class="text-muted">Dokumentasi arsip surat.</small></div>
        <div class="user-toolbar-actions module-header-actions">
          <a href="./surat" class="btn-mod btn-mod-secondary" title="Kembali"><i class="fas fa-arrow-left"></i></a>
        </div>
      </div>
    </div>
    <div class="card-body"><p class="text-muted text-center py-4"><i class="fas fa-archive mr-2"></i>Halaman arsip surat akan diintegrasikan dengan database pada tahap berikutnya.</p></div>
  </div>
</div>
