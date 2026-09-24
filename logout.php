<?php
declare(strict_types=1);

require __DIR__ . '/common.php';

/*
|--------------------------------------------------------------------------
| PROSES LOGOUT
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validasi CSRF
    check_csrf();

    // Hapus seluruh session
    $_SESSION = [];

    // Hapus cookie session
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    // Hancurkan session
    session_destroy();

    // Kembali ke katalog utama
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| TOKEN CSRF
|--------------------------------------------------------------------------
*/
$csrfToken = csrf();
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
    content="#070b12"
>

<title>Logout — GEOTAMA COMPUTER</title>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin
>

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap"
    rel="stylesheet"
>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>

<style>

:root{

    --blue:#2563eb;
    --blue-light:#3b82f6;
    --blue-dark:#1d4ed8;
    --cyan:#38bdf8;

    --bg:#070b12;
    --bg-2:#0a101b;

    --panel:#0d1420;
    --panel-2:#101a29;

    --text:#f1f5f9;
    --muted:#8fa1b8;

    --border:rgba(148,163,184,.12);

    --shadow:
        0 25px 80px rgba(0,0,0,.45);
}

*{
    box-sizing:border-box;
}

html,
body{
    margin:0;
    min-height:100%;
}

body{

    min-height:100vh;

    display:flex;
    align-items:center;
    justify-content:center;

    padding:20px;

    background:
        radial-gradient(
            circle at 50% 0%,
            rgba(37,99,235,.18),
            transparent 38%
        ),
        radial-gradient(
            circle at 100% 100%,
            rgba(56,189,248,.07),
            transparent 35%
        ),
        linear-gradient(
            145deg,
            var(--bg),
            var(--bg-2)
        );

    color:var(--text);

    font-family:
        Inter,
        Arial,
        Helvetica,
        sans-serif;

    overflow:hidden;
}

/* =========================================================
   BACKGROUND
========================================================= */

.bg-grid{

    position:fixed;

    inset:0;

    pointer-events:none;

    opacity:.35;

    background-image:
        linear-gradient(
            rgba(255,255,255,.025) 1px,
            transparent 1px
        ),
        linear-gradient(
            90deg,
            rgba(255,255,255,.025) 1px,
            transparent 1px
        );

    background-size:40px 40px;

    mask-image:
        linear-gradient(
            to bottom,
            black,
            transparent 90%
        );
}

/* =========================================================
   CARD
========================================================= */

.logout-card{

    position:relative;

    width:min(440px,100%);

    padding:34px;

    border:1px solid var(--border);

    border-radius:26px;

    background:
        linear-gradient(
            145deg,
            rgba(13,20,32,.97),
            rgba(10,16,27,.94)
        );

    box-shadow:var(--shadow);

    backdrop-filter:blur(18px);

    text-align:center;

    animation:
        popup .4s cubic-bezier(.2,.8,.2,1);

    overflow:hidden;
}

/* top blue glow */

.logout-card::before{

    content:"";

    position:absolute;

    top:-120px;
    left:50%;

    width:260px;
    height:260px;

    transform:translateX(-50%);

    background:
        radial-gradient(
            circle,
            rgba(37,99,235,.22),
            transparent 70%
        );

    pointer-events:none;
}

/* =========================================================
   ICON
========================================================= */

.icon-wrap{

    position:relative;

    width:76px;
    height:76px;

    margin:0 auto 22px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:22px;

    background:
        linear-gradient(
            145deg,
            rgba(37,99,235,.18),
            rgba(56,189,248,.08)
        );

    border:1px solid rgba(59,130,246,.22);

    color:var(--blue-light);

    box-shadow:
        0 12px 35px rgba(37,99,235,.12);
}

.icon-wrap i{

    font-size:30px;

    filter:
        drop-shadow(
            0 4px 12px rgba(59,130,246,.3)
        );
}

/* =========================================================
   TEXT
========================================================= */

h1{

    position:relative;

    margin:0 0 10px;

    font-family:
        "Space Grotesk",
        Inter,
        sans-serif;

    font-size:25px;

    line-height:1.25;

    font-weight:700;

    letter-spacing:-.5px;
}

.description{

    position:relative;

    margin:0 auto 26px;

    max-width:330px;

    color:var(--muted);

    font-size:14px;

    line-height:1.7;
}

/* =========================================================
   USER INFO
========================================================= */

.session-info{

    display:flex;

    align-items:center;

    gap:11px;

    margin-bottom:22px;

    padding:12px 14px;

    border-radius:15px;

    background:
        rgba(255,255,255,.025);

    border:1px solid var(--border);

    text-align:left;
}

.session-icon{

    width:36px;
    height:36px;

    flex:0 0 36px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:11px;

    background:
        rgba(37,99,235,.12);

    color:var(--blue-light);

    font-size:14px;
}

.session-text{

    min-width:0;
}

