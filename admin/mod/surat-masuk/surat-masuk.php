<?php
/**
 * MODUL: SURAT MASUK — Placeholder data dummy
 */
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) { header('location:./login'); exit; }
$modul_id = 53; include __DIR__ . '/../check_role.php'; if (!$has_access) { hak_akses(); return; }
$can_edit = (isset($data_role['modifikasi']) && $data_role['modifikasi'] == 'Y');
$can_del  = (isset($data_role['hapus']) && $data_role['hapus'] == 'Y');
?>
<div class="header bg-primary pb-4 user-page-header-compact"><div class="container-fluid"><div class="header-body"><div class="row align-items-center py-3"></div></div></div></div>
<div class="container-fluid mt--6 user-module-page surat-masuk-page">
  <div class="card user-table-panel module-table-card pb-2">
    <div class="card-header py-3 px-3 user-table-header module-table-header">
      <div class="user-table-head-row module-header-row" style="gap:10px;">
        <div><h4 class="mb-1">Surat Masuk</h4><small class="text-muted">Daftar surat masuk.</small></div>
        <div class="user-toolbar-actions module-header-actions">
          <a href="./surat" class="btn-mod btn-mod-secondary" title="Kembali"><i class="fas fa-arrow-left"></i></a>
          <?php if ($can_edit): ?><button class="btn-mod btn-mod-add" title="Tambah"><i class="fas fa-plus"></i></button><?php endif; ?>
        </div>
      </div>
    </div>
    <div class="card-body"><p class="text-muted text-center py-4"><i class="fas fa-envelope-open-text mr-2"></i>Halaman surat masuk akan diintegrasikan dengan database pada tahap berikutnya.</p></div>
  </div>
</div>
