<?php
session_start();
require_once __DIR__ . '/koneksiweb.php';
require_once __DIR__ . '/csrf_helper.php';
verify_csrf_token();

// Proteksi halaman
if (empty($_SESSION['Mahasiswa']['id_user'])) {
    header("Location: login.php");
    exit();
}

$id_user_session  = (int)$_SESSION['Mahasiswa']['id_user'];  
$username_session = mysqli_real_escape_string($koneksi, $_SESSION['Mahasiswa']['username']); 

// Ambil data mahasiswa
$query_mhs = "SELECT * FROM mahasiswa WHERE id_user = $id_user_session LIMIT 1";
$hasil_mhs = mysqli_query($koneksi, $query_mhs);
$data_mhs  = mysqli_fetch_assoc($hasil_mhs);

$nama_lengkap  = !empty($data_mhs['nama_mahasiswa']) ? trim($data_mhs['nama_mahasiswa']) : '';
$semester      = !empty($data_mhs['semester']) ? $data_mhs['semester'] : '';
$ipk           = !empty($data_mhs['ipk']) ? $data_mhs['ipk'] : '';
$jurusan       = !empty($data_mhs['jurusan']) ? $data_mhs['jurusan'] : '';
$fakultas      = !empty($data_mhs['fakultas']) ? $data_mhs['fakultas'] : '';
$no_whatsapp   = !empty($data_mhs['no_whatsapp']) ? $data_mhs['no_whatsapp'] : '';
$email         = !empty($data_mhs['email']) ? $data_mhs['email'] : '';
$foto_profil   = !empty($data_mhs['foto_profil']) ? $data_mhs['foto_profil'] : '';
$nim_tampilan  = !empty($data_mhs['nim']) ? $data_mhs['nim'] : $username_session;

// Ambil data pemberkasan & ujian
$query_berkas = "SELECT * FROM pemberkasan WHERE nim = '$nim_tampilan' LIMIT 1";
$hasil_berkas = mysqli_query($koneksi, $query_berkas);
$data_berkas  = mysqli_fetch_assoc($hasil_berkas);
$status_validasi = $data_berkas['status_validasi'] ?? 'Belum Upload';

$query_ujian = "SELECT COUNT(*) as total_jawab FROM sesi_ujian WHERE nim = '$nim_tampilan'";
$hasil_ujian = mysqli_query($koneksi, $query_ujian);
$data_ujian = mysqli_fetch_assoc($hasil_ujian);
$sudah_ujian = $data_ujian['total_jawab'] > 0;

$query_hasil = "SELECT status_kelulusan, keterangan FROM hasil_akhir WHERE nim = '$nim_tampilan' LIMIT 1";
$hasil_akhir_db = mysqli_query($koneksi, $query_hasil);
$data_hasil = mysqli_fetch_assoc($hasil_akhir_db);
$status_kelulusan = $data_hasil['status_kelulusan'] ?? 'Proses';

// AMBIL DATA WAWANCARA/INTERVIEW
$query_wawancara = "SELECT * FROM wawancara WHERE nim = '$nim_tampilan' LIMIT 1";
$hasil_wawancara = mysqli_query($koneksi, $query_wawancara);
$data_wawancara = mysqli_fetch_assoc($hasil_wawancara);

// Tentukan Tahap Saat Ini
$current_stage = 1;
if ($status_kelulusan !== 'Proses') {
    $current_stage = 4;
} elseif ($sudah_ujian) {
    $current_stage = 3;
} elseif ($status_validasi === 'Valid') {
    $current_stage = 2;
}

