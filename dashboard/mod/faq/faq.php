<?php
if (empty($connection)) {
    echo 'Koneksi tidak ditemukan';
    header('location:../');
    exit();
} else {
    if (isset($_COOKIE['siswa'])) {
?>

        <!DOCTYPE html>
        <html lang="id">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>FAQ - Tanya Jawab Aplikasi</title>
            <style>
                .faq-section {
                    max-width: 800px;
                    margin: 40px auto;
                }

                .faq-question {
                    font-weight: bold;
                }

                .card {
                    margin-bottom: 1rem;
                }

                /* Tambahkan padding bawah agar konten tidak tertutup footer */
                body {
                    padding-bottom: 50px;
                }
                
                /* Hover effect untuk tombol panduan */
                .panduan-btn:hover {
                    transform: translateY(-2px) !important;
                    box-shadow: 0 5px 15px rgba(255, 193, 7, 0.6) !important;
                    transition: all 0.3s ease !important;
                }
                
                /* Pastikan tombol panduan selalu terlihat */
                .panduan-box {
                    position: fixed !important;
                    top: 80px !important;
                    left: 20px !important;
                    right: 20px !important;
                    z-index: 9999 !important;
                    display: block !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                    max-width: 800px !important;
                    margin: 0 auto !important;
                }
                
                .panduan-btn {
                    display: inline-block !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                    animation: bounce-soft 2s infinite !important;
                }
                
                @keyframes bounce-soft {
                    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
                    40% { transform: translateY(-5px); }
                    60% { transform: translateY(-3px); }
                }
                
                /* Atur konten utama agar tidak tertutup tombol */
                .faq-section {
                    padding-top: 150px !important;
                }
                
                /* Responsive */
                @media (max-width: 768px) {
                    .panduan-box {
                        position: fixed !important;
                        top: 70px !important;
                        left: 10px !important;
                        right: 10px !important;
                        max-width: none !important;
                    }
                    
                    .faq-section {
                        padding-top: 120px !important;
                    }
                }
            </style>
        </head>

        <body>

            <div>
                <div class="container faq-section">
                    <h2 class="mb-4 text-center text-white">FAQ - Tanya Jawab Seputar Aplikasi</h2>
                    
                    <!-- Tombol Panduan -->
                    <div class="panduan-box mb-4 text-center" style="background: linear-gradient(135deg, #0f4c81, #0f766e); border: none; box-shadow: 0 4px 15px rgba(15, 118, 110, 0.3); border-radius: 0.375rem; padding: 1.25rem;">
                        <div class="row align-items-center">
                            <div class="col-md-8 text-center">
                                <h5 class="mb-1 text-white"><i class="fas fa-book"></i> Panduan Lengkap Aplikasi</h5>
                                <p class="mb-0 text-white">Baca panduan lengkap penggunaan aplikasi untuk memahami semua fitur yang tersedia.</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <a href="https://docs.google.com/presentation/d/1wpj8iMbWDAdc1Uvgnu5amzTRRw-8RqbMeH_2IrEVX-o/edit?usp=sharing" 
                                   target="_blank" 
                                   class="btn btn-warning btn-lg font-weight-bold text-dark panduan-btn"
                                   style="background: linear-gradient(45deg, #ffc107, #fd7e14); border: 3px solid #fff; box-shadow: 0 3px 10px rgba(255, 193, 7, 0.4); text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">
                                    <i class="fas fa-external-link-alt"></i> BACA PANDUAN
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div id="accordion">
                        <div class="card">
                            <div class="card-header" id="headingOne">
                                <h5 class="mb-0">
                                    <button class="btn btn-link collapsed faq-question" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                        Bagaimana cara masuk ke Aplikasi?
                                    </button>
                                </h5>
                            </div>
                            <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                                <div class="card-body">
                                    Silakan Login bisa melalui Halaman Awal dengan memasukkan NISN di Formulir <b>CEK DATA NISN</b> dan masukkan <b>NISN</b>. Setelah muncul <b>Halaman Data</b> scroll ke bawah dan klik <b>Login Untuk Data Lengkap</b>, lalu masukkan NISN dan password Anda. Jika belum punya password, gunakan <b>NISN sebagai Password</b> awal.
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header" id="headingTwo">
                                <h5 class="mb-0">
                                    <button class="btn btn-link collapsed faq-question" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        Bagaimana jika lupa <i>Password</i>?
                                    </button>
                                </h5>
                            </div>
                            <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
                                <div class="card-body">
                                    Hubungi admin sekolah untuk melakukan reset password. Password akan direset ke NISN Anda.
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header" id="headingThree">
                                <h5 class="mb-0">
                                    <button class="btn btn-link collapsed faq-question" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                        Bagaimana cara mengubah data profil?
                                    </button>
                                </h5>
                            </div>
                            <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordion">
                                <div class="card-body">
                                    Setelah login, masuk ke menu <b>Edit Data</b> lalu <b>Tambah Usulan Perubahan Data</b> untuk mengubah data Anda. Simpan perubahan setelah selesai dan pantau terus progresnya.
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header" id="headingFour">
                                <h5 class="mb-0">
                                    <button class="btn btn-link collapsed faq-question" data-toggle="collapse" data-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                        Apakah data saya aman di aplikasi ini?
                                    </button>
                                </h5>
                            </div>
                            <div id="collapseFour" class="collapse" aria-labelledby="headingFour" data-parent="#accordion">
                                <div class="card-body">
                                    Ya, data Anda disimpan dengan aman dan hanya dapat diakses oleh pihak yang berwenang.
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header" id="headingFive">
                                <h5 class="mb-0">
                                    <button class="btn btn-link collapsed faq-question" data-toggle="collapse" data-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                        Siapa yang bisa saya hubungi jika mengalami kendala?
                                    </button>
                                </h5>
                            </div>
                            <div id="collapseFive" class="collapse" aria-labelledby="headingFive" data-parent="#accordion">
                                <div class="card-body">
                                    Silakan hubungi Admin Sekolah atau Tim IT melalui kontak berikut:<br>
                                    <a href="https://wa.me/628151800116" target="_blank" class="btn btn-success mt-2">
                                        <i class="fab fa-whatsapp"></i> 0815 1800 116
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php
        // Load footer dashboard jika ada
        if (file_exists(__DIR__ . '/../mod/footer.php')) {
            include_once __DIR__ . '/../mod/footer.php';
        }
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
}
    ?>
        </body>

        </html>