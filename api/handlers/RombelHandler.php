<?php

/**
 * SAE v4 API - Rombongan Belajar Data Handler
 * 
 * Handler untuk menangani data rombongan belajar
 */

require_once __DIR__ . '/BaseHandler.php';

class RombelHandler extends BaseHandler
{

    protected function getTableName()
    {
        return 'sync_rombongan_belajar';
    }

    /**
     * Save rombongan belajar data
     */
    public function save($data)
    {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO sync_rombongan_belajar (
                    rombongan_belajar_id, nama, tingkat_pendidikan_id, tingkat_pendidikan_id_str,
                    semester_id, ptk_id, ptk_id_str, jurusan_id, jurusan_id_str, kurikulum_id,
                    kurikulum_id_str, id_ruang, id_ruang_str, moving_class, jenis_rombel, jenis_rombel_str,
                    is_sync_to_server, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                nama = VALUES(nama), tingkat_pendidikan_id = VALUES(tingkat_pendidikan_id),
                tingkat_pendidikan_id_str = VALUES(tingkat_pendidikan_id_str), semester_id = VALUES(semester_id),
                ptk_id = VALUES(ptk_id), ptk_id_str = VALUES(ptk_id_str), jurusan_id = VALUES(jurusan_id),
                jurusan_id_str = VALUES(jurusan_id_str), kurikulum_id = VALUES(kurikulum_id),
                kurikulum_id_str = VALUES(kurikulum_id_str), id_ruang = VALUES(id_ruang),
                id_ruang_str = VALUES(id_ruang_str), moving_class = VALUES(moving_class),
                jenis_rombel = VALUES(jenis_rombel), jenis_rombel_str = VALUES(jenis_rombel_str),
                is_sync_to_server = VALUES(is_sync_to_server),
                updated_at = NOW()
            ");

            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $this->connection->error);
            }

            // Extract dan map field values
            $rombongan_belajar_id = $data['rombongan_belajar_id'] ?? '';
            $nama = $data['nama'] ?? '';
            $tingkat_pendidikan_id = $data['tingkat_pendidikan_id'] ?? null;
            $tingkat_pendidikan_id_str = $data['tingkat_pendidikan_id_str'] ?? null;
            $semester_id = $data['semester_id'] ?? null;
            $ptk_id = $data['ptk_id'] ?? null;
            $ptk_id_str = $data['ptk_id_str'] ?? null;
            $jurusan_id = $data['jurusan_id'] ?? null;
            $jurusan_id_str = $data['jurusan_id_str'] ?? null;
            $kurikulum_id = $data['kurikulum_id'] ?? null;
            $kurikulum_id_str = $data['kurikulum_id_str'] ?? null;
            $id_ruang = $data['id_ruang'] ?? null;
            $id_ruang_str = $data['id_ruang_str'] ?? null;
            $moving_class = $data['moving_class'] ?? null;
            $jenis_rombel = $data['jenis_rombel'] ?? null;
            $jenis_rombel_str = $data['jenis_rombel_str'] ?? null;
            $is_sync_to_server = (int)($data['is_sync_to_server'] ?? 0);

