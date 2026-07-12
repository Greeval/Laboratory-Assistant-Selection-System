<?php
// FASE 3: DASHBOARD ADMIN - KEPUTUSAN SELEKSI (PROSES 6.0)
session_start();
require_once __DIR__ . '/koneksiweb.php';
require_once __DIR__ . '/csrf_helper.php';
verify_csrf_token();

// Proteksi halaman
if (empty($_SESSION['Admin Lab']['id_user'])) {
    header("Location: login.php");
    exit();
}

$id_user_session = (int)$_SESSION['Admin Lab']['id_user'];
$username_admin  = $_SESSION['Admin Lab']['username'];

// Ambil ID Admin Lab
$q_admin_lab = mysqli_query($koneksi, "SELECT id_admin_lab FROM admin_lab WHERE id_user = $id_user_session LIMIT 1");
$d_admin_lab = mysqli_fetch_assoc($q_admin_lab);
$id_admin_lab_real = $d_admin_lab ? (int)$d_admin_lab['id_admin_lab'] : 0;

// Proses aksi keputusan
if (isset($_POST['aksi_keputusan'])) {
    $nim_input = mysqli_real_escape_string($koneksi, $_POST['nim']);
    $status_kelulusan = mysqli_real_escape_string($koneksi, $_POST['status_kelulusan']);
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    $skor_edit = isset($_POST['skor_ujian']) ? (int)$_POST['skor_ujian'] : null;
    
    // Update skor ujian jika ada
    if ($skor_edit !== null) {
        mysqli_query($koneksi, "UPDATE sesi_ujian SET skor_ujian = $skor_edit WHERE nim = '$nim_input'");
    }

    // Cek apakah data hasil_akhir sudah ada
    $cek_hasil = mysqli_query($koneksi, "SELECT id_hasil FROM hasil_akhir WHERE nim = '$nim_input'");
    
    if (mysqli_num_rows($cek_hasil) > 0) {
        $query_save = "UPDATE hasil_akhir SET status_kelulusan = '$status_kelulusan', keterangan = '$keterangan', id_admin_lab = $id_admin_lab_real WHERE nim = '$nim_input'";
    } else {
        $query_save = "INSERT INTO hasil_akhir (nim, status_kelulusan, keterangan, id_admin_lab) VALUES ('$nim_input', '$status_kelulusan', '$keterangan', $id_admin_lab_real)";
    }
    
    if (mysqli_query($koneksi, $query_save)) {
        echo "<script>alert('Keputusan seleksi berhasil disimpan!'); window.location.href = 'keputusan_seleksi.php';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan keputusan. Silakan coba lagi.');</script>";
    }
}

// Fitur pencarian & penyaringan
$search_query = "";
$filter_jurusan = "";
$kondisi = [];

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = mysqli_real_escape_string($koneksi, trim($_GET['search']));
    $kondisi[] = "(m.nama_mahasiswa LIKE '%$search%' OR m.nim LIKE '%$search%')";
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

// 4. AMBIL DATA PESERTA (JOIN mahasiswa, pemberkasan, sesi_ujian, hasil_akhir)
$query_tampil = "SELECT 
                    m.nim, m.nama_mahasiswa, m.jurusan,
                    p.status_validasi, 
                    s.skor_ujian, s.tanggal_ujian,
                    h.status_kelulusan, h.keterangan,
                    al.nama_admin as nama_admin_keputusan
                 FROM mahasiswa m
                 LEFT JOIN pemberkasan p ON m.nim = p.nim
                 LEFT JOIN sesi_ujian s ON m.nim = s.nim
                 LEFT JOIN hasil_akhir h ON m.nim = h.nim
                 LEFT JOIN admin_lab al ON h.id_admin_lab = al.id_admin_lab
                 $where_clause
                 ORDER BY m.nim ASC";
