import os
import re

files_to_check = [
    'dashboard_admin_lab.php',
    'jadwal_interview.php',
    'kelola_soal.php',
    'keputusan_seleksi.php',
    'penilaian_ujian.php',
    'ujian_mahasiswa.php'
]

for filepath in files_to_check:
    if not os.path.exists(filepath): continue
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    original = content
    
    # 1. Replace mysqli_error with generic error
    content = re.sub(r"echo \"<script>alert\('([^']+): \" \. mysqli_error\(\$koneksi\) \. \"'\);(.*?)</script>\";",
                     r"echo \"<script>alert('\1. Silakan coba lagi.');\2</script>\";", content)
                     
    # 2. Fix $status_baru injection
    content = content.replace(
        "echo \"<script>alert('Status berkas diperbarui: \" . $status_baru . \"');",
        "echo \"<script>alert('Status berkas diperbarui: \" . htmlspecialchars($status_baru) . \"');"
    )

    if content != original:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Fixed script alerts in {filepath}")