// Simpan biodata & foto profil
if (isset($_POST['simpan_biodata'])) {
    $nim_input = mysqli_real_escape_string($koneksi, $_POST['nim']);
    $nama_input = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $semester_input = (int)$_POST['semester'];
    $ipk_input = (float)$_POST['ipk'];
    $jurusan_input = mysqli_real_escape_string($koneksi, $_POST['jurusan']);
    $fakultas_input = mysqli_real_escape_string($koneksi, $_POST['fakultas']);
    $wa_input = mysqli_real_escape_string($koneksi, $_POST['no_whatsapp']);
    $email_input = mysqli_real_escape_string($koneksi, $_POST['email']);
    
    // Foto Profil
    $nama_foto = $foto_profil;
    if (!empty($_FILES['foto_profil']['name']) && $_FILES['foto_profil']['error'] == 0) {
        $target_dir = "uploads/profil/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg','jpeg','png'];
        if (in_array(strtolower($ext), $allowed) && $_FILES['foto_profil']['size'] <= 2097152) {
            $nama_foto = "PROFIL_" . $nim_input . "_" . time() . "." . $ext;
            move_uploaded_file($_FILES['foto_profil']['tmp_name'], $target_dir . $nama_foto);
        }
    }
    
    $query_update = "UPDATE mahasiswa SET 
                     nim='$nim_input', nama_mahasiswa='$nama_input', semester=$semester_input, 
                     ipk=$ipk_input, jurusan='$jurusan_input', fakultas='$fakultas_input',
                     no_whatsapp='$wa_input', email='$email_input', foto_profil='$nama_foto'
                     WHERE id_user=$id_user_session";
    
    mysqli_query($koneksi, $query_update);
    echo "<script>alert('Biodata berhasil disimpan!'); window.location.href='dashboard_mahasiswa.php?tab=profil';</script>";
    exit();
}

// Proses baca notifikasi
if (isset($_POST['tandai_dibaca'])) {
    mysqli_query($koneksi, "UPDATE notifikasi SET is_read = 1 WHERE nim = '$nim_tampilan'");
    echo "<script>window.location.href='dashboard_mahasiswa.php';</script>";
    exit();
}

// Ambil notifikasi
$q_notif = mysqli_query($koneksi, "SELECT * FROM notifikasi WHERE nim = '$nim_tampilan' ORDER BY waktu DESC");
$unread_notif = 0;
$notif_list = [];
if ($q_notif) {
    while ($r = mysqli_fetch_assoc($q_notif)) {
        $notif_list[] = $r;
        if ($r['is_read'] == 0) $unread_notif++;
    }
}

// Proses ganti password
if (isset($_POST['ganti_password'])) {
    $pw_lama = $_POST['password_lama'];
    $pw_baru = $_POST['password_baru'];
    $pw_konf = $_POST['konfirmasi_password'];
    
    if ($pw_baru !== $pw_konf) {
        echo "<script>alert('Konfirmasi password baru tidak cocok!'); window.location.href='dashboard_mahasiswa.php?tab=profil';</script>";
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
            echo "<script>alert('Password lama salah!'); window.location.href='dashboard_mahasiswa.php?tab=profil';</script>";
            exit();
        }
        
        $pw_hash = password_hash($pw_baru, PASSWORD_DEFAULT);
        $q_pw = "UPDATE akun SET password = '$pw_hash' WHERE id_user = $id_user_target";
        if (mysqli_query($koneksi, $q_pw)) {
            echo "<script>alert('Password berhasil diubah.'); window.location.href='dashboard_mahasiswa.php?tab=profil';</script>";
        } else {
            echo "<script>alert('Gagal mengubah password.'); window.location.href='dashboard_mahasiswa.php?tab=profil';</script>";
        }
    }
    exit();
}

