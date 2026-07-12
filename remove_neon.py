import re
import os

files = [
    'c:/Users/hakim/Greeval/PROJECT/Web Seleksi Asleb/dashboard_mahasiswa.php',
    'c:/Users/hakim/Greeval/PROJECT/Web Seleksi Asleb/dashboard_admin_prodi_km.php',
    'c:/Users/hakim/Greeval/PROJECT/Web Seleksi Asleb/dashboard_admin_lab.php'
]

for file in files:
    with open(file, 'r', encoding='utf-8') as f:
        content = f.read()

    # 1. Replace Mahasiswa specific green styles
    content = content.replace('.sidebar { background-color: #0a2318;', '.sidebar { background-color: #1d1e24; border-right: 1px solid #2a2d35;')
    content = content.replace('.sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #0d2b1e; color: #6ee7b7 !important; border-left: 4px solid #10b981; }', '.sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: rgba(255,255,255,0.05); color: #ffffff !important; border-left: 4px solid #ffffff; }')
    content = content.replace('.navbar { background: linear-gradient(135deg, #0a2318 0%, #0d3321 100%);', '.navbar { background: #1d1e24; border-bottom: 1px solid #2a2d35;')
    
    # 2. Replace Admin Prodi specific green styles
    content = content.replace('background: linear-gradient(135deg, #0a2318 0%, #0d3321 100%);', 'background: #1d1e24; border-bottom: 1px solid #2a2d35;')
    content = content.replace('<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #0a2318;">', '<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #1d1e24; border-bottom: 1px solid #2a2d35;">')
    
    # 3. Replace all neon cyan (rgba(45, 212, 227) and #2dd4e3) with standard white/gray across all dashboards
    content = content.replace('rgba(45, 212, 227, 0.1)', 'rgba(255, 255, 255, 0.05)')
    content = content.replace('rgba(45, 212, 227, 0.05)', 'rgba(255, 255, 255, 0.05)')
    content = content.replace('rgba(45, 212, 227, 0.07)', 'rgba(255, 255, 255, 0.05)')
    content = content.replace('rgba(45, 212, 227, 0.15)', 'rgba(255, 255, 255, 0.15)')
    content = content.replace('rgba(45,212,227,0.07)', 'rgba(255, 255, 255, 0.05)')
    content = content.replace('color: #2dd4e3', 'color: #ffffff')
    content = content.replace('border-color: #2dd4e3', 'border-color: #ffffff')

    # Specific fix for admin lab neon text on active links
    content = content.replace('.sidebar .nav-link.active, .sidebar .nav-link:hover { background-color: rgba(255, 255, 255, 0.05) !important; color: #ffffff !important; }', '.sidebar .nav-link.active, .sidebar .nav-link:hover { background-color: rgba(255, 255, 255, 0.05) !important; color: #ffffff !important; }')

    with open(file, 'w', encoding='utf-8') as f:
        f.write(content)

print("Done removing green theme and neon colors.")
