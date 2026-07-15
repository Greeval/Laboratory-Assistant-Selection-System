<?php

session_start();
require_once __DIR__ . '/koneksiweb.php';
require_once __DIR__ . '/csrf_helper.php';
verify_csrf_token();
require_once __DIR__ . '/kirim_notifikasi.php';

// Proteksi halaman
if (empty($_SESSION['Admin Lab']['id_user'])) {
    header("Location: login.php");
    exit();
}

$id_user_session = (int)$_SESSION['Admin Lab']['id_user'];
$username_admin  = $_SESSION['Admin Lab']['username'];

// Ambil data admin lab
$q_admin_lab = mysqli_query($koneksi, "SELECT * FROM admin_lab WHERE id_user = $id_user_session LIMIT 1");
$d_admin_lab = mysqli_fetch_assoc($q_admin_lab);
$id_admin_lab_real = $d_admin_lab ? (int)$d_admin_lab['id_admin_lab'] : 0;
$nama_admin = $d_admin_lab['nama_admin'] ?? $username_admin;
$nip_admin = $d_admin_lab['nip'] ?? '';
$jabatan_admin = $d_admin_lab['jabatan'] ?? '';
$email_admin = $d_admin_lab['email'] ?? '';
$wa_admin = $d_admin_lab['no_whatsapp'] ?? '';
$foto_admin = $d_admin_lab['foto_profil'] ?? '';

// Tab aktif
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Proses simpan profil admin
if (isset($_POST['simpan_profil_admin'])) {
    $email_input = mysqli_real_escape_string($koneksi, trim($_POST['email']));
    $wa_input = mysqli_real_escape_string($koneksi, trim($_POST['no_whatsapp']));
    
    // Upload Foto
    $foto_name = $foto_admin;
    if (!empty($_FILES['foto_profil']['name']) && $_FILES['foto_profil']['error'] == 0) {
        $target_dir = "uploads/profil/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }
        $ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg','jpeg','png'];
        if (in_array(strtolower($ext), $allowed) && $_FILES['foto_profil']['size'] <= 2097152 && strpos(mime_content_type($_FILES['foto_profil']['tmp_name']), 'image/') === 0) {
            $foto_name = "ADMIN_" . $id_admin_lab_real . "_" . time() . "." . $ext;
            move_uploaded_file($_FILES['foto_profil']['tmp_name'], $target_dir . $foto_name);
        }
    }
    
    // Hanya simpan field yang bisa diedit (email, WA, foto). Nama/NIP/Jabatan hanya bisa diubah via Kelola Akun.
    $q = "UPDATE admin_lab SET email='$email_input', no_whatsapp='$wa_input', foto_profil='$foto_name' WHERE id_user = $id_user_session";
    mysqli_query($koneksi, $q);
    echo "<script>alert('Profil berhasil disimpan!'); window.location.href='dashboard_admin_lab.php?tab=profil';</script>";
    exit();
}

// Proses ganti password
if (isset($_POST['ganti_password_diri'])) {
    $pw_lama = $_POST['password_lama'];
    $pw_baru = $_POST['password_baru'];
    $pw_konf = $_POST['konfirmasi_password'];
    
    if ($pw_baru !== $pw_konf) {
        echo "<script>alert('Konfirmasi password baru tidak cocok!'); window.location.href='dashboard_admin_lab.php?tab=profil';</script>";
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
            echo "<script>alert('Password lama salah!'); window.location.href='dashboard_admin_lab.php?tab=profil';</script>";
            exit();
        }
        
        $pw_hash = password_hash($pw_baru, PASSWORD_DEFAULT);
        $q_pw = "UPDATE akun SET password = '$pw_hash' WHERE id_user = $id_user_target";
        if (mysqli_query($koneksi, $q_pw)) {
            echo "<script>alert('Password berhasil diubah.'); window.location.href='dashboard_admin_lab.php?tab=profil';</script>";
        } else {
            echo "<script>alert('Gagal mengubah password.'); window.location.href='dashboard_admin_lab.php?tab=profil';</script>";
        }
    }
    exit();
}