// Simpan berkas
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_berkas'])) {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }
    
    $cv_name = $data_berkas['cv'] ?? '';
    $ts_name = $data_berkas['transkrip_nilai'] ?? '';

    if (!empty($_FILES['cv']['name']) && $_FILES['cv']['error'] == 0) {
        $ext_cv = pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION);
        if (strtolower($ext_cv) == 'pdf' && $_FILES['cv']['size'] <= 2097152 && mime_content_type($_FILES['cv']['tmp_name']) == 'application/pdf') {
            $cv_name = "CV_" . $nim_tampilan . "_" . time() . ".pdf";
            move_uploaded_file($_FILES['cv']['tmp_name'], $target_dir . $cv_name);
        }
    }
    if (!empty($_FILES['transkrip']['name']) && $_FILES['transkrip']['error'] == 0) {
        $ext_ts = pathinfo($_FILES['transkrip']['name'], PATHINFO_EXTENSION);
        if (strtolower($ext_ts) == 'pdf' && $_FILES['transkrip']['size'] <= 2097152) {
            $ts_name = "TS_" . $nim_tampilan . "_" . time() . ".pdf";
            move_uploaded_file($_FILES['transkrip']['tmp_name'], $target_dir . $ts_name);
        }
    }

    if ($hasil_berkas && mysqli_num_rows($hasil_berkas) > 0) {
        $query_save_berkas = "UPDATE pemberkasan SET cv = '$cv_name', transkrip_nilai = '$ts_name', status_validasi = 'Pending' WHERE nim = '$nim_tampilan'";
    } else {
        $query_save_berkas = "INSERT INTO pemberkasan (nim, cv, transkrip_nilai, status_validasi) VALUES ('$nim_tampilan', '$cv_name', '$ts_name', 'Pending')";
    }
    mysqli_query($koneksi, $query_save_berkas);
    echo "<script>alert('Berkas berhasil diupload!'); window.location.href='dashboard_mahasiswa.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa</title>
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
            background-color: var(--mahasiswa-bg);
            color: var(--mahasiswa-ink) !important;
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

        /* Tracker progress adjustment */
        .tracker-wrapper { position: relative; margin: 40px auto; max-width: 900px; }
        .progress-bar-bg { position: absolute; top: 20px; left: 10%; right: 10%; height: 4px; background-color: var(--border); z-index: 1; border-radius: 4px;}
        .progress-bar-fill { position: absolute; top: 20px; left: 10%; height: 4px; background-color: var(--mahasiswa-ink); z-index: 2; transition: width 0.4s ease; border-radius: 4px;}
        .tracker-steps { display: flex; justify-content: space-between; position: relative; z-index: 3; }
        .step-item { text-align: center; width: 120px; }
        .step-circle {
            width: 44px; height: 44px; border-radius: 50%;
            background-color: var(--surface); border: 2px solid var(--border-strong);
            margin: 0 auto 10px auto; display: flex; align-items: center; justify-content: center;
            font-weight: bold; color: var(--muted);
        }
        .step-item.active .step-circle, .step-item.done .step-circle {
            background-color: var(--mahasiswa-bg);
            border-color: var(--mahasiswa-ink);
            color: var(--mahasiswa-ink);
        }
        .step-item.done .step-circle {
            background-color: var(--mahasiswa-ink);
            color: #fff;
        }

        @media (max-width: 768px) {
            .sidebar { width: 280px; left: -280px; z-index: 1050; }
            .sidebar.active { left: 0; }
            .main-content { margin-left: 0; }
            .sidebar-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1040; display: none; }
            .sidebar-overlay.active { display: block; }
            
            .step-item { width: auto; flex: 1; }
            .step-circle { width: 36px; height: 36px; font-size: 0.9rem; }
            .progress-bar-bg, .progress-bar-fill { top: 16px; }
        }
    </style>
