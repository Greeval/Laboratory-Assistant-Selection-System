<?php
// FASE 3: PENILAIAN UJIAN (PROSES 5.4 - 5.5)
// Admin Lab melihat jawaban mahasiswa & memberikan nilai essay
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

// Proses update skor (jika form disubmit)
if (isset($_POST['aksi_skor'])) {
    $id_ujian = (int)$_POST['id_ujian'];
    $skor_baru = (int)$_POST['skor_ujian'];
    
    $query_update = "UPDATE sesi_ujian SET skor_ujian = $skor_baru WHERE id_ujian = $id_ujian";
    if (mysqli_query($koneksi, $query_update)) {
        echo "<script>alert('Skor ujian berhasil diperbarui!'); window.location.href = 'penilaian_ujian.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui skor. Silakan coba lagi.');</script>";
    }
}

// 3. AMBIL DATA SESI UJIAN (JOIN mahasiswa)
$query_sesi = "SELECT s.id_ujian, s.nim, s.tanggal_ujian, s.skor_ujian, m.nama_mahasiswa
               FROM sesi_ujian s
               INNER JOIN mahasiswa m ON s.nim = m.nim
               ORDER BY s.tanggal_ujian DESC";
$hasil_sesi = mysqli_query($koneksi, $query_sesi);

