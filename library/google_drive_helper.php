<?php
/**
 * Google Drive Helper — untuk mengintegrasikan Google Drive API
 * menggunakan Service Account (JSON credential) dengan library legacy google-client v1.
 *
 * Dependensi:
 * - library/google-client/Google_Client.php (legacy v1)
 * - library/google-client/contrib/Google_DriveService.php
 * - File JSON Service Account yang diupload via surat-setting
 *
 * @author SAEV5
 */

// Pastikan library google-client sudah di-load
$googleClientPath = __DIR__ . '/google-client/Google_Client.php';
if (!class_exists('Google_Client', false)) {
    if (file_exists($googleClientPath)) {
        set_include_path(__DIR__ . '/google-client' . PATH_SEPARATOR . get_include_path());
        require_once $googleClientPath;
        require_once __DIR__ . '/google-client/contrib/Google_DriveService.php';
    } else {
        throw new Exception('Google Client library tidak ditemukan di: ' . $googleClientPath);
    }
}

class GoogleDriveHelper {

    /** @var Google_Client */
    private $client;

    /** @var Google_DriveService */
    private $driveService;

    /** @var string|null Pesan error terakhir */
    private $lastError = null;

    /** @var string Email Service Account */
    private $serviceAccountEmail = '';

    /** @var array|null Full credentials from JSON */
    private $creds = null;

    /** @var string|null OAuth Client ID */
    private $oauthClientId = null;

    /** @var string|null OAuth Client Secret */
    private $oauthClientSecret = null;

    /** @var string|null OAuth Refresh Token */
    private $oauthRefreshToken = null;

    /** @var string|null Access token hasil refresh OAuth */
    private $oauthAccessToken = null;

    /** @var int|null Waktu expiry access token OAuth */
    private $oauthTokenExpiry = null;

    /**
     * @param string $jsonFilePath Path absolut ke file JSON Service Account
     * @param string|array $scopes Scope yang diinginkan
     * @throws Exception
     */
    public function __construct($jsonFilePath, $scopes = null) {
        if (!file_exists($jsonFilePath)) {
            throw new Exception('File kredensial tidak ditemukan: ' . $jsonFilePath);
        }

        $jsonContent = file_get_contents($jsonFilePath);
        $creds = json_decode($jsonContent, true);
        if (!$creds || !isset($creds['client_email']) || !isset($creds['private_key'])) {
            throw new Exception('File JSON Service Account tidak valid.');
        }

        $this->serviceAccountEmail = $creds['client_email'];
        $this->creds = $creds;

        if ($scopes === null) {
            $scopes = array('https://www.googleapis.com/auth/drive.readonly');
        }

        // Inisialisasi Google Client
        $this->client = new Google_Client();
        // Gunakan array mode (default) — lebih mudah diakses

        // AssertionCredentials untuk Service Account
        $credentials = new Google_AssertionCredentials(
            $creds['client_email'],          // serviceAccountName
            $scopes,                          // scopes
            $creds['private_key'],            // privateKey (sudah termasuk \n literal)
            'notasecret'                      // privateKeyPassword (default)
        );

        $this->client->setAssertionCredentials($credentials);

        // Inisialisasi Drive Service
        $this->driveService = new Google_DriveService($this->client);
    }

    /**
     * Set OAuth 2.0 credentials (Client ID, Client Secret, Refresh Token)
     * untuk menggantikan Service Account authentication.
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $refreshToken
     * @return self
     */
    public function setOAuthCredentials($clientId, $clientSecret, $refreshToken) {
        $this->oauthClientId = $clientId;
        $this->oauthClientSecret = $clientSecret;
        $this->oauthRefreshToken = $refreshToken;
        return $this;
    }

