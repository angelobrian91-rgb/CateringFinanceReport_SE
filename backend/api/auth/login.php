<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$username = trim($body['username'] ?? '');
$password = $body['password'] ?? '';

if ($username === 'AngeloNvMirza' && $password === 'user12345') {
    echo json_encode(['success' => true, 'user' => ['username' => 'AngeloNvMirza', 'role' => 'owner']]);
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Username atau password salah.']);
}
