# Nyitott Múlt – Adatvédelmi és Kutatásetikai Irányelvek

## 1. Jogi Háttér: A 2026. évi LI. törvény 14. §-a

A 2026. évi LI. törvény alapján az Állambiztonsági Szolgálatok Történeti Levéltára (ÁBTL) 2026. november 4-től teszi közzé a digitális iratokat a *nyitottaktak.hu* portálon.

A **Nyitott Múlt** platform célja ezen iratok települési szintű, forrásalapú és kontextualizált kutatása, a lehető legszigorúbb személyiségi jogi és etikai garanciák mellett.

---

## 2. A Halál Éve + 5 Év Szabály (Privacy Engine)

Az információs önrendelkezési jog és a levéltári törvény értelmében nem anonimizált személyes adat kizárólag az alábbi feltétel teljesülésekor tehető közzé nyilvánosan:

$$\text{Közzétehetőség éve} = \text{Halál éve} + 5 \le \text{Aktuális naptári év}$$

### Szabályok:
1. **Élő személyek:** Nyilvános felületen SOHA nem jelennek meg. A rendszer automatikusan anonimizálja a nevet: `[Védett személy – 2026. évi LI. tv.]`.
2. **Ismeretlen élethelyzetű személyek:** Amennyiben a kutatók nem rendelkeznek hiteles halotti anyakönyvi kivonattal vagy levéltári elhalálozási dátummal, a személy védettnek minősül.
3. **Kettős jóváhagyás:** Egy személy nyilvános státusza csak két független lektor ellenőrzése után aktiválható.

---

## 3. Magánlakások és Térbeli Adatok Védelme

- **Középületek és találkozási helyek:** A volt állambiztonsági kirendeltségek (pl. Orosházi PNK), gyárak, iskolák, presszók pontos koordinátái megjelennek a térképen.
- **Magánlakások:** Ha egy személy magánlakása vagy háza szerepel az iratban, a rendszer a PostGIS / MySQL Spatial segítségével **automatikusan elmosást végez**:
  - A pontos épület-koordináta rejtett marad.
  - A nyilvános térképen a lakás a városrész közepeként (centroid) vagy utcaszintű poligonként jelenik meg.
  - A cím szövege: `[Magánlakás – a pontos cím és koordináta a törvény alapján városrész-szintre elmosva]`.

---

## 4. Szerepkörök Forráskritikai Elhatárolása

Az állambiztonsági iratokban való szereplés **önmagában SOHA nem jelent ügynöki múltat**.

A platformon minden személy mellett jól látható színkóddal jelölt szerepkör szerepel:
- 🔴 **Beszervezett hálózati személy:** Csak akkor alkalmazható, ha a 6-os karton, a beszervezési nyilatkozat vagy a B-napló hitelesen igazolja.
- 🔵 **Megfigyelt személy:** Áldozat, akinek alapvető jogait megsértették. A társadalom felé egyértelművé kell tenni, hogy ő a megfigyelés elszenvedője volt.
- 🟠 **Operatív tiszt / Tartótiszt:** Az állambiztonság hivatásos alkalmazottja.
- ⚪ **Egyéb említett személy:** Családtag, kolléga, szomszéd, aki pusztán a környezet leírásában fordul elő.

---

## 5. Helyesbítési Jog és Kiegészítő Nyilatkozat

Bármely érintett vagy közvetlen leszármazott jogosult:
1. Ténybeli tévedés javítását kérni a `/helyesbites` űrlapon.
2. **Kiegészítő nyilatkozatot** tenni, amelyet a kutatócsoport vizsgálat után az érintett adatlapján elválaszthatatlanul, kiemelt helyen köteles megjelentetni.
3. A törvényi vizsgálati határidő: **legfeljebb 30 nap**.
