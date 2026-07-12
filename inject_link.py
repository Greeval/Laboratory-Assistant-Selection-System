import os, re
files = ['dashboard_admin_lab.php', 'kelola_soal.php', 'penilaian_ujian.php', 'keputusan_seleksi.php']
for f in files:
    if os.path.exists(f):
        with open(f, 'r', encoding='utf-8') as file:
            c = file.read()
        
        if 'penilaian_interview.php' not in c:
            new_link = '<li class="nav-item"><a class="nav-link" href="penilaian_interview.php"><i class="bi bi-person-video3 me-2"></i> Interview</a></li>'
            c = re.sub(
                r'(<li class="nav-item"><a class="nav-link.*?href="penilaian_ujian\.php".*?</li>)',
                r'\1\n                ' + new_link,
                c
            )
            with open(f, 'w', encoding='utf-8') as file:
                file.write(c)
            print('Updated ' + f)