            $stmt->bind_param(
                'ssssssssssssssssi',
                $rombongan_belajar_id,
                $nama,
                $tingkat_pendidikan_id,
                $tingkat_pendidikan_id_str,
                $semester_id,
                $ptk_id,
                $ptk_id_str,
                $jurusan_id,
                $jurusan_id_str,
                $kurikulum_id,
                $kurikulum_id_str,
                $id_ruang,
                $id_ruang_str,
                $moving_class,
                $jenis_rombel,
                $jenis_rombel_str,
                $is_sync_to_server
            );

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            // Process nested anggota_rombel if present
            $anggota_count = 0;
            if (!empty($data['anggota_rombel']) && is_array($data['anggota_rombel'])) {
                $anggota_stmt = $this->connection->prepare("\n                    INSERT INTO sync_anggota_rombel (
                        anggota_rombel_id, rombongan_belajar_id, peserta_didik_id,
                        jenis_pendaftaran_id, jenis_pendaftaran_id_str, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE
                        peserta_didik_id = VALUES(peserta_didik_id),
                        jenis_pendaftaran_id = VALUES(jenis_pendaftaran_id),
                        jenis_pendaftaran_id_str = VALUES(jenis_pendaftaran_id_str),
                        updated_at = NOW()
                ");

                if ($anggota_stmt) {
                    foreach ($data['anggota_rombel'] as $ang) {
                        $anggota_id = $ang['anggota_rombel_id'] ?? '';
                        $peserta_id = $ang['peserta_didik_id'] ?? '';
                        $jenis_pendaftaran_id = $ang['jenis_pendaftaran_id'] ?? null;
                        $jenis_pendaftaran_id_str = $ang['jenis_pendaftaran_id_str'] ?? null;

                        $anggota_stmt->bind_param('sssss', $anggota_id, $rombongan_belajar_id, $peserta_id, $jenis_pendaftaran_id, $jenis_pendaftaran_id_str);
                        if (!$anggota_stmt->execute()) {
                            ApiLogger::logError('RombelHandler::save - anggota_rombel insert', $anggota_stmt->error, ['data' => $ang]);
                        } else {
                            $anggota_count++;
                        }
                    }
                    $anggota_stmt->close();
                } else {
                    ApiLogger::logError('RombelHandler::save', 'Prepare failed for anggota_rombel: ' . $this->connection->error);
                }
            }

            // Process nested pembelajaran if present
            $pembelajaran_count = 0;
            if (!empty($data['pembelajaran']) && is_array($data['pembelajaran'])) {
                $p_stmt = $this->connection->prepare("\n                    INSERT INTO sync_pembelajaran (
                        pembelajaran_id, rombongan_belajar_id, mata_pelajaran_id, mata_pelajaran_id_str,
                        ptk_terdaftar_id, ptk_id, nama_mata_pelajaran, induk_pembelajaran_id,
                        jam_mengajar_per_minggu, status_di_kurikulum, status_di_kurikulum_str, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE
                        mata_pelajaran_id = VALUES(mata_pelajaran_id),
                        mata_pelajaran_id_str = VALUES(mata_pelajaran_id_str),
                        ptk_terdaftar_id = VALUES(ptk_terdaftar_id),
                        ptk_id = VALUES(ptk_id),
                        nama_mata_pelajaran = VALUES(nama_mata_pelajaran),
                        induk_pembelajaran_id = VALUES(induk_pembelajaran_id),
                        jam_mengajar_per_minggu = VALUES(jam_mengajar_per_minggu),
                        status_di_kurikulum = VALUES(status_di_kurikulum),
                        status_di_kurikulum_str = VALUES(status_di_kurikulum_str),
                        updated_at = NOW()
                ");

                if ($p_stmt) {
                    foreach ($data['pembelajaran'] as $p) {
                        $pembelajaran_id = $p['pembelajaran_id'] ?? '';
                        $mata_pelajaran_id = $p['mata_pelajaran_id'] ?? null;
                        $mata_pelajaran_id_str = $p['mata_pelajaran_id_str'] ?? null;
                        $ptk_terdaftar_id = $p['ptk_terdaftar_id'] ?? null;
                        $ptk_id = $p['ptk_id'] ?? null;
                        $nama_mapel = $p['nama_mata_pelajaran'] ?? null;
                        $induk_id = $p['induk_pembelajaran_id'] ?? null;
                        $jam = $p['jam_mengajar_per_minggu'] ?? null;
                        $status_kurikulum = $p['status_di_kurikulum'] ?? null;
                        $status_kurikulum_str = $p['status_di_kurikulum_str'] ?? null;

                        $p_stmt->bind_param('sssssssssss', $pembelajaran_id, $rombongan_belajar_id, $mata_pelajaran_id, $mata_pelajaran_id_str, $ptk_terdaftar_id, $ptk_id, $nama_mapel, $induk_id, $jam, $status_kurikulum, $status_kurikulum_str);
                        if (!$p_stmt->execute()) {
                            ApiLogger::logError('RombelHandler::save - pembelajaran insert', $p_stmt->error, ['data' => $p]);
                        } else {
                            $pembelajaran_count++;
                        }
                    }
                    $p_stmt->close();
                } else {
                    ApiLogger::logError('RombelHandler::save', 'Prepare failed for pembelajaran: ' . $this->connection->error);
                }
            }

            return [
                'success' => true,
                'affected_rows' => $affected_rows,
                'rombongan_belajar_id' => $rombongan_belajar_id,
                'nama' => $nama,
                'anggota_count' => $anggota_count,
                'pembelajaran_count' => $pembelajaran_count
            ];
        } catch (Exception $e) {
            ApiLogger::logError('RombelHandler::save', $e->getMessage(), [
                'rombel_data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Batch save rombel data
     */
    public function batchSave($data_array)
    {
        $stats = [
            'total' => count($data_array),
            'success' => 0,
            'errors' => []
        ];

        try {
            $this->beginTransaction();

            foreach ($data_array as $data) {
                try {
                    $this->save($data);
                    $stats['success']++;
                } catch (Exception $e) {
                    $stats['errors'][] = $e->getMessage();
                }
            }

            $this->commitTransaction();

            ApiLogger::logSyncActivity(
                'rombel',
                'success',
                $stats['total'],
                "Batch save completed",
                $stats
            );
        } catch (Exception $e) {
            $this->rollbackTransaction();
            ApiLogger::logError('RombelHandler::batchSave', $e->getMessage());
            throw $e;
        }

        return $stats;
    }

    /**
     * Get rombel by ID
     */
    public function getById($rombongan_belajar_id)
    {
        try {
            $stmt = $this->connection->prepare("SELECT * FROM sync_rombongan_belajar WHERE rombongan_belajar_id = ?");
            $stmt->bind_param('s', $rombongan_belajar_id);
            $stmt->execute();

            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();

            return $data;
        } catch (Exception $e) {
            ApiLogger::logError('RombelHandler::getById', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get rombel by nama
     */
    public function getByNama($nama)
    {
        try {
            $stmt = $this->connection->prepare("SELECT * FROM sync_rombongan_belajar WHERE nama = ?");
            $stmt->bind_param('s', $nama);
            $stmt->execute();

            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();

            return $data;
        } catch (Exception $e) {
            ApiLogger::logError('RombelHandler::getByNama', $e->getMessage());
            throw $e;
        }
    }
}