$hasil_tampil = mysqli_query($koneksi, $query_tampil);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keputusan Seleksi - Admin Lab</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/saslab-design.css">
    <style>
        /* Layout Specific */
        body { background-color: var(--background); }
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
        
        .nav-link-custom, .sidebar .nav-link {
            color: var(--ink-soft);
            font-weight: 600;
            padding: 12px 24px;
            text-decoration: none;
            display: block;
            transition: all 0.2s;
            margin: 4px 16px;
            border-radius: 999px;
        }
        .nav-link-custom:hover, .sidebar .nav-link:hover {
            background-color: var(--surface-alt);
            color: var(--on-surface);
        }
        .sidebar .nav-link-custom.active, .sidebar .nav-link.active {
            background-color: var(--admin-lab-bg);
            color: var(--admin-lab-ink) !important;
        }

        .main-content, main {
            margin-left: 260px;
            transition: 0.3s;
            min-height: 100vh;
            background-color: var(--background);
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
        
        .navbar-custom, .navbar {
            background-color: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 12px 24px;
        }

        @media (max-width: 768px) {
            .sidebar { width: 280px; left: -280px; z-index: 1050; }
            .sidebar.active { left: 0; }
            .main-content, main { margin-left: 0 !important; }
            .sidebar-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1040; display: none; }
            .sidebar-overlay.active { display: block; }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- SIDEBAR -->
        <!-- Mobile Navbar Toggler (Visible only on small screens) -->
<nav class="navbar navbar-light d-md-none px-3 py-2" style="">
    <a class="navbar-brand text-dark fw-bold fs-6" href="#">Admin Lab Portal</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
</nav>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse p-3">
    <div class="text-center my-3">
        <h5 class="fw-bold text-dark">Admin Lab Portal</h5>
        <hr>
    </div>
    <ul class="nav flex-column">
        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
        <li class="nav-item">
            <a class="nav-link" href="dashboard_admin_lab.php?tab=profil"><i class="bi bi-person-fill me-2"></i>Profil</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page == 'dashboard_admin_lab.php') ? 'active' : '' ?>" href="dashboard_admin_lab.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page == 'kelola_soal.php') ? 'active' : '' ?>" href="kelola_soal.php"><i class="bi bi-journal-text me-2"></i>Kelola Soal</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page == 'penilaian_ujian.php' || $current_page == 'penilaian_interview.php') ? 'active' : '' ?>" href="penilaian_ujian.php"><i class="bi bi-pencil-square me-2"></i>Penilaian</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page == 'jadwal_interview.php') ? 'active' : '' ?>" href="jadwal_interview.php"><i class="bi bi-camera-video me-2"></i>Interview</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page == 'keputusan_seleksi.php') ? 'active' : '' ?>" href="keputusan_seleksi.php"><i class="bi bi-trophy me-2"></i>Hasil Seleksi</a>
        </li>
        <li class="nav-item mt-3">
            <a class="nav-link text-warning fw-bold" href="logout.php?role=Admin%20Lab" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                <i class="bi bi-box-arrow-left me-2"></i>Logout
            </a>
        </li>
    </ul>
