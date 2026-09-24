<?php
declare(strict_types=1);
session_start();

const DATA_DIR = __DIR__ . '/data';
const PRODUCTS_FILE = DATA_DIR . '/products.json';
const USERS_FILE = DATA_DIR . '/users.json';
const SETTINGS_FILE = DATA_DIR . '/settings.json';

if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);

function read_json(string $file, array $fallback=[]): array {
    if (!file_exists($file)) return $fallback;
    $raw = file_get_contents($file);
    $data = json_decode($raw ?: '', true);
    return is_array($data) ? $data : $fallback;
}
function write_json(string $file, array $data): bool {
    $tmp = $file . '.tmp';
    $ok = file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), LOCK_EX);
    return $ok !== false && rename($tmp, $file);
}
function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function money($n): string { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}
function check_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419); exit('CSRF token tidak valid.');
    }
}
function user(): ?array { return $_SESSION['user'] ?? null; }
function logged(): bool { return user() !== null; }
function require_login(): void { if (!logged()) { header('Location: login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? 'dashboard.php')); exit; } }
function require_role(array $roles): void {
    require_login();
    if (!in_array(user()['role'] ?? '', $roles, true)) { http_response_code(403); exit('Akses ditolak.'); }
}
function settings(): array {
    $defaults = [
        'store_name'=>'GEOTAMA COMPUTER',
        'address'=>'Ruko Elang Kav-A No.5, Jl. Elang Raya Cirebon',
        'address2'=>'',
        'phone'=>'628563293445',
        'email'=>'admin@geotama.local',
        'telegram_bot_token'=>'',
        'telegram_chat_id'=>'',
    ];
    return array_merge($defaults, read_json(SETTINGS_FILE, $defaults));
}

/* =====================================================
   TELEGRAM BOT INTEGRATION
===================================================== */

/**
 * Kirim pesan ke Telegram lewat Bot API.
 * Butuh telegram_bot_token & telegram_chat_id di settings.json
 * (diisi lewat halaman Setting > Integrasi Telegram Bot).
 *
 * Alasan gagal (kalau ada) bisa dibaca lewat telegram_last_error().
 */
function telegram_send(string $text): bool {
    $s = settings();
    $token  = trim((string)($s['telegram_bot_token'] ?? ''));
    $chatId = trim((string)($s['telegram_chat_id'] ?? ''));

    if ($token === '' || $chatId === '') {
        $GLOBALS['__telegram_last_error'] = 'Bot Token / Chat ID belum diisi.';
        return false;
    }

    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $payload = [
        'chat_id'    => $chatId,
        'text'       => $text,
        'parse_mode' => 'Markdown',
    ];

    $body     = false;
    $httpCode = 0;
    $curlErr  = '';

    try {

        if (function_exists('curl_init')) {

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $body = curl_exec($ch);
            $curlErr = $body === false ? curl_error($ch) : '';
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            /*
             * Fallback: sebagian hosting punya CA bundle usang
             * sehingga verifikasi SSL gagal padahal token/chat id
             * sudah benar. Coba sekali lagi tanpa verifikasi SSL
             * supaya notifikasi tetap bisa terkirim.
             */
            if ($body === false && stripos($curlErr, 'ssl') !== false) {

                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => http_build_query($payload),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 10,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                ]);

                $body = curl_exec($ch);
                $curlErr = $body === false ? curl_error($ch) : '';
                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
            }

        } else {

            $ctx = stream_context_create([
                'http' => [
                    'method'        => 'POST',
                    'header'        => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'content'       => http_build_query($payload),
                    'timeout'       => 10,
                    'ignore_errors' => true,
                ],
            ]);

            $body = @file_get_contents($url, false, $ctx);

            if (isset($http_response_header[0]) && preg_match('/(\d{3})/', $http_response_header[0], $m)) {
                $httpCode = (int) $m[1];
            }

            if ($body === false) {
                $curlErr = 'Koneksi ke Telegram gagal (allow_url_fopen mati / firewall hosting?).';
            }
        }

    } catch (\Throwable $e) {
        $curlErr = $e->getMessage();
    }

    if ($body === false) {
        $GLOBALS['__telegram_last_error'] = $curlErr !== ''
            ? "Gagal konek ke server Telegram: {$curlErr}"
            : 'Gagal konek ke server Telegram.';
        return false;
    }

    $decoded = json_decode((string) $body, true);

    if (!is_array($decoded) || empty($decoded['ok'])) {

        $reason = $decoded['description'] ?? ('HTTP ' . $httpCode);

        if ($httpCode === 401) {
            $reason = 'Bot Token salah / tidak valid (Unauthorized). Cek ulang token dari @BotFather.';
        } elseif (stripos((string) $reason, 'chat not found') !== false) {
            $reason = 'Chat ID salah, ATAU kamu belum pernah kirim pesan / klik Start ke bot ini. Buka Telegram, cari bot-nya, tekan Start dulu.';
        } elseif (stripos((string) $reason, "bot can't initiate") !== false || stripos((string) $reason, 'bot was blocked') !== false) {
            $reason = 'Bot diblokir, atau kamu belum pernah chat/klik Start ke bot ini terlebih dahulu.';
        }

        $GLOBALS['__telegram_last_error'] = $reason;
        return false;
    }

    $GLOBALS['__telegram_last_error'] = '';
    return true;
}

/**
 * Alasan gagal terakhir dari telegram_send(), untuk ditampilkan
 * di UI supaya gampang di-debug (bukan cuma "gagal kirim").
 */
function telegram_last_error(): string {
    return $GLOBALS['__telegram_last_error'] ?? '';
}

function telegram_configured(): bool {
    $s = settings();
    return trim((string)($s['telegram_bot_token'] ?? '')) !== ''
        && trim((string)($s['telegram_chat_id'] ?? '')) !== '';
}

/**
 * Kirim notifikasi error server ke Telegram.
 */
function telegram_notify_error(string $type, string $message, string $file = '', int $line = 0): void {
    $s = settings();
    $storeName = $s['store_name'] ?? 'Geotama Computer';

    $text = "🚨 *SERVER ERROR - " . $storeName . "*\n\n"
        . "Jenis: {$type}\n"
        . "Pesan: {$message}\n"
        . ($file !== '' ? "File: " . basename($file) . "\n" : '')
        . ($line > 0 ? "Baris: {$line}\n" : '')
        . "Halaman: " . ($_SERVER['REQUEST_URI'] ?? '-') . "\n"
        . "Waktu: " . date('d-m-Y H:i:s');

    telegram_send($text);
}

/*
 * Pantau error fatal & exception yang tidak tertangani,
 * lalu kirim notifikasi ke Telegram secara otomatis.
 */
set_exception_handler(function (\Throwable $e): void {
    telegram_notify_error('Uncaught Exception', $e->getMessage(), $e->getFile(), $e->getLine());

    if (!headers_sent()) {
        http_response_code(500);
    }

    echo 'Terjadi kesalahan pada server. Tim kami sudah diberi tahu otomatis.';
});

register_shutdown_function(function (): void {
    $err = error_get_last();

    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        telegram_notify_error('Fatal Error', $err['message'], $err['file'], $err['line']);
    }
});
function badge_role(string $role): string {
    $map=['guest'=>'Guest/Pembeli','admin'=>'Admin','verified_admin'=>'Verified Admin','super_admin'=>'Super Admin'];
    return $map[$role] ?? $role;
}
function wa_link(array $p): string {
    $s=settings(); $phone=preg_replace('/\D/','',$s['phone'] ?? '');
    $msg='Halo '.($s['store_name']??'Geotama Computer').', saya tertarik dengan '.$p['name'].' ('.money($p['price']).'). Apakah masih tersedia?';
    return 'https://wa.me/'.$phone.'?text='.rawurlencode($msg);
}
$S = settings();
