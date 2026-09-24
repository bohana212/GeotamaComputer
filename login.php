<?php
declare(strict_types=1);

require __DIR__ . '/common.php';

/*
=========================================================
 GEOTAMA COMPUTER
 LOGIN SYSTEM
=========================================================

Default:
Username : superadmin
Password : admin123

Support:
- users.json array langsung
- users.json {"users":[...]}
- password_hash()
- password plaintext lama -> otomatis diubah menjadi hash
=========================================================
*/


/* =========================================================
   LOGOUT
========================================================= */

if (isset($_GET['logout'])) {

    $_SESSION = [];

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

    session_destroy();

    header('Location: index.php');
    exit;
}


/* =========================================================
   JIKA SUDAH LOGIN
========================================================= */

if (logged()) {

    header('Location: dashboard.php');
    exit;
}


/* =========================================================
   AMBIL USERS
========================================================= */

function get_users(): array
{
    $data = read_json(USERS_FILE, []);

    /*
     * Format:
     *
     * [
     *   {...},
     *   {...}
     * ]
     */

    if (isset($data[0]) && is_array($data[0])) {
        return $data;
    }


    /*
     * Format:
     *
     * {
     *   "users": [
     *      {...}
     *   ]
     * }
     */

    if (
        isset($data['users']) &&
        is_array($data['users'])
    ) {
        return $data['users'];
    }

    return [];
}


/* =========================================================
   BUAT AKUN DEFAULT JIKA BELUM ADA USER
========================================================= */

function ensure_default_admin(): void
{
    $users = get_users();

    foreach ($users as $u) {

        if (
            strtolower(
                trim((string)($u['username'] ?? ''))
            ) === 'superadmin'
        ) {
            return;
        }
    }


    /*
     * Kalau users.json kosong,
     * otomatis buat superadmin.
     */

    $users[] = [

        'id' => 1,

        'username' => 'superadmin',

        'name' => 'Super Administrator',

        'email' => 'admin@geotama.local',

        'password' => password_hash(
            'admin123',
            PASSWORD_DEFAULT
        ),

        'role' => 'super_admin',

        'verified' => true,

        'status' => 'active',

        'created_at' => date(
            'Y-m-d H:i:s'
        )
    ];


    write_json(
        USERS_FILE,
        $users
    );
}


/*
 * Pastikan akun default tersedia.
 */

ensure_default_admin();


/* =========================================================
   CSRF TOKEN
========================================================= */

$error = '';

$csrfToken = csrf();


