<?php
declare(strict_types=1);

require __DIR__ . '/common.php';
require_login();

$products = read_json(PRODUCTS_FILE, []);
$currentUser = user() ?? [];
$storeName = $S['store_name'] ?? 'GEOTAMA COMPUTER';

/* =========================================================
   HELPERS
========================================================= */

function getProductStatus(array $p): array
{
    $manual = strtoupper(trim((string)($p['status'] ?? '')));
    $stock  = $p['stock'] ?? '';

    if ($manual === 'RESTOCKING') {
        return [
            'key' => 'restocking',
            'label' => 'RESTOCKING',
            'icon' => 'fa-arrows-rotate'
        ];
    }

    if (is_numeric($stock)) {
        if ((int)$stock <= 0) {
            return [
                'key' => 'out',
                'label' => 'OUT OF STOCK',
                'icon' => 'fa-box-open'
            ];
        }

        return [
            'key' => 'ready',
            'label' => 'READY STOCK',
            'icon' => 'fa-circle-check'
        ];
    }

    $stockText = strtoupper(trim((string)$stock));

    if (
        $stockText === '' ||
        $stockText === 'OUT OF STOCK' ||
        $stockText === 'HABIS' ||
        $stockText === 'KOSONG'
    ) {
        return [
            'key' => 'out',
            'label' => 'OUT OF STOCK',
            'icon' => 'fa-box-open'
        ];
    }

    return [
        'key' => 'ready',
        'label' => 'READY STOCK',
        'icon' => 'fa-circle-check'
    ];
}

function isFlashSale(array $p): bool
{
    $v = $p['flash_sale'] ?? false;

    return
        $v === true ||
        $v === 1 ||
        $v === '1' ||
        strtoupper((string)$v) === 'TRUE';
}


/* =========================================================
   STATISTICS
========================================================= */

$readyCount = 0;
$outCount = 0;
$restockingCount = 0;
$flashSaleCount = 0;
$totalStock = 0;

foreach ($products as $p) {

    $status = getProductStatus($p);

    if ($status['key'] === 'ready') {
        $readyCount++;
    }

    if ($status['key'] === 'out') {
        $outCount++;
    }

    if ($status['key'] === 'restocking') {
        $restockingCount++;
    }

    if (isFlashSale($p)) {
        $flashSaleCount++;
    }

    if (is_numeric($p['stock'] ?? null)) {
        $totalStock += (int)$p['stock'];
    }
}


/* =========================================================
   TOAST
========================================================= */

$toastMessage = '';

if (isset($_GET['msg'])) {
    $toastMessage = trim((string)$_GET['msg']);
}

$toastType = trim((string)($_GET['type'] ?? 'success'));

$allowedToastTypes = [
    'success',
    'error',
    'warning',
    'info'
];

if (!in_array($toastType, $allowedToastTypes, true)) {
    $toastType = 'success';
}

?>
<!doctype html>
<html lang="id">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<meta
    name="theme-color"
    content="#020617">

<title>
Dashboard — <?=h($storeName)?>
</title>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com">

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin>

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap"
    rel="stylesheet">

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">


<style>

/* =========================================================
   RESET
========================================================= */

*{
    box-sizing:border-box;
}

html{
    scroll-behavior:smooth;
}

body{
    margin:0;
}


/* =========================================================
   VARIABLES
========================================================= */

:root{

    --bg:#020617;
    --bg2:#030b17;
    --bg3:#07111f;

    --panel:#08111f;
    --panel2:#0b1627;
    --panel3:#0f1b2e;

    --blue:#2563eb;
    --blue2:#3b82f6;
    --blue3:#60a5fa;

    --cyan:#06b6d4;

    --green:#22c55e;
    --red:#ef4444;
    --yellow:#f59e0b;

    --white:#f8fafc;
    --text:#e5e7eb;
    --muted:#94a3b8;
    --muted2:#64748b;

    --border:rgba(148,163,184,.11);

    --shadow:
        0 20px 60px
        rgba(0,0,0,.35);
}


/* =========================================================
   BODY — PURE BLACK BLUE
========================================================= */

body{

    min-height:100vh;

    font-family:
        "Inter",
        sans-serif;

    color:var(--text);

    background:

        radial-gradient(
            circle at 8% 0%,
            rgba(37,99,235,.20),
            transparent 28%
        ),

        radial-gradient(
            circle at 92% 5%,
            rgba(6,182,212,.12),
            transparent 25%
        ),

        radial-gradient(
            circle at 50% 50%,
            rgba(37,99,235,.045),
            transparent 45%
        ),

        linear-gradient(
            135deg,
            #020617 0%,
            #030914 45%,
            #020617 100%
        );

    background-attachment:fixed;
}


/* =========================================================
   CONTAINER
========================================================= */

.container{

    width:
        min(
            1400px,
            calc(100% - 32px)
        );

    margin:auto;
}


/* =========================================================
   TOPBAR
========================================================= */

