<?php

require __DIR__ . '/common.php';

require_role([
    'verified_admin',
    'super_admin'
]);

$users = read_json(USERS_FILE, []);

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    check_csrf();

    $a = $_POST['action'] ?? '';

    /*
    =========================================================
    PROFILE TOKO
    =========================================================
    */

    if ($a === 'store') {

        $S = array_merge($S, [

            'store_name' => trim(
                $_POST['store_name'] ?? ''
            ),

            'address' => trim(
                $_POST['address'] ?? ''
            ),

            'address2' => trim(
                $_POST['address2'] ?? ''
            ),

            'phone' => preg_replace(
                '/\D/',
                '',
                $_POST['phone'] ?? ''
            ),

            'email' => trim(
                $_POST['email'] ?? ''
            ),

            'contact_admin' => trim(
                $_POST['contact_admin'] ?? ''
            ),

            'bio' => trim(
                $_POST['bio'] ?? ''
            ),

            'profile_image' => trim(
                $_POST['profile_image'] ?? ''
            ),

            'instagram' => trim(
                $_POST['instagram'] ?? ''
            ),

            'facebook' => trim(
                $_POST['facebook'] ?? ''
            ),

            'tiktok' => trim(
                $_POST['tiktok'] ?? ''
            ),

            'whatsapp' => preg_replace(
                '/\D/',
                '',
                $_POST['whatsapp'] ?? ''
            )

        ]);

        write_json(
            SETTINGS_FILE,
            $S
        );

        $msg = 'Profile toko berhasil diperbarui.';

    }

    /*
    =========================================================
    PROFILE USER
    =========================================================
    */

    elseif ($a === 'profile') {

        $uid = (int)user()['id'];

        foreach ($users as &$u) {

            if (
                (int)($u['id'] ?? 0) === $uid
            ) {

                $u['name'] = trim(
                    $_POST['name']
                    ?? $u['name']
                );

                $u['email'] = trim(
                    $_POST['email']
                    ?? $u['email']
                );

                if (
                    !empty(
                        $_POST['password']
                    )
                ) {

                    $u['password'] =
                        password_hash(
                            $_POST['password'],
                            PASSWORD_DEFAULT
                        );

                }

                $_SESSION['user']['name'] =
                    $u['name'];

                $_SESSION['user']['email'] =
                    $u['email'];
            }
        }

        unset($u);

        write_json(
            USERS_FILE,
            $users
        );

        $msg = 'Profile akun diperbarui.';

    }

    /*
    =========================================================
    EDIT ADMIN
    =========================================================
    */

    elseif (
        $a === 'admin' &&
        user()['role'] === 'super_admin'
    ) {

        $rid = (int)(
            $_POST['id'] ?? 0
        );

        foreach ($users as &$u) {

            if (
                (int)($u['id'] ?? 0) === $rid
            ) {

                $u['name'] = trim(
                    $_POST['name']
                    ?? $u['name']
                );

                $u['email'] = trim(
                    $_POST['email']
                    ?? $u['email']
                );

                $u['role'] =
                    in_array(
                        $_POST['role'] ?? '',
                        [
                            'admin',
                            'verified_admin',
                            'super_admin'
                        ],
                        true
                    )
                    ? $_POST['role']
                    : 'admin';

                $u['verified'] =
                    isset($_POST['verified']);

                $u['status'] =
                    ($_POST['status'] ?? '') === 'active'
                    ? 'active'
                    : 'disabled';

                if (
                    !empty(
                        $_POST['password']
                    )
                ) {

                    $u['password'] =
                        password_hash(
                            $_POST['password'],
                            PASSWORD_DEFAULT
                        );

                }
            }
        }

        unset($u);

        write_json(
            USERS_FILE,
            $users
        );

        $msg = 'Admin diperbarui.';

    }

    /*
    =========================================================
    BUAT ADMIN (LANGKAH 1: KIRIM OTP KE TELEGRAM)

    Kalau Bot Telegram sudah dikonfigurasi, akun TIDAK
    langsung dibuat. Sistem akan mengirim kode OTP ke
    Telegram admin, dan akun baru dibuat setelah OTP
    dikonfirmasi (lihat action "confirm_new_admin").

    Kalau Telegram belum dikonfigurasi, akun tetap bisa
    dibuat langsung seperti sebelumnya (fallback).
    =========================================================
    */

    elseif (
        $a === 'new_admin' &&
        user()['role'] === 'super_admin'
    ) {

        $un = trim(
            $_POST['username'] ?? ''
        );

        $pw =
            $_POST['password'] ?? '';

        if (
            $un &&
            strlen($pw) >= 6
        ) {

            $pendingUser = [
                'id' => time(),
                'username' => $un,
                'email' => trim($_POST['email'] ?? ''),
                'name' => trim($_POST['name'] ?? $un),
                'password' => password_hash($pw, PASSWORD_DEFAULT),
                'role' => 'admin',
                'verified' => false,
                'status' => 'active'
            ];

            if (telegram_configured()) {

                $otp = (string)random_int(100000, 999999);

                $_SESSION['pending_admin'] = [
                    'data'    => $pendingUser,
                    'otp'     => $otp,
                    'expires' => time() + 300
                ];

                telegram_send(
                    "🔐 *Kode OTP Pembuatan Akun Admin*\n\n" .
                    "Username baru: {$un}\n" .
                    "Diminta oleh: " . (user()['username'] ?? '-') . "\n\n" .
                    "Kode OTP: *{$otp}*\n" .
                    "Berlaku 5 menit. Masukkan kode ini di halaman Setting untuk konfirmasi."
                );

                $msg = 'Kode OTP telah dikirim ke Telegram. Masukkan kode untuk mengonfirmasi pembuatan akun.';

            } else {

                $users[] = $pendingUser;

                write_json(USERS_FILE, $users);

                $msg = 'Akun admin berhasil dibuat. (Hubungkan Bot Telegram di panel di bawah agar pembuatan akun berikutnya diamankan dengan OTP.)';
            }

        } else {

            $err =
                'Username wajib dan password minimal 6 karakter.';

        }

    }

    /*
    =========================================================
    BUAT ADMIN (LANGKAH 2: KONFIRMASI OTP)
    =========================================================
    */

    elseif (
        $a === 'confirm_new_admin' &&
        user()['role'] === 'super_admin'
    ) {

        $pending = $_SESSION['pending_admin'] ?? null;

        $otpInput = trim((string)($_POST['otp'] ?? ''));

        if (!$pending) {

            $err = 'Tidak ada proses pembuatan akun yang menunggu konfirmasi.';

        } elseif (time() > ($pending['expires'] ?? 0)) {

            unset($_SESSION['pending_admin']);

            $err = 'Kode OTP sudah kadaluarsa. Silakan buat ulang akunnya.';

        } elseif (!hash_equals((string)$pending['otp'], $otpInput)) {

            $err = 'Kode OTP salah. Coba periksa kembali pesan Telegram.';

        } else {

            $users[] = $pending['data'];

            write_json(USERS_FILE, $users);

            telegram_send(
                "✅ *Akun Admin Baru Dibuat*\n\n" .
                "Username: " . ($pending['data']['username'] ?? '-') . "\n" .
                "Nama: " . ($pending['data']['name'] ?? '-') . "\n" .
                "Waktu: " . date('d-m-Y H:i:s')
            );

            unset($_SESSION['pending_admin']);

            $msg = 'OTP benar. Akun admin baru berhasil dibuat.';
        }

    }

    /*
    =========================================================
    BATALKAN PROSES OTP
    =========================================================
    */

    elseif ($a === 'cancel_new_admin') {

        unset($_SESSION['pending_admin']);

        $msg = 'Proses pembuatan akun dibatalkan.';

    }

    /*
    =========================================================
    INTEGRASI BOT TELEGRAM
    =========================================================
    */

    elseif (
        $a === 'telegram' &&
        user()['role'] === 'super_admin'
    ) {

        $S = array_merge($S, [

            'telegram_bot_token' => trim(
                $_POST['telegram_bot_token'] ?? ''
            ),

            'telegram_chat_id' => trim(
                $_POST['telegram_chat_id'] ?? ''
            ),

        ]);

        write_json(SETTINGS_FILE, $S);

        $msg = 'Pengaturan Bot Telegram berhasil disimpan.';

    }

    /*
    =========================================================
    TES KIRIM NOTIFIKASI TELEGRAM
    =========================================================
    */

    elseif (
        $a === 'telegram_test' &&
        user()['role'] === 'super_admin'
    ) {

        if (telegram_configured()) {

            $ok = telegram_send(
                "✅ Tes notifikasi berhasil dari panel Setting " .
                ($S['store_name'] ?? 'Geotama Computer') . ".\n" .
                "Waktu: " . date('d-m-Y H:i:s')
            );

            $msg = $ok
                ? 'Pesan tes berhasil dikirim ke Telegram. Cek chat bot kamu.'
                : '';

            $err = $ok
                ? ''
                : 'Gagal mengirim pesan: ' . telegram_last_error();

        } else {

            $err = 'Isi Bot Token & Chat ID terlebih dahulu.';

        }

    }

}

