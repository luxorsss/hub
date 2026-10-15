<?php
require_once __DIR__ . '/config.php';
check_auth();

$contents = HubDB::getContents();
$sales = HubDB::getSalesStats();

$filter = $_GET['filter'] ?? 'all';
$filteredContents = [];

foreach ($contents as $c) {
    if ($filter === 'carousel' && stripos($c['channel'], 'carousel') === false) continue;
    if ($filter === 'reels' && stripos($c['channel'], 'reels') === false) continue;
    if ($filter === 'story' && stripos($c['channel'], 'story') === false) continue;
    if ($filter === 'crm' && stripos($c['channel'], 'crm') === false) continue;
    $filteredContents[] = $c;
}

$targetSales = $sales['target_sales'] ?? 100;
$currentSales = $sales['current_sales'] ?? 12;
$percent = min(100, round(($currentSales / $targetSales) * 100, 1));
$totalRevenue = number_format($sales['total_revenue'] ?? ($currentSales * 47000), 0, ',', '.');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduMuslim Content & Sales Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #090a0f;
            --surface: #11131a;
            --card: #151822;
            --card-hover: #1c202e;
            --border: #232738;
            --text: #f0f2f8;
            --text-muted: #8b92a8;
            --primary: #10b981;
            --primary-glow: rgba(16, 185, 129, 0.15);
            --amber: #f59e0b;
            --blue: #3b82f6;
            --purple: #8b5cf6;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            padding: 1.5rem;
            min-height: 100vh;
        }
        .container {
            max-width: 1080px;
            margin: 0 auto;
        }
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #10b981, #047857);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: #022c22;
            font-size: 1.25rem;
        }
        .brand h1 {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .brand span {
            color: var(--text-muted);
            font-size: 0.8rem;
            font-weight: 500;
            display: block;
        }
        .header-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }
        .btn-sm {
            padding: 0.5rem 0.9rem;
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: 1px solid var(--border);
            color: var(--text);
            background: var(--surface);
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .btn-sm:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        .btn-logout {
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.3);
        }
        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        /* Sales Widget */
        .sales-widget {
            background: linear-gradient(135deg, #131722, #0d1017);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        .sales-widget::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary);
        }
        .sales-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .sales-title h2 {
            font-size: 1.1rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .sales-title p {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }
        .sales-stat {
            text-align: right;
        }
        .sales-stat .number {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--primary);
            font-family: 'JetBrains Mono', monospace;
        }
        .sales-stat .sub {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .progress-bar-bg {
            background: rgba(255, 255, 255, 0.05);
            height: 12px;
            border-radius: 999px;
            overflow: hidden;
            margin-bottom: 0.75rem;
        }
        .progress-bar-fill {
            background: linear-gradient(90deg, #10b981, #34d399);
            height: 100%;
            border-radius: 999px;
            transition: width 0.5s ease;
        }
        .sales-footer {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* Filter Tabs */
        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            overflow-x: auto;
            padding-bottom: 0.25rem;
        }
        .tab {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-muted);
            background: var(--surface);
            border: 1px solid var(--border);
            white-space: nowrap;
            transition: all 0.2s;
        }
        .tab:hover, .tab.active {
            color: var(--text);
            background: var(--card-hover);
            border-color: var(--primary);
        }
        .tab.active {
            color: var(--primary);
            border-color: var(--primary);
            background: var(--primary-glow);
        }

        /* Content List */
        .content-grid {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .content-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            transition: all 0.2s;
        }
        .content-card:hover {
            border-color: #3b4258;
            background: var(--card-hover);
            transform: translateY(-1px);
        }
        .card-main {
            flex: 1;
        }
        .card-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            flex-wrap: wrap;
        }
        .tag {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .tag-carousel { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .tag-reels { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        .tag-story { background: rgba(139, 92, 246, 0.15); color: #c084fc; }
        .tag-crm { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .tag-funnel {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-muted);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .card-title {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 0.35rem;
            color: var(--text);
            line-height: 1.4;
        }
        .card-hook {
            font-size: 0.85rem;
            color: var(--text-muted);
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .card-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .btn-view {
            padding: 0.65rem 1.1rem;
            background: var(--primary);
            color: #022c22;
            font-weight: 700;
            font-size: 0.85rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .btn-view:hover {
            background: #34d399;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        .status-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
            border: 1px solid rgba(16, 185, 129, 0.2);
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            body { padding: 1rem; }
            .content-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            .card-actions {
                width: 100%;
                justify-content: space-between;
            }
            .btn-view {
                flex: 1;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="brand">
                <div class="logo-icon">EM</div>
                <div>
                    <h1>EduMuslim Hub</h1>
                    <span>Draf Konten Siap Desain & Mesin Konversi</span>
                </div>
            </div>
            <div class="header-actions">
                <a href="create.php" class="btn-sm" style="background: var(--primary); color: #022c22; font-weight: 700;">+ Tambah Konten</a>
                <a href="sync.php" class="btn-sm" title="Sinkronkan file naskah terbaru dari folder tim">🔄 Sync Naskah</a>
                <a href="http://app.edumuslim.my.id/pixelcrm/" target="_blank" class="btn-sm">📊 Buka PixelCRM</a>
                <a href="logout.php" class="btn-sm btn-logout">Keluar</a>
            </div>
        </header>

        <!-- Widget Progres Penjualan -->
        <div class="sales-widget">
            <div class="sales-header">
                <div class="sales-title">
                    <h2>🎯 Target 100 Penjualan eBook Qila</h2>
                    <p>Fokus Operasional: Instagram Carousel, Reels Loop, & Alur Closing DM</p>
                </div>
                <div class="sales-stat">
                    <div class="number"><?= $currentSales ?> / <?= $targetSales ?></div>
                    <div class="sub">Omzet: Rp<?= $totalRevenue ?></div>
                </div>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: <?= $percent ?>%;"></div>
            </div>
            <div class="sales-footer">
                <span>Pencapaian: <?= $percent ?>%</span>
                <span>Sisa: <?= max(0, $targetSales - $currentSales) ?> Penjualan Menuju Target</span>
            </div>
        </div>

        <!-- Filter Tab -->
        <div class="tabs">
            <a href="index.php?filter=all" class="tab <?= $filter === 'all' ? 'active' : '' ?>">Semua Konten (<?= count($contents) ?>)</a>
            <a href="index.php?filter=carousel" class="tab <?= $filter === 'carousel' ? 'active' : '' ?>">Carousel (5)</a>
            <a href="index.php?filter=reels" class="tab <?= $filter === 'reels' ? 'active' : '' ?>">Reels 1-Slide (1)</a>
            <a href="index.php?filter=story" class="tab <?= $filter === 'story' ? 'active' : '' ?>">Story Jualan (1)</a>
            <a href="index.php?filter=crm" class="tab <?= $filter === 'crm' ? 'active' : '' ?>">DM Closing (1)</a>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <?php 
                $hariMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                $hariIni = $hariMap[date('N')] ?? 'Kamis';
            ?>
            <?php foreach ($filteredContents as $item): ?>
                <?php 
                    $channelLower = strtolower($item['channel']);
                    $tagClass = 'tag-carousel';
                    if (strpos($channelLower, 'reels') !== false) $tagClass = 'tag-reels';
                    if (strpos($channelLower, 'story') !== false) $tagClass = 'tag-story';
                    if (strpos($channelLower, 'crm') !== false) $tagClass = 'tag-crm';
                    $isToday = (stripos($item['day'], $hariIni) !== false);
                ?>
                <div class="content-card" style="<?= $isToday ? 'border-color: #f59e0b; background: rgba(245, 158, 11, 0.04);' : '' ?>">
                    <div class="card-main">
                        <div class="card-meta">
                            <?php if ($isToday): ?>
                                <span class="tag" style="background: #f59e0b; color: #000; font-weight: 800;">🔥 TAYANG HARI INI (<?= $hariIni ?>)</span>
                            <?php endif; ?>
                            <span class="tag <?= $tagClass ?>"><?= htmlspecialchars($item['channel']) ?></span>
                            <span class="tag tag-funnel"><?= htmlspecialchars($item['day']) ?> • <?= htmlspecialchars($item['funnel']) ?></span>
                            <span class="status-badge">✅ Siap Desain (Audited)</span>
                        </div>
                        <h3 class="card-title"><?= htmlspecialchars($item['title']) ?></h3>
                        <p class="card-hook">Target: <?= htmlspecialchars($item['target_product']) ?> — <?= htmlspecialchars($item['hook']) ?></p>
                    </div>
                    <div class="card-actions">
                        <a href="view.php?id=<?= urlencode($item['id']) ?>" class="btn-view" style="<?= $isToday ? 'background: #f59e0b; color: #000;' : '' ?>">
                            📄 Buka Naskah Canva
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
