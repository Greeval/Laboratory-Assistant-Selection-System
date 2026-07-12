import os
import re

css_block = """
        /* GLOBAL DARK MODE OVERRIDES UNTUK ADMIN LAB TABS */
        body { background-color: #121318 !important; color: #e2e8f0 !important; }
        .card { background-color: #1d1e24 !important; border: 1px solid #2a2d35 !important; color: #e2e8f0 !important; }
        .card-body, .card-header, .card-footer { background-color: #1d1e24 !important; color: #e2e8f0 !important; border-color: #2a2d35 !important; }
        .modal-content { background-color: #1d1e24 !important; border: 1px solid #2a2d35 !important; color: #e2e8f0 !important; }
        .modal-header, .modal-body, .modal-footer { background-color: #1d1e24 !important; color: #e2e8f0 !important; border-color: #2a2d35 !important; }
        
        /* Form inputs */
        .form-control, .form-select { background-color: #131418 !important; border-color: #2a2d35 !important; color: #e2e8f0 !important; }
        .form-control:focus, .form-select:focus { background-color: #131418 !important; border-color: #2dd4e3 !important; color: #fff !important; box-shadow: 0 0 0 0.2rem rgba(45, 212, 227, 0.15) !important; }
        
        /* Table overrides */
        .table { color: #e2e8f0 !important; }
        .table * { color: #e2e8f0 !important; }
        .table thead th, .table-light, .table-light > th, .table-light > td {
            background-color: #252730 !important;
            color: #94a3b8 !important;
            border-bottom: 2px solid #2a2d35 !important;
        }
        .table tbody td {
            background-color: transparent !important;
        }
        .table tbody tr {
            background-color: #1d1e24 !important;
            color: #e2e8f0 !important;
        }
        .table tbody tr:nth-child(even) {
            background-color: #22242c !important;
        }
        .table tbody tr:hover {
            background-color: rgba(45, 212, 227, 0.07) !important;
        }
        .table-bordered td, .table-bordered th { border-color: #2a2d35 !important; }
        
        /* Text overrides */
        .text-dark { color: #f8fafc !important; }
        .text-muted { color: #94a3b8 !important; }
        
        /* Buttons hover */
        .btn-outline-warning:hover { background-color: #ffc107 !important; color: #000 !important; }
        .btn-outline-danger { color: #fca5a5 !important; border-color: #dc3545 !important; }
        .btn-outline-danger:hover { background-color: #dc3545 !important; color: #ffffff !important; }
        .btn-outline-info { color: #67e8f9 !important; border-color: #0dcaf0 !important; }
        .btn-outline-info:hover { background-color: #0dcaf0 !important; color: #000 !important; }
"""

files = ['kelola_soal.php', 'jadwal_interview.php', 'keputusan_seleksi.php', 'penilaian_ujian.php', 'penilaian_interview.php']

for file in files:
    if os.path.exists(file):
        with open(file, 'r', encoding='utf-8') as f:
            content = f.read()
            
        # Check if already injected
        if 'GLOBAL DARK MODE OVERRIDES UNTUK ADMIN LAB TABS' in content:
            print(f"Skipping {file}, already injected.")
            continue
            
        # Insert before </style> or </head>
        if '</style>' in content:
            new_content = content.replace('</style>', f'{css_block}\n</style>')
        elif '</head>' in content:
            new_content = content.replace('</head>', f'<style>{css_block}</style>\n</head>')
        else:
            print(f"No </style> or </head> found in {file}")
            continue
            
        with open(file, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Injected into {file}")
