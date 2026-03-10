import mapboxgl from 'mapbox-gl';
import {
    formatPrice,
    isMobileViewport,
    getMapContainer,
} from 'map-helpers';

document.addEventListener('DOMContentLoaded', () => {
    const config = document.getElementById('map-config');
    if (!config) return;

    mapboxgl.accessToken = config.dataset.token;
    const apiUrl = config.dataset.apiUrl;
    const isMobile = isMobileViewport(window.innerWidth);

    const mapContainer = getMapContainer(isMobile);
    const mapEl = document.getElementById(mapContainer);
    if (!mapEl) return;

    const map = new mapboxgl.Map({
        container: mapContainer,
        style: 'mapbox://styles/mapbox/dark-v11',
        center: [2.3522, 46.6034],
        zoom: 5.5,
        attributionControl: false,
    });

    map.on('load', () => map.resize());
    map.addControl(new mapboxgl.AttributionControl({ compact: true }), 'bottom-right');

    const zoomInBtn = document.querySelector('#map-zoom-controls [data-zoom-in]');
    const zoomOutBtn = document.querySelector('#map-zoom-controls [data-zoom-out]');
    if (zoomInBtn) zoomInBtn.addEventListener('click', () => map.zoomIn());
    if (zoomOutBtn) zoomOutBtn.addEventListener('click', () => map.zoomOut());

    if (!isMobile) {
        const sidebar = document.getElementById('map-sidebar');
        const toggleBtn = document.getElementById('toggle-sidebar-desktop');
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('map-sidebar-open');
                sidebar.classList.toggle('map-sidebar-closed');
            });
        }
    }

    fetch(apiUrl)
        .then((res) => res.json())
        .then((geojson) => {
            const count = geojson.features.length;

            ['restaurant-count', 'restaurant-count-mobile'].forEach((id) => {
                const el = document.getElementById(id);
                if (el) el.textContent = count;
            });

            const init = () => {
                addRestaurantLayer(map, geojson, isMobile);
                if (isMobile) {
                    renderMobileCards(geojson.features, map);
                } else {
                    renderSidebar(geojson.features, map);
                }
            };

            if (map.isStyleLoaded()) {
                init();
            } else {
                map.once('load', init);
            }

            if (count > 0) {
                const bounds = new mapboxgl.LngLatBounds();
                geojson.features.forEach((f) => bounds.extend(f.geometry.coordinates));
                const padding = isMobile
                    ? { top: 40, bottom: 40, left: 40, right: 40 }
                    : { top: 50, bottom: 50, left: 450, right: 50 };
                map.fitBounds(bounds, { padding, maxZoom: 12 });
            }
        })
        .catch((err) => console.error('Erreur chargement restaurants:', err));
});

function addRestaurantLayer(map, geojson, isMobile) {
    map.addSource('restaurants', { type: 'geojson', data: geojson });

    map.addLayer({
        id: 'restaurant-points',
        type: 'circle',
        source: 'restaurants',
        paint: {
            'circle-radius': isMobile ? 8 : 10,
            'circle-color': 'rgb(99, 102, 241)',
            'circle-stroke-width': 2,
            'circle-stroke-color': 'rgba(255, 255, 255, 0.4)',
        },
    });

    map.on('click', 'restaurant-points', (e) => {
        const feature = e.features[0];
        if (!feature) return;
        const p = feature.properties;
        const coords = feature.geometry.coordinates;

        if (isMobile) {
            const popupHtml = buildMobilePopupHtml(p);
            const containerHeight = map.getContainer().clientHeight || 0;
            const offsetY = -Math.max(80, Math.round(containerHeight * 1.15));

            const popup = new mapboxgl.Popup({
                closeButton: false,
                closeOnClick: true,
                anchor: 'bottom',
                offset: [0, offsetY],
                maxWidth: '280px',
            })
                .setLngLat(coords)
                .setHTML(popupHtml)
                .addTo(map);

            const popupEl = popup.getElement();
            const closeInside = popupEl.querySelector('.map-popup-close');
            if (closeInside) {
                closeInside.addEventListener('click', () => popup.remove());
            }
        } else {
            const detail = document.getElementById('map-sidebar-detail');
            if (detail) showDetailInSidebar(p, detail);
        }

        map.flyTo({ center: coords, zoom: 14, duration: 800 });
    });

    map.on('mouseenter', 'restaurant-points', () => {
        map.getCanvas().style.cursor = 'pointer';
    });
    map.on('mouseleave', 'restaurant-points', () => {
        map.getCanvas().style.cursor = '';
    });
}

