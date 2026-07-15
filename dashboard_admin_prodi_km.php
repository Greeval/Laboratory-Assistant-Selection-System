<?php
// Inisialisasi sesi & koneksi
session_start();
require_once __DIR__ . '/koneksiweb.php';
require_once __DIR__ . '/csrf_helper.php';
verify_csrf_token();

// PROTEKSI SESI: Hanya 'Admin Prodi' yang boleh mengakses
if (empty($_SESSION['Admin Prodi']['id_user'])) {
    header("Location: login.php");
    exit();
}

// Ambil data sesi pengguna yang sedang login
$id_user_login   = $_SESSION['Admin Prodi']['id_user'];
$username_login  = $_SESSION['Admin Prodi']['username'];

// Proses tambah akun (create)
if (isset($_POST['tambah_akun'])) {
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $password = $_POST['password'];
    $role     = mysqli_real_escape_string($koneksi, $_POST['role']);
    $admin_password = $_POST['admin_password'];

    // Verifikasi password Admin Prodi
    $q_cek_admin = mysqli_query($koneksi, "SELECT password FROM akun WHERE id_user = $id_user_login LIMIT 1");
    if ($q_cek_admin && mysqli_num_rows($q_cek_admin) > 0) {
        $dt_admin = mysqli_fetch_assoc($q_cek_admin);
        $pw_valid = false;
        if (strpos($dt_admin['password'], '$2y$') === 0) {
            $pw_valid = password_verify($admin_password, $dt_admin['password']);
        } else {
            $pw_valid = hash_equals($dt_admin['password'], $admin_password);
        }
        if (!$pw_valid) {
            echo "<script>alert('Password Admin Prodi salah! Akun gagal ditambahkan.'); window.location.href='dashboard_admin_prodi_km.php';</script>";
            exit();
        }
    }

    // Validasi Anti-Duplikasi
    $cek = mysqli_query($koneksi, "SELECT id_user FROM akun WHERE username='$username'");
    if (mysqli_num_rows($cek) > 0) {
        header("Location: dashboard_admin_prodi_km.php?status=username_sudah_ada");
        exit();
    }

    // Selalu simpan akun baru menggunakan hash BCRYPT standar
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);
    
    // created_by dari $username_login
    $created_by = mysqli_real_escape_string($koneksi, $username_login);

    $query_tambah = "INSERT INTO akun (username, password, role, created_by) VALUES ('$username', '$password_hashed', '$role', '$created_by')";
    if (mysqli_query($koneksi, $query_tambah)) {
        $new_id = mysqli_insert_id($koneksi);
        $nama_admin = isset($_POST['nama_admin']) && !empty(trim($_POST['nama_admin'])) ? mysqli_real_escape_string($koneksi, trim($_POST['nama_admin'])) : 'Admin Baru';
        $jabatan = isset($_POST['jabatan']) ? mysqli_real_escape_string($koneksi, trim($_POST['jabatan'])) : '';
        if ($role === 'Admin Lab') {
            mysqli_query($koneksi, "INSERT INTO admin_lab (id_user, nama_admin, nip, jabatan) VALUES ($new_id, '$nama_admin', '$username', '$jabatan')");
        } elseif ($role === 'Admin Prodi') {
            mysqli_query($koneksi, "INSERT INTO admin_prodi (id_user, nama_admin, nip, jabatan) VALUES ($new_id, '$nama_admin', '$username', '$jabatan')");
        }
        header("Location: dashboard_admin_prodi_km.php?status=sukses_tambah");
    } else {
        header("Location: dashboard_admin_prodi_km.php?status=gagal_tambah");
    }
    exit();
}

// Proses import excel mahasiswa
if (isset($_POST['import_mahasiswa'])) {
    if (isset($_FILES['file_excel']) && $_FILES['file_excel']['error'] == 0) {
        $ext = pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION);
        if (strtolower($ext) == 'xlsx') {
            require_once __DIR__ . '/SimpleXLSX.php';
            if ( $xlsx = Shuchkin\SimpleXLSX::parse( $_FILES['file_excel']['tmp_name'] ) ) {
                $rows = $xlsx->rows();
                $nim_idx = -1;
                $nama_idx = -1;
                $jurusan_idx = -1;
                $fakultas_idx = -1;
                $password_idx = -1;
                $data_start_row = -1;

                foreach ($rows as $r_idx => $row) {
                    $row_lower = array_map('strtolower', $row);
                    if ($nim_idx == -1) {
                        $nim_col = array_search('nim', $row_lower);
                        if ($nim_col !== false) {
                            $nim_idx = $nim_col;
                            foreach ($row_lower as $c_idx => $val) {
                                if (strpos($val, 'nama') !== false) $nama_idx = $c_idx;
                                if (strpos($val, 'program studi') !== false || strpos($val, 'jurusan') !== false) $jurusan_idx = $c_idx;
                                if (strpos($val, 'fakultas') !== false) $fakultas_idx = $c_idx;
                                if (strpos($val, 'password') !== false) $password_idx = $c_idx;
                            }
                            $data_start_row = $r_idx + 1;
                            break;
                        }
                    }
                }

                if ($nim_idx != -1) {
                    $sukses = 0;
                    $gagal = 0;
                    for ($i = $data_start_row; $i < count($rows); $i++) {
                        $row = $rows[$i];
                        $nim = isset($row[$nim_idx]) ? trim($row[$nim_idx]) : '';
                        if (empty($nim)) continue;
                        
                        // Otomatis tambah 0 jika belum ada
                        if (substr($nim, 0, 1) !== '0') {
                            $nim = '0' . $nim;
                        }
                        
                        $nama = isset($row[$nama_idx]) ? mysqli_real_escape_string($koneksi, trim($row[$nama_idx])) : '';
                        $jurusan = isset($row[$jurusan_idx]) ? mysqli_real_escape_string($koneksi, trim($row[$jurusan_idx])) : '';
                        $fakultas = isset($row[$fakultas_idx]) ? mysqli_real_escape_string($koneksi, trim($row[$fakultas_idx])) : '';
                        $password = isset($row[$password_idx]) && $row[$password_idx] !== '' ? trim($row[$password_idx]) : $nim;
                        
                        $cek = mysqli_query($koneksi, "SELECT id_user FROM akun WHERE username='$nim'");
                        if (mysqli_num_rows($cek) == 0) {
                            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
                            $query_tambah = "INSERT INTO akun (username, password, role) VALUES ('$nim', '$password_hashed', 'Mahasiswa')";
                            if (mysqli_query($koneksi, $query_tambah)) {
                                $id_user = mysqli_insert_id($koneksi);
                                $query_mhs = "INSERT INTO mahasiswa (id_user, nim, nama_mahasiswa, semester, ipk, jurusan, fakultas) VALUES ('$id_user', '$nim', '$nama', 0, 0, '$jurusan', '$fakultas')";
                                mysqli_query($koneksi, $query_mhs);
                                $sukses++;
                            } else {
                                $gagal++;
                            }
                        } else {
                            $gagal++;
                        }
                    }
                    header("Location: dashboard_admin_prodi_km.php?status=import_selesai&s=$sukses&g=$gagal");
                    exit();
                } else {
                    header("Location: dashboard_admin_prodi_km.php?status=format_excel_salah");
                    exit();
                }
            } else {
                header("Location: dashboard_admin_prodi_km.php?status=gagal_import");
                exit();
            }
        }
    }
}

