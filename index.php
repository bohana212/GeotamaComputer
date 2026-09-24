<?php
declare(strict_types=1);
require __DIR__ . '/common.php';

/* =====================================================
   HELPER
===================================================== */

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function settingValue(array $settings, string $key, string $default = ''): string
{
    return isset($settings[$key]) && $settings[$key] !== ''
        ? (string)$settings[$key]
        : $default;
}

function rupiah($value): string
{
    return 'Rp ' . number_format((float)$value, 0, ',', '.');
}

function waNumber(string $number): string
{
    $number = preg_replace('/\D+/', '', $number);

    if ($number === '') {
        return '';
    }

    if (str_starts_with($number, '0')) {
        $number = '62' . substr($number, 1);
    }

    return $number;
}

function getStockNumber(array $product): int
{
    return (int)($product['stock'] ?? 0);
}

function getProductStatus(array $product): array
{
    $stock = getStockNumber($product);

    if ($stock <= 0) {
        return [
            'label' => 'Stok Habis',
            'class' => 'out',
            'icon'  => 'fa-circle-xmark'
        ];
    }

    if ($stock <= 3) {
        return [
            'label' => 'Stok Terbatas',
            'class' => 'low',
            'icon'  => 'fa-triangle-exclamation'
        ];
    }

    return [
        'label' => 'Ready Stock',
        'class' => 'ready',
        'icon'  => 'fa-circle-check'
    ];
}

function isFlashSale(array $product): bool
{
    return !empty($product['flash_sale'])
        || !empty($product['flash'])
        || !empty($product['is_flash_sale']);
}

function productSearchText(array $product): string
{
    $parts = [
        $product['name'] ?? '',
        $product['category'] ?? '',
        $product['description'] ?? '',
        $product['brand'] ?? '',
        $product['sku'] ?? '',
        $product['code'] ?? ''
    ];

    return strtolower(implode(' ', $parts));
}

/* =====================================================
   LOAD DATA
===================================================== */

$products = read_json(PRODUCTS_FILE, []);
$store    = settings();

if (!is_array($products)) {
    $products = [];
}

/*
 * Support jika products.json berbentuk:
 *
 * [
 *   {...},
 *   {...}
 * ]
 *
 * maupun:
 *
 * {
 *   "products": [...]
 * }
 */
if (
    isset($products['products']) &&
    is_array($products['products'])
) {
    $products = $products['products'];
}

/* =====================================================
   STORE INFO
===================================================== */

$storeName = settingValue(
    $store,
    'store_name',
    'GEOTAMA COMPUTER'
);

$storeBio = settingValue(
    $store,
    'bio',
    'Solusi komputer, laptop, networking dan kebutuhan IT.'
);

$address = settingValue(
    $store,
    'address',
    'Ruko Elang Kav-A No.5, Jl. Elang Raya, Cirebon'
);

$address2 = settingValue(
    $store,
    'address2',
    ''
);

$phone = settingValue(
    $store,
    'phone',
    '628563293445'
);

$whatsapp = settingValue(
    $store,
    'whatsapp',
    $phone
);

$email = settingValue(
    $store,
    'email',
    'admin@geotama.local'
);

$profileImage = settingValue(
    $store,
    'profile_image',
    ''
);

$instagram = settingValue(
    $store,
    'instagram',
    ''
);

$facebook = settingValue(
    $store,
    'facebook',
    ''
);

$tiktok = settingValue(
    $store,
    'tiktok',
    ''
);

$wa = waNumber($whatsapp);

/* =====================================================
   CATEGORY
===================================================== */

$categories = [
    'Laptop',
    'PC Fullset',
    'CPU Only',
    'Monitor',
    'Aksesoris PC/Laptop',
    'Printer',
    'Tinta',
    'Networking',
    'CCTV'
];

/* =====================================================
   STATISTIC
===================================================== */

$totalProducts = count($products);
$totalReady    = 0;
$totalFlash    = 0;
$totalRestock  = 0;

foreach ($products as $product) {

    if (!is_array($product)) {
        continue;
    }

    $stock = getStockNumber($product);

    if ($stock > 0) {
        $totalReady++;
    }

    if (isFlashSale($product)) {
        $totalFlash++;
    }

    if ($stock > 0 && $stock <= 3) {
        $totalRestock++;
    }
}

/* =====================================================
   ADMIN BUTTON
===================================================== */

/*
 * PENTING:
 *
 * Jangan arahkan berdasarkan session dari index.php.
 *
 * Admin selalu masuk ke login.php.
 *
 * login.php sendiri sudah punya:
 *
 * if (logged()) {
 *     header('Location: dashboard.php');
 *     exit;
 * }
 *
 * Jadi:
 *
 * Belum login
 * index -> login.php -> tampil login
 *
 * Sudah login
 * index -> login.php -> dashboard.php
 *
 * Setelah logout
 * index -> login.php
 *
 * Ini mencegah katalog membaca session secara keliru.
 */
$adminUrl = 'login.php';

/* =====================================================
   WHATSAPP
===================================================== */

$waBase = $wa !== ''
    ? 'https://wa.me/' . $wa
    : '#';

$generalWaMessage = rawurlencode(
    'Halo ' . $storeName . ', saya ingin bertanya mengenai produk komputer.'
);

$generalWa = $wa !== ''
    ? $waBase . '?text=' . $generalWaMessage
    : '#';

/* =====================================================
   PRODUCT WHATSAPP
===================================================== */

