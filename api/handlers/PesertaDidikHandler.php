<?php

/**
 * SAE v4 API - Peserta Didik Data Handler
 * 
 * Handler untuk menangani data peserta didik
 */

require_once __DIR__ . '/BaseHandler.php';

class PesertaDidikHandler extends BaseHandler
{

    protected function getTableName()
    {
        return 'sync_peserta_didik';
    }

    /**
     * Save single peserta didik data
     */
    public function save($data)
    {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO sync_peserta_didik 
                (registrasi_id, jenis_pendaftaran_id, jenis_pendaftaran_id_str, nipd, tanggal_masuk_sekolah, 
                 sekolah_asal, peserta_didik_id, nama, nisn, jenis_kelamin, nik, tempat_lahir, tanggal_lahir,
                 agama_id, agama_id_str, nomor_telepon_rumah, nomor_telepon_seluler, nama_ayah, pekerjaan_ayah_id,
                 pekerjaan_ayah_id_str, nama_ibu, pekerjaan_ibu_id, pekerjaan_ibu_id_str, nama_wali, 
                 pekerjaan_wali_id, pekerjaan_wali_id_str, anak_keberapa, tinggi_badan, berat_badan, email,
                 semester_id, anggota_rombel_id, rombongan_belajar_id, tingkat_pendidikan_id, nama_rombel,
                 kurikulum_id, kurikulum_id_str, kebutuhan_khusus, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                nama = VALUES(nama), nisn = VALUES(nisn), nipd = VALUES(nipd), 
                jenis_kelamin = VALUES(jenis_kelamin), nik = VALUES(nik), tempat_lahir = VALUES(tempat_lahir),
                tanggal_lahir = VALUES(tanggal_lahir), agama_id = VALUES(agama_id), agama_id_str = VALUES(agama_id_str),
                tanggal_masuk_sekolah = VALUES(tanggal_masuk_sekolah), sekolah_asal = VALUES(sekolah_asal),
                nomor_telepon_rumah = VALUES(nomor_telepon_rumah), nomor_telepon_seluler = VALUES(nomor_telepon_seluler),
                email = VALUES(email), nama_ayah = VALUES(nama_ayah), nama_ibu = VALUES(nama_ibu), nama_wali = VALUES(nama_wali),
                anak_keberapa = VALUES(anak_keberapa), tinggi_badan = VALUES(tinggi_badan), berat_badan = VALUES(berat_badan),
                anggota_rombel_id = VALUES(anggota_rombel_id), rombongan_belajar_id = VALUES(rombongan_belajar_id),
                tingkat_pendidikan_id = VALUES(tingkat_pendidikan_id), nama_rombel = VALUES(nama_rombel),
                kurikulum_id = VALUES(kurikulum_id), kurikulum_id_str = VALUES(kurikulum_id_str),
                kebutuhan_khusus = VALUES(kebutuhan_khusus), updated_at = NOW()
            ");

            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $this->connection->error);
            }

