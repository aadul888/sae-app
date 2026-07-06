<?php
// Set zona waktu ke Asia/Jakarta agar waktu sesuai lokal
if(function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Asia/Jakarta');
}

require_once '../../library/config.php';
require_once '../../library/function.php';

switch (@$_GET['action']) {
    case 'cari':
        if (empty(htmlentities($_POST['nisn']))) {
            echo 'NISN tidak boleh kosong.';
        } else {
            $nisn = htmlentities($_POST['nisn']);

            $query_user = "SELECT nisn, user_id FROM user WHERE nisn='$nisn'";
            $result_user = $connection->query($query_user);

            if ($result_user->num_rows > 0) {
                $data_user = $result_user->fetch_assoc();
                $user_id = $data_user['user_id'];
                $nisn_encrypted = convert("encrypt", strip_tags($data_user['nisn']));

                $current_date = date('Y-m-d');
                $current_time = date('H:i:s');
                $one_month_ago = date('Y-m-d', strtotime('-1 month'));

                $check_statistik = "SELECT * FROM statistik WHERE user_id='$user_id' AND date BETWEEN '$one_month_ago' AND '$current_date'";
                $result_statistik = $connection->query($check_statistik);

                if ($result_statistik->num_rows > 0) {
                    $data_statistik = $result_statistik->fetch_assoc();
                    $new_jumlah = $data_statistik['jumlah'] + 1;

                    $update_statistik = "UPDATE statistik SET jumlah='$new_jumlah', date='$current_date', time='$current_time' WHERE user_id='$user_id' AND statistik_id='" . $data_statistik['statistik_id'] . "'";
                    if ($connection->query($update_statistik)) {
                        echo 'success/' . $nisn_encrypted;
                    } else {
                        echo 'Error memperbarui statistik: ' . $connection->error;
                    }
                } else {
                    $insert_statistik = "INSERT INTO statistik (user_id, jumlah, date, time) VALUES ('$user_id', 1, '$current_date', '$current_time')";
                    if ($connection->query($insert_statistik)) {
                        echo 'success/' . $nisn_encrypted;
                    } else {
                        echo 'Error memasukkan data ke statistik: ' . $connection->error;
                    }
                }
            } else {
                echo 'NISN yang Anda cari tidak ditemukan, silahkan periksa kembali.';
            }
        }
        break;
}
