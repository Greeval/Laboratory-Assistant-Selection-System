<?php
// HALAMAN KELOLA INTERVIEW - ADMIN LAB
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

$q_admin_lab = mysqli_query($koneksi, "SELECT id_admin_lab, nama_admin FROM admin_lab WHERE id_user = $id_user_session LIMIT 1");
$d_admin_lab = mysqli_fetch_assoc($q_admin_lab);
$id_admin_lab_real = $d_admin_lab ? (int)$d_admin_lab['id_admin_lab'] : 0;
$nama_admin_lab = $d_admin_lab['nama_admin'] ?? $username_admin;

// PROSES SIMPAN/UPDATE JADWAL INTERVIEW
if (isset($_POST['simpan_jadwal'])) {
    $nim_input    = mysqli_real_escape_string($koneksi, $_POST['nim']);
    $jadwal_input = mysqli_real_escape_string($koneksi, $_POST['jadwal']);
    $link_input   = mysqli_real_escape_string($koneksi, $_POST['link_meet']);

    // Cek apakah data wawancara sudah ada
    $cek = mysqli_query($koneksi, "SELECT nim FROM wawancara WHERE nim = '$nim_input'");
    if (mysqli_num_rows($cek) > 0) {
        $q = "UPDATE wawancara SET jadwal = '$jadwal_input', link_meet = '$link_input', id_admin_lab = $id_admin_lab_real WHERE nim = '$nim_input'";
    } else {
        $q = "INSERT INTO wawancara (nim, jadwal, link_meet, id_admin_lab) VALUES ('$nim_input', '$jadwal_input', '$link_input', $id_admin_lab_real)";
    }

    if (mysqli_query($koneksi, $q)) {
        // Notifikasi Jadwal
        $judul = "Jadwal Interview Telah Ditetapkan";
        $pesan = "Jadwal interview Anda telah ditetapkan.\n\nTanggal & Waktu: " . date('d M Y, H:i', strtotime($jadwal_input)) . " WIB\nLokasi / Link: " . $link_input . "\n\nHarap hadir tepat waktu.";
        kirim_notifikasi_ganda($nim_input, $judul, $pesan, $d_admin_lab['email'] ?? '', $nama_admin_lab);

        echo "<script>alert('Jadwal interview berhasil disimpan! Notifikasi terkirim.'); window.location.href = 'jadwal_interview.php';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan. Silakan coba lagi.');</script>";
    }
}

// Proses update nilai interview
if (isset($_POST['simpan_nilai_interview'])) {
    $nim_input   = mysqli_real_escape_string($koneksi, $_POST['nim']);
    $nilai_input = (float)$_POST['nilai_interview'];
    $status_input = mysqli_real_escape_string($koneksi, $_POST['status_interview']);

    $q = "UPDATE wawancara SET nilai = $nilai_input, status = '$status_input' WHERE nim = '$nim_input'";
    if (mysqli_query($koneksi, $q)) {
        // Notifikasi Penilaian
        $judul = "Status Interview Anda: " . $status_input;
        $pesan = "Interview Anda telah dinilai.\n\nStatus saat ini: " . $status_input . ".\n\nSilakan tunggu pengumuman hasil akhir seleksi Asisten Laboratorium pada tab Pengumuman.";
        kirim_notifikasi_ganda($nim_input, $judul, $pesan, $d_admin_lab['email'] ?? '', $nama_admin_lab);

        echo "<script>alert('Nilai interview berhasil disimpan!'); window.location.href = 'jadwal_interview.php';</script>";
    } else {
        echo "<script>alert('Gagal. Silakan coba lagi.');</script>";
    }
}
// AMBIL DATA PESERTA YANG SUDAH UJIAN (layak interview)
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, trim($_GET['search'])) : '';
$where = $search_query ? "AND (m.nama_mahasiswa LIKE '%$search_query%' OR m.nim LIKE '%$search_query%')" : '';

$query_tampil = "SELECT m.nim, m.nama_mahasiswa, m.jurusan, m.email, m.no_whatsapp,
                        s.skor_ujian,
                        w.jadwal, w.link_meet, w.nilai, w.status as status_interview
                 FROM mahasiswa m
                 INNER JOIN sesi_ujian s ON m.nim = s.nim
                 LEFT JOIN wawancara w ON m.nim = w.nim
                 WHERE 1=1 $where
                 ORDER BY m.nim ASC";