.topbar{

    position:sticky;

    top:0;

    z-index:1000;

    background:
        rgba(2,6,23,.88);

    backdrop-filter:
        blur(22px);

    -webkit-backdrop-filter:
        blur(22px);

    border-bottom:
        1px solid
        rgba(96,165,250,.10);

    box-shadow:
        0 15px 40px
        rgba(0,0,0,.35);
}


.nav{

    min-height:72px;

    display:flex;

    align-items:center;

    gap:20px;
}


/* =========================================================
   BRAND
========================================================= */

.brand{

    display:flex;

    align-items:center;

    gap:11px;

    color:#fff;

    text-decoration:none;

    flex-shrink:0;
}


.logo{

    width:43px;
    height:43px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:13px;

    color:#fff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #06b6d4
        );

    box-shadow:
        0 8px 30px
        rgba(37,99,235,.38);
}


.brand strong{

    display:block;

    color:#fff;

    font-family:
        "Space Grotesk",
        sans-serif;

    font-size:15px;

    letter-spacing:-.3px;
}


.brand small{

    display:block;

    margin-top:2px;

    color:#64748b;

    font-size:8px;

    font-weight:800;

    letter-spacing:1.2px;
}


/* =========================================================
   NAV LINKS
========================================================= */

.navlinks{

    display:flex;

    align-items:center;

    justify-content:center;

    gap:4px;

    flex:1;
}


.navlinks a{

    display:inline-flex;

    align-items:center;

    gap:7px;

    padding:9px 12px;

    color:#94a3b8;

    text-decoration:none;

    border-radius:9px;

    font-size:11px;

    font-weight:700;

    transition:.2s ease;
}


.navlinks a:hover{

    color:#fff;

    background:
        rgba(59,130,246,.08);
}


.navlinks a.active{

    color:#60a5fa;

    background:
        rgba(37,99,235,.13);

    border:
        1px solid
        rgba(59,130,246,.12);
}


/* =========================================================
   BUTTON
========================================================= */

.btn{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    gap:8px;

    min-height:40px;

    padding:0 15px;

    border-radius:10px;

    border:1px solid transparent;

    font-size:11px;

    font-weight:800;

    text-decoration:none;

    cursor:pointer;

    transition:.2s ease;
}


.btn:hover{

    transform:translateY(-1px);
}


.btn-dark{

    color:#e2e8f0;

    background:
        rgba(255,255,255,.045);

    border:
        1px solid
        rgba(148,163,184,.13);
}


.btn-dark:hover{

    color:#fff;

    background:
        rgba(59,130,246,.12);

    border-color:
        rgba(96,165,250,.25);
}


.btn-primary{

    color:#fff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #06b6d4
        );

    border:0;

    box-shadow:
        0 10px 30px
        rgba(37,99,235,.25);
}


.btn-primary:hover{

    box-shadow:
        0 14px 38px
        rgba(37,99,235,.38);
}


/* =========================================================
   MAIN
========================================================= */

.section{

    padding:
        34px 0 70px;
}


/* =========================================================
   TITLE
========================================================= */

.title-row{

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:20px;

    margin-bottom:25px;
}


.title-row h2{

    margin:
        0 0 7px;

    color:#fff;

    font-family:
        "Space Grotesk",
        sans-serif;

    font-size:28px;

    letter-spacing:-1px;
}


.dashboard-user{

    color:#94a3b8;

    font-size:12px;

    display:flex;

    align-items:center;

    gap:8px;
}


.user-dot{

    width:7px;
    height:7px;

    border-radius:50%;

    background:#22c55e;

    box-shadow:
        0 0 0 5px
        rgba(34,197,94,.09);
}


/* =========================================================
   STATS
========================================================= */

.stats{

    display:grid;

    grid-template-columns:
        repeat(5,1fr);

    gap:15px;

    margin-bottom:22px;
}


.stat{

    position:relative;

    overflow:hidden;

    padding:19px;

    border-radius:18px;

    background:
        linear-gradient(
            145deg,
            rgba(15,23,42,.96),
            rgba(3,9,20,.98)
        );

    border:
        1px solid
        rgba(96,165,250,.10);

    box-shadow:
        0 18px 50px
        rgba(0,0,0,.28),

        inset
        0 1px 0
        rgba(255,255,255,.025);

    transition:.25s ease;
}


.stat::after{

    content:"";

    position:absolute;

    width:130px;
    height:130px;

    right:-70px;
    bottom:-70px;

    border-radius:50%;

    background:
        rgba(37,99,235,.13);

    filter:blur(2px);
}


.stat:hover{

    transform:
        translateY(-4px);

    border-color:
        rgba(59,130,246,.25);

    box-shadow:
        0 22px 60px
        rgba(0,0,0,.4),

        0 0 35px
        rgba(37,99,235,.07);
}


.stat i{

    width:40px;
    height:40px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:11px;

    margin-bottom:13px;

    color:#60a5fa;

    background:
        rgba(37,99,235,.12);

    border:
        1px solid
        rgba(96,165,250,.10);
}


