<?php
require_once '../../library/config.php';
require_once '../../library/function.php';
require_once '../../library/sso_config.php';

if (!function_exists('getUserByUsername')) {
    function getUserByUsername($username, $connection, $type)
    {
        if ($type === 'email') {
            $query = "SELECT user_id, password, status FROM user WHERE email = ? LIMIT 1";
        } elseif ($type === 'nisn') {
            $query = "SELECT user_id, password, status FROM user WHERE nisn = ? LIMIT 1";
        } else {
            $query = "SELECT user_id, password, status FROM user WHERE username = ? OR nisn = ? LIMIT 1";
        }
        $stmt = $connection->prepare($query);
        if (!$stmt) {
            return null;
        }
        if ($type === 'username') {
            $stmt->bind_param('ss', $username, $username);
        } else {
            $stmt->bind_param('s', $username);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result && $result->num_rows > 0 ? $result->fetch_assoc() : null;
        $stmt->close();
        return $row;
    }
}

switch (@$_GET['action']) {
    case 'login':
        // Rate limiting: max 10 percobaan per IP per 5 menit
        if (!rate_limit_check($connection, 'student_login', 10, 300)) {
            echo 'Terlalu banyak percobaan login. Silakan coba lagi nanti.';
            exit;
        }
        $error = array();

        if (empty($_POST['username'])) {
            $error[] = 'Email atau NISN tidak boleh kosong';
        } else {
            $username = anti_injection($_POST['username']);
            if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                $username_type = 'email';
            } elseif (is_numeric($username)) {
                $username_type = 'nisn';
            } else {
                // Ijinkan login menggunakan username / alphanumeric identifier jika ada
                $username_type = 'username';
            }
        }

        if (empty($_POST['password'])) {
            $error[] = 'Password tidak boleh kosong';
        } else {
            $password_hash = strip_tags($_POST['password']);
        }

        if (empty($error)) {
            // Panggil function untuk mendapatkan user
            $user = getUserByUsername($username, $connection, $username_type);
            if (!$user) {
                echo 'Akun Anda tidak ditemukan!';
                exit;
            }

            // Cek apakah akun aktif
            if (strtolower($user['status']) !== 'aktif') {
                echo 'Saat ini akun Anda belum aktif, silahkan hubungi Admin!';
                exit;
            }

            // Verifikasi password
            if (password_verify($password_hash, $user['password'])) {
                // Login berhasil, cek apakah password default (NISN)
                $user_id = strip_tags($user['user_id']);
                $siswa = convert("encrypt", $user_id);
                // debug log removed for production
                // Ambil NISN user (prepared statement)
                $query_nisn = "SELECT nisn FROM user WHERE user_id = ? LIMIT 1";
                $stmt_nisn = $connection->prepare($query_nisn);
                $row_nisn = [];
                if ($stmt_nisn) {
                    $stmt_nisn->bind_param('s', $user_id);
                    $stmt_nisn->execute();
                    $result_nisn = $stmt_nisn->get_result();
                    $row_nisn = $result_nisn ? $result_nisn->fetch_assoc() : [];
                    $stmt_nisn->close();
                }
                $nisn_user = $row_nisn['nisn'] ?? '';
                setcookie('siswa', $siswa, time() + (30 * 24 * 60 * 60), '/');
                // If SSO target requested, generate signed token and return redirect URL
                $sso_target = isset($_POST['sso_target']) ? $_POST['sso_target'] : '';
                if ($sso_target === 'pkl') {
                    // payload data for PKL (use nisn and basic info)
                    $payload = [
                        'user_id' => $user_id,
                        'nisn' => $nisn_user,
                        'iat' => time(),
                        'exp' => time() + 60 // token valid 60 seconds
                    ];
                    $b64 = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
                    $sig = rtrim(strtr(base64_encode(hash_hmac('sha256', $b64, SSO_SECRET, true)), '+/', '-_'), '=');
                    $token = $b64 . '.' . $sig;
                    $url = PKL_SSO_URL . '?token=' . urlencode($token);
                    echo 'sso:' . $url;
                    exit;
                }

                if ($password_hash === $nisn_user) {
                    echo 'success_default_password';
                } else {
                    echo 'success';
                }
            } else {
                echo 'NISN atau Password yang Anda masukkan salah!';
            }
        } else {
            foreach ($error as $key => $values) {
                echo "$values\n";
            }
        }
        break;
    case 'update_password':
        $response = ["status" => "error", "msg" => "Terjadi kesalahan."];
        $user_id = isset($_COOKIE['siswa']) ? convert('decrypt', $_COOKIE['siswa']) : '';
        $newPassword = isset($_POST['newPassword']) ? trim($_POST['newPassword']) : '';
        $confirmPassword = isset($_POST['confirmPassword']) ? trim($_POST['confirmPassword']) : '';
        if (!$user_id) {
            $response['msg'] = 'User tidak valid.';
        } else {
            // Server-side password policy validation
            $policyRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{6,}$/';
            if (!preg_match($policyRegex, $newPassword)) {
                $response['msg'] = 'Password harus minimal 6 karakter dan mengandung huruf besar, huruf kecil, angka, dan simbol.';
            } elseif ($newPassword !== $confirmPassword) {
                $response['msg'] = 'Konfirmasi password tidak sama.';
            } else {
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $user_id_esc = $connection->real_escape_string($user_id);
                $update = $connection->query("UPDATE user SET password='$passwordHash' WHERE user_id='$user_id_esc'");
                if ($update) {
                    $response = ["status" => "success", "msg" => "Password berhasil diupdate. Silakan login ulang."];
                } else {
                    $response['msg'] = 'Gagal update password.';
                }
            }
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
        
    case 'lupa_password':
        $response = ["status" => "error", "msg" => "Terjadi kesalahan."];
        $nomor_hp = isset($_POST['nomor_hp']) ? trim($_POST['nomor_hp']) : '';
        
        if (empty($nomor_hp)) {
            $response['msg'] = 'Nomor HP tidak boleh kosong.';
        } else {
            // Format nomor HP (hapus karakter non-angka)
            $nomor_hp = preg_replace('/[^0-9]/', '', $nomor_hp);
            
            // Cari user berdasarkan nomor HP yang sudah terverifikasi WhatsApp
            $query_user = "SELECT user_id, nisn, nama_lengkap, telp FROM user 
                          WHERE telp = '$nomor_hp' 
                          AND whatsapp_verified = 1 
                          AND status = 'Aktif' 
                          LIMIT 1";
            $result_user = $connection->query($query_user);
            
            if (!$result_user || $result_user->num_rows == 0) {
                $response['msg'] = 'Nomor HP tidak ditemukan atau belum terverifikasi WhatsApp. Hubungi admin untuk bantuan.';
            } else {
                $user_data = $result_user->fetch_assoc();
                $user_id = $user_data['user_id'];
                $nisn = $user_data['nisn'];
                $nama = $user_data['nama_lengkap'];
                
                // Reset password ke NISN
                $password_hash = password_hash($nisn, PASSWORD_DEFAULT);
                $update_query = "UPDATE user SET password='$password_hash' WHERE user_id='$user_id'";
                
                if ($connection->query($update_query)) {
                    // Include WhatsApp Gateway library
                    require_once '../../library/whatsapp-gateway.php';
                    
                    // Format nomor untuk WhatsApp (Indonesia)
                    $wa_number = $nomor_hp;
                    if (substr($wa_number, 0, 1) == '0') {
                        $wa_number = '62' . substr($wa_number, 1);
                    }
                    
                    // Pesan WhatsApp
                    $wa_message = "🔒 *RESET PASSWORD SAE SMK NEGERI 1 PAGELARAN*\n\n";
                    $wa_message .= "Halo *{$nama}*,\n\n";
                    $wa_message .= "Password akun Anda telah berhasil direset!\n\n";
                    $wa_message .= "📋 *Detail Akun:*\n";
                    $wa_message .= "• Username/NISN: *{$nisn}*\n";
                    $wa_message .= "• Password: *{$nisn}*\n";
                    $wa_message .= "• Status: Aktif\n\n";
                    $wa_message .= "🌐 *Link Login:*\n";
                    $wa_message .= "https://sae.smakpal.sch.id/login\n\n";
                    $wa_message .= "⚠️ *PENTING:*\n";
                    $wa_message .= "• Segera login dan ubah password Anda\n";
                    $wa_message .= "• Jangan berikan informasi ini kepada siapa pun\n";
                    $wa_message .= "• Jika Anda tidak meminta reset ini, segera hubungi admin\n\n";
                    $wa_message .= "_Pesan otomatis dari Sistem SAE_\n";
                    $wa_message .= "_SMK Negeri 1 Pagelaran_";
                    
                    // Kirim WhatsApp
                    $wa_result = sendWhatsAppNotification($wa_number, $wa_message, 'reset_password', $connection, $user_id);
                    
                    if ($wa_result['success']) {
                        $response = [
                            'status' => 'success', 
                            'msg' => 'Password berhasil direset! Informasi login telah dikirim ke WhatsApp Anda.'
                        ];
                    } else {
                        $response = [
                            'status' => 'warning', 
                            'msg' => 'Password berhasil direset, namun gagal mengirim WhatsApp. Silakan hubungi admin untuk mendapatkan password baru.'
                        ];
                    }
                } else {
                    $response['msg'] = 'Gagal reset password. Silakan coba lagi.';
                }
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
}
