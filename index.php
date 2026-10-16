<?php
require_once __DIR__ . '/config.php';
check_auth();

$contents = HubDB::getContents();
$sales = HubDB::getSalesStats();
$kaizenFile = DATA_DIR . '/kaizen.json';
$kaizenList = file_exists($kaizenFile) ? json_decode(file_get_contents($kaizenFile), true) : [];

$filter = $_GET['filter'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';
$filteredContents = [];

$countAll = count($contents);
$countPending = 0;
$countDesigned = 0;

foreach ($contents as $c) {
    $isDesigned = (($c['status'] ?? '') === 'designed');
    if ($isDesigned) {
        $countDesigned++;
    } else {
        $countPending++;
    }

    if ($filter === 'carousel' && stripos($c['channel'], 'carousel') === false) continue;
    if ($filter === 'reels' && stripos($c['channel'], 'reels') === false) continue;
    if ($filter === 'story' && stripos($c['channel'], 'story') === false) continue;
    if ($filter === 'crm' && stripos($c['channel'], 'crm') === false) continue;

    if ($statusFilter === 'pending' && $isDesigned) continue;
    if ($statusFilter === 'designed' && !$isDesigned) continue;

    $filteredContents[] = $c;
}

$targetSales = $sales['target_sales'] ?? 100;
$currentSales = $sales['current_sales'] ?? 0;
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
            --red: #ef4444;
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
            flex-wrap: wrap;
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
        }
        .sales-title p {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        .sales-stat {
            text-align: right;
        }
        .sales-stat .number {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            font-family: 'JetBrains Mono', monospace;
        }
        .sales-stat .sub {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .progress-bar-bg {
            background: rgba(255, 255, 255, 0.05);
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        .progress-bar-fill {
            background: var(--primary);
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        .sales-footer {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* Kaizen Widget */
        .kaizen-widget {
            background: linear-gradient(135deg, #0d1527, #070b14);
            border: 1px solid rgba(59, 130, 246, 0.35);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        }
        .kaizen-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .kaizen-header h3 {
            font-size: 1.05rem;
            color: #60a5fa;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
        }
        .kaizen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 0.85rem;
        }
        .kaizen-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 0.9rem;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            transition: all 0.2s;
        }
        .kaizen-item:hover {
            border-color: rgba(96, 165, 250, 0.4);
            background: rgba(96, 165, 250, 0.04);
        }
        .kaizen-agent {
            font-size: 0.7rem;
            font-weight: 700;
            color: #93c5fd;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .kaizen-title {
            font-size: 0.82rem;
            font-weight: 600;
            color: #f1f5f9;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            line-height: 1.3;
        }
        .kaizen-title:hover {
            color: #60a5fa;
            text-decoration: underline;
        }
        .kaizen-desc {
            font-size: 0.76rem;
            color: #94a3b8;
            line-height: 1.45;
        }

        /* Filter Controls */
        .filter-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .tabs {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .tab {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 8px;
            border: 1px solid transparent;
            background: transparent;
            transition: all 0.2s;
        }
        .tab:hover {
            color: var(--text);
            background: var(--surface);
        }
        .tab.active {
            color: var(--primary);
            background: var(--surface);
            border-color: var(--border);
        }
        .status-filter-group {
            display: inline-flex;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 3px;
            gap: 3px;
        }
        .status-pill {
            padding: 0.35rem 0.75rem;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.15s;
        }
        .status-pill.active {
            background: var(--card);
            color: var(--text);
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        /* Content Cards Grid */
        .content-grid {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .content-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .content-card:hover {
            background: var(--card-hover);
            border-color: rgba(255, 255, 255, 0.15);
        }
        .content-card.is-designed {
            border-color: rgba(16, 185, 129, 0.4);
            background: rgba(16, 185, 129, 0.02);
        }
        .content-card.fade-out {
            opacity: 0;
            transform: scale(0.96) translateY(-10px);
            pointer-events: none;
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

        /* Checkbox & Actions */
        .card-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-shrink: 0;
        }
        .check-design-wrapper {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border);
            padding: 0.45rem 0.85rem;
            border-radius: 8px;
            cursor: pointer;
            user-select: none;
            transition: all 0.2s;
        }
        .check-design-wrapper:hover {
            border-color: var(--primary);
            background: rgba(16, 185, 129, 0.08);
        }
        .check-design-wrapper input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--primary);
            cursor: pointer;
        }
        .check-design-wrapper span {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            transition: color 0.2s;
        }
        .check-design-wrapper.checked {
            border-color: rgba(16, 185, 129, 0.5);
            background: rgba(16, 185, 129, 0.12);
        }
        .check-design-wrapper.checked span {
            color: #34d399;
        }

        .btn-view {
            padding: 0.55rem 1rem;
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
            transform: translateY(-1px);
        }

        .btn-delete {
            background: transparent;
            color: var(--text-muted);
            border: 1px solid var(--border);
            padding: 0.55rem 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-delete:hover {
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.4);
            background: rgba(239, 68, 68, 0.1);
        }

        /* Toast Feedback */
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: #10b981;
            color: #022c22;
            padding: 0.85rem 1.5rem;
            font-weight: 700;
            font-size: 0.9rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            display: none;
            z-index: 999;
            animation: fadeIn 0.25s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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
                flex-wrap: wrap;
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
                <a href="daily.php" class="btn-sm" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24; border-color: rgba(245, 158, 11, 0.3); font-weight: 700;">📅 Jadwal & Aksi Harian</a>
                <a href="create.php" class="btn-sm" style="background: var(--primary); color: #022c22; font-weight: 700;">+ Tambah Konten</a>
                <a href="sync.php" class="btn-sm" title="Sinkronkan file naskah terbaru dari folder tim">🔄 Sync Naskah</a>
                <a href="https://app.edumuslim.my.id/pixelcrm/" target="_blank" class="btn-sm">📊 PixelCRM</a>
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

        <?php if (!empty($kaizenList)): ?>
        <!-- Widget Kaizen Harian Tim -->
        <div class="kaizen-widget">
            <div class="kaizen-header">
                <h3>🎓 Kaizen Belajar Harian Tim (Tokoh Bisnis Dunia)</h3>
                <span style="font-size: 0.75rem; color: #64748b; font-family: monospace;">Kurikulum: Dan Koe · Hormozi · Huberman · McDowell · Voss</span>
            </div>
            <div class="kaizen-grid">
                <?php foreach ($kaizenList as $k): ?>
                <div class="kaizen-item">
                    <div class="kaizen-agent"><?= htmlspecialchars($k['agent']) ?> · <?= htmlspecialchars($k['tokoh']) ?></div>
                    <a href="<?= htmlspecialchars($k['url']) ?>" target="_blank" class="kaizen-title" title="Buka video di YouTube">
                        📺 <?= htmlspecialchars($k['title']) ?> ↗
                    </a>
                    <div class="kaizen-desc"><?= htmlspecialchars($k['takeaway']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filter Controls -->
        <div class="filter-section">
            <div class="tabs">
                <a href="index.php?filter=all&status=<?= $statusFilter ?>" class="tab <?= $filter === 'all' ? 'active' : '' ?>">Semua Format (<?= $countAll ?>)</a>
                <a href="index.php?filter=carousel&status=<?= $statusFilter ?>" class="tab <?= $filter === 'carousel' ? 'active' : '' ?>">Carousel</a>
                <a href="index.php?filter=reels&status=<?= $statusFilter ?>" class="tab <?= $filter === 'reels' ? 'active' : '' ?>">Reels 1-Slide</a>
                <a href="index.php?filter=story&status=<?= $statusFilter ?>" class="tab <?= $filter === 'story' ? 'active' : '' ?>">Story Jualan</a>
                <a href="index.php?filter=crm&status=<?= $statusFilter ?>" class="tab <?= $filter === 'crm' ? 'active' : '' ?>">DM Closing</a>
            </div>
            
            <div class="status-filter-group">
                <a href="index.php?filter=<?= $filter ?>&status=all" class="status-pill <?= $statusFilter === 'all' ? 'active' : '' ?>">Semua (<?= $countAll ?>)</a>
                <a href="index.php?filter=<?= $filter ?>&status=pending" class="status-pill <?= $statusFilter === 'pending' ? 'active' : '' ?>">⏳ Perlu Desain (<?= $countPending ?>)</a>
                <a href="index.php?filter=<?= $filter ?>&status=designed" class="status-pill <?= $statusFilter === 'designed' ? 'active' : '' ?>">✅ Sudah Didesain (<?= $countDesigned ?>)</a>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid" id="contentGrid">
            <?php 
                $hariMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                $hariIni = $hariMap[date('N')] ?? 'Kamis';
            ?>
            <?php if (empty($filteredContents)): ?>
                <div style="text-align: center; padding: 3rem; background: var(--surface); border: 1px dashed var(--border); border-radius: 12px; color: var(--text-muted);">
                    Tidak ada naskah yang cocok dengan filter yang dipilih.
                </div>
            <?php else: ?>
                <?php foreach ($filteredContents as $item): ?>
                    <?php 
                        $channelLower = strtolower($item['channel']);
                        $tagClass = 'tag-carousel';
                        if (strpos($channelLower, 'reels') !== false) $tagClass = 'tag-reels';
                        if (strpos($channelLower, 'story') !== false) $tagClass = 'tag-story';
                        if (strpos($channelLower, 'crm') !== false) $tagClass = 'tag-crm';
                        $isToday = (stripos($item['day'], $hariIni) !== false);
                        $isDesigned = (($item['status'] ?? '') === 'designed');
                    ?>
                    <div class="content-card <?= $isDesigned ? 'is-designed' : '' ?>" id="card-<?= htmlspecialchars($item['id']) ?>" style="<?= ($isToday && !$isDesigned) ? 'border-color: #f59e0b; background: rgba(245, 158, 11, 0.04);' : '' ?>">
                        <div class="card-main">
                            <div class="card-meta">
                                <?php if ($isToday): ?>
                                    <span class="tag" style="background: #f59e0b; color: #000; font-weight: 800;">🔥 TAYANG HARI INI (<?= $hariIni ?>)</span>
                                <?php endif; ?>
                                <span class="tag <?= $tagClass ?>"><?= htmlspecialchars($item['channel']) ?></span>
                                <span class="tag tag-funnel"><?= htmlspecialchars($item['day']) ?> • <?= htmlspecialchars($item['funnel']) ?></span>
                                <span class="tag" id="status-badge-<?= htmlspecialchars($item['id']) ?>" style="<?= $isDesigned ? 'background: rgba(16, 185, 129, 0.2); color: #34d399;' : 'background: rgba(255,255,255,0.05); color: var(--text-muted);' ?>">
                                    <?= $isDesigned ? '✅ Sudah Didesain' : '⏳ Belum Didesain' ?>
                                </span>
                            </div>
                            <h3 class="card-title"><?= htmlspecialchars($item['title']) ?></h3>
                            <p class="card-hook">Target: <?= htmlspecialchars($item['target_product']) ?> — <?= htmlspecialchars($item['hook']) ?></p>
                        </div>
                        <div class="card-actions">
                            <!-- Checkbox Sudah Didesain -->
                            <label class="check-design-wrapper <?= $isDesigned ? 'checked' : '' ?>" id="check-wrapper-<?= htmlspecialchars($item['id']) ?>" title="Tandai jika naskah ini sudah selesai kamu desain di Canva">
                                <input type="checkbox" onchange="toggleDesignStatus('<?= htmlspecialchars($item['id']) ?>', this.checked)" <?= $isDesigned ? 'checked' : '' ?>>
                                <span id="check-text-<?= htmlspecialchars($item['id']) ?>"><?= $isDesigned ? 'Sudah Didesain' : 'Tandai Didesain' ?></span>
                            </label>

                            <!-- Tombol Buka Naskah -->
                            <a href="view.php?id=<?= urlencode($item['id']) ?>" class="btn-view" style="<?= ($isToday && !$isDesigned) ? 'background: #f59e0b; color: #000;' : '' ?>">
                                📄 Naskah Canva
                            </a>

                            <!-- Tombol Hapus -->
                            <button type="button" class="btn-delete" onclick="deleteContentItem('<?= htmlspecialchars($item['id']) ?>')" title="Hapus naskah ini dari daftar">
                                🗑️
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <script>
        function showToast(text, color = '#10b981', textColor = '#022c22') {
            const toast = document.getElementById('toast');
            toast.textContent = text;
            toast.style.background = color;
            toast.style.color = textColor;
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        function toggleDesignStatus(id, isChecked) {
            const status = isChecked ? 'designed' : 'ready_to_design';
            const card = document.getElementById('card-' + id);
            const wrapper = document.getElementById('check-wrapper-' + id);
            const badge = document.getElementById('status-badge-' + id);
            const checkText = document.getElementById('check-text-' + id);

            fetch('api_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'toggle_status', id: id, status: status })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (isChecked) {
                        card.classList.add('is-designed');
                        wrapper.classList.add('checked');
                        badge.textContent = '✅ Sudah Didesain';
                        badge.style.background = 'rgba(16, 185, 129, 0.2)';
                        badge.style.color = '#34d399';
                        checkText.textContent = 'Sudah Didesain';
                        showToast('✅ Ditandai: Selesai didesain!');
                    } else {
                        card.classList.remove('is-designed');
                        wrapper.classList.remove('checked');
                        badge.textContent = '⏳ Belum Didesain';
                        badge.style.background = 'rgba(255, 255, 255, 0.05)';
                        badge.style.color = 'var(--text-muted)';
                        checkText.textContent = 'Tandai Didesain';
                        showToast('ℹ️ Status dikembalikan ke Belum Didesain', '#3b82f6', '#fff');
                    }
                } else {
                    showToast('Gagal mengubah status: ' + (data.error || 'Error'), '#ef4444', '#fff');
                }
            })
            .catch(err => {
                showToast('Koneksi terganggu', '#ef4444', '#fff');
            });
        }

        function deleteContentItem(id) {
            if (!confirm('Yakin ingin menghapus naskah ini dari daftar Hub?')) {
                return;
            }

            const card = document.getElementById('card-' + id);
            card.classList.add('fade-out');

            fetch('api_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id: id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    setTimeout(() => {
                        card.remove();
                    }, 250);
                    showToast('🗑️ Naskah berhasil dihapus dari daftar');
                } else {
                    card.classList.remove('fade-out');
                    showToast('Gagal menghapus: ' + (data.error || 'Error'), '#ef4444', '#fff');
                }
            })
            .catch(err => {
                card.classList.remove('fade-out');
                showToast('Koneksi terganggu', '#ef4444', '#fff');
            });
        }
    </script>
</body>
</html>
