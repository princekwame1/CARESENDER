// Per-device recent searches (signed-in mobile users get server-side history via the API).
const KEY = 'cs-recent';
const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

function read() {
    try {
        return JSON.parse(localStorage.getItem(KEY) || '[]');
    } catch {
        return [];
    }
}

function write(list) {
    try {
        localStorage.setItem(KEY, JSON.stringify(list.slice(0, 6)));
    } catch {}
}

export function recordCurrentSearch() {
    if (location.pathname !== '/search') return;
    const params = new URLSearchParams(location.search);
    const q = (params.get('q') || '').trim();
    const type = params.get('type') || 'all';
    if (!q && type === 'all') return;
    write([{ q, type }, ...read().filter((r) => !(r.q === q && r.type === type))]);
}

export function renderRecent() {
    const list = document.querySelector('[data-recent-list]');
    if (!list) return;
    const items = read();
    const labels = { all: 'All categories', specialist: 'Specialist', test: 'Diagnostic test', facility: 'Health facility' };
    list.innerHTML = items.length
        ? items.map((r) => `<li><a class="flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 hover:bg-surface-2" href="/search?${new URLSearchParams({ q: r.q, type: r.type })}">
            <span class="min-w-0"><strong class="block truncate text-sm">${esc(r.q || labels[r.type])}</strong><small class="text-xs text-muted">${labels[r.type] ?? ''}</small></span><span aria-hidden="true" class="text-brand">→</span></a></li>`).join('')
        : '<li class="text-sm text-muted">Your searches will appear here.</li>';
    document.querySelector('[data-recent-clear]')?.addEventListener('click', () => {
        write([]);
        renderRecent();
    }, { once: true });
}
