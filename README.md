# Smart Apps Education (SAE)

Sistem informasi manajemen sekolah berbasis web yang mencakup absensi, agenda, poin pelanggaran, kelulusan, inventaris, surat, portal GTK, dan banyak modul lainnya.

---

## Persyaratan Sistem

| Komponen        | Minimum                                                   |
| --------------- | --------------------------------------------------------- |
| PHP             | 8.1 atau lebih baru                                       |
| MySQL / MariaDB | 8.0 / 10.6 atau lebih baru                                |
| Web Server      | Apache dengan `mod_rewrite` aktif                         |
| Ekstensi PHP    | `mysqli`, `mbstring`, `json`, `openssl`, `gd`, `fileinfo` |

---

## Cara Instalasi (Panduan Langkah demi Langkah)

Ikuti langkah berikut secara berurutan. Tidak diperlukan keahlian teknis mendalam.

### Langkah 1 — Siapkan File Aplikasi

Letakkan file aplikasi ke folder website Anda. Anda bisa menggunakan salah satu cara:

- **Git clone** (rekomendasi): jalankan di folder tujuan web server:
  ```bash
  git clone https://github.com/aadul888/sae-app.git .
  ```
  Tanda `.` di akhir agar file langsung masuk ke folder saat ini.
- **Unduh ZIP**: ekstrak isi arsip langsung ke folder web server (misal `htdocs/` atau `www/`).

### Langkah 2 — Buat Database Kosong

Buat satu database baru di MySQL/MariaDB melalui phpMyAdmin atau panel hosting Anda. Beri nama apa saja, misalnya `db_sae`. **Jangan** import file SQL apa pun — installer akan melakukannya otomatis nanti.

### Langkah 3 — Buka Halaman Konfigurasi

Buka alamat website Anda di browser (misal `https://domainanda.com/`).

Aplikasi akan otomatis mendeteksi bahwa belum terpasang, lalu menampilkan halaman **Konfigurasi Server**. Bila tidak otomatis pindah, buka `https://domainanda.com/konfigurasi/`.

### Langkah 4 — Isi Form dan Instal

Pada halaman konfigurasi, isi tiga kolom berikut:

| Kolom            | Isi dengan                                  |
| ---------------- | ------------------------------------------- |
| Nama Database    | Nama database yang dibuat di Langkah 2      |
| Username Database| User MySQL Anda (biasanya `root` di lokal)  |
| Password Database| Password MySQL (kosongkan bila tidak ada)   |

Kolom host sudah diisi otomatis (`localhost`). Klik tombol **Instal Aplikasi & Import Database**.

> **Catatan:** Kredensial database disimpan langsung di `library/config.php` (blok *Koneksi Database*). Tidak perlu membuat file `.env` maupun `.sae-installed`. Jika instalasi gagal menulis `config.php` (folder read-only), buka `library/config.php` dan edit nilai `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWD` secara manual.

### Langkah 5 — Simpan Akun Administrator

Setelah proses selesai, halaman akan menampilkan **username dan password acak** untuk superadmin. Ini hanya tampil sekali — salin dan simpan ke tempat aman (misal password manager) sebelum lanjut.

### Langkah 6 — Masuk ke Aplikasi

Klik **Kembali ke Beranda**, lalu buka panel admin:

```
https://domainanda.com/admin/
```

Masuk dengan username & password dari Langkah 5. **Segera ganti password** setelah login pertama lewat menu **Pengaturan → Profil**.

---

## Lisensi

© Smart Apps Education. Seluruh hak cipta dilindungi.