// Proses ubah akun (update) & manual cascade nim sinkronis
// Notifikasi
$pesan = '';
$alert_type = 'info';
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status == 'sukses_tambah') {
        $pesan = "Data akun berhasil didaftarkan.";
        $alert_type = 'success';
    } elseif ($status == 'gagal_tambah') {
        $pesan = "Terjadi kesalahan. Akun gagal ditambahkan.";
        $alert_type = 'danger';
    } elseif ($status == 'sukses_ubah') {
        $pesan = "Data akun berhasil diperbarui dan disinkronisasi.";
        $alert_type = 'success';
    } elseif ($status == 'gagal_ubah') {
        $pesan = "Terjadi kesalahan. Akun gagal diperbarui.";
        $alert_type = 'danger';
    } elseif ($status == 'sukses_hapus') {
        $pesan = "Data akun beserta berkas terkait berhasil dihapus permanen.";
        $alert_type = 'success';
    } elseif ($status == 'gagal_hapus') {
        $pesan = "Terjadi kesalahan. Akun gagal dihapus.";
        $alert_type = 'danger';
    } elseif ($status == 'username_sudah_ada') {
        $pesan = "NIM/Username sudah terdaftar! Harap gunakan yang lain.";
        $alert_type = 'warning';
    } elseif ($status == 'import_selesai') {
        $s = $_GET['s'] ?? 0;
        $g = $_GET['g'] ?? 0;
        $pesan = "Import selesai! Berhasil: $s baris. Gagal/Sudah ada: $g baris.";
        $alert_type = $s > 0 ? 'success' : 'warning';
    } elseif ($status == 'format_excel_salah') {
        $pesan = "Format Excel tidak sesuai. Pastikan ada kolom 'NIM'.";
        $alert_type = 'danger';
    } elseif ($status == 'gagal_import') {
        $pesan = "Gagal memproses file Excel.";
        $alert_type = 'danger';
    }
}

if (isset($_POST['ubah_akun']) || isset($_POST['update_akun'])) { 
    $id_user       = mysqli_real_escape_string($koneksi, $_POST['id_user']);
    $username_lama = mysqli_real_escape_string($koneksi, trim($_POST['username_lama'])); // NIM/NIP sebelum diedit
    $username_baru = mysqli_real_escape_string($koneksi, trim($_POST['username']));      // NIM/NIP baru dari form
    $role          = mysqli_real_escape_string($koneksi, $_POST['role']);
    $admin_password = $_POST['admin_password'];
    
    // Verifikasi password Admin Prodi
    $q_cek_admin = mysqli_query($koneksi, "SELECT password FROM akun WHERE id_user = $id_user_login LIMIT 1");
    if ($q_cek_admin && mysqli_num_rows($q_cek_admin) > 0) {
        $dt_admin = mysqli_fetch_assoc($q_cek_admin);
        $pw_valid = false;
        if (strpos($dt_admin['password'], '$2y$') === 0) {
            $pw_valid = password_verify($admin_password, $dt_admin['password']);
        } else {
            $pw_valid = hash_equals($dt_admin['password'], $admin_password);
        }
        if (!$pw_valid) {
            echo "<script>alert('Password Admin Prodi salah! Akun gagal diperbarui.'); window.location.href='dashboard_admin_prodi_km.php';</script>";
            exit();
        }
    }
    
    // 0. Cegah ubah akun sesama Admin Prodi
    $q_target = mysqli_query($koneksi, "SELECT role FROM akun WHERE id_user = '$id_user' LIMIT 1");
    if ($q_target && mysqli_num_rows($q_target) > 0) {
        $dt_target = mysqli_fetch_assoc($q_target);
        if ($dt_target['role'] === 'Admin Prodi' && (int)$id_user !== (int)$id_user_login) {
            header("Location: dashboard_admin_prodi_km.php?status=akses_ditolak");
            exit();
        }
    }

    // 1. Validasi duplikasi username jika username/NIM diubah
    if ($username_baru !== $username_lama) {
        $cek_username = mysqli_query($koneksi, "SELECT id_user FROM akun WHERE username='$username_baru' AND id_user != '$id_user'");
        if (mysqli_num_rows($cek_username) > 0) {
            header("Location: dashboard_admin_prodi_km.php?status=username_sudah_ada");
            exit();
        }
    }

    $last_updated_by = mysqli_real_escape_string($koneksi, $username_login);

    // 2. Periksa apakah kolom password diisi baru atau dikosongkan
    if (!empty($_POST['password'])) {
        $password_input  = trim($_POST['password']);
        $password_hashed = password_hash($password_input, PASSWORD_DEFAULT);
        $query_akun      = "UPDATE akun SET username = '$username_baru', password = '$password_hashed', role = '$role', last_updated_by = '$last_updated_by' WHERE id_user = '$id_user'";
    } else {
        $query_akun      = "UPDATE akun SET username = '$username_baru', role = '$role', last_updated_by = '$last_updated_by' WHERE id_user = '$id_user'";
    }

    // 3. Jalankan pembaruan data pada tabel induk 'akun'
    if (mysqli_query($koneksi, $query_akun)) {
        
        // Sinkronisasi cascading manual
        if ($username_baru !== $username_lama) {
            // Mahasiswa
            mysqli_query($koneksi, "UPDATE mahasiswa SET nim = '$username_baru' WHERE nim = '$username_lama'");
            mysqli_query($koneksi, "UPDATE pemberkasan SET nim = '$username_baru' WHERE nim = '$username_lama'");
            // Admin
            mysqli_query($koneksi, "UPDATE admin_lab SET nip = '$username_baru' WHERE nip = '$username_lama'");
            mysqli_query($koneksi, "UPDATE admin_prodi SET nip = '$username_baru' WHERE nip = '$username_lama'");
        }

        // Fail-safe jika admin prodi sedang menyunting akun pribadinya yang sedang aktif
        if ($username_login === $username_lama) {
            $_SESSION['Admin Prodi']['username'] = $username_baru;
            $username_login = $username_baru; // update internal variable too
        }

        header("Location: dashboard_admin_prodi_km.php?status=sukses_ubah");
        exit();
    } else {
        header("Location: dashboard_admin_prodi_km.php?status=gagal_ubah");
        exit();
    }
}

