# SAE - Smart Apps Education

## Deskripsi Aplikasi
SAE adalah sistem manajemen kehadiran dan administrasi sekolah yang komprehensif. Aplikasi ini dirancang untuk mengelola absensi siswa, administrasi sekolah, dan berbagai fitur pendukung lainnya seperti sistem PIP (Program Indonesia Pintar), integrasi WhatsApp, dan sinkronisasi data dengan Dapodik.

## Teknologi yang Digunakan
- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap
- **Authentication**: Cookie-based sessions, CSRF protection
- **API**: RESTful API endpoints
- **Integration**: Google OAuth2, WhatsApp Gateway, Dapodik sync
- **Additional Libraries**: PHPMailer, QR Code generator, PDF generator

## Struktur File dan Direktori

```
saev4/
├── index.php                    # Entry point aplikasi utama
├── maintenance.php              # Halaman maintenance
├── .htaccess                   # Apache rewrite rules
│
├── admin/                      # Panel administrasi sekolah
│   ├── index.php              # Dashboard admin
│   ├── assets/                # Asset admin (CSS, JS, images)
│   ├── login/                 # Sistem login admin
│   └── mod/                   # Modul-modul admin
│       ├── absensi/          # Manajemen absensi
│       ├── user/             # Manajemen user
│       ├── kelas/            # Manajemen kelas
│       ├── jurusan/          # Manajemen jurusan
│       ├── jadwal/           # Manajemen jadwal
│       ├── usulan-pip*/      # Manajemen Program Indonesia Pintar
│       └── ...
│
├── dashboard/                  # Dashboard siswa
│   ├── index.php              # Entry point dashboard siswa
│   ├── assets/                # Asset dashboard
│   ├── mod/                   # Modul dashboard siswa
│   │   ├── absensi/          # Lihat absensi
│   │   ├── profile/          # Profile siswa
│   │   ├── e-izin/           # Pengajuan izin
│   │   ├── usulan-pip/       # Usulan PIP
│   │   └── ...
│   └── oauth/                 # Autentikasi siswa
│
├── api/                        # API endpoints
│   ├── api_config.php         # Konfigurasi API
│   ├── receive-data.php       # Endpoint menerima data
│   ├── sync.php               # Sinkronisasi data
│   ├── whatsapp-webhook.php   # WhatsApp webhook
│   ├── core/                  # Core API classes
│   │   ├── ApiAuth.php        # Autentikasi API
│   │   ├── ApiLogger.php      # Logging API
│   │   ├── ApiResponse.php    # Response handler
│   │   ├── ApiValidator.php   # Validasi data
│   │   └── DataProcessor.php  # Processor data
│   ├── endpoints/             # API endpoints
│   │   └── sync_data.php      # Endpoint sync data
│   ├── handlers/              # Request handlers
│   │   ├── BaseHandler.php    # Base handler
│   │   ├── GtkHandler.php     # Handler GTK
│   │   ├── PenggunaHandler.php # Handler pengguna
│   │   ├── PesertaDidikHandler.php # Handler peserta didik
│   │   ├── RombelHandler.php  # Handler rombel
│   │   └── SekolahHandler.php # Handler sekolah
│   └── logs/                  # Log files
│
├── module/                     # Modul aplikasi utama
│   ├── header.php             # Header template
│   ├── footer.php             # Footer template
│   ├── home/                  # Homepage
│   ├── login/                 # Login publik
│   ├── absensi/               # Absensi siswa
│   ├── nisn/                  # Cek NISN
│   ├── pra-spmb/              # Pre-SPMB system
│   └── ...
│
├── library/                    # Library dan utilitas
│   ├── config.php             # Konfigurasi database
│   ├── function.php           # Fungsi-fungsi umum
│   ├── csrf.php               # CSRF protection
│   ├── timthumb.php           # Image resizing
│   ├── whatsapp-gateway.php   # WhatsApp integration
│   ├── google-client/         # Google OAuth client
│   ├── PDF/                   # PDF generation
│   ├── PHPMailer/             # Email library
│   └── phpqrcode/             # QR code generator
│
├── content/                    # File upload dan konten
│   ├── assets/                # Asset konten
│   ├── avatar/                # Avatar users
│   ├── berkas/                # Upload berkas
│   ├── capture/               # Foto absensi
│   ├── qrcode/                # QR codes
│   ├── sound/                 # Audio files
│   └── usulan-pip/            # Dokumen PIP
│
├── google/                     # Integrasi Google
│   ├── google-config.php      # Konfigurasi Google API
│   └── google.php             # Google OAuth handler
│
└── database/                   # Database dan migrasi
    ├── saev4_final.sql        # Database schema utama
    ├── whatsapp_gateway.sql   # Schema WhatsApp
    └── whatsapp_verification_update.sql
```

## Fitur Utama

### 1. Sistem Absensi
- ✅ Absensi siswa dengan foto selfie
- ✅ Deteksi lokasi (geolocation)
- ✅ QR Code untuk absensi
- ✅ Status kehadiran (Hadir, Izin, Sakit, Alpa)
- ✅ Toleransi waktu terlambat
- ✅ Laporan absensi harian/bulanan

