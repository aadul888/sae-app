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

        // 2) Coba dengan Bearer token — untuk file yg bisa diakses Service Account
        $result = $this->httpGet($exportUrl);
        if ($result !== false) {
            return $result;
        }

        // 3) Fallback: JWT langsung (bypass legacy library yg bermasalah dengan SSL)
        $result = $this->directJwtDownload($exportUrl);
        if ($result !== false) {
            return $result;
        }

        $this->lastError = 'downloadDirectExport gagal (HTTP ' . $httpCode . '). ' .
            'Pastikan dokumen sudah di-share "Anyone with the link" atau share ke Service Account email: ' .
            $this->getServiceAccountEmail();
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
        // Dapatkan access token
        $accessToken = $this->getAccessToken();
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
     * Dapatkan access token dari Google_Client (via assertion).
     *
     * @return string|false
     */
    private function getAccessToken() {
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
        if (empty($this->creds) || empty($this->creds['client_email']) || empty($this->creds['private_key'])) {
            return false;
        }

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
            return false;
        }
        $tokenData = json_decode($resp, true);
        return $tokenData['access_token'] ?? false;
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
}
