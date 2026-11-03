/**
 * Nyitott Múlt – Kapcsolati Háló (Cytoscape.js)
 */

document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('network-graph-container');
    if (!container) return;

    fetch('/api/v1/network/graph')
        .then(res => res.json())
        .then(data => {
            initCytoscape(data.elements || []);
        })
        .catch(err => {
            console.error('Hiba a kapcsolati gráf betöltésekor:', err);
            // Fallback beépített minta adatok
            initCytoscape([
                { data: { id: 'e1', label: 'MINTA Kovács János ("Homok")', type: 'agent' } },
                { data: { id: 'e2', label: 'MINTA Dr. Szabó István', type: 'observed' } },
                { data: { id: 'e3', label: 'MINTA Tóth Béla r. fhdgy.', type: 'officer' } },
                { data: { id: 'e4', label: 'Orosházi PNK (1959)', type: 'org' } },
                { data: { id: 'rel1', source: 'e3', target: 'e1', label: 'beszervezte (1959)', quote: '1959. április 12-én szervezte be hazafias alapon.' } },
                { data: { id: 'rel2', source: 'e1', target: 'e2', label: 'megfigyelte (1960)', quote: '1960. szeptember 18-i jelentés a Szabadság téri presszóból.' } }
            ]);
        });

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