            // Map data fields sesuai API Dapodik
            $registrasi_id = (string)($data['registrasi_id'] ?? '');
            $jenis_pendaftaran_id = (string)($data['jenis_pendaftaran_id'] ?? '');
            $jenis_pendaftaran_id_str = (string)($data['jenis_pendaftaran_id_str'] ?? '');
            $nipd = (string)($data['nipd'] ?? '');
            $tanggal_masuk_sekolah = (string)($data['tanggal_masuk_sekolah'] ?? '');
            $sekolah_asal = (string)($data['sekolah_asal'] ?? '');
            $peserta_didik_id = (string)($data['peserta_didik_id'] ?? '');
            $nama = (string)($data['nama'] ?? '');
            $nisn = (string)($data['nisn'] ?? '');
            $jenis_kelamin = (string)($data['jenis_kelamin'] ?? '');
            $nik = (string)($data['nik'] ?? '');
            $tempat_lahir = (string)($data['tempat_lahir'] ?? '');
            $tanggal_lahir = (string)($data['tanggal_lahir'] ?? '');
            $agama_id = (string)($data['agama_id'] ?? 0);
            $agama_id_str = (string)($data['agama_id_str'] ?? '');
            $nomor_telepon_rumah = ($data['nomor_telepon_rumah'] !== null) ? (string)$data['nomor_telepon_rumah'] : null;
            $nomor_telepon_seluler = (string)($data['nomor_telepon_seluler'] ?? '');
            $nama_ayah = (string)($data['nama_ayah'] ?? '');
            $pekerjaan_ayah_id = (string)($data['pekerjaan_ayah_id'] ?? 0);
            $pekerjaan_ayah_id_str = (string)($data['pekerjaan_ayah_id_str'] ?? '');
            $nama_ibu = (string)($data['nama_ibu'] ?? '');
            $pekerjaan_ibu_id = (string)($data['pekerjaan_ibu_id'] ?? 0);
            $pekerjaan_ibu_id_str = (string)($data['pekerjaan_ibu_id_str'] ?? '');
            $nama_wali = ($data['nama_wali'] !== null) ? (string)$data['nama_wali'] : null;
            $pekerjaan_wali_id = ($data['pekerjaan_wali_id'] !== null) ? (string)$data['pekerjaan_wali_id'] : null;
            $pekerjaan_wali_id_str = (string)($data['pekerjaan_wali_id_str'] ?? '');
            $anak_keberapa = (string)($data['anak_keberapa'] ?? '');
            $tinggi_badan = (string)($data['tinggi_badan'] ?? '');
            $berat_badan = (string)($data['berat_badan'] ?? '');
            $email = ($data['email'] !== null) ? (string)$data['email'] : null;
            $semester_id = (string)($data['semester_id'] ?? '');
            $anggota_rombel_id = (string)($data['anggota_rombel_id'] ?? '');
            $rombongan_belajar_id = (string)($data['rombongan_belajar_id'] ?? '');
            $tingkat_pendidikan_id = (string)($data['tingkat_pendidikan_id'] ?? '');
            $nama_rombel = (string)($data['nama_rombel'] ?? '');
            $kurikulum_id = (string)($data['kurikulum_id'] ?? 0);
            $kurikulum_id_str = (string)($data['kurikulum_id_str'] ?? '');
            $kebutuhan_khusus = (string)($data['kebutuhan_khusus'] ?? '');

            // Bind all parameters
            $stmt->bind_param(
                'ssssssssssssssssssssssssssssssssssssss',
                $registrasi_id,
                $jenis_pendaftaran_id,
                $jenis_pendaftaran_id_str,
                $nipd,
                $tanggal_masuk_sekolah,
                $sekolah_asal,
                $peserta_didik_id,
                $nama,
                $nisn,
                $jenis_kelamin,
                $nik,
                $tempat_lahir,
                $tanggal_lahir,
                $agama_id,
                $agama_id_str,
                $nomor_telepon_rumah,
                $nomor_telepon_seluler,
                $nama_ayah,
                $pekerjaan_ayah_id,
                $pekerjaan_ayah_id_str,
                $nama_ibu,
                $pekerjaan_ibu_id,
                $pekerjaan_ibu_id_str,
                $nama_wali,
                $pekerjaan_wali_id,
                $pekerjaan_wali_id_str,
                $anak_keberapa,
                $tinggi_badan,
                $berat_badan,
                $email,
                $semester_id,
                $anggota_rombel_id,
                $rombongan_belajar_id,
                $tingkat_pendidikan_id,
                $nama_rombel,
                $kurikulum_id,
                $kurikulum_id_str,
                $kebutuhan_khusus
            );

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $affected_rows = $stmt->affected_rows;
            $stmt->close();

