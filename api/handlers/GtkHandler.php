<?php
/**
 * SAE v4 API - GTK Data Handler
 * 
 * Handler untuk menangani data GTK (Guru dan Tenaga Kependidikan)
 */

require_once __DIR__ . '/BaseHandler.php';

class GtkHandler extends BaseHandler {
    
    protected function getTableName() {
        return 'sync_gtk';
    }
    
    /**
     * Save GTK data
     */
    public function save($data) {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO sync_gtk (
                    tahun_ajaran_id, ptk_terdaftar_id, ptk_id, ptk_induk, tanggal_surat_tugas,
                    nama, jenis_kelamin, tempat_lahir, tanggal_lahir, agama_id, agama_id_str,
                    nuptk, nik, jenis_ptk_id, jenis_ptk_id_str, jabatan_ptk_id, jabatan_ptk_id_str,
                    status_kepegawaian_id, status_kepegawaian_id_str, nip, pendidikan_terakhir,
                    bidang_studi_terakhir, pangkat_golongan_terakhir,
                    rwy_pend_formal, rwy_kepangkatan,
                    is_sync_to_server_ptk_terdaftar, is_sync_to_server_ptk,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                nama = VALUES(nama), jenis_kelamin = VALUES(jenis_kelamin), tempat_lahir = VALUES(tempat_lahir),
                tanggal_lahir = VALUES(tanggal_lahir), agama_id = VALUES(agama_id), agama_id_str = VALUES(agama_id_str),
                nuptk = VALUES(nuptk), nik = VALUES(nik), jenis_ptk_id = VALUES(jenis_ptk_id), 
                jenis_ptk_id_str = VALUES(jenis_ptk_id_str), jabatan_ptk_id = VALUES(jabatan_ptk_id),
                jabatan_ptk_id_str = VALUES(jabatan_ptk_id_str), status_kepegawaian_id = VALUES(status_kepegawaian_id),
                status_kepegawaian_id_str = VALUES(status_kepegawaian_id_str), nip = VALUES(nip),
                pendidikan_terakhir = VALUES(pendidikan_terakhir), bidang_studi_terakhir = VALUES(bidang_studi_terakhir),
                pangkat_golongan_terakhir = VALUES(pangkat_golongan_terakhir), tanggal_surat_tugas = VALUES(tanggal_surat_tugas),
                rwy_pend_formal = VALUES(rwy_pend_formal), rwy_kepangkatan = VALUES(rwy_kepangkatan),
                is_sync_to_server_ptk_terdaftar = VALUES(is_sync_to_server_ptk_terdaftar),
                is_sync_to_server_ptk = VALUES(is_sync_to_server_ptk),
                updated_at = NOW()
            ");
            
            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $this->connection->error);
            }
            
            // Extract dan map field values
            $tahun_ajaran_id = $data['tahun_ajaran_id'] ?? null;
            $ptk_terdaftar_id = $data['ptk_terdaftar_id'] ?? '';
            $ptk_id = $data['ptk_id'] ?? null;
            $ptk_induk = $data['ptk_induk'] ?? null;
            $tanggal_surat_tugas = !empty($data['tanggal_surat_tugas']) ? $data['tanggal_surat_tugas'] : null;
            $nama = $data['nama'] ?? '';
            $jenis_kelamin = $data['jenis_kelamin'] ?? null;
            $tempat_lahir = $data['tempat_lahir'] ?? null;
            $tanggal_lahir = !empty($data['tanggal_lahir']) ? $data['tanggal_lahir'] : null;
            $agama_id = $data['agama_id'] ?? null;
            $agama_id_str = $data['agama_id_str'] ?? null;
            $nuptk = $data['nuptk'] ?? null;
            $nik = $data['nik'] ?? null;
            $jenis_ptk_id = $data['jenis_ptk_id'] ?? null;
            $jenis_ptk_id_str = $data['jenis_ptk_id_str'] ?? null;
            $jabatan_ptk_id = $data['jabatan_ptk_id'] ?? null;
            $jabatan_ptk_id_str = $data['jabatan_ptk_id_str'] ?? null;
            $status_kepegawaian_id = $data['status_kepegawaian_id'] ?? null;
            $status_kepegawaian_id_str = $data['status_kepegawaian_id_str'] ?? null;
            $nip = $data['nip'] ?? null;
            $pendidikan_terakhir = $data['pendidikan_terakhir'] ?? null;
            $bidang_studi_terakhir = $data['bidang_studi_terakhir'] ?? null;
            $pangkat_golongan_terakhir = $data['pangkat_golongan_terakhir'] ?? null;
            // Riwayat pendidikan formal dan kepangkatan (JSON array dari Dapodik)
            $rwy_pend_formal = isset($data['rwy_pend_formal'])
                ? (is_array($data['rwy_pend_formal']) ? json_encode($data['rwy_pend_formal']) : (string)$data['rwy_pend_formal'])
                : null;
            $rwy_kepangkatan = isset($data['rwy_kepangkatan'])
                ? (is_array($data['rwy_kepangkatan']) ? json_encode($data['rwy_kepangkatan']) : (string)$data['rwy_kepangkatan'])
                : null;
            $is_sync_to_server_ptk_terdaftar = isset($data['is_sync_to_server_ptk_terdaftar']) ? (int)$data['is_sync_to_server_ptk_terdaftar'] : 0;
            $is_sync_to_server_ptk = isset($data['is_sync_to_server_ptk']) ? (int)$data['is_sync_to_server_ptk'] : 0;
            
