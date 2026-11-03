<?php
declare(strict_types=1);
$pageTitle = __('nav.api_docs');
include __DIR__ . '/layout/header.php';
?>

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">
        ⚡ Nyílt REST API & Adat-Export
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">
        Gépileg olvasható, nyílt hozzáférésű OpenAPI 3.1 kompatibilis végpontok és letölthető adatkészletek (JSON, CSV, GeoJSON).
    </p>
</div>

<!-- Közvetlen Export Letöltések -->
<div class="card" style="margin-bottom: 2.5rem; background: #f8fafc;">
    <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--primary); margin-bottom: 1rem;">
        💾 Nyílt Adatkészletek Letöltése (Open Data)
    </h2>
    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
        <a href="/api/v1/export/documents.json" target="_blank" class="btn btn-secondary">
            📦 Iratok és Források (.json)
        </a>
        <a href="/api/v1/export/documents.csv" target="_blank" class="btn btn-secondary">
            📊 Iratok és Források (.csv)
        </a>
        <a href="/api/v1/export/places.geojson" target="_blank" class="btn btn-secondary">
            🗺️ Térképi Helyszínek (.geojson)
        </a>
        <a href="/api/v1/export/claims.json" target="_blank" class="btn btn-secondary">
            ⚖️ Ellenőrzött Állítások (.json)
        </a>
    </div>
</div>

<!-- API Végpontok Leírása -->
<div class="card" style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="font-size: 1.25rem; font-weight: 700; color: #0f172a;">
            REST API Végpontok (v1)
        </h2>
        <a href="/api/v1/openapi.json" target="_blank" class="btn btn-secondary" style="font-size: 0.8rem;">
            OpenAPI 3.1 Séma (.json) &rarr;
        </a>
    </div>

    <div style="display: flex; flex-direction: column; gap: 1rem; font-size: 0.9rem;">
        <div style="border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem;">
            <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.25rem;">
                <span class="badge" style="background: #dcfce7; color: #166534; font-weight: bold;">GET</span>
                <code style="font-weight: 600; color: var(--primary);">/api/v1/documents</code>
            </div>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                A feltöltött levéltári iratok listája, oldalszámmal és jelzettel.
            </p>
        </div>

        <div style="border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem;">
            <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.25rem;">
                <span class="badge" style="background: #dcfce7; color: #166534; font-weight: bold;">GET</span>
                <code style="font-weight: 600; color: var(--primary);">/api/v1/entities</code>
            </div>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                A feltárt személyek és szervezetek listája (a védett és élő személyek automatikusan anonimizálva a 2026. évi LI. tv. alapján).
            </p>
        </div>

        <div style="border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem;">
            <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.25rem;">
                <span class="badge" style="background: #dcfce7; color: #166534; font-weight: bold;">GET</span>
                <code style="font-weight: 600; color: var(--primary);">/api/v1/map/places</code>
            </div>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Helyszínek GeoJSON formátumban (magánlakások koordinátája városrész-szintre elmosva).
            </p>
        </div>

        <div style="border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem;">
            <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.25rem;">
                <span class="badge" style="background: #e0f2fe; color: #0369a1; font-weight: bold;">POST</span>
                <code style="font-weight: 600; color: var(--primary);">/api/v1/ai/ask</code>
            </div>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Forrásalapú AI kérdezz-felelek modul. Bemenet: <code>{"question": "..."}</code>. Válasz kötelező forráshivatkozásokkal.
            </p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