            return [
                'success' => true,
                'affected_rows' => $affected_rows,
                'peserta_didik_id' => $peserta_didik_id,
                'nama' => $nama,
                'nisn' => $nisn
            ];
        } catch (Exception $e) {
            ApiLogger::logError('PesertaDidikHandler::save', $e->getMessage(), [
                'peserta_didik_data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Smart sync untuk array data peserta didik
     * Menghapus data yang tidak ada di update terbaru
     */
    public function smartSync($data_array)
    {
        $stats = [
            'total' => count($data_array),
            'inserted' => 0,
            'updated' => 0,
            'deleted' => 0,
            'errors' => []
        ];

        try {
            $this->beginTransaction();

            // Collect all peserta_didik_id from incoming data
            $incoming_ids = [];
            foreach ($data_array as $data) {
                if (!empty($data['peserta_didik_id'])) {
                    $incoming_ids[] = $data['peserta_didik_id'];
                }
            }

            // Process all incoming data
            foreach ($data_array as $data) {
                try {
                    $result = $this->save($data);
                    if ($result['affected_rows'] > 0) {
                        // Check if it's an insert (new record) or update
                        $check_stmt = $this->connection->prepare("SELECT COUNT(*) as count FROM sync_peserta_didik WHERE peserta_didik_id = ?");
                        $check_stmt->bind_param('s', $data['peserta_didik_id']);
                        $check_stmt->execute();
                        $check_result = $check_stmt->get_result();
                        $count = $check_result->fetch_assoc()['count'];
                        $check_stmt->close();

                        if ($count == 1 && $result['affected_rows'] == 1) {
                            $stats['inserted']++;
                        } else {
                            $stats['updated']++;
                        }
                    }
                } catch (Exception $e) {
                    $stats['errors'][] = $e->getMessage();
                }
            }

            // Delete records that are not in the incoming data (peserta didik yang sudah tidak ada)
            if (!empty($incoming_ids)) {
                $placeholders = str_repeat('?,', count($incoming_ids) - 1) . '?';
                $delete_stmt = $this->connection->prepare("DELETE FROM sync_peserta_didik WHERE peserta_didik_id NOT IN ($placeholders)");

                $types = str_repeat('s', count($incoming_ids));
                $delete_stmt->bind_param($types, ...$incoming_ids);

                if ($delete_stmt->execute()) {
                    $stats['deleted'] = $delete_stmt->affected_rows;
                }
                $delete_stmt->close();
            }

            $this->commitTransaction();

            // Log the sync activity with correct stats
            ApiLogger::logSyncActivity(
                'peserta_didik',
                'success',
                $stats['total'],
                "Smart sync completed: {$stats['inserted']} inserted, {$stats['updated']} updated, {$stats['deleted']} deleted",
                $stats
            );
        } catch (Exception $e) {
            $this->rollbackTransaction();
            ApiLogger::logError('PesertaDidikHandler::smartSync', $e->getMessage());
            throw $e;
        }

        return $stats;
    }

    /**
     * Get peserta didik by NISN
     */
    public function getByNisn($nisn)
    {
        try {
            $stmt = $this->connection->prepare("SELECT * FROM sync_peserta_didik WHERE nisn = ?");
            $stmt->bind_param('s', $nisn);
            $stmt->execute();

            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();

            return $data;
        } catch (Exception $e) {
            ApiLogger::logError('PesertaDidikHandler::getByNisn', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get peserta didik by rombel
     */
    public function getByRombel($rombongan_belajar_id)
    {
        try {
            $stmt = $this->connection->prepare("SELECT * FROM sync_peserta_didik WHERE rombongan_belajar_id = ?");
            $stmt->bind_param('s', $rombongan_belajar_id);
            $stmt->execute();

            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $stmt->close();

            return $data;
        } catch (Exception $e) {
            ApiLogger::logError('PesertaDidikHandler::getByRombel', $e->getMessage());
            throw $e;
        }
    }
}