function showDetailInSidebar(p, detailEl) {
    const priceFormatted = formatPrice(p.askingPrice);
    const imageHtml = p.image
        ? `<img src="${p.image}" alt="${p.name}" class="w-full h-40 object-cover rounded-t-xl">`
        : `<div class="w-full h-40 bg-gray-800 rounded-t-xl flex items-center justify-center"><span class="text-gray-500">Pas d'image</span></div>`;

    detailEl.innerHTML = `
        <div class="p-4">
            <div class="rounded-xl overflow-hidden bg-gray-900/95 border border-white/10">
                ${imageHtml}
                <div class="p-4">
                    <span class="text-[10px] text-primary uppercase tracking-wider font-semibold">${p.category || 'Restaurant'}</span>
                    <h3 class="font-bold text-white text-lg mt-1">${p.name}</h3>
                    <p class="text-sm text-gray-400 mt-1">${p.address}</p>
                    <div class="flex items-center justify-between mt-3">
                        <div>
                            <p class="text-xs text-gray-500">Prix de départ</p>
                            <p class="font-bold text-primary text-lg">${priceFormatted} €</p>
                        </div>
                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-primary/20 text-primary">${p.capacity} places</span>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <a href="${p.url}" data-turbo="false" class="flex-1 text-center py-2.5 rounded-xl bg-primary text-white font-semibold text-sm no-underline hover:opacity-90">Découvrir</a>
                        <button type="button" id="map-detail-close" class="px-4 py-2.5 rounded-xl border border-white/20 text-gray-300 text-sm font-medium hover:bg-white/5">Fermer</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    detailEl.classList.remove('hidden');
    detailEl.querySelector('#map-detail-close')?.addEventListener('click', () => {
        detailEl.classList.add('hidden');
        detailEl.innerHTML = '';
    });
}

function renderSidebar(features, map) {
    const list = document.getElementById('restaurant-list');
    if (!list) return;

    features.forEach((feature) => {
        const p = feature.properties;
        const coords = feature.geometry.coordinates;
        const card = createSidebarCard(p);

        card.addEventListener('click', () => {
            const detail = document.getElementById('map-sidebar-detail');
            if (detail) showDetailInSidebar(p, detail);
            map.flyTo({ center: coords, zoom: 14, duration: 1000 });
        });

        list.appendChild(card);
    });
}

function createSidebarCard(p) {
    const priceFormatted = formatPrice(p.askingPrice);
    const card = document.createElement('div');
    card.className = 'map-restaurant-card group flex gap-4 p-3 rounded-xl border border-white/5 hover:border-primary/30 hover:bg-white/5 transition-all duration-200 cursor-pointer';

    const imageHtml = p.image
        ? `<img src="${p.image}" alt="${p.name}" class="w-20 h-20 rounded-xl object-cover flex-shrink-0">`
        : `<div class="w-20 h-20 rounded-xl bg-gray-800 flex-shrink-0 flex items-center justify-center"><span class="text-gray-600 text-xs">N/A</span></div>`;

    card.innerHTML = `
        ${imageHtml}
        <div class="flex-1 min-w-0">
            <span class="text-[10px] text-primary uppercase tracking-wider font-semibold">${p.category || 'Restaurant'}</span>
            <h3 class="font-semibold text-white text-sm truncate mt-0.5 group-hover:text-primary transition-colors">${p.name}</h3>
            <p class="text-xs text-gray-500 truncate">${p.address}</p>
            <p class="font-bold text-primary text-sm mt-1">${priceFormatted} €</p>
        </div>
    `;
    return card;
}

function buildMobilePopupHtml(p) {
    const priceFormatted = formatPrice(p.askingPrice);
    const imageHtml = p.image
        ? `<img src="${p.image}" alt="${p.name}" class="w-full h-32 object-cover rounded-t-lg">`
        : `<div class="w-full h-32 bg-gray-800 rounded-t-lg flex items-center justify-center"><span class="text-gray-500 text-xs">Pas d'image</span></div>`;

    return `
        <div class="relative rounded-lg overflow-hidden bg-gray-900/95 border border-white/15 text-left">
            <button type="button" class="map-popup-close absolute top-2 right-2 w-7 h-7 rounded-full bg-black/70 text-white text-xs flex items-center justify-center z-10">✕</button>
            ${imageHtml}
            <div class="p-3">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[10px] text-primary uppercase tracking-wider font-semibold">${p.category || 'Restaurant'}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-primary/20 text-primary">${p.capacity} places</span>
                </div>
                <h3 class="font-semibold text-white text-sm leading-snug">${p.name}</h3>
                <p class="text-[11px] text-gray-400 mt-0.5 line-clamp-2">${p.address}</p>
                <div class="flex items-center justify-between mt-2">
                    <p class="font-bold text-primary text-sm">${priceFormatted} €</p>
                    <a href="${p.url}" data-turbo="false" class="px-3 py-1.5 rounded-lg bg-primary text-white text-xs font-semibold no-underline hover:opacity-90">Découvrir</a>
                </div>
            </div>
        </div>
    `;
}

function renderMobileCards(features, map) {
    const list = document.getElementById('restaurant-list-mobile');
    if (!list) return;

    features.forEach((feature) => {
        const p = feature.properties;
        const priceFormatted = formatPrice(p.askingPrice);

        const card = document.createElement('a');
        card.href = p.url;
        card.setAttribute('data-turbo', 'false');
        card.setAttribute('data-restaurant-id', p.id);
        card.className = 'block rounded-2xl border border-white/10 overflow-hidden bg-gray-900/50 hover:border-primary/30 transition-all duration-200';

        const imageHtml = p.image
            ? `<img src="${p.image}" alt="${p.name}" class="w-full h-48 object-cover">`
            : `<div class="w-full h-48 bg-gray-800 flex items-center justify-center"><span class="text-gray-500">Pas d'image</span></div>`;

        card.innerHTML = `
            ${imageHtml}
            <div class="p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs text-primary uppercase tracking-wider font-semibold">${p.category || 'Restaurant'}</span>
                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-primary/20 text-primary">${p.capacity} places</span>
                </div>
                <h3 class="font-bold text-white text-lg">${p.name}</h3>
                <p class="text-sm text-gray-400 mt-1">${p.address}</p>
                <div class="flex items-center justify-between mt-3">
                    <div>
                        <p class="text-xs text-gray-500">Prix de départ</p>
                        <p class="font-bold text-primary text-xl">${priceFormatted} €</p>
                    </div>
                    <span class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">Découvrir</span>
                </div>
            </div>
        `;

        list.appendChild(card);
    });
}