### 2. Administrasi Sekolah
- ✅ Manajemen siswa dan guru
- ✅ Manajemen kelas dan jurusan  
- ✅ Jadwal pelajaran
- ✅ Pengaturan sistem
- ✅ User management dengan level akses
- ✅ Profile sekolah dan identitas

### 3. Program Indonesia Pintar (PIP)
- ✅ Pengajuan usulan PIP
- ✅ Kriteria penilaian PIP
- ✅ Ranking usulan PIP
- ✅ Laporan PIP yang diterima
- ✅ History usulan PIP

### 4. Integrasi & API
- ✅ Sinkronisasi dengan Dapodik
- ✅ WhatsApp Gateway untuk notifikasi
- ✅ Google OAuth untuk login
- ✅ RESTful API endpoints
- ✅ Logging system untuk tracking

### 5. Dashboard & Reporting
- ✅ Dashboard admin dengan statistik
- ✅ Dashboard siswa untuk self-service
- ✅ Laporan absensi detail
- ✅ Export data ke PDF/Excel
- ✅ Real-time notifications

## Konfigurasi Database

### Tabel Utama:
- `admin` - Data administrator dan guru
- `siswa` - Data siswa  
- `absensi` - Record kehadiran siswa
- `kelas` - Data kelas
- `jurusan` - Data jurusan/program studi
- `jadwal` - Jadwal pelajaran
- `usulan_pip` - Data Program Indonesia Pintar
- `setting` - Pengaturan sistem

### Koneksi Database:
```php
// File: library/config.php
$DB_HOST   = 'localhost';
$DB_NAME   = 'saev4'; 
$DB_USER   = 'root'; 
$DB_PASSWD = '';
```

## Instalasi

### Persiapan Server
1. **Requirement**:
   - PHP 7.4 atau lebih tinggi
   - MySQL 5.7+ atau MariaDB 10.4+
   - Apache/Nginx dengan mod_rewrite
   - Extension: mysqli, gd, json, curl, openssl

2. **Download dan Setup**:
   ```bash
   # Clone atau extract project ke htdocs/saev4
   cd c:\xampp\htdocs\saev4
   
   # Import database
   mysql -u root -p saev4 < database/saev4_final.sql
   mysql -u root -p saev4 < database/whatsapp_gateway.sql
   ```

3. **Konfigurasi**:
   - Edit `library/config.php` untuk database
   - Set permission folder `content/` untuk upload
   - Konfigurasi Google API di `google/google-config.php`
   - Setup WhatsApp Gateway configuration

### URL Access:
- **Portal Utama**: `http://localhost/saev4/`
- **Admin Panel**: `http://localhost/saev4/admin/`
- **Dashboard Siswa**: `http://localhost/saev4/dashboard/`
- **API Endpoints**: `http://localhost/saev4/api/`

## Penggunaan

### Login Admin
1. Akses `/admin/`  
2. Login dengan kredensial admin
3. Dashboard admin dengan menu:
   - Home & Statistik
   - Manajemen User
   - Data Siswa & Kelas
   - Absensi & Laporan
   - Program PIP
   - Pengaturan

### Login Siswa  
1. Akses `/dashboard/`
2. Login dengan NISN/username
3. Fitur siswa:
   - Lihat profile dan data
   - Absensi harian
   - Riwayat kehadiran
   - Pengajuan izin
   - Usulan PIP

### API Usage
```php
// Contoh API call untuk sync data
$api_key = 'YOUR_API_KEY';
$endpoint = 'http://localhost/saev4/api/endpoints/sync_data.php';

$data = [
    'api_key' => $api_key,
    'action' => 'sync_students',
    'data' => $student_data
];

$response = file_get_contents($endpoint, false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'content' => json_encode($data),
        'header' => 'Content-Type: application/json'
    ]
]));
```

## Maintenance & Security

### Security Features:
- ✅ CSRF Token protection
- ✅ SQL injection prevention  
- ✅ XSS filtering
- ✅ Session management
- ✅ File upload validation
- ✅ API key authentication

### Maintenance Mode:
- Aktifkan melalui `maintenance.php`
- Blokir akses user saat maintenance
- Admin tetap bisa akses sistem

### Logging:
- API logs di `api/logs/`
- Error logging dalam application
- User activity tracking

## Troubleshooting

### Common Issues:
1. **Database connection error**: 
   - Cek konfigurasi di `library/config.php`
   - Pastikan MySQL service running

2. **Upload file gagal**:
   - Set permission 755/777 untuk folder `content/`
   - Cek php.ini untuk max_upload_size

3. **API tidak response**:
   - Cek API key configuration
   - Verify .htaccess mod_rewrite
   - Check error logs

4. **WhatsApp tidak kirim**:
   - Cek konfigurasi gateway
   - Verify webhook URL
   - Test koneksi API WhatsApp

## Changelog & Version
- **v4.0**: Initial release dengan fitur lengkap
- **v4.1**: Penambahan API sync Dapodik
- **v4.2**: Integrasi WhatsApp Gateway
- **Current**: Maintenance dan bug fixes

## Developer & Support
- **Developer**: [Developer Name]
- **Support**: Technical support untuk implementation
- **Documentation**: Update berkala sesuai development

---
*Sistem ini dikembangkan untuk keperluan administrasi sekolah dengan fokus pada efisiensi dan user experience*#   s a e  
 