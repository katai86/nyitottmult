import urllib.request
import ssl
import json

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

urls = [
    ('/', 200, 'Orosháza titkos múltja'),
    ('/terkep', 200, 'Időutazó Térkép'),
    ('/dokumentumok', 200, 'Dokumentumtár'),
    ('/bizonyitekterkep', 200, 'Bizonyítéktérkép'),
    ('/kapcsolati-halo', 200, 'Kapcsolati Háló'),
    ('/idovonal', 200, 'Idővonal'),
    ('/ai-tortenesz', 200, 'AI Történész'),
    ('/tortenetek', 200, 'Helytörténeti Történetek'),
    ('/kozosseg', 200, 'Közösségi Kutatás'),
    ('/helyesbites', 200, 'Helyesbítés'),
    ('/atlathatosag', 200, 'Átláthatóság'),
    ('/api-dokumentacio', 200, 'API'),
    ('/kutato/belepes', 200, 'Kutatói Belépés'),
    ('/api/v1/documents', 200, 'MINTA'),
    ('/api/v1/entities', 200, 'Kovács'),
    ('/api/v1/map-layers', 200, 'features'),
]

base = 'https://nyitottmult.kataiattila.hu'
all_ok = True
for path, expected_code, expected_snippet in urls:
    try:
        req = urllib.request.Request(base + path, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req, context=ctx, timeout=10) as resp:
            content = resp.read().decode('utf-8', errors='replace')
            status = resp.status
            has_snippet = expected_snippet.lower() in content.lower()
            if status == expected_code and has_snippet:
                print(f'OK   {path} -> {status} (found "{expected_snippet}")')
            else:
                print(f'FAIL {path} -> {status} (snippet "{expected_snippet}" found: {has_snippet})')
                all_ok = False
    except Exception as e:
        print(f'ERR  {path} -> {e}')
        all_ok = False

if all_ok:
    print('\nALL ENDPOINTS RESPONDED PERFECTLY!')
else:
    print('\nSOME ENDPOINTS HAD ISSUES!')