?>

<!doctype html>

<html lang="id">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>
    Setting — <?= h($S['store_name'] ?? 'Geotama Computer') ?>
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
   PROFILE PREVIEW
========================================================= */

.store-profile-preview{
    display:flex;
    align-items:center;
    gap:20px;
    padding:20px;
    margin-bottom:22px;

    border:1px solid rgba(0,0,0,.07);
    border-radius:20px;

    background:
        linear-gradient(
            135deg,
            rgba(255,122,0,.08),
            rgba(255,255,255,.8)
        );
}

.store-avatar{
    width:90px;
    height:90px;
    flex:0 0 90px;

    border-radius:24px;

    overflow:hidden;

    display:flex;
    align-items:center;
    justify-content:center;

    background:#fff1e8;
    color:#ff7a00;

    font-size:32px;

    border:2px solid
        rgba(255,122,0,.15);

    box-shadow:
        0 10px 30px
        rgba(0,0,0,.08);
}

.store-avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.store-profile-preview h3{
    margin:0 0 5px;
}

.store-profile-preview p{
    margin:0;
}

/* =========================================================
   SOCIAL INPUT
========================================================= */

.social-input{
    position:relative;
}

.social-input > i{
    position:absolute;

    left:15px;
    top:50%;

    transform:
        translateY(-50%);

    color:#999;

    pointer-events:none;
}

