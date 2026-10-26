// Leaflet is loaded on demand (separate chunk) only when a map scrolls into view.
let markersPromise;

function markers() {
    // Web route (not /api) so the session counts: members see the facilities their plan opens.
    markersPromise ??= fetch('/map/markers', { headers: { Accept: 'application/json' } }).then((r) => r.json()).then((j) => j.data);
    return markersPromise;
}

const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

async function mount(el) {
    const [{ default: L }] = await Promise.all([import('leaflet'), import('leaflet/dist/leaflet.css')]);
    const lat = parseFloat(el.dataset.lat);
    const lng = parseFloat(el.dataset.lng);
    const map = L.map(el, { zoomControl: true, scrollWheelZoom: false, preferCanvas: true }).setView([lat, lng], parseInt(el.dataset.zoom || '12', 10));

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    const dark = () => document.documentElement.classList.contains('dark') || (!document.documentElement.classList.contains('light') && matchMedia('(prefers-color-scheme: dark)').matches);
    el.classList.toggle('dark-tiles', dark());

    const brand = getComputedStyle(document.documentElement).getPropertyValue('--brand').trim() || '#1d4ed8';

    if (el.dataset.single) {
        if (el.dataset.precision !== 'exact') {
            const approx = el.dataset.precision === 'area' ? 600 : 2500;
            L.circle([lat, lng], { radius: approx, color: brand, weight: 1.5, fillOpacity: 0.12 }).addTo(map);
        }
        L.circleMarker([lat, lng], { radius: 8, color: '#fff', weight: 2, fillColor: brand, fillOpacity: 1 }).addTo(map);
        return;
    }

    const user = el.closest('[data-nearby-panel]') && el.dataset.zoom === '13';
    if (user) {
        L.marker([lat, lng], { icon: L.divIcon({ className: '', html: '<div class="user-pulse"></div>', iconSize: [18, 18] }), title: 'You are here' }).addTo(map);
    }

    // Many facilities share a neighbourhood centroid; jitter slightly so they do not stack.
    const seen = new Map();
    for (const [id, mLat, mLng, name, type, lab] of await markers()) {
        const key = `${mLat},${mLng}`;
        const n = seen.get(key) ?? 0;
        seen.set(key, n + 1);
        const angle = n * 2.39996;
        const r = n ? 0.0009 * Math.sqrt(n) : 0;
        const marker = L.circleMarker([mLat + r * Math.sin(angle), mLng + r * Math.cos(angle)], {
            radius: lab ? 6 : 5, color: '#fff', weight: 1.5, fillColor: lab ? brand : '#38bdf8', fillOpacity: 0.9,
        });
        // Locked markers come without an id or name: just a rough dot, no popup.
        if (id) {
            marker.bindPopup(`<strong>${esc(name)}</strong><br><small>${esc(type || 'Health facility')}</small><br><a href="/facilities/${encodeURIComponent(id)}">View services →</a>`);
        }
        marker.addTo(map);
    }
}

export function initMaps(root = document) {
    const els = root.querySelectorAll('[data-map]:not([data-mounted])');
    if (!els.length) return;
    const io = new IntersectionObserver((entries) => {
        entries.filter((e) => e.isIntersecting).forEach((e) => {
            io.unobserve(e.target);
            mount(e.target).catch(() => (e.target.innerHTML = '<p class="grid h-full place-items-center p-6 text-sm text-muted">The map didn’t load. Check your connection and refresh.</p>'));
        });
    }, { rootMargin: '200px' });
    els.forEach((el) => {
        el.dataset.mounted = '1';
        io.observe(el);
    });
}
