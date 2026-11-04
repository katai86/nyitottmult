/**
 * Nyitott Múlt – Interaktív Időutazó Térkép (MapLibre GL JS + OSM)
 */

document.addEventListener('DOMContentLoaded', function () {
    const config = window.siteMapConfig || {
        center: [20.6680, 46.5650],
        zoom: 13,
        tileUrl: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'
    };

    // Térkép konténer ellenőrzése
    const mapEl = document.getElementById('map-container');
    if (!mapEl) return;

    // Megbízható nyílt OpenStreetMap raster csempék (mindig működnek böngészőben, kulcs nélkül)
    const map = new maplibregl.Map({
        container: 'map-container',
        style: {
            version: 8,
            sources: {
                'osm-raster': {
                    type: 'raster',
                    tiles: [
                        'https://tile.openstreetmap.org/{z}/{x}/{y}.png'
                    ],
                    tileSize: 256,
                    attribution: '© <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> közreműködők'
                }
            },
            layers: [
                {
                    id: 'osm-tiles',
                    type: 'raster',
                    source: 'osm-raster',
                    minzoom: 0,
                    maxzoom: 19
                }
            ]
        },
        center: config.center,
        zoom: config.zoom
    });

    // Navigációs gombok (Zoom + / -)
    map.addControl(new maplibregl.NavigationControl(), 'top-right');

    const allMarkers = [];

    map.on('load', function () {
        // 1. Városrészek megjelenítése (Belváros, Rákóczitelep, Szentetornya, Gyopárosfürdő, Kiscsákó, Gyökeres, Bonum stb.)
        if (config.districts && config.districts.length > 0) {
            config.districts.forEach(d => {
                const el = document.createElement('div');
                el.className = 'map-marker-district';
                el.style.cursor = 'pointer';
                el.innerHTML = `<span style="background: rgba(30, 58, 138, 0.92); color: #fff; padding: 4px 9px; border-radius: 6px; font-size: 11px; font-weight: 700; border: 1px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.3); display: inline-block; white-space: nowrap;">🏘️ ${escapeHtml(d.name)}</span>`;

                el.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const hist = (d.historical_names || []).join(', ');
                    const meta = `Típus: Városrész / Terület` + (hist ? `<br>Korabeli nevek: <strong>${escapeHtml(hist)}</strong>` : '');
                    showInfoPanel(d.name, d.description, meta, 'districts');
                    map.flyTo({ center: d.center, zoom: Math.max(map.getZoom(), 13.5), speed: 0.8 });
                });

                const m = new maplibregl.Marker({ element: el })
                    .setLngLat(d.center)
                    .addTo(map);

                allMarkers.push({ type: 'districts', marker: m, data: d });
            });
        }

        // 2. Üzemek és intézmények (Síküveggyár, Mezőgép, Ruhagyár, Táncsics Gimnázium, Evangélikus Nagytemplom, Baromfifeldolgozó)
        if (config.organizations && config.organizations.length > 0) {
            config.organizations.forEach(o => {
                const el = document.createElement('div');
                el.className = 'map-marker-org';
                el.style.cursor = 'pointer';
                el.innerHTML = `<span style="background: #0284c7; color: #fff; padding: 4px 9px; border-radius: 9999px; font-size: 11px; font-weight: 700; border: 1px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.3); display: inline-block; white-space: nowrap;">🏭 ${escapeHtml(o.name)}</span>`;

                el.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const hist = (o.aliases || []).join(', ');
                    const meta = `Alapítva: <strong>${o.founded || 'N/A'}</strong>` + (hist ? `<br>Korabeli elnevezések: <em>${escapeHtml(hist)}</em>` : '');
                    showInfoPanel(o.name, o.description, meta, 'orgs');
                    map.flyTo({ center: o.location, zoom: Math.max(map.getZoom(), 14.5), speed: 0.8 });
                });

                const m = new maplibregl.Marker({ element: el })
                    .setLngLat(o.location)
                    .addTo(map);

                allMarkers.push({ type: 'orgs', marker: m, data: o });
            });
        }

        // 3. Állambiztonsági egységek és konspirált pontok a névtárból (PNK, Presszó, Ipari rezidentúra)
        if (config.stateSecurity && config.stateSecurity.length > 0) {
            config.stateSecurity.forEach(s => {
                const el = document.createElement('div');
                el.className = 'map-marker-security';
                el.style.cursor = 'pointer';
                el.innerHTML = `<span style="background: #b91c1c; color: #fff; padding: 4px 9px; border-radius: 9999px; font-size: 11px; font-weight: 700; border: 1px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.35); display: inline-block; white-space: nowrap;">🛡️ ${escapeHtml(s.short_name || s.name)}</span>`;

                el.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const meta = `Operatív időszak: <strong>${escapeHtml(s.period || '1956–1990')}</strong><br>Minősítés: <em>Kiemelt állambiztonsági / operatív helyszín</em>`;
                    showInfoPanel(s.name, s.description, meta, 'security');
                    map.flyTo({ center: s.location, zoom: Math.max(map.getZoom(), 15), speed: 0.8 });
                });

                const m = new maplibregl.Marker({ element: el })
                    .setLngLat(s.location)
                    .addTo(map);

                allMarkers.push({ type: 'security', marker: m, data: s });
            });
        }

        // 4. Állambiztonsági és levéltári helyszínek betöltése az adatbázisból (REST API)
        fetch('/api/v1/map/places')
            .then(res => res.json())
            .then(data => {
                if (data.features && data.features.length > 0) {
                    data.features.forEach(f => {
                        const props = f.properties;
                        const coords = f.geometry.coordinates;

                        const el = document.createElement('div');
                        el.className = 'map-marker-security';
                        el.style.cursor = 'pointer';

                        const isBlurred = props.is_blurred;
                        const icon = isBlurred ? '🛡️' : '📍';
                        const bgColor = isBlurred ? '#b45309' : '#b91c1c';

                        el.innerHTML = `<span style="background: ${bgColor}; color: #fff; padding: 4px 9px; border-radius: 9999px; font-size: 11px; font-weight: 700; border: 1px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.3); display: inline-block; white-space: nowrap;">${icon} ${escapeHtml(props.name)}</span>`;

                        el.addEventListener('click', (e) => {
                            e.stopPropagation();
                            let note = props.address_text || '';
                            if (isBlurred) {
                                note += `<br><span style="display:inline-block; margin-top: 6px; padding: 4px 8px; background: #fef3c7; color: #92400e; border-radius: 4px; font-weight: bold; font-size: 0.8rem;">⚠️ 2026. évi LI. tv.: Magánlakás pontos koordinátája elmosva városrész-szintre!</span>`;
                            }
                            const meta = `Típus: ${props.precision_level || 'Levéltári helyszín'} | Körzet: ${props.district_id || 'Orosháza'}`;
                            showInfoPanel(props.name, note, meta, 'security');
                            map.flyTo({ center: coords, zoom: Math.max(map.getZoom(), 14.5), speed: 0.8 });
                        });

                        const m = new maplibregl.Marker({ element: el })
                            .setLngLat(coords)
                            .addTo(map);

                        allMarkers.push({ type: 'security', marker: m, data: props });
                    });
                }
            })
            .catch(err => console.log('Nem található további dinamikus helyszín:', err));

        // Kezdő év szűrése
        applyYearFilter(1960);
    });

    // Időcsúszka kezelése (1945 - 1990)
    const yearSlider = document.getElementById('yearSlider');
    const yearDisplay = document.getElementById('currentYearDisplay');

    if (yearSlider && yearDisplay) {
        yearSlider.addEventListener('input', function () {
            const year = parseInt(this.value, 10);
            yearDisplay.textContent = year;
            applyYearFilter(year);
        });
    }

    // Évszám szerinti szűrés és korabeli utcanév frissítés
    function applyYearFilter(year) {
        // 1. Üzemek időbeli állapota
        allMarkers.filter(m => m.type === 'orgs').forEach(m => {
            const founded = m.data.founded || 0;
            const closed = m.data.closed || 9999;
            const el = m.marker.getElement();
            if (el) {
                if (year < founded) {
                    el.style.opacity = '0.35';
                    el.title = `${m.data.name} (Még nem létezett ${year}-ban, alapítva: ${founded})`;
                } else if (year > closed) {
                    el.style.opacity = '0.35';
                    el.title = `${m.data.name} (Megszűnt ${closed}-ben)`;
                } else {
                    el.style.opacity = '1';
                    el.title = `${m.data.name} (Aktív üzem ${year}-ban)`;
                }
            }
        });

        // 2. Korabeli utcanevek megjelenítése az info panel feletti sávban
        if (config.streetHistory && config.streetHistory.length > 0) {
            const changes = [];
            config.streetHistory.forEach(s => {
                if (s.history) {
                    const match = s.history.find(h => {
                        const from = h.valid_from || 0;
                        const to = h.valid_to || 9999;
                        return year >= from && year <= to;
                    });
                    if (match && match.name !== s.current_name) {
                        changes.push(`<strong>${escapeHtml(match.name)}</strong> (ma: ${escapeHtml(s.current_name)})`);
                    }
                }
            });

            const streetBox = document.getElementById('street-history-box');
            if (streetBox) {
                if (changes.length > 0) {
                    streetBox.innerHTML = `⏳ <strong>${year}-as évek korabeli elnevezései:</strong> ` + changes.join(' • ');
                    streetBox.style.display = 'block';
                } else {
                    streetBox.style.display = 'none';
                }
            }
        }
    }

    // Rétegek ki/be kapcsolása
    function setupToggle(toggleId, typeKey) {
        const toggle = document.getElementById(toggleId);
        if (toggle) {
            toggle.addEventListener('change', function () {
                const isChecked = this.checked;
                allMarkers.filter(m => m.type === typeKey).forEach(m => {
                    const el = m.marker.getElement();
                    if (el) el.style.display = isChecked ? 'block' : 'none';
                });
            });
        }
    }

    setupToggle('toggleOrgs', 'orgs');
    setupToggle('toggleDistricts', 'districts');
    setupToggle('toggleSecurity', 'security');

    // Információs panel frissítése
    function showInfoPanel(title, description, meta, typeKey) {
        const titleEl = document.getElementById('info-title');
        const descEl = document.getElementById('info-content');
        const claimsEl = document.getElementById('info-claims');

        let badgeHtml = '';
        if (typeKey === 'districts') {
            badgeHtml = '<span class="badge" style="background: #dbeafe; color: #1e40af; margin-bottom: 0.5rem; display: inline-block;">🏘️ Városrész / Terület</span>';
        } else if (typeKey === 'orgs') {
            badgeHtml = '<span class="badge" style="background: #e0f2fe; color: #0369a1; margin-bottom: 0.5rem; display: inline-block;">🏭 Gyár / Közintézmény</span>';
        } else if (typeKey === 'security') {
            badgeHtml = '<span class="badge" style="background: #fee2e2; color: #991b1b; margin-bottom: 0.5rem; display: inline-block;">🛡️ Állambiztonsági / Operatív Pont</span>';
        }

        if (titleEl) titleEl.innerHTML = badgeHtml + '<div style="font-size: 1.25rem; font-weight: 800; color: #0f172a;">' + escapeHtml(title) + '</div>';
        if (descEl) descEl.innerHTML = '<div style="font-size: 0.9rem; color: #334155; line-height: 1.6; margin-bottom: 0.5rem;">' + description + '</div>' + 
            (meta ? `<div style="margin-top: 0.75rem; font-size: 0.825rem; color: #64748b; line-height: 1.5; border-top: 1px dashed #cbd5e1; padding-top: 0.5rem; background: #f8fafc; padding: 0.5rem; border-radius: 4px;">${meta}</div>` : '');
        
        if (claimsEl) {
            claimsEl.innerHTML = `
                <div style="border-top: 1px solid var(--border); padding-top: 0.75rem; margin-top: 0.75rem;">
                    <a href="/kereses?q=${encodeURIComponent(title)}" class="btn btn-primary" style="width: 100%; font-size: 0.85rem; text-align: center; display: block;">
                        🔍 Kapcsolódó levéltári iratok keresése &rarr;
                    </a>
                </div>
            `;
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});
