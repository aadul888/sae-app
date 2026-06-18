<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
  $modul_id = 13;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

        switch (@$_GET['op']) {
            default:
                // Query untuk mengambil data hari libur
                $query_libur = "SELECT * FROM hari_libur";
                $result_libur = $connection->query($query_libur);

                echo '  
                <!-- Header -->
                <div class="header bg-primary pb-4 user-page-header-compact">
                    <div class="container-fluid">
                        <div class="header-body">
                            <div class="row align-items-center py-3"></div>
                        </div>
                    </div>
                </div>

                <!-- Page content -->
                <div class="container-fluid mt--6 user-module-page">
                    <!-- Table -->
                    <div class="row">
                        <div class="col">
                            <div class="card user-table-panel module-table-card pb-2">
                                <!-- Card header -->
                                <div class="card-header py-3 px-3 user-table-header module-table-header">
                                  <div class="module-header-row" style="gap:10px;">
                                    <div>
                                      <h4 class="mb-1">Daftar Hari Libur</h4>
                                      <small class="text-muted">Kelola kalender hari libur nasional dan sekolah.</small>
                                    </div>
                                    <div class="module-header-actions">
                                      <button class="btn-mod btn-mod-add btn-add" title="Tambah"><i class="fas fa-plus"></i></button>
                                    </div>
                                  </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-items-center table-flush table-striped datatable" style="width:100%">
                                        <thead class="thead-light">
                                            <tr>
                                                <th class="text-center" width="5">No</th>
                                                <th class="text-center" width="15">Tanggal Mulai</th>
                                                <th class="text-center" width="15">Tanggal Selesai</th>
                                                <th class="text-center" width="50">Keterangan</th>
                                                <th class="text-center" width="10">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>';
                if ($result_libur->num_rows > 0) {
                    $no = 1;
                    while ($row = $result_libur->fetch_assoc()) {
                        echo '
                                                <tr>
                                                    <td class="text-center">' . $no++ . '</td>
                                                    <td class="text-center">' . $row['tanggal_mulai'] . '</td>
                                                    <td class="text-center">' . $row['tanggal_selesai'] . '</td>
                                                    <td class="text-center">' . $row['keterangan'] . '</td>
                                                    <td class="text-center">
                                                        <a href="proses.php?op=delete&id=' . $row['id'] . '" class="btn btn-sm btn-danger">Hapus</a>
                                                    </td>
                                                </tr>';
                    }
                } else {
                    echo '
                                            <tr>
                                                <td colspan="5" class="text-center">Tidak ada data hari libur.</td>
                                            </tr>';
                }
                echo '
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Tambah Hari Libur -->
                <div class="modal fade modal-add" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Tambah Hari Libur</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form class="form-add">
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="tanggal_mulai">Tanggal Mulai</label>
                                        <input type="date" class="form-control tanggal_mulai" name="tanggal_mulai" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="tanggal_selesai">Tanggal Selesai</label>
                                        <input type="date" class="form-control tanggal_selesai" name="tanggal_selesai" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="keterangan">Keterangan</label>
                                        <input type="text" class="form-control keterangan" name="keterangan" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary btn-save"><i class="far fa-save"></i> Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Edit Hari Libur -->
                <div class="modal fade modal-edit" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Hari Libur</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form class="form-edit">
                                <input type="hidden" class="id" name="id">
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="edit-tanggal_mulai">Tanggal Mulai</label>
                                        <input type="date" class="form-control edit-tanggal_mulai" name="tanggal_mulai" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit-tanggal_selesai">Tanggal Selesai</label>
                                        <input type="date" class="form-control edit-tanggal_selesai" name="tanggal_selesai" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit-keterangan">Keterangan</label>
                                        <input type="text" class="form-control edit-keterangan" name="keterangan" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary btn-save-edit"><i class="far fa-save"></i> Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>';
                break;
        }
    } else {
        theme_404();
    }
}
