import os
import re

files_to_fix = [
    'login.php',
    'dashboard_admin_prodi_km.php',
    'dashboard_admin_lab.php',
    'dashboard_mahasiswa.php',
    'kelola_soal.php',
    'jadwal_interview.php',
    'keputusan_seleksi.php',
    'penilaian_ujian.php',
    'penilaian_interview.php',
    'ujian_mahasiswa.php'
]

for filepath in files_to_fix:
    if not os.path.exists(filepath): continue
    
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
        
    original = content
    
    # 1. Fix CSRF Helper Include
    # Replace require_once __DIR__ . '/koneksiweb.php'; (and variants)
    # Only if csrf_helper.php is NOT already included (except in ajax_update_inline.php which we skip here)
    if 'csrf_helper.php' not in content:
        content = re.sub(r"(require_once\s*__DIR__\s*\.\s*['\"]/koneksiweb\.php['\"];|include_once\s*__DIR__\s*\.\s*['\"]/koneksiweb\.php['\"];|require_once\s*['\"]koneksiweb\.php['\"];|include\s*['\"]koneksiweb\.php['\"];)",
                         r"\1\nrequire_once __DIR__ . '/csrf_helper.php';\nverify_csrf_token();", content)

    # 2. Fix broken backslashes from previous regex script
    content = content.replace('echo \\"<script>', 'echo "<script>')
    content = content.replace('</script>\\";', '</script>";')
    
    # Check for another pattern
    content = content.replace('echo \\"<script>alert(', 'echo "<script>alert(')
                         
    if content != original:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Fixed CSRF include and syntax errors in {filepath}")
    else:
        print(f"No changes needed in {filepath}")
