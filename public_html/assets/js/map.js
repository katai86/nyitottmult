/**
 * Nyitott Múlt – Interaktív Időutazó Térkép (MapLibre GL JS)
 */

document.addEventListener('DOMContentLoaded', function () {
    const config = window.siteMapConfig || {
        center: [20.6634, 46.5658],
        zoom: 13,
        tileUrl: 'https://tiles.openfreemap.org/styles/bright/{z}/{x}/{y}.png'
    };

    // MapLibre térkép inicializálása
    const map = new maplibregl.Map({
        container: 'map-container',
        style: {
            version: 8,
            sources: {
                'openfreemap': {
                    type: 'raster',
                    tiles: [config.tileUrl],
                    tileSize: 256,
                    attribution: '© OpenStreetMap contributors, OpenFreeMap'
                }
            },
            layers: [
                {
                    id: 'osm-tiles',
                    type: 'raster',
                    source: 'openfreemap',
                    minzoom: 0,
                    maxzoom: 19
                }
            ]
        },
        center: config.center,
        zoom: config.zoom
    });

    map.addControl(new maplibregl.NavigationControl(), 'top-right');

    const markers = [];

    // Helyszín adatok betöltése
    map.on('load', function () {
        // 1. Városrészek megjelenítése
        if (config.districts && config.districts.length > 0) {
            config.districts.forEach(d => {
                const el = document.createElement('div');
                el.className = 'map-marker-district';
                el.innerHTML = `<span style="background: rgba(30, 58, 138, 0.85); color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; border: 1px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.2); cursor: pointer;">🏘️ ${d.name}</span>`;
                
                el.addEventListener('click', () => {
                    showInfoPanel(d.name, d.description, `Városrész azonosító: ${d.id}`);
                });

                const m = new maplibregl.Marker({ element: el })
                    .setLngLat(d.center)
                    .addTo(map);
                markers.push({ type: 'districts', marker: m });
            });
        }

        // 2. Üzemek és intézmények
        if (config.organizations && config.organizations.length > 0) {
            config.organizations.forEach(o => {
                const el = document.createElement('div');
                el.innerHTML = `<span style="background: #0284c7; color: #fff; padding: 3px 6px; border-radius: 9999px; font-size: 11px; font-weight: bold; border: 1px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.25); cursor: pointer;">🏭 ${o.name}</span>`;
                
                el.addEventListener('click', () => {
                    showInfoPanel(o.name, o.description, `Alapítva: ${o.founded || 'N/A'} | Névváltozatok: ${(o.aliases || []).join(', ')}`);
                });

                const m = new maplibregl.Marker({ element: el })
                    .setLngLat(o.location)
                    .addTo(map);
                markers.push({ type: 'orgs', marker: m });
            });
        }

        // 3. Helyszínek és Állambiztonsági pontok betöltése a REST API-ról
        fetch('/api/v1/map/places')
            .then(res => res.json())
            .then(data => {
                if (data.features) {
                    data.features.forEach(f => {
                        const props = f.properties;
                        const coords = f.geometry.coordinates;

                        const el = document.createElement('div');
                        const isBlurred = props.is_blurred;
                        const icon = isBlurred ? '🛡️' : '📍';
                        const bgColor = isBlurred ? '#b45309' : '#b91c1c';

                        el.innerHTML = `<span style="background: ${bgColor}; color: #fff; padding: 3px 7px; border-radius: 9999px; font-size: 11px; font-weight: bold; border: 1px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.3); cursor: pointer;">${icon} ${props.name}</span>`;

                        el.addEventListener('click', () => {
                            let note = props.address_text || '';
                            if (isBlurred) {
                                note += `<br><span style="color: #b45309; font-weight: bold;">⚠️ 2026. évi LI. tv.: Magánlakás pontos koordinátája elmosva városrész-szintre!</span>`;
                            }
                            showInfoPanel(props.name, note, `Típus: ${props.precision_level || 'Helyszín'}`);
                        });

                        const m = new maplibregl.Marker({ element: el })
                            .setLngLat(coords)
                            .addTo(map);
                        markers.push({ type: 'security', marker: m });
                    });
                }
            })
            .catch(err => console.log('Nem sikerült betölteni a dinamikus helyszíneket:', err));
    });

    // Időcsúszka kezelése és korabeli utcanevek frissítése
    const yearSlider = document.getElementById('yearSlider');
    const yearDisplay = document.getElementById('currentYearDisplay');

    if (yearSlider && yearDisplay) {
        yearSlider.addEventListener('input', function () {
            const selectedYear = parseInt(this.value, 10);
            yearDisplay.textContent = selectedYear;
            updateStreetNamesForYear(selectedYear);
        });
    }

    function updateStreetNamesForYear(year) {
        if (!config.streetHistory) return;

        // Keresünk korabeli utcanév egyezéseket az adott évre
        config.streetHistory.forEach(s => {
            let activeName = s.current_name;
            if (s.history) {
                const match = s.history.find(h => {
                    const from = h.valid_from || 0;
                    const to = h.valid_to || 9999;
                    return year >= from && year <= to;
                });
                if (match) {
                    activeName = match.name;
                }
            }
            // Ha változott a korabeli név (pl. Lenin tér a Szabadság tér helyett)
            // konzol vagy info réteg frissítés
        });
    }

    // Rétegek ki/be kapcsolása
    function setupToggle(toggleId, typeKey) {
        const toggle = document.getElementById(toggleId);
        if (toggle) {
            toggle.addEventListener('change', function () {
                const isChecked = this.checked;
                markers.filter(m => m.type === typeKey).forEach(m => {
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
        if (descEl) descEl.innerHTML = description + (meta ? `<div style="margin-top: 0.5rem; font-size: 0.8rem; color: #64748b;">${meta}</div>` : '');
        if (claimsEl) {
            claimsEl.innerHTML = `
                <div style="border-top: 1px solid var(--border); padding-top: 0.75rem;">
                    <a href="/kereses?q=${encodeURIComponent(title)}" class="btn btn-secondary" style="width: 100%; font-size: 0.8rem;">
                        🔍 Kapcsolódó iratok keresése
                    </a>
                </div>
            `;
        }
    }
});
