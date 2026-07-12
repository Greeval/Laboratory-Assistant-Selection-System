import os

files_lab = ['dashboard_admin_lab.php', 'kelola_soal.php', 'keputusan_seleksi.php', 'penilaian_ujian.php']
for f in files_lab:
    if os.path.exists(f):
        with open(f, 'r', encoding='utf-8') as file:
            c = file.read()
        c = c.replace("if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin Lab' || empty($_SESSION['Admin Lab']['id_user']))", "if (empty($_SESSION['Admin Lab']['id_user']))")
        with open(f, 'w', encoding='utf-8') as file:
            file.write(c)
        print('Updated ' + f)

if os.path.exists('dashboard_admin_prodi_km.php'):
    with open('dashboard_admin_prodi_km.php', 'r', encoding='utf-8') as file:
        c = file.read()
    c = c.replace("if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin Prodi')", "if (empty($_SESSION['Admin Prodi']['id_user']))")
    with open('dashboard_admin_prodi_km.php', 'w', encoding='utf-8') as file:
        file.write(c)
    print('Updated dashboard_admin_prodi_km.php')

if os.path.exists('ujian_mahasiswa.php'):
    with open('ujian_mahasiswa.php', 'r', encoding='utf-8') as file:
        c = file.read()
    c = c.replace("if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Mahasiswa' || empty($_SESSION['Mahasiswa']['id_user']))", "if (empty($_SESSION['Mahasiswa']['id_user']))")
    with open('ujian_mahasiswa.php', 'w', encoding='utf-8') as file:
        file.write(c)
    print('Updated ujian_mahasiswa.php')
