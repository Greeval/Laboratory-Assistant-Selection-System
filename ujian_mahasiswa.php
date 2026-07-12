<?php
// FASE 3: UJIAN MAHASISWA (PROSES 5.0)
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

// Ambil nim mahasiswa & cek status berkas
$query_mhs = "SELECT m.nim, p.status_validasi 
              FROM mahasiswa m 
              LEFT JOIN pemberkasan p ON m.nim = p.nim 
              WHERE m.id_user = $id_user_session LIMIT 1";
$hasil_mhs = mysqli_query($koneksi, $query_mhs);
$data_mhs  = mysqli_fetch_assoc($hasil_mhs);

$nim = $data_mhs['nim'] ?? '';
$status_validasi = $data_mhs['status_validasi'] ?? 'Pending';

// Jika berkas belum valid, tolak akses ke ujian
if ($status_validasi !== 'Valid') {
    echo "<script>alert('Anda belum bisa mengikuti ujian karena berkas Anda belum divalidasi oleh Admin Lab.'); window.location.href = 'dashboard_mahasiswa.php';</script>";
    exit();
}

// Cek apakah sudah ujian
$query_cek_ujian = "SELECT id_ujian FROM sesi_ujian WHERE nim = '$nim' LIMIT 1";
$hasil_cek_ujian = mysqli_query($koneksi, $query_cek_ujian);
if (mysqli_num_rows($hasil_cek_ujian) > 0) {
    echo "<script>alert('Anda sudah menyelesaikan ujian. Hasil dapat dilihat di halaman Hasil Seleksi.'); window.location.href = 'dashboard_mahasiswa.php';</script>";
    exit();
}

// Proses penyimpanan ujian
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_ujian'])) {
    // Ambil kunci jawaban, jenis soal dan bobot
    $query_kunci = "SELECT id_soal, kunci_jawaban, jenis_soal, bobot FROM bank_soal";
    $hasil_kunci = mysqli_query($koneksi, $query_kunci);
    
    $jawaban_peserta = []; // [id_soal => jawaban]
    $max_skor = 0;
    $skor_peserta = 0;
    
    while ($row = mysqli_fetch_assoc($hasil_kunci)) {
        $id_soal = $row['id_soal'];
        $kunci = $row['kunci_jawaban'];
        $jenis = $row['jenis_soal'];
        $bobot = (int)$row['bobot'];
        
        $max_skor += $bobot;
        
        $name_input = 'soal_' . $id_soal;
        if (isset($_POST[$name_input])) {
            $jawaban = trim($_POST[$name_input]);
            $jawaban_peserta[$id_soal] = $jawaban;
            
            if ($jenis === 'PG' && $jawaban === $kunci) {
                $skor_peserta += $bobot;
            }
        }
    }
    
    // Hitung skor (skala 100) - Nilai Essay akan dihitung belakangan oleh Admin
    $skor_ujian = $max_skor > 0 ? round(($skor_peserta / $max_skor) * 100) : 0;
    $waktu_selesai = date('Y-m-d H:i:s');
    
    // Simpan ke sesi_ujian
    $query_sesi = "INSERT INTO sesi_ujian (nim, tanggal_ujian, skor_ujian) VALUES ('$nim', '$waktu_selesai', $skor_ujian)";
    if (mysqli_query($koneksi, $query_sesi)) {
        $id_ujian_baru = mysqli_insert_id($koneksi);
        
        // Simpan detail_jawaban
        foreach ($jawaban_peserta as $id_soal => $jawaban) {
            $jawaban_aman = mysqli_real_escape_string($koneksi, $jawaban);
            mysqli_query($koneksi, "INSERT INTO detail_jawaban (id_ujian, id_soal, jawaban_peserta) VALUES ($id_ujian_baru, $id_soal, '$jawaban_aman')");
        }
        
        echo "<script>alert('Ujian berhasil diselesaikan! Skor Anda telah disimpan.'); window.location.href = 'dashboard_mahasiswa.php';</script>";
        exit();
    } else {
        echo "<script>alert('Gagal menyimpan sesi ujian. Silakan coba lagi.');</script>";
    }
}