/* =========================================================
   PROSES LOGIN
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
     * Validasi CSRF.
     */

    check_csrf();


    $username = trim(
        (string)(
            $_POST['username'] ?? ''
        )
    );


    $password = (string)(
        $_POST['password'] ?? ''
    );


    /*
     * Validasi input.
     */

    if (
        $username === '' ||
        $password === ''
    ) {

        $error =
            'Username dan password wajib diisi.';

    } else {

        $users = get_users();

        $loginSuccess = false;


        foreach (
            $users
            as $index => $x
        ) {

            if (!is_array($x)) {
                continue;
            }


            $storedUsername = trim(
                (string)(
                    $x['username'] ?? ''
                )
            );


            /*
             * Username tidak cocok.
             */

            if (
                strtolower(
                    $storedUsername
                )
                !==
                strtolower(
                    $username
                )
            ) {

                continue;
            }


            /*
             * Status akun.
             */

            $status = strtolower(
                trim(
                    (string)(
                        $x['status']
                        ?? 'active'
                    )
                )
            );


            if (
                $status !== 'active'
            ) {

                $error =
                    'Akun kamu sedang tidak aktif.';

                break;
            }


            /*
             * Password tersimpan.
             */

            $storedPassword = (string)(
                $x['password'] ?? ''
            );


            $passwordValid = false;


            /*
             * PASSWORD HASH
             */

            if (
                $storedPassword !== '' &&
                password_verify(
                    $password,
                    $storedPassword
                )
            ) {

                $passwordValid = true;
            }


            /*
             * LEGACY PLAINTEXT PASSWORD
             */

            if (
                !$passwordValid &&
                $storedPassword !== '' &&
                hash_equals(
                    $storedPassword,
                    $password
                )
            ) {

                $passwordValid = true;


                /*
                 * Upgrade plaintext
                 * menjadi password hash.
                 */

                $users[$index]['password'] =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );


                write_json(
                    USERS_FILE,
                    $users
                );
            }


            /*
             * Password salah.
             */

            if (!$passwordValid) {

                $error =
                    'Password yang kamu masukkan salah.';

                break;
            }


            /*
             * LOGIN BERHASIL
             */

            session_regenerate_id(true);


            /*
             * Pastikan ID tersedia.
             */

            $userId =
                $x['id']
                ??
                ($index + 1);


            /*
             * Simpan session.
             */

            $_SESSION['user'] = [

                'id' => $userId,

                'username' =>
                    $storedUsername,

                'name' =>
                    (string)(
                        $x['name']
                        ??
                        $storedUsername
                    ),

                'email' =>
                    (string)(
                        $x['email']
                        ??
                        ''
                    ),

                'role' =>
                    (string)(
                        $x['role']
                        ??
                        'admin'
                    ),

                'verified' =>
                    (bool)(
                        $x['verified']
                        ??
                        false
                    )
            ];


            /*
             * Regenerate CSRF
             * setelah login.
             */

            $_SESSION['csrf'] =
                bin2hex(
                    random_bytes(24)
                );


            /*
             * Next URL.
             */

            $next = trim(
                (string)(
                    $_GET['next']
                    ??
                    $_POST['next']
                    ??
                    ''
                )
            );


            /*
             * Cegah redirect
             * keluar domain.
             */

            if (
                $next !== '' &&
                str_starts_with(
                    $next,
                    '/'
                )
            ) {

                header(
                    'Location: '
                    . $next
                );

            } else {

                header(
                    'Location: dashboard.php'
                );
            }

            exit;
        }


        /*
         * Username tidak ditemukan.
         */

        if (
            !$loginSuccess &&
            $error === ''
        ) {

            $error =
                'Username tidak ditemukan.';
        }
    }
}

?>

<!doctype html>

<html
    lang="id"
>

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<meta
    name="theme-color"
    content="#ff7a00"
>

<meta
    name="description"
    content="Login Admin Geotama Computer"
>

<title>
    Login Admin — Geotama Computer
</title>


<link
    rel="stylesheet"
    href="style.css"
>


<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap"
    rel="stylesheet"
>


<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
>


<style>

/* =========================================================
   BASE
========================================================= */

*{
    box-sizing:border-box;
}


html{
    min-height:100%;
}


body{
    margin:0;
    min-height:100vh;

    overflow-x:hidden;

    font-family:
        "Inter",
        sans-serif;
}


/* =========================================================
   LOGIN WRAPPER
========================================================= */

.login{
    min-height:100vh;

    display:flex;

    align-items:center;

    justify-content:center;

    padding:24px;

    position:relative;

    overflow:hidden;

    background:
        radial-gradient(
            circle at 10% 10%,
            rgba(255,122,0,.10),
            transparent 30%
        ),
        radial-gradient(
            circle at 90% 90%,
            rgba(255,122,0,.07),
            transparent 30%
        );
}


/* =========================================================
   BACKGROUND DECORATION
========================================================= */

.login::before{
    content:"";

    position:absolute;

    width:430px;
    height:430px;

    left:-190px;
    top:-190px;

    border-radius:50%;

    background:
        radial-gradient(
            circle,
            rgba(255,122,0,.24),
            transparent 68%
        );

    filter:blur(25px);

    pointer-events:none;
}


.login::after{
    content:"";

    position:absolute;

    width:500px;
    height:500px;

    right:-230px;
    bottom:-240px;

    border-radius:50%;

    background:
        radial-gradient(
            circle,
            rgba(255,122,0,.13),
            transparent 68%
        );

    filter:blur(30px);

    pointer-events:none;
}


/* =========================================================
   LOGIN BOX
========================================================= */

.login-box{
    width:100%;

    max-width:445px;

    position:relative;

    z-index:5;

    padding:36px;

    border-radius:30px;

    background:
        rgba(255,255,255,.94);

    border:
        1px solid
        rgba(0,0,0,.07);

    box-shadow:
        0 30px 90px
        rgba(0,0,0,.12);

    backdrop-filter:
        blur(20px);

    -webkit-backdrop-filter:
        blur(20px);

    animation:
        loginEnter
        .65s
        cubic-bezier(.2,.8,.2,1);
}


