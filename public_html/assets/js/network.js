/**
 * Nyitott Múlt – Kapcsolati Háló (Cytoscape.js)
 */

document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('network-graph-container');
    if (!container) return;

    fetch('/api/v1/network/graph')
        .then(res => res.json())
        .then(data => {
            if (data.elements && data.elements.length > 0) {
                initCytoscape(data.elements);
            } else {
                showEmptyNetwork();
            }
        })
        .catch(err => {
            console.error('Hiba a kapcsolati gráf betöltésekor:', err);
            showEmptyNetwork();
        });

    function showEmptyNetwork() {
        container.innerHTML = '<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; min-height: 380px; color: #64748b; font-size: 0.95rem; text-align: center; padding: 2rem;">' +
            '<div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🕸️</div>' +
            '<div style="font-weight: 700; color: #0f172a; margin-bottom: 0.25rem;">Nincsenek jóváhagyott kapcsolati adatok</div>' +
            '<div style="font-size: 0.85rem; max-width: 480px; line-height: 1.5; color: #64748b;">A hálózatban kizárólag a forráskritikailag igazolt, két lektor által jóváhagyott levéltári állítások kapcsolatai jelenhetnek meg a 2026. évi LI. törvény rendelkezéseinek megfelelően.</div>' +
            '</div>';
    }

    function initCytoscape(elements) {
        const cy = cytoscape({
            container: container,
            elements: elements,
            style: [
                {
                    selector: 'node',
                    style: {
                        'label': 'data(label)',
                        'font-size': '12px',
                        'text-valign': 'bottom',
                        'text-margin-y': '6px',
                        'background-color': '#475569',
                        'color': '#0f172a',
                        'width': '36px',
                        'height': '36px'
                    }
                },
                {
                    selector: 'node[type = "agent"]',
                    style: { 'background-color': '#b91c1c' }
                },
                {
                    selector: 'node[type = "observed"]',
                    style: { 'background-color': '#0284c7' }
                },
                {
                    selector: 'node[type = "officer"]',
                    style: { 'background-color': '#b45309' }
                },
                {
                    selector: 'node[type = "org"]',
                    style: { 'background-color': '#64748b', 'shape': 'rectangle' }
                },
                {
                    selector: 'edge',
                    style: {
                        'width': 2,
                        'line-color': '#94a3b8',
                        'target-arrow-color': '#94a3b8',
                        'target-arrow-shape': 'triangle',
                        'curve-style': 'bezier',
                        'label': 'data(label)',
                        'font-size': '10px',
                        'text-rotation': 'autorotate',
                        'color': '#475569'
                    }
                },
                {
                    selector: ':selected',
                    style: {
                        'border-width': 3,
                        'border-color': '#0284c7',
                        'line-color': '#0284c7',
                        'target-arrow-color': '#0284c7'
                    }
                }
            ],
            layout: {
                name: 'cose',
                animate: false,
                nodeRepulsion: 4500,
                idealEdgeLength: 120
            }
        });

        // Eseménykezelők
        cy.on('tap', 'node', function (evt) {
            const node = evt.target;
            const data = node.data();
            showNetInfo(data.label, `Típus: ${data.type || 'Személy'}`, data.id);
        });

        cy.on('tap', 'edge', function (evt) {
            const edge = evt.target;
            const data = edge.data();
            showNetInfo(
                `Kapcsolat: ${data.label || 'Levéltári kapcsolat'}`,
                `<strong>Bizonyító forrásidézet:</strong><br>"${data.quote || 'Hitelesített levéltári irat alapján.'}"`,
                null
            );
        });
    }

    function showNetInfo(title, content, entityId) {
        const titleEl = document.getElementById('net-title');
        const contentEl = document.getElementById('net-content');
        const actionEl = document.getElementById('net-action');

        if (titleEl) titleEl.textContent = title;
        if (contentEl) contentEl.innerHTML = content;
        if (actionEl) {
            if (entityId) {
                actionEl.innerHTML = `<a href="/entitasok/${encodeURIComponent(entityId)}" class="btn btn-primary" style="width: 100%; font-size: 0.85rem;">Adatlap megtekintése &rarr;</a>`;
            } else {
                actionEl.innerHTML = `<a href="/bizonyitekterkep" class="btn btn-secondary" style="width: 100%; font-size: 0.85rem;">Bizonyítéktérkép &rarr;</a>`;
            }
        }
    }
});
