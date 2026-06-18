<?php
/**
 * SAE v4 API - Sekolah Data Handler
 * 
 * Handler untuk menangani data sekolah
 */

require_once __DIR__ . '/BaseHandler.php';

class SekolahHandler extends BaseHandler {
    
    protected function getTableName() {
        return 'sync_sekolah';
    }
    
    /**
     * Save sekolah data
     */
    public function save($data) {
        try {
            // Debug: log data yang diterima
            ApiLogger::logRequest('sekolah_save', 'info', 
                "Saving sekolah: " . ($data['nama'] ?? 'Unknown') . " (NPSN: " . ($data['npsn'] ?? 'N/A') . ")"
            );
            
            $stmt = $this->connection->prepare("
                INSERT INTO sync_sekolah (
                    sekolah_id, nama, nss, npsn, bentuk_pendidikan_id, bentuk_pendidikan_id_str, 
                    status_sekolah, status_sekolah_str, alamat_jalan, rt, rw, dusun, 
                    desa_kelurahan, kode_wilayah, kode_pos, lintang, bujur, nomor_telepon, 
                    nomor_fax, email, website, is_sks, kecamatan, kabupaten_kota, provinsi,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                nama = VALUES(nama), nss = VALUES(nss), bentuk_pendidikan_id = VALUES(bentuk_pendidikan_id),
                bentuk_pendidikan_id_str = VALUES(bentuk_pendidikan_id_str), status_sekolah = VALUES(status_sekolah),
                status_sekolah_str = VALUES(status_sekolah_str), alamat_jalan = VALUES(alamat_jalan),
                rt = VALUES(rt), rw = VALUES(rw), dusun = VALUES(dusun),
                desa_kelurahan = VALUES(desa_kelurahan), kode_wilayah = VALUES(kode_wilayah),
                kode_pos = VALUES(kode_pos), lintang = VALUES(lintang), bujur = VALUES(bujur),
                nomor_telepon = VALUES(nomor_telepon), nomor_fax = VALUES(nomor_fax), 
                email = VALUES(email), website = VALUES(website), is_sks = VALUES(is_sks),
                kecamatan = VALUES(kecamatan), kabupaten_kota = VALUES(kabupaten_kota),
                provinsi = VALUES(provinsi), updated_at = NOW()
            ");
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }
            
            // Map data fields dengan tipe yang tepat
            $sekolah_id = (string)($data['sekolah_id'] ?? '');
            $nama = (string)($data['nama'] ?? '');
            $nss = (string)($data['nss'] ?? '');
            $npsn = (string)($data['npsn'] ?? '');
            $bentuk_pendidikan_id = $data['bentuk_pendidikan_id'] ? (string)$data['bentuk_pendidikan_id'] : null;
            $bentuk_pendidikan_id_str = (string)($data['bentuk_pendidikan_id_str'] ?? '');
            $status_sekolah = $data['status_sekolah'] ? (string)$data['status_sekolah'] : null;
            $status_sekolah_str = (string)($data['status_sekolah_str'] ?? '');
            $alamat_jalan = (string)($data['alamat_jalan'] ?? '');
            $rt = (string)($data['rt'] ?? '');
            $rw = (string)($data['rw'] ?? '');
            $dusun = (string)($data['dusun'] ?? $data['nama_dusun'] ?? '');
            $desa_kelurahan = (string)($data['desa_kelurahan'] ?? '');
            $kode_wilayah = (string)($data['kode_wilayah'] ?? '');
            $kode_pos = (string)($data['kode_pos'] ?? '');
            $lintang = $data['lintang'] ? (float)$data['lintang'] : null;
            $bujur = $data['bujur'] ? (float)$data['bujur'] : null;
            $nomor_telepon = (string)($data['nomor_telepon'] ?? '');
            $nomor_fax = $data['nomor_fax'] ? (string)$data['nomor_fax'] : null;
            $email = (string)($data['email'] ?? '');
            $website = (string)($data['website'] ?? '');
            $is_sks = isset($data['is_sks']) ? (int)$data['is_sks'] : 0;
            $kecamatan = (string)($data['kecamatan'] ?? '');
            $kabupaten_kota = (string)($data['kabupaten_kota'] ?? '');
            $provinsi = (string)($data['provinsi'] ?? '');
            
            $stmt->bind_param('ssssssssssssssddsssssisss',
                $sekolah_id, $nama, $nss, $npsn, $bentuk_pendidikan_id, $bentuk_pendidikan_id_str,
                $status_sekolah, $status_sekolah_str, $alamat_jalan, $rt, $rw, $dusun,
                $desa_kelurahan, $kode_wilayah, $kode_pos, $lintang, $bujur, $nomor_telepon,
                $nomor_fax, $email, $website, $is_sks, $kecamatan, $kabupaten_kota, $provinsi
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            
            ApiLogger::logRequest('sekolah_save', 'success', 
                "Sekolah data saved successfully: $nama (NPSN: $npsn)"
            );
            
            return [
                'success' => true,
                'affected_rows' => $affected_rows,
                'sekolah_id' => $sekolah_id,
                'nama' => $nama,
                'npsn' => $npsn
            ];
            
        } catch (Exception $e) {
            ApiLogger::logError('SekolahHandler::save', $e->getMessage(), [
                'sekolah_data' => $data
            ]);
            throw $e;
        }
    }
    
    /**
     * Get sekolah by ID
     */
    public function getById($sekolah_id) {
        try {
            $stmt = $this->connection->prepare("SELECT * FROM sync_sekolah WHERE sekolah_id = ?");
            $stmt->bind_param('s', $sekolah_id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            
            return $data;
            
        } catch (Exception $e) {
            ApiLogger::logError('SekolahHandler::getById', $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get sekolah by NPSN
     */
    public function getByNpsn($npsn) {
        try {
            $stmt = $this->connection->prepare("SELECT * FROM sync_sekolah WHERE npsn = ?");
            $stmt->bind_param('s', $npsn);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            
            return $data;
            
        } catch (Exception $e) {
            ApiLogger::logError('SekolahHandler::getByNpsn', $e->getMessage());
            throw $e;
        }
    }
}