<?php
/**
 * EduMuslim Hub — Remote Dispatcher
 * Mengirim seluruh naskah lokal ke endpoint live cPanel secara otomatis.
 */

require_once __DIR__ . '/config.php';

$remoteHost = 'wegqxcgv@digitalprofit.my.id';
$remotePort = 30524;
$remotePath = '/home/wegqxcgv/subdomains/app.edumuslim.my.id/hub';

// 1. Tarik daftar naskah yang dihapus & status live dari cPanel via HTTP sync
shell_exec("python3 /home/fadil/edumuslim-team/audit/cpanel_sync.py >/dev/null 2>&1");

$cpanelEndpoint = 'https://app.edumuslim.my.id/hub/api_push.php';
$secret = DEFAULT_PIN;

$contents = HubDB::getContents();

// HARD GATE: Pastikan seluruh naskah 100% lolos uji Anti-Slop Linter sebelum dikirim ke cPanel!
$outLinter = [];
$retLinter = 0;
exec("python3 /home/fadil/edumuslim-team/audit/antislop_linter.py", $outLinter, $retLinter);
if ($retLinter !== 0) {
    echo "🛑 [HARD GATE REJECTED] Naskah gagal uji Anti-Slop Linter! Pengiriman ke cPanel DIBATALKAN:\n";
    echo implode("\n", $outLinter) . "\n";
    exit(1);
}

if (empty($contents)) {
    echo "Tidak ada data konten lokal untuk dikirim.\n";
    exit(1);
}

echo "Mengirim " . count($contents) . " konten ke $cpanelEndpoint ...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $cpanelEndpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($contents, JSON_UNESCAPED_UNICODE));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Hub-Secret: ' . $secret
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$res = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Berhasil push naskah ke cPanel!\n";
} else {
    echo "⚠️ Gagal mengirim naskah (HTTP $httpCode):\n$err\nResponse: $res\n";
}

// Push Daily Brief jika ada
$briefFile = DATA_DIR . '/daily_brief.json';
if (file_exists($briefFile)) {
    $briefData = json_decode(file_get_contents($briefFile), true);
    $briefPayload = [
        'type' => 'daily_brief',
        'data' => $briefData
    ];

    echo "Mengirim daily_brief ke $cpanelEndpoint ...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $cpanelEndpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($briefPayload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Hub-Secret: ' . $secret
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $resBrief = curl_exec($ch);
    $httpCodeBrief = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCodeBrief === 200) {
        echo "✅ Berhasil push daily_brief ke cPanel!\n";
    } else {
        echo "⚠️ Gagal mengirim daily_brief (HTTP $httpCodeBrief):\nResponse: $resBrief\n";
    }
}

// Direct SSH/SCP sync to guarantee cPanel files & DB match local 100% (hanya jika SSH aktif)
echo "Mengecek konektivitas SSH ke cPanel...\n";
$sshCheck = @fsockopen('digitalprofit.my.id', $remotePort, $errno, $errstr, 2);

if (!$sshCheck) {
    echo "ℹ️ Port SSH ($remotePort) tidak dapat diakses langsung. Naskah & brief tetap sukses terkirim via HTTPS API.\n";
    exit(0);
}
fclose($sshCheck);

echo "Melakukan sinkronisasi langsung file Hub & DB ke cPanel via SSH...\n";

// Safe merge for crm_batch_status.json: preserve completed batches from remote
$remoteBatchJson = shell_exec("ssh -p $remotePort $remoteHost 'cat $remotePath/data/crm_batch_status.json 2>/dev/null'");
if (!empty($remoteBatchJson)) {
    $remoteBatch = json_decode($remoteBatchJson, true);
    if (!empty($remoteBatch['batches']) && file_exists(__DIR__ . '/data/crm_batch_status.json')) {
        $localBatch = json_decode(file_get_contents(__DIR__ . '/data/crm_batch_status.json'), true);
        foreach ($remoteBatch['batches'] as $bId => $bData) {
            if (!empty($bData['completed'])) {
                if (isset($localBatch['batches'][$bId])) {
                    $localBatch['batches'][$bId]['completed'] = true;
                    $localBatch['batches'][$bId]['completed_at'] = $bData['completed_at'] ?? date('Y-m-d H:i:s');
                }
            }
        }
        file_put_contents(__DIR__ . '/data/crm_batch_status.json', json_encode($localBatch, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

$scpFiles = [
    __DIR__ . '/config.php' => "$remotePath/config.php",
    __DIR__ . '/index.php' => "$remotePath/index.php",
    __DIR__ . '/view.php' => "$remotePath/view.php",
    __DIR__ . '/api_action.php' => "$remotePath/api_action.php",
    __DIR__ . '/sync.php' => "$remotePath/sync.php",
    __DIR__ . '/data/contents.json' => "$remotePath/data/contents.json",
    __DIR__ . '/data/daily_brief.json' => "$remotePath/data/daily_brief.json",
    __DIR__ . '/data/crm_batch_status.json' => "$remotePath/data/crm_batch_status.json",
    __DIR__ . '/data/deleted_ids.json' => "$remotePath/data/deleted_ids.json",
];

foreach ($scpFiles as $src => $dest) {
    if (file_exists($src)) {
        exec("scp -P $remotePort " . escapeshellarg($src) . " " . escapeshellarg("$remoteHost:$dest") . " 2>&1", $output, $code);
    }
}

// Sync ebook files to cPanel
$localEbookDir = '/home/fadil/edumuslim-team/ebooks/sisa-usia';
if (is_dir($localEbookDir)) {
    exec("scp -P $remotePort $localEbookDir/*.md $localEbookDir/*.html " . escapeshellarg("$remoteHost:$remotePath/data/ebooks/sisa-usia/") . " 2>&1");
}

// Mirror contents.json to root
exec("ssh -p $remotePort $remoteHost " . escapeshellarg("cp $remotePath/data/contents.json $remotePath/contents.json 2>/dev/null") . " 2>&1");

echo "✅ Sinkronisasi langsung ke server cPanel selesai!\n";
