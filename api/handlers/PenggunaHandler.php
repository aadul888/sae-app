<?php
/**
 * SAE v4 API - Pengguna Data Handler
 * 
 * Handler untuk menangani data pengguna
 */

require_once __DIR__ . '/BaseHandler.php';

class PenggunaHandler extends BaseHandler {
    
    protected function getTableName() {
        return 'sync_pengguna';
    }
    
    /**
     * Save pengguna data
     */
    public function save($data) {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO sync_pengguna (
                    pengguna_id, sekolah_id, username, password, nama, peran_id_str,
                    ptk_id, peserta_didik_id, no_telepon, no_hp, alamat, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                sekolah_id = VALUES(sekolah_id), username = VALUES(username),
                password = VALUES(password), nama = VALUES(nama),
                peran_id_str = VALUES(peran_id_str), ptk_id = VALUES(ptk_id),
                peserta_didik_id = VALUES(peserta_didik_id),
                no_telepon = VALUES(no_telepon), no_hp = VALUES(no_hp), alamat = VALUES(alamat),
                updated_at = NOW()
            ");
            
            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $this->connection->error);
            }
            
            // Extract dan map field values
            $pengguna_id = $data['pengguna_id'] ?? '';
            $sekolah_id = $data['sekolah_id'] ?? null;
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';
            $nama = $data['nama'] ?? '';
            $peran_id_str = $data['peran_id_str'] ?? '';
            $ptk_id = $data['ptk_id'] ?? null;
            $peserta_didik_id = $data['peserta_didik_id'] ?? null;
            $no_telepon = $data['no_telepon'] ?? null;
            $no_hp = $data['no_hp'] ?? null;
            $alamat = $data['alamat'] ?? null;
            
            $stmt->bind_param('sssssssssss',
                $pengguna_id, $sekolah_id, $username, $password, $nama, $peran_id_str,
                $ptk_id, $peserta_didik_id, $no_telepon, $no_hp, $alamat
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            
            return [
                'success' => true,
                'affected_rows' => $affected_rows,
                'pengguna_id' => $pengguna_id,
                'username' => $username,
                'nama' => $nama
            ];
            
        } catch (Exception $e) {
            ApiLogger::logError('PenggunaHandler::save', $e->getMessage(), [
                'pengguna_data' => $data
            ]);
            throw $e;
        }
    }
    
    /**
     * Batch save pengguna data
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
            
            ApiLogger::logSyncActivity('pengguna', 'success', $stats['total'], 
                "Batch save completed", $stats
            );
            
        } catch (Exception $e) {
            $this->rollbackTransaction();
            ApiLogger::logError('PenggunaHandler::batchSave', $e->getMessage());
            throw $e;
        }
        
        return $stats;
    }
    
    /**
     * Get pengguna by username
     */
    public function getByUsername($username) {
        try {
            $stmt = $this->connection->prepare("SELECT * FROM sync_pengguna WHERE username = ?");
            $stmt->bind_param('s', $username);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            
            return $data;
            
        } catch (Exception $e) {
            ApiLogger::logError('PenggunaHandler::getByUsername', $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get pengguna by PTK ID
     */
    public function getByPtkId($ptk_id) {
        try {
            $stmt = $this->connection->prepare("SELECT * FROM sync_pengguna WHERE ptk_id = ?");
            $stmt->bind_param('s', $ptk_id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            
            return $data;
            
        } catch (Exception $e) {
            ApiLogger::logError('PenggunaHandler::getByPtkId', $e->getMessage());
            throw $e;
        }
    }
}