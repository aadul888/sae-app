<?php
// Ambil data siswa dari session yang login
$user_id = $current_user['user_id'] ?? 0;

$query = "SELECT user.*, kelas.nama_kelas FROM user 
LEFT JOIN kelas ON user.kelas = kelas.kelas_id 
WHERE user.user_id='$user_id'";
$result = $connection->query($query);
$data = $result->fetch_assoc();

if (!$data) {
  echo '<div class="alert alert-danger text-center">Data tidak ditemukan.</div>';
  exit;
}

$avatar_path = (!empty($data['avatar']) && $data['avatar'] != 'avatar.jpg') ? '../sw-content/avatar/' . htmlspecialchars($data['avatar']) : '../sw-content/avatar/avatar.jpg';
?>

<!-- Header -->
<div class="header bg-primary pb-6">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-4">
        <div class="col-lg-6 col-7">
          <h6 class="h2 text-white d-inline-block mb-0">Siswa</h6>
          <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
            <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
              <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
              <li class="breadcrumb-item active" aria-current="page">Profil</li>
            </ol>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Page Content -->
<div class="container-fluid mt--6">
  <div class="row">
    <!-- Kartu Profil Kiri -->
    <!-- Kartu Profil Kiri -->
    <div class="col-xl-4 order-xl-2">
      <div class="card card-profile">
        <!-- Background -->
        <img src="./sw-assets/img/theme/img-1-1000x600.jpg" alt="Image placeholder" class="card-img-top">

        <div class="row justify-content-center">
          <div class="col-lg-3 order-lg-2">
            <div class="card-profile-image">
              <?php
              if ($data['avatar'] == NULL || $data['avatar'] == 'avatar.jpg') {
                echo '<img src="../sw-content/avatar/avatar.jpg" class="rounded-circle w-150" height="140">';
              } else {
                $avatar_file = '../sw-content/avatar/' . $data['avatar'];
                if (file_exists($avatar_file)) {
                  echo '<a class="open-popup-link" href="' . $avatar_file . '">
                          <img src="' . $avatar_file . '" class="rounded-circle w-150" height="140">
                        </a>';
                } else {
                  echo '<img src="sw-assets/img/media.png" class="rounded-circle w-150" height="100">';
                }
              }
              ?>
            </div>
          </div>
        </div>

        <div class="card-header text-center border-0 pt-8 pt-md-4 pb-0 pb-md-4"></div>

        <div class="card-body pt-0">
          <div class="text-center mt-5">
            <h5 class="h3"><?= htmlspecialchars($data['nama_lengkap']) ?></h5>
            <div class="h5 font-weight-300">
              <?= htmlspecialchars($data['nisn']) ?>
            </div>
            <div class="h5 font-weight-300 mt-2">
              <?php if ($data['status'] == 'Aktif') {
                echo '<span class="badge badge-info">AKTIF</span>';
              } else {
                echo '<span class="badge badge-danger">TIDAK AKTIF</span>';
              } ?>
            </div>
            <div class="mt-3">
              <ul class="list-group list-group-flush">
                <li class="list-group-item">NIK: <?= htmlspecialchars($data['nik']) ?></li>
                <li class="list-group-item">NIPD: <?= htmlspecialchars($data['nipd']) ?></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Detail Profil Kanan -->
    <div class="col-xl-8 order-xl-1">
      <div class="card">
        <div class="card-header">
          <div class="row align-items-center">
            <div class="col-8">
              <h3 class="mb-0">Profil</h3>
            </div>
            <div class="col-4 text-right">
              <!-- Kalau mau edit bisa ditambahkan tombol setting di sini -->
            </div>
          </div>
        </div>
        <div class="card-body">
          <form>
            <h6 class="heading-small text-muted mb-4">Informasi Profil</h6>
            <div class="pl-lg-4">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label class="form-control-label">Nama Lengkap</label>
                    <p><?= htmlspecialchars($data['nama_lengkap']) ?></p>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label class="form-control-label">Tempat Lahir</label>
                    <p><?= htmlspecialchars($data['tempat_lahir']) ?></p>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label class="form-control-label">Tanggal Lahir</label>
                    <p><?= htmlspecialchars($data['tanggal_lahir']) ?></p>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label class="form-control-label">Jenis Kelamin</label>
                    <p><?= htmlspecialchars($data['jenis_kelamin']) ?></p>
                  </div>
                </div>
              </div>
            </div>

            <hr class="my-4">
            <h6 class="heading-small text-muted mb-4">Orang Tua</h6>
            <div class="pl-lg-4">
              <div class="row">
                <div class="col-lg-6 border-right">
                  <h6 class="heading-small text-muted mb-3">Ayah</h6>
                  <div class="form-group">
                    <label class="form-control-label">Nama Ayah</label>
                    <p><?= htmlspecialchars($data['nama_ayah']) ?></p>
                  </div>
                  <div class="form-group">
                    <label class="form-control-label">NIK Ayah</label>
                    <p><?= htmlspecialchars($data['nik_ayah']) ?></p>
                  </div>
                  <div class="form-group">
                    <label class="form-control-label">Pekerjaan Ayah</label>
                    <p><?= htmlspecialchars($data['pekerjaan_ayah']) ?></p>
                  </div>
                </div>

                <div class="col-lg-6">
                  <h6 class="heading-small text-muted mb-3">Ibu</h6>
                  <div class="form-group">
                    <label class="form-control-label">Nama Ibu</label>
                    <p><?= htmlspecialchars($data['nama_ibu']) ?></p>
                  </div>
                  <div class="form-group">
                    <label class="form-control-label">NIK Ibu</label>
                    <p><?= htmlspecialchars($data['nik_ibu']) ?></p>
                  </div>
                  <div class="form-group">
                    <label class="form-control-label">Pekerjaan Ibu</label>
                    <p><?= htmlspecialchars($data['pekerjaan_ibu']) ?></p>
                  </div>
                </div>
              </div>
            </div>

            <hr class="my-4" />
            <h6 class="heading-small text-muted mb-4">Kontak</h6>
            <div class="pl-lg-4">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label class="form-control-label">Telp</label>
                    <p><?= htmlspecialchars($data['telp']) ?></p>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label class="form-control-label">e-Mail Belajar.ID</label>
                    <p><?= htmlspecialchars($data['email_siswa']) ?></p>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label class="form-control-label">Alamat</label>
                    <p><?= htmlspecialchars($data['alamat']) ?></p>
                  </div>
                </div>
              </div>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
</div>