import os, glob, re

def replace_in_file(path, replacements):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    original = content
    for old, new in replacements:
        content = content.replace(old, new)
    
    # Regex for complex ones
    content = re.sub(r"if\s*\(\s*empty\(\s*\$_SESSION\['id_user'\]\s*\)\s*\|\|\s*\$_SESSION\['role'\]\s*!==\s*'Mahasiswa'\s*\)", "if (empty($_SESSION['Mahasiswa']['id_user']))", content)
    content = re.sub(r"if\s*\(\s*empty\(\s*\$_SESSION\['id_user'\]\s*\)\s*\|\|\s*\$_SESSION\['role'\]\s*!==\s*'Admin Prodi'\s*\)", "if (empty($_SESSION['Admin Prodi']['id_user']))", content)
    content = re.sub(r"if\s*\(\s*empty\(\s*\$_SESSION\['id_user'\]\s*\)\s*\|\|\s*\$_SESSION\['role'\]\s*!==\s*'Admin Lab'\s*\)", "if (empty($_SESSION['Admin Lab']['id_user']))", content)
    
    if content != original:
        with open(path, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f'Updated {path}')

# 1. Update Mahasiswa files
m_files = ['dashboard_mahasiswa.php', 'ujian_mahasiswa.php']
for f in m_files:
    if os.path.exists(f):
        replace_in_file(f, [
            ("$_SESSION['id_user']", "$_SESSION['Mahasiswa']['id_user']"),
            ("$_SESSION['username']", "$_SESSION['Mahasiswa']['username']"),
            ('href="logout.php"', 'href="logout.php?role=Mahasiswa"')
        ])

# 2. Update Admin Prodi files
p_files = ['dashboard_admin_prodi_km.php', 'ajax_update_inline.php']
for f in p_files:
    if os.path.exists(f):
        replace_in_file(f, [
            ("$_SESSION['id_user']", "$_SESSION['Admin Prodi']['id_user']"),
            ("$_SESSION['username']", "$_SESSION['Admin Prodi']['username']"),
            ('href="logout.php"', 'href="logout.php?role=Admin%20Prodi"')
        ])

# 3. Update Admin Lab files
l_files = ['dashboard_admin_lab.php', 'kelola_soal.php', 'penilaian_ujian.php', 'keputusan_seleksi.php']
for f in l_files:
    if os.path.exists(f):
        replace_in_file(f, [
            ("$_SESSION['id_user']", "$_SESSION['Admin Lab']['id_user']"),
            ("$_SESSION['username']", "$_SESSION['Admin Lab']['username']"),
            ('href="logout.php"', 'href="logout.php?role=Admin%20Lab"')
        ])

print('Done')