/* =========================================================
   TOP STARS
========================================================= */

.top-stars{
    text-align:center;

    margin-bottom:18px;

    color:#ff7a00;

    font-family:
        "JetBrains Mono",
        monospace;

    font-size:15px;

    font-weight:900;

    letter-spacing:6px;

    opacity:.9;

    user-select:none;

    animation:
        starsPulse
        2.8s
        ease-in-out
        infinite;
}


.top-stars span{
    display:inline-block;

    transform:
        translateY(0);
}


.top-stars span:nth-child(2){
    animation:
        starBounce
        2s
        ease-in-out
        infinite
        .15s;
}


.top-stars span:nth-child(3){
    animation:
        starBounce
        2s
        ease-in-out
        infinite
        .30s;
}


.top-stars span:nth-child(4){
    animation:
        starBounce
        2s
        ease-in-out
        infinite
        .45s;
}


.top-stars span:nth-child(5){
    animation:
        starBounce
        2s
        ease-in-out
        infinite
        .60s;
}


/* =========================================================
   LOGO
========================================================= */

.login-logo{
    width:82px;

    height:82px;

    margin:0 auto 22px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:25px;

    background:
        linear-gradient(
            145deg,
            #ff941f 0%,
            #ff7a00 48%,
            #ed5f00 100%
        );

    color:#fff;

    font-size:32px;

    box-shadow:
        0 18px 40px
        rgba(255,122,0,.28);

    position:relative;

    animation:
        logoFloat
        3.5s
        ease-in-out
        infinite;
}


.login-logo::before{
    content:"";

    position:absolute;

    inset:2px;

    border-radius:23px;

    border:
        1px solid
        rgba(255,255,255,.35);

    pointer-events:none;
}


.login-logo::after{
    content:"*****";

    position:absolute;

    left:50%;

    bottom:-10px;

    transform:
        translateX(-50%);

    padding:
        2px 9px;

    border-radius:999px;

    background:#fff;

    color:#ff7a00;

    font-family:
        "JetBrains Mono",
        monospace;

    font-size:8px;

    font-weight:900;

    letter-spacing:1px;

    box-shadow:
        0 5px 15px
        rgba(0,0,0,.10);
}


/* =========================================================
   TITLE
========================================================= */

.login-title{
    text-align:center;

    margin:0;

    font-family:
        "Space Grotesk",
        sans-serif;

    font-size:30px;

    font-weight:800;

    letter-spacing:-1px;

    color:#161616;
}


.login-title span{
    color:#ff7a00;
}


/* =========================================================
   SUBTITLE
========================================================= */

.login-subtitle{
    text-align:center;

    margin:
        10px
        auto
        27px;

    max-width:350px;

    line-height:1.65;

    color:#777;

    font-size:13px;
}


.login-subtitle b{
    color:#222;
}


/* =========================================================
   ERROR
========================================================= */

.login-error{
    display:flex;

    align-items:flex-start;

    gap:10px;

    padding:
        13px
        15px;

    margin-bottom:18px;

    border-radius:15px;

    background:
        #fff2f2;

    color:#c62828;

    border:
        1px solid
        #ffd5d5;

    font-size:13px;

    line-height:1.5;

    animation:
        errorShake
        .4s
        ease;
}


.login-error i{
    margin-top:2px;

    flex-shrink:0;
}


/* =========================================================
   FIELD
========================================================= */

.field{
    margin-bottom:17px;
}


.field label{
    display:block;

    margin-bottom:8px;

    color:#303030;

    font-size:13px;

    font-weight:700;
}


.input-wrap{
    position:relative;
}


.input-wrap > i{
    position:absolute;

    left:16px;

    top:50%;

    transform:
        translateY(-50%);

    color:#a1a1a1;

    pointer-events:none;

    transition:
        color .25s ease;
}


.input-wrap input{
    width:100%;

    min-height:51px;

    padding:
        14px
        48px
        14px
        45px;

    border-radius:15px;

    border:
        1px solid
        #e3e3e3;

    outline:none;

    font-family:
        "Inter",
        sans-serif;

    font-size:14px;

    background:#fff;

    color:#222;

    transition:
        border-color .25s ease,
        box-shadow .25s ease,
        transform .2s ease;
}