function productWhatsApp(array $product, string $waBase): string
{
    if ($waBase === '#') {
        return '#';
    }

    $name  = $product['name'] ?? 'produk';
    $price = rupiah($product['price'] ?? 0);

    $message =
        "Halo GEOTAMA COMPUTER, saya tertarik dengan produk:\n\n" .
        "Nama: {$name}\n" .
        "Harga: {$price}\n\n" .
        "Apakah produk ini masih tersedia?";

    return $waBase . '?text=' . rawurlencode($message);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<meta
    name="theme-color"
    content="#0b1020"
>

<meta
    name="description"
    content="<?= e($storeName) ?> — Computer Store, Laptop, PC, Printer, Networking dan kebutuhan IT."
>

<title>
    <?= e($storeName) ?> — Computer Store
</title>

<!-- ==================================================
     FONT
================================================== -->

<link rel="preconnect" href="https://fonts.googleapis.com">

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin
>

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap"
    rel="stylesheet"
>

<!-- ==================================================
     FONT AWESOME
================================================== -->

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>

<style>

/* =====================================================
   ROOT
===================================================== */

:root {
    --bg: #050812;
    --bg2: #080d1b;
    --panel: rgba(15, 23, 42, .78);
    --panel2: rgba(20, 30, 55, .78);

    --border: rgba(255,255,255,.08);

    --text: #f7f9ff;
    --muted: #94a3b8;

    --blue: #3b82f6;
    --blue2: #2563eb;
    --cyan: #22d3ee;

    --green: #22c55e;
    --yellow: #facc15;
    --red: #ef4444;

    --radius: 20px;
    --shadow: 0 20px 60px rgba(0,0,0,.35);
}

/* =====================================================
   RESET
===================================================== */

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

html {
    scroll-behavior: smooth;
}

body {
    min-height: 100vh;
    background:
        radial-gradient(
            circle at 10% 0%,
            rgba(37,99,235,.16),
            transparent 30%
        ),
        radial-gradient(
            circle at 90% 10%,
            rgba(34,211,238,.10),
            transparent 25%
        ),
        linear-gradient(
            180deg,
            #050812 0%,
            #080d18 45%,
            #050812 100%
        );

    color: var(--text);

    font-family:
        Inter,
        Manrope,
        Arial,
        sans-serif;

    line-height: 1.5;
}

a {
    color: inherit;
    text-decoration: none;
}

button,
input {
    font: inherit;
}

button {
    cursor: pointer;
}

/* =====================================================
   CONTAINER
===================================================== */

.container {
    width: min(1180px, calc(100% - 32px));
    margin: auto;
}

/* =====================================================
   NAVBAR
===================================================== */

.navbar {
    position: sticky;
    top: 0;
    z-index: 1000;

    background:
        rgba(5,8,18,.82);

    backdrop-filter:
        blur(18px);

    -webkit-backdrop-filter:
        blur(18px);

    border-bottom:
        1px solid var(--border);
}

.nav-inner {
    min-height: 72px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 20px;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;

    min-width: 0;
}

.brand-logo {
    width: 42px;
    height: 42px;

    display: grid;
    place-items: center;

    border-radius: 13px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #06b6d4
        );

    box-shadow:
        0 10px 30px rgba(37,99,235,.3);

    font-size: 18px;
}

.brand-text {
    min-width: 0;
}

.brand-name {
    font-family:
        "Space Grotesk",
        sans-serif;

    font-weight: 800;

    font-size: 16px;

    letter-spacing: -.3px;

    white-space: nowrap;
}

.brand-sub {
    color: var(--muted);

    font-size: 11px;

    white-space: nowrap;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 8px;
}

.nav-link {
    color: #cbd5e1;

    padding:
        10px 13px;

    border-radius: 12px;

    font-size: 13px;
    font-weight: 600;

    transition:
        .2s ease;
}

.nav-link:hover {
    background:
        rgba(255,255,255,.06);

    color: white;
}

.nav-admin {
    display: inline-flex;
    align-items: center;
    gap: 8px;

    padding:
        10px 15px;

    border-radius: 12px;

    background:
        linear-gradient(
            135deg,
            var(--blue),
            #06b6d4
        );

    color: white;

    font-size: 13px;
    font-weight: 700;

    box-shadow:
        0 8px 25px rgba(37,99,235,.25);

    transition:
        transform .2s ease,
        box-shadow .2s ease;
}

.nav-admin:hover {
    transform:
        translateY(-2px);

    box-shadow:
        0 12px 32px rgba(37,99,235,.35);
}

/* =====================================================
   HERO
===================================================== */

.hero {
    padding:
        80px 0 55px;
}

.hero-grid {
    display: grid;

    grid-template-columns:
        minmax(0, 1.4fr)
        minmax(280px, .6fr);

    gap: 30px;

    align-items: center;
}

.hero-badge {
    display: inline-flex;

    align-items: center;
    gap: 8px;

    padding:
        8px 12px;

    border:
        1px solid rgba(59,130,246,.25);

    background:
        rgba(37,99,235,.10);

    color:
        #93c5fd;

    border-radius: 999px;

    font-size: 12px;
    font-weight: 700;

    margin-bottom: 18px;
}

.hero h1 {
    font-family:
        "Space Grotesk",
        sans-serif;

    font-size:
        clamp(38px, 6vw, 68px);

    line-height: .98;

    letter-spacing:
        -3px;

    max-width:
        760px;
}

.hero h1 span {
    background:
        linear-gradient(
            90deg,
            #60a5fa,
            #22d3ee
        );

    -webkit-background-clip:
        text;

    background-clip:
        text;

    color:
        transparent;
}

.hero p {
    max-width:
        680px;

    margin-top:
        22px;

    color:
        var(--muted);

    font-size:
        15px;

    line-height:
        1.8;
}

.hero-actions {
    display: flex;

    flex-wrap: wrap;

    gap: 12px;

    margin-top:
        28px;
}

.btn {
    display: inline-flex;

    align-items: center;
    justify-content: center;

    gap: 9px;

    min-height:
        46px;

    padding:
        0 18px;

    border-radius:
        13px;

    font-size:
        13px;

    font-weight:
        700;

    border:
        1px solid transparent;

    transition:
        .2s ease;
}

.btn-primary {
    background:
        linear-gradient(
            135deg,
            var(--blue),
            #06b6d4
        );

    color: white;

    box-shadow:
        0 12px 30px rgba(37,99,235,.25);
}

.btn-primary:hover {
    transform:
        translateY(-2px);
}

