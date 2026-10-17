<?php
require_once __DIR__ . '/config.php';
// Boleh diakses publik atau butuh login (kita buat bebas baca bila ada token atau via hub)
// check_auth(); // opsional jika mau login atau publik

$ebookDir = __DIR__ . '/data/ebooks/sisa-usia';
if (!is_dir($ebookDir)) {
    $ebookDir = '/home/fadil/edumuslim-team/ebooks/sisa-usia';
}

// Daftar bab & file
$chapters = [
    [
        'id' => 'kata-pengantar',
        'badge' => 'Mukaddimah',
        'title' => 'Kata Pengantar',
        'file' => 'kata-pengantar.md'
    ],
    [
        'id' => 'bab-1',
        'badge' => 'Bab 1',
        'title' => 'Hampir Sampai',
        'file' => 'bab-1.md'
    ],
    [
        'id' => 'bab-2',
        'badge' => 'Bab 2',
        'title' => 'Kaidah Emas',
        'file' => 'bab-2.md'
    ],
    [
        'id' => 'bab-3',
        'badge' => 'Bab 3 (Segera)',
        'title' => 'Amalan Rumah',
        'file' => 'bab-3.md'
    ],
    [
        'id' => 'bab-4',
        'badge' => 'Bab 4 (Segera)',
        'title' => 'Pelit Waktu',
        'file' => 'bab-4.md'
    ],
    [
        'id' => 'bab-5',
        'badge' => 'Bab 5 (Segera)',
        'title' => 'Bebas Hisab',
        'file' => 'bab-5.md'
    ]
];

