<?php
require_once __DIR__ . '/config.php';
check_auth();

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $channel = trim($_POST['channel'] ?? 'Carousel');
    $day = trim($_POST['day'] ?? 'Senin');
    $funnel = trim($_POST['funnel'] ?? 'TOFU');
    $targetProduct = trim($_POST['target_product'] ?? 'eBook Qila (Rp47rb)');
    $hook = trim($_POST['hook'] ?? $title);
    $caption = trim($_POST['caption'] ?? '');
    
    if (empty($title)) {
        $err = 'Judul konten wajib diisi.';
    } else {
        $id = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $channel . '-' . $day . '-' . time())));
        
        $slides = [];
        if (!empty($_POST['slides']) && is_array($_POST['slides'])) {
            foreach ($_POST['slides'] as $idx => $s) {
                $sTitle = clean_slide_text($s['title'] ?? '');
                $sContent = clean_slide_text($s['content'] ?? '');
                $sNote = clean_slide_text($s['design_note'] ?? '');
                
                if (!empty($sTitle) || !empty($sContent)) {
                    $slides[] = [
                        'slide_number' => count($slides) + 1,
                        'header' => 'Slide ' . (count($slides) + 1),
                        'title' => $sTitle,
                        'content' => $sContent,
                        'design_note' => $sNote
                    ];
                }
            }
        }

        $item = [
            'id' => $id,
            'channel' => $channel,
            'day' => $day,
            'funnel' => $funnel,
            'target_product' => $targetProduct,
            'title' => $title,
            'hook' => $hook,
            'slides' => $slides,
            'caption' => $caption,
            'raw_markdown' => '',
            'status' => 'ready_to_design',
            'audit_badge' => 'Input Manual / Approved'
        ];

        HubDB::upsertContent($item);
        header('Location: view.php?id=' . urlencode($id));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Konten Baru — EduMuslim Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #090a0f;
            --surface: #11131a;
            --card: #151822;
            --border: #232738;
            --text: #f0f2f8;
            --text-muted: #8b92a8;
            --primary: #10b981;
            --primary-hover: #059669;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            padding: 1.5rem;
            line-height: 1.5;
        }
        .container { max-width: 860px; margin: 0 auto; }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
        .btn-back { color: var(--text-muted); text-decoration: none; font-size: 0.85rem; font-weight: 600; }
        .btn-back:hover { color: var(--text); }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text); }
        input[type="text"], select, textarea {
            width: 100%;
            padding: 0.85rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-family: inherit;
            font-size: 0.95rem;
            outline: none;
        }
        input:focus, select:focus, textarea:focus { border-color: var(--primary); }
        .slide-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
        }
        .slide-title { font-size: 0.85rem; font-weight: 800; color: var(--primary); margin-bottom: 1rem; text-transform: uppercase; }
        .btn-submit {
            background: var(--primary);
            color: #022c22;
            padding: 0.9rem 2rem;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
        }
        .btn-submit:hover { background: #34d399; }
        .error-msg { background: rgba(239, 68, 68, 0.15); color: #ef4444; padding: 0.85rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <a href="index.php" class="btn-back">← Batal / Kembali ke Dashboard</a>
                <h1 style="font-size: 1.5rem; font-weight: 800; margin-top: 0.5rem;">Buat / Masukkan Konten Baru</h1>
            </div>
        </div>

        <?php if (!empty($err)): ?>
            <div class="error-msg">⚠️ <?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Judul Konten</label>
                <input type="text" name="title" placeholder="Contoh: Bangun Jam 7 Mau Rezeki Jam 3?" required>
            </div>

            <div class="grid-3">
                <div class="form-group">
                    <label>Channel / Format</label>
                    <select name="channel">
                        <option value="Carousel">Carousel Feed (7 Slide)</option>
                        <option value="Reels 1-Slide">Reels 1-Slide Loop</option>
                        <option value="Instagram Story">Instagram Story Sequence</option>
                        <option value="CRM / DM Closing">CRM / DM Closing</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Hari Tayang</label>
                    <select name="day">
                        <option value="Senin">Senin</option>
                        <option value="Selasa">Selasa</option>
                        <option value="Rabu">Rabu</option>
                        <option value="Kamis">Kamis</option>
                        <option value="Jumat">Jumat</option>
                        <option value="Sabtu">Sabtu</option>
                        <option value="Minggu">Minggu</option>
                        <option value="Harian">Harian</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Funnel Penjualan</label>
                    <select name="funnel">
                        <option value="TOFU">TOFU (Followers & Share)</option>
                        <option value="MOFU">MOFU (Edukasi & Save)</option>
                        <option value="BOFU">BOFU (Jualan Qila Rp47rb)</option>
                    </select>
                </div>
            </div>

            <h3 style="margin: 2rem 0 1rem; font-size: 1.1rem; font-weight: 700;">Naskah Per Slide (Slide 1–7)</h3>
            
            <?php for ($i = 1; $i <= 7; $i++): ?>
                <div class="slide-box">
                    <div class="slide-title">Slide <?= $i ?> <?= $i === 1 ? '(Cover / Hook)' : ($i === 7 ? '(Call To Action)' : '') ?></div>
                    <div class="form-group">
                        <label>Judul Slide (Maks 5 kata)</label>
                        <input type="text" name="slides[<?= $i ?>][title]" placeholder="<?= $i === 1 ? 'Contoh: Bangun Jam 7 Mau Rezeki Jam 3?' : 'Poin judul slide...' ?>">
                    </div>
                    <div class="form-group">
                        <label>Isi Teks Slide (Maks 20–25 kata, murni teks)</label>
                        <textarea name="slides[<?= $i ?>][content]" rows="2" placeholder="Tulis teks bersih yang akan diketik di Canva..."></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Catatan Desain (Opsional - Tidak akan ikut tersalin)</label>
                        <input type="text" name="slides[<?= $i ?>][design_note]" placeholder="Warna latar, tanpa makhluk hidup...">
                    </div>
                </div>
            <?php endfor; ?>

            <div class="form-group">
                <label>Caption Instagram & CTA</label>
                <textarea name="caption" rows="8" placeholder="Tulis caption lengkap..."></textarea>
            </div>

            <button type="submit" class="btn-submit">Simpan Konten ke Hub</button>
        </form>
    </div>
</body>
</html>