.stat b{

    position:relative;

    z-index:1;

    display:block;

    color:#fff;

    font-family:
        "Space Grotesk",
        sans-serif;

    font-size:25px;

    line-height:1.1;
}


.stat span{

    position:relative;

    z-index:1;

    display:block;

    margin-top:5px;

    color:#64748b;

    font-size:11px;
}


.stat-ready i{

    color:#4ade80;

    background:
        rgba(34,197,94,.10);
}


.stat-out i{

    color:#f87171;

    background:
        rgba(239,68,68,.10);
}


.stat-restock i{

    color:#fbbf24;

    background:
        rgba(245,158,11,.10);
}


.stat-flash i{

    color:#22d3ee;

    background:
        rgba(6,182,212,.10);
}


/* =========================================================
   PANEL
========================================================= */

.panel{

    background:
        linear-gradient(
            145deg,
            rgba(11,22,39,.97),
            rgba(3,9,20,.98)
        );

    border:
        1px solid
        rgba(96,165,250,.10);

    border-radius:20px;

    padding:22px;

    box-shadow:
        0 20px 65px
        rgba(0,0,0,.35),

        inset
        0 1px 0
        rgba(255,255,255,.025);
}


.panel-head{

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:15px;

    margin-bottom:18px;
}


.panel-head h3{

    margin:0;

    color:#fff;

    font-family:
        "Space Grotesk",
        sans-serif;

    font-size:18px;
}


.product-count{

    color:#64748b;

    font-size:12px;
}


/* =========================================================
   TABLE
========================================================= */

.table-wrap{

    overflow:auto;

    border-radius:13px;

    border:
        1px solid
        rgba(148,163,184,.07);
}


.table{

    width:100%;

    min-width:950px;

    border-collapse:separate;

    border-spacing:0;
}


.table th{

    padding:
        13px 14px;

    background:
        #0a1424;

    color:#64748b;

    font-size:10px;

    text-transform:uppercase;

    letter-spacing:.7px;

    white-space:nowrap;

    border-bottom:
        1px solid
        rgba(148,163,184,.08);
}


.table td{

    padding:14px;

    color:#cbd5e1;

    font-size:12px;

    vertical-align:middle;

    background:
        rgba(5,12,24,.65);

    border-bottom:
        1px solid
        rgba(148,163,184,.055);
}


.table tbody tr:last-child td{
    border-bottom:0;
}


.table tbody tr{

    transition:.2s ease;
}


.table tbody tr:hover td{

    background:
        rgba(37,99,235,.055);
}


/* =========================================================
   PRODUCT
========================================================= */

.product-name{

    display:flex;

    align-items:center;

    gap:11px;

    min-width:250px;
}


.product-thumb,
.product-thumb-fallback{

    width:44px;
    height:44px;

    flex-shrink:0;

    border-radius:11px;
}


.product-thumb{

    object-fit:cover;

    background:#0f1b2e;

    border:
        1px solid
        rgba(96,165,250,.10);
}


.product-thumb-fallback{

    display:flex;

    align-items:center;
    justify-content:center;

    background:
        #0d1728;

    color:#475569;

    border:
        1px solid
        rgba(96,165,250,.08);
}


.product-title{

    max-width:260px;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;

    color:#f8fafc;

    font-weight:700;
}


.product-category{

    color:#64748b;

    font-size:10px;

    margin-top:3px;
}


/* =========================================================
   SPEC
========================================================= */

.spec-mini{

    display:flex;

    flex-wrap:wrap;

    gap:5px;

    max-width:430px;
}


.spec-chip{

    display:inline-flex;

    align-items:center;

    gap:5px;

    padding:
        5px 7px;

    border-radius:6px;

    color:#94a3b8;

    background:
        rgba(255,255,255,.035);

    border:
        1px solid
        rgba(148,163,184,.08);

    font-size:9px;

    white-space:nowrap;
}


.spec-chip i{

    color:#60a5fa;

    font-size:8px;
}


/* =========================================================
   STATUS
========================================================= */

.status-badge{

    display:inline-flex;

    align-items:center;

    gap:6px;

    padding:
        7px 10px;

    border-radius:999px;

    font-size:9px;

    font-weight:800;

    white-space:nowrap;
}


.status-ready{

    color:#4ade80;

    background:
        rgba(34,197,94,.10);

    border:
        1px solid
        rgba(34,197,94,.16);
}


.status-out{

    color:#f87171;

    background:
        rgba(239,68,68,.10);

    border:
        1px solid
        rgba(239,68,68,.16);
}


.status-restocking{

    color:#fbbf24;

    background:
        rgba(245,158,11,.10);

    border:
        1px solid
        rgba(245,158,11,.16);
}


.status-restocking i{

    animation:
        spin 1.4s linear infinite;
}


@keyframes spin{

    to{
        transform:rotate(360deg);
    }
}


/* =========================================================
   FLASH
========================================================= */

