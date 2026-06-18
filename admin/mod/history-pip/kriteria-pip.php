<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}else{
  $modul_id = 32;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

switch(@$_GET['op']){ 
default:
echo'
<!-- Header -->
<div class="header bg-primary pb-6">
      <div class="container-fluid">
        <div class="header-body">
          <div class="row align-items-center py-4">
            <div class="col-lg-6 col-7">
              <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
                <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                  <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Kriteria PIP</li>
                </ol>
              </nav>
            </div>
            
          </div>
        </div>
      </div>
    </div>
<!-- Page content -->
<div class="container-fluid mt--6">
  <!-- Table -->
  <div class="row">
    <div class="col">
      <div class="card pb-3">
        <!-- Card header -->
        <div class="card-header mb-2">
          <h3 class="mt-2 mb-0 text-left float-left">Kriteria PIP</h3>
          <div class="float-right">
            <button class="btn-mod btn-mod-add btn-add" title="Tambah"><i class="fas fa-plus"></i></button>
          </div>
        </div>
        
  <!-- Custom CSS untuk modal -->
  <link rel="stylesheet" href="./mod/kriteria-pip/style.css">
          
            <div class="table-responsive" style="overflow-x:auto;width:100%">';
            if($data_role['lihat']=='Y'){
              echo'
              <table class="table align-items-center table-flush table-striped datatable">
                <thead class="thead-light">
                  <tr>
                    <th class="text-center" width="4">No</th>
                    <th>Nama Kriteria</th>
                    <th>Deskripsi</th>
                    <th width="8">Poin</th>
                    <th width="6">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>';
            }else{
              hak_akses();
            }
            echo'
            </div>
          </div>
        </div>
      </div>';      
  break;
  }
  }else{
    theme_404();
  }
}?>

<!-- Modal Add/Edit Kriteria -->
<div class="modal fade" id="kriteriaModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Form Kriteria PIP</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="kriteriaForm">
          <input type="hidden" name="id" id="kriteria_id" value="">
          <div class="form-group">
            <label>Nama Kriteria</label>
            <select id="nama_kriteria_select" class="form-control">
              <option value="">-- Pilih Kriteria --</option>
              <option>Daerah Konflik</option>
              <option>Dampak Bencana Alam</option>
              <option>Kelaianan Fisik</option>
              <option>Keluarga Terpidana / Berada di LAPAS</option>
              <option>Pemegang PKH/KPS/KKS</option>
              <option>Pernah Drop Out</option>
              <option>Siswa Miskin/Rentan Miskin</option>
              <option>Yatim Piatu/Panti Asuhan/Panti Sosial</option>
              <option value="__lainnya__">Lainnya (isi manual)</option>
            </select>
            <input type="text" id="nama_kriteria_custom" class="form-control mt-2" placeholder="Isi kriteria lain..." style="display:none;" />
            <!-- Hidden field submitted to server -->
            <input type="hidden" name="nama_kriteria" id="nama_kriteria_hidden" value="" required />
          </div>
          <div class="form-group">
            <label>Deskripsi</label>
            <textarea name="deskripsi" id="deskripsi" class="form-control" rows="4"></textarea>
          </div>
          <div class="form-group">
            <label>Poin</label>
            <input type="number" name="poin" id="poin" class="form-control" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary btn-save-kriteria">Simpan</button>
      </div>
    </div>
  </div>
</div>

<script src="./mod/kriteria-pip/scripts.js"></script>
<!-- (Removed unrelated usulan-pip modals and preview markup.) -->

