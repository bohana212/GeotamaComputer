<?php
declare(strict_types=1);

require __DIR__ . '/common.php';
require_role(['verified_admin', 'super_admin']);

$products = read_json(PRODUCTS_FILE, []);

$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$editProduct = null;

foreach ($products as $p) {
    if ((int)($p['id'] ?? 0) === $id) {
        $editProduct = $p;
        break;
    }
}

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

/*
|--------------------------------------------------------------------------
| CATEGORY SPECIFICATION CONFIG
|--------------------------------------------------------------------------
*/

function getCategorySpecs($product) {

    $category = strtolower(
        trim((string)($product['category'] ?? ''))
    );

    $specs = [];

    if ($category === 'laptop') {

        $specs = [
            ['key'=>'cpu','label'=>'Processor','icon'=>'fa-solid fa-microchip'],
            ['key'=>'ram','label'=>'RAM','icon'=>'fa-solid fa-memory'],
            ['key'=>'ssd','label'=>'Storage','icon'=>'fa-solid fa-hard-drive'],
            ['key'=>'display','label'=>'Display','icon'=>'fa-solid fa-display'],
            ['key'=>'os','label'=>'OS','icon'=>'fa-brands fa-windows'],
            ['key'=>'gpu','label'=>'GPU','icon'=>'fa-solid fa-gamepad']
        ];

    } elseif ($category === 'pc fullset') {

        $specs = [
            ['key'=>'cpu','label'=>'Processor','icon'=>'fa-solid fa-microchip'],
            ['key'=>'ram','label'=>'RAM','icon'=>'fa-solid fa-memory'],
            ['key'=>'ssd','label'=>'Storage','icon'=>'fa-solid fa-hard-drive'],
            ['key'=>'vga','label'=>'VGA','icon'=>'fa-solid fa-gamepad'],
            ['key'=>'monitor','label'=>'Monitor','icon'=>'fa-solid fa-display'],
            ['key'=>'psu','label'=>'PSU','icon'=>'fa-solid fa-bolt']
        ];

    } elseif ($category === 'cpu only') {

        $specs = [
            ['key'=>'socket','label'=>'Socket','icon'=>'fa-solid fa-microchip'],
            ['key'=>'cores','label'=>'Core','icon'=>'fa-solid fa-layer-group'],
            ['key'=>'threads','label'=>'Thread','icon'=>'fa-solid fa-diagram-project'],
            ['key'=>'base_clock','label'=>'Base Clock','icon'=>'fa-solid fa-gauge-high'],
            ['key'=>'boost_clock','label'=>'Boost Clock','icon'=>'fa-solid fa-bolt'],
            ['key'=>'tdp','label'=>'TDP','icon'=>'fa-solid fa-temperature-half']
        ];

    } elseif ($category === 'monitor') {

        $specs = [
            ['key'=>'size','label'=>'Ukuran','icon'=>'fa-solid fa-expand'],
            ['key'=>'panel','label'=>'Panel','icon'=>'fa-solid fa-tv'],
            ['key'=>'resolution','label'=>'Resolusi','icon'=>'fa-solid fa-maximize'],
            ['key'=>'refresh_rate','label'=>'Refresh Rate','icon'=>'fa-solid fa-arrows-rotate'],
            ['key'=>'response_time','label'=>'Response Time','icon'=>'fa-solid fa-stopwatch'],
            ['key'=>'ports','label'=>'Port','icon'=>'fa-solid fa-plug']
        ];

    } elseif ($category === 'printer') {

        $specs = [
            ['key'=>'brand','label'=>'Merk','icon'=>'fa-solid fa-copyright'],
            ['key'=>'printer_type','label'=>'Tipe','icon'=>'fa-solid fa-print'],
            ['key'=>'print_technology','label'=>'Teknologi','icon'=>'fa-solid fa-gears'],
            ['key'=>'resolution','label'=>'Resolusi','icon'=>'fa-solid fa-maximize'],
            ['key'=>'print_speed','label'=>'Kecepatan','icon'=>'fa-solid fa-gauge-high'],
            ['key'=>'connectivity','label'=>'Koneksi','icon'=>'fa-solid fa-wifi']
        ];

    } elseif ($category === 'tinta') {

        $specs = [
            ['key'=>'ink_type','label'=>'Tipe','icon'=>'fa-solid fa-droplet'],
            ['key'=>'color','label'=>'Warna','icon'=>'fa-solid fa-palette'],
            ['key'=>'capacity','label'=>'Kapasitas','icon'=>'fa-solid fa-flask'],
            ['key'=>'compatibility','label'=>'Kompatibilitas','icon'=>'fa-solid fa-link'],
            ['key'=>'type_code','label'=>'Kode','icon'=>'fa-solid fa-barcode'],
            ['key'=>'condition','label'=>'Kondisi','icon'=>'fa-solid fa-circle-check']
        ];

    } elseif ($category === 'networking') {

        $specs = [
            ['key'=>'network_type','label'=>'Tipe','icon'=>'fa-solid fa-network-wired'],
            ['key'=>'speed','label'=>'Speed','icon'=>'fa-solid fa-gauge-high'],
            ['key'=>'ports','label'=>'Port','icon'=>'fa-solid fa-ethernet'],
            ['key'=>'wifi','label'=>'Wi-Fi','icon'=>'fa-solid fa-wifi'],
            ['key'=>'antenna','label'=>'Antenna','icon'=>'fa-solid fa-satellite-dish'],
            ['key'=>'range','label'=>'Coverage','icon'=>'fa-solid fa-tower-broadcast']
        ];

    } elseif ($category === 'cctv') {

        $specs = [
            ['key'=>'resolution','label'=>'Resolusi','icon'=>'fa-solid fa-camera'],
            ['key'=>'lens','label'=>'Lens','icon'=>'fa-solid fa-eye'],
            ['key'=>'night_vision','label'=>'Night Vision','icon'=>'fa-solid fa-moon'],
            ['key'=>'storage','label'=>'Storage','icon'=>'fa-solid fa-hard-drive'],
            ['key'=>'connectivity','label'=>'Koneksi','icon'=>'fa-solid fa-wifi'],
            ['key'=>'weatherproof','label'=>'Protection','icon'=>'fa-solid fa-cloud-rain']
        ];

    } elseif (
        $category === 'aksesoris pc/laptop' ||
        $category === 'aksesoris'
    ) {

        $specs = [
            ['key'=>'accessory_type','label'=>'Tipe','icon'=>'fa-solid fa-toolbox'],
            ['key'=>'connectivity','label'=>'Koneksi','icon'=>'fa-solid fa-plug'],
            ['key'=>'compatibility','label'=>'Kompatibilitas','icon'=>'fa-solid fa-laptop'],
            ['key'=>'interface','label'=>'Interface','icon'=>'fa-solid fa-usb'],
            ['key'=>'material','label'=>'Material','icon'=>'fa-solid fa-cubes'],
            ['key'=>'warranty','label'=>'Garansi','icon'=>'fa-solid fa-shield-halved']
        ];

    } else {

        $specs = [
            ['key'=>'brand','label'=>'Merk','icon'=>'fa-solid fa-copyright'],
            ['key'=>'model','label'=>'Model','icon'=>'fa-solid fa-tag'],
            ['key'=>'condition','label'=>'Kondisi','icon'=>'fa-solid fa-circle-check'],
            ['key'=>'warranty','label'=>'Garansi','icon'=>'fa-solid fa-shield-halved'],
            ['key'=>'weight','label'=>'Berat','icon'=>'fa-solid fa-weight-hanging'],
            ['key'=>'connectivity','label'=>'Koneksi','icon'=>'fa-solid fa-plug']
        ];
    }

    return $specs;
}