            $stmt->bind_param('ssssssssssssssssssssssssssi',
                $tahun_ajaran_id, $ptk_terdaftar_id, $ptk_id, $ptk_induk, $tanggal_surat_tugas,
                $nama, $jenis_kelamin, $tempat_lahir, $tanggal_lahir, $agama_id, $agama_id_str,
                $nuptk, $nik, $jenis_ptk_id, $jenis_ptk_id_str, $jabatan_ptk_id, $jabatan_ptk_id_str,
                $status_kepegawaian_id, $status_kepegawaian_id_str, $nip, $pendidikan_terakhir,
                $bidang_studi_terakhir, $pangkat_golongan_terakhir,
                $rwy_pend_formal, $rwy_kepangkatan,
                $is_sync_to_server_ptk_terdaftar, $is_sync_to_server_ptk
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            
            return [
                'success' => true,
                'affected_rows' => $affected_rows,
                'ptk_id' => $ptk_id,
                'nama' => $nama
            ];
            
        } catch (Exception $e) {
            ApiLogger::logError('GtkHandler::save', $e->getMessage(), [
                'gtk_data' => $data
            ]);
            throw $e;
        }
    }
    
    /**
     * Batch save GTK data
     */
    public function batchSave($data_array) {
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
            
            ApiLogger::logSyncActivity('gtk', 'success', $stats['total'], 
                "Batch save completed", $stats
            );
            
        } catch (Exception $e) {
            $this->rollbackTransaction();
            ApiLogger::logError('GtkHandler::batchSave', $e->getMessage());
            throw $e;
        }
        
        return $stats;
    }

    /**
     * Smart sync GTK:
     * - upsert data GTK terbaru dari Dapodik
     * - hapus GTK yang sudah tidak ada di Dapodik dari tabel sync_gtk
     * - nonaktifkan admin terkait ptk_id yang tidak lagi ada di sync_gtk
     */
    public function smartSync($data_array) {
        $stats = [
            'total' => count($data_array),
            'inserted' => 0,
            'updated' => 0,
            'deleted' => 0,
            'deactivated_admin' => 0,
            'errors' => []
        ];

        try {
            $this->beginTransaction();

            $incoming_ids = [];
            foreach ($data_array as $data) {
                $ptk_id = trim((string)($data['ptk_id'] ?? ''));
                if ($ptk_id !== '') {
                    $incoming_ids[] = $ptk_id;
                }
            }
            $incoming_ids = array_values(array_unique($incoming_ids));

            if (empty($incoming_ids)) {
                throw new Exception('Smart sync GTK dibatalkan: tidak ada ptk_id valid dari Dapodik.');
            }

            foreach ($data_array as $data) {
                try {
                    $before_exists = false;
                    $check_stmt = $this->connection->prepare("SELECT 1 FROM sync_gtk WHERE ptk_id = ? LIMIT 1");
                    if ($check_stmt) {
                        $ptk_id = trim((string)($data['ptk_id'] ?? ''));
                        $check_stmt->bind_param('s', $ptk_id);
                        $check_stmt->execute();
                        $check_res = $check_stmt->get_result();
                        $before_exists = ($check_res && $check_res->num_rows > 0);
                        $check_stmt->close();
                    }

                    $this->save($data);
                    if ($before_exists) {
                        $stats['updated']++;
                    } else {
                        $stats['inserted']++;
                    }
                } catch (Exception $e) {
                    $stats['errors'][] = $e->getMessage();
                    ApiLogger::logError('GtkHandler::smartSync.save', $e->getMessage(), ['gtk_data' => $data]);
                }
            }

            // Hapus data sync_gtk yang tidak lagi ada di payload Dapodik terbaru
            $placeholders = implode(',', array_fill(0, count($incoming_ids), '?'));
            $delete_sql = "DELETE FROM sync_gtk WHERE ptk_id IS NOT NULL AND TRIM(ptk_id) <> '' AND TRIM(ptk_id) NOT IN ($placeholders)";
            $delete_stmt = $this->connection->prepare($delete_sql);
            if ($delete_stmt) {
                $types = str_repeat('s', count($incoming_ids));
                $delete_stmt->bind_param($types, ...$incoming_ids);
                if ($delete_stmt->execute()) {
                    $stats['deleted'] = (int)$delete_stmt->affected_rows;
                }
                $delete_stmt->close();
            }

            // Nonaktifkan admin yang ptk_id-nya sudah tidak ada lagi di sync_gtk
            $deactivate_sql = "
                UPDATE admin a
                LEFT JOIN sync_gtk g ON TRIM(COALESCE(g.ptk_id, '')) = TRIM(COALESCE(a.ptk_id, ''))
                SET
                    a.active = 'N',
                    a.status = 'Offline',
                    a.sync_status = 'manual',
                    a.updated_at = NOW()
                WHERE
                    a.ptk_id IS NOT NULL
                    AND TRIM(a.ptk_id) <> ''
                    AND g.ptk_id IS NULL
                    AND UPPER(TRIM(COALESCE(a.active, 'N'))) = 'Y'
            ";
            if ($this->connection->query($deactivate_sql)) {
                $stats['deactivated_admin'] = (int)$this->connection->affected_rows;
            }

            $this->commitTransaction();

            ApiLogger::logSyncActivity(
                'gtk',
                'success',
                $stats['total'],
                "Smart sync GTK completed",
                $stats
            );
        } catch (Exception $e) {
            $this->rollbackTransaction();
            ApiLogger::logError('GtkHandler::smartSync', $e->getMessage());
            throw $e;
        }

        return $stats;
    }
    
    /**
     * Get GTK by PTK ID
     */
    public function getByPtkId($ptk_id) {
        try {
            $stmt = $this->connection->prepare("SELECT * FROM sync_gtk WHERE ptk_id = ?");
            $stmt->bind_param('s', $ptk_id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            
            return $data;
            
        } catch (Exception $e) {
            ApiLogger::logError('GtkHandler::getByPtkId', $e->getMessage());
            throw $e;
        }
    }
}