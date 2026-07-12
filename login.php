<?php
session_start();
require_once __DIR__ . '/koneksiweb.php';
require_once __DIR__ . '/csrf_helper.php';
verify_csrf_token();

function verifikasiPasswordHybrid($password_input, $password_database) {
    if (strpos($password_database, '$2y$') === 0) {
        return password_verify($password_input, $password_database);
    } else {
        return hash_equals($password_database, $password_input);
    }
}

/**
 * Menampilkan layar animasi "Welcome to SASLAB" full-screen,
 * lalu redirect otomatis ke dashboard sesuai role setelah animasi selesai.
 */
function tampilkan_welcome_screen($username, $redirect_url) {
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang - SASLAB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/saslab-design.css">
    <style>
        body {
            background-color: var(--background);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            transition: opacity 0.8s ease;
        }
        body.fade-out { opacity: 0; }

        .welcome-wrap { text-align: center; padding: 0 20px; }

        .welcome-sub {
            color: var(--muted);
            font-size: 0.95rem;
            letter-spacing: 4px;
            text-transform: uppercase;
            opacity: 0;
            animation: fadeUp 1.0s ease forwards;
            animation-delay: 0.3s;
        }

        .welcome-title {
            font-family: var(--font-heading);
            font-size: clamp(2rem, 6vw, 3.4rem);
            font-weight: 800;
            color: var(--on-surface);
            letter-spacing: -0.02em;
            margin-top: 12px;
            opacity: 0;
            transform: scale(0.95);
            animation: popIn 1.0s cubic-bezier(.25,1,.5,1) forwards;
            animation-delay: 0.6s;
        }

        .welcome-bar {
            width: 0;
            height: 4px;
            background-color: var(--on-background);
            margin: 28px auto 0;
            border-radius: 999px;
            animation: growBar 1.5s ease forwards;
            animation-delay: 1.2s;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes popIn {
            from { opacity: 0; transform: scale(0.95); }
            to   { opacity: 1; transform: scale(1); }
        }
        @keyframes growBar {
            from { width: 0; }
            to   { width: 120px; }
        }
    </style>
</head>
<body id="welcomeBody">
    <div class="welcome-wrap">
        <div class="welcome-sub label-caps">Selamat datang, <?= htmlspecialchars($username); ?></div>
        <div class="welcome-title">SASLAB</div>
        <div class="welcome-bar"></div>
    </div>

    <script>
        setTimeout(function () {
            document.getElementById('welcomeBody').classList.add('fade-out');
        }, 3000);

        setTimeout(function () {
            window.location.href = "<?= htmlspecialchars($redirect_url, ENT_QUOTES); ?>";
        }, 3800);
    </script>
</body>
</html>
    <?php
}

$is_logged_in = false;
$logged_in_role = '';
$logged_in_url = '';
$active_roles = [];

if (isset($_SESSION['Mahasiswa'])) $active_roles['Mahasiswa'] = "dashboard_mahasiswa.php";
if (isset($_SESSION['Admin Prodi'])) $active_roles['Admin Prodi'] = "dashboard_admin_prodi_km.php";
if (isset($_SESSION['Admin Lab'])) $active_roles['Admin Lab'] = "dashboard_admin_lab.php";

if (!empty($active_roles)) {
    $is_logged_in = true;
    $logged_in_role = array_key_first($active_roles);
    $logged_in_url = $active_roles[$logged_in_role];
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        $error_message = "Username/NIM dan kata sandi wajib diisi.";
    } else {
        $query = "SELECT id_user, username, password, role FROM akun WHERE username = ? LIMIT 1";
        $stmt = mysqli_prepare($koneksi, $query);
        if (!$stmt) {
            die("Terjadi kesalahan pada sistem autentikasi basis data.");
        }

        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $hasil = mysqli_stmt_get_result($stmt);

        if ($hasil && mysqli_num_rows($hasil) === 1) {
            $data = mysqli_fetch_assoc($hasil);

            if (verifikasiPasswordHybrid($password, $data['password'])) {
                
                // AUTO-UPGRADE hash
                if (strpos($data['password'], '$2y$') !== 0) {
                    $password_baru_hash = password_hash($password, PASSWORD_DEFAULT);
                    $id_user_login      = $data['id_user'];
                    mysqli_query($koneksi, "UPDATE akun SET password = '$password_baru_hash' WHERE id_user = '$id_user_login'");
                }

                session_regenerate_id(true);

                // Simpan data sesi dalam array berdasarkan role agar tidak saling menimpa
                $_SESSION[$data['role']] = [
                    'id_user'  => $data['id_user'],
                    'username' => $data['username']
                ];

                mysqli_stmt_close($stmt);

                // Tentukan URL dashboard sesuai role
                $redirect_map = [
                    'Mahasiswa'   => 'dashboard_mahasiswa.php',
                    'Admin Prodi' => 'dashboard_admin_prodi_km.php',
                    'Admin Lab'   => 'dashboard_admin_lab.php',
                ];
                $redirect_url = $redirect_map[$data['role']] ?? 'login.php';

                // Tampilkan animasi welcome full-screen, lalu redirect otomatis via JS
                // Untuk mahasiswa, gunakan nama lengkap; untuk admin, pakai nama dari tabel profil
                $display_name = $data['username'];
                if ($data['role'] === 'Mahasiswa') {
                    $q_nama = mysqli_query($koneksi, "SELECT nama_mahasiswa FROM mahasiswa WHERE id_user = " . (int)$data['id_user'] . " LIMIT 1");
                    $r_nama = mysqli_fetch_assoc($q_nama);
                    if ($r_nama && !empty($r_nama['nama_mahasiswa'])) {
                        $display_name = $r_nama['nama_mahasiswa'];
                    }
                } elseif ($data['role'] === 'Admin Lab') {
                    $q_nama = mysqli_query($koneksi, "SELECT nama_admin FROM admin_lab WHERE id_user = " . (int)$data['id_user'] . " LIMIT 1");
                    $r_nama = mysqli_fetch_assoc($q_nama);
                    if ($r_nama && !empty($r_nama['nama_admin'])) {
                        $display_name = $r_nama['nama_admin'];
                    }
                } elseif ($data['role'] === 'Admin Prodi') {
                    $q_nama = mysqli_query($koneksi, "SELECT nama_admin FROM admin_prodi WHERE id_user = " . (int)$data['id_user'] . " LIMIT 1");
                    $r_nama = mysqli_fetch_assoc($q_nama);
                    if ($r_nama && !empty($r_nama['nama_admin'])) {
                        $display_name = $r_nama['nama_admin'];
                    }
                }

                tampilkan_welcome_screen($display_name, $redirect_url);
                exit();
            } else {
                $error_message = "Username atau kata sandi salah.";
            }
        } else {
            $error_message = "Username atau kata sandi salah.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Login - Seleksi Aslab UINSU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/saslab-design.css">
    <style>
        body { 
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background-color: var(--background);
        }
        
        .login-container { 
            width: 100%;
            max-width: 440px; 
            padding: 20px;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .brand-header h1 {
            font-size: 2.5rem;
            margin-bottom: 4px;
        }
        .brand-header p {
            color: var(--muted);
            font-size: 0.95rem;
        }
        
        /* Floating Card animation */
        .sas-card {
            animation: floatIn 0.8s cubic-bezier(0.25, 1, 0.5, 1) forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        @keyframes floatIn {
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="container login-container">
    <div class="brand-header">
        <h1 class="fw-bold">SASLAB</h1>
        <p>Portal Seleksi Asisten Laboratorium</p>
    </div>

    <div class="sas-card hoverable">
        <h4 class="mb-4 fw-bold text-center">Sign In</h4>
        
        <?php if (!empty($error_message)) : ?>
            <div class="alert alert-danger sas-badge-danger py-2 d-flex align-items-center mb-4" role="alert" style="border: none; border-radius: 12px; font-weight: normal; text-transform: none; letter-spacing: normal;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?= csrf_field(); ?>
            <div class="mb-3">
                <label class="form-label label-caps text-muted mb-2">Username / NIM / NIP</label>
                <input type="text" name="username" class="form-control sas-input w-100" placeholder="NIM (Mahasiswa) / NIP (Admin)" required autocomplete="off">
            </div>
            
            <div class="mb-4">
                <label class="form-label label-caps text-muted mb-2">Password</label>
                <input type="password" name="password" class="form-control sas-input w-100" placeholder="Masukkan Password" required>
            </div>
            
            <button type="submit" class="btn btn-sas btn-sas-primary w-100 py-3">Log in to Account</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
