<?php
// FASE 3: DASHBOARD ADMIN - KELOLA SOAL (PROSES 3.0)
session_start();
require_once __DIR__ . '/koneksiweb.php';
require_once __DIR__ . '/csrf_helper.php';
verify_csrf_token();

// 1. PROTEKSI HALAMAN: Validasi hak akses Admin secara ketat
if (empty($_SESSION['Admin Lab']['id_user'])) {
    header("Location: login.php");
    exit();
}

$id_user_session = (int)$_SESSION['Admin Lab']['id_user'];
$username_admin  = $_SESSION['Admin Lab']['username'];

// Proses crud soal
if (isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];
    
    if ($aksi === 'tambah') {
        $jenis_soal = mysqli_real_escape_string($koneksi, $_POST['jenis_soal']);
        $bobot = (int)$_POST['bobot'];
        $pertanyaan = mysqli_real_escape_string($koneksi, $_POST['pertanyaan']);
        
        $opsi_a = $jenis_soal === 'PG' ? "'" . mysqli_real_escape_string($koneksi, $_POST['opsi_a']) . "'" : "NULL";
        $opsi_b = $jenis_soal === 'PG' ? "'" . mysqli_real_escape_string($koneksi, $_POST['opsi_b']) . "'" : "NULL";
        $opsi_c = $jenis_soal === 'PG' ? "'" . mysqli_real_escape_string($koneksi, $_POST['opsi_c']) . "'" : "NULL";
        $opsi_d = $jenis_soal === 'PG' ? "'" . mysqli_real_escape_string($koneksi, $_POST['opsi_d']) . "'" : "NULL";
        $kunci_jawaban = $jenis_soal === 'PG' ? "'" . mysqli_real_escape_string($koneksi, $_POST['kunci_jawaban']) . "'" : "NULL";
        
        $query = "INSERT INTO bank_soal (jenis_soal, bobot, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, kunci_jawaban) 
                  VALUES ('$jenis_soal', $bobot, '$pertanyaan', $opsi_a, $opsi_b, $opsi_c, $opsi_d, $kunci_jawaban)";
        if (mysqli_query($koneksi, $query)) {
            echo "<script>alert('Soal berhasil ditambahkan!'); window.location.href = 'kelola_soal.php';</script>";
        } else {
            echo "<script>alert('Gagal menambah soal. Silakan coba lagi.');</script>";
        }
    } elseif ($aksi === 'edit') {
        $id_soal = (int)$_POST['id_soal'];
        $jenis_soal = mysqli_real_escape_string($koneksi, $_POST['jenis_soal']);
        $bobot = (int)$_POST['bobot'];
        $pertanyaan = mysqli_real_escape_string($koneksi, $_POST['pertanyaan']);
        
        $opsi_a = $jenis_soal === 'PG' ? "'" . mysqli_real_escape_string($koneksi, $_POST['opsi_a']) . "'" : "NULL";
        $opsi_b = $jenis_soal === 'PG' ? "'" . mysqli_real_escape_string($koneksi, $_POST['opsi_b']) . "'" : "NULL";
        $opsi_c = $jenis_soal === 'PG' ? "'" . mysqli_real_escape_string($koneksi, $_POST['opsi_c']) . "'" : "NULL";
        $opsi_d = $jenis_soal === 'PG' ? "'" . mysqli_real_escape_string($koneksi, $_POST['opsi_d']) . "'" : "NULL";
        $kunci_jawaban = $jenis_soal === 'PG' ? "'" . mysqli_real_escape_string($koneksi, $_POST['kunci_jawaban']) . "'" : "NULL";
        
        $query = "UPDATE bank_soal SET 
                  jenis_soal='$jenis_soal', bobot=$bobot, pertanyaan='$pertanyaan', 
                  opsi_a=$opsi_a, opsi_b=$opsi_b, opsi_c=$opsi_c, opsi_d=$opsi_d, kunci_jawaban=$kunci_jawaban 
                  WHERE id_soal=$id_soal";
        if (mysqli_query($koneksi, $query)) {
            echo "<script>alert('Soal berhasil diubah!'); window.location.href = 'kelola_soal.php';</script>";
        } else {
            echo "<script>alert('Gagal mengubah soal. Silakan coba lagi.');</script>";
        }
    } elseif ($aksi === 'hapus') {
        $id_soal = (int)$_POST['id_soal'];
        if (mysqli_query($koneksi, "DELETE FROM bank_soal WHERE id_soal = $id_soal")) {
            echo "<script>alert('Soal berhasil dihapus!'); window.location.href = 'kelola_soal.php';</script>";
        } else {
            echo "<script>alert('Gagal menghapus soal. Silakan coba lagi.');</script>";
        }
    }
}

