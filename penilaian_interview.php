<?php
session_start();
require_once __DIR__ . '/koneksiweb.php';
require_once __DIR__ . '/csrf_helper.php';
verify_csrf_token();

// Proteksi halaman
if (empty($_SESSION['Admin Lab']['id_user'])) {
    header("Location: login.php");
    exit();
}

$id_admin_session = (int)$_SESSION['Admin Lab']['id_user'];
$query_admin = "SELECT id_admin_lab, nama_admin FROM admin_lab WHERE id_user = $id_admin_session LIMIT 1";
$hasil_admin = mysqli_query($koneksi, $query_admin);
$data_admin  = mysqli_fetch_assoc($hasil_admin);
$id_admin_lab = $data_admin['id_admin_lab'];
$nama_admin = $data_admin['nama_admin'] ?? $_SESSION['Admin Lab']['username'];

// PROSES SIMPAN / UPDATE WAWANCARA
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_wawancara'])) {
    $nim = mysqli_real_escape_string($koneksi, trim($_POST['nim']));
    $jadwal = mysqli_real_escape_string($koneksi, trim($_POST['jadwal']));
    $link = mysqli_real_escape_string($koneksi, trim($_POST['link_meet']));
    $nilai = empty($_POST['nilai']) ? 'NULL' : (float)$_POST['nilai'];
    $status = mysqli_real_escape_string($koneksi, trim($_POST['status']));

    // Cek apakah data sudah ada
    $cek = mysqli_query($koneksi, "SELECT nim FROM wawancara WHERE nim = '$nim'");
    if (mysqli_num_rows($cek) > 0) {
        $q = "UPDATE wawancara SET jadwal = " . (empty($jadwal) ? "NULL" : "'$jadwal'") . ", link_meet = '$link', nilai = $nilai, status = '$status', id_admin_lab = $id_admin_lab WHERE nim = '$nim'";
    } else {
        $q = "INSERT INTO wawancara (nim, jadwal, link_meet, nilai, status, id_admin_lab) VALUES ('$nim', " . (empty($jadwal) ? "NULL" : "'$jadwal'") . ", '$link', $nilai, '$status', $id_admin_lab)";
    }
    
    mysqli_query($koneksi, $q);
    echo "<script>alert('Data wawancara berhasil disimpan!'); window.location.href='penilaian_interview.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian Interview - Admin Lab</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
    <a class="navbar-brand text-secondary fw-bold fs-6" href="#">Admin Lab Portal</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
</nav>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse p-3">
    <div class="text-center my-3">
        <h5 class="fw-bold text-secondary">Admin Lab Portal</h5>
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

        <!-- KONTEN UTAMA -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                <h2>Penjadwalan & Penilaian Interview</h2>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-light fs-6 px-3 py-2">Admin: <?= htmlspecialchars($nama_admin); ?></span>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>NIM</th>
                                    <th>Nama Mahasiswa</th>
                                    <th>Jadwal (Waktu)</th>
                                    <th>Link Meeting</th>
                                    <th>Nilai</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $q = "SELECT m.nim, m.nama_mahasiswa, w.jadwal, w.link_meet, w.nilai, w.status 
                                      FROM mahasiswa m 
                                      LEFT JOIN wawancara w ON m.nim = w.nim 
                                      ORDER BY m.nim ASC";
                                $res = mysqli_query($koneksi, $q);
                                
                                if(mysqli_num_rows($res) > 0) {
                                    while($row = mysqli_fetch_assoc($res)) {
                                        $j = $row['jadwal'] ? date('Y-m-d\TH:i', strtotime($row['jadwal'])) : '';
                                        $s = $row['status'] ?? 'Menunggu';
                                        ?>
                                        <form method="POST">
    <?= csrf_field(); ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['nim']) ?>
                                                    <input type="hidden" name="nim" value="<?= htmlspecialchars($row['nim']) ?>">
                                                </td>
                                                <td><?= htmlspecialchars($row['nama_mahasiswa']) ?></td>
                                                <td><input type="datetime-local" class="form-control sas-input form-control sas-input-sm" name="jadwal" value="<?= $j ?>"></td>
                                                <td><input type="text" class="form-control sas-input form-control sas-input-sm" name="link_meet" placeholder="URL Zoom/GMeet" value="<?= htmlspecialchars($row['link_meet'] ?? '') ?>"></td>
                                                <td><input type="number" step="0.1" max="100" class="form-control sas-input form-control sas-input-sm" name="nilai" placeholder="0-100" style="width: 80px;" value="<?= htmlspecialchars($row['nilai'] ?? '') ?>"></td>
                                                <td>
                                                    <select name="status" class="form-select form-select-sm">
                                                        <option value="Menunggu" <?= ($s == 'Menunggu') ? 'selected' : '' ?>>Menunggu</option>
                                                        <option value="Lulus" <?= ($s == 'Lulus') ? 'selected' : '' ?>>Lulus</option>
                                                        <option value="Tidak Lulus" <?= ($s == 'Tidak Lulus') ? 'selected' : '' ?>>Tidak Lulus</option>
                                                    </select>
                                                </td>
                                                <td class="text-center">
                                                    <button type="submit" name="simpan_wawancara" class="btn btn-sm btn-sas btn-sas-lab">Simpan</button>
                                                </td>
                                            </tr>
                                        </form>
                                        <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='text-center py-4'>Belum ada data mahasiswa</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