.input-wrap input::placeholder{
    color:#aaa;
}


.input-wrap input:hover{
    border-color:#d0d0d0;
}


.input-wrap input:focus{
    border-color:#ff7a00;

    box-shadow:
        0 0 0 4px
        rgba(255,122,0,.10);
}


.input-wrap:focus-within > i{
    color:#ff7a00;
}


/* =========================================================
   PASSWORD BUTTON
========================================================= */

.password-toggle{
    position:absolute;

    right:9px;

    top:50%;

    transform:
        translateY(-50%);

    width:35px;

    height:35px;

    border:0;

    background:transparent;

    color:#999;

    cursor:pointer;

    border-radius:10px;

    transition:
        background .2s ease,
        color .2s ease,
        transform .2s ease;
}


.password-toggle:hover{
    background:#fff3e9;

    color:#ff7a00;
}


.password-toggle:active{
    transform:
        translateY(-50%)
        scale(.92);
}


/* =========================================================
   FORM ACTION
========================================================= */

.form-actions{
    display:grid;

    grid-template-columns:
        1fr 1fr;

    gap:10px;

    margin-top:22px;
}


.form-actions .btn{
    min-height:50px;

    justify-content:center;

    border-radius:14px;
}


/* =========================================================
   LOGIN BUTTON
========================================================= */

.login-submit{
    position:relative;

    overflow:hidden;
}


.login-submit:disabled{
    opacity:.72;

    cursor:not-allowed;
}


.login-submit::after{
    content:"";

    position:absolute;

    inset:0;

    background:
        linear-gradient(
            110deg,
            transparent 20%,
            rgba(255,255,255,.25) 50%,
            transparent 80%
        );

    transform:
        translateX(-120%);

    transition:
        transform .65s ease;
}


.login-submit:hover::after{
    transform:
        translateX(120%);
}


/* =========================================================
   NOTICE
========================================================= */

.login-notice{
    margin-top:19px;

    padding:
        14px
        15px;

    border-radius:15px;

    background:
        linear-gradient(
            145deg,
            #fafafa,
            #f5f5f5
        );

    border:
        1px solid
        #ededed;

    color:#737373;

    font-size:12px;

    line-height:1.7;
}


.login-notice i{
    color:#ff7a00;

    margin-right:4px;
}


.login-notice b{
    color:#333;
}


/* =========================================================
   SECURITY LINE
========================================================= */

.security-line{
    display:flex;

    align-items:center;

    justify-content:center;

    gap:7px;

    margin-top:18px;

    color:#999;

    font-size:11px;
}


.security-line i{
    color:#41a85f;
}


/* =========================================================
   LOADING OVERLAY
========================================================= */

.loading-overlay{
    position:fixed;

    inset:0;

    z-index:9999;

    display:flex;

    align-items:center;

    justify-content:center;

    background:
        rgba(12,12,12,.58);

    backdrop-filter:
        blur(15px);

    -webkit-backdrop-filter:
        blur(15px);

    opacity:0;

    visibility:hidden;

    transition:
        opacity .3s ease,
        visibility .3s ease;
}


.loading-overlay.show{
    opacity:1;

    visibility:visible;
}


.loading-card{
    width:
        min(
            350px,
            calc(100% - 40px)
        );

    padding:
        32px
        25px;

    text-align:center;

    border-radius:27px;

    background:
        rgba(255,255,255,.97);

    box-shadow:
        0 30px 90px
        rgba(0,0,0,.28);

    transform:
        translateY(15px)
        scale(.95);

    transition:
        transform .4s
        cubic-bezier(.2,.8,.2,1);
}


.loading-overlay.show
.loading-card{
    transform:
        translateY(0)
        scale(1);
}


/* =========================================================
   LOADER
========================================================= */

.loader-wrap{
    position:relative;

    width:70px;

    height:70px;

    margin:
        0
        auto
        20px;
}


.loader{
    width:70px;

    height:70px;

    border-radius:50%;

    border:
        4px solid
        #ededed;

    border-top-color:#ff7a00;

    border-right-color:#ff7a00;

    animation:
        spin
        .8s
        linear
        infinite;
}


.loader-star{
    position:absolute;

    inset:0;

    display:flex;

    align-items:center;

    justify-content:center;

    color:#ff7a00;

    font-family:
        "JetBrains Mono",
        monospace;

    font-size:11px;

    font-weight:900;

    letter-spacing:1px;
}


