import os
import re

def process_file(filepath):
    if not os.path.exists(filepath): return
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    original = content
    
    # 1. Remove from INSERT queries
    content = re.sub(r"INSERT INTO akun \(username, password, password_plain, role\)\s+VALUES\s+\('([^']+)',\s*'([^']+)',\s*'([^']+)',\s*'([^']+)'\)", 
                     r"INSERT INTO akun (username, password, role) VALUES ('\1', '\2', '\4')", content)
                     
    # 2. Remove from UPDATE queries
    content = re.sub(r"UPDATE akun SET username = '([^']+)', password = '([^']+)', password_plain = '([^']+)', role = '([^']+)'",
                     r"UPDATE akun SET username = '\1', password = '\2', role = '\4'", content)
                     
    content = re.sub(r"UPDATE akun SET password = '([^']+)', password_plain = '([^']+)'",
                     r"UPDATE akun SET password = '\1'", content)
                     
    # 3. Remove from SELECT queries
    content = content.replace("a.password_plain, ", "")
    content = content.replace(", password_plain", "")
    
    # 4. Remove HTML td showing password
    content = re.sub(r"\s*<td><span class=\"badge bg-secondary\"><\?= htmlspecialchars\(\$row_mhs\['password_plain'\] \?\? 'Terenkripsi'\); \?></span></td>", "", content)
    content = re.sub(r"\s*<td><span class=\"badge bg-secondary\"><\?= htmlspecialchars\(\$row_admin\['password_plain'\] \?\? 'Terenkripsi'\); \?></span></td>", "", content)
    
    # 5. Remove HTML th for password
    content = re.sub(r"\s*<th>Password</th>", "", content)
    content = re.sub(r"\s*<th style=\"width: 150px;\">Password</th>", "", content)
    
    if content != original:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Fixed {filepath}")
    else:
        print(f"No changes made to {filepath}")

files_to_fix = [
    'dashboard_admin_prodi_km.php',
    'dashboard_admin_lab.php',
    'login.php'
]

for file in files_to_fix:
    process_file(file)

# Juga modifikasi seed.sql dan web_seleksi.sql agar tidak punya password_plain jika kebetulan ada
def remove_column_from_sql(filepath):
    if not os.path.exists(filepath): return
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    original = content
    content = re.sub(r"`password_plain` varchar\(\d+\) [^,]+,\n", "", content)
    if content != original:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Removed password_plain column from {filepath}")

remove_column_from_sql('web_seleksi.sql')