.btn-outline {
    background:
        rgba(255,255,255,.04);

    border-color:
        var(--border);

    color:
        #e2e8f0;
}

.btn-outline:hover {
    background:
        rgba(255,255,255,.08);
}

/* =====================================================
   HERO CARD
===================================================== */

.hero-card {
    position: relative;

    padding:
        25px;

    border:
        1px solid var(--border);

    border-radius:
        24px;

    background:
        linear-gradient(
            145deg,
            rgba(20,30,55,.9),
            rgba(8,13,27,.9)
        );

    box-shadow:
        var(--shadow);

    overflow:
        hidden;
}

.hero-card::before {
    content: "";

    position: absolute;

    width: 180px;
    height: 180px;

    right: -70px;
    top: -70px;

    border-radius:
        50%;

    background:
        rgba(34,211,238,.12);

    filter:
        blur(10px);
}

.hero-card-icon {
    width: 58px;
    height: 58px;

    display: grid;
    place-items: center;

    border-radius:
        17px;

    background:
        rgba(59,130,246,.12);

    color:
        #60a5fa;

    font-size:
        23px;

    margin-bottom:
        18px;
}

.hero-card h3 {
    font-family:
        "Space Grotesk",
        sans-serif;

    font-size:
        20px;

    margin-bottom:
        7px;
}

.hero-card p {
    font-size:
        13px;

    margin: 0;

    line-height:
        1.7;
}

.hero-card-info {
    margin-top:
        20px;

    padding-top:
        18px;

    border-top:
        1px solid var(--border);
}

.info-row {
    display: flex;

    align-items: flex-start;

    gap: 10px;

    color:
        #cbd5e1;

    font-size:
        12px;

    margin-bottom:
        11px;
}

.info-row i {
    width: 18px;

    color:
        #60a5fa;

    margin-top:
        3px;
}

.info-row a {
    color: #cbd5e1;
    transition: color .2s ease;
}

.info-row a:hover {
    color: #93c5fd;
    text-decoration: underline;
}

/* =====================================================
   STATS
===================================================== */

.stats {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 12px;

    margin:
        10px 0 45px;
}

.stat {
    padding:
        20px;

    border:
        1px solid var(--border);

    background:
        rgba(255,255,255,.025);

    border-radius:
        17px;
}

.stat-icon {
    color:
        #60a5fa;

    margin-bottom:
        11px;
}

.stat-number {
    font-family:
        "Space Grotesk",
        sans-serif;

    font-size:
        25px;

    font-weight:
        800;
}

.stat-label {
    color:
        var(--muted);

    font-size:
        12px;

    margin-top:
        3px;
}

/* =====================================================
   SECTION
===================================================== */

.section {
    padding:
        25px 0 70px;
}

.section-head {
    display: flex;

    align-items: flex-end;
    justify-content: space-between;

    gap: 20px;

    margin-bottom:
        24px;
}

.section-title {
    font-family:
        "Space Grotesk",
        sans-serif;

    font-size:
        28px;

    letter-spacing:
        -1px;
}

.section-desc {
    color:
        var(--muted);

    font-size:
        13px;

    margin-top:
        6px;
}

/* =====================================================
   SEARCH
===================================================== */

.catalog-toolbar {
    display: flex;

    align-items: center;

    gap: 12px;

    flex-wrap: wrap;

    margin-bottom:
        20px;
}

.search-box {
    position: relative;

    flex:
        1 1 300px;
}

.search-box i {
    position: absolute;

    left: 15px;
    top: 50%;

    transform:
        translateY(-50%);

    color:
        #64748b;
}

.search-box input {
    width: 100%;

    height: 46px;

    padding:
        0 16px 0 43px;

    border-radius:
        13px;

    border:
        1px solid var(--border);

    outline: none;

    background:
        rgba(255,255,255,.035);

    color:
        white;

    font-size:
        13px;
}

.search-box input:focus {
    border-color:
        rgba(59,130,246,.6);

    box-shadow:
        0 0 0 3px rgba(59,130,246,.1);
}

/* =====================================================
   CATEGORY
===================================================== */

.category-list {
    display: flex;

    gap: 8px;

    overflow-x: auto;

    padding-bottom:
        5px;

    scrollbar-width:
        none;
}

.category-list::-webkit-scrollbar {
    display:
        none;
}

.category-btn {
    flex:
        0 0 auto;

    border:
        1px solid var(--border);

    background:
        rgba(255,255,255,.035);

    color:
        #cbd5e1;

    border-radius:
        999px;

    padding:
        9px 14px;

    font-size:
        12px;

    font-weight:
        600;

    transition:
        .2s ease;
}

.category-btn:hover,
.category-btn.active {
    background:
        rgba(59,130,246,.14);

    border-color:
        rgba(59,130,246,.35);

    color:
        #93c5fd;
}

/* =====================================================
   PRODUCT GRID
===================================================== */

.product-grid {
    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

    gap: 17px;
}

.product-card {
    position: relative;

    display: flex;

    flex-direction: column;

    overflow:
        hidden;

    border:
        1px solid var(--border);

    background:
        linear-gradient(
            180deg,
            rgba(16,24,43,.88),
            rgba(7,11,23,.94)
        );

    border-radius:
        20px;

    transition:
        transform .25s ease,
        border-color .25s ease,
        box-shadow .25s ease;
}

.product-card:hover {
    transform:
        translateY(-5px);

    border-color:
        rgba(59,130,246,.3);

    box-shadow:
        0 20px 45px rgba(0,0,0,.3);
}

.product-image {
    position: relative;

    height:
        220px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        18px;

    background:
        radial-gradient(
            circle at center,
            rgba(59,130,246,.11),
            transparent 65%
        );
}

.product-image img {
    width:
        100%;

    height:
        100%;

    object-fit:
        contain;

    transition:
        transform .3s ease;
}

.product-card:hover .product-image img {
    transform:
        scale(1.04);
}

