#!/usr/bin/env python3
"""
Minta archív iratképek generálása a demóhoz (OpenSeadragon iratnéző teszteléséhez)
"""

from pathlib import Path
from PIL import Image, ImageDraw, ImageFont

BASE_DIR = Path(__file__).resolve().parent.parent
OUTPUT_DIR = BASE_DIR / "public_html" / "assets" / "sample_docs"
OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

def create_vintage_page(filename, thumb_name, title, lines, stamp_text="MINTA LEVÉLTÁRI MÁSOLAT"):
    w, h = 1200, 1600
    # Korabeli, enyhén sárgás papírszín
    img = Image.new("RGB", (w, h), color=(245, 241, 230))
    draw = ImageDraw.Draw(img)

    # Korabeli írógép jellegű keret
    draw.rectangle([50, 50, w - 50, h - 50], outline=(180, 170, 150), width=2)
    draw.rectangle([54, 54, w - 54, h - 54], outline=(200, 190, 170), width=1)

    # Címsor
    draw.text((80, 80), title, fill=(30, 30, 30))

    # Szöveg sorok
    y = 160
    for line in lines:
        draw.text((80, y), line, fill=(45, 45, 45))
        y += 40

    # Piros levéltári "MINTA" bélyegző
    draw.rectangle([w - 480, h - 220, w - 80, h - 120], outline=(190, 40, 40), width=4)
    draw.text((w - 460, h - 185), stamp_text, fill=(190, 40, 40))

    # Mentés WebP és JPEG formátumban
    img.save(OUTPUT_DIR / filename, format="WEBP", quality=85)
    
    # Thumbnail
    thumb = img.resize((300, 400), Image.Resampling.LANCZOS)
    thumb.save(OUTPUT_DIR / thumb_name, format="WEBP", quality=80)
    print(f"Létrehozva: {filename} és {thumb_name}")

def main():
    # 1. 6-os karton minta
    create_vintage_page(
        "karton_sample_p1.webp",
        "karton_sample_p1_thumb.webp",
        "ÁLLAMBIZTONSÁGI NYILVÁNTARTÓ KARTON (6-os)\nOrosházi Politikai Nyomozó Kirendeltség (1959)",
        [
            "Név: MINTA Kovács János",
            "Születési hely, idő: Orosháza, 1925. március 10.",
            "Anyja neve: MINTA Kis Julianna",
            "Foglalkozása: géplakatos, Orosházi Mezőgép Vállalat",
            "Fedőneve: \"Homok\"",
            "Beszervezés ideje: 1959. április 12.",
            "Beszervező tiszt: MINTA Tóth Béla r. fhdgy. (Orosházi PNK)",
            "Beszervezés alapja: hazafias alapon",
            "Foglalkoztatási vonal: belső reakció / mezőgazdasági szektor, Orosháza és Gyökeres térsége.",
            "--------------------------------------------------------------------------------",
            "Karton nyilvántartási szám: 6-1959/OR-042",
            "Irat másolat készült: 2026. évi LI. tv. alapján (nyitottaktak.hu)"
        ],
        "MINTA 6-OS KARTON"
    )

    # 2. M-dosszié jelentés minta
    create_vintage_page(
        "m_dosszie_p14.webp",
        "m_dosszie_p14_thumb.webp",
        "JELENTÉS – Munka-dosszié (14. oldal)\nOrosháza, 1960. szeptember 20.",
        [
            "Vette: MINTA Tóth Béla r. százados",
            "Adta: \"Homok\" fn. informátor",
            "Találkozás helye: Orosháza, Beloiannisz utcai 'K' lakás",
            "",
            "Jelentem, hogy 1960. szeptember 18-án este találkoztam MINTA Dr. Szabó István",
            "gimnáziumi tanárral a Szabadság téri presszóban.",
            "Szabó tanár úr az 1956-os eseményekről és a helyi egyházi ifjúsági körökről",
            "beszélt, kifejezve aggodalmát a kollektivizálás orosházi kényszermódszerei miatt.",
            "Felesége, MINTA Kis Éva nem vett részt a beszélgetésben.",
            "",
            "Intézkedés:",
            "A jelentést ellenőrzésre átadom az orosházi egyházügyi vonalnak.",
            "Tóth Béla r. szds."
        ],
        "MINTA JELENTÉS (ÁBTL)"
    )

    # 3. Sztori borító
    cover = Image.new("RGB", (800, 500), color=(30, 58, 138))
    draw = ImageDraw.Draw(cover)
    draw.rectangle([20, 20, 780, 480], outline=(255, 255, 255), width=2)
    draw.text((60, 200), "OROSHÁZA TITKOS MÚLTJA", fill=(255, 255, 255))
    draw.text((60, 260), "Helytörténeti Esettanulmány (1960)", fill=(186, 230, 253))
    cover.save(OUTPUT_DIR / "story_cover.webp", format="WEBP", quality=85)
    print("Létrehozva: story_cover.webp")

if __name__ == "__main__":
    main()