.loading-title{
    margin:
        0
        0
        7px;

    font-family:
        "Space Grotesk",
        sans-serif;

    font-size:20px;

    font-weight:800;

    color:#202020;
}


.loading-text{
    margin:0;

    color:#777;

    font-size:13px;
}


/* =========================================================
   ANIMATION
========================================================= */

@keyframes loginEnter{

    from{
        opacity:0;

        transform:
            translateY(25px)
            scale(.97);
    }

    to{
        opacity:1;

        transform:
            translateY(0)
            scale(1);
    }
}


@keyframes logoFloat{

    0%,100%{
        transform:
            translateY(0);
    }

    50%{
        transform:
            translateY(-6px);
    }
}


@keyframes starsPulse{

    0%,100%{
        opacity:.65;
    }

    50%{
        opacity:1;
    }
}


@keyframes starBounce{

    0%,100%{
        transform:
            translateY(0);
    }

    50%{
        transform:
            translateY(-3px);
    }
}


@keyframes spin{

    to{
        transform:
            rotate(360deg);
    }
}


@keyframes errorShake{

    0%,100%{
        transform:
            translateX(0);
    }

    25%{
        transform:
            translateX(-6px);
    }

    50%{
        transform:
            translateX(6px);
    }

    75%{
        transform:
            translateX(-3px);
    }
}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width:500px){

    .login{
        padding:15px;
    }


    .login-box{
        padding:
            28px
            20px;

        border-radius:24px;
    }


    .top-stars{
        margin-bottom:15px;

        font-size:13px;

        letter-spacing:5px;
    }


    .login-logo{
        width:76px;

        height:76px;

        border-radius:22px;

        font-size:29px;
    }


    .login-title{
        font-size:25px;
    }


    .login-subtitle{
        font-size:12px;

        margin-bottom:23px;
    }


    .form-actions{
        grid-template-columns:1fr;
    }


    .form-actions .btn{
        min-height:49px;
    }


    .login-notice{
        font-size:11px;
    }
}


/* =========================================================
   VERY SMALL SCREEN
========================================================= */

@media(max-width:350px){

    .login{
        padding:10px;
    }


    .login-box{
        padding:
            24px
            16px;
    }


    .login-title{
        font-size:23px;
    }
}

</style>

</head>


<body>


<main class="login">


    <div class="login-box panel">


        <!-- =================================================
             STAR HEADER
        ================================================== -->

        <div
            class="top-stars"
            aria-hidden="true"
        >

            <span>*</span>
            <span>*</span>
            <span>*</span>
            <span>*</span>
            <span>*</span>

        </div>


        <!-- =================================================
             LOGO
        ================================================== -->

        <div class="login-logo">

            <i
                class="fa-solid fa-microchip"
            ></i>

        </div>


        <!-- =================================================
             TITLE
        ================================================== -->

        <h1 class="login-title">

            ADMIN
            <span>LOGIN</span>

        </h1>


        <p class="muted login-subtitle">

            Masuk ke dashboard
            <b>Geotama Computer</b>
            untuk mengelola produk dan toko.

        </p>


        <!-- =================================================
             ERROR
        ================================================== -->

        <?php if ($error): ?>

            <div
                class="login-error"
                role="alert"
            >

                <i
                    class="fa-solid
                    fa-circle-exclamation"
                ></i>

                <span>

                    <?= h($error) ?>

                </span>

            </div>

        <?php endif; ?>


        <!-- =================================================
             LOGIN FORM
        ================================================== -->

        <form
            method="post"
            id="loginForm"
            autocomplete="on"
        >


            <input
                type="hidden"
                name="csrf"
                value="<?= h($csrfToken) ?>"
            >


            <!-- USERNAME -->

            <div class="field">

                <label
                    for="username"
                >
                    Username
                </label>


                <div class="input-wrap">

                    <i
                        class="fa-solid
                        fa-user"
                    ></i>


                    <input
                        id="username"
                        name="username"
                        type="text"
                        autocomplete="username"
                        placeholder="Masukkan username"
                        maxlength="100"
                        required
                        autofocus
                    >

                </div>

            </div>


            <!-- PASSWORD -->

            <div class="field">

                <label
                    for="password"
                >
                    Password
                </label>


                <div class="input-wrap">

                    <i
                        class="fa-solid
                        fa-lock"
                    ></i>


                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        placeholder="Masukkan password"
                        maxlength="255"
                        required
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        id="togglePassword"
                        aria-label="Tampilkan password"
                    >

                        <i
                            class="fa-solid
                            fa-eye"
                        ></i>

                    </button>

                </div>

            </div>


            <!-- ACTION -->

            <div class="form-actions">


                <a
                    class="btn btn-dark"
                    href="index.php"
                >

                    <i
                        class="fa-solid
                        fa-arrow-left"
                    ></i>

                    Kembali

                </a>


                <button
                    class="btn btn-primary login-submit"
                    id="loginButton"
                    type="submit"
                >

                    <span
                        id="loginButtonContent"
                    >

                        <i
                            class="fa-solid
                            fa-right-to-bracket"
                        ></i>

                        Login

                    </span>

                </button>


            </div>


        </form>


        <!-- =================================================
             DEFAULT ACCOUNT NOTICE
        ================================================== -->

        <div class="login-notice">

            <i
                class="fa-solid
                fa-shield-halved"
            ></i>

            Akun awal:

            <b>superadmin</b>

            /

            <b>admin123</b>

            <br>

            Ubah password setelah berhasil
            masuk ke dashboard.

        </div>


        <!-- =================================================
             SECURITY
        ================================================== -->

        <div class="security-line">

            <i
                class="fa-solid
                fa-circle-check"
            ></i>

            Secure admin authentication

        </div>


    </div>