.no-image {
    width:
        76px;

    height:
        76px;

    display:
        grid;

    place-items:
        center;

    border-radius:
        20px;

    background:
        rgba(59,130,246,.1);

    color:
        #60a5fa;

    font-size:
        27px;
}

.product-badge {
    position: absolute;

    top:
        13px;

    left:
        13px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        6px;

    padding:
        7px 9px;

    border-radius:
        999px;

    font-size:
        10px;

    font-weight:
        800;
}

.badge-flash {
    background:
        rgba(250,204,21,.13);

    color:
        #fde047;

    border:
        1px solid rgba(250,204,21,.22);
}

.product-status {
    position: absolute;

    top:
        13px;

    right:
        13px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        6px;

    padding:
        7px 9px;

    border-radius:
        999px;

    font-size:
        10px;

    font-weight:
        700;
}

.product-status.ready {
    color:
        #86efac;

    background:
        rgba(34,197,94,.10);

    border:
        1px solid rgba(34,197,94,.18);
}

.product-status.low {
    color:
        #fde68a;

    background:
        rgba(250,204,21,.10);

    border:
        1px solid rgba(250,204,21,.18);
}

.product-status.out {
    color:
        #fca5a5;

    background:
        rgba(239,68,68,.10);

    border:
        1px solid rgba(239,68,68,.18);
}

.product-body {
    padding:
        18px;
}

.product-category {
    color:
        #60a5fa;

    text-transform:
        uppercase;

    font-size:
        10px;

    font-weight:
        800;

    letter-spacing:
        .8px;

    margin-bottom:
        6px;
}

.product-name {
    font-family:
        "Space Grotesk",
        sans-serif;

    font-size:
        17px;

    font-weight:
        700;

    line-height:
        1.35;

    min-height:
        46px;
}

.product-description {
    color:
        var(--muted);

    font-size:
        11px;

    line-height:
        1.65;

    margin-top:
        8px;

    min-height:
        37px;
}

.product-price {
    margin-top:
        16px;

    font-family:
        "Space Grotesk",
        sans-serif;

    font-size:
        20px;

    font-weight:
        800;

    color:
        white;
}

.product-stock {
    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        10px;

    margin-top:
        8px;

    color:
        #64748b;

    font-size:
        11px;
}

.product-actions {
    display:
        grid;

    grid-template-columns:
        1fr 44px;

    gap:
        8px;

    margin-top:
        17px;
}

.buy-btn {
    min-height:
        42px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        8px;

    border:
        0;

    border-radius:
        12px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #0891b2
        );

    color:
        white;

    font-size:
        12px;

    font-weight:
        800;
}

.buy-btn.disabled {
    opacity:
        .4;

    pointer-events:
        none;
}

.detail-btn {
    display:
        grid;

    place-items:
        center;

    border:
        1px solid var(--border);

    border-radius:
        12px;

    background:
        rgba(255,255,255,.035);

    color:
        #cbd5e1;
}

/* =====================================================
   EMPTY
===================================================== */

.empty {
    display:
        none;

    text-align:
        center;

    padding:
        65px 20px;

    border:
        1px dashed rgba(255,255,255,.12);

    border-radius:
        20px;

    color:
        var(--muted);
}

.empty i {
    font-size:
        35px;

    color:
        #475569;

    margin-bottom:
        13px;
}

.empty.show {
    display:
        block;
}

/* =====================================================
   CONTACT
===================================================== */

.contact-box {
    display:
        grid;

    grid-template-columns:
        1.2fr .8fr;

    gap:
        20px;

    padding:
        28px;

    border:
        1px solid var(--border);

    border-radius:
        24px;

    background:
        linear-gradient(
            135deg,
            rgba(37,99,235,.10),
            rgba(255,255,255,.025)
        );
}

.contact-box h2 {
    font-family:
        "Space Grotesk",
        sans-serif;

    font-size:
        27px;
}

.contact-box p {
    color:
        var(--muted);

    font-size:
        13px;

    line-height:
        1.8;

    margin-top:
        8px;
}

.contact-info {
    display:
        flex;

    flex-direction:
        column;

    gap:
        10px;
}

.contact-item {
    display:
        flex;

    align-items:
        center;

    gap:
        11px;

    padding:
        12px 14px;

    border:
        1px solid var(--border);

    background:
        rgba(0,0,0,.15);

    border-radius:
        13px;

    color:
        #cbd5e1;

    font-size:
        12px;
}

.contact-item i {
    color:
        #60a5fa;

    width:
        18px;

    text-align:
        center;
}

/* =====================================================
   FOOTER
===================================================== */

.footer {
    padding:
        35px 0;

    border-top:
        1px solid var(--border);

    margin-top:
        20px;

    color:
        #64748b;

    font-size:
        11px;
}

.footer-inner {
    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        20px;

    flex-wrap:
        wrap;
}

.socials {
    display:
        flex;

    gap:
        8px;
}

.social {
    width:
        35px;

    height:
        35px;

    display:
        grid;

    place-items:
        center;

    border:
        1px solid var(--border);

    border-radius:
        10px;

    background:
        rgba(255,255,255,.03);

    color:
        #94a3b8;

    transition:
        .2s ease;
}

.social:hover {
    color:
        white;

    border-color:
        rgba(59,130,246,.4);
}

/* =====================================================
   FLOATING WA
===================================================== */

.float-wa {
    position:
        fixed;

    right:
        20px;

    bottom:
        20px;

    z-index:
        900;

    width:
        52px;

    height:
        52px;

    display:
        grid;

    place-items:
        center;

    border-radius:
        50%;

    background:
        #22c55e;

    color:
        white;

    font-size:
        22px;

    box-shadow:
        0 15px 35px rgba(34,197,94,.28);

    transition:
        .2s ease;
}

.float-wa:hover {
    transform:
        translateY(-3px)
        scale(1.03);
}

/* =====================================================
   MODAL
===================================================== */

