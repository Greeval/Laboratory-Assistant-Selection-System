import os

filepath = 'dashboard_admin_prodi_km.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

original = content

# 1. Update the PHP handler
old_handler = """// PROSES AKSI: HAPUS AKUN (via Parameter GET)
// ============================================================
if (isset($_GET['aksi']) && $_GET['aksi'] === 'hapus' && isset($_GET['id'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['id']);"""

new_handler = """// PROSES AKSI: HAPUS AKUN (via POST dengan CSRF)
// ============================================================
if (isset($_POST['hapus_akun']) && isset($_POST['id_hapus'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_POST['id_hapus']);"""

content = content.replace(old_handler, new_handler)

# 2. Update the HTML links (Mahasiswa)
# Old: <a href="dashboard_admin_prodi_km.php?aksi=hapus&id=<?= $row_mhs['id_user']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah anda yakin ingin menghapus akun mahasiswa ini?')"><i class="bi bi-trash3"></i> Hapus</a>
old_link_mhs_1 = """<a href="dashboard_admin_prodi_km.php?aksi=hapus&id=<?= $row_mhs['id_user']; ?>"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Apakah anda yakin ingin menghapus akun mahasiswa ini?')">
                                                        <i class="bi bi-trash3"></i> Hapus
                                                    </a>"""

old_link_mhs_2 = """<a href="dashboard_admin_prodi_km.php?aksi=hapus&id=<?= $row_mhs['id_user']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah anda yakin ingin menghapus akun mahasiswa ini?')">
                                                        <i class="bi bi-trash3"></i> Hapus
                                                    </a>"""

new_link_mhs = """<form method="POST" class="d-inline" onsubmit="return confirm('Apakah anda yakin ingin menghapus akun mahasiswa ini?');">
                                                        <?= csrf_field(); ?>
                                                        <input type="hidden" name="id_hapus" value="<?= $row_mhs['id_user']; ?>">
                                                        <button type="submit" name="hapus_akun" class="btn btn-sm btn-outline-danger">
                                                            <i class="bi bi-trash3"></i> Hapus
                                                        </button>
                                                    </form>"""

if old_link_mhs_1 in content:
    content = content.replace(old_link_mhs_1, new_link_mhs)
elif old_link_mhs_2 in content:
    content = content.replace(old_link_mhs_2, new_link_mhs)
else:
    import re
    content = re.sub(r'<a[^>]*href="dashboard_admin_prodi_km\.php\?aksi=hapus&id=<\?=\s*\$row_mhs\[\'id_user\'\];\s*\?>"[^>]*onclick="return confirm\(\'Apakah anda yakin ingin menghapus akun mahasiswa ini\?\'\)"[^>]*>\s*<i class="bi bi-trash3"></i> Hapus\s*</a>', new_link_mhs, content)

# 3. Update HTML links (Admin)
old_link_admin_1 = """<a href="dashboard_admin_prodi_km.php?aksi=hapus&id=<?= $row_admin['id_user']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah anda yakin ingin menghapus akun administrator ini?')">
                                                        <i class="bi bi-trash3"></i> Hapus
                                                    </a>"""
                                                    
new_link_admin = """<form method="POST" class="d-inline" onsubmit="return confirm('Apakah anda yakin ingin menghapus akun administrator ini?');">
                                                        <?= csrf_field(); ?>
                                                        <input type="hidden" name="id_hapus" value="<?= $row_admin['id_user']; ?>">
                                                        <button type="submit" name="hapus_akun" class="btn btn-sm btn-outline-danger">
                                                            <i class="bi bi-trash3"></i> Hapus
                                                        </button>
                                                    </form>"""

content = content.replace(old_link_admin_1, new_link_admin)

if content != original:
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Fixed hapus akun to use POST form")
else:
    print("Could not find targets in file")
