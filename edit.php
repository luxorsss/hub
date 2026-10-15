<?php
require_once __DIR__ . '/config.php';
check_auth();

$id = $_GET['id'] ?? '';
$item = HubDB::findContentById($id);

if (!$item) {
    header('Location: index.php');
    exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item['title'] = trim($_POST['title'] ?? $item['title']);
    $item['hook'] = trim($_POST['hook'] ?? $item['hook']);
    $item['caption'] = trim($_POST['caption'] ?? $item['caption']);
    
    // Update slides
    if (!empty($_POST['slides']) && is_array($_POST['slides'])) {
        foreach ($_POST['slides'] as $idx => $sData) {
            if (isset($item['slides'][$idx])) {
                $item['slides'][$idx]['title'] = trim($sData['title'] ?? '');
                $item['slides'][$idx]['content'] = trim($sData['content'] ?? '');
                $item['slides'][$idx]['design_note'] = trim($sData['design_note'] ?? '');
            }
        }
    }

    HubDB::upsertContent($item);
    $msg = 'Perubahan berhasil disimpan!';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit: <?= htmlspecialchars($item['title']) ?> — EduMuslim Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400&display=swap" rel="stylesheet">
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
        .container {
            max-width: 860px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
        .btn-back {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .btn-back:hover { color: var(--text); }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text);
        }
        input[type="text"], textarea {
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
        input[type="text"]:focus, textarea:focus {
            border-color: var(--primary);
        }
        textarea {
            resize: vertical;
        }
        .slide-edit-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
        }
        .slide-header {
            font-size: 0.85rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 1rem;
            text-transform: uppercase;
        }
        .btn-save {
            background: var(--primary);
            color: #022c22;
            padding: 0.85rem 1.75rem;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-save:hover {
            background: #34d399;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            color: var(--primary);
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 0.85rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <a href="view.php?id=<?= urlencode($item['id']) ?>" class="btn-back">← Batal / Kembali ke Naskah</a>
                <h1 style="font-size: 1.5rem; font-weight: 800; margin-top: 0.5rem;">Edit Naskah</h1>
            </div>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert-success">✅ <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Judul Konten</label>
                <input type="text" name="title" value="<?= htmlspecialchars($item['title']) ?>" required>
            </div>

            <div class="form-group">
                <label>Hook / Catatan Target</label>
                <input type="text" name="hook" value="<?= htmlspecialchars($item['hook']) ?>">
            </div>

            <h3 style="margin: 2rem 0 1rem; font-size: 1.1rem; font-weight: 700;">Teks Per Slide</h3>
            <?php if (!empty($item['slides'])): ?>
                <?php foreach ($item['slides'] as $idx => $s): ?>
                    <div class="slide-edit-box">
                        <div class="slide-header">Slide <?= $s['slide_number'] ?></div>
                        
                        <div class="form-group">
                            <label>Judul Slide</label>
                            <input type="text" name="slides[<?= $idx ?>][title]" value="<?= htmlspecialchars($s['title']) ?>">
                        </div>

                        <div class="form-group">
                            <label>Isi Teks Slide</label>
                            <textarea name="slides[<?= $idx ?>][content]" rows="3"><?= htmlspecialchars(strip_tags($s['content'])) ?></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Catatan Desain</label>
                            <input type="text" name="slides[<?= $idx ?>][design_note]" value="<?= htmlspecialchars($s['design_note']) ?>">
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="form-group">
                <label>Caption Instagram</label>
                <textarea name="caption" rows="10"><?= htmlspecialchars($item['caption']) ?></textarea>
            </div>

            <button type="submit" class="btn-save">Simpan Perubahan Naskah</button>
        </form>
    </div>
</body>
</html>
