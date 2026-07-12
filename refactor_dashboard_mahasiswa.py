import re

def refactor_dashboard():
    with open('dashboard_mahasiswa.php', 'r', encoding='utf-8') as f:
        content = f.read()

    # 1. Replace the entire <style> block with a link to our new css, and keep only layout specific styles
    style_pattern = re.compile(r'<style>.*?</style>', re.DOTALL)
    new_style = """<link rel="stylesheet" href="assets/css/saslab-design.css">
    <style>
        /* Layout Specific */
        .sidebar {
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
        }
        .sidebar.closed { left: -260px; }
        
        .sidebar-header {
            padding: 24px;
            font-family: var(--font-heading);
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--on-surface);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
        }
        
        .nav-link-custom {
            color: var(--ink-soft);
            font-weight: 600;
            padding: 12px 24px;
            text-decoration: none;
            display: block;
            transition: all 0.2s;
            margin: 4px 16px;
            border-radius: 999px;
        }
        .nav-link-custom:hover {
            background-color: var(--surface-alt);
            color: var(--on-surface);
        }
        .sidebar .nav-link-custom.active {
            background-color: var(--mahasiswa-bg);
            color: var(--mahasiswa-ink) !important;
        }

        .main-content {
            margin-left: 260px;
            transition: 0.3s;
            min-height: 100vh;
        }
        .main-content.expanded { margin-left: 0; }

        .page-header {
            background: var(--surface); 
            border-bottom: 1px solid var(--border);
            padding: 24px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .tab-content-panel { display: none; }
        .tab-content-panel.active { display: block; }
        
        .navbar-custom {
            background-color: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 12px 24px;
        }

        /* Tracker progress adjustment */
        .tracker-wrapper { position: relative; margin: 40px auto; max-width: 900px; }
        .progress-bar-bg { position: absolute; top: 20px; left: 10%; right: 10%; height: 4px; background-color: var(--border); z-index: 1; border-radius: 4px;}
        .progress-bar-fill { position: absolute; top: 20px; left: 10%; height: 4px; background-color: var(--mahasiswa-ink); z-index: 2; transition: width 0.4s ease; border-radius: 4px;}
        .tracker-steps { display: flex; justify-content: space-between; position: relative; z-index: 3; }
        .step-item { text-align: center; width: 120px; }
        .step-circle {
            width: 44px; height: 44px; border-radius: 50%;
            background-color: var(--surface); border: 2px solid var(--border-strong);
            margin: 0 auto 10px auto; display: flex; align-items: center; justify-content: center;
            font-weight: bold; color: var(--muted);
        }
        .step-item.active .step-circle, .step-item.done .step-circle {
            background-color: var(--mahasiswa-bg);
            border-color: var(--mahasiswa-ink);
            color: var(--mahasiswa-ink);
        }
        .step-item.done .step-circle {
            background-color: var(--mahasiswa-ink);
            color: #fff;
        }

        @media (max-width: 768px) {
            .sidebar { width: 280px; left: -280px; z-index: 1050; }
            .sidebar.active { left: 0; }
            .main-content { margin-left: 0; }
            .sidebar-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1040; display: none; }
            .sidebar-overlay.active { display: block; }
            
            .step-item { width: auto; flex: 1; }
            .step-circle { width: 36px; height: 36px; font-size: 0.9rem; }
            .progress-bar-bg, .progress-bar-fill { top: 16px; }
        }
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
    content = content.replace('style="background-color: #1d1e24; border: 1px solid #2a2d35 !important;"', '')
    content = content.replace('style="background-color: #1d1e24; border-color: #2a2d35 !important;"', '')
    content = content.replace('background-color: #252730; border-color: #3d4050 !important;', '')
    
    # 4. Update buttons and inputs
    content = content.replace('btn-primary', 'btn-sas btn-sas-mahasiswa')
    content = content.replace('form-control', 'form-control sas-input focus-mahasiswa')
    content = content.replace('alert-warning', 'alert-warning sas-badge-warning')
    content = content.replace('alert-danger', 'alert-danger sas-badge-danger')
    content = content.replace('alert-success', 'alert-success sas-badge-success')
    content = content.replace('alert-info', 'alert-info sas-badge-info')
    
    # 5. Fix brand colors
    content = content.replace('text-danger', 'text-primary')
    content = content.replace('text-info', 'text-secondary')

    with open('dashboard_mahasiswa.php', 'w', encoding='utf-8') as f:
        f.write(content)

if __name__ == "__main__":
    refactor_dashboard()
