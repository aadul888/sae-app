# SAE – Smart Apps Education

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![PHP](https://img.shields.io/badge/php-7.4+-blue.svg)](https://php.net)
[![Status](https://img.shields.io/badge/status-active-brightgreen.svg)]()

**SAE** adalah aplikasi utama (*client-side*) dalam ekosistem **Smart Apps Education**. Aplikasi ini dirancang sebagai platform administrasi pendidikan yang interaktif dan efisien untuk mengelola data operasional sekolah secara terpadu.

> **Integrasi Ekosistem:** Aplikasi ini terhubung secara *seamless* dengan [SAE Induk](https://github.com/aadul888/sae-induk) sebagai server pusat untuk otorisasi lisensi dan sinkronisasi data.

---

## 🚀 Fitur Utama
* **Manajemen Kehadiran:** Sistem absensi siswa *real-time* yang akurat.
* **Integrasi Data:** Sinkronisasi data yang efisien dengan database Dapodik.
* **Layanan PIP:** Pengelolaan data usulan Program Indonesia Pintar yang terpusat.
* **Notifikasi Pintar:** Integrasi *WhatsApp Gateway* untuk komunikasi sekolah.
* **Autentikasi Modern:** Mendukung *Google OAuth2* untuk keamanan akses pengguna.

## 🛠 Teknologi
* **Backend:** PHP 7.4+
* **Frontend:** Bootstrap, JavaScript, HTML5
* **Database:** MySQL / MariaDB
* **Integrasi:** RESTful API, WhatsApp Webhook, Google OAuth2

## 📦 Struktur Proyek
Aplikasi ini diorganisir dengan standar modular untuk memudahkan pengembangan:

```text
sae/
├── admin/       # Panel manajemen operasional sekolah
├── api/         # Endpoint komunikasi ke SAE Induk
├── dashboard/   # Antarmuka utama siswa & pengguna
├── module/      # Fitur fungsionalitas utama (Absensi, PIP, dll)
├── library/     # Library dan utilitas pendukung
└── ...          # Konfigurasi sistem