// Proses aksi validasi
if (isset($_POST['aksi_validasi'])) {
    $id_berkas_input = (int)$_POST['id_berkas'];
    $status_baru     = mysqli_real_escape_string($koneksi, $_POST['aksi_validasi']);

    $query_update = "UPDATE pemberkasan SET 
                     status_validasi = '$status_baru', 
                     id_admin_lab = $id_admin_lab_real 
                     WHERE id_berkas = $id_berkas_input";
    
    if (mysqli_query($koneksi, $query_update)) {
        // Kirim notifikasi ke mahasiswa
        $q_nim = mysqli_query($koneksi, "SELECT nim FROM pemberkasan WHERE id_berkas = $id_berkas_input LIMIT 1");
        $d_nim = mysqli_fetch_assoc($q_nim);
        if ($d_nim) {
            $nim_notif = $d_nim['nim'];
            $subject = "Status Berkas Seleksi Aslab - " . $status_baru;
            $body = "Status berkas pendaftaran Anda telah diperbarui menjadi: " . $status_baru . "\n\n";
            if ($status_baru === 'Valid') {
                $body .= "Selamat! Berkas Anda telah divalidasi. Silakan lanjut ke tahap ujian seleksi.\n";
            } else {
                $body .= "Mohon periksa kembali berkas Anda dan upload ulang jika ada kesalahan.\n";
            }
            
            // Panggil helper pengirim notifikasi dan email
            kirim_notifikasi_ganda($nim_notif, $subject, $body, $email_admin, $nama_admin);
        }
        echo "<script>alert('Status berkas diperbarui: " . htmlspecialchars($status_baru) . "'); window.location.href = 'dashboard_admin_lab.php';</script>";
        exit();
    } else {
        echo "<script>alert('Gagal. Silakan coba lagi.');</script>";
    }
}

// FITUR PENCARIAN & PENYARINGAN (untuk tab dashboard/validasi)
$search_query = "";
$filter_jurusan = "";
$kondisi = [];

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = mysqli_real_escape_string($koneksi, trim($_GET['search']));
    $kondisi[] = "(m.nama_mahasiswa LIKE '%$search%' OR p.nim LIKE '%$search%')";
    $search_query = $search;
}

if (isset($_GET['filter_jurusan']) && $_GET['filter_jurusan'] !== '') {
    $jurusan = mysqli_real_escape_string($koneksi, $_GET['filter_jurusan']);
    $kondisi[] = "m.jurusan = '$jurusan'";
    $filter_jurusan = $jurusan;
}

$where_clause = "";
if (count($kondisi) > 0) {
    $where_clause = "WHERE " . implode(" AND ", $kondisi);
}

$query_tampil = "SELECT p.id_berkas, p.nim, p.cv, p.transkrip_nilai, p.status_validasi, m.nama_mahasiswa, m.jurusan, a.nama_admin as nama_admin_lab 
                 FROM pemberkasan p
                 INNER JOIN mahasiswa m ON p.nim = m.nim
                 LEFT JOIN admin_lab a ON p.id_admin_lab = a.id_admin_lab
                 $where_clause
                 ORDER BY p.id_berkas DESC";
$hasil_tampil = mysqli_query($koneksi, $query_tampil);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin Lab - Rekrutmen Aslab</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
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
            background-color: var(--admin-lab-bg);
            color: var(--admin-lab-ink) !important;
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

        .tab-panel { display: none; }
        .tab-panel.active { display: block; }
        
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
</head>
<body>

<!-- Mobile Navbar Toggler (Visible only on small screens) -->
<nav class="navbar navbar-dark d-md-none px-3 py-2" style="background-color: #1d1e24; border-bottom: 1px solid #2a2d35;">
    <a class="navbar-brand text-white fw-bold fs-6" href="#">Admin Lab Portal</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
</nav>