.social-input input{
    padding-left:43px;
}

/* =========================================================
   BIO
========================================================= */

textarea{
    width:100%;
    min-height:120px;
    resize:vertical;
    font-family:inherit;
}

/* =========================================================
   SOCIAL PREVIEW
========================================================= */

.social-preview{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-top:18px;
}

.social-chip{
    display:inline-flex;
    align-items:center;
    gap:8px;

    padding:9px 13px;

    border-radius:999px;

    background:#f5f5f5;

    color:#444;

    font-size:13px;
    font-weight:600;
}

.social-chip i{
    color:#ff7a00;
}

/* =========================================================
   MOBILE
========================================================= */

@media(max-width:650px){

    .store-profile-preview{
        align-items:flex-start;
        flex-direction:column;
    }

    .store-avatar{
        width:75px;
        height:75px;
        flex-basis:75px;
    }

}

</style>

</head>

<body>

<header class="topbar">

    <div class="container nav">

        <a
            class="brand"
            href="dashboard.php"
        >

            <div class="logo">
                <i class="fa-solid fa-microchip"></i>
            </div>

            <div>

                <strong>
                    <?= h($S['store_name'] ?? 'Geotama Computer') ?>
                </strong>

                <small>
                    SETTINGS
                </small>

            </div>

        </a>

        <nav class="navlinks">

            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="edit.php">
                Produk
            </a>

            <a href="index.php">
                Toko
            </a>

        </nav>

    </div>

</header>


<section class="section">