.flash-badge{

    display:inline-flex;

    align-items:center;

    gap:5px;

    margin-top:5px;

    padding:
        5px 8px;

    border-radius:999px;

    color:#fbbf24;

    background:
        rgba(245,158,11,.08);

    border:
        1px solid
        rgba(245,158,11,.13);

    font-size:8px;

    font-weight:800;
}


/* =========================================================
   ACTION
========================================================= */

.action-group{

    display:flex;

    gap:6px;
}


.action-btn{

    width:34px;
    height:34px;

    display:inline-flex;

    align-items:center;
    justify-content:center;

    border-radius:9px;

    color:#cbd5e1;

    background:
        #0d1728;

    border:
        1px solid
        rgba(148,163,184,.10);

    text-decoration:none;

    cursor:pointer;

    transition:.2s ease;
}


.action-btn:hover{

    color:#fff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #0891b2
        );

    border-color:
        rgba(96,165,250,.25);

    box-shadow:
        0 8px 20px
        rgba(37,99,235,.25);

    transform:
        translateY(-1px);
}


/* =========================================================
   EMPTY
========================================================= */

.empty-state{

    min-height:220px;

    display:flex;

    flex-direction:column;

    align-items:center;

    justify-content:center;

    text-align:center;

    color:#64748b;

    gap:8px;
}


.empty-state i{

    font-size:32px;

    color:#334155;
}


.empty-state strong{

    color:#e2e8f0;

    font-family:
        "Space Grotesk",
        sans-serif;
}


.empty-state span{

    color:#64748b;

    font-size:11px;
}


/* =========================================================
   MODAL
========================================================= */

.modal{

    position:fixed;

    inset:0;

    z-index:5000;

    display:none;

    align-items:center;

    justify-content:center;

    padding:20px;

    background:
        rgba(0,0,0,.72);

    backdrop-filter:
        blur(12px);
}


.modal.active{

    display:flex;

    animation:
        fadeIn .2s ease;
}


@keyframes fadeIn{

    from{
        opacity:0;
    }

    to{
        opacity:1;
    }
}


.modal-card{

    width:
        min(
            760px,
            100%
        );

    max-height:90vh;

    overflow:auto;

    background:
        linear-gradient(
            145deg,
            #0b1627,
            #030914
        );

    border:
        1px solid
        rgba(96,165,250,.13);

    border-radius:22px;

    box-shadow:
        0 35px 100px
        rgba(0,0,0,.6),

        0 0 50px
        rgba(37,99,235,.07);

    animation:
        modalUp .25s ease;
}


@keyframes modalUp{

    from{

        opacity:0;

        transform:
            translateY(20px)
            scale(.98);
    }

    to{

        opacity:1;

        transform:none;
    }
}


.modal-head{

    position:sticky;

    top:0;

    z-index:2;

    display:flex;

    align-items:center;

    justify-content:space-between;

    padding:
        20px 22px;

    background:
        rgba(8,17,31,.94);

    backdrop-filter:
        blur(18px);

    border-bottom:
        1px solid
        rgba(148,163,184,.08);
}


.modal-head h3{

    margin:0;

    color:#fff;

    font-family:
        "Space Grotesk",
        sans-serif;
}


.modal-close{

    width:36px;
    height:36px;

    border:0;

    border-radius:10px;

    color:#94a3b8;

    background:
        rgba(255,255,255,.05);

    cursor:pointer;

    transition:.2s;
}


.modal-close:hover{

    color:#fff;

    background:
        #2563eb;
}


/* =========================================================
   DETAIL
========================================================= */

.modal-body{

    padding:22px;
}


.detail-product{

    display:flex;

    align-items:center;

    gap:15px;

    padding-bottom:20px;

    margin-bottom:20px;

    border-bottom:
        1px solid
        rgba(148,163,184,.08);
}


.detail-image{

    width:80px;
    height:80px;

    flex-shrink:0;

    border-radius:14px;

    object-fit:cover;

    background:#0f1b2e;

    border:
        1px solid
        rgba(96,165,250,.12);
}


.detail-title{

    color:#fff;

    font-family:
        "Space Grotesk",
        sans-serif;

    font-size:20px;

    font-weight:800;
}


.detail-category{

    margin-top:4px;

    color:#64748b;

    font-size:12px;
}


.spec-grid{

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:10px;
}


.spec-item{

    padding:14px;

    border-radius:12px;

    background:
        rgba(255,255,255,.025);

    border:
        1px solid
        rgba(148,163,184,.08);
}


.spec-item small{

    display:block;

    color:#64748b;

    font-size:10px;

    margin-bottom:6px;
}


.spec-item small i{

    color:#60a5fa;

    margin-right:5px;
}


.spec-item strong{

    display:block;

    color:#e2e8f0;

    font-size:12px;

    word-break:break-word;
}


.detail-price{

    margin-top:15px;

    padding:16px;

    border-radius:13px;

    background:
        linear-gradient(
            135deg,
            rgba(37,99,235,.13),
            rgba(6,182,212,.07)
        );

    border:
        1px solid
        rgba(96,165,250,.13);
}


