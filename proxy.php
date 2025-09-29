<?php
$API_BASE = 'https://crm.belmar.pro/api/v1/';
$TOKEN = 'ba67df6a-a17c-476f-8e95-bcdb75ed3958';

$action = isset($_GET['action']) ? trim($_GET['action']) : '';

if (!$action && isset($_SERVER['PATH_INFO'])) {
    $action = ltrim($_SERVER['PATH_INFO'], '/');
}


$targetUrl = rtrim($API_BASE, '/') . '/' . $action;

$input = file_get_contents('php://input');
if ($input === false) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => false, 'error' => 'No input read']);
    exit;
}

if (!empty($input)) {
    $maybeJson = json_decode($input, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($maybeJson)) {
        unset($maybeJson['token']);
        $input = json_encode($maybeJson, JSON_UNESCAPED_UNICODE);
    }
}

$ch = curl_init($targetUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'token:'. $TOKEN
    ],
    CURLOPT_POSTFIELDS => $input,
    CURLOPT_HEADER => true,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
if ($response === false) {
    $err = curl_error($ch);
    curl_close($ch);
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => false, 'error' => 'Proxy CURL error: ' . $err], JSON_UNESCAPED_UNICODE);
    exit;
}

$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$body       = substr($response, $headerSize);
curl_close($ch);

http_response_code($httpCode);
header('Content-Type: application/json; charset=utf-8');

$json = json_decode($body, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => false, 'error' => 'Upstream non-JSON', 'raw' => $body], JSON_UNESCAPED_UNICODE);
    exit;
}

echo $body;