.modal {
    position:
        fixed;

    inset:
        0;

    z-index:
        2000;

    display:
        none;

    align-items:
        center;

    justify-content:
        center;

    padding:
        20px;

    background:
        rgba(0,0,0,.72);

    backdrop-filter:
        blur(8px);
}

.modal.show {
    display:
        flex;
}

.modal-box {
    width:
        min(560px, 100%);

    max-height:
        90vh;

    overflow:
        auto;

    border:
        1px solid var(--border);

    border-radius:
        22px;

    background:
        #0a1020;

    box-shadow:
        0 30px 90px rgba(0,0,0,.5);
}

.modal-head {
    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    padding:
        18px 20px;

    border-bottom:
        1px solid var(--border);
}

.modal-title {
    font-family:
        "Space Grotesk",
        sans-serif;

    font-weight:
        800;
}

.modal-close {
    width:
        35px;

    height:
        35px;

    display:
        grid;

    place-items:
        center;

    border:
        0;

    border-radius:
        10px;

    background:
        rgba(255,255,255,.06);

    color:
        #cbd5e1;
}

.modal-content {
    padding:
        20px;
}

.modal-product-image {
    width:
        100%;

    height:
        250px;

    object-fit:
        contain;

    border-radius:
        16px;

    background:
        rgba(255,255,255,.025);

    margin-bottom:
        18px;
}

.modal-product-name {
    font-family:
        "Space Grotesk",
        sans-serif;

    font-size:
        23px;

    font-weight:
        800;
}

.modal-product-price {
    color:
        #60a5fa;

    font-size:
        20px;

    font-weight:
        800;

    margin-top:
        7px;
}

.modal-product-desc {
    color:
        var(--muted);

    font-size:
        13px;

    line-height:
        1.8;

    margin-top:
        15px;
}

/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 900px) {

    .hero-grid {
        grid-template-columns:
            1fr;
    }

    .product-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .contact-box {
        grid-template-columns:
            1fr;
    }

}

@media (max-width: 680px) {

    .container {
        width:
            min(100% - 22px, 1180px);
    }

    .nav-inner {
        min-height:
            64px;
    }

    .nav-links .nav-link {
        display:
            none;
    }

    .brand-name {
        font-size:
            14px;
    }

    .brand-sub {
        font-size:
            9px;
    }

    .nav-admin {
        padding:
            9px 11px;
    }

    .nav-admin span {
        display:
            none;
    }

    .hero {
        padding:
            55px 0 35px;
    }

    .hero h1 {
        font-size:
            42px;

        letter-spacing:
            -2px;
    }

    .hero p {
        font-size:
            13px;
    }

    .stats {
        grid-template-columns:
            repeat(2, 1fr);
    }

    .product-grid {
        grid-template-columns:
            1fr;
    }

    .product-image {
        height:
            230px;
    }

    .section-title {
        font-size:
            24px;
    }

    .contact-box {
        padding:
            21px;
    }

}

@media (max-width: 390px) {

    .hero h1 {
        font-size:
            36px;
    }

    .stats {
        gap:
            8px;
    }

    .stat {
        padding:
            15px;
    }

}

/* =====================================================
   ANIMATION
===================================================== */

@keyframes fadeUp {

    from {
        opacity:
            0;

        transform:
            translateY(12px);
    }

    to {
        opacity:
            1;

        transform:
            translateY(0);
    }

}

.product-card {
    animation:
        fadeUp .45s ease both;
}

</style>

</head>

<body>

<!-- ==================================================
     NAVBAR
================================================== -->

<header class="navbar">

    <div class="container nav-inner">

        <a
            href="index.php"
            class="brand"
        >

            <div class="brand-logo">
                <i class="fa-solid fa-microchip"></i>
            </div>

            <div class="brand-text">

                <div class="brand-name">
                    <?= e($storeName) ?>
                </div>

                <div class="brand-sub">
                    Computer Store & IT Solution
                </div>

            </div>

        </a>

        <nav class="nav-links">

            <a
                href="#produk"
                class="nav-link"
            >
                Produk
            </a>

            <a
                href="#tentang"
                class="nav-link"
            >
                Tentang
            </a>

            <a
                href="#kontak"
                class="nav-link"
            >
                Kontak
            </a>

            <!--
                PENTING:
                SELALU login.php

                login.php akan mengecek session.
            -->

            <a
                href="login.php"
                class="nav-admin"
                title="Login Admin"
            >

                <i class="fa-solid fa-user-shield"></i>

                <span>
                    Admin
                </span>

            </a>

        </nav>

    </div>

</header>


<!-- ==================================================
     HERO
================================================== -->

<main>

<section class="hero">

    <div class="container">

        <div class="hero-grid">

            <div>

                <div class="hero-badge">

                    <i class="fa-solid fa-circle-check"></i>

                    Ready Stock • Original Product

                </div>

                <h1>

                    Upgrade
                    <span>Your Digital Life.</span>

                </h1>

                <p>
                    <?= e($storeBio) ?>
                    Temukan laptop, PC, monitor, printer,
                    sparepart, networking dan berbagai kebutuhan
                    komputer lainnya di <?= e($storeName) ?>.
                </p>

                <div class="hero-actions">

                    <a
                        href="#produk"
                        class="btn btn-primary"
                    >
                        <i class="fa-solid fa-store"></i>
                        Lihat Produk
                    </a>

                    <?php if ($wa !== ''): ?>

                        <a
                            href="<?= e($generalWa) ?>"
                            target="_blank"
                            rel="noopener"
                            class="btn btn-outline"
                        >
                            <i class="fa-brands fa-whatsapp"></i>
                            Chat WhatsApp
                        </a>

                    <?php endif; ?>

                </div>

            </div>


            <div class="hero-card">

                <div class="hero-card-icon">

                    <i class="fa-solid fa-shield-halved"></i>

                </div>

                <h3>
                    Belanja Lebih Mudah
                </h3>

                <p>
                    Pilih produk, cek stok, lalu langsung
                    hubungi kami melalui WhatsApp.
                </p>

                <div class="hero-card-info">

                    <div class="info-row">

                        <i class="fa-solid fa-location-dot"></i>

                        <a
                            href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($address) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <?= e($address) ?>
                        </a>

                    </div>

                    <div class="info-row">

                        <i class="fa-solid fa-envelope"></i>

                        <a href="mailto:<?= e($email) ?>">
                            <?= e($email) ?>
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- ==================================================
     STATISTICS
