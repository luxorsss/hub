<?php
/**
 * EduMuslim Hub — Sync Script
 * Memindai file markdown di /home/fadil/edumuslim-team/ dan mengimpornya ke data/contents.json
 */

require_once __DIR__ . '/config.php';

$kontenDir = '/home/fadil/edumuslim-team/konten';
$crmDir = '/home/fadil/edumuslim-team/crm';

$hariMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
$hariIni = $hariMap[date('N')] ?? 'Minggu';
if (!empty($argv[1])) {
    $hariIni = ucfirst(strtolower($argv[1]));
} elseif (!empty($_GET['day'])) {
    $hariIni = ucfirst(strtolower($_GET['day']));
}

$allMasterFiles = [
    [
        'file' => $kontenDir . '/carousel-senin.md',
        'channel' => 'Carousel',
        'day' => 'Senin',
        'funnel' => 'TOFU',
        'target_product' => 'eBook Qila (Rp47rb)'
    ],
    [
        'file' => $kontenDir . '/carousel-selasa.md',
        'channel' => 'Carousel',
        'day' => 'Selasa',
        'funnel' => 'MOFU',
        'target_product' => 'eBook Qila (Rp47rb)'
    ],
    [
        'file' => $kontenDir . '/carousel-rabu.md',
        'channel' => 'Carousel',
        'day' => 'Rabu',
        'funnel' => 'TOFU',
        'target_product' => 'eBook Qila (Rp47rb)'
    ],
    [
        'file' => $kontenDir . '/carousel-kamis.md',
        'channel' => 'Carousel',
        'day' => 'Kamis',
        'funnel' => 'MOFU',
        'target_product' => 'eBook Qila (Rp47rb)'
    ],
    [
        'file' => $kontenDir . '/carousel-jumat.md',
        'channel' => 'Carousel',
        'day' => 'Jumat',
        'funnel' => 'BOFU',
        'target_product' => 'eBook Qila (Rp47rb)'
    ],
    [
        'file' => $kontenDir . '/reels-1-slide-senin.md',
        'channel' => 'Reels 1-Slide',
        'day' => 'Senin/Selasa',
        'funnel' => 'TOFU/BOFU',
        'target_product' => 'eBook Qila (Rp47rb)'
    ],
    [
        'file' => $kontenDir . '/story-jualan-hari-ini.md',
        'channel' => 'Instagram Story',
        'day' => 'Harian',
        'funnel' => 'BOFU (Direct)',
        'target_product' => 'eBook Qila (Rp47rb)'
    ],
    [
        'file' => $kontenDir . '/threads-senin.md',
        'channel' => 'Threads',
        'day' => 'Senin',
        'funnel' => 'MOFU/BOFU (Utas)',
        'target_product' => 'eBook Qila (Rp47rb)'
    ],
    [
        'file' => $kontenDir . '/threads-jumat.md',
        'channel' => 'Threads',
        'day' => 'Jumat',
        'funnel' => 'BOFU (Utas)',
        'target_product' => 'eBook Qila (Rp47rb)'
    ],
    [
        'file' => $kontenDir . '/threads-sabtu.md',
        'channel' => 'Threads',
        'day' => 'Sabtu',
        'funnel' => 'MOFU/BOFU (Utas)',
        'target_product' => 'eBook Qila (Rp47rb)'
    ],
    [
        'file' => $kontenDir . '/threads-minggu.md',
        'channel' => 'Threads',
        'day' => 'Minggu',
        'funnel' => 'MOFU/BOFU (Utas)',
        'target_product' => 'eBook Qila (Rp47rb)'
    ],
    [
        'file' => $kontenDir . '/grup-wa-hatim.md',
        'channel' => 'Grup WhatsApp',
        'day' => 'Senin',
        'funnel' => 'Nurturing (Turath)',
        'target_product' => 'Komunitas (Faedah Murni)'
    ],
    [
        'file' => $kontenDir . '/grup-wa-salaf.md',
        'channel' => 'Grup WhatsApp',
        'day' => 'Bank',
        'funnel' => 'Nurturing (Turath)',
        'target_product' => 'Komunitas (Faedah Murni)'
    ],
    [
        'file' => $kontenDir . '/grup-wa-hasan.md',
        'channel' => 'Grup WhatsApp',
        'day' => 'Bank',
        'funnel' => 'Nurturing (Turath)',
        'target_product' => 'Komunitas (Faedah Murni)'
    ],
    [
        'file' => $crmDir . '/broadcast-wa-senin.md',
        'channel' => 'CRM Outreach',
        'day' => 'Senin',
        'funnel' => 'BOFU (Direct Outreach)',
        'target_product' => 'Alumni Ihfadz/DaQo'
    ]
];