// Proses ganti password admin diri sendiri
if (isset($_POST['ganti_password_diri'])) {
    $pw_lama = $_POST['password_lama'];
    $pw_baru = $_POST['password_baru'];
    $pw_konf = $_POST['konfirmasi_password'];
    
    if ($pw_baru !== $pw_konf) {
        echo "<script>alert('Konfirmasi password baru tidak cocok!'); window.location.href='dashboard_admin_prodi_km.php?status=sukses_ubah';</script>";
        exit();
    }
    
    // Ambil password saat ini
    $id_user_target = isset($id_user_session) ? $id_user_session : (isset($id_user_login) ? $id_user_login : 0);
    $q_cek = mysqli_query($koneksi, "SELECT password FROM akun WHERE id_user = $id_user_target LIMIT 1");
    if ($q_cek && mysqli_num_rows($q_cek) > 0) {
        $dt = mysqli_fetch_assoc($q_cek);
        $db_pw = $dt['password'];
        
        $pw_valid = false;
        if (strpos($db_pw, '$2y$') === 0) {
            $pw_valid = password_verify($pw_lama, $db_pw);
        } else {
            $pw_valid = hash_equals($db_pw, $pw_lama);
        }
        
        if (!$pw_valid) {
            echo "<script>alert('Password lama salah!'); window.location.href='dashboard_admin_prodi_km.php?status=sukses_ubah';</script>";
            exit();
        }
        
        $pw_hash = password_hash($pw_baru, PASSWORD_DEFAULT);
        $q_pw = "UPDATE akun SET password = '$pw_hash' WHERE id_user = $id_user_target";
        if (mysqli_query($koneksi, $q_pw)) {
            echo "<script>alert('Password berhasil diubah.'); window.location.href='dashboard_admin_prodi_km.php?status=sukses_ubah';</script>";
        } else {
            echo "<script>alert('Gagal mengubah password.'); window.location.href='dashboard_admin_prodi_km.php?status=sukses_ubah';</script>";
        }
    }
    exit();
}