</nav>

        <!-- MAIN CONTENT -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-4 pb-2 mb-3 border-bottom">
                <h2 class="h3 fw-bold text-slate-800">Keputusan Hasil Seleksi Aslab</h2>
                <span class="badge bg-light p-2 fs-6">Admin: <?= htmlspecialchars($username_admin); ?></span>
            </div>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <input type="text" name="search" class="form-control sas-input" placeholder="Cari Nama / NIM..." value="<?= htmlspecialchars($search_query) ?>">
                        </div>
                        <div class="col-md-4">
                            <select name="filter_jurusan" class="form-select">
                                <option value="">-- Semua Program Studi --</option>
                                <option value="Informatika" <?= $filter_jurusan === 'Informatika' ? 'selected' : '' ?>>Informatika</option>
                                <option value="Sistem Informasi" <?= $filter_jurusan === 'Sistem Informasi' ? 'selected' : '' ?>>Sistem Informasi</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sas btn-sas-lab w-100"><i class="bi bi-search"></i> Cari</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-5">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>NIM</th>
                                    <th>Nama Pelamar</th>
                                    <th>Program Studi</th>
                                    <th>Status Berkas</th>
                                    <th>Skor Ujian (Total)</th>
                                    <th>Hasil Seleksi</th>
                                    <th>Diputuskan Oleh</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($hasil_tampil) > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($hasil_tampil)): ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($row['nim']); ?></td>
                                            <td><?= htmlspecialchars($row['nama_mahasiswa']); ?></td>
                                            <td><?= htmlspecialchars($row['jurusan'] ?? '-'); ?></td>
                                            
                                            <td>
                                                <?php 
                                                    $val = $row['status_validasi'] ?? 'Belum Upload';
                                                    if ($val === 'Valid') $b_class = 'bg-success';
                                                    elseif ($val === 'Tidak Valid') $b_class = 'bg-danger';
                                                    else $b_class = 'bg-warning text-dark';
                                                ?>
                                                <span class="badge <?= $b_class; ?>"><?= htmlspecialchars($val); ?></span>
                                            </td>
                                            
                                            <td>
                                                <?php if ($row['skor_ujian'] !== null): ?>
                                                    <span class="badge bg-primary fs-6"><?= htmlspecialchars($row['skor_ujian']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Belum Ujian</span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <td>
                                                <?php 
                                                    $hasil = $row['status_kelulusan'] ?? 'Proses';
                                                    if ($hasil === 'Lulus') $h_class = 'bg-success';
                                                    elseif ($hasil === 'Tidak Lulus') $h_class = 'bg-danger';
                                                    else $h_class = 'bg-secondary';
                                                ?>
                                                <span class="badge <?= $h_class; ?>"><?= htmlspecialchars($hasil); ?></span>
                                            </td>
                                            
                                            <td class="text-center">
                                                <?php if (!empty($row['nama_admin_keputusan'])): ?>
                                                    <span class="text-muted small"><?= htmlspecialchars($row['nama_admin_keputusan']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <td class="text-center">
                                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalKeputusan<?= $row['nim']; ?>">
                                                    <i class="bi bi-pencil-square"></i> Update Hasil
                                                </button>
                                            </td>
                                        </tr>
                                        
                                        <!-- Modal Update Hasil -->
                                        <div class="modal fade" id="modalKeputusan<?= $row['nim']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="">
    <?= csrf_field(); ?>
                                                        <div class="modal-header bg-warning text-dark">
                                                            <h5 class="modal-title">Update Hasil: <?= htmlspecialchars($row['nama_mahasiswa']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body text-start">
                                                            <input type="hidden" name="nim" value="<?= htmlspecialchars($row['nim']); ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Skor Ujian (Termasuk Nilai Essay)</label>
                                                                <input type="number" name="skor_ujian" class="form-control sas-input" value="<?= $row['skor_ujian'] !== null ? $row['skor_ujian'] : '0'; ?>" min="0" max="100" <?= $row['skor_ujian'] === null ? 'readonly' : '' ?>>
                                                                <?php if ($row['skor_ujian'] === null): ?>
                                                                    <small class="text-primary">Mahasiswa belum mengikuti ujian.</small>
                                                                <?php else: ?>
                                                                    <small class="text-muted">Bisa di-override jika ada nilai tambahan (essay/wawancara).</small>
                                                                <?php endif; ?>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Status Kelulusan</label>
                                                                <select name="status_kelulusan" class="form-select" required>
                                                                    <option value="Proses" <?= ($row['status_kelulusan'] ?? 'Proses') === 'Proses' ? 'selected' : ''; ?>>Proses</option>
                                                                    <option value="Lulus" <?= ($row['status_kelulusan'] ?? '') === 'Lulus' ? 'selected' : ''; ?>>Lulus</option>
                                                                    <option value="Tidak Lulus" <?= ($row['status_kelulusan'] ?? '') === 'Tidak Lulus' ? 'selected' : ''; ?>>Tidak Lulus</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Keterangan Tambahan</label>
                                                                <textarea name="keterangan" class="form-control sas-input" rows="3"><?= htmlspecialchars($row['keterangan'] ?? ''); ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" name="aksi_keputusan" class="btn btn-sas btn-sas-lab">Simpan Keputusan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">Belum ada data mahasiswa.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
