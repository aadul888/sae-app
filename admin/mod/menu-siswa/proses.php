<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
}
require_once '../../../library/config.php';
require_once '../../../library/function.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'toggle':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $aktif = isset($_POST['aktif']) && $_POST['aktif'] === 'Y' ? 'Y' : 'N';
        if ($id <= 0) {
            echo 'ID tidak valid';
            exit;
        }
        $stmt = $connection->prepare("UPDATE student_menu SET aktif = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('si', $aktif, $id);
            if ($stmt->execute()) {
                echo 'success';
            } else {
                echo 'Gagal menyimpan status';
            }
            $stmt->close();
        } else {
            echo 'Gagal menyimpan status';
        }
        break;
    case 'save':
        // add or update
        $id = isset($_POST['id']) && intval($_POST['id']) > 0 ? intval($_POST['id']) : 0;
        $label = isset($_POST['label']) ? trim($_POST['label']) : '';
        $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
        $position = isset($_POST['position']) ? intval($_POST['position']) : 0;
        if ($label === '' || $slug === '') {
            echo 'Label atau slug tidak boleh kosong';
            exit;
        }
        if ($id > 0) {
            $stmt = $connection->prepare("UPDATE student_menu SET label = ?, slug = ?, position = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('ssii', $label, $slug, $position, $id);
                if ($stmt->execute()) {
                    echo 'success';
                } else {
                    echo 'Gagal menyimpan perubahan';
                }
                $stmt->close();
            } else {
                echo 'Gagal menyimpan perubahan';
            }
        } else {
            $stmt = $connection->prepare("INSERT INTO student_menu (label, slug, aktif, position) VALUES (?, ?, 'Y', ?)");
            if ($stmt) {
                $stmt->bind_param('ssi', $label, $slug, $position);
                if ($stmt->execute()) {
                    echo 'success';
                } else {
                    echo 'Gagal menyimpan data';
                }
                $stmt->close();
            } else {
                echo 'Gagal menyimpan data';
            }
        }
        break;
    case 'delete':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            echo 'ID tidak valid';
            exit;
        }
        $stmt = $connection->prepare("DELETE FROM student_menu WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                echo 'success';
            } else {
                echo 'Gagal menghapus data';
            }
            $stmt->close();
        } else {
            echo 'Gagal menghapus data';
        }
        break;
    case 'sync':
        // sync defaults (similar to menu-siswa initial seeding)
        $defaults = [
            'identitas' => ['label' => 'Identitas', 'pos' => 10],
            'berkas' => ['label' => 'Berkas', 'pos' => 20],
            'absensi' => ['label' => 'Absensi', 'pos' => 30],
            'izin' => ['label' => 'Izin', 'pos' => 40],
            'edit-identitas' => ['label' => 'Edit Identitas', 'pos' => 50],
            'tata-tertib' => ['label' => 'Tata Tertib', 'pos' => 60],
            'catatan-pelanggaran' => ['label' => 'Poin', 'pos' => 70],
            'usulan-pip' => ['label' => 'Usulan PIP', 'pos' => 80],
            'kelas-q' => ['label' => 'Kelas Q', 'pos' => 90],
            'e-izin' => ['label' => 'e-Izin', 'pos' => 100],
            'ekpd' => ['label' => 'e-KPD', 'pos' => 110],
            'applain' => ['label' => 'Lainnya', 'pos' => 120]
        ];
        foreach ($defaults as $slug => $meta) {
            $s = $connection->real_escape_string($slug);
            $q = $connection->query("SELECT id FROM student_menu WHERE slug='" . $s . "' LIMIT 1");
            if (!$q || $q->num_rows == 0) {
                $lbl = $connection->real_escape_string($meta['label']);
                $pos = intval($meta['pos']);
                $connection->query("INSERT INTO student_menu (slug, label, aktif, position) VALUES ('" . $s . "', '" . $lbl . "', 'Y', '" . $pos . "')");
            }
        }
        // Additionally scan dashboard/mod folders and add missing modules (except 'home')
        $base = realpath(__DIR__ . '/../../../dashboard/mod');
        $added = 0;
        if ($base && is_dir($base)) {
            $dirs = scandir($base);
            $pos_base = 10;
            foreach ($dirs as $d) {
                if ($d === '.' || $d === '..') continue;
                $full = $base . DIRECTORY_SEPARATOR . $d;
                if (!is_dir($full)) continue; // skip files like header.php
                $slug = $d;
                if ($slug === 'home') continue; // skip main dashboard
                // normalize slug
                $slug_clean = preg_replace('/[^a-z0-9-_]/i', '', $slug);
                if ($slug_clean === '') continue;
                $s = $connection->real_escape_string($slug_clean);
                $q = $connection->query("SELECT id FROM student_menu WHERE slug='" . $s . "' LIMIT 1");
                if (!$q || $q->num_rows == 0) {
                    $lbl = ucwords(str_replace(array('-', '_'), ' ', $slug_clean));
                    $lbl = $connection->real_escape_string($lbl);
                    // find max position to append
                    $r = $connection->query("SELECT MAX(position) AS mx FROM student_menu");
                    $mx = 0;
                    if ($r && $rowmx = $r->fetch_assoc()) $mx = intval($rowmx['mx']);
                    $pos = $mx + $pos_base;
                    $connection->query("INSERT INTO student_menu (slug, label, aktif, position) VALUES ('" . $s . "', '" . $lbl . "', 'Y', '" . $pos . "')");
                    $added++;
                }
            }
        }
        echo 'success|' . $added;
        break;
    default:
        echo 'Unknown action';
        break;
}