</head>
<body>
    
    <!-- OVERLAY BACKDROP UNTUK MOBILE -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <span>Portal Mahasiswa</span>
            <button class="btn btn-sm btn-outline-dark border-0 d-md-none" onclick="toggleSidebar()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="mt-3">
            <a class="nav-link-custom" id="nav-profil" href="#" onclick="switchTab('profil')">
                <i class="bi bi-person-fill me-2"></i> Profil
            </a>
            <a class="nav-link-custom active" id="nav-dashboard" href="#" onclick="switchTab('dashboard')">
                <i class="bi bi-grid-fill me-2"></i> Dashboard
            </a>
            <a class="nav-link-custom" href="ujian_mahasiswa.php">
                <i class="bi bi-pencil-square me-2"></i> Ujian Seleksi
            </a>
            <a class="nav-link-custom mt-4 text-warning" href="logout.php?role=Mahasiswa" onclick="return confirm('Keluar?')">
                <i class="bi bi-box-arrow-left me-2"></i> Log Out
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="mainContent">
        
        <!-- NAVBAR ATAS -->
        <nav class="navbar navbar-light navbar-custom px-3">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-dark border-0 me-3" onclick="toggleSidebar()">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <div class="navbar-brand mb-0 h1">
                    <span class="fw-bold text-dark">SASLAB</span>
                    <span class="brand-subtext">Seleksi Asisten Laboratorium</span>
                </div>
            </div>
            <div class="text-dark d-flex align-items-center">
                <!-- Dropdown Notifikasi -->
                <div class="dropdown me-3">
                    <a href="#" class="text-dark position-relative text-decoration-none" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell-fill fs-5"></i>
                        <?php if ($unread_notif > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                                <?= $unread_notif ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="notifDropdown" style="width: 320px; max-height: 400px; overflow-y: auto;">
                        <li class="dropdown-header d-flex justify-content-between align-items-center">
                            <strong class="text-dark">Notifikasi</strong>
                            <?php if ($unread_notif > 0): ?>
                                <form method="POST" class="m-0">
    <?= csrf_field(); ?>
                                    <button type="submit" name="tandai_dibaca" class="btn btn-sm btn-link text-decoration-none text-primary p-0">Tandai dibaca</button>
                                </form>
                            <?php endif; ?>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if (count($notif_list) > 0): ?>
                            <?php foreach ($notif_list as $notif): ?>
                                <li>
                                    <div class="dropdown-item py-2 <?= $notif['is_read'] == 0 ? 'bg-light' : '' ?>" style="white-space: normal;">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1 fw-bold fs-6 text-truncate"><?= htmlspecialchars($notif['judul']) ?></h6>
                                            <small class="text-muted" style="font-size: 0.7rem;"><?= date('d M H:i', strtotime($notif['waktu'])) ?></small>
                                        </div>
                                        <p class="mb-1 text-muted" style="font-size: 0.8rem;"><?= nl2br(htmlspecialchars($notif['pesan'])) ?></p>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider m-0"></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><span class="dropdown-item text-center text-muted py-3">Belum ada notifikasi.</span></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div>
                    <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($username_session) ?>
                </div>
            </div>
        </nav>

        <!-- DASHBOARD TAB -->
        <div id="dashboard" class="tab-content-panel active">
            
            <div class="page-header">
                <div class="container-fluid px-4">
                    <h3 class="fw-bold mb-1">Halo, <?= htmlspecialchars($nama_lengkap ?: $username_session); ?></h3>
                    <p class="mb-0 text-dark-50">Pantau proses rekrutmen asisten laboratorium Anda di sini.</p>
                </div>
            </div>

            <div class="container-fluid px-4 pb-5">
                <div class="sas-card mb-4">
                    <div class="card-body p-5">
                        
                        <!-- PROGRESS TRACKER -->
                        <div class="tracker-wrapper">
                            <?php
                                // Hitung persentase untuk progress bar line (Ada 4 step, span line dari 10% ke 90% = 80% total)
                                // Jika stage 1 -> 0%
                                // Jika stage 2 -> 26.66%
                                // Jika stage 3 -> 53.33%
                                // Jika stage 4 -> 80% (Berhenti tepat di lingkaran Pengumuman)
                                $progress_width = ($current_stage - 1) * 26.66;
                            ?>
                            <div class="progress-bar-bg"></div>
                            <div class="progress-bar-fill" style="width: <?= $progress_width ?>%;"></div>
                            
                            <div class="tracker-steps">
                                <!-- Step 1 -->
                                <div class="step-item <?= ($current_stage > 1) ? 'done' : ($current_stage == 1 ? 'active' : '') ?>">
                                    <div class="step-circle"><i class="bi bi-file-earmark-text-fill"></i></div>
                                    <div class="step-label">Pemberkasan</div>
                                </div>
                                <!-- Step 2 -->
                                <div class="step-item <?= ($current_stage > 2) ? 'done' : ($current_stage == 2 ? 'active' : '') ?>">
                                    <div class="step-circle"><i class="bi bi-pencil-square"></i></div>
                                    <div class="step-label">Ujian</div>
                                </div>
                                <!-- Step 3 -->
                                <div class="step-item <?= ($current_stage > 3) ? 'done' : ($current_stage == 3 ? 'active' : '') ?>">
                                    <div class="step-circle"><i class="bi bi-camera-video-fill"></i></div>
                                    <div class="step-label">Interview</div>
                                </div>
                                <!-- Step 4 -->
                                <div class="step-item <?= ($current_stage == 4) ? 'active' : '' ?>">
                                    <div class="step-circle"><i class="bi bi-award-fill"></i></div>
                                    <div class="step-label">Pengumuman</div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-5">

                        <!-- KONTEN BERDASARKAN TAHAP AKTIF -->
                        <div class="row justify-content-center">
                            <div class="col-lg-8">
                                
                                <?php if ($current_stage == 1): ?>
                                    <div class="text-center mb-4">
                                        <h4 class="fw-bold">Tahap 1: Pemberkasan</h4>
                                        <p class="text-muted">Silakan lengkapi dan unggah berkas pendaftaran Anda.</p>
                                    </div>

                                    <?php if ($status_validasi === 'Pending' || $status_validasi === 'Tidak Valid'): ?>
                                        <div class="alert <?= $status_validasi == 'Pending' ? 'alert-warning sas-badge-warning' : 'alert-danger sas-badge-danger' ?> d-flex align-items-center mb-4">
                                            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                                            <div>
                                                <strong>Status: <?= htmlspecialchars($status_validasi) ?>.</strong><br>
                                                <?= $status_validasi == 'Pending' ? 'Berkas Anda sedang menunggu pengecekan Admin.' : 'Berkas Anda ditolak, silakan perbaiki dan unggah ulang.' ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" action="" enctype="multipart/form-data" class="p-4 rounded border" >
                                        <?= csrf_field(); ?>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Curriculum Vitae (CV) <small class="text-muted fw-normal">- PDF, maks 2MB</small></label>
                                            <input type="file" name="cv" class="form-control sas-input focus-mahasiswa" accept=".pdf">
                                            <?php if (!empty($data_berkas['cv'])): ?>
                                                <small class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Sudah ada: <?= htmlspecialchars($data_berkas['cv']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Transkrip Nilai <small class="text-muted fw-normal">- PDF, maks 2MB</small></label>
                                            <input type="file" name="transkrip" class="form-control sas-input focus-mahasiswa" accept=".pdf">
                                            <?php if (!empty($data_berkas['transkrip_nilai'])): ?>
                                                <small class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Sudah ada: <?= htmlspecialchars($data_berkas['transkrip_nilai']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <button type="submit" name="simpan_berkas" class="btn btn-sas btn-sas-mahasiswa fw-bold w-100">
                                            <i class="bi bi-upload me-2"></i>Unggah Berkas
                                        </button>
                                    </form>

                                <?php elseif ($current_stage == 2): ?>
                                    <div class="text-center">
                                        <i class="bi bi-pencil-square display-3 text-primary mb-3 d-block"></i>
                                        <h4 class="fw-bold">Tahap 2: Ujian Seleksi</h4>
                                        <p class="text-muted">Berkas Anda telah divalidasi. Silakan kerjakan ujian seleksi.</p>
                                        <a href="ujian_mahasiswa.php" class="btn btn-sas btn-sas-mahasiswa fw-bold px-5 mt-2">
                                            <i class="bi bi-arrow-right-circle me-2"></i>Mulai Ujian
                                        </a>
                                    </div>

                                <?php elseif ($current_stage == 3): ?>
                                    <div class="text-center">
                                        <i class="bi bi-camera-video-fill display-3 text-primary mb-3 d-block"></i>
                                        <h4 class="fw-bold">Tahap 3: Interview</h4>
                                        
                                        <?php 
                                            $status_interview = $data_wawancara['status'] ?? 'Menunggu';
                                        ?>

                                        <?php if ($status_interview === 'Lulus'): ?>
                                            <div class="alert alert-success sas-badge-success mt-3 mb-4">
                                                <h5 class="fw-bold mb-1"><i class="bi bi-check-circle-fill me-2"></i>Anda Lulus Tahap Interview!</h5>
                                                <p class="mb-0">Selamat! Anda telah melewati tahap wawancara. Silakan tunggu pengumuman hasil akhir pada Tahap Pengumuman.</p>
                                            </div>
                                        <?php elseif ($status_interview === 'Tidak Lulus'): ?>
                                            <div class="alert alert-danger sas-badge-danger mt-3 mb-4">
                                                <h5 class="fw-bold mb-1"><i class="bi bi-x-circle-fill me-2"></i>Anda Tidak Lulus Tahap Interview</h5>
                                                <p class="mb-0">Mohon maaf, Anda tidak lolos pada tahap wawancara ini. Terima kasih atas partisipasi Anda.</p>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted">Ujian selesai. Tunggu jadwal interview dari Admin Lab.</p>
                                        <?php endif; ?>

                                        <?php if (!empty($data_wawancara)): ?>
                                            <div class="alert alert-info sas-badge-info mt-3 text-start">
                                                <h6 class="fw-bold"><i class="bi bi-calendar-event me-2"></i>Jadwal Interview</h6>
                                                <p class="mb-1"><strong>Jadwal:</strong> <?= htmlspecialchars($data_wawancara['jadwal'] ?? '-') ?></p>
                                                <p class="mb-0"><strong>Lokasi / Link:</strong> <?= htmlspecialchars($data_wawancara['link_meet'] ?? '-') ?></p>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-warning sas-badge-warning mt-3">Jadwal interview belum ditentukan. Harap pantau notifikasi Anda.</div>
                                        <?php endif; ?>
                                    </div>

                                <?php elseif ($current_stage == 4): ?>
                                    <div class="text-center">
                                        <h4 class="fw-bold mb-1">Tahap Akhir: Pengumuman</h4>
                                        <p class="text-muted mb-4">Proses seleksi telah berakhir.</p>
                                        <?php if ($status_kelulusan === 'Lulus'): ?>
                                            <div class="card border-0 shadow-sm text-center p-5 bg-success text-dark">
                                                <i class="bi bi-patch-check-fill display-3 mb-3"></i>
                                                <h3 class="fw-bold">SELAMAT, ANDA LULUS!</h3>
                                                <p class="fs-5 mb-0">semoga betah</p>
                                                <?php if(!empty($data_hasil['keterangan'])): ?>
                                                    <hr class="border-white-50">
                                                    <p class="mb-0"><strong>Catatan:</strong> <?= htmlspecialchars($data_hasil['keterangan']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($status_kelulusan === 'Tidak Lulus'): ?>
                                            <div class="alert alert-danger sas-badge-danger d-inline-block px-5 py-4 w-100">
                                                <i class="bi bi-x-circle-fill display-3 d-block mb-3"></i>
                                                <h3 class="fw-bold">MOHON MAAF, ANDA TIDAK LULUS.</h3>
                                                <p class="fs-5 mb-0">Terima kasih atas partisipasi Anda dalam seleksi ini.</p>
                                                <?php if(!empty($data_hasil['keterangan'])): ?>
                                                    <hr>
                                                    <p class="mb-0"><strong>Catatan:</strong> <?= htmlspecialchars($data_hasil['keterangan']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-warning sas-badge-warning d-inline-block px-5 py-4 w-100">
                                                <i class="bi bi-hourglass-split display-3 d-block mb-3 text-dark"></i>
                                                <h3 class="fw-bold text-dark">SEDANG DALAM PROSES</h3>
                                                <p class="fs-5 mb-0 text-dark">Hasil seleksi Anda sedang diproses oleh Admin Lab.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>

                    </div> <!-- /card-body -->
                </div> <!-- /card -->
            </div> <!-- /container-fluid -->
        </div> <!-- /tab dashboard -->

        <!-- TAB PROFIL -->
        <div id="profil" class="tab-content-panel">
            <div class="page-header">
                <h2 class="h3 fw-bold mb-0">Profil Mahasiswa</h2>
                <span class="badge bg-light p-2 fs-6"><?= htmlspecialchars($nama_lengkap ?: $username_session) ?></span>
            </div>

            <div class="card border-0 shadow-sm mb-5 mx-4 mt-4" >
                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data">
                        <?= csrf_field(); ?>
                        <div class="row g-4">
                            <!-- Photo Column -->
                            <div class="col-md-3">
                                <div class="border rounded p-3 text-center h-100 d-flex flex-column justify-content-center" style="">
                                    <?php
                                        $foto_src = (!empty($foto_profil) && file_exists("uploads/profil/" . $foto_profil))
                                            ? "uploads/profil/" . htmlspecialchars($foto_profil)
                                            : "";
                                    ?>
                                    <?php if (!empty($foto_src)): ?>
                                        <img src="<?= $foto_src ?>" alt="Foto" class="img-fluid rounded mb-3" style="max-height:200px; object-fit:cover;">
                                    <?php else: ?>
                                        <div class="mb-3 d-flex align-items-center justify-content-center" style="height:150px; background:#131418; border-radius:10px; border: 1px solid #3d4050;">
                                            <i class="bi bi-person-fill" style="font-size:5rem; color: #3d4050;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <label class="form-label fw-bold text-secondary small">PHOTO PROFIL (Maks 2MB)</label>
                                    <input type="file" name="foto_profil" class="form-control sas-input focus-mahasiswa form-control sas-input focus-mahasiswa-sm" accept=".jpg,.jpeg,.png">
                                </div>
                            </div>

                            <!-- Biodata Column -->
                            <div class="col-md-9">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-secondary">NIM</label>
                                        <input type="text" name="nim" class="form-control sas-input focus-mahasiswa" value="<?= htmlspecialchars($nim_tampilan) ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-secondary">NAMA LENGKAP</label>
                                        <input type="text" name="nama" class="form-control sas-input focus-mahasiswa" value="<?= htmlspecialchars($nama_lengkap) ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-secondary">SEMESTER</label>
                                        <input type="number" name="semester" class="form-control sas-input focus-mahasiswa" value="<?= htmlspecialchars($semester) ?>" min="1" max="14">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-secondary">IPK</label>
                                        <input type="number" name="ipk" class="form-control sas-input focus-mahasiswa" value="<?= htmlspecialchars($ipk) ?>" step="0.01" min="0" max="4">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-secondary">PROGRAM STUDI</label>
                                        <input type="text" name="jurusan" class="form-control sas-input focus-mahasiswa" value="<?= htmlspecialchars($jurusan) ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-secondary">FAKULTAS</label>
                                        <input type="text" name="fakultas" class="form-control sas-input focus-mahasiswa" value="<?= htmlspecialchars($fakultas) ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-secondary">NO WHATSAPP</label>
                                        <input type="text" name="no_whatsapp" class="form-control sas-input focus-mahasiswa" value="<?= htmlspecialchars($no_whatsapp) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-secondary">EMAIL</label>
                                        <input type="email" name="email" class="form-control sas-input focus-mahasiswa" value="<?= htmlspecialchars($email) ?>">
                                    </div>
                                </div>

                                <div class="d-flex gap-2 mt-4">
                                    <button type="submit" name="simpan_biodata" class="btn btn-sas btn-sas-mahasiswa fw-bold px-4">SIMPAN PROFIL</button>
                                    <button type="button" class="btn btn-outline-secondary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalGantiPasswordDiri">GANTI PASSWORD</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div> <!-- /tab profil -->
    </div> <!-- /main-content -->

    <!-- Modal Ganti Password -->
    <div class="modal fade" id="modalGantiPasswordDiri" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background-color: #1d1e24; border: 1px solid #2a2d35;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Ganti Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <?= csrf_field(); ?>
                    <div class="modal-body p-4">
                        <div class="mb-3 position-relative">
                            <label class="form-label fw-bold text-secondary">Password Lama</label>
                            <div class="input-group">
                                <input type="password" name="password_lama" class="form-control sas-input focus-mahasiswa" placeholder="Masukkan password lama" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button"><i class="bi bi-eye-slash"></i></button>
                            </div>
                        </div>
                        <div class="mb-3 position-relative">
                            <label class="form-label fw-bold text-secondary">Password Baru</label>
                            <div class="input-group">
                                <input type="password" name="password_baru" class="form-control sas-input focus-mahasiswa" placeholder="Masukkan password baru" required minlength="4">
                                <button class="btn btn-outline-secondary toggle-password" type="button"><i class="bi bi-eye-slash"></i></button>
                            </div>
                        </div>
                        <div class="mb-3 position-relative">
                            <label class="form-label fw-bold text-secondary">Konfirmasi Password Baru</label>
                            <div class="input-group">
                                <input type="password" name="konfirmasi_password" class="form-control sas-input focus-mahasiswa" placeholder="Ulangi password baru" required minlength="4">
                                <button class="btn btn-outline-secondary toggle-password" type="button"><i class="bi bi-eye-slash"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="ganti_password" class="btn btn-sas btn-sas-mahasiswa fw-bold">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.toggle('closed');
            
            if (window.innerWidth >= 768) {
                mainContent.classList.toggle('expanded');
            } else {
                mainContent.classList.add('expanded'); // Di mobile main content harus selalu penuh
                if (sidebar.classList.contains('closed')) {
                    overlay.classList.remove('active');
                } else {
                    overlay.classList.add('active');
                }
            }
        }

        // Tab Switcher
        function switchTab(tabId) {
            document.querySelectorAll('.nav-link-custom').forEach(el => el.classList.remove('active'));
            document.getElementById('nav-' + tabId).classList.add('active');
            document.querySelectorAll('.tab-content-panel').forEach(el => el.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            if(window.innerWidth < 768) {
                document.getElementById('sidebar').classList.add('closed');
                document.getElementById('mainContent').classList.add('expanded');
                document.getElementById('sidebarOverlay').classList.remove('active');
            }
        }

        // Jalankan saat pertama kali dimuat untuk merespons mobile view
        window.addEventListener('DOMContentLoaded', () => {
            if(window.innerWidth < 768) {
                document.getElementById('sidebar').classList.add('closed');
                document.getElementById('mainContent').classList.add('expanded');
            }
        });

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

        // Initialize based on URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab) switchTab(tab);
    </script>
</body>
</html>