// Jika melihat detail jawaban seorang mahasiswa
$detail_data = null;
$detail_info = null;
if (isset($_GET['lihat']) && is_numeric($_GET['lihat'])) {
    $id_ujian_lihat = (int)$_GET['lihat'];
    
    // Info sesi
    $q_info = mysqli_query($koneksi, "SELECT s.*, m.nama_mahasiswa FROM sesi_ujian s INNER JOIN mahasiswa m ON s.nim = m.nim WHERE s.id_ujian = $id_ujian_lihat LIMIT 1");
    $detail_info = mysqli_fetch_assoc($q_info);
    
    // Detail jawaban
    $q_detail = mysqli_query($koneksi, "SELECT dj.id_detail, dj.id_soal, dj.jawaban_peserta, bs.pertanyaan, bs.opsi_a, bs.opsi_b, bs.opsi_c, bs.opsi_d, bs.kunci_jawaban, bs.jenis_soal, bs.bobot
                                        FROM detail_jawaban dj
                                        INNER JOIN bank_soal bs ON dj.id_soal = bs.id_soal
                                        WHERE dj.id_ujian = $id_ujian_lihat
                                        ORDER BY dj.id_soal ASC");
    $detail_data = [];
    while ($row = mysqli_fetch_assoc($q_detail)) {
        $detail_data[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian Ujian - Admin Lab</title>
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

        .tab-panel { display: none; }
        .tab-panel.active { display: block; }
        
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


        <!-- SIDEBAR -->
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
        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
        <li class="nav-item">
            <a class="nav-link-custom" href="dashboard_admin_lab.php?tab=profil"><i class="bi bi-person-fill me-2"></i>Profil</a>
        </li>
        <li class="nav-item">
            <a class="nav-link-custom <?= ($current_page == 'dashboard_admin_lab.php') ? 'active' : '' ?>" href="dashboard_admin_lab.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
        </li>
        <li class="nav-item">
            <a class="nav-link-custom <?= ($current_page == 'kelola_soal.php') ? 'active' : '' ?>" href="kelola_soal.php"><i class="bi bi-journal-text me-2"></i>Kelola Soal</a>
        </li>
        <li class="nav-item">
            <a class="nav-link-custom <?= ($current_page == 'penilaian_ujian.php' || $current_page == 'penilaian_interview.php') ? 'active' : '' ?>" href="penilaian_ujian.php"><i class="bi bi-pencil-square me-2"></i>Penilaian</a>
        </li>
        <li class="nav-item">
            <a class="nav-link-custom <?= ($current_page == 'jadwal_interview.php') ? 'active' : '' ?>" href="jadwal_interview.php"><i class="bi bi-camera-video me-2"></i>Interview</a>
        </li>
        <li class="nav-item">
            <a class="nav-link-custom <?= ($current_page == 'keputusan_seleksi.php') ? 'active' : '' ?>" href="keputusan_seleksi.php"><i class="bi bi-trophy me-2"></i>Hasil Seleksi</a>
        </li>
        <li class="nav-item mt-3">
            <a class="nav-link-custom text-warning fw-bold" href="logout.php?role=Admin%20Lab" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                <i class="bi bi-box-arrow-left me-2"></i>Logout
            </a>
        </li>
    </ul>
</nav>

        <!-- MAIN CONTENT -->
        <main class="main-content px-4">
            <div class="d-flex justify-content-between align-items-center pt-4 pb-2 mb-3 border-bottom">
                <h2 class="h3 fw-bold text-slate-800">
                    <?php if ($detail_info): ?>
                        <a href="penilaian_ujian.php" class="text-decoration-none text-muted"><i class="bi bi-arrow-left me-2"></i></a>
                        Detail Jawaban: <?= htmlspecialchars($detail_info['nama_mahasiswa']) ?>
                    <?php else: ?>
                        Penilaian Ujian Mahasiswa
                    <?php endif; ?>
                </h2>
                <span class="badge bg-light p-2 fs-6">Admin: <?= htmlspecialchars($username_admin); ?></span>
            </div>

            <?php if ($detail_info && $detail_data): ?>
                <!-- DETAIL JAWABAN VIEW -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="fw-bold text-muted">NIM</h6>
                                <p class="fs-5 fw-bold"><?= htmlspecialchars($detail_info['nim']) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="fw-bold text-muted">Tanggal Ujian</h6>
                                <p class="fs-5 fw-bold"><?= htmlspecialchars($detail_info['tanggal_ujian']) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="fw-bold text-muted">Skor Saat Ini</h6>
                                <form method="POST" action="" class="d-flex gap-2 align-items-center">
    <?= csrf_field(); ?>
                                    <input type="hidden" name="id_ujian" value="<?= $detail_info['id_ujian'] ?>">
                                    <input type="number" name="skor_ujian" class="form-control sas-input form-control sas-input-lg fw-bold" value="<?= $detail_info['skor_ujian'] ?>" min="0" max="100" style="width:100px">
                                    <button type="submit" name="aksi_skor" class="btn btn-sas btn-sas-lab btn-sm"><i class="bi bi-check-lg"></i> Simpan</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php 
                $benar_pg = 0; $total_pg = 0; $total_essay = 0;
                foreach ($detail_data as $d) { 
                    if (($d['jenis_soal'] ?? 'PG') === 'PG') {
                        $total_pg++;
                        if ($d['jawaban_peserta'] === $d['kunci_jawaban']) $benar_pg++;
                    } else {
                        $total_essay++;
                    }
                }
                ?>
                <div class="alert alert-info sas-badge-info mb-3">
                    <strong>Rekap PG:</strong> <?= $benar_pg ?> benar dari <?= $total_pg ?> soal PG
                    <?php if ($total_essay > 0): ?>
                        &nbsp;|&nbsp; <strong>Essay:</strong> <?= $total_essay ?> soal <span class="text-warning">(perlu dinilai manual)</span>
                    <?php endif; ?>
                </div>

                <?php foreach ($detail_data as $idx => $d): 
                    $jenis = $d['jenis_soal'] ?? 'PG';
                    $is_benar = ($jenis === 'PG' && $d['jawaban_peserta'] === $d['kunci_jawaban']);
                ?>
                    <?php if ($jenis === 'Essay'): ?>
                        <!-- ESSAY QUESTION -->
                        <div class="card border-0 shadow-sm mb-3" style="border-left: 4px solid #0dcaf0 !important;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="badge bg-secondary">Soal <?= $idx + 1 ?></span>
                                        <span class="badge bg-info">Essay</span>
                                        <span class="badge bg-light">Bobot: <?= $d['bobot'] ?></span>
                                    </div>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-pencil"></i> Perlu Dinilai Manual</span>
                                </div>
                                <p class="fw-semibold mb-3"><?= nl2br(htmlspecialchars($d['pertanyaan'])) ?></p>
                                <div class="bg-light border rounded p-3">
                                    <label class="form-label fw-bold text-muted mb-1"><i class="bi bi-chat-left-text me-1"></i>Jawaban Peserta:</label>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($d['jawaban_peserta'] ?? '-')) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- PG QUESTION -->
                        <div class="card border-0 shadow-sm mb-3 <?= $is_benar ? 'benar' : 'salah' ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="badge bg-secondary">Soal <?= $idx + 1 ?></span>
                                        <span class="badge bg-light">Bobot: <?= $d['bobot'] ?></span>
                                    </div>
                                    <?php if ($is_benar): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Benar</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Salah</span>
                                    <?php endif; ?>
                                </div>
                                <p class="fw-semibold mb-3"><?= nl2br(htmlspecialchars($d['pertanyaan'])) ?></p>
                                <div class="row">
                                    <?php 
                                    $opsi = ['A' => $d['opsi_a'], 'B' => $d['opsi_b'], 'C' => $d['opsi_c'], 'D' => $d['opsi_d']];
                                    foreach ($opsi as $huruf => $teks): 
                                        $cls = '';
                                        if ($huruf === $d['kunci_jawaban']) $cls = 'border-success text-success fw-bold';
                                        if ($huruf === $d['jawaban_peserta'] && !$is_benar) $cls = 'border-danger text-primary';
                                        if ($huruf === $d['jawaban_peserta'] && $is_benar) $cls = 'border-success text-success fw-bold';
                                    ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="border rounded p-2 <?= $cls ?>">
                                                <?php if ($huruf === $d['jawaban_peserta']): ?><i class="bi bi-arrow-right-circle-fill me-1"></i><?php endif; ?>
                                                <?php if ($huruf === $d['kunci_jawaban']): ?><i class="bi bi-check-circle-fill me-1"></i><?php endif; ?>
                                                <strong><?= $huruf ?>.</strong> <?= htmlspecialchars($teks) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

            <?php else: ?>
                <!-- DAFTAR SESI UJIAN -->
                <div class="card border-0 shadow-sm mb-5">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>NIM</th>
                                        <th>Nama Peserta</th>
                                        <th>Tanggal Ujian</th>
                                        <th>Skor PG Otomatis</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($hasil_sesi) > 0): ?>
                                        <?php while($row = mysqli_fetch_assoc($hasil_sesi)): ?>
                                            <tr>
                                                <td class="fw-bold"><?= htmlspecialchars($row['nim']); ?></td>
                                                <td><?= htmlspecialchars($row['nama_mahasiswa']); ?></td>
                                                <td><?= htmlspecialchars($row['tanggal_ujian']); ?></td>
                                                <td><span class="badge bg-primary fs-6"><?= htmlspecialchars($row['skor_ujian']); ?></span></td>
                                                <td class="text-center">
                                                    <a href="penilaian_ujian.php?lihat=<?= $row['id_ujian'] ?>" class="btn btn-info btn-sm text-dark">
                                                        <i class="bi bi-eye"></i> Lihat Jawaban & Nilai
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Belum ada mahasiswa yang mengikuti ujian.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