// PROSES AKSI: HAPUS AKUN (via POST dengan CSRF)
if (isset($_POST['hapus_akun']) && isset($_POST['id_hapus'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_POST['id_hapus']);
    $admin_password = $_POST['admin_password'];
    
    // Verifikasi password Admin Prodi
    $q_cek_admin = mysqli_query($koneksi, "SELECT password FROM akun WHERE id_user = $id_user_login LIMIT 1");
    if ($q_cek_admin && mysqli_num_rows($q_cek_admin) > 0) {
        $dt_admin = mysqli_fetch_assoc($q_cek_admin);
        $pw_valid = false;
        if (strpos($dt_admin['password'], '$2y$') === 0) {
            $pw_valid = password_verify($admin_password, $dt_admin['password']);
        } else {
            $pw_valid = hash_equals($dt_admin['password'], $admin_password);
        }
        if (!$pw_valid) {
            echo "<script>alert('Password Admin Prodi salah! Akun gagal dihapus.'); window.location.href='dashboard_admin_prodi_km.php';</script>";
            exit();
        }
    }

    // Cek role target, larang hapus sesama Admin Prodi
    $q_target = mysqli_query($koneksi, "SELECT role FROM akun WHERE id_user = '$id_hapus' LIMIT 1");
    if ($q_target && mysqli_num_rows($q_target) > 0) {
        $dt_target = mysqli_fetch_assoc($q_target);
        if ($dt_target['role'] === 'Admin Prodi' && (int)$id_hapus !== (int)$id_user_login) {
            header("Location: dashboard_admin_prodi_km.php?status=akses_ditolak");
            exit();
        }
    }

    // Batasan Keamanan: Dilarang menghapus akun sendiri
    if ((int)$id_hapus === (int)$id_user_login) {
        header("Location: dashboard_admin_prodi_km.php?status=gagal_hapus_diri");
        exit();
    }

    if (is_numeric($id_hapus) && (int)$id_hapus > 0) {
        $query_hapus = "DELETE FROM akun WHERE id_user = '$id_hapus'";
        if (mysqli_query($koneksi, $query_hapus)) {
            header("Location: dashboard_admin_prodi_km.php?status=sukses_hapus");
        } else {
            header("Location: dashboard_admin_prodi_km.php?status=gagal_hapus");
        }
    } else {
        header("Location: dashboard_admin_prodi_km.php?status=gagal_hapus");
    }
    exit();
}

// Ambil data rekap untuk elemen tabel html dibaliknya
// Query 1: Data Akun Mahasiswa
$query_mhs    = "SELECT a.id_user, a.username, a.role, m.nama_mahasiswa, m.jurusan, m.fakultas, a.created_by, a.last_updated_by FROM akun a LEFT JOIN mahasiswa m ON a.id_user = m.id_user WHERE a.role = 'Mahasiswa' ORDER BY a.id_user ASC";
$result_mhs   = mysqli_query($koneksi, $query_mhs);

// Query 2: Data Akun Administrator (Admin Prodi & Admin Lab)
$query_admin  = "SELECT a.id_user, a.username, a.role, a.created_by, a.last_updated_by, COALESCE(al.nama_admin, ap.nama_admin) AS nama_admin FROM akun a LEFT JOIN admin_lab al ON a.id_user = al.id_user LEFT JOIN admin_prodi ap ON a.id_user = ap.id_user WHERE a.role = 'Admin Prodi' OR a.role = 'Admin Lab' ORDER BY a.role ASC, a.id_user ASC";
$result_admin = mysqli_query($koneksi, $query_admin);

// Query 3: Profil Admin Prodi yang sedang login
$q_profil_prodi = mysqli_query($koneksi, "SELECT ap.nama_admin, ap.nip, ap.jabatan, ap.email, ap.no_whatsapp, ap.foto FROM admin_prodi ap WHERE ap.id_user = $id_user_login LIMIT 1");
$profil_prodi = $q_profil_prodi ? mysqli_fetch_assoc($q_profil_prodi) : [];
$nama_prodi   = $profil_prodi['nama_admin'] ?? $username_login;
$nip_prodi    = $profil_prodi['nip'] ?? $username_login;
$jabatan_prodi = $profil_prodi['jabatan'] ?? 'Admin Prodi';
$email_prodi  = $profil_prodi['email'] ?? '';
$wa_prodi     = $profil_prodi['no_whatsapp'] ?? '';
$foto_prodi   = $profil_prodi['foto'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Akun - Admin Prodi | Seleksi Aslab UINSU</title>
<link class="text/html" rel="stylesheet" href="assets/css/bootstrap.min.css">
<link class="text/html" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">

<link class="text/html" rel="stylesheet" href="assets/pelengkap/dapk.css?v=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
 </head>
<body>
    <link rel="stylesheet" href="assets/css/saslab-design.css">
    <style>
        /* Layout Specific */
        .sidebar {
            background-color: var(--surface); 
            border-right: 1px solid var(--border);
            height: 100vh;
            width: 260px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: 0.3s;
            overflow-y: auto;
        }
        .sidebar.closed { left: -260px; }
        
        .sidebar-header {
            padding: 24px;
            font-family: var(--font-heading);
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--on-surface);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
        }
        
        .nav-link-custom {
            color: var(--ink-soft);
            font-weight: 600;
            padding: 12px 24px;
            text-decoration: none;
            display: block;
            transition: all 0.2s;
            margin: 4px 16px;
            border-radius: 999px;
        }
        .nav-link-custom:hover {
            background-color: var(--surface-alt);
            color: var(--on-surface);
        }
        .sidebar .nav-link-custom.active {
            background-color: var(--admin-prodi-bg);
            color: var(--admin-prodi-ink) !important;
        }

        .main-content {
            margin-left: 260px;
            transition: 0.3s;
            min-height: 100vh;
        }
        .main-content.expanded { margin-left: 0; }

        .page-header {
            background: var(--surface); 
            border-bottom: 1px solid var(--border);
            padding: 24px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .tab-content-panel { display: none; }
        .tab-content-panel.active { display: block; }
        
        .navbar-custom {
            background-color: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 12px 24px;
        }

        @media (max-width: 768px) {
            .sidebar { width: 280px; left: -280px; z-index: 1050; }
            .sidebar.active { left: 0; }
            .main-content { margin-left: 0; }
            .sidebar-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1040; display: none; }
            .sidebar-overlay.active { display: block; }
        }
    </style>

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #1d1e24; border-bottom: 1px solid #2a2d35;">
    <div class="container-xl">
        <a class="navbar-brand navbar-brand-custom text-white fw-bold" href="#">
            <i class="bi bi-shield-lock-fill me-2"></i>
            Admin Prodi &mdash; UINSU
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarAdmin">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <span class="navbar-text text-white-50 me-3">
                        <i class="bi bi-person-circle me-1"></i>
                        Selamat datang, <strong class="text-white"><?php echo htmlspecialchars($username_login); ?></strong>
                    </span>
                </li>
                <li class="nav-item me-2">
                    <button class="btn btn-outline-info btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalProfilProdi">
                        <i class="bi bi-person-circle me-1"></i> Profil Saya
                    </button>
                </li>
                <li class="nav-item me-2">
                    <button class="btn btn-outline-light btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalGantiPasswordDiri">
                        <i class="bi bi-key me-1"></i> Ganti Password
                    </button>
                </li>
                <li class="nav-item">
                    <a class="btn btn-outline-danger btn-sm px-3" href="login.php">
                        <i class="bi bi-box-arrow-right me-1"></i> Keluar
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="page-header">
    <div class="container-xl">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1 fw-bold">
                    <i class="bi bi-people-fill me-2"></i>
                    Kelola Akun Pengguna
                </h4>
                <p class="mb-0 text-dark-50">
                    Manajemen akun mahasiswa dan tenaga administrator sistem rekrutmen asisten laboratorium.
                </p>
            </div>
            <div>
                <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalImport" title="Import Data Mahasiswa via Excel">
                    <i class="bi bi-file-earmark-excel"></i> Import Excel
                </button>
            </div>
        </div>
    </div>
</div>

<button class="btn btn-sas btn-sas-prodi btn-float-add" data-bs-toggle="modal" data-bs-target="#modalTambah" title="Tambah Akun Baru">
    <i class="bi bi-person-plus-fill"></i>
</button>

<div class="container-xl pb-5">

    <?php if (isset($_GET['status'])) : ?>
        <?php
        $status      = $_GET['status'];
        $alert_class = '';
        $alert_icon  = '';
        $alert_msg   = '';

if ($status === 'sukses_tambah') {

    $alert_class = 'alert-success sas-badge-success';
    $alert_icon  = 'bi-check-circle-fill';
    $alert_msg   = '<strong>Berhasil!</strong> Akun pengguna baru telah didaftarkan ke sistem.';

} elseif ($status === 'sukses_ubah') {

    $alert_class = 'alert-success sas-badge-success';
    $alert_icon  = 'bi-check-circle-fill';
    $alert_msg   = '<strong>Berhasil!</strong> Perubahan data akun telah disimpan.';

} elseif ($status === 'sukses_hapus') {

    $alert_class = 'alert-success sas-badge-success';
    $alert_icon  = 'bi-check-circle-fill';
    $alert_msg   = '<strong>Berhasil!</strong> Akun pengguna telah berhasil dihapus dari sistem.';

} elseif ($status === 'gagal_hapus_diri') {

    $alert_class = 'alert-danger sas-badge-danger';
    $alert_icon  = 'bi-shield-exclamation';
    $alert_msg   = '<strong>Akses Ditolak!</strong> Anda tidak diizinkan menghapus akun Anda sendiri yang sedang aktif.';

} elseif ($status === 'gagal_hapus' || $status === 'gagal_tambah' || $status === 'gagal_ubah') {

    $alert_class = 'alert-danger sas-badge-danger';
    $alert_icon  = 'bi-x-circle-fill';
    $alert_msg   = '<strong>Gagal!</strong> Proses pengelolaan akun gagal dieksekusi.';
} elseif ($status === 'akses_ditolak') {
    $alert_class = 'alert-warning sas-badge-warning';
    $alert_icon  = 'bi-exclamation-triangle-fill';
    $alert_msg   = '<strong>Peringatan!</strong> Anda tidak memiliki izin untuk mengakses halaman atau fitur tersebut.';

} elseif ($status === 'username_sudah_ada') {
    $alert_class = 'alert-warning sas-badge-warning';
    $alert_icon  = 'bi-exclamation-triangle-fill';
    $alert_msg   = '<strong>Peringatan!</strong> Username sudah digunakan, silakan gunakan username lain.';
}
        ?>
        <?php if (!empty($alert_msg)) : ?>
            <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show d-flex align-items-center mb-4 shadow-sm" role="alert">
                <i class="bi <?php echo $alert_icon; ?> me-2 fs-5"></i>
                <div><?php echo $alert_msg; ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card card-main">
        <div class="card-header border-bottom-0 pt-3 px-4" style="background-color:#1d1e24;">
            <h5 class="mb-0 fw-semibold text-white">
                <i class="bi bi-card-list me-2 text-success"></i>
                Daftar Akun Terdaftar
            </h5>
        </div>

        <div class="px-4 pt-3">
            <ul class="nav nav-tabs" id="akunTab" role="tablist">

                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-mhs-btn" data-bs-toggle="tab"
                        data-bs-target="#tab-mhs" type="button" role="tab"
                        aria-controls="tab-mhs" aria-selected="true">
                        <i class="bi bi-mortarboard-fill me-2"></i>
                        Data Akun Mahasiswa
                        <span class="badge bg-primary ms-2">
                            <?php echo $result_mhs ? mysqli_num_rows($result_mhs) : 0; ?>
                        </span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-admin-btn" data-bs-toggle="tab"
                        data-bs-target="#tab-admin" type="button" role="tab"
                        aria-controls="tab-admin" aria-selected="false">
                        <i class="bi bi-person-gear me-2"></i>
                        Data Akun Administrator
                        <span class="badge bg-primary ms-2">
                            <?php echo $result_admin ? mysqli_num_rows($result_admin) : 0; ?>
                        </span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content" id="akunTabContent">

            <div class="tab-pane fade show active" id="tab-mhs" role="tabpanel" aria-labelledby="tab-mhs-btn">
                <div class="tab-pane-inner">

                    <?php if ($result_mhs && mysqli_num_rows($result_mhs) > 0) : ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;" class="text-center">No.</th>
                                        <th>Username</th>
                                        <th>Nama</th>
                                        <th>Fakultas</th>
                                        <th>Program Studi</th>
                                        <th style="width: 140px;">Role</th>
                                        <th>Dibuat Oleh</th>
                                        <th>Diperbarui Oleh</th>
                                        <th style="width: 160px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no_mhs = 1;
                                    while ($row_mhs = mysqli_fetch_assoc($result_mhs)) :
                                    ?>
                                        <tr>
                                            <td class="text-center text-muted fw-semibold"><?php echo $no_mhs++; ?></td>
                                            <td>
                                                <i class="bi bi-person-fill me-2 text-primary"></i>
                                                <?php echo htmlspecialchars($row_mhs['username']); ?>
                                            </td>
                                            <td ondblclick="editInline(this, 'mahasiswa', 'nama_mahasiswa', '<?= $row_mhs['id_user']; ?>')" style="cursor: pointer;" title="Klik ganda untuk edit"><?= htmlspecialchars($row_mhs['nama_mahasiswa'] ?? '-'); ?></td>
                                            <td ondblclick="editInline(this, 'mahasiswa', 'fakultas', '<?= $row_mhs['id_user']; ?>')" style="cursor: pointer;" title="Klik ganda untuk edit"><?= htmlspecialchars($row_mhs['fakultas'] ?? '-'); ?></td>
                                            <td ondblclick="editInline(this, 'mahasiswa', 'jurusan', '<?= $row_mhs['id_user']; ?>')" style="cursor: pointer;" title="Klik ganda untuk edit"><?= htmlspecialchars($row_mhs['jurusan'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge badge-role-mhs px-3 py-2">
                                                    <i class="bi bi-mortarboard me-1"></i>
                                                    <?php echo htmlspecialchars($row_mhs['role']); ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($row_mhs['created_by'] ?? '-'); ?></td>
                                            <td><?= htmlspecialchars($row_mhs['last_updated_by'] ?? '-'); ?></td>
                                            <td style="width: 1%; white-space: nowrap;">
                                            <div class="d-flex justify-content-center gap-1">
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-warning"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalEditMhs<?= $row_mhs['id_user']; ?>">
                                                    <i class="bi bi-pencil-square"></i> Ubah
                                                </button>

                                                <?php if (!( (int)$row_mhs['id_user'] === (int)$id_user_login )) : ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalHapusMhs<?= $row_mhs['id_user']; ?>">
                                                        <i class="bi bi-trash3"></i> Hapus
                                                    </button>
                                                <?php else : ?>
                                                    <button type="button" class="btn btn-sm btn-light text-muted" title="Tidak dapat menghapus akun sendiri" disabled>
                                                        <i class="bi bi-trash3"></i> Hapus
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        </tr>

                                        <!-- Modal Edit Mahasiswa -->
                                        <div class="modal fade" id="modalEditMhs<?= $row_mhs['id_user']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content text-white" style="background:#1d1e24; border:1px solid #2a2d35;">
                                                    <form method="POST">
    <?= csrf_field(); ?>
                                                        <div class="modal-header" style="background:#2a2d35;">
                                                            <h5 class="modal-title fw-bold text-warning"><i class="bi bi-pencil-square me-1"></i> Ubah Akun Mahasiswa</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id_user" value="<?= $row_mhs['id_user']; ?>">
                                                            <input type="hidden" name="username_lama" value="<?= htmlspecialchars($row_mhs['username']); ?>">
                                                            <input type="hidden" name="role" value="<?= htmlspecialchars($row_mhs['role']); ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Username / NIM</label>
                                                                <input type="text" name="username" class="form-control sas-input focus-prodi" value="<?= htmlspecialchars($row_mhs['username']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Password Baru (Opsional)</label>
                                                                <input type="password" name="password" class="form-control sas-input focus-prodi" placeholder="Kosongkan jika tidak diubah">
                                                            </div>
                                                            <hr style="border-color:#3d4050;">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold text-warning"><i class="bi bi-shield-lock me-1"></i> Konfirmasi Password Admin Prodi</label>
                                                                <input type="password" name="admin_password" class="form-control sas-input focus-prodi" placeholder="Masukkan password Anda" required>
                                                                <small class="text-light">Diperlukan untuk verifikasi keamanan.</small>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer" style="border-color:#2a2d35;">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" name="ubah_akun" class="btn btn-warning fw-bold text-dark">Simpan Perubahan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal Hapus Mahasiswa -->
                                        <?php if (!( (int)$row_mhs['id_user'] === (int)$id_user_login )) : ?>
                                        <div class="modal fade" id="modalHapusMhs<?= $row_mhs['id_user']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content text-white" style="background:#1d1e24; border:1px solid #2a2d35;">
                                                    <form method="POST">
    <?= csrf_field(); ?>
                                                        <div class="modal-header" style="background:#2a2d35;">
                                                            <h5 class="modal-title fw-bold text-primary"><i class="bi bi-trash3 me-1"></i> Hapus Akun Mahasiswa</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id_hapus" value="<?= $row_mhs['id_user']; ?>">
                                                            <p class="text-primary fw-bold">Anda yakin ingin menghapus akun <strong><?= htmlspecialchars($row_mhs['username']); ?></strong>?</p>
                                                            <p class="text-light small">Tindakan ini tidak dapat dibatalkan.</p>
                                                            <hr style="border-color:#3d4050;">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold text-warning"><i class="bi bi-shield-lock me-1"></i> Konfirmasi Password Admin Prodi</label>
                                                                <input type="password" name="admin_password" class="form-control sas-input focus-prodi" placeholder="Masukkan password Anda" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer" style="border-color:#2a2d35;">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" name="hapus_akun" class="btn btn-danger fw-bold">Ya, Hapus</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox-fill text-muted mb-2"></i>
                            <h5 class="fw-bold">Tidak ada akun mahasiswa</h5>
                            <p class="mb-0">Saat ini belum ada mahasiswa yang terdaftar.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TAB ADMIN -->
            <div class="tab-pane fade" id="tab-admin" role="tabpanel" aria-labelledby="tab-admin-btn">
                <div class="tab-pane-inner">
                    <?php if ($result_admin && mysqli_num_rows($result_admin) > 0) : ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;" class="text-center">No.</th>
                                        <th>Username / NIP</th>
                                        <th>Nama</th>
                                        <th style="width: 140px;">Role</th>
                                        <th>Dibuat Oleh</th>
                                        <th>Diperbarui Oleh</th>
                                        <th style="width: 160px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no_adm = 1;
                                    while ($row_adm = mysqli_fetch_assoc($result_admin)) :
                                    ?>
                                        <tr>
                                            <td class="text-center text-muted fw-semibold"><?php echo $no_adm++; ?></td>
                                            <td>
                                                <i class="bi bi-person-gear me-2 text-primary"></i>
                                                <?php echo htmlspecialchars($row_adm['username']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($row_adm['nama_admin'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge <?php echo ($row_adm['role'] == 'Admin Lab') ? 'badge-role-lab' : 'badge-role-prodi'; ?> px-3 py-2">
                                                    <?php echo htmlspecialchars($row_adm['role']); ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($row_adm['created_by'] ?? '-'); ?></td>
                                            <td><?= htmlspecialchars($row_adm['last_updated_by'] ?? '-'); ?></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditAdmin<?= $row_adm['id_user']; ?>">
                                                    <i class="bi bi-pencil-square"></i> Ubah
                                                </button>
                                                <?php if (!( (int)$row_adm['id_user'] === (int)$id_user_login )) : ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalHapusAdmin<?= $row_adm['id_user']; ?>">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                <?php else : ?>
                                                    <button type="button" class="btn btn-sm btn-light text-muted" disabled title="Tidak bisa hapus diri sendiri"><i class="bi bi-trash3"></i></button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <!-- Modal Edit Admin -->
                                        <div class="modal fade" id="modalEditAdmin<?= $row_adm['id_user']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content text-white" style="background:#1d1e24; border:1px solid #2a2d35;">
                                                    <form method="POST">
                                                        <?= csrf_field(); ?>
                                                        <div class="modal-header" style="background:#2a2d35;">
                                                            <h5 class="modal-title fw-bold text-warning"><i class="bi bi-pencil-square me-1"></i> Ubah Akun Admin</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id_user" value="<?= $row_adm['id_user']; ?>">
                                                            <input type="hidden" name="username_lama" value="<?= htmlspecialchars($row_adm['username']); ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Username / NIP</label>
                                                                <input type="text" name="username" class="form-control sas-input focus-prodi" value="<?= htmlspecialchars($row_adm['username']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Role</label>
                                                                <select name="role" class="form-select" required>
                                                                    <option value="Admin Lab" <?= ($row_adm['role'] == 'Admin Lab') ? 'selected' : ''; ?>>Admin Lab</option>
                                                                    <option value="Admin Prodi" <?= ($row_adm['role'] == 'Admin Prodi') ? 'selected' : ''; ?>>Admin Prodi</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Password Baru (Opsional)</label>
                                                                <input type="password" name="password" class="form-control sas-input focus-prodi" placeholder="Kosongkan jika tidak diubah">
                                                            </div>
                                                            <hr style="border-color:#3d4050;">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold text-warning"><i class="bi bi-shield-lock me-1"></i> Konfirmasi Password Admin Prodi</label>
                                                                <input type="password" name="admin_password" class="form-control sas-input focus-prodi" placeholder="Masukkan password Anda" required>
                                                                <small class="text-light">Diperlukan untuk verifikasi keamanan.</small>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer" style="border-color:#2a2d35;">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" name="ubah_akun" class="btn btn-warning fw-bold text-dark">Simpan Perubahan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal Hapus Admin -->
                                        <?php if (!( (int)$row_adm['id_user'] === (int)$id_user_login )) : ?>
                                        <div class="modal fade" id="modalHapusAdmin<?= $row_adm['id_user']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content text-white" style="background:#1d1e24; border:1px solid #2a2d35;">
                                                    <form method="POST">
                                                        <?= csrf_field(); ?>
                                                        <div class="modal-header" style="background:#2a2d35;">
                                                            <h5 class="modal-title fw-bold text-primary"><i class="bi bi-trash3 me-1"></i> Hapus Akun Admin</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id_hapus" value="<?= $row_adm['id_user']; ?>">
                                                            <p class="text-primary fw-bold">Anda yakin ingin menghapus akun <strong><?= htmlspecialchars($row_adm['username']); ?></strong> (<?= htmlspecialchars($row_adm['role']); ?>)?</p>
                                                            <p class="text-light small">Tindakan ini tidak dapat dibatalkan.</p>
                                                            <hr style="border-color:#3d4050;">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold text-warning"><i class="bi bi-shield-lock me-1"></i> Konfirmasi Password Admin Prodi</label>
                                                                <input type="password" name="admin_password" class="form-control sas-input focus-prodi" placeholder="Masukkan password Anda" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer" style="border-color:#2a2d35;">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" name="hapus_akun" class="btn btn-danger fw-bold">Ya, Hapus</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <div class="empty-state">
                            <i class="bi bi-person-x-fill text-muted mb-2"></i>
                            <h5 class="fw-bold">Tidak ada akun admin</h5>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div> <!-- /tab-content -->
    </div> <!-- /card-main -->
</div> <!-- /container -->

<!-- Modal Ganti Password Diri Sendiri -->
<div class="modal fade" id="modalGantiPasswordDiri" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-white" style="background-color: #1d1e24; border: 1px solid #2a2d35;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Ganti Password Anda</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <?= csrf_field(); ?>
                <div class="modal-body p-4">
                    <div class="mb-3 position-relative">
                        <label class="form-label fw-bold text-secondary">Password Lama</label>
                        <div class="input-group">
                            <input type="password" name="password_lama" class="form-control sas-input focus-prodi" placeholder="Masukkan password lama" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button"><i class="bi bi-eye-slash"></i></button>
                        </div>
                    </div>
                    <div class="mb-3 position-relative">
                        <label class="form-label fw-bold text-secondary">Password Baru</label>
                        <div class="input-group">
                            <input type="password" name="password_baru" class="form-control sas-input focus-prodi" placeholder="Masukkan password baru" required minlength="4">
                            <button class="btn btn-outline-secondary toggle-password" type="button"><i class="bi bi-eye-slash"></i></button>
                        </div>
                    </div>
                    <div class="mb-3 position-relative">
                        <label class="form-label fw-bold text-secondary">Konfirmasi Password Baru</label>
                        <div class="input-group">
                            <input type="password" name="konfirmasi_password" class="form-control sas-input focus-prodi" placeholder="Ulangi password baru" required minlength="4">
                            <button class="btn btn-outline-secondary toggle-password" type="button"><i class="bi bi-eye-slash"></i></button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="ganti_password_diri" class="btn btn-sas btn-sas-prodi fw-bold">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Akun -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content text-white" style="background:#1d1e24; border:1px solid #2a2d35;">
            <form method="POST">
                <?= csrf_field(); ?>
                <div class="modal-header" style="background:#2a2d35;">
                    <h5 class="modal-title fw-bold text-primary"><i class="bi bi-person-plus-fill me-1"></i> Tambah Akun Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Username / NIM / NIP</label>
                        <input type="text" name="username" class="form-control sas-input focus-prodi" required placeholder="Masukkan Username / NIM / NIP">
                        <small class="text-light">Untuk Admin Lab/Prodi gunakan NIP. Untuk Mahasiswa gunakan NIM.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Role</label>
                        <select name="role" id="roleSelect" class="form-select" required onchange="toggleAdminFields()">
                            <option value="" disabled selected>-- Pilih Role --</option>
                            <option value="Mahasiswa">Mahasiswa</option>
                            <option value="Admin Lab">Admin Lab</option>
                            <option value="Admin Prodi">Admin Prodi</option>
                        </select>
                    </div>
                    
                    <!-- Form Khusus Admin (Awalnya Disembunyikan) -->
                    <div id="adminFields" style="display: none; background: #252730; padding: 15px; border-radius: 8px; border: 1px solid #3d4050; margin-bottom: 1rem;">
                        <h6 class="text-secondary fw-bold mb-3"><i class="bi bi-person-lines-fill me-1"></i> Data Profil Admin</h6>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Lengkap</label>
                            <input type="text" name="nama_admin" id="namaAdmin" class="form-control sas-input focus-prodi" placeholder="Nama Lengkap (Sesuai Gelar)">
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-bold">Jabatan</label>
                            <input type="text" name="jabatan" id="jabatanAdmin" class="form-control sas-input focus-prodi" placeholder="Cth: Kepala Lab Komputer / Admin Prodi">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Password Akun Baru</label>
                        <input type="password" name="password" class="form-control sas-input focus-prodi" required placeholder="Minimal 4 karakter">
                    </div>
                    <hr style="border-color:#3d4050;">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-warning"><i class="bi bi-shield-lock me-1"></i> Konfirmasi Password Admin Prodi</label>
                        <input type="password" name="admin_password" class="form-control sas-input focus-prodi" placeholder="Masukkan password Anda sendiri" required>
                        <small class="text-light">Diperlukan untuk verifikasi keamanan setiap pembuatan akun baru.</small>
                    </div>
                </div>
                <div class="modal-footer" style="border-color:#2a2d35;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_akun" class="btn btn-sas btn-sas-prodi fw-bold">Simpan Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Import Excel -->
<div class="modal fade" id="modalImport" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content text-white" style="background:#1d1e24; border:1px solid #2a2d35;">
            <form method="POST" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <div class="modal-header" style="background:#2a2d35;">
                    <h5 class="modal-title fw-bold text-success"><i class="bi bi-file-earmark-excel-fill me-1"></i> Import Data Mahasiswa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih File Excel (.xlsx)</label>
                        <input type="file" name="file_excel" class="form-control sas-input focus-prodi" accept=".xlsx" required>
                        <small class="text-light d-block mt-2">
                            Pastikan file Anda berformat <strong>.xlsx</strong> dan memiliki kolom berurutan sebagai berikut: <br>
                            <strong>Nama, NIM, Fakultas, Program Studi, Password</strong>
                        </small>
                    </div>
                </div>
                <div class="modal-footer" style="border-color:#2a2d35;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="import_mahasiswa" class="btn btn-success fw-bold">Import Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Profil Admin Prodi -->
<div class="modal fade" id="modalProfilProdi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content text-white" style="background:#1d1e24; border:1px solid #2a2d35;">
            <form method="POST" enctype="multipart/form-data" action="dashboard_admin_lab.php">
                <?= csrf_field(); ?>
                <div class="modal-header" style="background:linear-gradient(135deg,#1e3a5f,#1d1e24); border-color:#2a2d35;">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-circle me-2"></i> Profil Admin Prodi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <!-- Foto -->
                        <div class="col-md-3">
                            <div class="border rounded p-3 text-center h-100 d-flex flex-column justify-content-center" style="background:#252730; border-color:#3d4050 !important;">
                                <?php if (!empty($foto_prodi)): ?>
                                    <img src="uploads/profil/<?= htmlspecialchars($foto_prodi) ?>" alt="Foto" class="img-fluid rounded mb-3" style="max-height:180px; object-fit:cover;">
                                <?php else: ?>
                                    <div class="mb-3 d-flex align-items-center justify-content-center" style="height:140px; background:#131418; border-radius:10px; border:1px solid #3d4050;">
                                        <i class="bi bi-person-fill" style="font-size:4rem; color:#3d4050;"></i>
                                    </div>
                                <?php endif; ?>
                                <label class="form-label fw-bold text-secondary small">FOTO PROFIL (Maks 2MB)</label>
                                <input type="file" name="foto_profil" class="form-control sas-input focus-prodi form-control sas-input focus-prodi-sm" accept="image/*">
                            </div>
                        </div>
                        <!-- Data -->
                        <div class="col-md-9">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary">NAMA LENGKAP</label>
                                    <input type="text" class="form-control sas-input focus-prodi" value="<?= htmlspecialchars($nama_prodi) ?>" readonly style="background:#131418; color:#94a3b8;">
                                    <small class="text-light">Ubah melalui Kelola Akun</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary">NIP</label>
                                    <input type="text" class="form-control sas-input focus-prodi" value="<?= htmlspecialchars($nip_prodi) ?>" readonly style="background:#131418; color:#94a3b8;">
                                    <small class="text-light">Ubah melalui Kelola Akun</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary">JABATAN</label>
                                    <input type="text" class="form-control sas-input focus-prodi" value="<?= htmlspecialchars($jabatan_prodi) ?>" readonly style="background:#131418; color:#94a3b8;">
                                    <small class="text-light">Ubah melalui Kelola Akun</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary">EMAIL</label>
                                    <input type="email" name="email" class="form-control sas-input focus-prodi" value="<?= htmlspecialchars($email_prodi) ?>" placeholder="Email aktif">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary">NO WHATSAPP</label>
                                    <input type="text" name="no_whatsapp" class="form-control sas-input focus-prodi" value="<?= htmlspecialchars($wa_prodi) ?>" placeholder="08xxxxxxxxxx">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="alert mt-4 mb-0" style="background:#1a2a1a; border:1px solid #2d6a2d; border-radius:8px;">
                        <i class="bi bi-info-circle me-2 text-success"></i>
                        <small class="text-success">Data <strong>Nama</strong>, <strong>NIP</strong>, dan <strong>Jabatan</strong> bersifat <em>read-only</em>. Perubahan hanya bisa dilakukan oleh admin yang berwenang melalui menu <strong>Kelola Akun</strong>.</small>
                    </div>
                </div>
                <div class="modal-footer" style="border-color:#2a2d35;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editInline(cell, table, field, id) {
    if (cell.querySelector('input')) return; // Already editing
    
    let originalText = cell.innerText === '-' ? '' : cell.innerText;
    cell.innerHTML = `<input type="text" class="form-control sas-input focus-prodi form-control sas-input focus-prodi-sm border-success shadow-none" value="${originalText}" onblur="saveInline(this, '${table}', '${field}', '${id}', '${originalText}')" onkeydown="if(event.key === 'Enter') this.blur();">`;
    cell.querySelector('input').focus();
}

function saveInline(input, table, field, id, originalText) {
    let newValue = input.value.trim();
    let cell = input.parentElement;
    
    if (newValue === originalText) {
        cell.innerText = newValue === '' ? '-' : newValue;
        return;
    }
    
    cell.innerText = 'Menyimpan...';
    
    let formData = new FormData();
    formData.append('table', table);
    formData.append('field', field);
    formData.append('value', newValue);
    formData.append('id', id);
    formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');
    
    fetch('ajax_update_inline.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            cell.innerText = newValue === '' ? '-' : newValue;
        } else {
            alert('Gagal: ' + data.msg);
            cell.innerText = originalText === '' ? '-' : originalText;
        }
    })
    .catch(err => {
        alert('Terjadi kesalahan jaringan.');
        cell.innerText = originalText === '' ? '-' : originalText;
    });
}
// Toggle password visibility
document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', function() {
        const input = this.previousElementSibling;
        const icon = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        }
    });
});
// Script untuk toggle field Admin
function toggleAdminFields() {
    const role = document.getElementById('roleSelect').value;
    const adminFields = document.getElementById('adminFields');
    if (role === 'Admin Lab' || role === 'Admin Prodi') {
        adminFields.style.display = 'block';
    } else {
        adminFields.style.display = 'none';
        document.getElementById('namaAdmin').value = '';
        document.getElementById('jabatanAdmin').value = '';
    }
}
</script>
</body>
</html>