.session-label{

    display:block;

    margin-bottom:2px;

    color:#64748b;

    font-size:11px;

    font-weight:700;

    text-transform:uppercase;

    letter-spacing:.7px;
}

.session-value{

    display:block;

    color:#cbd5e1;

    font-size:13px;

    font-weight:600;
}

/* =========================================================
   ACTIONS
========================================================= */

.actions{

    display:grid;

    grid-template-columns:1fr 1fr;

    gap:11px;
}

button{

    width:100%;

    min-height:49px;

    border:0;

    border-radius:14px;

    padding:13px 16px;

    font-family:inherit;

    font-size:14px;

    font-weight:700;

    cursor:pointer;

    transition:
        transform .2s ease,
        background .2s ease,
        border-color .2s ease,
        box-shadow .2s ease;
}

button:active{

    transform:scale(.98);
}

/* cancel */

.btn-cancel{

    display:flex;

    align-items:center;
    justify-content:center;

    gap:8px;

    background:
        rgba(255,255,255,.055);

    color:#cbd5e1;

    border:1px solid var(--border);
}

.btn-cancel:hover{

    background:
        rgba(255,255,255,.09);

    border-color:
        rgba(148,163,184,.2);

    transform:translateY(-1px);
}

/* logout */

.btn-logout{

    display:flex;

    align-items:center;
    justify-content:center;

    gap:8px;

    color:#fff;

    background:
        linear-gradient(
            135deg,
            var(--blue),
            var(--blue-dark)
        );

    box-shadow:
        0 10px 28px rgba(37,99,235,.22);
}

.btn-logout:hover{

    background:
        linear-gradient(
            135deg,
            var(--blue-light),
            var(--blue)
        );

    transform:translateY(-1px);

    box-shadow:
        0 14px 32px rgba(37,99,235,.32);
}

/* =========================================================
   FOOTER
========================================================= */

.footer-text{

    margin-top:22px;

    color:#526176;

    font-size:11px;

    letter-spacing:.2px;
}

.footer-text strong{

    color:#71829a;

    font-weight:700;
}

/* =========================================================
   ANIMATION
========================================================= */

@keyframes popup{

    from{

        opacity:0;

        transform:
            translateY(14px)
            scale(.96);
    }

    to{

        opacity:1;

        transform:
            translateY(0)
            scale(1);
    }
}

/* =========================================================
   MOBILE
========================================================= */

@media(max-width:480px){

    body{

        padding:15px;
    }

    .logout-card{

        padding:27px 20px;

        border-radius:22px;
    }

    .icon-wrap{

        width:68px;
        height:68px;

        margin-bottom:19px;

        border-radius:19px;
    }

    .icon-wrap i{

        font-size:27px;
    }

    h1{

        font-size:22px;
    }

    .description{

        font-size:13px;

        margin-bottom:22px;
    }

    .actions{

        grid-template-columns:1fr;
    }

    .btn-cancel{

        order:2;
    }

    .btn-logout{

        order:1;
    }
}

@media(prefers-reduced-motion:reduce){

    *{

        animation:none !important;

        transition:none !important;
    }
}

</style>

</head>

<body>

<div class="bg-grid"></div>

<div class="logout-card">

    <!-- ICON -->

    <div class="icon-wrap">

        <i class="fa-solid fa-arrow-right-from-bracket"></i>

    </div>


    <!-- TITLE -->

    <h1>
        Keluar dari akun?
    </h1>


    <!-- DESCRIPTION -->

    <p class="description">
        Sesi login kamu akan diakhiri.
        Pastikan semua pekerjaan sudah selesai
        sebelum keluar dari panel Geotama Computer.
    </p>


    <!-- SESSION INFO -->

    <?php if (logged()): ?>

        <?php
        $currentUser = user();
        ?>

        <div class="session-info">

            <div class="session-icon">

                <i class="fa-solid fa-user"></i>

            </div>

            <div class="session-text">

                <span class="session-label">
                    Akun yang sedang aktif
                </span>

                <span class="session-value">
                    <?= h(
                        $currentUser['name']
                        ?? $currentUser['username']
                        ?? 'User'
                    ) ?>
                </span>

            </div>

        </div>

    <?php endif; ?>


    <!-- FORM -->

    <form
        method="post"
        action="logout.php"
    >

        <input
            type="hidden"
            name="csrf"
            value="<?= h($csrfToken) ?>"
        >

        <div class="actions">

            <button
                type="button"
                class="btn-cancel"
                onclick="history.back()"
            >

                <i class="fa-solid fa-arrow-left"></i>

                <span>Batal</span>

            </button>


            <button
                type="submit"
                class="btn-logout"
            >

                <i class="fa-solid fa-right-from-bracket"></i>

                <span>Ya, Logout</span>

            </button>

        </div>

    </form>


    <div class="footer-text">

        <strong>GEOTAMA COMPUTER</strong>
        &nbsp;•&nbsp;
        Store Management

    </div>

</div>

</body>
</html>