.detail-price small{

    display:block;

    color:#60a5fa;

    font-size:9px;

    font-weight:800;

    letter-spacing:.7px;

    margin-bottom:5px;
}


.detail-price strong{

    color:#fff;

    font-family:
        "Space Grotesk",
        sans-serif;

    font-size:21px;
}


.detail-description{

    margin-top:15px;

    padding:15px;

    border-radius:12px;

    background:
        rgba(255,255,255,.025);

    border:
        1px solid
        rgba(148,163,184,.08);
}


.detail-description small{

    display:block;

    color:#64748b;

    font-size:9px;

    font-weight:800;

    letter-spacing:.6px;

    margin-bottom:7px;
}


.detail-description p{

    margin:0;

    color:#94a3b8;

    font-size:12px;

    line-height:1.7;

    white-space:pre-line;
}


/* =========================================================
   TOAST
========================================================= */

.toast{

    position:fixed;

    right:22px;

    bottom:22px;

    z-index:9000;

    width:
        min(
            390px,
            calc(100vw - 30px)
        );

    display:flex;

    align-items:flex-start;

    gap:12px;

    padding:14px 15px;

    border-radius:14px;

    color:#fff;

    background:
        #07111f;

    border:
        1px solid
        rgba(96,165,250,.16);

    box-shadow:
        0 20px 60px
        rgba(0,0,0,.5);

    animation:
        toastIn .35s ease forwards;
}


.toast.hide{

    animation:
        toastOut .3s ease forwards;
}


.toast-icon{

    width:34px;
    height:34px;

    display:flex;

    align-items:center;
    justify-content:center;

    flex-shrink:0;

    border-radius:9px;

    background:
        rgba(255,255,255,.06);
}


.toast-content{
    flex:1;
}


.toast-title{

    margin-bottom:3px;

    color:#fff;

    font-size:11px;

    font-weight:800;
}


.toast-message{

    color:#94a3b8;

    font-size:10px;

    line-height:1.5;
}


.toast-close{

    border:0;

    background:transparent;

    color:#64748b;

    cursor:pointer;
}


.toast-success .toast-icon{
    color:#4ade80;
}


.toast-error .toast-icon{
    color:#f87171;
}


.toast-warning .toast-icon{
    color:#fbbf24;
}


.toast-info .toast-icon{
    color:#60a5fa;
}


@keyframes toastIn{

    from{

        opacity:0;

        transform:
            translateY(15px)
            scale(.97);
    }

    to{

        opacity:1;

        transform:none;
    }
}


@keyframes toastOut{

    from{

        opacity:1;

        transform:none;
    }

    to{

        opacity:0;

        transform:
            translateY(15px)
            scale(.97);
    }
}


/* =========================================================
   SCROLLBAR
========================================================= */

::-webkit-scrollbar{
    width:8px;
    height:8px;
}


::-webkit-scrollbar-track{
    background:#020617;
}


::-webkit-scrollbar-thumb{

    background:
        #1e3a5f;

    border-radius:10px;
}


::-webkit-scrollbar-thumb:hover{
    background:#2563eb;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1100px){

    .stats{
        grid-template-columns:
            repeat(3,1fr);
    }
}


@media(max-width:800px){

    .navlinks{
        display:none;
    }

    .stats{
        grid-template-columns:
            repeat(2,1fr);
    }

    .title-row{
        align-items:flex-start;
    }

    .spec-grid{
        grid-template-columns:1fr;
    }
}


@media(max-width:550px){

    .container{
        width:
            calc(100% - 20px);
    }

    .section{
        padding-top:22px;
    }

    .title-row{

        flex-direction:column;

        align-items:stretch;
    }

    .title-row .btn{
        width:100%;
    }

    .title-row h2{
        font-size:24px;
    }

    .stats{

        grid-template-columns:
            repeat(2,1fr);

        gap:9px;
    }

    .stat{
        padding:15px;
    }

    .stat b{
        font-size:21px;
    }

    .panel{
        padding:14px;
    }

    .panel-head{

        align-items:flex-start;

        flex-direction:column;
    }

    .modal{
        padding:10px;
    }

    .modal-card{
        border-radius:17px;
    }

    .toast{

        right:15px;

        bottom:15px;
    }
}

</style>

</head>


<body>


<!-- =====================================================
     TOPBAR
===================================================== -->

<header class="topbar">

<div class="container nav">


<a
    class="brand"
    href="dashboard.php">

    <div class="logo">

        <i class="fa-solid fa-microchip"></i>

    </div>

    <div>

        <strong>
            <?=h($storeName)?>
        </strong>

        <small>
            ADMIN DASHBOARD
        </small>

    </div>

</a>


<nav class="navlinks">


<a
    href="dashboard.php"
    class="active">

    <i class="fa-solid fa-gauge-high"></i>

    Dashboard

</a>


<a href="index.php">

    <i class="fa-solid fa-store"></i>

    Lihat Toko

</a>


<a href="edit.php">

    <i class="fa-solid fa-box"></i>

    Produk

</a>


