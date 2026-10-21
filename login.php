<?php
require_once __DIR__ . '/config.php';

$error = '';
$manualLogout = !empty($_GET['manual_logout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim($_POST['pin'] ?? '');
    if ($pin === DEFAULT_PIN) {
        $_SESSION['hub_auth'] = true;
        // Simpan cookie tahan 90 hari juga
        setcookie('hub_auth_remember', '1', time() + (86400 * 90), '/', '', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'), true);
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            --amber: #f59e0b;
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
            max-width: 420px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            text-align: center;
        }
        .badge {
            display: inline-block;
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
            font-size: 0.75rem;
            font-weight: 700;
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
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        .form-group {
            margin-bottom: 1.25rem;
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
        input[type="password"], input[type="text"] {
            width: 100%;
            padding: 0.9rem 1rem;
            background: #0d0e15;
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-size: 1.1rem;
            text-align: center;
            letter-spacing: 0.15em;
            outline: none;
            transition: border-color 0.2s;
        }
        input:focus {
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }
        .btn-quick {
            background: #1e293b;
            color: #38bdf8;
            border: 1px solid #334155;
            margin-bottom: 1rem;
            display: none;
        }
        .btn-quick:hover {
            background: #334155;
            color: #7dd3fc;
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
        .remember-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1rem;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .remember-box label {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin: 0;
            cursor: pointer;
            text-transform: none;
            letter-spacing: normal;
        }
        .btn-clear-saved {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 0.75rem;
            text-decoration: underline;
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

        <!-- Tombol Quick Login jika ada PIN tersimpan di browser -->
        <button type="button" id="btnQuickLogin" class="btn btn-quick" onclick="submitSavedPin()">
            ⚡ Masuk Langsung (PIN Tersimpan)
        </button>

        <form method="POST" id="loginForm" onsubmit="handleFormSubmit()">
            <div class="form-group">
                <label for="pin">PIN Keamanan</label>
                <input type="password" id="pin" name="pin" placeholder="••••••••" autofocus required autocomplete="current-password">
            </div>

            <button type="submit" class="btn">Buka Dashboard</button>

            <div class="remember-box">
                <label>
                    <input type="checkbox" id="rememberPin" checked> Ingat PIN di perangkat ini
                </label>
                <button type="button" id="btnClearPin" class="btn-clear-saved" style="display:none;" onclick="clearSavedPin()">Hapus PIN</button>
            </div>
        </form>
    </div>

    <script>
        const STORAGE_KEY = 'hub_saved_pin_v1';
        const isManualLogout = <?= $manualLogout ? 'true' : 'false' ?>;

        document.addEventListener('DOMContentLoaded', () => {
            const savedPin = localStorage.getItem(STORAGE_KEY);
            const pinInput = document.getElementById('pin');
            const quickBtn = document.getElementById('btnQuickLogin');
            const clearBtn = document.getElementById('btnClearPin');

            if (savedPin) {
                pinInput.value = savedPin;
                quickBtn.style.display = 'flex';
                clearBtn.style.display = 'inline-block';

                // Jika bukan karena klik tombol Logout manual, auto-login langsung
                if (!isManualLogout && !<?= !empty($error) ? 'true' : 'false' ?>) {
                    quickBtn.innerHTML = '⚡ Sedang masuk otomatis...';
                    setTimeout(() => {
                        document.getElementById('loginForm').submit();
                    }, 400);
                }
            }
        });

        function handleFormSubmit() {
            const pinVal = document.getElementById('pin').value.trim();
            const shouldRemember = document.getElementById('rememberPin').checked;
            if (shouldRemember && pinVal) {
                localStorage.setItem(STORAGE_KEY, pinVal);
            } else if (!shouldRemember) {
                localStorage.removeItem(STORAGE_KEY);
            }
        }

        function submitSavedPin() {
            const savedPin = localStorage.getItem(STORAGE_KEY);
            if (savedPin) {
                document.getElementById('pin').value = savedPin;
                document.getElementById('loginForm').submit();
            }
        }

        function clearSavedPin() {
            localStorage.removeItem(STORAGE_KEY);
            document.getElementById('pin').value = '';
            document.getElementById('btnQuickLogin').style.display = 'none';
            document.getElementById('btnClearPin').style.display = 'none';
            alert('PIN tersimpan di perangkat ini berhasil dihapus.');
        }
    </script>
</body>
</html>