function render_markdown($text) {
    $lines = explode("\n", $text);
    $out = [];
    $inBox = false;

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '') continue;

        if ($trimmed === '---') {
            if ($inBox) {
                $out[] = "</div>";
                $inBox = false;
            }
            $out[] = "<hr class='ebook-divider'>";
            continue;
        }

        // Lembar Evaluasi
        if (strpos($trimmed, '### Lembar Evaluasi') === 0 || strpos($trimmed, '### LEMBAR EVALUASI') === 0) {
            $title = htmlspecialchars(str_replace('### ', '', $trimmed));
            $out[] = "<div class='ebook-worksheet'><div class='worksheet-header'>📝 " . $title . "</div>";
            $inBox = true;
            continue;
        }

        // Judul bab header
        if (in_array($trimmed, ['Kata Pengantar', 'Bab 1', 'Bab 2', 'Bab 3', 'Bab 4', 'Bab 5', 'Penutup'])) {
            $out[] = "<div class='chapter-tag'>" . htmlspecialchars($trimmed) . "</div>";
            continue;
        }

        if (in_array($trimmed, ['Hampir Sampai', 'Kaidah Emas', 'Amalan Rumah', 'Pelit Waktu', 'Bebas Hisab'])) {
            $out[] = "<h2 class='chapter-title'>" . htmlspecialchars($trimmed) . "</h2>";
            continue;
        }

        // Teks Arab (deteksi karakter arab)
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $trimmed)) {
            $out[] = "<div class='arabic-block' dir='rtl'>" . $trimmed . "</div>";
            continue;
        }

        // Formatting
        $safe = htmlspecialchars($trimmed);
        // Bold
        $safe = preg_replace('/\*\*(.+?)\*\*/', '<strong class="highlight-bold">$1</strong>', $safe);
        // Italic
        $safe = preg_replace('/\*([^\*]+)\*/', '<em class="text-italic">$1</em>', $safe);

        // List item
        if (preg_match('/^(\d+\.)\s*(.+)/', $trimmed, $m)) {
            $num = $m[1];
            $body = preg_replace('/\*\*(.+?)\*\*/', '<strong class="highlight-bold">$1</strong>', htmlspecialchars($m[2]));
            $body = preg_replace('/\*([^\*]+)\*/', '<em class="text-italic">$1</em>', $body);
            $out[] = "<div class='list-item'><span class='list-num'>{$num}</span><span>{$body}</span></div>";
            continue;
        }

        // Socratic questions
        if (strpos($trimmed, 'Bukankah ') === 0 || substr($trimmed, -1) === '?') {
            $out[] = "<p class='socratic-q'>" . $safe . "</p>";
            continue;
        }

        $out[] = "<p class='ebook-p'>" . $safe . "</p>";
    }

    if ($inBox) {
        $out[] = "</div>";
    }

    return implode("\n", $out);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sisa Usia - Ebook Reader | EduMuslim Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Anti-slop Design System + Emil Kowalski fluid dynamics */
        :root {
            --bg: #090a0f;
            --surface: #11131a;
            --card: #151822;
            --border: #232738;
            --border-subtle: #1a1e2d;
            --text-main: #f0f2f8;
            --text-body: #cbd5e1;
            --text-muted: #8b92a8;
            --emerald: #10b981;
            --emerald-soft: rgba(16, 185, 129, 0.12);
            --amber: #f59e0b;
            --amber-soft: rgba(245, 158, 11, 0.12);
            --ease-out: cubic-bezier(0.23, 1, 0.32, 1);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg);
            color: var(--text-body);
            line-height: 1.7;
            -webkit-font-smoothing: antialiased;
            padding-bottom: 5rem;
        }

        /* Top Sticky Bar */
        .reader-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(9, 10, 15, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 0.75rem 1rem;
        }

        .header-content {
            max-width: 768px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.4rem 0.75rem;
            border-radius: 8px;
            transition: color 150ms var(--ease-out), background 150ms var(--ease-out), transform 160ms var(--ease-out);
        }

        .back-link:hover {
            color: var(--text-main);
            background: var(--surface);
        }

        .back-link:active {
            transform: scale(0.97);
        }

        .book-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 0.25rem 0.65rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--amber);
        }

        /* Container */
        .reader-container {
            max-width: 680px;
            margin: 0 auto;
            padding: 1.5rem 1rem;
        }

        /* Cover Hero */
        .book-hero {
            background: linear-gradient(180deg, #131722 0%, #0d0f17 100%);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.25rem 1.5rem;
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
        }

        .book-badge {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--emerald);
            background: var(--emerald-soft);
            border: 1px solid rgba(16, 185, 129, 0.25);
            padding: 0.2rem 0.65rem;
            border-radius: 6px;
            margin-bottom: 0.85rem;
        }

        .book-title {
            font-size: 2.25rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: #ffffff;
            line-height: 1.15;
            margin-bottom: 0.6rem;
        }

        .book-subtitle {
            font-size: 0.95rem;
            color: #94a3b8;
            font-weight: 500;
            line-height: 1.5;
            max-width: 480px;
            margin: 0 auto 1.5rem auto;
        }

        .book-meta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.85rem;
            font-size: 0.75rem;
            color: var(--text-muted);
            border-top: 1px solid var(--border-subtle);
            padding-top: 1rem;
        }

        .book-meta strong {
            color: var(--text-main);
        }

        /* Nav Pills (Bab Quick Jump) */
        .chapter-nav {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 0.75rem;
            margin-bottom: 2rem;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .chapter-nav::-webkit-scrollbar { display: none; }

        .nav-chip {
            flex-shrink: 0;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.4rem 0.85rem;
            border-radius: 8px;
            background: var(--card);
            border: 1px solid var(--border);
            color: var(--text-muted);
            transition: transform 160ms var(--ease-out), color 150ms var(--ease-out), border-color 150ms var(--ease-out);
        }
        .nav-chip:active {
            transform: scale(0.97);
        }
        .nav-chip:hover {
            color: var(--text-main);
            border-color: var(--emerald);
        }

        /* Chapter Card */
        .chapter-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.75rem 1.5rem;
            margin-bottom: 2rem;
            scroll-margin-top: 4.5rem;
        }

        .chapter-tag {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--amber);
            background: var(--amber-soft);
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            margin-bottom: 0.6rem;
        }

        .chapter-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.02em;
            line-height: 1.25;
            margin-bottom: 1.5rem;
        }

        /* Typography & Anti-slop Airflow */
        .ebook-p {
            font-size: 1.05rem;
            color: #d1d5db;
            margin-bottom: 1.25rem;
            line-height: 1.75;
        }

        .highlight-bold {
            color: #ffffff;
            font-weight: 700;
            background: rgba(245, 158, 11, 0.15);
            padding: 0.1rem 0.35rem;
            border-radius: 4px;
        }

        .text-italic {
            font-style: italic;
            color: #e2e8f0;
        }

        .socratic-q {
            font-size: 1.1rem;
            font-weight: 700;
            color: #fde68a;
            padding-left: 0.85rem;
            border-left: 3px solid var(--amber);
            margin: 1.5rem 0;
            line-height: 1.5;
        }

        .arabic-block {
            font-family: 'Amiri', serif;
            font-size: 1.65rem;
            line-height: 2.3;
            text-align: right;
            color: #6ee7b7;
            background: rgba(16, 185, 129, 0.06);
            border: 1px solid rgba(16, 185, 129, 0.2);
            padding: 1rem 1.25rem;
            border-radius: 10px;
            margin: 1.5rem 0;
        }

        .ebook-divider {
            border: 0;
            height: 1px;
            background: var(--border);
            margin: 2rem 0;
        }

        .list-item {
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            font-size: 1.05rem;
            margin-bottom: 0.75rem;
            color: #e2e8f0;
        }

        .list-num {
            font-weight: 800;
            color: var(--amber);
            flex-shrink: 0;
        }

        /* Worksheet Card */
        .ebook-worksheet {
            background: rgba(16, 185, 129, 0.05);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 12px;
            padding: 1.25rem;
            margin: 1.75rem 0 0.5rem 0;
        }

        .worksheet-header {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--emerald);
            margin-bottom: 1rem;
            border-bottom: 1px dashed rgba(16, 185, 129, 0.3);
            padding-bottom: 0.5rem;
        }

        /* Coming Soon Chapter Card */
        .chapter-card-locked {
            opacity: 0.6;
            border: 1px dashed var(--border);
            background: transparent;
        }

        /* Mobile Adjustments */
        @media (max-width: 640px) {
            .book-title { font-size: 1.85rem; }
            .chapter-title { font-size: 1.35rem; }
            .arabic-block { font-size: 1.45rem; line-height: 2.1; }
            .ebook-p { font-size: 1rem; }
            .chapter-card { padding: 1.25rem 1rem; }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="reader-header">
        <div class="header-content">
            <a href="index.php" class="back-link">
                ← Kembali ke Hub
            </a>
            <div class="book-pill">
                <span>📖</span> Sisa Usia
            </div>
        </div>
    </header>

    <main class="reader-container">
        <!-- Hero Cover -->
        <section class="book-hero">
            <span class="book-badge">Naskah Live Draft</span>
            <h1 class="book-title">SISA USIA</h1>
            <p class="book-subtitle">Rahasia Menyelamatkan Akhir Hayat Meski Masa Muda Penuh Kelalaian</p>
            <div class="book-meta">
                <span>Penulis: <strong>Fadil Muhammad</strong></span>
                <span>•</span>
                <span>Penerbit: <strong>EduMuslim</strong></span>
            </div>
        </section>

        <!-- Chapter Chips Quick Nav -->
        <nav class="chapter-nav">
            <?php foreach ($chapters as $ch): ?>
                <a href="#<?= $ch['id'] ?>" class="nav-chip">
                    <?= htmlspecialchars($ch['title']) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Chapter Contents -->
        <?php foreach ($chapters as $ch): ?>
            <?php 
                $filePath = $ebookDir . '/' . $ch['file'];
                $exists = file_exists($filePath);
            ?>
            <article id="<?= $ch['id'] ?>" class="chapter-card <?= !$exists ? 'chapter-card-locked' : '' ?>">
                <span class="chapter-tag"><?= htmlspecialchars($ch['badge']) ?></span>
                <h2 class="chapter-title"><?= htmlspecialchars($ch['title']) ?></h2>

                <?php if ($exists): ?>
                    <div class="chapter-body">
                        <?= render_markdown(file_get_contents($filePath)) ?>
                    </div>
                <?php else: ?>
                    <p class="ebook-p text-italic" style="color: var(--text-muted);">
                        🔒 Bab ini sedang dalam tahap review dan penyusunan poin bahasan oleh tim EduMuslim.
                    </p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </main>

</body>
</html>
