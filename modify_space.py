import re
import os

filepath = 'c:/Users/hakim/Greeval/PROJECT/Web Seleksi Asleb/login.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update stars count to 300
content = content.replace('for ($i = 0; $i < 90; $i++) {', 'for ($i = 0; $i < 300; $i++) {')

# 2. Add star hover CSS and remove space-scene pointer-events:none
css_space_scene_old = """        .space-scene {
            position: fixed;
            inset: 0;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }

        .star {"""

css_space_scene_new = """        .space-scene {
            position: fixed;
            inset: 0;
            overflow: hidden;
            z-index: 0;
        }

        .star {
            pointer-events: auto;
            transition: transform 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;"""

content = content.replace(css_space_scene_old, css_space_scene_new)

star_hover_css = """
        .star:hover {
            transform: scale(4) !important;
            background-color: #2dd4e3 !important;
            box-shadow: 0 0 10px #2dd4e3, 0 0 20px #2dd4e3 !important;
            opacity: 1 !important;
        }"""
content = content.replace('animation: twinkle 3s ease-in-out infinite;\n        }', 'animation: twinkle 3s ease-in-out infinite;\n        }' + star_hover_css)

# 3. Remove CSS for planets, astronauts, rockets
# Use regex to remove from .planet-glow up to @media (prefers-reduced-motion
pattern_css_remove = re.compile(r'        \.planet-glow \{.*?(?=        @media \(max-width: 900px\))', re.DOTALL)
content = re.sub(pattern_css_remove, '', content)

# Also remove the media query that hides astronaut/rocket
pattern_media_remove = re.compile(r'        @media \(max-width: 900px\) \{.*?        \}\n\n', re.DOTALL)
content = re.sub(pattern_media_remove, '', content)

# 4. Remove HTML for planets, astronauts, rockets
html_scene_old = """<div class="space-scene">
    <?= $stars_html; ?>
    <div class="planet-glow planet-glow-1"></div>
    <div class="planet-glow planet-glow-2"></div>

    <div class="astronaut-parallax" data-depth="1.2">
        <svg class="astronaut-float" viewBox="0 0 120 170" xmlns="http://www.w3.org/2000/svg">
            <path d="M60 122 C 90 136, 96 150, 82 166" fill="none" stroke="#2dd4e3" stroke-width="1.5" stroke-dasharray="3 4" opacity="0.55"/>
            <rect x="14" y="78" width="12" height="42" rx="5" fill="#aab3bd"/>
            <rect x="94" y="78" width="12" height="42" rx="5" fill="#aab3bd"/>
            <path d="M42 132 C 38 148, 30 154, 22 158" fill="none" stroke="#f1f4f7" stroke-width="14" stroke-linecap="round"/>
            <path d="M78 132 C 82 148, 90 154, 98 158" fill="none" stroke="#f1f4f7" stroke-width="14" stroke-linecap="round"/>
            <path d="M30 90 C 14 96, 10 112, 20 122" fill="none" stroke="#f1f4f7" stroke-width="13" stroke-linecap="round"/>
            <path d="M90 88 C 108 78, 112 58, 100 46" fill="none" stroke="#f1f4f7" stroke-width="13" stroke-linecap="round"/>
            <circle cx="100" cy="44" r="9" fill="#f1f4f7" stroke="#c3cbd4" stroke-width="2"/>
            <rect x="28" y="72" width="64" height="62" rx="22" fill="#f1f4f7" stroke="#c3cbd4" stroke-width="2"/>
            <rect x="46" y="88" width="28" height="18" rx="4" fill="#1d1e24"/>
            <circle cx="53" cy="97" r="2.6" fill="#2dd4e3"/>
            <circle cx="62" cy="97" r="2.6" fill="#f02865"/>
            <circle cx="71" cy="97" r="2.6" fill="#fff"/>
            <circle cx="60" cy="42" r="34" fill="#f1f4f7" stroke="#c3cbd4" stroke-width="2"/>
            <ellipse cx="60" cy="43" rx="24" ry="22" fill="url(#visorGrad)"/>
            <ellipse cx="52" cy="34" rx="7" ry="4" fill="rgba(255,255,255,0.5)"/>
            <defs>
                <linearGradient id="visorGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#2dd4e3" stop-opacity="0.9"/>
                    <stop offset="100%" stop-color="#0c2a30" stop-opacity="0.95"/>
                </linearGradient>
            </defs>
        </svg>
    </div>

    <div class="rocket-parallax" data-depth="0.8">
        <svg class="rocket-float" viewBox="0 0 100 220" xmlns="http://www.w3.org/2000/svg">
            <path d="M50 6 L72 64 L28 64 Z" fill="#f02865"/>
            <rect x="28" y="64" width="44" height="96" rx="14" fill="#f1f4f7" stroke="#c3cbd4" stroke-width="2"/>
            <rect x="28" y="104" width="44" height="10" fill="#2dd4e3"/>
            <circle cx="50" cy="86" r="11" fill="#1d1e24"/>
            <circle cx="50" cy="86" r="8" fill="#2dd4e3"/>
            <path d="M28 150 L4 188 L28 172 Z" fill="#f02865"/>
            <path d="M72 150 L96 188 L72 172 Z" fill="#f02865"/>
            <g class="rocket-flame">
                <path d="M38 160 C 36 178, 42 192, 50 200 C 58 192, 64 178, 62 160 Z" fill="#ffb020"/>
                <path d="M44 160 C 43 172, 46 182, 50 188 C 54 182, 57 172, 56 160 Z" fill="#fff06b"/>
            </g>
        </svg>
    </div>
</div>"""

html_scene_new = """<div class="space-scene">
    <?= $stars_html; ?>
</div>"""

content = content.replace(html_scene_old, html_scene_new)

# 5. Remove Javascript for parallax, clicks for astronaut and rocket
js_old_pattern = re.compile(r'    // Astronot & roket: parallax mengikuti cursor \+ interaksi klik\n.*?    \}\)\(\);\n', re.DOTALL)
content = re.sub(js_old_pattern, '', content)

# 6. Update prefers-reduced-motion to remove astronaut/rocket animations
content = content.replace('.star, .astronaut-float, .rocket-float, .rocket-flame {', '.star {')

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print("Done.")
