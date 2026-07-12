import os
import re

css_addition = """
        /* Sidebar dark mode override */
        .sidebar { min-height: 100vh !important; background-color: #1d1e24 !important; border-right: 1px solid #2a2d35 !important; }
        .sidebar .nav-link { color: #94a3b8 !important; font-weight: 500 !important; padding: 12px 20px !important; border-radius: 8px !important; margin-bottom: 5px !important; background-color: transparent !important; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: rgba(45, 212, 227, 0.1) !important; color: #2dd4e3 !important; }
"""

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
</nav>"""

files = ['kelola_soal.php', 'jadwal_interview.php', 'keputusan_seleksi.php', 'penilaian_ujian.php', 'penilaian_interview.php']

for file in files:
    if os.path.exists(file):
        with open(file, 'r', encoding='utf-8') as f:
            content = f.read()
            
        # 1. Inject CSS
        if '/* Sidebar dark mode override */' not in content:
            if '</style>' in content:
                content = content.replace('</style>', f'{css_addition}\n</style>')
                
        # 2. Replace sidebar HTML
        # We need to find the old <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse p-3"> ... </nav>
        # Use regex to replace the <nav> block
        pattern = r'<nav[^>]*class="[^"]*sidebar[^"]*"[^>]*>.*?</nav>'
        if re.search(pattern, content, flags=re.DOTALL):
            content = re.sub(pattern, new_sidebar_html, content, count=1, flags=re.DOTALL)
            with open(file, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Fixed {file}")
        else:
            print(f"Could not find sidebar nav in {file}")