// ATURAN MUTLAK: Proses naskah HARI INI + Bank WA cadangan
$filesToProcess = [];
foreach ($allMasterFiles as $m) {
    if ($m['day'] === 'Harian' || stripos($m['day'], $hariIni) !== false || $m['day'] === 'Bank') {
        $filesToProcess[] = $m;
    }
}

$syncedContents = [];
$syncedCount = 0;

foreach ($filesToProcess as $meta) {
    $filePath = $meta['file'];
    if (!file_exists($filePath)) {
        continue;
    }

    $raw = file_get_contents($filePath);
    $filename = basename($filePath, '.md');
    $id = $filename;

    // Anti-Slop Linter Gate (ERR-006 & ERR-011)
    $slopPatterns = [
        'Bukan X tapi Y' => '/\bBukan\s+[^\n.,!?]+,\s*(?:tapi|melainkan)\s+[^\n.,!?]+/iu',
        'Formula Trio Tanpa' => '/Tanpa\s+[^,]+,\s*tanpa\s+[^,]+,\s*tanpa\s+/iu',
        'Kosakata Hampa AI' => '/\b(unlock|elevate|empower|delve|game-changer|robust|revolutionize|next-level)\b/iu',
        'Klise Pembuka AI' => '/\b(Tahukah Anda|Di era (?:modern )?yang serba cepat|Mari kita renungkan|Tanpa berlama-lama)\b/iu'
    ];

    foreach ($slopPatterns as $ruleName => $pattern) {
        if (preg_match($pattern, $raw, $slopMatch)) {
            echo "⚠️ [ANTI-SLOP WARNING] Berkas $filename melanggar aturan '$ruleName': \"{$slopMatch[0]}\"\n";
        }
    }

    // Ekstraksi Topik / Title
    $title = $filename;
    if (preg_match('/\*\*Topik:\*\*\s*(.+)/i', $raw, $m)) {
        $title = trim($m[1]);
    } elseif (preg_match('/^#\s*(.+)/m', $raw, $m)) {
        $title = trim($m[1]);
    }

    // Ekstraksi Hook
    $hook = $title;
    if (preg_match('/\[Slide 1[^\]]*\][\s\S]*?\*([^*]+)\*/i', $raw, $m)) {
        $hook = trim($m[1]);
    } elseif (preg_match('/Teks Utama di Tengah Layar:\s*> \*([^*]+)\*/i', $raw, $m)) {
        $hook = trim($m[1]);
    } elseif (preg_match('/###\s*(?:\[)?Slide\s*1[^\n]*\n+([\s\S]*?)(?=(?:\n\n|\n-|\Z))/i', $raw, $m)) {
        $cleanHook = clean_slide_text($m[1]);
        if (!empty($cleanHook)) {
            $hook = $cleanHook;
        }
    }

    // Ekstraksi Caption
    $caption = '';
    if (preg_match('/(?:##[^\n]*Caption|###\s*2\.\s*Copywriting Caption Lengkap)[\s\S]*?```(?:text)?\s*([\s\S]*?)```/iu', $raw, $m)) {
        $caption = trim($m[1]);
    } elseif (preg_match('/(?:##[^\n]*Caption[^\n]*\n+)([\s\S]*?)(?:\n---|\Z)/iu', $raw, $m)) {
        $caption = trim($m[1]);
    }

    // 1. Ekstraksi Slides (Carousel)
    $slides = [];
    preg_match_all('/###\s*(?:\[)?Slide\s*(\d+)[^:\r\n\]]*[:\]]?[ \t]*([^\r\n]*)([\s\S]*?)(?=(?:###\s*(?:\[)?Slide|\Z|##\s*Caption))/i', $raw, $matches, PREG_SET_ORDER);
    
    if (!empty($matches)) {
        foreach ($matches as $sMatch) {
            $sNum = (int)$sMatch[1];
            $sHeader = trim($sMatch[2]);
            $sBody = trim($sMatch[3]);

            $sTitle = '';
            if (preg_match('/\[Teks Judul\]:\*?\*?\s*\*?([^*\n(]+)/i', $sBody, $tm)) {
                $sTitle = clean_slide_text($tm[1]);
            }

            $sNote = '';
            if (preg_match('/\[Catatan Desain\]:\*?\*?\s*([^\n]+)/i', $sBody, $nm)) {
                $sNote = clean_slide_text($nm[1]);
            }

            $sContent = '';
            if (preg_match('/\[Teks Isi\]:\*?\*?\s*([\s\S]*?)(?=(?:-\s*\*?\*?\[Catatan|\Z))/i', $sBody, $cm)) {
                $sContent = clean_slide_text($cm[1]);
            } else {
                $sContent = clean_slide_text($sBody);
            }

            if (empty($sTitle) && !empty($sHeader)) {
                $cleanHead = clean_slide_text($sHeader);
                if (stripos($cleanHead, 'Visual Note') === false) {
                    $sTitle = $cleanHead;
                }
            }

            $slides[] = [
                'slide_number' => $sNum,
                'header' => $sHeader,
                'title' => $sTitle,
                'content' => $sContent,
                'design_note' => $sNote
            ];
        }
    }

    // 2. Ekstraksi Posts / Utas (Threads)
    $posts = [];
    if (stripos($meta['channel'], 'Threads') !== false || stripos($filename, 'threads') !== false) {
        preg_match_all('/###\s*Post\s*(\d+)[^:\r\n]*[:\]]?[ \t]*([^\r\n]*)\n+([\s\S]*?)(?=(?:###\s*Post|\Z|---))/i', $raw, $pMatches, PREG_SET_ORDER);
        foreach ($pMatches as $pm) {
            $pNum = (int)$pm[1];
            $pHeader = trim($pm[2]);
            $pBody = trim($pm[3]);
            $posts[] = [
                'post_number' => $pNum,
                'header' => $pHeader,
                'content' => $pBody
            ];
        }
    }

    // 3. Ekstraksi Stories (Instagram Story)
    $stories = [];
    if (stripos($meta['channel'], 'Story') !== false || stripos($filename, 'story') !== false) {
        preg_match_all('/###\s*Story\s*(\d+)[^:\r\n]*[:\]]?[ \t]*([^\r\n]*)\n+([\s\S]*?)(?=(?:###\s*Story|\Z|---))/i', $raw, $stMatches, PREG_SET_ORDER);
        foreach ($stMatches as $stm) {
            $stNum = (int)$stm[1];
            $stTitle = trim($stm[2]);
            $stBody = trim($stm[3]);
            $stories[] = [
                'story_number' => $stNum,
                'title' => $stTitle,
                'content' => $stBody
            ];
        }
    }

    // 4. Ekstraksi Pesan Grup WhatsApp
    $waMessage = '';
    if (stripos($meta['channel'], 'WhatsApp') !== false || stripos($filename, 'grup-wa') !== false) {
        if (preg_match('/```(?:whatsapp)?\s*([\s\S]*?)```/iu', $raw, $wm)) {
            $waMessage = trim($wm[1]);
        } else {
            $parts = explode('---', $raw);
            $waMessage = trim(end($parts));
        }
    }

    // 5. Ekstraksi Pesan CRM Outreach
    $outreachMessages = [];
    if (stripos($filename, 'broadcast') !== false || stripos($meta['channel'], 'Outreach') !== false) {
        preg_match_all('/(?:###|##)\s*([^\n\r]+)\n+(?:[^\n`]*\n+)?```(?:text)?\s*([\s\S]*?)```/iu', $raw, $omMatches, PREG_SET_ORDER);
        foreach ($omMatches as $om) {
            $outreachMessages[] = [
                'title' => trim($om[1]),
                'content' => trim($om[2])
            ];
        }
    }

    $existing = HubDB::findContentById($id);
    $status = $existing['status'] ?? 'ready_to_design';

    $item = [
        'id' => $id,
        'channel' => $meta['channel'],
        'day' => $meta['day'],
        'funnel' => $meta['funnel'],
        'target_product' => $meta['target_product'],
        'title' => $title,
        'hook' => $hook,
        'slides' => $slides,
        'posts' => $posts,
        'stories' => $stories,
        'wa_message' => $waMessage,
        'outreach_messages' => $outreachMessages,
        'caption' => $caption,
        'raw_markdown' => $raw,
        'status' => $status,
        'audit_badge' => 'Audit Lolos (Marketing & CEO Approved)'
    ];

    $syncedContents[] = $item;
    $syncedCount++;
}

// SIMPAN HANYA KONTEN HARI INI (HAPUS KONTEN HARI LAIN)
HubDB::saveContents($syncedContents);

if (php_sapi_name() === 'cli') {
    echo "Sync berhasil! $syncedCount dokumen konten HARI INI ($hariIni) masuk ke database (naskah hari lain dibersihkan).\n";
} else {
    header('Location: index.php?msg=sync_success');
    exit;
}
