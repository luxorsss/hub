<?php
/**
 * EduMuslim Hub — Instagram Graph API Sync
 * Menarik profil, status postingan hari ini, dan metrik insights (Reach, Saves, Shares, Views).
 */

require_once __DIR__ . '/config.php';

$metaConfigFile = DATA_DIR . '/meta_config.json';
$igCacheFile = DATA_DIR . '/ig_cache.json';

if (!file_exists($metaConfigFile)) {
    echo "Config meta_config.json belum ada.\n";
    exit(1);
}

$meta = json_decode(file_get_contents($metaConfigFile), true);
$token = $meta['access_token'];
$igId = $meta['instagram_account_id'];

// 1. Ambil Profil Akun
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/v22.0/{$igId}?fields=id,username,name,biography,followers_count,media_count&access_token=" . urlencode($token));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$resProfile = curl_exec($ch);
curl_close($ch);

$profile = json_decode($resProfile, true);

// 2. Ambil 10 Media Terakhir
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/v22.0/{$igId}/media?fields=id,caption,media_type,timestamp,permalink,like_count,comments_count&limit=10&access_token=" . urlencode($token));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$resMedia = curl_exec($ch);
curl_close($ch);

$mediaList = json_decode($resMedia, true)['data'] ?? [];

// 3. Ambil Insights untuk tiap media
$detailedMedia = [];
$todayDate = date('Y-m-d');
$hasPostedToday = false;
$todayPost = null;

foreach ($mediaList as $item) {
    $mediaId = $item['id'];
    $postDate = substr($item['timestamp'], 0, 10);
    
    if ($postDate === $todayDate) {
        $hasPostedToday = true;
        $todayPost = $item;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/v22.0/{$mediaId}/insights?metric=reach,saved,shares,total_interactions,views&access_token=" . urlencode($token));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $resInsights = curl_exec($ch);
    curl_close($ch);

    $insightsJson = json_decode($resInsights, true)['data'] ?? [];
    $metrics = [
        'reach' => 0,
        'saved' => 0,
        'shares' => 0,
        'total_interactions' => 0,
        'views' => 0
    ];
    foreach ($insightsJson as $m) {
        $mName = $m['name'] ?? '';
        $mVal = $m['values'][0]['value'] ?? 0;
        if (isset($metrics[$mName])) {
            $metrics[$mName] = $mVal;
        }
    }

    $item['insights'] = $metrics;
    $detailedMedia[] = $item;
}

$cacheData = [
    'synced_at' => date('Y-m-d H:i:s'),
    'profile' => $profile,
    'has_posted_today' => $hasPostedToday,
    'today_post' => $todayPost,
    'latest_posts' => $detailedMedia
];

file_put_contents($igCacheFile, json_encode($cacheData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "✅ Sync Instagram Berhasil!\n";
echo "Username: @" . ($profile['username'] ?? 'edumuslimin') . "\n";
echo "Followers: " . ($profile['followers_count'] ?? 0) . "\n";
echo "Posting Hari Ini: " . ($hasPostedToday ? "SUDAH TAYANG" : "BELUM ADA POSTINGAN HARI INI") . "\n";
if (!empty($detailedMedia[0])) {
    $latest = $detailedMedia[0];
    echo "Postingan Terakhir: " . $latest['timestamp'] . " (Reach: " . $latest['insights']['reach'] . ", Saves: " . $latest['insights']['saved'] . ", Shares: " . $latest['insights']['shares'] . ")\n";
}
