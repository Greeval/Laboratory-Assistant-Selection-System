import os
import re

def fix_upload_in_file(filepath):
    if not os.path.exists(filepath): return
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    original = content
    
    # PDF CV
    target_cv = r"if \(strtolower\(\$ext_cv\) == 'pdf' && \$_FILES\['cv'\]\['size'\] <= 2097152\) \{"
    replacement_cv = "if (strtolower($ext_cv) == 'pdf' && $_FILES['cv']['size'] <= 2097152 && mime_content_type($_FILES['cv']['tmp_name']) == 'application/pdf') {"
    content = re.sub(target_cv, replacement_cv, content)

    # PDF Transkrip
    target_ts = r"if \(strtolower\(\$ext_ts\) == 'pdf' && \$_FILES\['transkrip_nilai'\]\['size'\] <= 2097152\) \{"
    replacement_ts = "if (strtolower($ext_ts) == 'pdf' && $_FILES['transkrip_nilai']['size'] <= 2097152 && mime_content_type($_FILES['transkrip_nilai']['tmp_name']) == 'application/pdf') {"
    content = re.sub(target_ts, replacement_ts, content)
    
    # Image Foto
    target_foto = r"if \(in_array\(strtolower\(\$ext_foto\), \['jpg', 'jpeg', 'png'\]\) && \$_FILES\['foto_profil'\]\['size'\] <= 2097152\) \{"
    replacement_foto = "if (in_array(strtolower($ext_foto), ['jpg', 'jpeg', 'png']) && $_FILES['foto_profil']['size'] <= 2097152 && strpos(mime_content_type($_FILES['foto_profil']['tmp_name']), 'image/') === 0) {"
    content = re.sub(target_foto, replacement_foto, content)
    
    if content != original:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Fixed file upload validation in {filepath}")
    else:
        print(f"No changes made to {filepath}")

fix_upload_in_file('dashboard_mahasiswa.php')
fix_upload_in_file('dashboard_admin_lab.php')