$hasil_tampil = mysqli_query($koneksi, $query_tampil);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Interview - Admin Lab</title>
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
                <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-outline-secondary me-3 d-none d-md-block" id="sidebarToggle" onclick="document.getElementById('sidebarMenu').classList.toggle('closed'); document.querySelector('.main-content').classList.toggle('expanded');">
                            <i class="bi bi-list"></i>
                        </button>
                        <h2 class="h3 fw-bold mb-0">Kelola Jadwal Interview</h2>
                    </div>
                <span class="badge bg-light p-2 fs-6">Admin: <?= htmlspecialchars($nama_admin_lab); ?></span>
            </div>

            <!-- Search -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <input type="text" name="search" class="form-control sas-input" placeholder="Cari Nama / NIM..." value="<?= htmlspecialchars($search_query) ?>">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-sas btn-sas-lab w-100"><i class="bi bi-search"></i> Cari</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabel Interview -->
            <div class="card border-0 shadow-sm mb-5">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>NIM</th>
                                    <th>Nama</th>
                                    <th>Program Studi</th>
                                    <th>Skor Ujian</th>
                                    <th>Jadwal Interview</th>
                                    <th>Link Meet</th>
                                    <th>Status</th>
                                    <th>Nilai</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($hasil_tampil && mysqli_num_rows($hasil_tampil) > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($hasil_tampil)): ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($row['nim']); ?></td>
                                            <td><?= htmlspecialchars($row['nama_mahasiswa']); ?></td>
                                            <td><?= htmlspecialchars($row['jurusan'] ?? '-'); ?></td>
                                            <td><span class="badge bg-primary"><?= htmlspecialchars($row['skor_ujian']); ?></span></td>
                                            <td>
                                                <?php if ($row['jadwal']): ?>
                                                    <span class="text-success fw-bold"><?= date('d M Y H:i', strtotime($row['jadwal'])); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Belum dijadwalkan</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['link_meet']): ?>
                                                    <?php if (filter_var($row['link_meet'], FILTER_VALIDATE_URL)): ?>
                                                        <a href="<?= htmlspecialchars($row['link_meet']); ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                            <i class="bi bi-camera-video"></i> Buka
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-dark fw-semibold"><?= htmlspecialchars($row['link_meet']); ?></span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                    $st = $row['status_interview'] ?? 'Menunggu';
                                                    if ($st === 'Lulus') $sc = 'bg-success';
                                                    elseif ($st === 'Tidak Lulus') $sc = 'bg-danger';
                                                    else $sc = 'bg-secondary';
                                                ?>
                                                <span class="badge <?= $sc; ?>"><?= htmlspecialchars($st); ?></span>
                                            </td>
                                            <td><?= $row['nilai'] !== null ? htmlspecialchars($row['nilai']) : '-'; ?></td>
                                            <td class="text-center">
                                                <div class="btn-group-vertical btn-group-sm">
                                                    <button class="btn btn-info btn-sm mb-1" data-bs-toggle="modal" data-bs-target="#modalJadwal<?= $row['nim']; ?>">
                                                        <i class="bi bi-calendar-event"></i> Jadwal
                                                    </button>
                                                    <?php if ($row['jadwal']): ?>
                                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalNilai<?= $row['nim']; ?>">
                                                        <i class="bi bi-clipboard-check"></i> Nilai
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Modal Set Jadwal -->
                                        <div class="modal fade" id="modalJadwal<?= $row['nim']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST">
    <?= csrf_field(); ?>
                                                        <div class="modal-header bg-info text-dark">
                                                            <h5 class="modal-title fw-bold">Atur Jadwal Interview</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="nim" value="<?= $row['nim']; ?>">
                                                            <p class="mb-3"><strong><?= htmlspecialchars($row['nama_mahasiswa']); ?></strong> (<?= htmlspecialchars($row['nim']); ?>)</p>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Tanggal & Waktu Interview</label>
                                                                <input type="datetime-local" name="jadwal" class="form-control sas-input" value="<?= $row['jadwal'] ? date('Y-m-d\TH:i', strtotime($row['jadwal'])) : '' ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Lokasi / Link Meeting</label>
                                                                <input type="text" name="link_meet" class="form-control sas-input" placeholder="Cth: Ruang Lab A atau https://meet..." value="<?= htmlspecialchars($row['link_meet'] ?? ''); ?>">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" name="simpan_jadwal" class="btn btn-info text-dark fw-bold">Simpan Jadwal</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal Input Nilai Interview -->
                                        <div class="modal fade" id="modalNilai<?= $row['nim']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST">
    <?= csrf_field(); ?>
                                                        <div class="modal-header bg-warning text-dark">
                                                            <h5 class="modal-title fw-bold">Nilai Interview</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="nim" value="<?= $row['nim']; ?>">
                                                            <p class="mb-3"><strong><?= htmlspecialchars($row['nama_mahasiswa']); ?></strong></p>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Nilai Interview (0-100)</label>
                                                                <input type="number" name="nilai_interview" class="form-control sas-input" min="0" max="100" step="0.01" value="<?= $row['nilai'] ?? ''; ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Status</label>
                                                                <select name="status_interview" class="form-select" required>
                                                                    <option value="Menunggu" <?= ($row['status_interview'] ?? 'Menunggu') === 'Menunggu' ? 'selected' : '' ?>>Menunggu</option>
                                                                    <option value="Lulus" <?= ($row['status_interview'] ?? '') === 'Lulus' ? 'selected' : '' ?>>Lulus</option>
                                                                    <option value="Tidak Lulus" <?= ($row['status_interview'] ?? '') === 'Tidak Lulus' ? 'selected' : '' ?>>Tidak Lulus</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" name="simpan_nilai_interview" class="btn btn-warning fw-bold">Simpan Nilai</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">Belum ada mahasiswa yang menyelesaikan ujian.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
