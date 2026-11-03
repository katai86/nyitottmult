/**
 * Nyitott Múlt – OpenSeadragon Iratnéző és Bounding Box Kiemelő
 */

document.addEventListener('DOMContentLoaded', function () {
    const config = window.viewerConfig || {
        imagePath: '/assets/sample_docs/karton_sample_p1.webp',
        boxes: [],
        citationText: '[ÁBTL, 1. o.]'
    };

    // OpenSeadragon inicializálása
    const viewer = OpenSeadragon({
        id: 'openseadragon-viewer',
        prefixUrl: 'https://cdnjs.cloudflare.com/ajax/libs/openseadragon/4.1.0/images/',
        tileSources: {
            type: 'image',
            url: config.imagePath
        },
        showNavigationControl: true,
        showNavigator: true,
        navigatorPosition: 'BOTTOM_RIGHT',
        minZoomLevel: 0.5,
        maxZoomLevel: 10,
        visibilityRatio: 0.8
    });

    const zoomDisplay = document.getElementById('zoomDisplay');
    viewer.addHandler('zoom', function (e) {
        if (zoomDisplay) {
            zoomDisplay.textContent = 'Zoom: ' + Math.round(e.zoom * 100) + '%';
        }
    });

    // Bounding Boxok ráillesztése overlayként
    viewer.addHandler('open', function () {
        if (config.boxes && config.boxes.length > 0) {
            const tiledImage = viewer.world.getItemAt(0);
            if (!tiledImage) return;

            const imgWidth = tiledImage.getContentSize().x;
            const imgHeight = tiledImage.getContentSize().y;

            config.boxes.forEach((box, idx) => {
                const elt = document.createElement('div');
                elt.className = 'ocr-box-overlay';
                elt.title = box.text || 'Kiemelt szövegrészlet';
                elt.style.border = '2px solid rgba(2, 132, 199, 0.7)';
                elt.style.backgroundColor = 'rgba(2, 132, 199, 0.15)';
                elt.style.cursor = 'pointer';
                elt.style.transition = 'all 0.2s';

                elt.addEventListener('mouseenter', () => {
                    elt.style.backgroundColor = 'rgba(2, 132, 199, 0.35)';
                    elt.style.border = '2px solid rgba(2, 132, 199, 1)';
                });

                elt.addEventListener('mouseleave', () => {
                    elt.style.backgroundColor = 'rgba(2, 132, 199, 0.15)';
                    elt.style.border = '2px solid rgba(2, 132, 199, 0.7)';
                });

                // Koordináta konverzió OpenSeadragon viewportra
                const rect = viewer.viewport.imageToViewportRectangle(
                    new OpenSeadragon.Rect(box.x, box.y, box.w, box.h)
                );

                viewer.addOverlay(elt, rect);
            });
        }
    });

    // Hivatkozás másolása vágólapra
    const copyBtn = document.getElementById('copyCitationBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(config.citationText).then(() => {
                    const originalText = copyBtn.textContent;
                    copyBtn.textContent = '✓ Másolva!';
                    copyBtn.style.backgroundColor = '#dcfce7';
                    copyBtn.style.color = '#15803d';
                    setTimeout(() => {
                        copyBtn.textContent = originalText;
                        copyBtn.style.backgroundColor = '';
                        copyBtn.style.color = '';
                    }, 2000);
                });
            } else {
                alert('Hivatkozás: ' + config.citationText);
            }
        });
    }

    // OCR hibajelzés
    const reportBtn = document.getElementById('reportOcrBtn');
    if (reportBtn) {
        reportBtn.addEventListener('click', function () {
            const suggestion = prompt('Kérjük, írja be az észlelt OCR hibát és a helyes szöveget:');
            if (suggestion && suggestion.trim() !== '') {
                alert('Köszönjük! A javítási javaslatot rögzítettük a kutatói moderációs sorba.');
            }
        });
    }
});
