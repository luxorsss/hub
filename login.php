<?php
require_once __DIR__ . '/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim($_POST['pin'] ?? '');
    if ($pin === DEFAULT_PIN) {
        $_SESSION['hub_auth'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'PIN salah. Silakan coba lagi.';
    }
}

if (!empty($_SESSION['hub_auth'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — EduMuslim Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #090a0f;
            --card: #13151f;
            --border: #232738;
            --text: #f0f2f8;
            --text-muted: #8b92a8;
            --primary: #10b981;
            --primary-hover: #059669;
            --danger: #ef4444;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            text-align: center;
        }
        .badge {
            display: inline-block;
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 999px;
            margin-bottom: 1rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }
        p {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-bottom: 2rem;
            line-height: 1.5;
        }
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        input[type="password"] {
            width: 100%;
            padding: 0.9rem 1rem;
            background: #0d0e15;
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-size: 1.1rem;
            text-align: center;
            letter-spacing: 0.2em;
            outline: none;
            transition: border-color 0.2s;
        }
        input[type="password"]:focus {
            border-color: var(--primary);
        }
        .btn {
            width: 100%;
            padding: 0.9rem;
            background: var(--primary);
            color: #042f2e;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }
        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <span class="badge">EduMuslim Executive</span>
        <h1>Content & Sales Hub</h1>
        <p>Akses cepat naskah konten Canva & metrik target 100 penjualan eBook Qila.</p>

        <?php if (!empty($error)): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="pin">Masukkan PIN Keamanan</label>
                <input type="password" id="pin" name="pin" placeholder="••••••••" autofocus required>
            </div>
            <button type="submit" class="btn">Buka Dashboard</button>
        </form>
    </div>
</body>
</html>