<?php if(
    in_array(
        $currentUser['role'] ?? '',
        ['verified_admin','super_admin'],
        true
    )
): ?>

<a href="setting.php">

    <i class="fa-solid fa-sliders"></i>

    Setting

</a>

<?php endif; ?>


</nav>


<a
    class="btn btn-dark"
    href="logout.php">

    <i class="fa-solid fa-right-from-bracket"></i>

    Logout

</a>


</div>

</header>


<!-- =====================================================
     MAIN
===================================================== -->

<section class="section">

<div class="container">


<div class="title-row">

<div>

<h2>
DASHBOARD
</h2>

<div class="dashboard-user">

<span class="user-dot"></span>

<span>

<?=h(
    $currentUser['name']
    ?? 'Administrator'
)?>

&nbsp;•&nbsp;

<?=h(
    badge_role(
        $currentUser['role']
        ?? 'admin'
    )
)?>

<?php if(
    !empty(
        $currentUser['verified']
    )
): ?>

&nbsp;•&nbsp; Verified

<?php endif; ?>

</span>

</div>

</div>


<a
    class="btn btn-primary"
    href="edit.php">

    <i class="fa-solid fa-plus"></i>

    Tambah Produk

</a>

</div>


<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="stats">


<div class="stat">

<i class="fa-solid fa-box"></i>

<b>
<?=count($products)?>
</b>

<span>
Total Produk
</span>

</div>


<div class="stat stat-ready">

<i class="fa-solid fa-circle-check"></i>

<b>
<?=$readyCount?>
</b>

<span>
Ready Stock
</span>

</div>


<div class="stat stat-out">

<i class="fa-solid fa-box-open"></i>

<b>
<?=$outCount?>
</b>

<span>
Out of Stock
</span>

</div>


<div class="stat stat-restock">

<i class="fa-solid fa-arrows-rotate"></i>

<b>
<?=$restockingCount?>
</b>

<span>
Restocking
</span>

</div>


<div class="stat stat-flash">

<i class="fa-solid fa-bolt"></i>

<b>
<?=$flashSaleCount?>
</b>

<span>
Flash Sale
</span>

</div>


</div>


<!-- =====================================================
     INVENTORY
===================================================== -->

<div class="panel">


<div class="panel-head">

<h3>
Product Inventory
</h3>

<span class="product-count">

<?=count($products)?>
produk terdaftar

</span>

</div>


<?php if(!$products): ?>


<div class="empty-state">

<i class="fa-solid fa-box-open"></i>

<strong>
Belum ada produk
</strong>

<span>
Tambahkan produk pertama melalui menu Produk.
</span>

<a
    class="btn btn-primary"
    href="edit.php">

    <i class="fa-solid fa-plus"></i>

    Tambah Produk

</a>

</div>


<?php else: ?>


<div class="table-wrap">


<table class="table">


<thead>

<tr>

<th>Produk</th>

<th>Spesifikasi</th>

<th>Harga</th>

<th>Status</th>

<th>Aksi</th>

</tr>

</thead>


<tbody>


<?php foreach($products as $p): ?>


<?php

$status = getProductStatus($p);

$flash = isFlashSale($p);

$image = trim(
    (string)(
        $p['image']
        ?? ''
    )
);

?>


<tr>


<td>

<div class="product-name">


<?php if($image): ?>

<img
    class="product-thumb"
    src="<?=h($image)?>"
    alt="<?=h(
        $p['name']
        ?? 'Product'
    )?>"
    onerror="
        this.style.display='none';
        this.nextElementSibling.style.display='flex';
    ">


<div
    class="product-thumb-fallback"
    style="display:none">

    <i class="fa-solid fa-image"></i>

</div>


<?php else: ?>


<div class="product-thumb-fallback">

    <i class="fa-solid fa-image"></i>

</div>


<?php endif; ?>


<div>

<div class="product-title">

<?=h(
    $p['name']
    ?? 'Unnamed Product'
)?>

</div>

<div class="product-category">

<?=h(
    $p['category']
    ?? 'Uncategorized'
)?>

</div>

</div>


</div>

</td>


<td>

<div class="spec-mini">


<?php if(!empty($p['brand'])): ?>

<span class="spec-chip">

<i class="fa-solid fa-tag"></i>

<?=h($p['brand'])?>

</span>

<?php endif; ?>


<?php if(!empty($p['cpu'])): ?>

<span class="spec-chip">

<i class="fa-solid fa-microchip"></i>

<?=h($p['cpu'])?>

</span>

<?php endif; ?>


<?php if(!empty($p['ram'])): ?>

<span class="spec-chip">

<i class="fa-solid fa-memory"></i>

<?=h($p['ram'])?>

</span>

<?php endif; ?>


<?php if(!empty($p['ssd'])): ?>

<span class="spec-chip">

<i class="fa-solid fa-hard-drive"></i>

<?=h($p['ssd'])?>

</span>

<?php endif; ?>


<?php if(!empty($p['vga'])): ?>

<span class="spec-chip">

<i class="fa-solid fa-display"></i>

