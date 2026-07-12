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
    
    # 1. Include csrf_helper.php and verify token near the top
    if 'koneksiweb.php' in content and 'csrf_helper.php' not in content:
        # Some files use require_once 'koneksiweb.php', some use include 'koneksiweb.php'
        # We will insert require_once 'csrf_helper.php'; and verify_csrf_token(); right after it
        content = re.sub(r"(require_once\s*['\"]koneksiweb\.php['\"];|include\s*['\"]koneksiweb\.php['\"];)",
                         r"\1\nrequire_once 'csrf_helper.php';\nverify_csrf_token();", content)
                         
    # 2. Add csrf_field() to all <form method="POST"...>
    if '<?= csrf_field(); ?>' not in content:
        content = re.sub(r"(<form[^>]*method=[\"']POST[\"'][^>]*>)",
                         r"\1\n    <?= csrf_field(); ?>", content)
                         
    if content != original:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Added CSRF to {filepath}")
    else:
        print(f"No CSRF changes needed or target not found in {filepath}")
