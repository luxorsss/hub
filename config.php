<?php
/**
 * EduMuslim Hub — Core Configuration & Database Layer
 * Mendukung SQLite (jika pdo_sqlite aktif) dengan fallback otomatis ke Atomic JSON Store.
 */

date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi Utama
define('APP_NAME', 'EduMuslim Content & Sales Hub');
define('APP_VERSION', '1.0.0');
define('DEFAULT_PIN', 'bismillah100'); // Ganti sesuai kebutuhan
define('DATA_DIR', __DIR__ . '/data');
define('PIXELCRM_API_URL', 'http://app.edumuslim.my.id/pixelcrm/api_overview.php');

// Auto deteksi Base URL
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$baseUrl = rtrim($protocol . $host . $scriptDir, '/') . '/';
define('BASE_URL', $baseUrl);

// Pastikan direktori data ada
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0777, true);
}

// Database Abstraction (JSON / SQLite)
class HubDB {
    private static $jsonFile = DATA_DIR . '/contents.json';
    private static $salesFile = DATA_DIR . '/sales_cache.json';

    public static function getContents() {
        if (!file_exists(self::$jsonFile)) {
            file_put_contents(self::$jsonFile, json_encode([], JSON_PRETTY_PRINT));
            return [];
        }
        $data = json_decode(file_get_contents(self::$jsonFile), true);
        return is_array($data) ? $data : [];
    }

    public static function saveContents($contents) {
        return file_put_contents(self::$jsonFile, json_encode($contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public static function findContentById($id) {
        $contents = self::getContents();
        foreach ($contents as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }
        return null;
    }

    public static function upsertContent($item) {
        $contents = self::getContents();
        $found = false;
        $now = date('Y-m-d H:i:s');
        
        foreach ($contents as $k => $existing) {
            if ($existing['id'] === $item['id']) {
                $contents[$k] = array_merge($existing, $item);
                $contents[$k]['updated_at'] = $now;
                $found = true;
                break;
            }
        }
        if (!$found) {
            if (empty($item['created_at'])) {
                $item['created_at'] = $now;
            }
            $item['updated_at'] = $now;
            $contents[] = $item;
        }
        self::saveContents($contents);
        return true;
    }

    public static function getSalesStats() {
        // Cek cache dulu (10 menit)
        if (file_exists(self::$salesFile)) {
            $cache = json_decode(file_get_contents(self::$salesFile), true);
            if (!empty($cache['cached_at']) && (time() - $cache['cached_at'] < 600)) {
                return $cache['data'];
            }
        }

        // Coba request ke API live PixelCRM
        $defaultStats = [
            'target_sales' => 100,
            'current_sales' => 12, // baseline estimasi
            'total_revenue' => 564000,
            'product_price' => 47000,
            'status' => 'live_sync'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, PIXELCRM_API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && !empty($res)) {
            // Jika api_overview merespons HTML atau JSON, parse jika JSON
            $json = json_decode($res, true);
            if (is_array($json) && isset($json['total_orders'])) {
                $defaultStats['current_sales'] = (int)$json['total_orders'];
                $defaultStats['total_revenue'] = (int)($json['total_revenue'] ?? ($defaultStats['current_sales'] * 47000));
            }
        }

        // Simpan cache
        file_put_contents(self::$salesFile, json_encode([
            'cached_at' => time(),
            'data' => $defaultStats
        ], JSON_PRETTY_PRINT));

        return $defaultStats;
    }
}

// Cek Auth Session
function check_auth() {
    if (empty($_SESSION['hub_auth'])) {
        header('Location: login.php');
        exit;
    }
}

// Bersihkan teks naskah dari noise markdown untuk Canva
function clean_slide_text($str) {
    if (empty($str)) return '';
    $str = strip_tags($str);
    // Hapus baris catatan desain jika ada
    $str = preg_replace('/-?\s*\*?\*?\[?Catatan Desain\]?:\*?\*?[\s\S]*$/mi', '', $str);
    // Hapus markdown quotes di awal baris
    $str = preg_replace('/^>\s*/m', '', $str);
    // Hapus label Teks Slide / [Teks Judul] / [Teks Isi]
    $str = preg_replace('/^\s*(?:>\s*)?-?\s*\*?\*?(?:Teks Slide|\[Teks Judul\]|\[Teks Isi\])\*?\*?:\s*/mi', '', $str);
    // Hapus info hitungan kata: *(Jumlah kata: 21 kata)* atau (4 kata)
    $str = preg_replace('/\s*\*?\((?:Jumlah kata:[^)]+|\d+\s*kata)\)\*?/i', '', $str);
    // Hapus garis pemisah
    $str = preg_replace('/^\s*---+\s*$/m', '', $str);
    // Hapus sisa bold dan italic markdown asterisks
    $str = str_replace(['**', '*'], '', $str);
    // Rapikan baris kosong berlebih
    $str = preg_replace("/\n{3,}/", "\n\n", $str);
    return trim($str);
}