<div class="container">

    <div class="title-row">

        <div>

            <h2>
                SETTING PROFILE
            </h2>

            <div class="muted">
                Kelola identitas toko, kontak,
                sosial media, dan akun administrator.
            </div>

        </div>

    </div>


    <?php if ($msg): ?>

        <div class="notice">
            <i class="fa-solid fa-circle-check"></i>
            <?= h($msg) ?>
        </div>

    <?php endif; ?>


    <?php if ($err): ?>

        <div class="error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= h($err) ?>
        </div>

    <?php endif; ?>


    <!-- =====================================================
         PROFILE TOKO
    ====================================================== -->

    <div class="panel">

        <h3>
            <i class="fa-solid fa-store"></i>
            Profile Toko
        </h3>

        <p class="muted">
            Informasi ini dapat digunakan pada halaman
            katalog publik Geotama Computer.
        </p>


        <!-- PREVIEW -->

        <div class="store-profile-preview">

            <div class="store-avatar">

                <?php if (
                    !empty(
                        $S['profile_image']
                        ?? ''
                    )
                ): ?>

                    <img
                        src="<?= h($S['profile_image']) ?>"
                        alt="Profile toko"
                        onerror="
                            this.style.display='none';
                            this.nextElementSibling.style.display='flex';
                        "
                    >

                    <i
                        class="fa-solid fa-store"
                        style="display:none"
                    ></i>

                <?php else: ?>

                    <i class="fa-solid fa-store"></i>

                <?php endif; ?>

            </div>


            <div>

                <h3>
                    <?= h(
                        $S['store_name']
                        ?? 'Geotama Computer'
                    ) ?>
                </h3>

                <p class="muted">

                    <?= h(
                        $S['bio']
                        ?? 'Computer Store'
                    ) ?>

                </p>

            </div>

        </div>


        <form method="post">

            <input
                type="hidden"
                name="csrf"
                value="<?= h(csrf()) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="store"
            >


            <div class="form-grid">


                <div class="field">

                    <label>
                        Nama Toko
                    </label>

                    <input
                        name="store_name"
                        value="<?= h(
                            $S['store_name']
                            ?? ''
                        ) ?>"
                        placeholder="Geotama Computer"
                        required
                    >

                </div>


                <div class="field">

                    <label>
                        Email
                    </label>

                    <input
                        name="email"
                        type="email"
                        value="<?= h(
                            $S['email']
                            ?? ''
                        ) ?>"
                        placeholder="email@toko.com"
                    >

                </div>


                <div class="field full">

                    <label>
                        Bio / Deskripsi Toko
                    </label>

                    <textarea
                        name="bio"
                        placeholder="Contoh: Geotama Computer menyediakan laptop, PC rakitan, printer, networking, CCTV dan sparepart komputer."
                    ><?= h(
                        $S['bio']
                        ?? ''
                    ) ?></textarea>

                </div>


                <div class="field full">

                    <label>
                        URL Foto Profile / Logo
                    </label>

                    <input
                        name="profile_image"
                        type="url"
                        value="<?= h(
                            $S['profile_image']
                            ?? ''
                        ) ?>"
                        placeholder="https://domain.com/logo.png"
                    >

                    <small class="muted">
                        Gunakan URL gambar langsung
                        agar ringan dan kompatibel dengan hosting.
                    </small>

                </div>


                <div class="field full">

                    <label>
                        Alamat Toko (Lokasi 1)
                    </label>

                    <input
                        name="address"
                        value="<?= h(
                            $S['address']
                            ?? ''
                        ) ?>"
                        placeholder="Alamat lengkap toko"
                    >

                </div>


                <div class="field full">

                    <label>
                        Alamat Toko (Lokasi 2) — opsional
                    </label>

                    <input
                        name="address2"
                        value="<?= h(
                            $S['address2']
                            ?? ''
                        ) ?>"
                        placeholder="Cabang kedua / lokasi tambahan (kosongkan jika tidak ada)"
                    >

                    <small class="muted">
                        Kalau diisi, akan tampil sebagai lokasi kedua
                        di halaman "Tentang" katalog dan bisa
                        diklik langsung ke Google Maps.
                    </small>

                </div>


                <div class="field">

                    <label>
                        No. Telepon
                    </label>

                    <input
                        name="phone"
                        value="<?= h(
                            $S['phone']
                            ?? ''
                        ) ?>"
                        placeholder="08xxxxxxxxxx"
                    >

                </div>


                <div class="field">

                    <label>
                        Contact Admin
                    </label>

                    <input
                        name="contact_admin"
                        value="<?= h(
                            $S['contact_admin']
                            ?? ''
                        ) ?>"
                        placeholder="Nama admin"
                    >

                </div>


                <!-- WHATSAPP -->

                <div class="field">

                    <label>
                        WhatsApp
                    </label>

                    <div class="social-input">

                        <i class="fa-brands fa-whatsapp"></i>

                        <input
                            name="whatsapp"
                            value="<?= h(
                                $S['whatsapp']
                                ?? ''
                            ) ?>"
                            placeholder="628xxxxxxxxxx"
                        >

                    </div>

                </div>


                <!-- INSTAGRAM -->

                <div class="field">

                    <label>
                        Instagram
                    </label>

                    <div class="social-input">

                        <i class="fa-brands fa-instagram"></i>

                        <input
                            name="instagram"
                            value="<?= h(
                                $S['instagram']
                                ?? ''
                            ) ?>"
                            placeholder="@geotamacomputer"
                        >

                    </div>

                </div>


                <!-- FACEBOOK -->

                <div class="field">

                    <label>
                        Facebook
                    </label>

                    <div class="social-input">

                        <i class="fa-brands fa-facebook"></i>

                        <input
                            name="facebook"
                            value="<?= h(
                                $S['facebook']
                                ?? ''
                            ) ?>"
                            placeholder="https://facebook.com/..."
                        >

                    </div>

                </div>


                <!-- TIKTOK -->

                <div class="field">

                    <label>
                        TikTok
                    </label>

                    <div class="social-input">

                        <i class="fa-brands fa-tiktok"></i>

                        <input
                            name="tiktok"
                            value="<?= h(
                                $S['tiktok']
                                ?? ''
                            ) ?>"
                            placeholder="@geotamacomputer"
                        >

                    </div>

                </div>


            </div>


            <!-- SOCIAL PREVIEW -->

            <div class="social-preview">

                <?php if (
                    !empty($S['instagram'] ?? '')
                ): ?>

                    <span class="social-chip">
                        <i class="fa-brands fa-instagram"></i>
                        Instagram
                    </span>

                <?php endif; ?>


                <?php if (
                    !empty($S['whatsapp'] ?? '')
                ): ?>

                    <span class="social-chip">
                        <i class="fa-brands fa-whatsapp"></i>
                        WhatsApp
                    </span>

                <?php endif; ?>


                <?php if (
                    !empty($S['facebook'] ?? '')
                ): ?>

                    <span class="social-chip">
                        <i class="fa-brands fa-facebook"></i>
                        Facebook
                    </span>

                <?php endif; ?>


                <?php if (
                    !empty($S['tiktok'] ?? '')
                ): ?>

                    <span class="social-chip">
                        <i class="fa-brands fa-tiktok"></i>
                        TikTok
                    </span>

                <?php endif; ?>

            </div>


            <div class="form-actions">

                <button
                    class="btn btn-primary"
                    type="submit"
                >

                    <i class="fa-solid fa-floppy-disk"></i>

                    Simpan Profile Toko

                </button>

            </div>

        </form>

    </div>


    <!-- =====================================================
         PROFILE SAYA
    ====================================================== -->

    <div
        class="panel"
        style="margin-top:18px"
    >

        <h3>
            <i class="fa-solid fa-user"></i>
            Profile Saya
        </h3>

        <p class="muted">
            Kelola informasi akun administrator yang sedang login.
        </p>


        <form method="post">

            <input
                type="hidden"
                name="csrf"
                value="<?= h(csrf()) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="profile"
            >


            <div class="form-grid">


                <div class="field">

                    <label>
                        Nama
                    </label>

                    <input
                        name="name"
                        value="<?= h(
                            user()['name']
                            ?? ''
                        ) ?>"
                        required
                    >

                </div>


                <div class="field">

                    <label>
                        Email
                    </label>

                    <input
                        name="email"
                        type="email"
                        value="<?= h(
                            user()['email']
                            ?? ''
                        ) ?>"
                    >

                </div>


                <div class="field full">

                    <label>
                        Password Baru
                    </label>

                    <input
                        name="password"
                        type="password"
                        minlength="6"
                        placeholder="Kosongkan jika tidak ingin mengubah password"
                    >

                </div>


            </div>


            <div class="form-actions">

                <button
                    class="btn btn-primary"
                    type="submit"
                >

                    <i class="fa-solid fa-user-pen"></i>

                    Update Profile

                </button>

            </div>

        </form>

    </div>


    <?php if (
        user()['role'] === 'super_admin'
    ): ?>


    <!-- =====================================================
         ADMIN MANAGEMENT
    ====================================================== -->

    <div
        class="panel"
        style="margin-top:18px"
    >

        <h3>
            <i class="fa-solid fa-users-gear"></i>
            Admin / Verified / Super Admin
        </h3>


        <div class="table-wrap">

            <table class="table">

                <tr>

                    <th>
                        Username
                    </th>

                    <th>
                        Nama & Email
                    </th>

                    <th>
                        Role
                    </th>

                    <th>
                        Verified
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Simpan
                    </th>

                </tr>


                <?php foreach (
                    $users as $u
                ): ?>

                <tr>

                    <form method="post">

                        <input
                            type="hidden"
                            name="csrf"
                            value="<?= h(csrf()) ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="admin"
                        >

                        <input
                            type="hidden"
                            name="id"
                            value="<?= h(
                                (string)(
                                    $u['id']
                                    ?? ''
                                )
                            ) ?>"
                        >


                        <td>

                            <?= h(
                                $u['username']
                                ?? ''
                            ) ?>

                        </td>


                        <td>

                            <input
                                name="name"
                                value="<?= h(
                                    $u['name']
                                    ?? ''
                                ) ?>"
                            >

                            <input
                                name="email"
                                value="<?= h(
                                    $u['email']
                                    ?? ''
                                ) ?>"
                            >

                        </td>


                        <td>

                            <select name="role">

                                <option
                                    value="admin"
                                    <?= (
                                        ($u['role'] ?? '')
                                        === 'admin'
                                    )
                                    ? 'selected'
                                    : ''
                                    ?>
                                >
                                    Admin
                                </option>

                                <option
                                    value="verified_admin"
                                    <?= (
                                        ($u['role'] ?? '')
                                        === 'verified_admin'
                                    )
                                    ? 'selected'
                                    : ''
                                    ?>
                                >
                                    Verified Admin
                                </option>

                                <option
                                    value="super_admin"
                                    <?= (
                                        ($u['role'] ?? '')
                                        === 'super_admin'
                                    )
                                    ? 'selected'
                                    : ''
                                    ?>
                                >
                                    Super Admin
                                </option>

                            </select>

                        </td>


                        <td>

                            <input
                                type="checkbox"
                                name="verified"
                                <?= !empty(
                                    $u['verified']
                                )
                                ? 'checked'
                                : ''
                                ?>
                            >

                        </td>


                        <td>

                            <select name="status">

                                <option
                                    value="active"
                                    <?= (
                                        ($u['status'] ?? '')
                                        === 'active'
                                    )
                                    ? 'selected'
                                    : ''
                                    ?>
                                >
                                    Active
                                </option>

                                <option
                                    value="disabled"
                                    <?= (
                                        ($u['status'] ?? '')
                                        === 'disabled'
                                    )
                                    ? 'selected'
                                    : ''
                                    ?>
                                >
                                    Disabled
                                </option>

                            </select>

                        </td>


                        <td>

                            <input
                                name="password"
                                type="password"
                                placeholder="Password baru"
                            >

                            <button
                                class="btn btn-primary"
                                type="submit"
                            >
                                Simpan
                            </button>

                        </td>

                    </form>

                </tr>

                <?php endforeach; ?>

            </table>

        </div>

    </div>


    <!-- =====================================================
         CREATE ADMIN
    ====================================================== -->

    <div
        class="panel"
        style="margin-top:18px"
    >

        <h3>
            <i class="fa-solid fa-user-plus"></i>
            Buat Akun Admin
        </h3>

        <?php if (telegram_configured()): ?>

            <p class="muted">
                <i class="fa-solid fa-shield-halved"></i>
                Pembuatan akun dilindungi OTP yang dikirim ke Telegram.
            </p>

        <?php else: ?>

            <p class="muted">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Bot Telegram belum terhubung — akun akan dibuat langsung
                tanpa OTP. Hubungkan di panel "Integrasi Bot Telegram"
                di bawah untuk keamanan ekstra.
            </p>

        <?php endif; ?>


        <?php if (!empty($_SESSION['pending_admin'])): ?>

            <!-- =================================================
                 KONFIRMASI OTP
            ================================================== -->

            <div class="notice" style="margin-bottom:16px;">
                <i class="fa-solid fa-paper-plane"></i>
                Kode OTP sudah dikirim ke Telegram untuk akun
                "<?= h($_SESSION['pending_admin']['data']['username'] ?? '') ?>".
                Masukkan kode di bawah (berlaku 5 menit).
            </div>

            <form method="post">

                <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
                <input type="hidden" name="action" value="confirm_new_admin">

                <div class="form-grid">

                    <div class="field">
                        <label>Kode OTP</label>
                        <input
                            name="otp"
                            inputmode="numeric"
                            maxlength="6"
                            placeholder="123456"
                            autofocus
                            required
                        >
                    </div>

                </div>

                <div class="form-actions">

                    <button class="btn btn-primary" type="submit">
                        <i class="fa-solid fa-check"></i>
                        Konfirmasi OTP
                    </button>

                    <button
                        class="btn btn-dark"
                        type="submit"
                        name="action"
                        value="cancel_new_admin"
                        formnovalidate
                    >
                        <i class="fa-solid fa-xmark"></i>
                        Batalkan
                    </button>

                </div>

            </form>

        <?php else: ?>

            <form method="post">

                <input
                    type="hidden"
                    name="csrf"
                    value="<?= h(csrf()) ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="new_admin"
                >


                <div class="form-grid">


                    <div class="field">

                        <label>
                            Username
                        </label>

                        <input
                            name="username"
                            required
                        >

                    </div>


                    <div class="field">

                        <label>
                            Nama
                        </label>

                        <input
                            name="name"
                            required
                        >

                    </div>


                    <div class="field">

                        <label>
                            Email
                        </label>

                        <input
                            name="email"
                            type="email"
                        >

                    </div>


                    <div class="field">

                        <label>
                            Password
                        </label>

                        <input
                            name="password"
                            type="password"
                            minlength="6"
                            required
                        >

                    </div>


                </div>


                <div class="form-actions">

                    <button
                        class="btn btn-primary"
                        type="submit"
                    >

                        <i class="fa-solid fa-user-plus"></i>

                        Buat Admin

                    </button>

                </div>

            </form>

        <?php endif; ?>

    </div>


    <!-- =====================================================
         INTEGRASI BOT TELEGRAM
    ====================================================== -->

    <div
        class="panel"
        style="margin-top:18px"
    >

        <h3>
            <i class="fa-brands fa-telegram"></i>
            Integrasi Bot Telegram
        </h3>

        <p class="muted">
            Dipakai untuk mengirim notifikasi otomatis ke Telegram saat:
            ada error/kerusakan di server, ada aktivitas pembelian
            (klik "Tanya/Pesan" di katalog), dan saat ada pembuatan
            akun admin baru (sekaligus untuk kirim kode OTP-nya).
        </p>

        <p class="muted">
            Cara setup singkat: (1) buat bot baru lewat
            <b>@BotFather</b> di Telegram, salin <b>Bot Token</b>
            yang diberikan. (2) <b>WAJIB</b> chat bot barunya dulu
            dan tekan tombol <b>Start</b> (Telegram tidak izinkan
            bot mengirim pesan duluan ke orang yang belum pernah
            chat dia). (3) buka <b>@userinfobot</b> untuk melihat
            <b>Chat ID</b> kamu (atau ID grup jika notifikasi ingin
            masuk ke grup — bot juga harus sudah masuk ke grup itu).
            (4) isi Bot Token & Chat ID di bawah lalu simpan, terakhir
            klik "Kirim Tes Notifikasi".
        </p>

        <p class="muted">
            <i class="fa-solid fa-circle-info"></i>
            Kalau tes gagal padahal token & chat id sudah benar,
            penyebab paling sering adalah langkah (2) di atas
            terlewat. Pesan error di bawah tombol tes akan
            menampilkan alasan pastinya dari Telegram.
        </p>

        <form method="post">

            <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
            <input type="hidden" name="action" value="telegram">

            <div class="form-grid">

                <div class="field full">

                    <label>Bot Token</label>

                    <input
                        name="telegram_bot_token"
                        value="<?= h($S['telegram_bot_token'] ?? '') ?>"
                        placeholder="123456789:AAExxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                        autocomplete="off"
                    >

                </div>

                <div class="field">

                    <label>Chat ID</label>

                    <input
                        name="telegram_chat_id"
                        value="<?= h($S['telegram_chat_id'] ?? '') ?>"
                        placeholder="123456789"
                        autocomplete="off"
                    >

                </div>

            </div>

            <div class="form-actions">

                <button class="btn btn-primary" type="submit">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Simpan Pengaturan Telegram
                </button>

            </div>

        </form>

        <form method="post" style="margin-top:10px;">

            <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
            <input type="hidden" name="action" value="telegram_test">

            <div class="form-actions">

                <button class="btn btn-outline" type="submit">
                    <i class="fa-solid fa-paper-plane"></i>
                    Kirim Tes Notifikasi
                </button>

            </div>

        </form>

        <?php if (telegram_configured()): ?>

            <div class="social-preview">
                <span class="social-chip">
                    <i class="fa-solid fa-circle-check" style="color:#22c55e"></i>
                    Bot Telegram Terhubung
                </span>
            </div>

        <?php else: ?>

            <div class="social-preview">
                <span class="social-chip">
                    <i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i>
                    Bot Telegram Belum Terhubung
                </span>
            </div>

        <?php endif; ?>


        <!-- =================================================
             DIAGNOSTIK KONEKSI SERVER
        ================================================== -->

        <?php
        $hasCurl   = function_exists('curl_init');
        $hasFopen  = (bool) ini_get('allow_url_fopen');
        $hasOpenssl = extension_loaded('openssl');
        ?>

        <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--line);">

            <p class="muted" style="margin-bottom:10px;">
                <i class="fa-solid fa-server"></i>
                Diagnostik server (kalau "Kirim Tes Notifikasi" gagal
                terus padahal token & chat id sudah benar, cek di sini —
                biasanya hosting yang memblokir koneksi keluar):
            </p>

            <div class="social-preview" style="flex-wrap:wrap;">

                <span class="social-chip">
                    <i class="fa-solid <?= $hasCurl ? 'fa-circle-check' : 'fa-circle-xmark' ?>"
                       style="color:<?= $hasCurl ? '#22c55e' : '#ef4444' ?>"></i>
                    Ekstensi cURL: <?= $hasCurl ? 'Aktif' : 'Tidak Aktif' ?>
                </span>

                <span class="social-chip">
                    <i class="fa-solid <?= $hasFopen ? 'fa-circle-check' : 'fa-circle-xmark' ?>"
                       style="color:<?= $hasFopen ? '#22c55e' : '#ef4444' ?>"></i>
                    allow_url_fopen: <?= $hasFopen ? 'Aktif' : 'Tidak Aktif' ?>
                </span>

                <span class="social-chip">
                    <i class="fa-solid <?= $hasOpenssl ? 'fa-circle-check' : 'fa-circle-xmark' ?>"
                       style="color:<?= $hasOpenssl ? '#22c55e' : '#ef4444' ?>"></i>
                    OpenSSL: <?= $hasOpenssl ? 'Aktif' : 'Tidak Aktif' ?>
                </span>

            </div>

            <?php if (!$hasCurl && !$hasFopen): ?>

                <p class="muted" style="margin-top:8px;color:#ef4444;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    cURL dan allow_url_fopen dua-duanya mati. PHP di
                    hosting kamu tidak bisa mengakses internet sama
                    sekali, ini penyebab notifikasi selalu gagal.
                    Hubungi provider hosting untuk mengaktifkan salah
                    satunya (biasanya lewat pengaturan PHP / php.ini
                    di cPanel, atau ganti versi PHP).
                </p>

            <?php elseif (!$hasOpenssl): ?>

                <p class="muted" style="margin-top:8px;color:#ef4444;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    Ekstensi OpenSSL tidak aktif, sehingga koneksi HTTPS
                    ke Telegram tidak bisa dibuat. Hubungi provider
                    hosting untuk mengaktifkan ekstensi OpenSSL di PHP.
                </p>

            <?php else: ?>

                <p class="muted" style="margin-top:8px;">
                    <i class="fa-solid fa-circle-info"></i>
                    Semua ekstensi yang dibutuhkan sudah aktif. Kalau
                    tes masih gagal, kemungkinan besar firewall hosting
                    memblokir koneksi keluar ke domain
                    <b>api.telegram.org</b> secara khusus — ini perlu
                    diwhitelist oleh provider hosting kamu.
                </p>

            <?php endif; ?>

        </div>

    </div>


    <?php endif; ?>


</div>

</section>

</body>

</html>