<nav id="sidebarMenu" class="sidebar collapse d-md-block p-3">
    <div class="text-center my-3">
        <h5 class="fw-bold text-dark">Admin Lab Portal</h5>
        <hr>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link-custom <?= $active_tab === 'profil' ? 'active' : '' ?>" href="#" onclick="switchTab('profil')"><i class="bi bi-person-fill me-2"></i>Profil</a>
        </li>
        <li class="nav-item">
            <a class="nav-link-custom <?= $active_tab === 'dashboard' ? 'active' : '' ?>" href="#" onclick="switchTab('dashboard')"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
        </li>
        <li class="nav-item">
            <a class="nav-link-custom" href="kelola_soal.php"><i class="bi bi-journal-text me-2"></i>Kelola Soal</a>
        </li>
        <li class="nav-item">
            <a class="nav-link-custom" href="penilaian_ujian.php"><i class="bi bi-pencil-square me-2"></i>Penilaian</a>
        </li>
        <li class="nav-item">
            <a class="nav-link-custom" href="jadwal_interview.php"><i class="bi bi-camera-video me-2"></i>Interview</a>
        </li>
        <li class="nav-item">
            <a class="nav-link-custom" href="keputusan_seleksi.php"><i class="bi bi-trophy me-2"></i>Hasil Seleksi</a>
        </li>
        <li class="nav-item mt-3">
            <a class="nav-link-custom text-warning fw-bold" href="logout.php?role=Admin%20Lab" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                <i class="bi bi-box-arrow-left me-2"></i>Logout
            </a>
        </li>
    </ul>