</main>


<!-- =========================================================
     LOADING
========================================================= -->

<div
    class="loading-overlay"
    id="loadingOverlay"
>

    <div class="loading-card">


        <div class="loader-wrap">

            <div class="loader"></div>

            <div
                class="loader-star"
            >
                *****
            </div>

        </div>


        <h3 class="loading-title">

            Memproses login...

        </h3>


        <p class="loading-text">

            Memverifikasi akun kamu

        </p>


    </div>

</div>


<script>

/* =========================================================
   PASSWORD TOGGLE
========================================================= */

const passwordInput =
    document.getElementById(
        'password'
    );


const togglePassword =
    document.getElementById(
        'togglePassword'
    );


togglePassword.addEventListener(
    'click',
    function(){

        const icon =
            this.querySelector('i');


        if (
            passwordInput.type ===
            'password'
        ){

            passwordInput.type =
                'text';


            icon.className =
                'fa-solid fa-eye-slash';


            this.setAttribute(
                'aria-label',
                'Sembunyikan password'
            );

        } else {

            passwordInput.type =
                'password';


            icon.className =
                'fa-solid fa-eye';


            this.setAttribute(
                'aria-label',
                'Tampilkan password'
            );
        }

    }
);


/* =========================================================
   LOGIN LOADING
========================================================= */

const loginForm =
    document.getElementById(
        'loginForm'
    );


const loginButton =
    document.getElementById(
        'loginButton'
    );


const loginButtonContent =
    document.getElementById(
        'loginButtonContent'
    );


const loadingOverlay =
    document.getElementById(
        'loadingOverlay'
    );


let submitted = false;


loginForm.addEventListener(
    'submit',
    function(e){

        if (submitted) {

            e.preventDefault();

            return;
        }


        submitted = true;


        loginButton.disabled =
            true;


        loginButtonContent.innerHTML = `

            <i
                class="fa-solid
                fa-circle-notch
                fa-spin"
            ></i>

            Memproses...

        `;


        loadingOverlay.classList.add(
            'show'
        );

    }
);


/* =========================================================
   ENTER KEY FEEDBACK
========================================================= */

passwordInput.addEventListener(
    'keydown',
    function(e){

        if (
            e.key === 'Enter'
        ){

            if (
                !submitted &&
                loginForm.checkValidity()
            ){

                loginForm.requestSubmit();
            }

        }

    }
);


/* =========================================================
   PREVENT DOUBLE SUBMIT
========================================================= */

window.addEventListener(
    'pageshow',
    function(){

        submitted = false;

        loginButton.disabled =
            false;

        loadingOverlay.classList.remove(
            'show'
        );

    }
);

</script>


</body>

</html>