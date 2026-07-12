import os

files = ['dashboard_admin_lab.php', 'kelola_soal.php', 'jadwal_interview.php', 'keputusan_seleksi.php', 'penilaian_ujian.php', 'penilaian_interview.php']

for file in files:
    if os.path.exists(file):
        with open(file, 'r', encoding='utf-8') as f:
            content = f.read()
            
        # Add special hover for logout so it doesn't turn cyan
        target = ".sidebar .nav-link.active, .sidebar .nav-link:hover { background-color: rgba(45, 212, 227, 0.1) !important; color: #2dd4e3 !important; }"
        replacement = target + "\n        .sidebar .nav-link.text-warning:hover { background-color: rgba(255, 193, 7, 0.1) !important; color: #ffc107 !important; }"
        
        if target in content and ".sidebar .nav-link.text-warning:hover" not in content:
            content = content.replace(target, replacement)
            with open(file, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Fixed hover for {file}")