</nav>

        <main class="main-content px-4">

            <!-- ========== TAB PROFIL ========== -->
            <div class="tab-panel <?= $active_tab === 'profil' ? 'active' : '' ?>" id="tab-profil">
                <div class="d-flex justify-content-between align-items-center pt-4 pb-2 mb-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-outline-secondary me-3 d-none d-md-block" id="sidebarToggle" onclick="document.getElementById('sidebarMenu').classList.toggle('closed'); document.querySelector('.main-content').classList.toggle('expanded');">
                            <i class="bi bi-list"></i>
                        </button>
                        <h2 class="h3 fw-bold mb-0">Profil Admin Lab</h2>
                    </div>
                    <span class="badge bg-light p-2 fs-6">Admin: <?= htmlspecialchars($nama_admin); ?></span>
                </div>

                <div class="card border-0 shadow-sm mb-5">
                    <div class="card-body p-4">
                        <form method="POST" action="" enctype="multipart/form-data">
    <?= csrf_field(); ?>
                            <div class="row g-4">
                                <!-- Photo Column -->
                                <div class="col-md-3">
                                    <div class="border rounded p-3 text-center h-100 d-flex flex-column justify-content-center" style="">
                                        <?php if (!empty($foto_admin)): ?>
                                            <img src="uploads/profil/<?= htmlspecialchars($foto_admin) ?>" alt="Foto" class="img-fluid rounded mb-3" style="max-height:200px; object-fit:cover;">
                                        <?php else: ?>
                                            <div class="mb-3 d-flex align-items-center justify-content-center text-secondary" style="height:150px; background:#131418; border-radius:10px; border: 1px solid #3d4050;">
                                                <i class="bi bi-person-fill" style="font-size:5rem; color: #3d4050;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <label class="form-label fw-bold text-secondary small">PHOTO PROFIL (Maks 2MB)</label>
                                        <input type="file" name="foto_profil" class="form-control sas-input focus-lab form-control sas-input focus-lab-sm" accept="image/*">
                                    </div>
                                </div>

                                <!-- Biodata Column -->
                                <div class="col-md-9">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-secondary">NAMA LENGKAP</label>
                                            <input type="text" name="nama_admin" class="form-control sas-input focus-lab" value="<?= htmlspecialchars($nama_admin); ?>" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-secondary">NIP</label>
                                            <input type="text" name="nip" class="form-control sas-input focus-lab" value="<?= htmlspecialchars($nip_admin); ?>" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-secondary">JABATAN</label>
                                            <input type="text" name="jabatan" class="form-control sas-input focus-lab" value="<?= htmlspecialchars($jabatan_admin); ?>" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-secondary">EMAIL</label>
                                            <input type="email" name="email" class="form-control sas-input focus-lab" value="<?= htmlspecialchars($email_admin); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-secondary">NO WHATSAPP</label>
                                            <input type="text" name="no_whatsapp" class="form-control sas-input focus-lab" value="<?= htmlspecialchars($wa_admin); ?>">
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 mt-4">
                                        <button type="submit" name="simpan_profil_admin" class="btn btn-sas btn-sas-lab fw-bold px-4">SIMPAN PROFIL</button>
                                        <button type="button" class="btn btn-outline-secondary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalGantiPasswordDiri">GANTI PASSWORD</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ========== TAB DASHBOARD / VALIDASI ========== -->
            <div class="tab-panel <?= $active_tab === 'dashboard' ? 'active' : '' ?>" id="tab-dashboard">
                <div class="d-flex justify-content-between align-items-center pt-4 pb-2 mb-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-outline-secondary me-3 d-none d-md-block" id="sidebarToggle" onclick="document.getElementById('sidebarMenu').classList.toggle('closed'); document.querySelector('.main-content').classList.toggle('expanded');">
                            <i class="bi bi-list"></i>
                        </button>
                        <h2 class="h3 fw-bold text-slate-800 mb-0">Validasi Berkas Pendaftar</h2>
                    </div>
                    <span class="badge bg-light p-2 fs-6">Admin: <?= htmlspecialchars($nama_admin); ?></span>
                </div>

                <div class="card border-0 mb-4" style="background-color:#1d1e24; border:1px solid #2a2d35 !important;">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3 align-items-center">
                            <input type="hidden" name="tab" value="dashboard">
                            <div class="col-md-6">
                                <input type="text" name="search" class="form-control sas-input focus-lab" placeholder="Cari Nama / NIM..." value="<?= htmlspecialchars($search_query) ?>">
                            </div>
                            <div class="col-md-4">
                                <select name="filter_jurusan" class="form-select">
                                    <option value="">-- Semua Program Studi --</option>
                                    <option value="Informatika" <?= $filter_jurusan === 'Informatika' ? 'selected' : '' ?>>Informatika</option>
                                    <option value="Sistem Informasi" <?= $filter_jurusan === 'Sistem Informasi' ? 'selected' : '' ?>>Sistem Informasi</option>
                                    <option value="ILMU KOMPUTER" <?= $filter_jurusan === 'ILMU KOMPUTER' ? 'selected' : '' ?>>Ilmu Komputer</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-sas btn-sas-lab w-100"><i class="bi bi-search"></i> Cari</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card border-0 mb-5" style="background-color:#1d1e24; border:1px solid #2a2d35 !important;">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead style="background-color:#252730;">
                                    <tr>
                                        <th>NIM</th>
                                        <th>Nama Pelamar</th>
                                        <th>Program Studi</th>
                                        <th>CV</th>
                                        <th>Transkrip</th>
                                        <th>Status Berkas</th>
                                        <th>Divalidasi Oleh</th>
                                        <th class="text-center">Aksi Validasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($hasil_tampil && mysqli_num_rows($hasil_tampil) > 0): ?>
                                        <?php while($row = mysqli_fetch_assoc($hasil_tampil)): ?>
                                            <tr>
                                                <td class="fw-bold" style="color:#e2e8f0 !important;"><?= htmlspecialchars($row['nim']); ?></td>
                                                <td style="color:#e2e8f0 !important;"><?= htmlspecialchars($row['nama_mahasiswa']); ?></td>
                                                <td style="color:#e2e8f0 !important;"><?= htmlspecialchars($row['jurusan'] ?? '-'); ?></td>
                                                
                                                <td>
                                                    <a href="uploads/<?= htmlspecialchars($row['cv']); ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-file-pdf me-1"></i>Lihat CV
                                                    </a>
                                                </td>
                                                
                                                <td>
                                                    <a href="uploads/<?= htmlspecialchars($row['transkrip_nilai']); ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-file-pdf me-1"></i>Lihat Transkrip
                                                    </a>
                                                </td>
                                                
                                                <td>
                                                    <?php 
                                                        if ($row['status_validasi'] === 'Pending') {
                                                            $badge_class = 'bg-warning';
                                                        } elseif ($row['status_validasi'] === 'Valid') {
                                                            $badge_class = 'bg-success';
                                                        } else {
                                                            $badge_class = 'bg-danger';
                                                        }
                                                    ?>
                                                    <span class="badge <?= $badge_class; ?> px-2 py-1"><?= htmlspecialchars($row['status_validasi']); ?></span>
                                                </td>
                                                
                                                <td class="text-muted small">
                                                    <?= $row['status_validasi'] !== 'Pending' ? htmlspecialchars($row['nama_admin_lab'] ?? 'Admin') : '-'; ?>
                                                </td>
                                                
                                                <td class="text-center">
                                                    <form method="POST" action="" class="d-inline-flex gap-1">
    <?= csrf_field(); ?>
                                                        <input type="hidden" name="id_berkas" value="<?= $row['id_berkas']; ?>">
                                                        
                                                        <button type="submit" name="aksi_validasi" value="Valid" class="btn btn-success btn-sm" onclick="return confirm('Tandai berkas mahasiswa ini sebagai VALID?')">
                                                            <i class="bi bi-check-circle"></i> Valid
                                                        </button>
                                                        
                                                        <button type="submit" name="aksi_validasi" value="Tidak Valid" class="btn btn-danger btn-sm" onclick="return confirm('Tolak berkas mahasiswa ini (TIDAK VALID)?')">
                                                            <i class="bi bi-x-circle"></i> Tolak
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">Belum ada berkas mahasiswa yang masuk untuk divalidasi.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </main>

