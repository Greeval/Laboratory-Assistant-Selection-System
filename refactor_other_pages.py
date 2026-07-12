import re

files_to_refactor = [
    'kelola_soal.php',
    'jadwal_interview.php',
    'keputusan_seleksi.php',
    'kirim_notifikasi.php',
    'penilaian_interview.php',
    'penilaian_ujian.php',
    'ujian_mahasiswa.php'
]

def refactor_files():
    for filename in files_to_refactor:
        try:
            with open(filename, 'r', encoding='utf-8') as f:
                content = f.read()
            
            # 1. Replace the entire <style> block
            style_pattern = re.compile(r'<style>.*?</style>', re.DOTALL)
            
            # Determine role from filename or content (heuristics)
            role_bg = "var(--primary)"
            role_ink = "#fff"
            if "Admin Lab" in content or "kelola_soal" in filename or "penilaian" in filename:
                role_bg = "var(--admin-lab-bg)"
                role_ink = "var(--admin-lab-ink)"
            elif "Admin Prodi" in content or "kirim_notifikasi" in filename:
                role_bg = "var(--admin-prodi-bg)"
                role_ink = "var(--admin-prodi-ink)"
            elif "Mahasiswa" in content or "ujian" in filename:
                role_bg = "var(--mahasiswa-bg)"
                role_ink = "var(--mahasiswa-ink)"
            
            new_style = f"""<link rel="stylesheet" href="assets/css/saslab-design.css">
    <style>
        /* Layout Specific */
        body {{ background-color: var(--background); }}
        .sidebar {{
            background-color: var(--surface); 
            border-right: 1px solid var(--border);
            height: 100vh;
            width: 260px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: 0.3s;
            overflow-y: auto;
        }}
        .sidebar.closed {{ left: -260px; }}
        
        .sidebar-header {{
            padding: 24px;
            font-family: var(--font-heading);
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--on-surface);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
        }}
        
        .nav-link-custom, .sidebar .nav-link {{
            color: var(--ink-soft);
            font-weight: 600;
            padding: 12px 24px;
            text-decoration: none;
            display: block;
            transition: all 0.2s;
            margin: 4px 16px;
            border-radius: 999px;
        }}
        .nav-link-custom:hover, .sidebar .nav-link:hover {{
            background-color: var(--surface-alt);
            color: var(--on-surface);
        }}
        .sidebar .nav-link-custom.active, .sidebar .nav-link.active {{
            background-color: {role_bg};
            color: {role_ink} !important;
        }}

        .main-content, main {{
            margin-left: 260px;
            transition: 0.3s;
            min-height: 100vh;
            background-color: var(--background);
        }}
        .main-content.expanded {{ margin-left: 0; }}

        .page-header {{
            background: var(--surface); 
            border-bottom: 1px solid var(--border);
            padding: 24px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }}

        .tab-content-panel {{ display: none; }}
        .tab-content-panel.active {{ display: block; }}
        
        .navbar-custom, .navbar {{
            background-color: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 12px 24px;
        }}

        @media (max-width: 768px) {{
            .sidebar {{ width: 280px; left: -280px; z-index: 1050; }}
            .sidebar.active {{ left: 0; }}
            .main-content, main {{ margin-left: 0 !important; }}
            .sidebar-overlay {{ position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1040; display: none; }}
            .sidebar-overlay.active {{ display: block; }}
        }}
    </style>"""
            
            content = style_pattern.sub(new_style, content)

            # 2. Update HTML classes
            content = content.replace('navbar-dark', 'navbar-light')
            content = content.replace('text-white', 'text-dark')
            content = content.replace('text-white-50', 'text-muted')
            content = content.replace('btn-outline-light', 'btn-outline-dark')
            content = content.replace('bg-dark', 'bg-light')
            
            # 3. Update cards
            content = content.replace('card card-custom', 'sas-card')
            content = content.replace('class="card"', 'class="card sas-card"')
            content = content.replace('style="background-color: #1d1e24; border: 1px solid #2a2d35 !important;"', '')
            content = content.replace('style="background-color: #1d1e24; border-color: #2a2d35 !important;"', '')
            content = content.replace('background-color: #252730; border-color: #3d4050 !important;', '')
            content = content.replace('background-color: #1d1e24; border-bottom: 1px solid #2a2d35;', '')
            
            # 4. Update buttons and inputs
            btn_class = 'btn-sas-prodi' if 'Admin Prodi' in content else ('btn-sas-lab' if 'Admin Lab' in content else 'btn-sas-mahasiswa')
            content = content.replace('btn-primary', f'btn-sas {btn_class}')
            content = content.replace('btn-success', f'btn-sas {btn_class}')
            
            content = content.replace('form-control', 'form-control sas-input')
            content = content.replace('alert-warning', 'alert-warning sas-badge-warning')
            content = content.replace('alert-danger', 'alert-danger sas-badge-danger')
            content = content.replace('alert-success', 'alert-success sas-badge-success')
            content = content.replace('alert-info', 'alert-info sas-badge-info')
            
            # 5. Fix brand colors
            content = content.replace('text-danger', 'text-primary')
            content = content.replace('text-info', 'text-secondary')

            with open(filename, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Refactored {filename}")
        except FileNotFoundError:
            print(f"File {filename} not found.")

if __name__ == "__main__":
    refactor_files()
