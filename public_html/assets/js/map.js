/**
 * Nyitott Múlt – Interaktív Időutazó Térkép (MapLibre GL JS + OSM / CartoDB)
 */

document.addEventListener('DOMContentLoaded', function () {
    const config = window.siteMapConfig || {
        center: [20.6634, 46.5658],
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
        // 1. Városrészek megjelenítése (pl. Belváros, Szentetornya, Rákóczitelep, Gyökeres, Gyopárosfürdő, Kiscsákó)
        if (config.districts && config.districts.length > 0) {
            config.districts.forEach(d => {
                const el = document.createElement('div');
                el.className = 'map-marker-district';
                el.style.cursor = 'pointer';
                el.innerHTML = `<span style="background: rgba(30, 58, 138, 0.9); color: #fff; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; border: 1px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.3); display: inline-block;">🏘️ ${escapeHtml(d.name)}</span>`;

                el.addEventListener('click', () => {
                    showInfoPanel(d.name, d.description, `Városrész azonosító: ${d.id}`);
                });

                const m = new maplibregl.Marker({ element: el })
                    .setLngLat(d.center)
                    .addTo(map);

                allMarkers.push({ type: 'districts', marker: m, data: d });
            });
        }

        // 2. Üzemek és intézmények (Síküveggyár, Ruhagyár, Mezőgép, Táncsics Gimnázium, Evangélikus Egyház)
        if (config.organizations && config.organizations.length > 0) {
            config.organizations.forEach(o => {
                const el = document.createElement('div');
                el.className = 'map-marker-org';
                el.style.cursor = 'pointer';
                el.innerHTML = `<span style="background: #0284c7; color: #fff; padding: 4px 8px; border-radius: 9999px; font-size: 11px; font-weight: 700; border: 1px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.25); display: inline-block;">🏭 ${escapeHtml(o.name)}</span>`;

                el.addEventListener('click', () => {
                    const meta = `Alapítva: ${o.founded || 'N/A'} | Névváltozatok: ${(o.aliases || []).join(', ')}`;
                    showInfoPanel(o.name, o.description, meta);
                });

                const m = new maplibregl.Marker({ element: el })
                    .setLngLat(o.location)
                    .addTo(map);

                allMarkers.push({ type: 'orgs', marker: m, data: o });
            });
        }

        // 3. Állambiztonsági és levéltári helyszínek betöltése az adatbázisból (REST API)
        fetch('/api/v1/map/places')
            .then(res => res.json())
            .then(data => {
                if (data.features) {
                    data.features.forEach(f => {
                        const props = f.properties;
                        const coords = f.geometry.coordinates;

                        const el = document.createElement('div');
                        el.className = 'map-marker-security';
                        el.style.cursor = 'pointer';

                        const isBlurred = props.is_blurred;
                        const icon = isBlurred ? '🛡️' : '📍';
                        const bgColor = isBlurred ? '#b45309' : '#b91c1c';

                        el.innerHTML = `<span style="background: ${bgColor}; color: #fff; padding: 4px 8px; border-radius: 9999px; font-size: 11px; font-weight: 700; border: 1px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.3); display: inline-block;">${icon} ${escapeHtml(props.name)}</span>`;

                        el.addEventListener('click', () => {
                            let note = props.address_text || '';
                            if (isBlurred) {
                                note += `<br><span style="display:inline-block; margin-top: 6px; padding: 4px 8px; background: #fef3c7; color: #92400e; border-radius: 4px; font-weight: bold; font-size: 0.8rem;">⚠️ 2026. évi LI. tv.: Magánlakás pontos koordinátája elmosva városrész-szintre!</span>`;
                            }
                            showInfoPanel(props.name, note, `Típus: ${props.precision_level || 'Helyszín'} | Körzet: ${props.district_id || 'Orosháza'}`);
                        });

                        const m = new maplibregl.Marker({ element: el })
                            .setLngLat(coords)
                            .addTo(map);

                        allMarkers.push({ type: 'security', marker: m, data: props });
                    });
                }
            })
            .catch(err => console.log('Nem sikerült betölteni a dinamikus helyszíneket:', err));

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
                    // Még nem épült fel az adott évben: halványítjuk
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

        // 2. Korabeli utcanevek megjelenítése az info panelben
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
                    streetBox.innerHTML = `⏳ <strong>${year}-as évek elnevezései:</strong> ` + changes.join(', ');
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
    function showInfoPanel(title, description, meta) {
        const titleEl = document.getElementById('info-title');
        const descEl = document.getElementById('info-content');
        const claimsEl = document.getElementById('info-claims');

        if (titleEl) titleEl.textContent = title;
        if (descEl) descEl.innerHTML = description + (meta ? `<div style="margin-top: 0.75rem; font-size: 0.8rem; color: #64748b; line-height: 1.5; border-top: 1px dashed #cbd5e1; padding-top: 0.5rem;">${meta}</div>` : '');
        if (claimsEl) {
            claimsEl.innerHTML = `
                <div style="border-top: 1px solid var(--border); padding-top: 0.75rem; margin-top: 0.75rem;">
                    <a href="/kereses?q=${encodeURIComponent(title)}" class="btn btn-primary" style="width: 100%; font-size: 0.85rem; text-align: center; display: block;">
                        🔍 Kapcsolódó levéltári iratok keresése
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