================================================== -->

<section>

    <div class="container">

        <div class="stats">

            <div class="stat">

                <div class="stat-icon">
                    <i class="fa-solid fa-box"></i>
                </div>

                <div class="stat-number">
                    <?= $totalProducts ?>
                </div>

                <div class="stat-label">
                    Produk
                </div>

            </div>


            <div class="stat">

                <div class="stat-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <div class="stat-number">
                    <?= $totalReady ?>
                </div>

                <div class="stat-label">
                    Ready Stock
                </div>

            </div>


            <div class="stat">

                <div class="stat-icon">
                    <i class="fa-solid fa-bolt"></i>
                </div>

                <div class="stat-number">
                    <?= $totalFlash ?>
                </div>

                <div class="stat-label">
                    Flash Sale
                </div>

            </div>


            <div class="stat">

                <div class="stat-icon">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>

                <div class="stat-number">
                    <?= $totalRestock ?>
                </div>

                <div class="stat-label">
                    Stok Terbatas
                </div>

            </div>

        </div>

    </div>

</section>


<!-- ==================================================
     PRODUCT
================================================== -->

<section
    class="section"
    id="produk"
>

    <div class="container">

        <div class="section-head">

            <div>

                <h2 class="section-title">
                    Produk Pilihan
                </h2>

                <p class="section-desc">
                    Cari kebutuhan komputer kamu dengan mudah.
                </p>

            </div>

        </div>


        <!-- SEARCH -->

        <div class="catalog-toolbar">

            <div class="search-box">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="search"
                    id="searchInput"
                    placeholder="Cari laptop, PC, RAM, SSD, printer..."
                    autocomplete="off"
                >

            </div>

        </div>


        <!-- CATEGORY -->

        <div class="category-list">

            <button
                class="category-btn active"
                type="button"
                data-category="all"
            >
                Semua
            </button>

            <?php foreach ($categories as $category): ?>

                <button
                    class="category-btn"
                    type="button"
                    data-category="<?= e(strtolower($category)) ?>"
                >
                    <?= e($category) ?>
                </button>

            <?php endforeach; ?>

        </div>


        <div
            class="product-grid"
            id="productGrid"
            style="margin-top:20px;"
        >

        <?php if (count($products) > 0): ?>

            <?php foreach ($products as $index => $product): ?>

                <?php

                if (!is_array($product)) {
                    continue;
                }

                $name = (string)($product['name'] ?? 'Produk');

                $category = (string)(
                    $product['category']
                    ?? 'Lainnya'
                );

                $description = (string)(
                    $product['description']
                    ?? 'Produk komputer berkualitas.'
                );

                $price = (float)(
                    $product['price']
                    ?? 0
                );

                $stock = getStockNumber($product);

                $status = getProductStatus($product);

                $image = (string)(
                    $product['image']
                    ?? $product['image_url']
                    ?? ''
                );

                $sku = (string)(
                    $product['sku']
                    ?? $product['code']
                    ?? ''
                );

                $waProduct = productWhatsApp(
                    $product,
                    $waBase
                );

                $searchText = productSearchText($product);

                ?>

                <article
                    class="product-card"
                    data-category="<?= e(strtolower($category)) ?>"
                    data-search="<?= e($searchText) ?>"
                >

                    <div class="product-image">

                        <?php if (isFlashSale($product)): ?>

                            <div class="product-badge badge-flash">

                                <i class="fa-solid fa-bolt"></i>

                                Flash Sale

                            </div>

                        <?php endif; ?>


                        <div
                            class="product-status <?= e($status['class']) ?>"
                        >

                            <i
                                class="fa-solid <?= e($status['icon']) ?>"
                            ></i>

                            <?= e($status['label']) ?>

                        </div>


                        <?php if ($image !== ''): ?>

                            <img
                                src="<?= e($image) ?>"
                                alt="<?= e($name) ?>"
                                loading="lazy"
                                onerror="this.style.display='none';this.nextElementSibling.style.display='grid';"
                            >

                            <div
                                class="no-image"
                                style="display:none;"
                            >
                                <i class="fa-solid fa-image"></i>
                            </div>

                        <?php else: ?>

                            <div class="no-image">

                                <i class="fa-solid fa-computer"></i>

                            </div>

                        <?php endif; ?>

                    </div>


                    <div class="product-body">

                        <div class="product-category">
                            <?= e($category) ?>
                        </div>

                        <h3 class="product-name">
                            <?= e($name) ?>
                        </h3>

                        <p class="product-description">
                            <?= e($description) ?>
                        </p>

                        <div class="product-price">
                            <?= rupiah($price) ?>
                        </div>

                        <div class="product-stock">

                            <span>
                                <i class="fa-solid fa-box"></i>
                                Stok:
                                <?= $stock ?>
                            </span>

                            <?php if ($sku !== ''): ?>

                                <span>
                                    <?= e($sku) ?>
                                </span>

                            <?php endif; ?>

                        </div>


                        <div class="product-actions">

                            <?php if ($stock > 0 && $waProduct !== '#'): ?>

                                <a
                                    href="<?= e($waProduct) ?>"
                                    target="_blank"
                                    rel="noopener"
                                    class="buy-btn"
                                    data-notify-name="<?= e($name) ?>"
                                    data-notify-price="<?= e(rupiah($price)) ?>"
                                    data-notify-sku="<?= e($sku) ?>"
                                >

                                    <i class="fa-brands fa-whatsapp"></i>

                                    Tanya / Pesan

                                </a>

                            <?php else: ?>

                                <a
                                    href="#"
                                    class="buy-btn disabled"
                                >

                                    <i class="fa-solid fa-ban"></i>

                                    Stok Habis

                                </a>

                            <?php endif; ?>


                            <button
                                type="button"
                                class="detail-btn"
                                title="Lihat detail"
                                data-detail-index="<?= (int)$index ?>"
                            >

                                <i class="fa-solid fa-eye"></i>

                            </button>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

        </div>


        <div
            class="empty"
            id="emptyState"
        >

            <i class="fa-solid fa-box-open"></i>

            <h3>
                Produk tidak ditemukan
            </h3>

            <p>
                Coba gunakan kata pencarian lain
                atau pilih kategori berbeda.
            </p>

        </div>

    </div>