<?=h($p['vga'])?>

</span>

<?php endif; ?>


<?php if(!empty($p['display'])): ?>

<span class="spec-chip">

<i class="fa-solid fa-desktop"></i>

<?=h($p['display'])?>

</span>

<?php endif; ?>


<?php if(
    empty($p['brand']) &&
    empty($p['cpu']) &&
    empty($p['ram']) &&
    empty($p['ssd']) &&
    empty($p['vga']) &&
    empty($p['display'])
): ?>

<span style="
    color:#475569;
    font-size:10px;
">

    Tidak ada spesifikasi

</span>

<?php endif; ?>


</div>

</td>


<td>

<strong
    style="
        color:#f8fafc;
        white-space:nowrap;
    ">

<?=money(
    $p['price']
    ?? 0
)?>

</strong>

</td>


<td>

<span
    class="
        status-badge
        status-<?=$status['key']?>
    ">

<i
    class="
        fa-solid
        <?=$status['icon']?>
    "></i>

<?=h(
    $status['label']
)?>

</span>


<?php if($flash): ?>

<div>

<span class="flash-badge">

<i class="fa-solid fa-bolt"></i>

FLASH SALE

</span>

</div>

<?php endif; ?>


</td>


<td>

<div class="action-group">


<button
    class="action-btn"
    type="button"
    title="Lihat"
    onclick='showProduct(<?=json_encode(
        $p,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_HEX_AMP
    )?>)'>

<i class="fa-solid fa-eye"></i>

</button>


<a
    class="action-btn"
    href="edit.php?id=<?=h(
        (string)(
            $p['id']
            ?? ''
        )
    )?>"
    title="Edit">

<i class="fa-solid fa-pen"></i>

</a>


</div>

</td>


</tr>


<?php endforeach; ?>


</tbody>

</table>

</div>


<?php endif; ?>


</div>

</div>

</section>


<!-- =====================================================
     MODAL
===================================================== -->

<div
    class="modal"
    id="productModal"
    onclick="closeOutside(event)">


<div class="modal-card">


<div class="modal-head">

<h3>
Product Specification
</h3>


<button
    class="modal-close"
    type="button"
    onclick="closeProduct()">

<i class="fa-solid fa-xmark"></i>

</button>

</div>


<div
    class="modal-body"
    id="productDetail">
</div>


</div>

</div>


<!-- =====================================================
     TOAST
===================================================== -->

<?php if($toastMessage): ?>


<div
    class="toast toast-<?=h($toastType)?>"
    id="toast">


<div class="toast-icon">

<?php if($toastType === 'success'): ?>

<i class="fa-solid fa-check"></i>

<?php elseif($toastType === 'error'): ?>

<i class="fa-solid fa-xmark"></i>

<?php elseif($toastType === 'warning'): ?>

<i class="fa-solid fa-triangle-exclamation"></i>

<?php else: ?>

<i class="fa-solid fa-circle-info"></i>

<?php endif; ?>

</div>


<div class="toast-content">

<div class="toast-title">

<?php

echo match($toastType){

    'success' => 'Berhasil',

    'error' => 'Terjadi Kesalahan',

    'warning' => 'Perhatian',

    default => 'Informasi'

};

?>

</div>


<div class="toast-message">

<?=h($toastMessage)?>

</div>

</div>


<button
    class="toast-close"
    type="button"
    onclick="closeToast()">

<i class="fa-solid fa-xmark"></i>

</button>


</div>


<?php endif; ?>


<script>

/* =========================================================
   ESCAPE
========================================================= */

function esc(value){

    if(
        value === null ||
        value === undefined ||
        value === ''
    ){
        return '-';
    }

    const div =
        document.createElement('div');

    div.textContent =
        String(value);

    return div.innerHTML;
}


/* =========================================================
   PRODUCT MODAL
========================================================= */

