<?php
/**
 * EduMuslim Hub — Remote Dispatcher
 * Mengirim seluruh naskah lokal ke endpoint live cPanel secara otomatis.
 */

require_once __DIR__ . '/config.php';

$cpanelEndpoint = 'https://app.edumuslim.my.id/hub/api_push.php';
$secret = DEFAULT_PIN;

$contents = HubDB::getContents();

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

// Direct SSH/SCP sync to guarantee cPanel files & DB match local 100%
echo "Melakukan sinkronisasi langsung file Hub & DB ke cPanel via SSH...\n";
$remoteHost = 'wegqxcgv@digitalprofit.my.id';
$remotePort = 30524;
$remotePath = '/home/wegqxcgv/subdomains/app.edumuslim.my.id/hub';

$scpFiles = [
    __DIR__ . '/index.php' => "$remotePath/index.php",
    __DIR__ . '/api_action.php' => "$remotePath/api_action.php",
    __DIR__ . '/sync.php' => "$remotePath/sync.php",
    __DIR__ . '/data/contents.json' => "$remotePath/data/contents.json",
    __DIR__ . '/data/daily_brief.json' => "$remotePath/data/daily_brief.json",
];

foreach ($scpFiles as $src => $dest) {
    if (file_exists($src)) {
        exec("scp -P $remotePort " . escapeshellarg($src) . " " . escapeshellarg("$remoteHost:$dest") . " 2>&1", $output, $code);
    }
}

echo "✅ Sinkronisasi langsung ke server cPanel selesai!\n";