    /**
     * Dapatkan access token dari OAuth refresh token.
     * Jika token sudah pernah di-refresh dan masih berlaku, gunakan cache.
     *
     * @return string|false
     */
    private function getOAuthAccessToken() {
        // Cek cache
        if ($this->oauthAccessToken && $this->oauthTokenExpiry && time() < $this->oauthTokenExpiry) {
            return $this->oauthAccessToken;
        }

        if (empty($this->oauthClientId) || empty($this->oauthClientSecret) || empty($this->oauthRefreshToken)) {
            return false;
        }

        $postData = http_build_query([
            'client_id' => $this->oauthClientId,
            'client_secret' => $this->oauthClientSecret,
            'refresh_token' => $this->oauthRefreshToken,
            'grant_type' => 'refresh_token',
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://oauth2.googleapis.com/token',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 15,
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->lastError = 'OAuth refresh token error (HTTP ' . $httpCode . ')';
            return false;
        }

        $data = json_decode($resp, true);
        if (!$data || empty($data['access_token'])) {
            $this->lastError = 'OAuth refresh response tidak valid.';
            return false;
        }

        $this->oauthAccessToken = $data['access_token'];
        // Set expiry (default 3600 detik, kurangi 60 detik untuk safety margin)
        $this->oauthTokenExpiry = time() + ($data['expires_in'] ?? 3600) - 60;

        return $this->oauthAccessToken;
    }

    /**
     * Ambil daftar file dari folder Drive berdasarkan folder ID.
     *
     * @param string $folderId ID folder Google Drive (bisa kosong untuk root)
     * @param int $maxResults Maksimal hasil
     * @return array Daftar file (title, id, mimeType, fileSize, modifiedDate)
     */
    public function listFiles($folderId = null, $maxResults = 50) {
        $params = array('maxResults' => $maxResults);

        if (!empty($folderId)) {
            // Query: file di dalam folder tertentu dan tidak di trash
            $params['q'] = "'" . $folderId . "' in parents and trashed=false";
        } else {
            $params['q'] = "trashed=false";
        }

        try {
            $result = $this->driveService->files->listFiles($params);
            $items = array();

            if ($result && isset($result['items'])) {
                foreach ($result['items'] as $file) {
                    $items[] = array(
                        'id'           => $file['id'],
                        'title'        => $file['title'],
                        'mimeType'     => $file['mimeType'],
                        'fileSize'     => isset($file['fileSize']) ? $file['fileSize'] : 0,
                        'modifiedDate' => isset($file['modifiedDate']) ? $file['modifiedDate'] : '',
                        'downloadUrl'  => isset($file['downloadUrl']) ? $file['downloadUrl'] : '',
                        'exportLinks'  => isset($file['exportLinks']) ? $file['exportLinks'] : array(),
                    );
                }
            }
            return $items;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Ambil metadata file berdasarkan ID.
     *
     * @param string $fileId
     * @return array|false
     */
    public function getFile($fileId) {
        try {
            $file = $this->driveService->files->get($fileId);
            if (!$file) return false;

            return array(
                'id'           => $file['id'],
                'title'        => $file['title'],
                'mimeType'     => $file['mimeType'],
                'description'  => isset($file['description']) ? $file['description'] : '',
                'fileSize'     => isset($file['fileSize']) ? $file['fileSize'] : 0,
                'modifiedDate' => isset($file['modifiedDate']) ? $file['modifiedDate'] : '',
                'downloadUrl'  => isset($file['downloadUrl']) ? $file['downloadUrl'] : '',
                'exportLinks'  => isset($file['exportLinks']) ? $file['exportLinks'] : array(),
            );
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Download konten file dari Google Drive.
     *
     * Untuk Google Docs (docs/sheets/slides), akan di-export ke format HTML.
     * Untuk file reguler (txt, html, dll), akan didownload langsung.
     *
     * @param string $fileId
     * @return string|false Konten file (text)
     */
    public function downloadContent($fileId) {
        try {
            // Dapatkan metadata file
            $file = $this->driveService->files->get($fileId);
            if (!$file) return false;

            $mimeType = $file['mimeType'];
            $downloadUrl = null;

            // Tentukan URL download
            if (isset($file['exportLinks'])) {
                // Google Docs: export ke HTML
                $exportLinks = $file['exportLinks'];
                // Prioritaskan text/html
                if (isset($exportLinks['text/html'])) {
                    $downloadUrl = $exportLinks['text/html'];
                } elseif (isset($exportLinks['text/plain'])) {
                    $downloadUrl = $exportLinks['text/plain'];
                }
            }

            if (!$downloadUrl && isset($file['downloadUrl'])) {
                // File reguler dengan direct download link
                $downloadUrl = $file['downloadUrl'];
            }

            if (!$downloadUrl) {
                $this->lastError = 'Tidak ada URL download untuk file ini. MIME: ' . $mimeType;
                return false;
            }

            // Download menggunakan HTTP autentikasi dari client
            return $this->httpGet($downloadUrl);

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Download konten file (Google Doc) sebagai plain text.
     * Alternatif dari downloadContent dengan export ke text/plain.
     *
     * @param string $fileId
     * @return string|false
     */
    public function downloadAsText($fileId) {
        try {
            $file = $this->driveService->files->get($fileId);
            if (!$file) return false;

            $exportLinks = isset($file['exportLinks']) ? $file['exportLinks'] : array();

            // Coba text/plain dulu
            $url = null;
            if (isset($exportLinks['text/plain'])) {
                $url = $exportLinks['text/plain'];
            } elseif (isset($exportLinks['text/html'])) {
                $url = $exportLinks['text/html'];
            } elseif (isset($file['downloadUrl'])) {
                $url = $file['downloadUrl'];
            }

            if (!$url) {
                $this->lastError = 'Tidak ada URL export/download.';
                return false;
            }

            return $this->httpGet($url);

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Download konten Google Doc langsung via export URL (tanpa files.get).
     *
     * Berguna ketika file tidak terdaftar di akun Service Account,
     * tetapi bisa diakses via link (Anyone with the link).
     *
     * Urutan percobaan:
     * 1) Export URL tanpa auth (docs.google.com — untuk "Anyone with the link")
     * 2) Export URL dengan Bearer token (via httpGet)
     *
     * @param string $fileId
     * @return string|false HTML content
     */
    public function downloadDirectExport($fileId) {
        $encodedId = rawurlencode($fileId);
        $exportUrl = 'https://docs.google.com/document/d/' . $encodedId . '/export?format=html';

        // 1) Coba tanpa auth — untuk file yg di-share "Anyone with the link"
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $exportUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ));
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200 && !empty($body)) {
            return $body;
        }

        // 2) Coba dengan Bearer token — OAuth or Service Account
        $result = $this->httpGet($exportUrl);
        if ($result !== false) {
            return $result;
        }

        // 3) Fallback: JWT langsung (hanya jika Service Account valid)
        if (!empty($this->creds) && isset($this->creds['private_key']) && strpos($this->creds['private_key'], 'dummy') === false) {
            $result = $this->directJwtDownload($exportUrl);
            if ($result !== false) {
                return $result;
            }
        }

        $this->lastError = 'downloadDirectExport gagal (HTTP ' . $httpCode . '). ' .
            'Pastikan dokumen sudah di-share "Anyone with the link".';
        return false;
    }

    /**
     * Download via JWT assertion langsung (tanpa library legacy).
     * Berguna ketika library legacy gagal SSL verification.
     *
     * @param string $url
     * @return string|false
     */
    private function directJwtDownload($url) {
        if (empty($this->creds) || empty($this->creds['client_email']) || empty($this->creds['private_key'])) {
            $this->lastError = 'Kredensial tidak tersedia untuk JWT langsung.';
            return false;
        }

        // Buat JWT
        $header = $this->base64UrlEncode('{"alg":"RS256","typ":"JWT"}');
        $now = time();
        $claim = $this->base64UrlEncode(json_encode([
            'iss' => $this->creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/drive.readonly',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ]));
        $data = $header . '.' . $claim;
        $sig = '';
        openssl_sign($data, $sig, $this->creds['private_key'], 'sha256WithRSAEncryption');
        $signature = $this->base64UrlEncode($sig);
        $jwt = $data . '.' . $signature;

        // Tukar JWT dengan access token
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => 'https://oauth2.googleapis.com/token',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 15,
        ));
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            $this->lastError = 'JWT token error (HTTP ' . $httpCode . '): ' . $error;
            return false;
        }

        $tokenData = json_decode($resp, true);
        if (empty($tokenData['access_token'])) {
            $this->lastError = 'JWT token response tidak valid.';
            return false;
        }
        $accessToken = $tokenData['access_token'];

        // Download dengan Bearer token (SSL verification dimatikan utk Windows)
        $ch2 = curl_init();
        curl_setopt_array($ch2, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $accessToken,
            ),
            CURLOPT_TIMEOUT => 30,
        ));
        $body = curl_exec($ch2);
        $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        $error2 = curl_error($ch2);
        curl_close($ch2);

        if ($error2 || $httpCode2 !== 200 || empty($body)) {
            $this->lastError = 'JWT download error (HTTP ' . $httpCode2 . '): ' . $error2;
            return false;
        }

        return $body;
    }

    /**
     * Base64 URL encode (tanpa padding, aman utk URL).
     *
     * @param string $data
     * @return string
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Ambil email Service Account.
     *
     * @return string
     */
    private function getServiceAccountEmail() {
        return $this->serviceAccountEmail ?: '(lihat file JSON)';
    }

    /**
     * Ekstrak konten HTML murni dari hasil export Google Doc.
     * Google Docs menambahkan banyak styling ekstra, fungsi ini
     * membersihkan dan mengambil bagian <body> saja.
     *
     * @param string $fileId
     * @return string|false HTML bersih
     */
    public function downloadCleanHtml($fileId) {
        $raw = $this->downloadContent($fileId);
        if ($raw === false) return false;

        // Deteksi apakah ini HTML (hasil export Google Docs)
        if (stripos($raw, '<html') !== false || stripos($raw, '<body') !== false) {
            // Ambil konten dari <body> ... </body>
            if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $raw, $m)) {
                return trim($m[1]);
            }
            // Jika tidak ada tag body, gunakan apa adanya
        }

        return $raw;
    }

