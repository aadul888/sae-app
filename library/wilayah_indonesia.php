<?php

if (!function_exists('sae_get_wilayah_cache_dir')) {
    function sae_get_wilayah_cache_dir()
    {
        $cacheDir = dirname(__DIR__) . '/content/cache/wilayah-indonesia';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }

        return $cacheDir;
    }
}

if (!function_exists('sae_fetch_remote_json_cached')) {
    function sae_fetch_remote_json_cached($url, $cacheKey, $ttl = 2592000)
    {
        $cacheDir = sae_get_wilayah_cache_dir();
        $cacheFile = $cacheDir . '/' . preg_replace('/[^a-z0-9_\-]/i', '_', $cacheKey) . '.json';

        if (is_file($cacheFile) && (time() - filemtime($cacheFile) <= $ttl)) {
            $cached = @file_get_contents($cacheFile);
            $decoded = json_decode((string)$cached, true);
            if (is_array($decoded)) {
                return ['success' => true, 'data' => $decoded, 'source' => 'cache'];
            }
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 20,
                'header' => "User-Agent: SAE/1.0\r\nAccept: application/json\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        if ($body !== false) {
            $decoded = json_decode((string)$body, true);
            if (is_array($decoded)) {
                @file_put_contents($cacheFile, json_encode($decoded, JSON_UNESCAPED_UNICODE));
                return ['success' => true, 'data' => $decoded, 'source' => 'remote'];
            }
        }

        if (is_file($cacheFile)) {
            $cached = @file_get_contents($cacheFile);
            $decoded = json_decode((string)$cached, true);
            if (is_array($decoded)) {
                return ['success' => true, 'data' => $decoded, 'source' => 'stale-cache'];
            }
        }

        return ['success' => false, 'data' => [], 'source' => 'unavailable'];
    }
}

if (!function_exists('sae_get_wilayah_reference')) {
    function sae_get_wilayah_reference($level, $parentId = '')
    {
        $baseUrl = 'https://www.emsifa.com/api-wilayah-indonesia/api';
        $parentId = trim((string)$parentId);

        switch ($level) {
            case 'provinces':
                $url = $baseUrl . '/provinces.json';
                $cacheKey = 'provinces';
                break;

            case 'regencies':
                if ($parentId === '') {
                    return ['success' => false, 'data' => [], 'source' => 'invalid-parent'];
                }
                $url = $baseUrl . '/regencies/' . rawurlencode($parentId) . '.json';
                $cacheKey = 'regencies_' . $parentId;
                break;

            case 'districts':
                if ($parentId === '') {
                    return ['success' => false, 'data' => [], 'source' => 'invalid-parent'];
                }
                $url = $baseUrl . '/districts/' . rawurlencode($parentId) . '.json';
                $cacheKey = 'districts_' . $parentId;
                break;

            case 'villages':
                if ($parentId === '') {
                    return ['success' => false, 'data' => [], 'source' => 'invalid-parent'];
                }
                $url = $baseUrl . '/villages/' . rawurlencode($parentId) . '.json';
                $cacheKey = 'villages_' . $parentId;
                break;

            default:
                return ['success' => false, 'data' => [], 'source' => 'invalid-level'];
        }

        return sae_fetch_remote_json_cached($url, $cacheKey);
    }
}

if (!function_exists('sae_ensure_user_region_columns')) {
    function sae_ensure_user_region_columns($connection)
    {
        if (!($connection instanceof mysqli)) {
            return false;
        }

        $existing = [];
        $columns = $connection->query('SHOW COLUMNS FROM `user`');
        if ($columns instanceof mysqli_result) {
            while ($column = $columns->fetch_assoc()) {
                $existing[] = $column['Field'];
            }
            $columns->free();
        }

        $definitions = [
            'provinsi_id' => "ALTER TABLE `user` ADD COLUMN `provinsi_id` varchar(10) DEFAULT NULL AFTER `rw`",
            'provinsi' => "ALTER TABLE `user` ADD COLUMN `provinsi` varchar(100) DEFAULT NULL AFTER `provinsi_id`",
            'kabupaten_kota_id' => "ALTER TABLE `user` ADD COLUMN `kabupaten_kota_id` varchar(10) DEFAULT NULL AFTER `provinsi`",
            'kabupaten_kota' => "ALTER TABLE `user` ADD COLUMN `kabupaten_kota` varchar(100) DEFAULT NULL AFTER `kabupaten_kota_id`",
            'kecamatan_id' => "ALTER TABLE `user` ADD COLUMN `kecamatan_id` varchar(10) DEFAULT NULL AFTER `kabupaten_kota`",
            'desa_id' => "ALTER TABLE `user` ADD COLUMN `desa_id` varchar(10) DEFAULT NULL AFTER `desa`",
        ];

        foreach ($definitions as $column => $sql) {
            if (in_array($column, $existing, true)) {
                continue;
            }

            @$connection->query($sql);
        }

        return true;
    }
}