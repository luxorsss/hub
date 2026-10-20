<?php
/**
 * EduMuslim Hub — Action API
 * Endpoint untuk AJAX update status (Sudah Didesain) dan hapus konten.
 */

require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

if (empty($_SESSION['hub_auth'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (empty($data)) {
    $data = $_POST;
}

$action = $data['action'] ?? '';

if ($action === 'toggle_crm_batch') {
    $batch = intval($data['batch'] ?? 1);
    $checked = !empty($data['checked']);
    $batchFile = DATA_DIR . '/crm_batch_status.json';
    $batchData = file_exists($batchFile) ? json_decode(file_get_contents($batchFile), true) : ['batches' => []];
    if (!isset($batchData['batches'][$batch])) {
        $batchData['batches'][$batch] = [
            'name' => "Batch $batch",
            'contacts' => '',
            'completed' => false,
            'completed_at' => null
        ];
    }
    $batchData['batches'][$batch]['completed'] = $checked;
    $batchData['batches'][$batch]['completed_at'] = $checked ? date('Y-m-d H:i:s') : null;
    $batchData['updated_at'] = date('Y-m-d H:i:s');
    file_put_contents($batchFile, json_encode($batchData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(['success' => true, 'batch' => $batch, 'completed' => $checked]);
    exit;
}

$id = trim($data['id'] ?? '');

if (empty($id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID konten wajib diisi']);
    exit;
}

if ($action === 'toggle_status') {
    $status = $data['status'] ?? 'ready_to_design';
    $validStatuses = ['ready_to_design', 'designed', 'published', 'archived'];
    if (!in_array($status, $validStatuses)) {
        $status = 'ready_to_design';
    }
    
    $ok = HubDB::updateStatus($id, $status);
    if ($ok) {
        echo json_encode(['success' => true, 'id' => $id, 'status' => $status, 'message' => 'Status berhasil diperbarui']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Konten tidak ditemukan']);
    }
    exit;
}

if ($action === 'delete') {
    $ok = HubDB::deleteContent($id);
    if ($ok) {
        echo json_encode(['success' => true, 'id' => $id, 'message' => 'Naskah berhasil dihapus dari daftar']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Konten tidak ditemukan']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Aksi tidak dikenal']);
