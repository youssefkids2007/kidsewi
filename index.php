<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function respond(int $status, array $body): never {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, [
        'ok' => false,
        'error' => 'method_not_allowed',
        'message' => 'Send a POST request with JSON: {"auth":"your-demo-token"}.'
    ]);
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== 0) {
    respond(415, ['ok' => false, 'error' => 'content_type_must_be_json']);
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '', true);
if (!is_array($payload) || !isset($payload['auth']) || !is_string($payload['auth'])) {
    respond(400, ['ok' => false, 'error' => 'invalid_request']);
}

$provided = $payload['auth'];
$expected = getenv('DEMO_AUTH_TOKEN');

if ($expected === false || $expected === '') {
    respond(503, [
        'ok' => false,
        'error' => 'server_not_configured',
        'message' => 'Set the DEMO_AUTH_TOKEN environment variable in your hosting dashboard.'
    ]);
}

if (strlen($provided) > 512 || !hash_equals($expected, $provided)) {
    respond(401, ['ok' => false, 'authenticated' => false, 'message' => 'Invalid demo token.']);
}

respond(200, ['ok' => true, 'authenticated' => true, 'message' => 'Demo authentication successful.']);