/*
|--------------------------------------------------------------------------
| DELETE PRODUCT
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    check_csrf();

    $postAction = $_POST['form_action'] ?? '';

    if ($postAction === 'delete') {

        $deleteId = (int)($_POST['id'] ?? 0);

        $newProducts = [];

        foreach ($products as $product) {

            if ((int)($product['id'] ?? 0) !== $deleteId) {
                $newProducts[] = $product;
            }
        }

        write_json(PRODUCTS_FILE, $newProducts);

        header('Location: edit.php?deleted=1');
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | SAVE PRODUCT
    |--------------------------------------------------------------------------
    */

    if ($postAction === 'save') {

        $productId = (int)($_POST['id'] ?? 0);

        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');

        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);

        $description = trim($_POST['description'] ?? '');
        $image = trim($_POST['image'] ?? '');

        if ($name === '' || $category === '') {
            header('Location: edit.php?action=' . ($productId ? 'edit&id='.$productId : 'add') . '&error=required');
            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | BASIC DATA
        |--------------------------------------------------------------------------
        */

        $data = [
            'name'        => $name,
            'category'    => $category,
            'price'       => $price,
            'stock'       => $stock,
            'description' => $description,
            'image'       => $image
        ];


        /*
        |--------------------------------------------------------------------------
        | CATEGORY SPECIFICATIONS
        |--------------------------------------------------------------------------
        */

        $tempProduct = [
            'category' => $category
        ];

        $categorySpecs = getCategorySpecs($tempProduct);

        foreach ($categorySpecs as $spec) {

            $key = $spec['key'];

            $data[$key] = trim(
                $_POST['spec'][$key] ?? ''
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SAVE / UPDATE
        |--------------------------------------------------------------------------
        */

        if ($productId > 0) {

            $found = false;

            foreach ($products as &$product) {

                if ((int)($product['id'] ?? 0) === $productId) {

                    $data['id'] = $productId;

                    $product = array_merge(
                        $product,
                        $data
                    );

                    $found = true;
                    break;
                }
            }

            unset($product);

            if (!$found) {
                header('Location: edit.php?error=notfound');
                exit;
            }

        } else {

            $maxId = 0;

            foreach ($products as $product) {
                $maxId = max(
                    $maxId,
                    (int)($product['id'] ?? 0)
                );
            }

            $data['id'] = $maxId + 1;

            $products[] = $data;
        }


        write_json(
            PRODUCTS_FILE,
            array_values($products)
        );

        header('Location: edit.php?saved=1');
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| REFRESH DATA
|--------------------------------------------------------------------------
*/

$products = read_json(PRODUCTS_FILE, []);


/*
|--------------------------------------------------------------------------
| FORM DATA
|--------------------------------------------------------------------------
*/

$formProduct = [
    'id' => '',
    'name' => '',
    'category' => '',
    'price' => '',
    'stock' => '',
    'description' => '',
    'image' => ''
];

if ($editProduct) {

    $formProduct = array_merge(
        $formProduct,
        $editProduct
    );
}

?>
<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
<?= $action === 'add'
    ? 'Tambah Produk'
    : ($action === 'edit' ? 'Edit Produk' : 'Kelola Produk')
?>
 — GEOTAMA COMPUTER
</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link
href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap"
rel="stylesheet"
>

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>

<style>

*{
    box-sizing:border-box;
}

html{
    scroll-behavior:smooth;
}

body{
    margin:0;
    min-height:100vh;
    background:
        radial-gradient(
            circle at 10% 0%,
            rgba(0,123,255,.15),
            transparent 35%
        ),
        radial-gradient(
            circle at 90% 10%,
            rgba(0,210,255,.08),
            transparent 30%
        ),
        #050912;

    color:#e8f1ff;

    font-family:
        Inter,
        system-ui,
        sans-serif;
}

button,
input,
select,
textarea{
    font:inherit;
}

a{
    color:inherit;
    text-decoration:none;
}


/* =========================================================
   TOPBAR
========================================================= */

.topbar{

    position:sticky;
    top:0;
    z-index:100;

    height:70px;

    display:flex;
    align-items:center;
    justify-content:space-between;

    padding:0 28px;

    background:rgba(5,9,18,.88);

    border-bottom:
        1px solid
        rgba(255,255,255,.07);

    backdrop-filter:blur(18px);
}

.brand{

    display:flex;
    align-items:center;
    gap:12px;

    font-family:
        "Space Grotesk",
        sans-serif;

    font-weight:800;

    letter-spacing:-.4px;
}

.brand-icon{

    width:38px;
    height:38px;

    display:grid;
    place-items:center;

    border-radius:11px;

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #00b7ff
        );

    color:white;

    box-shadow:
        0 8px 25px
        rgba(0,140,255,.25);
}

.brand small{

    display:block;

    margin-top:2px;

    font-size:10px;

    color:#71809a;

    font-family:Inter,sans-serif;

    font-weight:500;
}

.nav{

    display:flex;
    align-items:center;
    gap:7px;
}

.nav a{

    padding:9px 12px;

    border-radius:9px;

    color:#8998ae;

    font-size:13px;

    transition:.2s;
}

.nav a:hover,
.nav a.active{

    color:#fff;

    background:
        rgba(0,130,255,.10);
}

.nav a i{
    margin-right:5px;
}

.logout-form{
    margin:0;
}

.logout-btn{

    border:0;

    padding:9px 12px;

    border-radius:9px;

    color:#ff9b9b;

    background:
        rgba(255,70,70,.08);

    cursor:pointer;

    transition:.2s;
}

.logout-btn:hover{

    background:
        rgba(255,70,70,.15);

    color:#ffb5b5;
}


/* =========================================================
   CONTAINER
========================================================= */

.container{

    width:min(1400px, calc(100% - 32px));

    margin:0 auto;

    padding:32px 0 60px;
}


/* =========================================================
   HEADER
========================================================= */

.page-head{

    display:flex;

    align-items:flex-end;

    justify-content:space-between;

    gap:20px;

    margin-bottom:25px;
}

.page-head h1{

    margin:0;

    font-family:
        "Space Grotesk",
        sans-serif;

    font-size:30px;

    letter-spacing:-1px;
}

.page-head p{

    margin:7px 0 0;

    color:#7d8ba1;

    font-size:13px;
}

.actions{

    display:flex;

    gap:9px;
}

.btn{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    gap:7px;

    padding:10px 14px;

    border:1px solid
        rgba(255,255,255,.08);

    border-radius:10px;

    background:
        rgba(255,255,255,.035);

    color:#d9e5f5;

    cursor:pointer;

    font-size:13px;

    transition:.2s;
}

.btn:hover{

    transform:translateY(-1px);

    background:
        rgba(255,255,255,.07);
}

.btn-primary{

    border-color:
        rgba(0,132,255,.35);

    background:
        linear-gradient(
            135deg,
            #087cff,
            #00a8ff
        );

    color:#fff;

    box-shadow:
        0 8px 25px
        rgba(0,126,255,.18);
}

.btn-danger{

    border-color:
        rgba(255,70,70,.18);

    background:
        rgba(255,60,60,.08);

    color:#ff9999;
}


/* =========================================================
   PANEL
========================================================= */

.panel{

    background:
        linear-gradient(
            145deg,
            rgba(14,23,39,.96),
            rgba(7,13,24,.96)
        );

    border:
        1px solid
        rgba(255,255,255,.065);

    border-radius:17px;

    box-shadow:
        0 20px 60px
        rgba(0,0,0,.22);

    overflow:hidden;
}

.panel-head{

    padding:19px 21px;

    border-bottom:
        1px solid
        rgba(255,255,255,.055);

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:15px;
}

.panel-title{

    display:flex;

    align-items:center;

    gap:10px;

    font-size:14px;

    font-weight:700;
}

.panel-title i{
    color:#4fa9ff;
}

.panel-body{
    padding:21px;
}


/* =========================================================
   TABLE
========================================================= */

.table-wrap{
    overflow-x:auto;
}

table{

    width:100%;

    border-collapse:collapse;

    min-width:850px;
}

th{

    text-align:left;

    padding:13px 15px;

    font-size:10px;

    text-transform:uppercase;

    letter-spacing:.8px;

    color:#65748b;

    background:
        rgba(255,255,255,.025);

    border-bottom:
        1px solid
        rgba(255,255,255,.06);
}

td{

    padding:14px 15px;

    border-bottom:
        1px solid
        rgba(255,255,255,.045);

    font-size:12px;

    color:#b9c6d8;

    vertical-align:middle;
}

tr:hover td{

    background:
        rgba(0,130,255,.025);
}

.product-name{

    display:flex;

    align-items:center;

    gap:11px;

    min-width:230px;
}

.product-thumb{

    width:45px;
    height:45px;

    border-radius:9px;

    overflow:hidden;

    background:#0b1423;

    border:
        1px solid
        rgba(255,255,255,.07);

    flex:none;
}

.product-thumb img{

    width:100%;
    height:100%;

    object-fit:cover;
}

.product-name strong{

    display:block;

    color:#eaf3ff;

    font-size:13px;
}

.product-name small{

    display:block;

    margin-top:4px;

    color:#63728a;

    font-size:10px;
}

.category-badge{

    display:inline-flex;

    padding:5px 8px;

    border-radius:7px;

    background:
        rgba(0,130,255,.08);

    border:
        1px solid
        rgba(0,130,255,.12);

    color:#67b6ff;

    font-size:10px;
}

.price{

    color:#66bdff;

    font-weight:700;

    white-space:nowrap;
}

.stock-ok{
    color:#62d99a;
}

.stock-empty{
    color:#ff8585;
}

.stock-low{
    color:#ffc36b;
}

.row-actions{

    display:flex;

    align-items:center;

    gap:6px;
}

.icon-btn{

    width:34px;
    height:34px;

    display:grid;
    place-items:center;

    border-radius:8px;

    border:
        1px solid
        rgba(255,255,255,.07);

    background:
        rgba(255,255,255,.025);

    color:#8fa0b8;

    cursor:pointer;

    transition:.2s;
}

.icon-btn:hover{

    color:#fff;

    background:
        rgba(0,130,255,.10);
}

.icon-btn.delete:hover{

    color:#ff8d8d;

    background:
        rgba(255,60,60,.10);
}


/* =========================================================
   FORM
========================================================= */

.form-grid{

    display:grid;

    grid-template-columns:
        repeat(2, minmax(0,1fr));

    gap:17px;
}

.field{

    display:flex;

    flex-direction:column;

    gap:7px;
}

.field.full{
    grid-column:1/-1;
}

.field label{

    font-size:11px;

    font-weight:700;

    color:#a7b5c8;
}

.field label span{
    color:#ff7272;
}

.field input,
.field select,
.field textarea{

    width:100%;

    border:
        1px solid
        rgba(255,255,255,.075);

    outline:none;

    border-radius:10px;

    background:
        rgba(255,255,255,.035);

    color:#e9f2ff;

    padding:11px 12px;

    transition:.2s;
}

.field select option{
    background:#0b1321;
    color:#fff;
}

.field textarea{

    min-height:115px;

    resize:vertical;
}

.field input:focus,
.field select:focus,
.field textarea:focus{

    border-color:
        rgba(0,153,255,.55);

    box-shadow:
        0 0 0 3px
        rgba(0,130,255,.08);
}

.field small{

    color:#64748b;

    font-size:10px;

    line-height:1.5;
}


/* =========================================================
   SPECIFICATION
========================================================= */

.spec-section{

    margin-top:23px;

    padding-top:22px;

    border-top:
        1px solid
        rgba(255,255,255,.06);
}

.spec-title{

    display:flex;

    align-items:center;

    gap:9px;

    margin-bottom:16px;

    font-size:14px;

    font-weight:800;
}

.spec-title i{
    color:#3ca6ff;
}

.spec-description{

    color:#66758c;

    font-size:11px;

    margin-top:-9px;

    margin-bottom:17px;
}

.spec-grid{

    display:grid;

    grid-template-columns:
        repeat(2,minmax(0,1fr));

    gap:14px;
}

.spec-field{

    display:flex;

    flex-direction:column;

    gap:7px;
}

.spec-field label{

    display:flex;

    align-items:center;

    gap:7px;

    font-size:11px;

    color:#a8b6c9;

    font-weight:600;
}

.spec-field label i{

    width:17px;

    color:#429eff;

    text-align:center;
}

.spec-field input{

    width:100%;

    padding:10px 11px;

    border-radius:9px;

    border:
        1px solid
        rgba(255,255,255,.065);

    outline:none;

    color:#e8f1ff;

    background:
        rgba(255,255,255,.028);
}

.spec-field input:focus{

    border-color:
        rgba(0,145,255,.5);

    box-shadow:
        0 0 0 3px
        rgba(0,130,255,.07);
}


/* =========================================================
   EMPTY
========================================================= */

.empty{

    padding:50px 20px;

    text-align:center;

    color:#65758d;
}

.empty i{

    display:block;

    font-size:30px;

    margin-bottom:12px;

    color:#2f7fc3;
}


/* =========================================================
   TOAST
========================================================= */

.toast{

    position:fixed;

    right:20px;
    bottom:20px;

    z-index:9999;

    min-width:280px;

    max-width:calc(100vw - 40px);

    padding:13px 15px;

    display:flex;

    align-items:center;

    gap:10px;

    border:
        1px solid
        rgba(70,190,255,.2);

    border-radius:11px;

    background:
        rgba(8,18,32,.96);

    box-shadow:
        0 20px 50px
        rgba(0,0,0,.35);

    color:#dceaff;

    font-size:12px;

    animation:
        toastIn .25s ease;
}

.toast i{
    color:#4fd0ff;
}

@keyframes toastIn{

    from{
        opacity:0;
        transform:translateY(12px);
    }

    to{
        opacity:1;
        transform:none;
    }
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:850px){

    .topbar{
        padding:0 15px;
    }

    .nav a span{
        display:none;
    }

    .nav a{
        padding:9px;
    }

    .container{
        width:min(100% - 20px, 1400px);
        padding-top:22px;
    }

    .page-head{
        align-items:flex-start;
        flex-direction:column;
    }

    .form-grid,
    .spec-grid{
        grid-template-columns:1fr;
    }

    .field.full{
        grid-column:auto;
    }
}

@media(max-width:520px){

    .brand-text{
        display:none;
    }

    .page-head h1{
        font-size:25px;
    }

    .actions{
        width:100%;
    }

    .actions .btn{
        flex:1;
    }

    .panel-body{
        padding:15px;
    }

    .toast{
        left:15px;
        right:15px;
        min-width:0;
    }
}

</style>

</head>

<body>


<header class="topbar">

    <a href="dashboard.php" class="brand">

        <div class="brand-icon">
            <i class="fa-solid fa-microchip"></i>
        </div>

        <div class="brand-text">
            GEOTAMA COMPUTER

            <small>
                Store Management
            </small>
        </div>

    </a>


    <nav class="nav">

        <a href="dashboard.php">
            <i class="fa-solid fa-chart-line"></i>
            <span>Dashboard</span>
        </a>

        <a href="edit.php" class="active">
            <i class="fa-solid fa-box"></i>
            <span>Produk</span>
        </a>

        <a href="index.php">
            <i class="fa-solid fa-store"></i>
            <span>Toko</span>
        </a>

        <?php if (in_array(user()['role'] ?? '', ['verified_admin','super_admin'], true)): ?>

            <a href="setting.php">
                <i class="fa-solid fa-gear"></i>
                <span>Setting</span>
            </a>

        <?php endif; ?>


        <form
            method="post"
            action="logout.php"
            class="logout-form"
        >

            <input
                type="hidden"
                name="csrf"
                value="<?= h(csrf()) ?>"
            >

            <button
                type="submit"
                class="logout-btn"
                title="Logout"
            >
                <i class="fa-solid fa-right-from-bracket"></i>

                <span>Logout</span>
            </button>

        </form>

    </nav>

</header>


<main class="container">


<?php if ($action === 'add' || $action === 'edit'): ?>


    <!-- =====================================================
         FORM TAMBAH / EDIT
    ====================================================== -->

    <div class="page-head">

        <div>

            <h1>
                <?= $action === 'edit'
                    ? 'Edit Produk'
                    : 'Tambah Produk'
                ?>
            </h1>

            <p>
                Kelola informasi produk dan spesifikasi berdasarkan kategori.
            </p>

        </div>


        <div class="actions">

            <a
                href="edit.php"
                class="btn"
            >
                <i class="fa-solid fa-arrow-left"></i>
                Kembali
            </a>

        </div>

    </div>


    <form
        method="post"
        action="edit.php"
        class="panel"
    >

        <input
            type="hidden"
            name="form_action"
            value="save"
        >

        <input
            type="hidden"
            name="csrf"
            value="<?= h(csrf()) ?>"
        >

        <input
            type="hidden"
            name="id"
            value="<?= h((string)($formProduct['id'] ?? '')) ?>"
        >


        <div class="panel-head">

            <div class="panel-title">

                <i class="fa-solid fa-box-open"></i>

                Informasi Produk

            </div>

        </div>


        <div class="panel-body">

            <div class="form-grid">


                <div class="field">

                    <label>
                        Nama Produk
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="name"
                        required
                        value="<?= h((string)($formProduct['name'] ?? '')) ?>"
                        placeholder="Contoh: Epson L3210"
                    >

                </div>


                <div class="field">

                    <label>
                        Kategori
                        <span>*</span>
                    </label>

                    <select
                        name="category"
                        id="category"
                        required
                    >

                        <option value="">
                            — Pilih Kategori —
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= h($category) ?>"
                                <?= strtolower((string)($formProduct['category'] ?? '')) === strtolower($category) ? 'selected' : '' ?>
                            >
                                <?= h($category) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <small>
                        Pilih kategori untuk menampilkan spesifikasi yang sesuai.
                    </small>

                </div>


                <div class="field">

                    <label>
                        Harga Jual
                    </label>

                    <input
                        type="number"
                        name="price"
                        min="0"
                        step="1"
                        value="<?= h((string)($formProduct['price'] ?? '')) ?>"
                        placeholder="Contoh: 3500000"
                    >

                </div>


                <div class="field">

                    <label>
                        Stok
                    </label>

                    <input
                        type="number"
                        name="stock"
                        min="0"
                        step="1"
                        value="<?= h((string)($formProduct['stock'] ?? '0')) ?>"
                        placeholder="0"
                    >

                </div>


                <div class="field full">

                    <label>
                        URL / Path Gambar
                    </label>

                    <input
                        type="text"
                        name="image"
                        value="<?= h((string)($formProduct['image'] ?? '')) ?>"
                        placeholder="images/epson-l3210.jpg atau URL gambar"
                    >

                    <small>
                        Bisa menggunakan path gambar lokal atau URL gambar.
                    </small>

                </div>


                <div class="field full">

                    <label>
                        Deskripsi Produk
                    </label>

                    <textarea
                        name="description"
                        placeholder="Tulis deskripsi singkat produk..."
                    ><?= h((string)($formProduct['description'] ?? '')) ?></textarea>

                </div>

            </div>


            <!-- =================================================
                 CATEGORY SPEC
            ================================================== -->

            <div
                class="spec-section"
                id="specSection"
            >

                <div class="spec-title">

                    <i class="fa-solid fa-sliders"></i>

                    Spesifikasi Produk

                </div>

                <div class="spec-description">
                    Field spesifikasi akan otomatis berubah mengikuti kategori produk.
                </div>


                <div
                    class="spec-grid"
                    id="specGrid"
                >

                    <!-- JS akan mengisi di sini -->

                </div>

            </div>


            <div
                style="
                    margin-top:24px;
                    display:flex;
                    justify-content:flex-end;
                    gap:9px;
                "
            >

                <a
                    href="edit.php"
                    class="btn"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    <i class="fa-solid fa-floppy-disk"></i>

                    <?= $action === 'edit'
                        ? 'Simpan Perubahan'
                        : 'Tambah Produk'
                    ?>

                </button>

            </div>

        </div>

    </form>


<?php else: ?>


    <!-- =====================================================
         LIST PRODUK
    ====================================================== -->

    <div class="page-head">

        <div>

            <h1>
                Kelola Produk
            </h1>

            <p>
                Tambah, edit, dan hapus produk Geotama Computer.
            </p>

        </div>


        <div class="actions">

            <a
                href="edit.php?action=add"
                class="btn btn-primary"
            >

                <i class="fa-solid fa-plus"></i>

                Tambah Barang

            </a>

        </div>

    </div>


    <div class="panel">

        <div class="panel-head">

            <div class="panel-title">

                <i class="fa-solid fa-boxes-stacked"></i>

                Daftar Produk

            </div>

            <span
                style="
                    color:#64748b;
                    font-size:11px;
                "
            >
                <?= count($products) ?> produk
            </span>

        </div>


        <div class="table-wrap">

            <?php if (!$products): ?>

                <div class="empty">

                    <i class="fa-solid fa-box-open"></i>

                    Belum ada produk.

                    <br>

                    <a
                        href="edit.php?action=add"
                        style="
                            display:inline-block;
                            margin-top:12px;
                            color:#55b3ff;
                        "
                    >
                        Tambahkan produk pertama
                    </a>

                </div>

            <?php else: ?>

                <table>

                    <thead>

                        <tr>

                            <th>Produk</th>

                            <th>Kategori</th>

                            <th>Harga</th>

                            <th>Stok</th>

                            <th>Spesifikasi</th>

                            <th>Aksi</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($products as $product): ?>

                        <?php

                        $pid = (int)($product['id'] ?? 0);

                        $stock = (int)($product['stock'] ?? 0);

                        $categorySpecs =
                            getCategorySpecs($product);

                        $filledSpecs = 0;

                        foreach ($categorySpecs as $spec) {

                            if (
                                trim(
                                    (string)(
                                        $product[$spec['key']]
                                        ?? ''
                                    )
                                ) !== ''
                            ) {
                                $filledSpecs++;
                            }
                        }

                        ?>

                        <tr>


                            <td>

                                <div class="product-name">

                                    <div class="product-thumb">

                                        <?php if (!empty($product['image'])): ?>

                                            <img
                                                src="<?= h((string)$product['image']) ?>"
                                                alt="<?= h((string)($product['name'] ?? 'Produk')) ?>"
                                                onerror="this.style.display='none'"
                                            >

                                        <?php else: ?>

                                            <div
                                                style="
                                                    width:100%;
                                                    height:100%;
                                                    display:grid;
                                                    place-items:center;
                                                    color:#315a7c;
                                                "
                                            >

                                                <i class="fa-solid fa-image"></i>

                                            </div>

                                        <?php endif; ?>

                                    </div>


                                    <div>

                                        <strong>
                                            <?= h((string)($product['name'] ?? '-')) ?>
                                        </strong>

                                        <small>
                                            ID #<?= $pid ?>
                                        </small>

                                    </div>

                                </div>

                            </td>


                            <td>

                                <span class="category-badge">

                                    <?= h((string)($product['category'] ?? '-')) ?>

                                </span>

                            </td>


                            <td>

                                <span class="price">

                                    <?= money($product['price'] ?? 0) ?>

                                </span>

                            </td>


                            <td>

                                <?php if ($stock <= 0): ?>

                                    <span class="stock-empty">
                                        <i class="fa-solid fa-circle-xmark"></i>
                                        Habis
                                    </span>

                                <?php elseif ($stock <= 3): ?>

                                    <span class="stock-low">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        <?= $stock ?>
                                    </span>

                                <?php else: ?>

                                    <span class="stock-ok">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <?= $stock ?>
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <span
                                    style="
                                        color:#8292a9;
                                        font-size:11px;
                                    "
                                >

                                    <?= $filledSpecs ?>
                                    /
                                    <?= count($categorySpecs) ?>

                                    field terisi

                                </span>

                            </td>


                            <td>

                                <div class="row-actions">

                                    <a
                                        href="edit.php?action=edit&id=<?= $pid ?>"
                                        class="icon-btn"
                                        title="Edit"
                                    >

                                        <i class="fa-solid fa-pen"></i>

                                    </a>


                                    <a
                                        href="index.php"
                                        target="_blank"
                                        class="icon-btn"
                                        title="Lihat Toko"
                                    >

                                        <i class="fa-solid fa-eye"></i>

                                    </a>


                                    <form
                                        method="post"
                                        action="edit.php"
                                        style="margin:0"
                                        onsubmit="return confirm('Hapus produk ini?')"
                                    >

                                        <input
                                            type="hidden"
                                            name="form_action"
                                            value="delete"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf"
                                            value="<?= h(csrf()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= $pid ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="icon-btn delete"
                                            title="Hapus"
                                        >

                                            <i class="fa-solid fa-trash"></i>

                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            <?php endif; ?>

        </div>

    </div>


<?php endif; ?>

</main>


<?php if (isset($_GET['saved'])): ?>

    <div class="toast">

        <i class="fa-solid fa-circle-check"></i>

        Produk berhasil disimpan.

    </div>

<?php endif; ?>


<?php if (isset($_GET['deleted'])): ?>

    <div class="toast">

        <i class="fa-solid fa-trash"></i>

        Produk berhasil dihapus.

    </div>

<?php endif; ?>


<?php if (isset($_GET['error'])): ?>

    <div class="toast">

        <i class="fa-solid fa-circle-exclamation"></i>

        Terjadi kesalahan. Silakan cek kembali data produk.

    </div>

<?php endif; ?>


<script>

const categorySpecs = {

    "Laptop": [
        ["cpu","Processor","fa-solid fa-microchip"],
        ["ram","RAM","fa-solid fa-memory"],
        ["ssd","Storage","fa-solid fa-hard-drive"],
        ["display","Display","fa-solid fa-display"],
        ["os","OS","fa-brands fa-windows"],
        ["gpu","GPU","fa-solid fa-gamepad"]
    ],

    "PC Fullset": [
        ["cpu","Processor","fa-solid fa-microchip"],
        ["ram","RAM","fa-solid fa-memory"],
        ["ssd","Storage","fa-solid fa-hard-drive"],
        ["vga","VGA","fa-solid fa-gamepad"],
        ["monitor","Monitor","fa-solid fa-display"],
        ["psu","PSU","fa-solid fa-bolt"]
    ],

    "CPU Only": [
        ["socket","Socket","fa-solid fa-microchip"],
        ["cores","Core","fa-solid fa-layer-group"],
        ["threads","Thread","fa-solid fa-diagram-project"],
        ["base_clock","Base Clock","fa-solid fa-gauge-high"],
        ["boost_clock","Boost Clock","fa-solid fa-bolt"],
        ["tdp","TDP","fa-solid fa-temperature-half"]
    ],

    "Monitor": [
        ["size","Ukuran","fa-solid fa-expand"],
        ["panel","Panel","fa-solid fa-tv"],
        ["resolution","Resolusi","fa-solid fa-maximize"],
        ["refresh_rate","Refresh Rate","fa-solid fa-arrows-rotate"],
        ["response_time","Response Time","fa-solid fa-stopwatch"],
        ["ports","Port","fa-solid fa-plug"]
    ],

    "Aksesoris PC/Laptop": [
        ["accessory_type","Tipe","fa-solid fa-toolbox"],
        ["connectivity","Koneksi","fa-solid fa-plug"],
        ["compatibility","Kompatibilitas","fa-solid fa-laptop"],
        ["interface","Interface","fa-solid fa-usb"],
        ["material","Material","fa-solid fa-cubes"],
        ["warranty","Garansi","fa-solid fa-shield-halved"]
    ],

    "Printer": [
        ["brand","Merk","fa-solid fa-copyright"],
        ["printer_type","Tipe","fa-solid fa-print"],
        ["print_technology","Teknologi","fa-solid fa-gears"],
        ["resolution","Resolusi","fa-solid fa-maximize"],
        ["print_speed","Kecepatan","fa-solid fa-gauge-high"],
        ["connectivity","Koneksi","fa-solid fa-wifi"]
    ],

    "Tinta": [
        ["ink_type","Tipe","fa-solid fa-droplet"],
        ["color","Warna","fa-solid fa-palette"],
        ["capacity","Kapasitas","fa-solid fa-flask"],
        ["compatibility","Kompatibilitas","fa-solid fa-link"],
        ["type_code","Kode","fa-solid fa-barcode"],
        ["condition","Kondisi","fa-solid fa-circle-check"]
    ],

    "Networking": [
        ["network_type","Tipe","fa-solid fa-network-wired"],
        ["speed","Speed","fa-solid fa-gauge-high"],
        ["ports","Port","fa-solid fa-ethernet"],
        ["wifi","Wi-Fi","fa-solid fa-wifi"],
        ["antenna","Antenna","fa-solid fa-satellite-dish"],
        ["range","Coverage","fa-solid fa-tower-broadcast"]
    ],

    "CCTV": [
        ["resolution","Resolusi","fa-solid fa-camera"],
        ["lens","Lens","fa-solid fa-eye"],
        ["night_vision","Night Vision","fa-solid fa-moon"],
        ["storage","Storage","fa-solid fa-hard-drive"],
        ["connectivity","Koneksi","fa-solid fa-wifi"],
        ["weatherproof","Protection","fa-solid fa-cloud-rain"]
    ]

};


const categorySelect =
    document.getElementById("category");

const specGrid =
    document.getElementById("specGrid");


/*
|--------------------------------------------------------------------------
| EXISTING PRODUCT DATA
|--------------------------------------------------------------------------
*/

const existingProduct =
    <?= json_encode(
        $formProduct,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_HEX_AMP
    ) ?>;


/*
|--------------------------------------------------------------------------
| RENDER SPECIFICATION
|--------------------------------------------------------------------------
*/

function renderSpecs(){

    if (!categorySelect || !specGrid) {
        return;
    }

    const category =
        categorySelect.value;

    specGrid.innerHTML = "";

    if (!category) {

        specGrid.innerHTML = `
            <div style="
                grid-column:1/-1;
                padding:25px;
                border:1px dashed rgba(255,255,255,.08);
                border-radius:11px;
                text-align:center;
                color:#627188;
                font-size:11px;
            ">
                <i
                    class="fa-solid fa-layer-group"
                    style="
                        font-size:22px;
                        display:block;
                        margin-bottom:9px;
                        color:#2c73ad;
                    "
                ></i>

                Pilih kategori terlebih dahulu.
            </div>
        `;

        return;
    }


    const specs =
        categorySpecs[category] || [];


    if (!specs.length) {

        specGrid.innerHTML = `
            <div style="
                grid-column:1/-1;
                color:#66758b;
                font-size:11px;
            ">
                Belum ada konfigurasi spesifikasi untuk kategori ini.
            </div>
        `;

        return;
    }


    specs.forEach(spec => {

        const [
            key,
            label,
            icon
        ] = spec;


        const value =
            existingProduct[key] || "";


        const wrapper =
            document.createElement("div");

        wrapper.className =
            "spec-field";


        wrapper.innerHTML = `

            <label>

                <i class="${icon}"></i>

                ${label}

            </label>

            <input
                type="text"
                name="spec[${key}]"
                value="${escapeHtml(value)}"
                placeholder="Isi ${label}"
            >

        `;


        specGrid.appendChild(wrapper);

    });

}


/*
|--------------------------------------------------------------------------
| ESCAPE VALUE
|--------------------------------------------------------------------------
*/

function escapeHtml(value){

    return String(value ?? "")
        .replace(/&/g,"&amp;")
        .replace(/</g,"&lt;")
        .replace(/>/g,"&gt;")
        .replace(/"/g,"&quot;")
        .replace(/'/g,"&#039;");
}


/*
|--------------------------------------------------------------------------
| CATEGORY CHANGE
|--------------------------------------------------------------------------
*/

if (categorySelect) {

    categorySelect.addEventListener(
        "change",
        function(){

            /*
             * Kalau kategori berubah,
             * hapus data spesifikasi lama dari tampilan.
             */

            Object.keys(existingProduct)
                .forEach(key => {

                    if (
                        key !== "category" &&
                        key !== "id" &&
                        key !== "name" &&
                        key !== "price" &&
                        key !== "stock" &&
                        key !== "description" &&
                        key !== "image"
                    ) {

                        delete existingProduct[key];

                    }

                });


            renderSpecs();

        }
    );

}


/*
|--------------------------------------------------------------------------
| INITIAL RENDER
|--------------------------------------------------------------------------
*/

renderSpecs();


/*
|--------------------------------------------------------------------------
| AUTO HIDE TOAST
|--------------------------------------------------------------------------
*/

setTimeout(() => {

    const toast =
        document.querySelector(".toast");

    if (toast) {

        toast.style.transition =
            "opacity .3s, transform .3s";

        toast.style.opacity = "0";

        toast.style.transform =
            "translateY(10px)";

        setTimeout(
            () => toast.remove(),
            350
        );

    }

}, 3500);

</script>

</body>
</html>