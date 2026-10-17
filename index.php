<?php
require_once __DIR__ . '/config.php';
check_auth();

// Tangani aksi simpan metrik Threads dari Owner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_threads_data') {
    $briefFile = DATA_DIR . '/daily_brief.json';
    if (file_exists($briefFile)) {
        $briefData = json_decode(file_get_contents($briefFile), true);
        $briefData['evaluasi']['threads']['views'] = (int)($_POST['threads_views'] ?? 0);
        $briefData['evaluasi']['threads']['replies'] = (int)($_POST['threads_replies'] ?? 0);
        $briefData['evaluasi']['threads']['likes'] = (int)($_POST['threads_likes'] ?? 0);
        $briefData['evaluasi']['threads']['reposts'] = (int)($_POST['threads_reposts'] ?? 0);
        $briefData['evaluasi']['threads']['status'] = 'Data Threads kemarin berhasil disimpan (' . date('d M Y H:i') . ')';
        file_put_contents($briefFile, json_encode($briefData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    header('Location: index.php?tab=brief&saved=1');
    exit;
}

$contents = HubDB::getContents();
$sales = HubDB::getSalesStats();
$briefFile = DATA_DIR . '/daily_brief.json';
$dailyBrief = file_exists($briefFile) ? json_decode(file_get_contents($briefFile), true) : null;
$igCacheFile = DATA_DIR . '/ig_cache.json';
$igCache = file_exists($igCacheFile) ? json_decode(file_get_contents($igCacheFile), true) : null;

// Tab aktif (default: 'today')
$currentTab = $_GET['tab'] ?? 'today';
$validTabs = ['today', 'brief', 'archive'];
if (!in_array($currentTab, $validTabs)) {
    $currentTab = 'today';
}

$hariMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
$hariIni = $hariMap[date('N')] ?? 'Minggu';
$tanggalLengkap = date('d F Y');

// Filter HANYA konten yang wajib tayang HARI INI
$todayContents = [];
foreach ($contents as $c) {
    $cDay = $c['day'] ?? '';
    $cId = $c['id'] ?? '';
    $isToday = false;

    // Kecocokan hari eksplisit
    if (stripos($cDay, $hariIni) !== false) {
        $isToday = true;
    }
    // Story harian tayang tiap hari
    if ($cId === 'story-jualan-hari-ini' || stripos($c['channel'] ?? '', 'story') !== false) {
        $isToday = true;
    }
    // Threads harian sesuai hari
    if (stripos($cId, 'threads-' . strtolower($hariIni)) !== false) {
        $isToday = true;
    }

    if ($isToday) {
        $todayContents[] = $c;
    }
}

// Data Target Sales
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
    <title>EduMuslim Hub — <?= ($currentTab === 'today') ? 'Tugas Hari Ini' : (($currentTab === 'brief') ? 'Brief Harian & Riset' : 'Arsip Konten') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #090a0f;
            --sidebar-bg: #0d1017;
            --surface: #12151f;
            --card: #161925;
            --card-hover: #1c2132;
            --border: #23283b;
            --text: #f1f4fb;
            --text-muted: #8c94a9;
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
            min-height: 100vh;
            display: flex;
        }

        /* SIDEBAR STYLES */
        .sidebar {
            width: 270px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 1.5rem 1rem;
        }
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid var(--border);
            margin-bottom: 1.25rem;
        }
        .sidebar-logo {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #10b981, #047857);
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: #022c22;
            font-size: 1.15rem;
        }
        .sidebar-brand h1 {
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .sidebar-brand span {
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 500;
            display: block;
        }

        /* Mini Sales Widget di Sidebar */
        .sidebar-sales {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0.85rem;
            margin-bottom: 1.5rem;
        }
        .sidebar-sales-title {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 0.4rem;
        }
        .sidebar-sales-num {
            color: var(--primary);
            font-family: 'JetBrains Mono', monospace;
            font-weight: 800;
        }
        .sidebar-progress-bg {
            background: rgba(255, 255, 255, 0.06);
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
        }
        .sidebar-progress-fill {
            background: var(--primary);
            height: 100%;
            border-radius: 3px;
        }

        /* Nav Menu */
        .nav-group-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            font-weight: 700;
            margin-bottom: 0.5rem;
            padding-left: 0.5rem;
        }
        .nav-links {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            margin-bottom: 1.5rem;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 0.85rem;
            border-radius: 8px;
            font-size: 0.86rem;
            font-weight: 600;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.15s ease;
            border: 1px solid transparent;
        }
        .nav-item:hover {
            color: var(--text);
            background: rgba(255, 255, 255, 0.04);
        }
        .nav-item.active {
            background: rgba(16, 185, 129, 0.12);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.25);
            font-weight: 700;
        }
        .nav-item .badge {
            margin-left: auto;
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            font-size: 0.7rem;
            padding: 0.15rem 0.45rem;
            border-radius: 10px;
            font-weight: 700;
        }

        .sidebar-footer {
            border-top: 1px solid var(--border);
            padding-top: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .date-display {
            font-size: 0.75rem;
            color: var(--text-muted);
            padding-left: 0.25rem;
        }

        /* MAIN CONTENT AREA */
        .main-wrapper {
            margin-left: 270px;
            flex: 1;
            padding: 2rem;
            max-width: 1100px;
        }

        /* PAGE HEADER */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.75rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 1rem;
        }
        .page-title h2 {
            font-size: 1.4rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .page-title p {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: 0.2rem;
        }
        .header-actions {
            display: flex;
            gap: 0.6rem;
            align-items: center;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 0.85rem;
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text);
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-action:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* CARDS & GRIDS */
        .content-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.25rem;
            transition: border-color 0.2s;
        }
        .content-card:hover {
            border-color: rgba(16, 185, 129, 0.4);
        }
        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .channel-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            border: 1px solid var(--border);
        }
        .channel-pill.threads { border-color: rgba(59, 130, 246, 0.4); color: #60a5fa; }
        .channel-pill.story { border-color: rgba(245, 158, 11, 0.4); color: #fbbf24; }
        .channel-pill.carousel { border-color: rgba(16, 185, 129, 0.4); color: #34d399; }

        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.4rem;
            color: var(--text);
        }
        .card-hook {
            font-size: 0.88rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
            line-height: 1.45;
        }

        /* Naskah Preview Box */
        .script-box {
            background: #0d0f15;
            border: 1px solid #1a1e2b;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.84rem;
            line-height: 1.6;
            color: #d1d5db;
            max-height: 250px;
            overflow-y: auto;
            white-space: pre-line;
        }
        .card-bottom-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            border-top: 1px solid rgba(255, 255, 255, 0.04);
            padding-top: 0.85rem;
        }
        .btn-copy {
            padding: 0.45rem 0.85rem;
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-copy:hover {
            background: var(--primary);
            color: #022c22;
        }

        /* BRIEF HARIAN TAB STYLES */
        .brief-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .brief-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem;
        }
        .brief-card h3 {
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #f3f4f6;
        }
        .metric-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.82rem;
            padding: 0.4rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }
        .metric-row:last-child { border-bottom: none; }
        .metric-val {
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            color: #fff;
        }

        /* Input Form Threads */
        .threads-input-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-top: 0.85rem;
            padding-top: 0.85rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }
        .form-group label {
            display: block;
            font-size: 0.72rem;
            color: var(--text-muted);
            margin-bottom: 0.2rem;
        }
        .form-group input {
            width: 100%;
            background: #0b0d13;
            border: 1px solid var(--border);
            color: #fff;
            padding: 0.4rem 0.5rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-family: 'JetBrains Mono', monospace;
        }
        .btn-submit-threads {
            grid-column: span 2;
            padding: 0.5rem;
            background: var(--blue);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 0.25rem;
            transition: opacity 0.15s;
        }
        .btn-submit-threads:hover { opacity: 0.9; }

        /* Kaizen & Plan List */
        .kaizen-block {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
        }
        .kaizen-item {
            padding: 0.85rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }
        .kaizen-item:last-child { border-bottom: none; }
        .kaizen-agent-badge {
            font-size: 0.7rem;
            font-weight: 800;
            color: #60a5fa;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .kaizen-topic {
            font-size: 0.85rem;
            font-weight: 700;
            color: #f3f4f6;
            margin: 0.2rem 0;
        }
        .kaizen-insight {
            font-size: 0.8rem;
            color: var(--text-muted);
            line-height: 1.45;
        }

        .toast-msg {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid var(--primary);
            color: #34d399;
            padding: 0.65rem 1rem;
            border-radius: 8px;
            font-size: 0.82rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        @media (max-width: 860px) {
            body { flex-direction: column; }
            .sidebar {
                width: 100%;
                position: relative;
                border-right: none;
                border-bottom: 1px solid var(--border);
            }
            .main-wrapper {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>

    <!-- SIDEBAR NAVIGASI -->
    <aside class="sidebar">
        <div>
            <div class="sidebar-brand">
                <div class="sidebar-logo">EM</div>
                <div>
                    <h1>EduMuslim Hub</h1>
                    <span>@edumuslimin</span>
                </div>
            </div>

            <!-- Mini Sales Tracker -->
            <div class="sidebar-sales">
                <div class="sidebar-sales-title">
                    <span>Ebook Qila Target</span>
                    <span class="sidebar-sales-num"><?= $currentSales ?>/<?= $targetSales ?></span>
                </div>
                <div class="sidebar-progress-bg">
                    <div class="sidebar-progress-fill" style="width: <?= $percent ?>%;"></div>
                </div>
                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.35rem; display: flex; justify-content: space-between;">
                    <span>Omzet: Rp<?= $totalRevenue ?></span>
                    <span><?= $percent ?>%</span>
                </div>
            </div>

            <!-- Menu Utama (Fokus & Efisien) -->
            <div class="nav-group-label">Menu Kerja</div>
            <nav class="nav-links">
                <a href="index.php?tab=today" class="nav-item <?= ($currentTab === 'today') ? 'active' : '' ?>">
                    <span>🎯 Tugas Hari Ini</span>
                    <span class="badge"><?= count($todayContents) ?> Draft</span>
                </a>
                <a href="index.php?tab=brief" class="nav-item <?= ($currentTab === 'brief') ? 'active' : '' ?>">
                    <span>📋 Brief Harian & Riset</span>
                </a>
                <a href="index.php?tab=archive" class="nav-item <?= ($currentTab === 'archive') ? 'active' : '' ?>">
                    <span>🗃️ Arsip Seluruh Naskah</span>
                </a>
            </nav>

            <div class="nav-group-label">Aset & Platform</div>
            <nav class="nav-links">
                <a href="ebook.php" class="nav-item" target="_blank">
                    <span>📖 Draft Ebook Live</span>
                </a>
                <a href="https://app.edumuslim.my.id/pixelcrm/" class="nav-item" target="_blank">
                    <span>📊 PixelCRM ↗</span>
                </a>
            </nav>
        </div>

        <div class="sidebar-footer">
            <div class="date-display">
                📅 <strong><?= $hariIni ?></strong>, <?= $tanggalLengkap ?>
            </div>
            <div style="display: flex; gap: 0.4rem;">
                <a href="sync.php" class="btn-action" style="flex: 1; justify-content: center;" title="Sinkronkan berkas naskah lokal ke Hub">🔄 Sync</a>
                <a href="logout.php" class="btn-action" style="color: #ef4444;" title="Keluar">Keluar</a>
            </div>
        </div>
    </aside>

    <!-- KONTEN UTAMA -->
    <main class="main-wrapper">

        <?php if (isset($_GET['saved'])): ?>
            <div class="toast-msg">
                ✅ Data metrik Threads kemarin berhasil disimpan ke database brief harian!
            </div>
        <?php endif; ?>

        <?php if ($currentTab === 'today'): ?>
            <!-- TAB 1: TUGAS HARI INI (HANYA DRAFT YANG WAJIB DIPOSTING HARI INI) -->
            <div class="page-header">
                <div class="page-title">
                    <h2>🎯 Konten Tayang Hari Ini (<?= $hariIni ?>)</h2>
                    <p>Fokus penuh: Cukup salin naskah di bawah ini dan posting ke akun @edumuslimin.</p>
                </div>
                <div class="header-actions">
                    <span style="font-size: 0.8rem; background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); padding: 0.4rem 0.75rem; border-radius: 8px; font-weight: 700;">
                        Hari: <?= strtoupper($hariIni) ?>
                    </span>
                </div>
            </div>

            <?php if (empty($todayContents)): ?>
                <div class="content-card" style="text-align: center; padding: 3rem 1.5rem;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">☕</div>
                    <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.3rem;">Tidak ada jadwal posting untuk hari ini</h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem;">Seluruh naskah cadangan dapat dilihat di tab Arsip.</p>
                </div>
            <?php else: ?>
                <?php foreach ($todayContents as $c): ?>
                    <div class="content-card" id="card-<?= htmlspecialchars($c['id']) ?>">
                        <div class="card-top">
                            <div>
                                <span class="channel-pill <?= (stripos($c['channel'], 'threads') !== false) ? 'threads' : ((stripos($c['channel'], 'story') !== false) ? 'story' : 'carousel') ?>">
                                    <?= htmlspecialchars($c['channel']) ?>
                                </span>
                                <span style="font-size: 0.78rem; color: var(--text-muted); margin-left: 0.5rem;">
                                    Corong: <strong><?= htmlspecialchars($c['funnel'] ?? 'TOFU') ?></strong>
                                </span>
                            </div>
                            <div>
                                <span style="font-size: 0.78rem; background: rgba(255, 255, 255, 0.05); padding: 0.25rem 0.5rem; border-radius: 5px; color: var(--amber); font-weight: 600;">
                                    🎯 Target: <?= htmlspecialchars($c['target_product'] ?? 'eBook Qila') ?>
                                </span>
                            </div>
                        </div>

                        <div class="card-title"><?= htmlspecialchars($c['title'] ?? 'Naskah Tanpa Judul') ?></div>
                        <div class="card-hook"><?= htmlspecialchars($c['hook'] ?? '') ?></div>

                        <!-- Kotak Teks Naskah Siap Posting -->
                        <?php
                            $fullScript = "";
                            if (!empty($c['slides'])) {
                                foreach ($c['slides'] as $idx => $s) {
                                    $num = $idx + 1;
                                    $fullScript .= "Slide $num: " . ($s['title'] ?? '') . "\n" . ($s['content'] ?? '') . "\n\n";
                                }
                            } elseif (!empty($c['raw_content'])) {
                                $fullScript = $c['raw_content'];
                            } else {
                                $fullScript = $c['caption'] ?? '';
                            }
                        ?>
                        <div class="script-box" id="script-<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($fullScript) ?></div>

                        <div class="card-bottom-actions">
                            <div style="display: flex; gap: 0.5rem;">
                                <button class="btn-copy" onclick="copyToClipboard('script-<?= htmlspecialchars($c['id']) ?>')">
                                    📋 Salin Naskah
                                </button>
                                <a href="view.php?id=<?= urlencode($c['id']) ?>" class="btn-action" target="_blank">
                                    Lihat Tampilan Penuh ↗
                                </a>
                            </div>

                            <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.8rem; cursor: pointer; color: var(--text-muted);">
                                <input type="checkbox" onchange="toggleStatus('<?= htmlspecialchars($c['id']) ?>', this.checked)" <?= (($c['status'] ?? '') === 'designed') ? 'checked' : '' ?> style="accent-color: var(--primary);">
                                <span>Sudah Siap / Tayang</span>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        <?php elseif ($currentTab === 'brief'): ?>
            <!-- TAB 2: BRIEF HARIAN & RISET (HASIL TRIGGER "KERJA") -->
            <div class="page-header">
                <div class="page-title">
                    <h2>📋 Brief Harian, Riset, & Evaluasi Tim</h2>
                    <p>Hasil konsolidasi otomatis dari trigger keyword "kerja" oleh CEO EduMuslim.</p>
                </div>
                <div class="header-actions">
                    <span style="font-size: 0.78rem; color: var(--text-muted);">
                        Update: <?= $dailyBrief['updated_at'] ?? 'Hari ini' ?>
                    </span>
                </div>
            </div>

            <!-- BAGIAN 1: EVALUASI METRIK 3 KANAL -->
            <div class="brief-grid">
                <!-- Evaluasi PixelCRM -->
                <div class="brief-card">
                    <h3>💰 Evaluasi PixelCRM</h3>
                    <div class="metric-row">
                        <span style="color: var(--text-muted);">Sales Kemarin</span>
                        <span class="metric-val"><?= $dailyBrief['evaluasi']['pixelcrm']['sales_yesterday'] ?? 0 ?> order</span>
                    </div>
                    <div class="metric-row">
                        <span style="color: var(--text-muted);">Sales Qila Bulan Ini</span>
                        <span class="metric-val"><?= $dailyBrief['evaluasi']['pixelcrm']['sales_this_month'] ?? 0 ?> order</span>
                    </div>
                    <div class="metric-row">
                        <span style="color: var(--text-muted);">Order Pending</span>
                        <span class="metric-val" style="color: var(--amber);"><?= $dailyBrief['evaluasi']['pixelcrm']['pending_orders'] ?? 0 ?> invoice</span>
                    </div>
                    <div class="metric-row">
                        <span style="color: var(--text-muted);">Target 100 Qila</span>
                        <span class="metric-val" style="color: var(--primary);"><?= $currentSales ?> / 100</span>
                    </div>
                    <div style="margin-top: 0.75rem; font-size: 0.78rem; color: #93c5fd; background: rgba(59, 130, 246, 0.1); padding: 0.5rem; border-radius: 6px;">
                        📌 <?= $dailyBrief['evaluasi']['pixelcrm']['status'] ?? 'Buka corong konversi hari ini.' ?>
                    </div>
                </div>

                <!-- Evaluasi Instagram 24 Jam Terakhir -->
                <div class="brief-card">
                    <h3>📸 Instagram Feed (24 Jam Terakhir)</h3>
                    <div class="metric-row">
                        <span style="color: var(--text-muted);">Postingan 24 Jam Terakhir</span>
                        <span class="metric-val"><?= (!empty($igCache['posts_last_24h'])) ? count($igCache['posts_last_24h']) : 0 ?> post</span>
                    </div>
                    <div class="metric-row">
                        <span style="color: var(--text-muted);">Followers Akun</span>
                        <span class="metric-val"><?= $igCache['profile']['followers_count'] ?? 476 ?></span>
                    </div>
                    <div class="metric-row">
                        <span style="color: var(--text-muted);">Post Terakhir (Baseline)</span>
                        <span class="metric-val">16 Okt (Reach: <?= $igCache['latest_post']['insights']['reach'] ?? 14 ?>)</span>
                    </div>
                    <div class="metric-row">
                        <span style="color: var(--text-muted);">Interaksi Terakhir</span>
                        <span class="metric-val">Save: <?= $igCache['latest_post']['insights']['saved'] ?? 0 ?> | Share: <?= $igCache['latest_post']['insights']['shares'] ?? 1 ?></span>
                    </div>
                    <div style="margin-top: 0.75rem; font-size: 0.78rem; color: #fbbf24; background: rgba(245, 158, 11, 0.1); padding: 0.5rem; border-radius: 6px;">
                        ⚠️ Tidak ada postingan feed 24 jam terakhir. Pancing interaksi via IG Story & Reels.
                    </div>
                </div>

                <!-- Evaluasi Threads & Input Form -->
                <div class="brief-card">
                    <h3>🧵 Evaluasi Threads Kemarin</h3>
                    <div class="metric-row">
                        <span style="color: var(--text-muted);">Tayangan (Views)</span>
                        <span class="metric-val"><?= $dailyBrief['evaluasi']['threads']['views'] ?? '-' ?></span>
                    </div>
                    <div class="metric-row">
                        <span style="color: var(--text-muted);">Balasan (Replies)</span>
                        <span class="metric-val"><?= $dailyBrief['evaluasi']['threads']['replies'] ?? '-' ?></span>
                    </div>
                    <div class="metric-row">
                        <span style="color: var(--text-muted);">Suka (Likes)</span>
                        <span class="metric-val"><?= $dailyBrief['evaluasi']['threads']['likes'] ?? '-' ?></span>
                    </div>
                    <div class="metric-row">
                        <span style="color: var(--text-muted);">Status Data</span>
                        <span class="metric-val" style="color: var(--blue); font-size: 0.75rem;"><?= $dailyBrief['evaluasi']['threads']['status'] ?? 'Menunggu input Owner' ?></span>
                    </div>

                    <!-- Form Input Cepat Threads -->
                    <form method="POST" action="index.php?tab=brief" class="threads-input-form">
                        <input type="hidden" name="action" value="save_threads_data">
                        <div class="form-group">
                            <label>Views Utas</label>
                            <input type="number" name="threads_views" placeholder="0" value="<?= $dailyBrief['evaluasi']['threads']['views'] ?? '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Replies</label>
                            <input type="number" name="threads_replies" placeholder="0" value="<?= $dailyBrief['evaluasi']['threads']['replies'] ?? '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Likes</label>
                            <input type="number" name="threads_likes" placeholder="0" value="<?= $dailyBrief['evaluasi']['threads']['likes'] ?? '' ?>">
                        </div>
                        <div class="form-group">
                            <label>Reposts</label>
                            <input type="number" name="threads_reposts" placeholder="0" value="<?= $dailyBrief['evaluasi']['threads']['reposts'] ?? '' ?>">
                        </div>
                        <button type="submit" class="btn-submit-threads">Simpan Data Threads</button>
                    </form>
                </div>
            </div>

            <!-- BAGIAN 2: HASIL RISET LINTAS PLATFORM -->
            <div class="kaizen-block">
                <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1rem; color: #fff;">
                    🔍 Hasil Riset Lintas Platform (YouTube, IG, Threads)
                </h3>
                <?php if (!empty($dailyBrief['riset_lintas_platform'])): ?>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($dailyBrief['riset_lintas_platform'] as $r): ?>
                            <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: 8px; padding: 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
                                    <span style="font-size: 1.1rem;"><?= $r['icon'] ?></span>
                                    <strong style="color: #60a5fa; font-size: 0.9rem;"><?= htmlspecialchars($r['platform']) ?>: <?= htmlspecialchars($r['temon_kunci']) ?></strong>
                                </div>
                                <p style="font-size: 0.84rem; color: #d1d5db; line-height: 1.5; margin-bottom: 0.5rem;">
                                    <?= htmlspecialchars($r['detail']) ?>
                                </p>
                                <div style="font-size: 0.78rem; color: var(--primary); font-weight: 600;">
                                    💡 Dampak ke Eksekusi: <?= htmlspecialchars($r['dampak_ke_konten']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- BAGIAN 3: HASIL BELAJAR MANDIRI TIM (KAIZEN 5 AGEN) -->
            <div class="kaizen-block">
                <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1rem; color: #fff;">
                    🎓 Hasil Belajar Mandiri Tim (Daily Kaizen 5 Agen)
                </h3>
                <?php if (!empty($dailyBrief['kaizen'])): ?>
                    <?php foreach ($dailyBrief['kaizen'] as $kz): ?>
                        <div class="kaizen-item">
                            <div class="kaizen-agent-badge"><?= htmlspecialchars($kz['agent']) ?> • <?= htmlspecialchars($kz['mentor']) ?></div>
                            <div class="kaizen-topic"><?= htmlspecialchars($kz['materi']) ?></div>
                            <div class="kaizen-insight"><?= htmlspecialchars($kz['insight']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- BAGIAN 4: ARAHAN PLAN & PRIORITAS HARI INI -->
            <div class="kaizen-block" style="border-left: 4px solid var(--primary);">
                <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1rem; color: #fff;">
                    🚀 Arahan Plan & Langkah Aksi Hari Ini
                </h3>
                <?php if (!empty($dailyBrief['plan_dan_arahan'])): ?>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php foreach ($dailyBrief['plan_dan_arahan'] as $plan): ?>
                            <div style="display: flex; gap: 0.75rem; align-items: flex-start; background: rgba(255, 255, 255, 0.02); padding: 0.75rem; border-radius: 8px;">
                                <div style="width: 26px; height: 26px; background: rgba(16, 185, 129, 0.2); color: #34d399; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8rem; flex-shrink: 0;">
                                    <?= $plan['prioritas'] ?>
                                </div>
                                <div>
                                    <strong style="color: #fff; font-size: 0.88rem;"><?= htmlspecialchars($plan['aksi']) ?></strong>
                                    <span style="font-size: 0.75rem; color: var(--text-muted); margin-left: 0.4rem;">(<?= htmlspecialchars($plan['pelaksana']) ?>)</span>
                                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem; line-height: 1.4;">
                                        <?= htmlspecialchars($plan['catatan']) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($currentTab === 'archive'): ?>
            <!-- TAB 3: ARSIP SELURUH NASKAH (CADANGAN & HARI LAIN) -->
            <div class="page-header">
                <div class="page-title">
                    <h2>🗃️ Arsip Seluruh Naskah Konten & CRM</h2>
                    <p>Daftar lengkap naskah mingguan (Senin–Jumat) serta skrip follow-up CRM.</p>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($contents as $c): ?>
                    <div class="content-card" style="padding: 1.25rem;">
                        <div class="card-top">
                            <div>
                                <span class="channel-pill"><?= htmlspecialchars($c['channel']) ?></span>
                                <span style="font-size: 0.8rem; color: var(--text-muted); margin-left: 0.4rem;">Hari: <?= htmlspecialchars($c['day'] ?? '-') ?></span>
                            </div>
                            <a href="view.php?id=<?= urlencode($c['id']) ?>" class="btn-action" target="_blank">
                                Buka Naskah ↗
                            </a>
                        </div>
                        <div class="card-title" style="font-size: 1rem;"><?= htmlspecialchars($c['title'] ?? 'Naskah') ?></div>
                        <div class="card-hook" style="font-size: 0.82rem;"><?= htmlspecialchars($c['hook'] ?? '') ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </main>

    <script>
        function copyToClipboard(elementId) {
            const el = document.getElementById(elementId);
            if (!el) return;
            const text = el.innerText || el.textContent;
            navigator.clipboard.writeText(text).then(() => {
                alert('✅ Teks naskah berhasil disalin ke clipboard!');
            }).catch(err => {
                console.error('Gagal menyalin:', err);
            });
        }

        function toggleStatus(id, isChecked) {
            const newStatus = isChecked ? 'designed' : 'ready_to_design';
            fetch('api_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'toggle_status', id: id, status: newStatus })
            }).then(r => r.json()).then(data => {
                console.log('Status updated:', data);
            });
        }
    </script>
</body>
</html>