<!-- Modal Ganti Password -->
<div class="modal fade" id="modalGantiPasswordDiri" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: #1d1e24; border: 1px solid #2a2d35;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Ganti Password Pribadi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <?= csrf_field(); ?>
                <div class="modal-body p-4">
                    <div class="mb-3 position-relative">
                        <label class="form-label fw-bold text-secondary">Password Lama</label>
                        <div class="input-group">
                            <input type="password" name="password_lama" class="form-control sas-input focus-lab" placeholder="Masukkan password lama" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button"><i class="bi bi-eye-slash"></i></button>
                        </div>
                    </div>
                    <div class="mb-3 position-relative">
                        <label class="form-label fw-bold text-secondary">Password Baru</label>
                        <div class="input-group">
                            <input type="password" name="password_baru" class="form-control sas-input focus-lab" placeholder="Masukkan password baru" required minlength="4">
                            <button class="btn btn-outline-secondary toggle-password" type="button"><i class="bi bi-eye-slash"></i></button>
                        </div>
                    </div>
                    <div class="mb-3 position-relative">
                        <label class="form-label fw-bold text-secondary">Konfirmasi Password Baru</label>
                        <div class="input-group">
                            <input type="password" name="konfirmasi_password" class="form-control sas-input focus-lab" placeholder="Ulangi password baru" required minlength="4">
                            <button class="btn btn-outline-secondary toggle-password" type="button"><i class="bi bi-eye-slash"></i></button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="ganti_password_diri" class="btn btn-sas btn-sas-lab fw-bold">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function switchTab(tabId) {
        document.querySelectorAll('.tab-panel').forEach(el => el.classList.remove('active'));
        document.getElementById('tab-' + tabId).classList.add('active');
        
        document.querySelectorAll('.sidebar .nav-link-custom').forEach(el => el.classList.remove('active'));
        event.target.closest('.nav-link-custom').classList.add('active');
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
</script>
</body>
</html>