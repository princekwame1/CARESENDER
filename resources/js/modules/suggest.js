// Type-ahead suggestions for any input[data-suggest]. Arrow keys move through
// the list, Enter opens the highlighted suggestion, Escape closes it.
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
const icons = {
    doctor: '<path d="M5 3v6a5 5 0 0 0 10 0V3M3 3h4m6 0h4m-7 11v3a4 4 0 0 0 8 0v-3"/><circle cx="18" cy="11" r="3"/>',
    test: '<path d="M9 3h6m-5 0v7l-6 9q-1 2 2 2h12q3 0 2-2l-6-9V3M8 15h8"/>',
    building: '<path d="M4 21V6h16v15M2 21h20M9 21v-5h6v5M8 9h2m4 0h2M8 12h2m4 0h2M10 3h4"/>',
};
const kinds = { specialist: ['Specialist', 'doctor'], test: ['Test', 'test'], facility: ['Facility', 'building'] };

function hrefFor(item) {
    if (item.kind === 'facility' && item.id) return `/facilities/${encodeURIComponent(item.id)}`;
    return `/search?${new URLSearchParams({ q: item.label, type: item.kind })}`;
}

function attach(input) {
    const box = document.createElement('div');
    box.className = 'absolute inset-x-0 top-full z-50 mt-2 hidden overflow-hidden rounded-3xl border border-line bg-surface p-2 shadow-[0_20px_50px_-20px_rgb(7_104_159/.35)]';
    box.setAttribute('role', 'listbox');
    box.id = `suggest-${Math.random().toString(36).slice(2, 8)}`;
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-controls', box.id);
    input.setAttribute('aria-expanded', 'false');
    input.closest('[data-suggest-root]').append(box);

    let items = [];
    let active = -1;
    let timer;
    let controller;

    const close = () => {
        box.classList.add('hidden');
        input.setAttribute('aria-expanded', 'false');
        active = -1;
    };

    const paint = () => {
        box.innerHTML = items.map((item, i) => {
            const [label, icon] = kinds[item.kind] ?? kinds.facility;
            return `<a role="option" id="${box.id}-${i}" aria-selected="${i === active}" href="${hrefFor(item)}"
                class="flex items-center gap-3 rounded-2xl px-3 py-2.5 transition ${i === active ? 'bg-brand-soft' : 'hover:bg-surface-2'}">
                <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-brand-soft text-brand"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">${icons[icon]}</svg></span>
                <span class="min-w-0 flex-1"><span class="block truncate font-medium text-ink">${esc(item.label)}</span><span class="block truncate text-xs text-muted">${label} · ${esc(item.meta)}</span></span>
            </a>`;
        }).join('');
        input.setAttribute('aria-activedescendant', active >= 0 ? `${box.id}-${active}` : '');
        box.classList.toggle('hidden', items.length === 0);
        input.setAttribute('aria-expanded', String(items.length > 0));
    };

    input.addEventListener('input', () => {
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 2) {
            items = [];
            return close();
        }
        timer = setTimeout(async () => {
            controller?.abort();
            controller = new AbortController();
            try {
                const res = await fetch(`/api/v1/suggest?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' }, signal: controller.signal });
                items = res.ok ? (await res.json()).data : [];
                active = -1;
                paint();
            } catch {}
        }, 180);
    });

    input.addEventListener('keydown', (e) => {
        if (box.classList.contains('hidden')) return;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            active = (active + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
            paint();
        } else if (e.key === 'Enter' && active >= 0) {
            e.preventDefault();
            location.href = hrefFor(items[active]);
        } else if (e.key === 'Escape') {
            close();
        }
    });

    input.addEventListener('blur', () => setTimeout(close, 150));
}

export function initSuggest(root = document) {
    root.querySelectorAll('input[data-suggest]:not([data-suggest-ready])').forEach((input) => {
        input.dataset.suggestReady = '1';
        attach(input);
    });
}