</section>


<!-- ==================================================
     ABOUT
================================================== -->

<section class="section" id="tentang">

    <div class="container">

        <div class="contact-box">

            <div>

                <div class="hero-badge">
                    <i class="fa-solid fa-circle-info"></i>
                    Tentang <?= e($storeName) ?>
                </div>

                <h2>
                    Solusi Lengkap
                    Perangkat IT.
                </h2>

                <p>
                    <?= e($storeBio) ?>
                    Kami menyediakan berbagai kebutuhan
                    komputer dan perangkat IT mulai dari
                    laptop, PC, monitor, printer, sparepart,
                    networking hingga CCTV.
                </p>

            </div>


            <div class="contact-info">

                <!-- ALAMAT 1 -->
                <a
                    href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($address) ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="contact-item"
                >

                    <i class="fa-solid fa-location-dot"></i>

                    <span>
                        <?= e($address) ?>
                    </span>

                </a>


                <!-- ALAMAT 2 (opsional, diatur lewat Setting) -->
                <?php if ($address2 !== ''): ?>

                    <a
                        href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($address2) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="contact-item"
                    >

                        <i class="fa-solid fa-location-dot"></i>

                        <span>
                            <?= e($address2) ?>
                        </span>

                    </a>

                <?php endif; ?>


                <!-- FACEBOOK -->
                <?php if ($facebook !== ''): ?>

                    <a
                        href="<?= e($facebook) ?>"
                        target="_blank"
                        rel="noopener"
                        class="contact-item"
                    >

                        <i class="fa-brands fa-facebook"></i>

                        <span>
                            Facebook
                        </span>

                    </a>

                <?php endif; ?>

            </div>

        </div>

    </div>

</section>



<!-- ==================================================
     CONTACT
================================================== -->

<section
    class="section"
    id="kontak"
>

    <div class="container">

        <div class="section-head">

            <div>

                <h2 class="section-title">
                    Hubungi Kami
                </h2>

                <p class="section-desc">
                    Butuh konsultasi atau ingin cek produk?
                    Langsung hubungi tim kami.
                </p>

            </div>

        </div>


        <div class="contact-box">

            <div>

                <h2>
                    Siap bantu kebutuhan IT kamu.
                </h2>

                <p>
                    Konsultasikan kebutuhan komputer,
                    upgrade, printer, networking,
                    maintenance dan kebutuhan IT lainnya.
                </p>

                <div
                    class="hero-actions"
                    style="margin-top:20px;"
                >

                    <?php if ($wa !== ''): ?>

                        <a
                            href="<?= e($generalWa) ?>"
                            target="_blank"
                            rel="noopener"
                            class="btn btn-primary"
                        >

                            <i class="fa-brands fa-whatsapp"></i>

                            WhatsApp

                        </a>

                    <?php endif; ?>

                    <a
                        href="mailto:<?= e($email) ?>"
                        class="btn btn-outline"
                    >

                        <i class="fa-solid fa-envelope"></i>

                        Email

                    </a>

                </div>

            </div>


            <div class="contact-info">

                <?php if ($instagram !== ''): ?>

                    <a
                        href="<?= e($instagram) ?>"
                        target="_blank"
                        rel="noopener"
                        class="contact-item"
                    >

                        <i class="fa-brands fa-instagram"></i>

                        <span>
                            Instagram
                        </span>

                    </a>

                <?php endif; ?>


                <?php if ($facebook !== ''): ?>

                    <a
                        href="<?= e($facebook) ?>"
                        target="_blank"
                        rel="noopener"
                        class="contact-item"
                    >

                        <i class="fa-brands fa-facebook"></i>

                        <span>
                            Facebook
                        </span>

                    </a>

                <?php endif; ?>


                <?php if ($tiktok !== ''): ?>

                    <a
                        href="<?= e($tiktok) ?>"
                        target="_blank"
                        rel="noopener"
                        class="contact-item"
                    >

                        <i class="fa-brands fa-tiktok"></i>

                        <span>
                            TikTok
                        </span>

                    </a>

                <?php endif; ?>

            </div>

        </div>

    </div>

</section>

</main>


<!-- ==================================================
     FOOTER
================================================== -->

<footer class="footer">

    <div class="container footer-inner">

        <div>

            © <?= date('Y') ?>
            <?= e($storeName) ?>.
            All rights reserved.

        </div>


        <div class="socials">

            <?php if ($instagram !== ''): ?>

                <a
                    href="<?= e($instagram) ?>"
                    target="_blank"
                    rel="noopener"
                    class="social"
                    title="Instagram"
                >

                    <i class="fa-brands fa-instagram"></i>

                </a>

            <?php endif; ?>


            <?php if ($facebook !== ''): ?>

                <a
                    href="<?= e($facebook) ?>"
                    target="_blank"
                    rel="noopener"
                    class="social"
                    title="Facebook"
                >

                    <i class="fa-brands fa-facebook"></i>

                </a>

            <?php endif; ?>


            <?php if ($tiktok !== ''): ?>

                <a
                    href="<?= e($tiktok) ?>"
                    target="_blank"
                    rel="noopener"
                    class="social"
                    title="TikTok"
                >

                    <i class="fa-brands fa-tiktok"></i>

                </a>

            <?php endif; ?>

        </div>

    </div>

</footer>


<!-- ==================================================
     FLOATING WHATSAPP
