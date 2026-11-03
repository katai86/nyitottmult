# Hozzájárulási Útmutató (Contributing Guide)

Köszönjük érdeklődését a **Nyitott Múlt** projekt iránt! A projekt célja a tudományos igényességű, forrásalapú és etikus helytörténeti kutatás támogatása.

## 📜 Developer Certificate of Origin (DCO 1.1)

Minden hozzájárulásnak meg kell felelnie a Developer Certificate of Origin előírásainak. A commit üzenetben használt `Signed-off-by: Név <email>` sor igazolja a szerzői jogi jogosultságot.

```text
Developer's Certificate of Origin 1.1

By making a contribution to this project, I certify that:

(a) The contribution was created in whole or in part by me and I
    have the right to submit it under the open source license
    indicated in the file; or

(b) The contribution is based upon previous work that, to the best
    of my knowledge, is covered under an appropriate open source
    license and I have the right under that license to submit that
    work with modifications, whether created in whole or in part
    by me, under the same open source license; or

(c) The contribution was provided directly to me by some other
    person who certified (a), (b) or (c) and I have not modified
    it.

(d) I understand and agree that this project and the contribution
    are public and that a record of the contribution (including all
    personal information I submit with it, including my sign-off) is
    maintained indefinitely and may be redistributed consistent with
    this project or the open source license(s) involved.
```

## ⚠️ Szigorú Adatvédelmi Szabály a Repóban

1. **SOHA ne commitoljon valós személyes adatot vagy levéltári iratot a nyílt repóba!**
2. Minden teszt- és seed adatnak fiktívnek kell lennie, és egyértelműen viselnie kell a `MINTA` (Sample) jelölést (pl. `MINTA Kovács János`, `MINTA-1959-KARTON`).
3. Tartsuk tiszteletben az információs önrendelkezési jogot és a 2026. évi LI. törvény előírásait.

## 🛠️ Fejlesztői Környezet és Kódolási Irányelvek

- **Backend:** PHP 8.1 / 8.2+, PSR-12 kódformázási szabályok, típusbiztonság (`declare(strict_types=1);`).
- **Frontend:** Szemantikus HTML5, modern Tailwind CSS, reszponzivitás, akadálymentesség (a11y).
- **Többnyelvűség (i18n):** Minden felhasználói felületi szöveg a `public_html/locales/hu.json` és `en.json` fájlokon keresztül kerüljön megjelenítésre.
- **Biztonság:** Minden SQL lekérdezés kizárólag PDO előkészített utasítással (prepared statement) futhat; minden kimenet XSS ellen védve (`htmlspecialchars`).
