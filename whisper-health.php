<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

$url = 'http://127.0.0.1:8080/';
$configFile = __DIR__ . '/config.php';
if (is_file($configFile)) {
    $config = require $configFile;
    if (is_array($config) && !empty($config['whisper_url'])) {
        $url = preg_replace('~/inference$~', '/', (string)$config['whisper_url']);
    }
}

$context = stream_context_create([
    'http' => ['timeout' => 3, 'ignore_errors' => true],
]);

$body = @file_get_contents($url, false, $context);
echo json_encode([
    'ok' => $body !== false,
    'whisper_url' => $url,
], JSON_UNESCAPED_SLASHES);
