import urllib.request
import urllib.parse
import ssl
import json
import re

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

def test_page(url, name):
    print(f"\n--- Testing {name}: {url} ---")
    try:
        req = urllib.request.Request(url, headers={'User-Agent': 'NyitottMultTester/1.0'})
        with urllib.request.urlopen(req, context=ctx, timeout=15) as resp:
            content = resp.read().decode('utf-8')
            print(f"Status: {resp.status}, Length: {len(content)} bytes")
            return content
    except Exception as e:
        print(f"Error loading {name}: {e}")
        return ""

# 1. Documents list
doc_html = test_page("https://nyitottmult.kataiattila.hu/dokumentumok", "Dokumentumok")
abtl_count = doc_html.count("ÁBTL")
print(f"'ÁBTL' occurrences in /dokumentumok: {abtl_count}")

# 2. Search test
q = urllib.parse.quote("ADÁSZ")
search_html = test_page(f"https://nyitottmult.kataiattila.hu/kereses?q={q}", "Keresés: ADÁSZ")
print("Found 'ADÁSZ' in search:", "ADÁSZ" in search_html)
print("Found 'KAPCSÁNDI' in search:", "KAPCSÁNDI" in search_html)

# 3. AI chat test
print("\n--- Testing AI Historian API ---")
payload = json.dumps({"question": "Ki volt ADÁSZ ZOLTÁN és mi volt a fedőneve?"}).encode('utf-8')
req = urllib.request.Request(
    "https://nyitottmult.kataiattila.hu/api/v1/ai/ask",
    data=payload,
    headers={'Content-Type': 'application/json', 'User-Agent': 'NyitottMultTester/1.0'}
)
try:
    with urllib.request.urlopen(req, context=ctx, timeout=20) as resp:
        res = json.loads(resp.read().decode('utf-8'))
        print("AI Response status:", resp.status)
        print("AI answer:\n", res.get("answer", ""))
        print("AI sources count:", len(res.get("sources", [])))
        if res.get("sources"):
            print("First source code:", res["sources"][0].get("archive_code"))
            print("First source title:", res["sources"][0].get("title"))
except Exception as e:
    print(f"AI query error: {e}")
