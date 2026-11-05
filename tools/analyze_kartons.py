import csv

csv_path = "C:/Users/Kátai Attila/.gemini/antigravity/brain/518befa3-3ed7-4757-a3ff-c71ae0aa3629/.user_uploaded/media_1793793396021_a11cf9c6.csv"

with open(csv_path, "r", encoding="utf-8") as f:
    reader = csv.DictReader(f)
    rows = list(reader)

print("Total rows:", len(rows))

kartons = [r for r in rows if r.get('Irattípus') == '6-os karton']
magnes = [r for r in rows if r.get('Irattípus') == 'Mágnesszalag']

print(f"6-os kartonok száma: {len(kartons)}")
print(f"Mágnesszalagok száma: {len(magnes)}")

print("\nPélda 6-os karton linkek:")
for k in kartons[:5]:
    print(" -", k['Név'], "->", k['Link'])
