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
    'ujian_mahasiswa.php',
    'koneksiweb.php',
    'kirim_notifikasi.php',
    'ajax_update_inline.php',
    'csrf_helper.php'
]

# Simple regex to catch emoji ranges
emoji_pattern = re.compile(r'[\U00010000-\U0010ffff]', flags=re.UNICODE)

for filepath in files_to_fix:
    if not os.path.exists(filepath): continue
    
    with open(filepath, 'r', encoding='utf-8') as f:
        lines = f.readlines()
        
    original_lines = list(lines)
    new_lines = []
    
    for line in lines:
        if '//' in line or '<!--' in line:
            # Remove emojis from lines containing comments
            cleaned = emoji_pattern.sub('', line)
            
            if cleaned.strip().startswith('//'):
                # Remove dividers
                if re.match(r'^\s*//\s*={5,}\s*$', cleaned):
                    continue
                
                # Check for all caps or numbered caps
                match_caps = re.match(r'^(\s*//\s*)([A-Z0-9\s\.\(\)&]+)$', cleaned)
                if match_caps:
                    prefix = match_caps.group(1)
                    text = match_caps.group(2).strip()
                    # Strip "1. " if present
                    text = re.sub(r'^\d+\.\s*', '', text)
                    if text.isupper():
                        text = text.capitalize()
                    cleaned = prefix + text + "\n"
                
                new_lines.append(cleaned)
            else:
                new_lines.append(cleaned)
        else:
            new_lines.append(line)
            
    # Also remove empty consecutive lines that might have been left by removing dividers
    final_content = "".join(new_lines)
    final_content = re.sub(r'\n{3,}', '\n\n', final_content)

    if "".join(original_lines) != final_content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(final_content)
        print(f"Cleaned comments in {filepath}")
