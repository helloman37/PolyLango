<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'error' => 'POST required.']);
}

if (!isset($_FILES['audio']) || !is_uploaded_file($_FILES['audio']['tmp_name'])) {
    respond(400, ['ok' => false, 'error' => 'No audio recording was received.']);
}

if ($_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
    respond(400, ['ok' => false, 'error' => 'Audio upload failed.']);
}

if ($_FILES['audio']['size'] > 20 * 1024 * 1024) {
    respond(413, ['ok' => false, 'error' => 'The recording is too large.']);
}

if (!function_exists('curl_init')) {
    respond(500, ['ok' => false, 'error' => 'PHP cURL is not installed.']);
}

$config = [
    'whisper_url' => 'http://127.0.0.1:8080/inference',
    'timeout_seconds' => 90,
];

$configFile = __DIR__ . '/config.php';
if (is_file($configFile)) {
    $custom = require $configFile;
    if (is_array($custom)) {
        $config = array_merge($config, $custom);
    }
}

$language = trim((string)($_POST['language'] ?? 'auto'));
if ($language === '') {
    $language = 'auto';
}
$language = strtolower(substr($language, 0, 2));

$tmpPath = $_FILES['audio']['tmp_name'];
$filename = basename((string)($_FILES['audio']['name'] ?? 'answer.webm'));
$mimeType = mime_content_type($tmpPath) ?: 'application/octet-stream';

$postFields = [
    'file' => new CURLFile($tmpPath, $mimeType, $filename),
    'response_format' => 'json',
    'temperature' => '0.0',
    'language' => $language,
];

$curl = curl_init((string)$config['whisper_url']);
curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => (int)$config['timeout_seconds'],
    CURLOPT_POSTFIELDS => $postFields,
]);

$body = curl_exec($curl);
$status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
curl_close($curl);

if ($body === false) {
    respond(502, [
        'ok' => false,
        'error' => 'Could not reach whisper.cpp. Make sure whisper-server is running on 127.0.0.1:8080.'
    ]);
}

$data = json_decode($body, true);

if ($status < 200 || $status >= 300) {
    $message = is_array($data)
        ? (string)($data['error'] ?? $data['message'] ?? 'whisper.cpp rejected the recording.')
        : 'whisper.cpp rejected the recording.';
    respond(502, ['ok' => false, 'error' => $message]);
}

$text = '';
if (is_array($data)) {
    $text = trim((string)($data['text'] ?? $data['transcription'] ?? ''));
} elseif (is_string($body)) {
    $text = trim($body);
}

respond(200, [
    'ok' => true,
    'text' => $text,
]);