// Ambil data bank soal untuk ditampilkan
$query_soal = "SELECT * FROM bank_soal ORDER BY id_soal ASC";
$hasil_soal = mysqli_query($koneksi, $query_soal);
$daftar_soal = [];
while ($row = mysqli_fetch_assoc($hasil_soal)) {
    $daftar_soal[] = $row;
}
$total_pertanyaan = count($daftar_soal);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian Online - Rekrutmen Aslab</title>
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

<div class="exam-header d-flex justify-content-between align-items-center px-4">
    <h5 class="mb-0">Ujian Seleksi Asisten Laboratorium</h5>
    <div class="bg-danger px-3 py-2 rounded fw-bold" id="timer">Sisa Waktu: 60:00</div>
</div>

<div class="container my-4">
    <form id="examForm" method="POST" action="">
    <?= csrf_field(); ?>
        <div class="row g-4">
            <!-- Soal Section -->
            <div class="col-lg-8">
                <?php if ($total_pertanyaan > 0): ?>
                    <?php foreach ($daftar_soal as $index => $soal): ?>
                        <div class="question-box <?= $index === 0 ? 'active' : '' ?>" id="soal-box-<?= $index ?>">
                            <span class="badge bg-secondary mb-3">Soal Nomor <?= $index + 1 ?></span>
                            <p class="fs-5 fw-semibold mb-4"><?= nl2br(htmlspecialchars($soal['pertanyaan'])) ?></p>
                            
                            <?php if(isset($soal['jenis_soal']) && $soal['jenis_soal'] === 'Essay'): ?>
                                <div class="form-group">
                                    <textarea name="soal_<?= $soal['id_soal'] ?>" class="form-control sas-input" rows="8" placeholder="Ketik jawaban essay Anda di sini..." oninput="tandaiTerjawab(<?= $index ?>)"></textarea>
                                </div>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-3">
                                    <label class="btn btn-outline-secondary text-start p-3">
                                        <input type="radio" name="soal_<?= $soal['id_soal'] ?>" value="A" class="me-2" onclick="tandaiTerjawab(<?= $index ?>)"> A. <?= htmlspecialchars($soal['opsi_a']) ?>
                                    </label>
                                    <label class="btn btn-outline-secondary text-start p-3">
                                        <input type="radio" name="soal_<?= $soal['id_soal'] ?>" value="B" class="me-2" onclick="tandaiTerjawab(<?= $index ?>)"> B. <?= htmlspecialchars($soal['opsi_b']) ?>
                                    </label>
                                    <label class="btn btn-outline-secondary text-start p-3">
                                        <input type="radio" name="soal_<?= $soal['id_soal'] ?>" value="C" class="me-2" onclick="tandaiTerjawab(<?= $index ?>)"> C. <?= htmlspecialchars($soal['opsi_c']) ?>
                                    </label>
                                    <label class="btn btn-outline-secondary text-start p-3">
                                        <input type="radio" name="soal_<?= $soal['id_soal'] ?>" value="D" class="me-2" onclick="tandaiTerjawab(<?= $index ?>)"> D. <?= htmlspecialchars($soal['opsi_d']) ?>
                                    </label>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between mt-5">
                                <button type="button" class="btn btn-secondary px-4 <?= $index === 0 ? 'disabled' : '' ?>" onclick="navigasi(<?= $index - 1 ?>)">Sebelumnya</button>
                                <?php if ($index === $total_pertanyaan - 1): ?>
                                    <button type="submit" name="submit_ujian" class="btn btn-danger px-4" onclick="return confirm('Apakah Anda yakin ingin menyelesaikan ujian sekarang?');">Selesaikan Ujian</button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sas btn-sas-lab px-4" onclick="navigasi(<?= $index + 1 ?>)">Selanjutnya</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="question-box active">
                        <div class="alert alert-warning sas-badge-warning">Belum ada soal ujian yang tersedia.</div>
                        <a href="dashboard_mahasiswa.php" class="btn btn-secondary mt-3">Kembali ke Dashboard</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Navigasi Nomor Section -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4">
                    <h6 class="fw-bold mb-3">Navigasi Soal</h6>
                    <div class="number-nav mb-4" id="numberNav">
                        <?php for ($i = 0; $i < $total_pertanyaan; $i++): ?>
                            <button type="button" id="nav-btn-<?= $i ?>" class="btn <?= $i === 0 ? 'btn-sas btn-sas-lab' : 'btn-outline-secondary' ?> fw-bold" onclick="navigasi(<?= $i ?>)"><?= $i + 1 ?></button>
                        <?php endfor; ?>
                    </div>
                    <hr>
                    <?php if ($total_pertanyaan > 0): ?>
                        <button type="submit" name="submit_ujian" class="btn btn-danger w-100 py-2 fw-bold" onclick="return confirm('Apakah Anda yakin ingin menyelesaikan ujian?')">Selesaikan Ujian</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
    let currentSoal = 0;
    const totalSoal = <?= $total_pertanyaan ?>;
    
    function navigasi(index) {
        if (index < 0 || index >= totalSoal) return;
        
        // Sembunyikan soal saat ini
        document.getElementById('soal-box-' + currentSoal).classList.remove('active');
        // Update nav button warna jika belum terjawab (biarkan hijau jika sudah)
        const currentBtn = document.getElementById('nav-btn-' + currentSoal);
        if (!currentBtn.classList.contains('btn-sas btn-sas-lab')) {
            currentBtn.classList.remove('btn-sas btn-sas-lab');
            currentBtn.classList.add('btn-outline-secondary');
        }
        
        // Tampilkan soal baru
        currentSoal = index;
        document.getElementById('soal-box-' + currentSoal).classList.add('active');
        // Update nav button baru
        const newBtn = document.getElementById('nav-btn-' + currentSoal);
        if (!newBtn.classList.contains('btn-sas btn-sas-lab')) {
            newBtn.classList.remove('btn-outline-secondary');
            newBtn.classList.add('btn-sas btn-sas-lab');
        }
    }
    
    function tandaiTerjawab(index) {
        const btn = document.getElementById('nav-btn-' + index);
        btn.classList.remove('btn-outline-secondary');
        btn.classList.remove('btn-sas btn-sas-lab');
        btn.classList.add('btn-sas btn-sas-lab');
    }

    // Timer logic 60 menit
    let waktuMulai = localStorage.getItem('ujian_waktu_mulai');
    const durasi = 60 * 60 * 1000; // 60 menit dalam milidetik

    if (!waktuMulai) {
        waktuMulai = new Date().getTime();
        localStorage.setItem('ujian_waktu_mulai', waktuMulai);
    } else {
        waktuMulai = parseInt(waktuMulai);
    }

    const timerElement = document.getElementById('timer');
    
    const interval = setInterval(function() {
        const sekarang = new Date().getTime();
        const selisih = (waktuMulai + durasi) - sekarang;

        if (selisih <= 0) {
            clearInterval(interval);
            timerElement.innerHTML = "Waktu Habis!";
            localStorage.removeItem('ujian_waktu_mulai');
            // Auto submit
            document.getElementById('examForm').submit();
        } else {
            const menit = Math.floor((selisih % (1000 * 60 * 60)) / (1000 * 60));
            const detik = Math.floor((selisih % (1000 * 60)) / 1000);
            
            // Format 2 digit
            const menitStr = menit < 10 ? '0' + menit : menit;
            const detikStr = detik < 10 ? '0' + detik : detik;
            
            timerElement.innerHTML = `Sisa Waktu: ${menitStr}:${detikStr}`;
        }
    }, 1000);
    
    // Hapus timer jika disubmit manual
    document.getElementById('examForm').addEventListener('submit', function() {
        localStorage.removeItem('ujian_waktu_mulai');
    });
</script>
</body>
</html>
