<?php
require_once __DIR__ . '/config.php';
check_auth();

$id = $_GET['id'] ?? '';
$item = HubDB::findContentById($id);

if (!$item) {
    header('Location: index.php');
    exit;
}

// Format full text untuk "Salin Semua Naskah" yang 100% bersih untuk Canva
$fullTextForCanva = $item['title'] . "\n\n";

if (!empty($item['slides'])) {
    foreach ($item['slides'] as $s) {
        $cleanTitle = clean_slide_text($s['title']);
        $cleanBody = clean_slide_text($s['content']);
        $fullTextForCanva .= "[Slide " . $s['slide_number'] . "]\n";
        if (!empty($cleanTitle) && stripos($cleanBody, $cleanTitle) === false) {
            $fullTextForCanva .= $cleanTitle . "\n";
        }
        $fullTextForCanva .= $cleanBody . "\n\n";
    }
} else {
    $fullTextForCanva .= clean_slide_text($item['raw_markdown']) . "\n\n";
}

if (!empty($item['caption'])) {
    $fullTextForCanva .= "[CAPTION INSTAGRAM]\n" . trim($item['caption']) . "\n";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($item['title']) ?> — Dokumen Naskah Canva</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0b0d13;
            --surface: #12151f;
            --card: #181b28;
            --border: #23273a;
            --text: #f0f2f8;
            --text-muted: #8b92a8;
            --primary: #10b981;
            --primary-hover: #059669;
            --primary-glow: rgba(16, 185, 129, 0.15);
            --amber: #f59e0b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
            padding-bottom: 5rem;
        }

        /* Sticky Action Bar */
        .sticky-bar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(11, 13, 19, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 0.85rem 1.5rem;
        }
        .bar-inner {
            max-width: 860px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .bar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .btn-back {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: color 0.2s;
        }
        .btn-back:hover { color: var(--text); }
        .bar-actions {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .btn-main-copy {
            background: var(--primary);
            color: #022c22;
            border: none;
            padding: 0.65rem 1.25rem;
            font-weight: 700;
            font-size: 0.85rem;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            transition: all 0.2s;
        }
        .btn-main-copy:hover {
            background: #34d399;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
            transform: translateY(-1px);
        }
        .btn-canva {
            background: #7d2ae8;
            color: #fff;
            text-decoration: none;
            padding: 0.65rem 1.1rem;
            font-weight: 700;
            font-size: 0.85rem;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            transition: all 0.2s;
        }
        .btn-canva:hover {
            background: #6b1fd4;
        }
        .btn-outline {
            background: var(--surface);
            color: var(--text);
            border: 1px solid var(--border);
            text-decoration: none;
            padding: 0.65rem 0.9rem;
            font-weight: 600;
            font-size: 0.85rem;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.2s;
        }
        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Document Article Layout */
        .doc-container {
            max-width: 860px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }
        .doc-header {
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        .doc-badges {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        .badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .badge-channel { background: var(--primary-glow); color: var(--primary); border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-funnel { background: rgba(255, 255, 255, 0.05); color: var(--text-muted); border: 1px solid var(--border); }
        .badge-audit { background: rgba(245, 158, 11, 0.1); color: var(--amber); border: 1px solid rgba(245, 158, 11, 0.3); }
        .doc-title {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.3;
            margin-bottom: 0.75rem;
            color: #ffffff;
        }
        .doc-hook {
            font-size: 1.05rem;
            color: #a5adcb;
            line-height: 1.6;
        }

        /* Notice Box */
        .rule-notice {
            background: #11141d;
            border-left: 4px solid var(--amber);
            border-radius: 8px;
            padding: 1rem 1.25rem;
            margin-bottom: 2.5rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        .rule-notice strong { color: var(--text); }

        /* Slide Article Sections */
        .slide-article-section {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.75rem;
            margin-bottom: 1.5rem;
            position: relative;
            transition: border-color 0.2s;
        }
        .slide-article-section:hover {
            border-color: #3b4258;
        }
        .slide-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .slide-num {
            font-size: 0.8rem;
            font-weight: 800;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-copy-part {
            background: transparent;
            color: var(--text-muted);
            border: 1px solid var(--border);
            padding: 0.35rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-copy-part:hover {
            color: var(--primary);
            border-color: var(--primary);
            background: var(--primary-glow);
        }
        .slide-heading {
            font-size: 1.25rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.75rem;
            line-height: 1.4;
        }
        .slide-body {
            font-size: 1rem;
            color: #e2e8f0;
            line-height: 1.7;
            white-space: pre-wrap;
        }
        .slide-note {
            margin-top: 1rem;
            padding-top: 0.75rem;
            border-top: 1px dashed rgba(255, 255, 255, 0.08);
            font-size: 0.8rem;
            color: #94a3b8;
            font-style: italic;
        }

        /* Caption Section */
        .caption-section {
            background: #0f131c;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.75rem;
            margin-top: 2.5rem;
        }
        .caption-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .caption-title {
            font-size: 1.1rem;
            font-weight: 700;
        }
        .caption-body {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            color: #cbd5e1;
            background: #090b10;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1.25rem;
            white-space: pre-wrap;
            line-height: 1.7;
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
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Sticky Top Navigation -->
    <div class="sticky-bar">
        <div class="bar-inner">
            <div class="bar-left">
                <a href="index.php" class="btn-back">← Kembali</a>
                <span style="font-weight: 700; font-size: 0.9rem;"><?= htmlspecialchars($item['day']) ?>: <?= htmlspecialchars($item['channel']) ?></span>
            </div>
            <div class="bar-actions">
                <button class="btn-main-copy" onclick="copyFullContent()">
                    📋 Salin Semua Naskah
                </button>
                <a href="https://canva.com" target="_blank" class="btn-canva">
                    🎨 Buka Canva
                </a>
                <a href="edit.php?id=<?= urlencode($item['id']) ?>" class="btn-outline">
                    ✏️ Edit
                </a>
            </div>
        </div>
    </div>

    <!-- Hidden Raw Content for 1-Click Copy -->
    <textarea id="fullContentData" style="display:none;"><?= htmlspecialchars($fullTextForCanva) ?></textarea>

    <!-- Document Body -->
    <div class="doc-container">
        <div class="doc-header">
            <div class="doc-badges">
                <span class="badge badge-channel"><?= htmlspecialchars($item['channel']) ?></span>
                <span class="badge badge-funnel"><?= htmlspecialchars($item['day']) ?> • <?= htmlspecialchars($item['funnel']) ?></span>
                <span class="badge badge-audit">✅ Lolos Audit Marketing & CEO</span>
            </div>
            <h1 class="doc-title"><?= htmlspecialchars($item['title']) ?></h1>
            <p class="doc-hook"><?= htmlspecialchars($item['hook']) ?></p>
        </div>

        <div class="rule-notice">
            <strong>🎨 Panduan Desain Canva:</strong> Naskah di bawah sudah disaring bersih dari kata-kata AI generik. Gunakan visual minimalis, tipografi kontras tebal, dan <strong>100% tanpa gambar makhluk bernyawa</strong>.
        </div>

        <!-- Slides List as Clean Document -->
        <?php if (!empty($item['slides'])): ?>
            <?php foreach ($item['slides'] as $s): ?>
                <?php 
                    $cleanTitle = clean_slide_text($s['title']);
                    $cleanBody = clean_slide_text($s['content']);
                    $slideTextToCopy = (!empty($cleanTitle) && stripos($cleanBody, $cleanTitle) === false ? $cleanTitle . "\n" : "") . $cleanBody;
                ?>
                <div class="slide-article-section" id="slide-<?= $s['slide_number'] ?>">
                    <div class="slide-top">
                        <div class="slide-num">Slide <?= $s['slide_number'] ?></div>
                        <button class="btn-copy-part" onclick="copySnippet(<?= htmlspecialchars(json_encode($slideTextToCopy)) ?>, 'Slide <?= $s['slide_number'] ?>')">
                            Salin Slide Ini
                        </button>
                    </div>

                    <?php if (!empty($cleanTitle) && stripos($cleanBody, $cleanTitle) === false): ?>
                        <div class="slide-heading"><?= htmlspecialchars($cleanTitle) ?></div>
                    <?php endif; ?>

                    <div class="slide-body"><?= nl2br(htmlspecialchars($cleanBody)) ?></div>

                    <?php if (!empty($s['design_note'])): ?>
                        <div class="slide-note">💡 Catatan: <?= htmlspecialchars($s['design_note']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Fallback if full markdown document -->
            <div class="slide-article-section">
                <div class="slide-body"><?= nl2br(htmlspecialchars($item['raw_markdown'])) ?></div>
            </div>
        <?php endif; ?>

        <!-- Caption Section -->
        <?php if (!empty($item['caption'])): ?>
            <div class="caption-section">
                <div class="caption-top">
                    <div class="caption-title">📝 Naskah Caption & Penutup CTA</div>
                    <button class="btn-copy-part" onclick="copySnippet(<?= htmlspecialchars(json_encode($item['caption'])) ?>, 'Caption')">
                        Salin Caption
                    </button>
                </div>
                <div class="caption-body"><?= htmlspecialchars($item['caption']) ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">✅ Naskah berhasil disalin! Silakan paste ke Canva.</div>

    <script>
        function showToast(msg) {
            const toast = document.getElementById('toast');
            toast.textContent = msg;
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 2500);
        }

        function copyFullContent() {
            const text = document.getElementById('fullContentData').value;
            navigator.clipboard.writeText(text).then(() => {
                showToast('✅ Semua naskah berhasil disalin ke clipboard!');
            }).catch(err => {
                alert('Gagal menyalin: ' + err);
            });
        }

        function copySnippet(text, label) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('✅ ' + label + ' berhasil disalin!');
            }).catch(err => {
                alert('Gagal menyalin: ' + err);
            });
        }
    </script>
</body>
</html>
