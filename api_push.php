<?php
/**
 * EduMuslim Hub — Remote Push API Endpoint
 * Menerima naskah ter-audit dari AI agent via HTTP POST secara instan.
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// Verifikasi Secret Token / PIN
$secret = $_SERVER['HTTP_X_HUB_SECRET'] ?? $_GET['secret'] ?? $_POST['secret'] ?? '';

if ($secret !== DEFAULT_PIN) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'PIN / Token rahasia tidak valid']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Hanya menerima request POST']);
    exit;
}

// Ambil payload JSON
$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

if (!$payload && !empty($_POST['data'])) {
    $payload = json_decode($_POST['data'], true);
}

if (empty($payload)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Payload JSON kosong atau format tidak valid']);
    exit;
}

if (isset($payload['type']) && $payload['type'] === 'daily_brief') {
    file_put_contents(DATA_DIR . '/daily_brief.json', json_encode($payload['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(['status' => 'success', 'message' => 'Daily brief berhasil disimpan']);
    exit;
}

$items = isset($payload[0]) ? $payload : [$payload];
$updatedCount = 0;

foreach ($items as $item) {
    if (empty($item['id']) || empty($item['title'])) {
        continue;
    }

    // Bersihkan teks slide secara otomatis
    if (!empty($item['slides']) && is_array($item['slides'])) {
        foreach ($item['slides'] as $k => $s) {
            $item['slides'][$k]['title'] = clean_slide_text($s['title'] ?? '');
            $item['slides'][$k]['content'] = clean_slide_text($s['content'] ?? '');
            $item['slides'][$k]['design_note'] = clean_slide_text($s['design_note'] ?? '');
        }
    }

    HubDB::upsertContent($item);
    $updatedCount++;
}

echo json_encode([
    'status' => 'success',
    'message' => "$updatedCount naskah berhasil dimasukkan ke hub server",
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
