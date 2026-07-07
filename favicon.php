<?php
// Redirect favicon.ico requests to actual favicon file
$favicon = __DIR__ . '/content/favicon.png';
if (file_exists($favicon)) {
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=604800');
    readfile($favicon);
} else {
    http_response_code(404);
}