    /**
     * Ekstrak konten HTML murni dari hasil export langsung (tanpa files.get).
     *
     * @param string $fileId
     * @return string|false HTML bersih
     */
    public function downloadCleanHtmlDirect($fileId) {
        $raw = $this->downloadDirectExport($fileId);
        if ($raw === false) return false;

        if (stripos($raw, '<html') !== false || stripos($raw, '<body') !== false) {
            if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $raw, $m)) {
                return trim($m[1]);
            }
        }

        return $raw;
    }

    /**
     * Lakukan HTTP GET dengan autentikasi Bearer token dari client.
     *
     * @param string $url
     * @return string|false Response body
     */
    private function httpGet($url) {
        // Dapatkan access token — OAuth 2.0 dulu, fallback Service Account
        $accessToken = $this->getWriteAccessToken();
        if (!$accessToken) return false;

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $accessToken,
                'User-Agent: Saev5-Google-Drive-Helper/1.0',
            ),
        ));

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->lastError = 'HTTP error: ' . $error;
            return false;
        }
        if ($httpCode !== 200) {
            $this->lastError = 'HTTP code: ' . $httpCode . ' — ' . substr($body, 0, 200);
            return false;
        }

        return $body;
    }

    /**
     * Dapatkan access token dari Google_Client (via assertion) atau OAuth 2.0.
     *
     * @return string|false
     */
    private function getAccessToken() {
        // Prioritaskan OAuth 2.0
        if (!empty($this->oauthRefreshToken)) {
            $token = $this->getOAuthAccessToken();
            if ($token) return $token;
        }

        try {
            // Trigger refresh token via assertion credentials
            $tokenData = $this->client->getAccessToken();
            if ($tokenData) {
                $token = json_decode($tokenData, true);
                if (isset($token['access_token'])) {
                    return $token['access_token'];
                }
            }

            // Jika belum ada, refresh dengan assertion
            if (method_exists($this->client, 'getAuth')) {
                $auth = $this->client->getAuth();
                if (method_exists($auth, 'refreshTokenWithAssertion')) {
                    @$auth->refreshTokenWithAssertion();
                }
            }

            $tokenData = $this->client->getAccessToken();
            if ($tokenData) {
                $token = json_decode($tokenData, true);
                if (isset($token['access_token'])) {
                    return $token['access_token'];
                }
            }

            // Fallback: JWT langsung jika library legacy gagal (SSL issue di Windows)
            $token = $this->directJwtGetToken();
            if ($token) {
                return $token;
            }

            $this->lastError = 'Gagal mendapatkan access token (legacy library & JWT fallback).';
            return false;
        } catch (Exception $e) {
            // Fallback: JWT langsung
            $token = $this->directJwtGetToken();
            if ($token) {
                return $token;
            }
            $this->lastError = 'Token error: ' . $e->getMessage() . ' & JWT fallback gagal.';
            return false;
        }
    }

    /**
     * Dapatkan access token via JWT assertion langsung (tanpa library legacy).
     *
     * @return string|false
     */
    private function directJwtGetToken() {
        return $this->directJwtGetTokenWithScope('https://www.googleapis.com/auth/drive.readonly');
    }

    /**
     * Dapatkan access token dengan scope Drive + Docs (write).
     * Menggunakan OAuth 2.0 jika refresh token tersedia, fallback ke Service Account.
     * @return string|false
     */
    public function getWriteAccessToken() {
        // Prioritaskan OAuth 2.0 jika refresh token tersedia
        if (!empty($this->oauthRefreshToken)) {
            $token = $this->getOAuthAccessToken();
            if ($token) return $token;
            // Jika OAuth gagal, fallback ke Service Account
        }

        return $this->directJwtGetTokenWithScope(
            'https://www.googleapis.com/auth/drive https://www.googleapis.com/auth/documents'
        );
    }

    /**
     * Dapatkan access token via JWT dengan scope tertentu.
     * @param string $scope
     * @return string|false
     */
    private function directJwtGetTokenWithScope($scope) {
        if (empty($this->creds) || empty($this->creds['client_email']) || empty($this->creds['private_key'])) {
            return false;
        }

        $header = $this->base64UrlEncode('{"alg":"RS256","typ":"JWT"}');
        $now = time();
        $claim = $this->base64UrlEncode(json_encode([
            'iss' => $this->creds['client_email'],
            'scope' => $scope,
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ]));
        $data = $header . '.' . $claim;
        $sig = '';
        openssl_sign($data, $sig, $this->creds['private_key'], 'sha256WithRSAEncryption');
        $signature = $this->base64UrlEncode($sig);
        $jwt = $data . '.' . $signature;

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => 'https://oauth2.googleapis.com/token',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 15,
        ));
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->lastError = 'JWT token error (HTTP ' . $httpCode . ')';
            return false;
        }
        $tokenData = json_decode($resp, true);
        return $tokenData['access_token'] ?? false;
    }

    /**
     * Lakukan API call ke Google Drive/Docs API dengan access token write.
     *
     * @param string $method HTTP method (GET, POST, PATCH, DELETE)
     * @param string $url Full URL API
     * @param array|null $data Data untuk dikirim (akan di-json_encode)
     * @return array|false Parsed response
     */
    public function apiCall($method, $url, $data = null) {
        $token = $this->getWriteAccessToken();
        if (!$token) return false;

        // Service Accounts perlu supportsAllDrives=true untuk semua operasi Drive API
        if (strpos($url, 'https://www.googleapis.com/drive/') === 0) {
            $separator = (strpos($url, '?') === false) ? '?' : '&';
            $url .= $separator . 'supportsAllDrives=true';
        }

        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ),
            CURLOPT_TIMEOUT => 30,
        );

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            if ($data !== null) {
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        } elseif ($method === 'PATCH') {
            $options[CURLOPT_CUSTOMREQUEST] = 'PATCH';
            if ($data !== null) {
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        } elseif ($method === 'DELETE') {
            $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        } else {
            // GET
            $options[CURLOPT_HTTPGET] = true;
        }

        curl_setopt_array($ch, $options);
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->lastError = 'API call error: ' . $error;
            return false;
        }

        $decoded = json_decode($body, true);
        if ($httpCode >= 200 && $httpCode < 300) {
            return $decoded !== null ? $decoded : true;
        }

        $errMsg = isset($decoded['error']['message']) ? $decoded['error']['message'] : substr($body, 0, 200);
        $this->lastError = 'API error (HTTP ' . $httpCode . '): ' . $errMsg;
        return false;
    }

    /**
     * Copy file Google Drive.
     *
     * @param string $fileId ID file yang akan dicopy
     * @param string|null $title Nama baru (optional)
     * @param string|null $parentFolderId Folder tujuan (optional)
     * @return string|false File ID baru
     */
    public function copyFile($fileId, $title = null, $parentFolderId = null) {
        $body = [];
        if ($title) $body['name'] = $title;
        if ($parentFolderId) $body['parents'] = [$parentFolderId];

        $url = 'https://www.googleapis.com/drive/v3/files/' . rawurlencode($fileId) . '/copy?supportsAllDrives=true';
        $result = $this->apiCall('POST', $url, $body);
        if ($result && isset($result['id'])) {
            return $result['id'];
        }
        return false;
    }

    /**
     * Replace text di Google Docs menggunakan Docs API batchUpdate.
     *
     * @param string $fileId ID Google Doc
     * @param array $replacements Array asosiatif [pattern => replacement]
     * @return bool
     */
    public function batchReplaceText($fileId, $replacements) {
        $requests = [];
        foreach ($replacements as $pattern => $replacement) {
            $requests[] = [
                'replaceAllText' => [
                    'containsText' => [
                        'text' => $pattern,
                        'matchCase' => true,
                    ],
                    'replaceText' => $replacement,
                ],
            ];
        }

        if (empty($requests)) return true;

        $result = $this->apiCall('POST', 'https://docs.googleapis.com/v1/documents/' . rawurlencode($fileId) . ':batchUpdate', [
            'requests' => $requests,
        ]);

        return $result !== false;
    }

    /**
     * Export file Google Drive ke format tertentu dan dapatkan konten binary.
     *
     * @param string $fileId
     * @param string $mimeType MIME type tujuan (default: application/pdf)
     * @return string|false Binary content
     */
    public function exportFile($fileId, $mimeType = 'application/pdf') {
        $token = $this->getWriteAccessToken();
        if (!$token) return false;

        $url = 'https://www.googleapis.com/drive/v3/files/' . rawurlencode($fileId) . '/export?mimeType=' . rawurlencode($mimeType) . '&supportsAllDrives=true';

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
            ),
            CURLOPT_TIMEOUT => 60,
        ));
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200 || empty($body)) {
            $this->lastError = 'Export error (HTTP ' . $httpCode . '): ' . $error;
            return false;
        }

        return $body;
    }

    /**
     * Upload file ke Google Drive.
     *
     * @param string $name Nama file
     * @param string $content Binary content
     * @param string $mimeType MIME type
     * @param string|null $parentFolderId Folder ID tujuan
     * @return string|false File ID
     */
    public function uploadFile($name, $content, $mimeType, $parentFolderId = null) {
        $token = $this->getWriteAccessToken();
        if (!$token) return false;

        // Step 1: Buat metadata file via API (agar SA punya parents yg di-share)
        $metadata = [
            'name' => $name,
            'mimeType' => $mimeType,
        ];
        if ($parentFolderId) {
            $metadata['parents'] = [$parentFolderId];
        }

        // Gunakan apiCall untuk membuat file — apiCall otomatis tambah supportsAllDrives=true
        $created = $this->apiCall('POST', 'https://www.googleapis.com/drive/v3/files', $metadata);
        if (!$created || !isset($created['id'])) {
            $this->lastError = 'Upload error: gagal membuat file metadata. ' . ($this->lastError ?? '');
            return false;
        }
        $fileId = $created['id'];

        // Step 2: Upload konten binary ke file via PATCH
        $url = 'https://www.googleapis.com/upload/drive/v3/files/' . rawurlencode($fileId) . '?uploadType=media&supportsAllDrives=true';
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => $content,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
                'Content-Type: ' . $mimeType,
                'Content-Length: ' . strlen($content),
            ),
            CURLOPT_TIMEOUT => 60,
        ));
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode < 200 || $httpCode >= 300) {
            $this->lastError = 'Upload error (HTTP ' . $httpCode . '): ' . $error . ' | Response: ' . substr($resp, 0, 500);
            return false;
        }

        return $fileId;
    }

    /**
     * Buat folder di Google Drive.
     *
     * @param string $name Nama folder
     * @param string|null $parentFolderId Parent folder
     * @return string|false Folder ID
     */
    public function createFolder($name, $parentFolderId = null) {
        $body = [
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
        ];
        if ($parentFolderId) {
            $body['parents'] = [$parentFolderId];
        }

        $result = $this->apiCall('POST', 'https://www.googleapis.com/drive/v3/files?supportsAllDrives=true', $body);
        if ($result && isset($result['id'])) {
            return $result['id'];
        }
        return false;
    }

    /**
     * Set permission "Anyone with the link can view" pada file.
     *
     * @param string $fileId
     * @return bool
     */
    public function makePublic($fileId) {
        $result = $this->apiCall('POST', 'https://www.googleapis.com/drive/v3/files/' . rawurlencode($fileId) . '/permissions', [
            'type' => 'anyone',
            'role' => 'reader',
        ]);
        return $result !== false;
    }

    /**
     * Dapatkan webViewLink untuk ditampilkan di browser.
     *
     * @param string $fileId
     * @return string|false
     */
    public function getWebViewLink($fileId) {
        $result = $this->apiCall('GET', 'https://www.googleapis.com/drive/v3/files/' . rawurlencode($fileId) . '?fields=webViewLink,id,name');
        if ($result && isset($result['webViewLink'])) {
            return $result['webViewLink'];
        }
        return false;
    }

    /**
     * Dapatkan informasi file.
     *
     * @param string $fileId
     * @param string $fields
     * @return array|false
     */
    public function getFileInfo($fileId, $fields = 'id,name,mimeType,webViewLink,webContentLink') {
        $result = $this->apiCall('GET', 'https://www.googleapis.com/drive/v3/files/' . rawurlencode($fileId) . '?fields=' . rawurlencode($fields));
        return $result;
    }

    /**
     * Hapus file dari Google Drive.
     *
     * @param string $fileId
     * @return bool
     */
    public function deleteFile($fileId) {
        $result = $this->apiCall('DELETE', 'https://www.googleapis.com/drive/v3/files/' . rawurlencode($fileId));
        return $result !== false;
    }

    /**
     * Ambil pesan error terakhir.
     * @return string|null
     */
    public function getLastError() {
        return $this->lastError;
    }

    /**
     * Ekstrak file ID dari link Google Drive.
     * Mendukung format:
     * - https://docs.google.com/document/d/FILE_ID/edit
     * - https://drive.google.com/file/d/FILE_ID/view
     * - https://drive.google.com/open?id=FILE_ID
     * - FILE_ID (langsung)
     *
     * @param string $link
     * @return string|false
     */
    public static function extractFileId($link) {
        $link = trim($link);
        if (empty($link)) return false;

        // Jika sudah berupa ID langsung (tanpa http)
        if (!preg_match('/^https?:\/\//', $link)) {
            // Validasi: ID Google Drive biasanya 28+ karakter alfanumerik + _ -
            if (preg_match('/^[a-zA-Z0-9_-]{25,}$/', $link)) {
                return $link;
            }
            return false;
        }

        // Pattern 1: docs.google.com/*/d/FILE_ID/*
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $link, $m)) {
            return $m[1];
        }

        // Pattern 2: drive.google.com/file/d/FILE_ID/view
        if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $link, $m)) {
            return $m[1];
        }

        // Pattern 3: open?id=FILE_ID
        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $link, $m)) {
            return $m[1];
        }

        // Pattern 4: drive.google.com/uc?export=download&id=FILE_ID
        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $link, $m)) {
            return $m[1];
        }

        return false;
    }

    /**
     * Fetch konten (HTML) dari link Google Docs.
     * Gabungan extractFileId + downloadCleanHtml.
     *
     * @param string $link URL Google Docs
     * @return string|false HTML bersih, atau false jika gagal
     */
    public function fetchByLink($link) {
        $fileId = self::extractFileId($link);
        if (!$fileId) {
            $this->lastError = 'Link Google Drive tidak valid.';
            return false;
        }
        return $this->downloadCleanHtml($fileId);
    }

    /**
     * Import file (upload dengan konversi) ke Google Docs.
     * Mengupload konten dan mengkonversinya ke Google Docs format.
     *
     * @param string $name Nama dokumen
     * @param string $content Konten (binary atau text)
     * @param string $sourceMimeType MIME type konten sumber (misal: text/html, text/plain)
     * @param string|null $parentFolderId Folder tujuan
     * @return string|false File ID Google Docs baru
     */
    public function importAsGoogleDoc($name, $content, $sourceMimeType = 'text/html', $parentFolderId = null) {
        $token = $this->getWriteAccessToken();
        if (!$token) return false;

        // Metadata untuk file Google Docs
        $metadata = [
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.document',
        ];
        if ($parentFolderId) {
            $metadata['parents'] = [$parentFolderId];
        }

        // Upload dengan multipart — metadata + media
        $boundary = 'boundary_' . uniqid();
        $delimiter = "\r\n--" . $boundary . "\r\n";
        $closeDelim = "\r\n--" . $boundary . "--\r\n";

        $body = '';
        // Bagian metadata
        $body .= $delimiter;
        $body .= 'Content-Type: application/json; charset=UTF-8' . "\r\n\r\n";
        $body .= json_encode($metadata) . "\r\n";
        // Bagian konten
        $body .= $delimiter;
        $body .= 'Content-Type: ' . $sourceMimeType . "\r\n";
        $body .= 'Content-Transfer-Encoding: binary' . "\r\n\r\n";
        $body .= $content . "\r\n";
        $body .= $closeDelim;

        $url = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&supportsAllDrives=true&convert=true';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: multipart/related; boundary="' . $boundary . '"',
                'Content-Length: ' . strlen($body),
            ],
            CURLOPT_TIMEOUT => 60,
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode < 200 || $httpCode >= 300) {
            $this->lastError = 'Import Google Docs error (HTTP ' . $httpCode . '): ' . $error . ' | ' . substr($resp, 0, 300);
            return false;
        }

        $decoded = json_decode($resp, true);
        return $decoded['id'] ?? false;
    }

    /**
     * Copy file dan konversi ke Google Docs jika perlu.
     * Jika file sumber sudah Google Docs, lakukan copy biasa.
     * Jika file sumber adalah Office file, copy dengan mimeType Google Docs
     * agar otomatis dikonversi tanpa merusak format.
     *
     * @param string $fileId ID file sumber
     * @param string $title Nama baru
     * @param string|null $parentFolderId Folder tujuan
     * @return string|false ID file Google Docs baru
     */
    public function copyAsGoogleDoc($fileId, $title, $parentFolderId = null) {
        // Cek mimeType file sumber
        $info = $this->getFileInfo($fileId, 'id,name,mimeType');
        if (!$info) {
            $this->lastError = 'Tidak dapat membaca metadata file sumber.';
            return false;
        }

        $sourceMime = $info['mimeType'] ?? '';

        // Jika sudah Google Docs native, copy biasa
        if ($sourceMime === 'application/vnd.google-apps.document') {
            return $this->copyFile($fileId, $title, $parentFolderId);
        }

        // Jika file Office (docx, doc, odt, dll):
        // Copy dengan override mimeType ke Google Docs — API akan konversi otomatis
        $body = [];
        if ($title) $body['name'] = $title;
        if ($parentFolderId) $body['parents'] = [$parentFolderId];
        // PENTING: override mimeType agar file Office dikonversi ke Google Docs
        $body['mimeType'] = 'application/vnd.google-apps.document';

        $url = 'https://www.googleapis.com/drive/v3/files/' . rawurlencode($fileId) . '/copy?supportsAllDrives=true';
        $result = $this->apiCall('POST', $url, $body);
        if ($result && isset($result['id'])) {
            return $result['id'];
        }
        $this->lastError = 'Konversi file ke Google Docs gagal: ' . ($this->lastError ?? '');
        return false;
    }
}