function showProduct(product){

    const modal =
        document.getElementById(
            'productModal'
        );

    const detail =
        document.getElementById(
            'productDetail'
        );


    let statusLabel =
        'READY STOCK';

    let statusClass =
        'status-ready';

    let statusIcon =
        'fa-circle-check';


    const rawStatus =
        String(
            product.status || ''
        ).toUpperCase();


    const rawStock =
        String(
            product.stock ?? ''
        ).toUpperCase();


    if(rawStatus === 'RESTOCKING'){

        statusLabel =
            'RESTOCKING';

        statusClass =
            'status-restocking';

        statusIcon =
            'fa-arrows-rotate';

    }

    else if(

        rawStock === '0' ||

        rawStock === 'HABIS' ||

        rawStock === 'KOSONG' ||

        rawStock === 'OUT OF STOCK'

    ){

        statusLabel =
            'OUT OF STOCK';

        statusClass =
            'status-out';

        statusIcon =
            'fa-box-open';
    }


    let imageHtml = '';


    if(product.image){

        imageHtml = `

            <img
                class="detail-image"
                src="${esc(product.image)}"
                alt=""
                onerror="
                    this.style.display='none';
                ">

        `;

    }else{

        imageHtml = `

            <div
                class="detail-image"
                style="
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    color:#475569;
                    background:#0f1b2e;
                ">

                <i
                    class="
                        fa-solid
                        fa-image
                    ">
                </i>

            </div>

        `;
    }


    const fields = [

        ['Brand','fa-tag',product.brand],

        ['Model','fa-barcode',product.model],

        ['Processor','fa-microchip',product.cpu],

        ['RAM','fa-memory',product.ram],

        ['Storage','fa-hard-drive',product.ssd],

        ['Graphics','fa-display',product.vga],

        ['Display','fa-desktop',product.display],

        ['Operating System','fa-windows',product.os],

        ['Condition','fa-box',product.condition],

        ['Warranty','fa-shield-halved',product.warranty],

        ['Weight','fa-weight-hanging',product.weight],

        ['Stock','fa-boxes-stacked',product.stock]

    ];


    let specs = '';


    fields.forEach(
        field => {

            if(
                field[2] !== undefined &&
                field[2] !== null &&
                String(field[2]).trim() !== ''
            ){

                specs += `

                    <div class="spec-item">

                        <small>

                            <i
                                class="
                                    fa-solid
                                    ${field[1]}
                                ">
                            </i>

                            ${esc(field[0])}

                        </small>

                        <strong>

                            ${esc(field[2])}

                        </strong>

                    </div>

                `;
            }

        }
    );


    const flash =

        product.flash_sale === true ||

        product.flash_sale === 1 ||

        product.flash_sale === '1' ||

        String(
            product.flash_sale
        ).toUpperCase() === 'TRUE';


    const price =
        Number(
            product.price || 0
        );


    const priceFormatted =
        new Intl.NumberFormat(
            'id-ID',
            {
                style:'currency',
                currency:'IDR',
                maximumFractionDigits:0
            }
        ).format(price);


    detail.innerHTML = `

        <div class="detail-product">

            ${imageHtml}

            <div>

                <div class="detail-title">

                    ${esc(
                        product.name ||
                        'Unnamed Product'
                    )}

                </div>

                <div class="detail-category">

                    ${esc(
                        product.category ||
                        'Uncategorized'
                    )}

                </div>

                <div
                    style="
                        margin-top:9px;
                    ">

                    <span
                        class="
                            status-badge
                            ${statusClass}
                        ">

                        <i
                            class="
                                fa-solid
                                ${statusIcon}
                            ">
                        </i>

                        ${esc(statusLabel)}

                    </span>

                    ${
                        flash
                        ? `

                            <span
                                class="
                                    flash-badge
                                ">

                                <i
                                    class="
                                        fa-solid
                                        fa-bolt
                                    ">
                                </i>

                                FLASH SALE

                            </span>

                        `
                        : ''
                    }

                </div>

            </div>

        </div>


        <div class="spec-grid">

            ${
                specs ||

                `

                <div class="spec-item">

                    <small>
                        Informasi
                    </small>

                    <strong>
                        Belum ada spesifikasi
                    </strong>

                </div>

                `
            }

        </div>


        <div class="detail-price">

            <small>
                HARGA JUAL
            </small>

            <strong>
                ${esc(priceFormatted)}
            </strong>

        </div>


        ${
            product.desc

            ? `

                <div class="detail-description">

                    <small>
                        DESCRIPTION
                    </small>

                    <p>
                        ${esc(product.desc)}
                    </p>

                </div>

            `
            : ''
        }

    `;


    modal.classList.add(
        'active'
    );

    document.body.style.overflow =
        'hidden';
}


/* =========================================================
   CLOSE MODAL
========================================================= */

function closeProduct(){

    const modal =
        document.getElementById(
            'productModal'
        );

    if(modal){

        modal.classList.remove(
            'active'
        );
    }

    document.body.style.overflow =
        '';
}


function closeOutside(event){

    if(
        event.target.id ===
        'productModal'
    ){

        closeProduct();
    }
}


document.addEventListener(
    'keydown',
    function(event){

        if(
            event.key ===
            'Escape'
        ){

            closeProduct();
        }

    }
);


/* =========================================================
   TOAST
========================================================= */

function closeToast(){

    const toast =
        document.getElementById(
            'toast'
        );

    if(!toast){
        return;
    }

    toast.classList.add(
        'hide'
    );

    setTimeout(
        function(){
            toast.remove();
        },
        300
    );
}


<?php if($toastMessage): ?>

setTimeout(
    closeToast,
    4000
);

<?php endif; ?>


/* =========================================================
   CLEAN URL
========================================================= */

if(
    window.history &&
    window.history.replaceState
){

    const url =
        new URL(
            window.location.href
        );

    if(
        url.searchParams.has('msg')
    ){

        url.searchParams.delete(
            'msg'
        );

        url.searchParams.delete(
            'type'
        );

        window.history.replaceState(
            {},
            document.title,
            url.pathname +
            (
                url.search
                ? url.search
                : ''
            )
        );
    }
}

</script>

</body>
</html>