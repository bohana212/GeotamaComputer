GEOTAMA COMPUTER - PHP + JSON
=============================
Public:
  index.php       katalog untuk guest/pembeli
  notify.php      endpoint internal (dipanggil JS index.php) untuk
                   kirim notifikasi Telegram saat ada aktivitas
                   klik "Tanya/Pesan" produk

Admin:
  login.php       login
  dashboard.php   dashboard
  edit.php        tambah/edit/hapus produk
  setting.php     profile toko, profile admin, role & status akun,
                   integrasi Bot Telegram, buat akun admin (+ OTP)
  logout.php      logout

Data:
  data/products.json
  data/users.json
  data/settings.json

Integrasi Bot Telegram:
  Diatur di halaman Setting > "Integrasi Bot Telegram" (khusus
  super_admin). Butuh Bot Token (dari @BotFather) dan Chat ID
  (dari @userinfobot). Setelah aktif, sistem otomatis mengirim
  notifikasi Telegram untuk:
    - Error/kerusakan fatal di server (otomatis, seluruh halaman)
    - Aktivitas pembelian (klik tombol Tanya/Pesan di katalog)
    - Kode OTP + konfirmasi setiap ada pembuatan akun admin baru
  Selama Bot Telegram belum diisi, pembuatan akun admin tetap bisa
  langsung dilakukan (tanpa OTP) sebagai fallback.

Lokasi Toko:
  Bisa diisi 2 alamat (Lokasi 1 & Lokasi 2) lewat Setting > Profile
  Toko. Keduanya otomatis tampil di halaman "Tentang" katalog dan
  bisa diklik langsung ke Google Maps.

Akun awal:
  username: superadmin
  password: admin123
  Segera ganti password lewat Setting.

Role:
  guest/pembeli : pengunjung publik, hanya melihat
  admin         : kelola produk setelah login
  verified_admin: kelola produk + setting/profile
  super_admin   : semua akses + kelola akun admin

Keamanan:
  Session login, password_hash/password_verify, CSRF token, dan JSON file locking.
  Pastikan folder data tidak dapat diakses langsung dari web. Untuk Apache, gunakan .htaccess.
  Gunakan HTTPS untuk website online.

Kebutuhan:
  PHP 7.4+ (disarankan PHP 8.x), Apache/Nginx dengan PHP.
