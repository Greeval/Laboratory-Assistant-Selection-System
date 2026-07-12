import re
import os

mahasiswa_file = 'c:/Users/hakim/Greeval/PROJECT/Web Seleksi Asleb/dashboard_mahasiswa.php'
with open(mahasiswa_file, 'r', encoding='utf-8') as f:
    m_content = f.read()

# Replace green in tracker
m_content = m_content.replace('background-color: #198754;', 'background-color: #0d6efd;')
m_content = m_content.replace('box-shadow: 0 0 0 2px #198754;', 'box-shadow: 0 0 0 2px #0d6efd;')
m_content = m_content.replace('color: #198754;', 'color: #0d6efd;')

with open(mahasiswa_file, 'w', encoding='utf-8') as f:
    f.write(m_content)

admin_files = [
    'c:/Users/hakim/Greeval/PROJECT/Web Seleksi Asleb/kelola_soal.php',
    'c:/Users/hakim/Greeval/PROJECT/Web Seleksi Asleb/penilaian_ujian.php',
    'c:/Users/hakim/Greeval/PROJECT/Web Seleksi Asleb/jadwal_interview.php',
    'c:/Users/hakim/Greeval/PROJECT/Web Seleksi Asleb/keputusan_seleksi.php'
]

for file in admin_files:
    if not os.path.exists(file):
        continue
    with open(file, 'r', encoding='utf-8') as f:
        content = f.read()

    # Replace neon cyan
    content = content.replace('rgba(45, 212, 227, 0.1)', 'rgba(255, 255, 255, 0.05)')
    content = content.replace('rgba(45, 212, 227, 0.05)', 'rgba(255, 255, 255, 0.05)')
    content = content.replace('rgba(45, 212, 227, 0.07)', 'rgba(255, 255, 255, 0.05)')
    content = content.replace('rgba(45, 212, 227, 0.15)', 'rgba(255, 255, 255, 0.15)')
    content = content.replace('rgba(45,212,227,0.07)', 'rgba(255, 255, 255, 0.05)')
    content = content.replace('color: #2dd4e3', 'color: #ffffff')
    content = content.replace('border-color: #2dd4e3', 'border-color: #ffffff')
    content = content.replace('text-info', 'text-white')

    with open(file, 'w', encoding='utf-8') as f:
        f.write(content)

print("Done fixing tracker green and admin sub-pages neon colors.")