================================================== -->

<?php if ($wa !== ''): ?>

<a
    href="<?= e($generalWa) ?>"
    target="_blank"
    rel="noopener"
    class="float-wa"
    title="Chat WhatsApp"
>

    <i class="fa-brands fa-whatsapp"></i>

</a>

<?php endif; ?>


<!-- ==================================================
     DETAIL MODAL
================================================== -->

<div
    class="modal"
    id="productModal"
>

    <div class="modal-box">

        <div class="modal-head">

            <div class="modal-title">
                Detail Produk
            </div>

            <button
                type="button"
                class="modal-close"
                id="modalClose"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>


        <div
            class="modal-content"
            id="modalContent"
        ></div>

    </div>

</div>


<!-- ==================================================
     JAVASCRIPT
================================================== -->

<script>

const products = <?= json_encode(
    array_values($products),
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES |
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT
) ?>;

const searchInput =
    document.getElementById('searchInput');

const productCards =
    Array.from(
        document.querySelectorAll('.product-card')
    );

const categoryButtons =
    Array.from(
        document.querySelectorAll('.category-btn')
    );

const emptyState =
    document.getElementById('emptyState');

let activeCategory = 'all';


/* =====================================================
   FILTER
===================================================== */

function filterProducts() {

    const query =
        (searchInput?.value || '')
        .trim()
        .toLowerCase();

    let visible = 0;

    productCards.forEach(card => {

        const category =
            (card.dataset.category || '')
            .toLowerCase();

        const search =
            (card.dataset.search || '')
            .toLowerCase();

        const categoryMatch =
            activeCategory === 'all' ||
            category === activeCategory;

        const searchMatch =
            query === '' ||
            search.includes(query);

        const show =
            categoryMatch &&
            searchMatch;

        card.style.display =
            show ? '' : 'none';

        if (show) {
            visible++;
        }

    });

    if (emptyState) {

        emptyState.classList.toggle(
            'show',
            visible === 0
        );

    }

}


/* =====================================================
   SEARCH
===================================================== */

if (searchInput) {

    searchInput.addEventListener(
        'input',
        filterProducts
    );

}


/* =====================================================
   CATEGORY
===================================================== */

categoryButtons.forEach(button => {

    button.addEventListener(
        'click',
        () => {

            categoryButtons.forEach(btn => {

                btn.classList.remove(
                    'active'
                );

            });

            button.classList.add(
                'active'
            );

            activeCategory =
                (
                    button.dataset.category
                    || 'all'
                ).toLowerCase();

            filterProducts();

        }
    );

});


/* =====================================================
   MODAL
===================================================== */

const modal =
    document.getElementById('productModal');

const modalContent =
    document.getElementById('modalContent');

const modalClose =
    document.getElementById('modalClose');


function openProduct(index) {

    const product =
        products[index];

    if (!product) {
        return;
    }

    const name =
        product.name || 'Produk';

    const category =
        product.category || 'Lainnya';

    const price =
        Number(product.price || 0);

    const description =
        product.description ||
        'Produk komputer berkualitas.';

    const stock =
        Number(product.stock || 0);

    const image =
        product.image ||
        product.image_url ||
        '';

    let imageHTML = '';

    if (image) {

        imageHTML = `
            <img
                src="${escapeHtml(image)}"
                class="modal-product-image"
                alt="${escapeHtml(name)}"
            >
        `;

    } else {

        imageHTML = `
            <div
                class="modal-product-image"
                style="
                    display:grid;
                    place-items:center;
                    color:#60a5fa;
                    font-size:55px;
                "
            >
                <i class="fa-solid fa-computer"></i>
            </div>
        `;

    }

    const priceText =
        'Rp ' +
        price.toLocaleString(
            'id-ID'
        );

    modalContent.innerHTML = `

        ${imageHTML}

        <div class="product-category">
            ${escapeHtml(category)}
        </div>

        <div class="modal-product-name">
            ${escapeHtml(name)}
        </div>

        <div class="modal-product-price">
            ${priceText}
        </div>

        <div class="modal-product-desc">
            ${escapeHtml(description)}
        </div>

        <div
            style="
                margin-top:18px;
                color:#94a3b8;
                font-size:12px;
            "
        >
            <i class="fa-solid fa-box"></i>
            Stok tersedia:
            <strong style="color:white;">
                ${stock}
            </strong>
        </div>

    `;

    modal.classList.add('show');

    document.body.style.overflow =
        'hidden';

}


function closeProduct() {

    modal.classList.remove(
        'show'
    );

    document.body.style.overflow =
        '';

}


function escapeHtml(value) {

    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

}


document
    .querySelectorAll(
        '[data-detail-index]'
    )
    .forEach(button => {

        button.addEventListener(
            'click',
            () => {

                const index =
                    Number(
                        button.dataset.detailIndex
                    );

                openProduct(index);

            }
        );

    });


modalClose.addEventListener(
    'click',
    closeProduct
);


modal.addEventListener(
    'click',
    event => {

        if (
            event.target === modal
        ) {
            closeProduct();
        }

    }
);


document.addEventListener(
    'keydown',
    event => {

        if (
            event.key === 'Escape'
        ) {
            closeProduct();
        }

    }
);


/* =====================================================
   NOTIFY TELEGRAM (AKTIVITAS PEMBELIAN)
===================================================== */

document
    .querySelectorAll('.buy-btn[data-notify-name]')
    .forEach(btn => {

        btn.addEventListener('click', () => {

            try {

                const data = new FormData();

                data.append('type', 'wa');
                data.append('name', btn.dataset.notifyName || 'Produk');
                data.append('price', btn.dataset.notifyPrice || '');
                data.append('sku', btn.dataset.notifySku || '');

                navigator.sendBeacon('notify.php', data);

            } catch (err) {
                /* diamkan, jangan sampai mengganggu link WA */
            }

        });

    });


/* =====================================================
   INITIAL
===================================================== */

filterProducts();

</script>

</body>
</html>