// Ambil data soal
$query_tampil = "SELECT * FROM bank_soal ORDER BY id_soal DESC";
$hasil_tampil = mysqli_query($koneksi, $query_tampil);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Soal - Admin Lab</title>
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
                <h2 class="h3 fw-bold text-slate-800">Kelola Bank Soal</h2>
                <span class="badge bg-light p-2 fs-6">Admin: <?= htmlspecialchars($username_admin); ?></span>
            </div>
            
            <div class="mb-3">
                <button class="btn btn-sas btn-sas-lab" data-bs-toggle="modal" data-bs-target="#modalTambahSoal">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Soal Baru
                </button>
            </div>

            <div class="card border-0 shadow-sm mb-5">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="10%">Jenis</th>
                                    <th width="40%">Pertanyaan</th>
                                    <th width="10%">Bobot</th>
                                    <th width="15%">Kunci (PG)</th>
                                    <th width="20%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($hasil_tampil) > 0): ?>
                                    <?php $no = 1; while($row = mysqli_fetch_assoc($hasil_tampil)): ?>
                                        <tr>
                                            <td class="fw-bold"><?= $no++; ?></td>
                                            <td><span class="badge <?= $row['jenis_soal'] === 'Essay' ? 'bg-info' : 'bg-secondary' ?>"><?= $row['jenis_soal']; ?></span></td>
                                            <td><?= nl2br(htmlspecialchars($row['pertanyaan'])); ?></td>
                                            <td><?= $row['bobot']; ?></td>
                                            <td class="text-center">
                                                <?php if($row['jenis_soal'] === 'PG'): ?>
                                                    <span class="badge bg-success fs-6"><?= htmlspecialchars($row['kunci_jawaban']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sas btn-sas-lab btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditSoal<?= $row['id_soal']; ?>">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </button>
                                                <form method="POST" action="" class="d-inline-flex gap-1 mb-0" onsubmit="return confirm('Apakah Anda yakin ingin menghapus soal ini?');">
    <?= csrf_field(); ?>
                                                    <input type="hidden" name="id_soal" value="<?= $row['id_soal']; ?>">
                                                    <button type="submit" name="aksi" value="hapus" class="btn btn-danger btn-sm">
                                                        <i class="bi bi-trash"></i> Hapus
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        
                                        <!-- Modal Edit Soal -->
                                        <div class="modal fade" id="modalEditSoal<?= $row['id_soal']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <form method="POST" action="">
    <?= csrf_field(); ?>
                                                        <div class="modal-header bg-primary text-dark">
                                                            <h5 class="modal-title">Edit Soal</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body text-start">
                                                            <input type="hidden" name="id_soal" value="<?= $row['id_soal']; ?>">
                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <label class="form-label fw-bold">Jenis Soal</label>
                                                                    <div>
                                                                        <div class="form-check form-check-inline">
                                                                            <input class="form-check-input" type="radio" name="jenis_soal" value="PG" id="edit_pg_<?= $row['id_soal'] ?>" <?= $row['jenis_soal'] === 'PG' ? 'checked' : '' ?> onchange="toggleEditOpsi(<?= $row['id_soal'] ?>)">
                                                                            <label class="form-check-label" for="edit_pg_<?= $row['id_soal'] ?>">Pilihan Ganda</label>
                                                                        </div>
                                                                        <div class="form-check form-check-inline">
                                                                            <input class="form-check-input" type="radio" name="jenis_soal" value="Essay" id="edit_essay_<?= $row['id_soal'] ?>" <?= $row['jenis_soal'] === 'Essay' ? 'checked' : '' ?> onchange="toggleEditOpsi(<?= $row['id_soal'] ?>)">
                                                                            <label class="form-check-label" for="edit_essay_<?= $row['id_soal'] ?>">Essay</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label fw-bold">Bobot Nilai</label>
                                                                    <input type="number" name="bobot" class="form-control sas-input" value="<?= $row['bobot'] ?>" required min="1">
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Pertanyaan</label>
                                                                <textarea name="pertanyaan" class="form-control sas-input" rows="3" required><?= htmlspecialchars($row['pertanyaan']); ?></textarea>
                                                            </div>
                                                            <div id="edit_opsi_container_<?= $row['id_soal'] ?>" style="<?= $row['jenis_soal'] === 'Essay' ? 'display: none;' : '' ?>">
                                                            <div class="row">
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Opsi A</label>
                                                                    <input type="text" name="opsi_a" class="form-control sas-input" value="<?= htmlspecialchars($row['opsi_a']); ?>" required>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Opsi B</label>
                                                                    <input type="text" name="opsi_b" class="form-control sas-input" value="<?= htmlspecialchars($row['opsi_b']); ?>" required>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Opsi C</label>
                                                                    <input type="text" name="opsi_c" class="form-control sas-input" value="<?= htmlspecialchars($row['opsi_c']); ?>" required>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Opsi D</label>
                                                                    <input type="text" name="opsi_d" class="form-control sas-input" value="<?= htmlspecialchars($row['opsi_d']); ?>" required>
                                                                </div>
                                                            </div>
                                                            </div>
                                                            <div class="mb-3" id="edit_kunci_container_<?= $row['id_soal'] ?>" style="<?= $row['jenis_soal'] === 'Essay' ? 'display: none;' : '' ?>">
                                                                <label class="form-label fw-bold">Kunci Jawaban</label>
                                                                <select name="kunci_jawaban" class="form-select">
                                                                    <option value="A" <?= $row['kunci_jawaban'] == 'A' ? 'selected' : ''; ?>>A</option>
                                                                    <option value="B" <?= $row['kunci_jawaban'] == 'B' ? 'selected' : ''; ?>>B</option>
                                                                    <option value="C" <?= $row['kunci_jawaban'] == 'C' ? 'selected' : ''; ?>>C</option>
                                                                    <option value="D" <?= $row['kunci_jawaban'] == 'D' ? 'selected' : ''; ?>>D</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" name="aksi" value="edit" class="btn btn-sas btn-sas-lab">Simpan Perubahan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Belum ada soal. Silakan tambahkan soal baru.</td>
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

<!-- Modal Tambah Soal -->
<div class="modal fade" id="modalTambahSoal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
    <?= csrf_field(); ?>
                <div class="modal-header bg-success text-dark">
                    <h5 class="modal-title">Tambah Soal Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Jenis Soal</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="jenis_soal" value="PG" id="tambah_pg" checked onchange="toggleTambahOpsi()">
                                    <label class="form-check-label" for="tambah_pg">Pilihan Ganda</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="jenis_soal" value="Essay" id="tambah_essay" onchange="toggleTambahOpsi()">
                                    <label class="form-check-label" for="tambah_essay">Essay</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Bobot Nilai</label>
                            <input type="number" name="bobot" class="form-control sas-input" value="1" required min="1">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pertanyaan</label>
                        <textarea name="pertanyaan" class="form-control sas-input" rows="3" required></textarea>
                    </div>
                    <div id="tambah_opsi_container">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Opsi A</label>
                                <input type="text" name="opsi_a" class="form-control sas-input">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Opsi B</label>
                                <input type="text" name="opsi_b" class="form-control sas-input">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Opsi C</label>
                                <input type="text" name="opsi_c" class="form-control sas-input">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Opsi D</label>
                                <input type="text" name="opsi_d" class="form-control sas-input">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kunci Jawaban</label>
                            <select name="kunci_jawaban" class="form-select">
                                <option value="">-- Pilih Kunci Jawaban --</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="aksi" value="tambah" class="btn btn-sas btn-sas-lab">Simpan Soal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
function toggleTambahOpsi() {
    const isEssay = document.getElementById('tambah_essay').checked;
    const container = document.getElementById('tambah_opsi_container');
    if (isEssay) {
        container.style.display = 'none';
    } else {
        container.style.display = 'block';
    }
}
function toggleEditOpsi(id) {
    const isEssay = document.getElementById('edit_essay_' + id).checked;
    const container = document.getElementById('edit_opsi_container_' + id);
    const kunci = document.getElementById('edit_kunci_container_' + id);
    if (isEssay) {
        container.style.display = 'none';
        kunci.style.display = 'none';
    } else {
        container.style.display = 'block';
        kunci.style.display = 'block';
    }
}
</script>
</body>
</html>
