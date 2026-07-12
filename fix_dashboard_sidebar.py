import os
import re

new_sidebar_html = """<!-- Mobile Navbar Toggler (Visible only on small screens) -->
<nav class="navbar navbar-dark d-md-none px-3 py-2" style="background-color: #1d1e24; border-bottom: 1px solid #2a2d35;">
    <a class="navbar-brand text-info fw-bold fs-6" href="#">Admin Lab Portal</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
</nav>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse p-3">
    <div class="text-center my-3">
        <h5 class="fw-bold text-info">Admin Lab Portal</h5>
        <hr>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= $active_tab === 'profil' ? 'active' : '' ?>" href="#" onclick="switchTab('profil')"><i class="bi bi-person-fill me-2"></i>Profil</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $active_tab === 'dashboard' ? 'active' : '' ?>" href="#" onclick="switchTab('dashboard')"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="kelola_soal.php"><i class="bi bi-journal-text me-2"></i>Kelola Soal</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="penilaian_ujian.php"><i class="bi bi-pencil-square me-2"></i>Penilaian</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="jadwal_interview.php"><i class="bi bi-camera-video me-2"></i>Interview</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="keputusan_seleksi.php"><i class="bi bi-trophy me-2"></i>Hasil Seleksi</a>
        </li>
        <li class="nav-item mt-3">
            <a class="nav-link text-warning fw-bold" href="logout.php?role=Admin%20Lab" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                <i class="bi bi-box-arrow-left me-2"></i>Logout
            </a>
        </li>
    </ul>
</nav>"""

file = 'dashboard_admin_lab.php'
if os.path.exists(file):
    with open(file, 'r', encoding='utf-8') as f:
        content = f.read()
        
    pattern = r'<nav[^>]*id="sidebarMenu"[^>]*>.*?</nav>'
    if re.search(pattern, content, flags=re.DOTALL):
        content = re.sub(pattern, new_sidebar_html, content, count=1, flags=re.DOTALL)
        with open(file, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Fixed {file}")
    else:
        print(f"Could not find sidebar nav in {file}")
