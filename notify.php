<?php
declare(strict_types=1);
require __DIR__ . '/common.php';

/*
=========================================================
 GEOTAMA COMPUTER
 NOTIFY.PHP

 Endpoint ringan yang dipanggil dari index.php (JS)
 setiap ada aktivitas "Tanya / Pesan" produk.
 Mengirim notifikasi ke Telegram admin.
=========================================================
*/

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

$type  = trim((string)($_POST['type'] ?? 'produk'));
$name  = trim((string)($_POST['name'] ?? 'Produk'));
$price = trim((string)($_POST['price'] ?? ''));
$sku   = trim((string)($_POST['sku'] ?? ''));

// Batasi panjang input supaya tidak disalahgunakan.
$name  = mb_substr($name, 0, 150);
$price = mb_substr($price, 0, 50);
$sku   = mb_substr($sku, 0, 50);

$text = "🛒 *Ada aktivitas pembelian!*\n\n"
    . "Produk: {$name}\n"
    . ($price !== '' ? "Harga: {$price}\n" : '')
    . ($sku !== '' ? "SKU: {$sku}\n" : '')
    . "Aksi: " . ($type === 'wa' ? 'Klik tombol Tanya/Pesan WhatsApp' : $type) . "\n"
    . "Waktu: " . date('d-m-Y H:i:s');

$sent = telegram_send($text);

echo json_encode(['ok' => $sent]);
