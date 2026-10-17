<?php
require_once __DIR__ . '/config.php';
check_auth();

$sales = HubDB::getSalesStats();
$currentSales = $sales['current_sales'] ?? 0;
$targetSales = $sales['target_sales'] ?? 100;

// Waktu saat ini di WIB
$currentTime = date('H:i');
$currentHourMin = (int)date('Hi'); // e.g. 1030
$hariMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
$hariIni = $hariMap[date('N')] ?? 'Kamis';
$tanggalLengkap = date('d F Y');

// Deteksi jendela aktif
$activeSlot = '';
if ($currentHourMin >= 730 && $currentHourMin < 800) {
    $activeSlot = 'morning';
} elseif ($currentHourMin >= 805 && $currentHourMin < 1400) {
    $activeSlot = 'day';
} elseif ($currentHourMin >= 1700 && $currentHourMin <= 2100) {
    $activeSlot = 'evening';
} else {
    $activeSlot = 'off';
}

// Cari naskah hari ini di database
$contents = HubDB::getContents();
$todayContentId = 'carousel-kamis';
foreach ($contents as $c) {
    if (stripos($c['day'], $hariIni) !== false && stripos($c['channel'], 'carousel') !== false) {
        $todayContentId = $c['id'];
        break;
    }
}

// Data Jadwal Terpadu (Owner + Tim AI)
$schedule = [
    [
        'id' => 'morning',
        'time' => '07:30 – 08:00 WIB',
        'duration' => '30 Menit',
        'title' => 'Sesi Pagi: Morning Spark & Setup Cepat',
        'badge' => 'Setup & Interaksi',
        'is_active' => ($activeSlot === 'morning'),
        'team_activity' => [
            'scout-researcher memindai isu/keluhan bangun malam terbaru di warganet.',
            'growth-planner memastikan agenda naskah hari ini sudah terkunci di Hub.',
            'sales-conversion merekap invoice transaksi PixelCRM semalam.'
        ],
        'owner_tasks' => [
            [
                'time' => '07:30 - 07:35',
                'task' => 'Upload Story 1 di IG: Polling interaktif (contoh: "Semalam pasang alarm jam 3, bangun jam berapa?")',
                'tag' => 'Instagram Story'
            ],
            [
                'time' => '07:35 - 07:50',
                'task' => 'Balas DM masuk semalam dengan Skrip Alur 2 (kirim link penawaran eBook Qila Rp47.000).',
                'tag' => 'DM Closing'
            ],
            [
                'time' => '07:50 - 08:00',
                'task' => 'Buka PixelCRM di laptop: verifikasi pembayaran baru & catat calon pembeli pending.',
                'tag' => 'PixelCRM'
            ]
        ]
    ],
    [
        'id' => 'day',
        'time' => '08:05 – 14:00 WIB',
        'duration' => '~6 Jam',
        'title' => 'Sesi Siang: Deep Production & Traffic Spike',
        'badge' => 'Fokus Desain & Posting',
        'is_active' => ($activeSlot === 'day'),
        'team_activity' => [
            'voice-copywriter menyusun naskah bersih 7 slide untuk konten berikutnya.',
            'sales-conversion menyiapkan objection handling & list pemilih polling untuk disapa.',
            'CEO melakukan Quality Gate (memastikan bebas AI-slop & dalil shahih).'
        ],
        'owner_tasks' => [
            [
                'time' => '08:05 - 09:00',
                'task' => 'Desain Canva: Buka naskah hari ini di Hub, klik Salin Semua, paste ke template Canva.',
                'tag' => 'Canva Studio'
            ],
            [
                'time' => '09:00 - 09:30',
                'task' => '📱 WhatsApp Outreach Batch 1 (10 Kontak): Buka crm/status_pelanggan.php, kirim pesan izin Qila ke 10 alumni Ihfadz (tanpa link di awal).',
                'tag' => 'WA Outreach'
            ],
            [
                'time' => '11:00 - 11:30',
                'task' => 'Publish Carousel Feed Hari Ini. Pasang pinned comment: "Ketik QILA buat ambil akses buku".',
                'tag' => 'Instagram Feed'
            ],
            [
                'time' => '11:30 - 12:00',
                'task' => 'Golden 30 Minutes: Balas semua komentar postingan feed secara ramah & ajak ke DM.',
                'tag' => 'Engagement'
            ],
            [
                'time' => '12:30 - 13:00',
                'task' => 'Upload Story 2: Validasi masalah sulit bangun & teaser framework sepertiga malam.',
                'tag' => 'Instagram Story'
            ],
            [
                'time' => '13:00 - 13:30',
                'task' => '📱 WhatsApp Outreach Batch 2 (10 Kontak): Lanjut 10 kontak berikutnya di CRM + kirim link LP ke yang balas "Mau".',
                'tag' => 'WA Outreach'
            ],
            [
                'time' => '13:30 - 14:00',
                'task' => 'DM Sprint: Kirim penawaran Qila ke pemilih polling & komentator feed. Cek update PixelCRM.',
                'tag' => 'Closing Sales'
            ]
        ]
    ],
    [
        'id' => 'evening',
        'time' => '17:00 – 21:00 WIB',
        'duration' => '4 Jam',
        'title' => 'Sesi Malam: Prime Time Closing & Evaluasi',
        'badge' => 'Closing & Rekonsiliasi',
        'is_active' => ($activeSlot === 'evening'),
        'team_activity' => [
            'voice-copywriter menyiapkan draf utas Threads harian.',
            'account-auditor mengevaluasi metrik Save & Share postingan hari ini.',
            'sales-conversion menyiapkan pesan follow-up 6 jam untuk calon pembeli yang belum checkout.',
            'Sistem Hub merefresh counter pencapaian 100 sales eBook Qila.'
        ],
        'owner_tasks' => [
            [
                'time' => '16:00 - 16:30',
                'task' => '📱 WhatsApp Outreach Batch 3 (10 Kontak): Sesi penutup 10 kontak di CRM (Total 30 kontak hari ini) & rekap respon.',
                'tag' => 'WA Outreach'
            ],
            [
                'time' => '16:30 - 17:00',
                'task' => 'Posting Utas Threads: Buka tab Threads di Hub, salin 6 post draf, lalu posting beruntun di @edumuslimin.',
                'tag' => 'Threads'
            ],
            [
                'time' => '17:00 - 18:00',
                'task' => 'Follow-up DM (Skrip Alur 3): Sapa calon pembeli yang tadi siang dapat link tapi belum bayar.',
                'tag' => 'Follow-Up'
            ],
            [
                'time' => '19:30 - 20:00',
                'task' => 'Upload Story 3 & 4: Cuplikan isi bab Qila + Stiker Link Landing Page Qila (https://lp.edumuslim.my.id/lpqila).',
                'tag' => 'Instagram Story'
            ],
            [
                'time' => '20:00 - 20:45',
                'task' => 'Night Closing Sprint: Atasi keraguan calon pembeli di DM & dorong transfer sebelum tidur.',
                'tag' => 'Closing Sales'
            ],
            [
                'time' => '20:45 - 21:00',
                'task' => 'Buka PixelCRM: rekap total penjualan harian, pastikan akses terkirim, lalu tutup laptop.',
                'tag' => 'PixelCRM'
            ]
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal & Aksi Harian — EduMuslim Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #090a0f;
            --surface: #11131a;
            --card: #151822;
            --card-active: #191d2a;
            --border: #232738;
            --border-active: #3b435e;
            --text: #f0f2f8;
            --text-muted: #8b92a8;
            --primary: #10b981;
            --primary-glow: rgba(16, 185, 129, 0.12);
            --amber: #f59e0b;
            --amber-glow: rgba(245, 158, 11, 0.12);
            --blue: #3b82f6;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.5;
            padding-bottom: 5rem;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 960px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        /* Top Nav */
        nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 1.25rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 1rem;
        }
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .logo-sq {
            width: 36px;
            height: 36px;
            background: var(--primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: #022c22;
            font-size: 1.1rem;
        }
        .nav-brand h1 {
            font-size: 1.1rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .nav-links {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .nav-link {
            padding: 0.45rem 0.85rem;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 7px;
            text-decoration: none;
            color: var(--text-muted);
            border: 1px solid transparent;
            transition: opacity 150ms ease-out, background 150ms ease-out;
        }
        .nav-link:hover {
            color: var(--text);
            background: var(--surface);
        }
        .nav-link.active {
            color: var(--primary);
            background: var(--primary-glow);
            border-color: rgba(16, 185, 129, 0.25);
        }

        /* Header Info Card */
        .day-banner {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.25rem;
        }
        .day-info h2 {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 0.25rem;
        }
        .day-info p {
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        .day-stats {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .stat-pill {
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 0.6rem 1rem;
            border-radius: 10px;
            text-align: right;
        }
        .stat-pill .label { font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; }
        .stat-pill .val { font-size: 1.15rem; font-weight: 800; color: var(--primary); font-family: 'JetBrains Mono', monospace; }

        /* Session Cards */
        .session-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: border-color 150ms ease-out, transform 150ms ease-out;
        }
        .session-card.active-window {
            border-color: var(--amber);
            box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.25);
            background: var(--card-active);
        }
        .session-header {
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.015);
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .session-title-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .time-badge {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.05);
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid var(--border);
        }
        .session-card.active-window .time-badge {
            background: var(--amber-glow);
            color: var(--amber);
            border-color: rgba(245, 158, 11, 0.35);
        }
        .status-chip {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 999px;
            letter-spacing: 0.05em;
        }
        .chip-now {
            background: #ef4444;
            color: #fff;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        .session-body {
            padding: 1.5rem;
        }

        /* Tasks Checklist */
        .task-list {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            margin-bottom: 1.5rem;
        }
        .task-item {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0.9rem 1.1rem;
            cursor: pointer;
            user-select: none;
            transition: all 150ms ease-out;
        }
        .task-item:hover {
            border-color: #3b4258;
        }
        .task-item.done {
            opacity: 0.55;
            background: #0d0f15;
            border-color: transparent;
        }
        .task-item.done .task-text {
            text-decoration: line-through;
            color: var(--text-muted);
        }
        .custom-check {
            width: 20px;
            height: 20px;
            border-radius: 6px;
            border: 2px solid #4a526d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 2px;
            flex-shrink: 0;
            transition: all 120ms ease-out;
        }
        .task-item.done .custom-check {
            background: var(--primary);
            border-color: var(--primary);
            color: #022c22;
        }
        .task-content { flex: 1; }
        .task-time-tag {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.2rem;
            display: inline-block;
        }
        .task-text {
            font-size: 0.925rem;
            color: var(--text);
            line-height: 1.5;
        }

        /* Team AI Box */
        .ai-sync-box {
            background: rgba(16, 185, 129, 0.04);
            border: 1px dashed rgba(16, 185, 129, 0.2);
            border-radius: 10px;
            padding: 1rem 1.25rem;
            font-size: 0.8rem;
        }
        .ai-sync-title {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .ai-sync-box ul {
            padding-left: 1.2rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* Bottom Quick Action */
        .quick-bar {
            margin-top: 2rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .btn-action {
            background: var(--primary);
            color: #022c22;
            padding: 0.65rem 1.2rem;
            font-weight: 700;
            font-size: 0.85rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            transition: transform 120ms ease-out, background 120ms ease-out;
        }
        .btn-action:hover { background: #34d399; }
        .btn-action:active { transform: scale(0.98); }
        .btn-secondary {
            background: var(--card);
            color: var(--text);
            border: 1px solid var(--border);
            padding: 0.65rem 1.1rem;
            font-weight: 600;
            font-size: 0.85rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }
        .btn-secondary:hover { border-color: var(--primary); color: var(--primary); }
    </style>
</head>
<body>
    <div class="container">
        <!-- Top Navigation -->
        <nav>
            <div class="nav-brand">
                <div class="logo-sq">EM</div>
                <div>
                    <h1>EduMuslim Hub</h1>
                </div>
            </div>
            <div class="nav-links">
                <a href="daily.php" class="nav-link active">📅 Jadwal & Aksi Harian</a>
                <a href="index.php" class="nav-link">📄 Naskah Canva (Hub)</a>
                <a href="create.php" class="nav-link">➕ Tambah Konten</a>
                <a href="https://app.edumuslim.my.id/pixelcrm/" target="_blank" class="nav-link">📊 PixelCRM</a>
                <a href="logout.php" class="nav-link" style="color: #ef4444;">Keluar</a>
            </div>
        </nav>

        <!-- Day Banner -->
        <div class="day-banner">
            <div class="day-info">
                <h2>Rencana Aksi: <?= $hariIni ?>, <?= $tanggalLengkap ?></h2>
                <p>Jadwal kerja Owner disinkronkan langsung dengan mesin produksi tim AI EduMuslim.</p>
            </div>
            <div class="day-stats">
                <div class="stat-pill">
                    <div class="label">Jam Sekarang</div>
                    <div class="val"><?= $currentTime ?> WIB</div>
                </div>
                <div class="stat-pill">
                    <div class="label">Target Qila</div>
                    <div class="val"><?= $currentSales ?> / <?= $targetSales ?></div>
                </div>
            </div>
        </div>

        <!-- 3 Time Windows -->
        <?php foreach ($schedule as $s): ?>
            <div class="session-card <?= $s['is_active'] ? 'active-window' : '' ?>" id="card-<?= $s['id'] ?>">
                <div class="session-header">
                    <div class="session-title-group">
                        <span class="time-badge"><?= $s['time'] ?></span>
                        <h3 style="font-size: 1.05rem; font-weight: 700;"><?= $s['title'] ?></h3>
                        <?php if ($s['is_active']): ?>
                            <span class="status-chip chip-now">Aktif Sekarang</span>
                        <?php endif; ?>
                    </div>
                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;"><?= $s['duration'] ?></span>
                </div>

                <div class="session-body">
                    <!-- Owner Checklist -->
                    <div class="task-list">
                        <?php foreach ($s['owner_tasks'] as $tIdx => $t): ?>
                            <?php $taskKey = 'task_' . $s['id'] . '_' . $tIdx . '_' . date('Ymd'); ?>
                            <div class="task-item" onclick="toggleTask(this, '<?= $taskKey ?>')" data-key="<?= $taskKey ?>">
                                <div class="custom-check">✓</div>
                                <div class="task-content">
                                    <span class="task-time-tag"><?= $t['time'] ?> • <?= $t['tag'] ?></span>
                                    <div class="task-text"><?= htmlspecialchars($t['task']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- What AI Team Did in the Background -->
                    <div class="ai-sync-box">
                        <div class="ai-sync-title">🤖 Di Balik Layar: Pekerjaan Tim AI Anda</div>
                        <ul>
                            <?php foreach ($s['team_activity'] as $act): ?>
                                <li><?= htmlspecialchars($act) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Quick Action Bar -->
        <div class="quick-bar">
            <div>
                <strong style="font-size: 0.95rem; display: block;">Materi Carousel Hari Ini: <?= $hariIni ?></strong>
                <span style="font-size: 0.8rem; color: var(--text-muted);">Naskah sudah diaudit & siap diketik di Canva.</span>
            </div>
            <div style="display: flex; gap: 0.6rem;">
                <a href="view.php?id=<?= urlencode($todayContentId) ?>" class="btn-action">
                    📄 Buka Naskah <?= $hariIni ?>
                </a>
                <a href="https://canva.com" target="_blank" class="btn-secondary">
                    🎨 Buka Canva
                </a>
            </div>
        </div>
    </div>

    <!-- Idempotent Persistent Checklist Script (LocalStorage) -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.task-item').forEach(item => {
                const key = item.getAttribute('data-key');
                if (localStorage.getItem(key) === '1') {
                    item.classList.add('done');
                }
            });
        });

        function toggleTask(elem, key) {
            elem.classList.toggle('done');
            if (elem.classList.contains('done')) {
                localStorage.setItem(key, '1');
            } else {
                localStorage.removeItem(key);
            }
        }
    </script>
</body>
</html>
