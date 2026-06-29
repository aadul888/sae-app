<?php
/**
 * Google Spreadsheet Helper — untuk mengirim data ke Google Sheets
 * menggunakan Service Account yang sama dengan Google Drive Helper.
 *
 * @author SAEV5
 */

class SpreadsheetHelper {

    /** @var Google_Client */
    private $client;

    /** @var string Email Service Account */
    private $serviceAccountEmail;

    /** @var string|null Pesan error terakhir */
    private $lastError = null;

    /** @var array|null Full credentials from JSON */
    private $creds = null;

    /**
     * @param string $jsonFilePath Path absolut ke file JSON Service Account
     * @throws Exception
     */
    public function __construct($jsonFilePath) {
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

        // Scope untuk Google Sheets API
        $scopes = array('https://www.googleapis.com/auth/spreadsheets');

        // Load Google Client
        $googleClientPath = __DIR__ . '/google-client/Google_Client.php';
        if (!class_exists('Google_Client', false)) {
            if (file_exists($googleClientPath)) {
                set_include_path(__DIR__ . '/google-client' . PATH_SEPARATOR . get_include_path());
                require_once $googleClientPath;
            } else {
                throw new Exception('Google Client library tidak ditemukan di: ' . $googleClientPath);
            }
        }

        $this->client = new Google_Client();

        $credentials = new Google_AssertionCredentials(
            $creds['client_email'],
            $scopes,
            $creds['private_key'],
            'notasecret'
        );

        $this->client->setAssertionCredentials($credentials);
    }

    /**
     * Dapatkan access token dari Google_Client.
     *
     * @return string|false
     */
    private function getAccessToken() {
        try {
            $tokenData = $this->client->getAccessToken();
            if ($tokenData) {
                $token = json_decode($tokenData, true);
                if (isset($token['access_token'])) {
                    return $token['access_token'];
                }
            }

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

            // Fallback: JWT langsung
            $token = $this->directJwtGetToken();
            if ($token) {
                return $token;
            }

            $this->lastError = 'Gagal mendapatkan access token (legacy & JWT fallback).';
            return false;
        } catch (Exception $e) {
            // Fallback: JWT langsung
            $token = $this->directJwtGetToken();
            if ($token) {
                return $token;
            }
            $this->lastError = 'Exception getAccessToken: ' . $e->getMessage() . ' & JWT fallback gagal.';
            return false;
        }
    }

    /**
     * Dapatkan access token via JWT assertion langsung.
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
            'scope' => 'https://www.googleapis.com/auth/spreadsheets',
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
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://oauth2.googleapis.com/token',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
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
     * Base64 URL encode.
     *
     * @param string $data
     * @return string
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Append baris data ke Google Sheet.
     *
     * @param string $spreadsheetId ID spreadsheet Google
     * @param string $range Range (contoh: 'Sheet1!A:F')
     * @param array $values Array baris data (array of array of values)
     * @return array|false Response dari API
     */
    public function appendRows($spreadsheetId, $range, $values) {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            $this->lastError = 'Tidak dapat mengakses token.';
            return false;
        }

        $url = 'https://sheets.googleapis.com/v4/spreadsheets/' . rawurlencode($spreadsheetId) . '/values/' . rawurlencode($range) . ':append?valueInputOption=USER_ENTERED&insertDataOption=INSERT_ROWS';

        $body = json_encode([
            'values' => $values
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
                'User-Agent: Saev5-Spreadsheet-Helper/1.0',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->lastError = 'HTTP error: ' . $error;
            return false;
        }

        if ($httpCode !== 200) {
            $this->lastError = 'HTTP ' . $httpCode . ': ' . substr($response, 0, 500);
            return false;
        }

        return json_decode($response, true);
    }

    /**
     * Ambil header/baris pertama spreadsheet untuk mapping kolom.
     *
     * @param string $spreadsheetId
     * @param string $range
     * @return array|false
     */
    public function getHeaderRow($spreadsheetId, $range) {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return false;

        $url = 'https://sheets.googleapis.com/v4/spreadsheets/' . rawurlencode($spreadsheetId) . '/values/' . rawurlencode($range);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'User-Agent: Saev5-Spreadsheet-Helper/1.0',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) return false;

        $data = json_decode($response, true);
        return isset($data['values'][0]) ? $data['values'][0] : false;
    }

    /**
     * Ambil error terakhir.
     *
     * @return string|null
     */
    public function getLastError() {
        return $this->lastError;
    }
}
