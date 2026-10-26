import { detect, label, onLocation, savedLocation } from './modules/location';
import { initAnimations } from './modules/animate';
import { initFlashToasts, toast } from './modules/toast';
import { initFileUploads } from './modules/file-upload';
import { initModals } from './modules/modal';
import { initPasswordToggles } from './modules/password';
import { initHeader } from './modules/header';
import { initMaps } from './modules/map';
import { recordCurrentSearch, renderRecent } from './modules/recent';
import { initRichEditors } from './modules/rich-editor';
import { initSelects } from './modules/select';
import { initSuggest } from './modules/suggest';

const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];


// Admin week-times editor: quick-fill helpers.
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-week-times] [data-fill]');
    if (!btn) return;
    const box = btn.closest('[data-week-times]');
    const rows = [...box.children].filter((r) => r.querySelector('input[type=checkbox]'));
    const parts = (r) => [r.querySelector('input[type=checkbox]'), ...r.querySelectorAll('input[type=time]')];
    if (btn.dataset.fill === 'weekdays') {
        rows.forEach((r, i) => {
            const [on, from, to] = parts(r);
            on.checked = i < 5;
            from.value = i < 5 ? btn.dataset.from : '';
            to.value = i < 5 ? btn.dataset.to : '';
        });
    } else if (btn.dataset.fill === 'copy-first') {
        const first = rows.map(parts).find(([on, from, to]) => on.checked && from.value && to.value);
        if (first) rows.map(parts).forEach(([on, from, to]) => { if (on.checked) { from.value = first[1].value; to.value = first[2].value; } });
    } else {
        rows.map(parts).forEach(([on, from, to]) => { on.checked = false; from.value = ''; to.value = ''; });
    }
});
// Typing a time ticks that day automatically.
document.addEventListener('change', (e) => {
    if (!e.target.matches('[data-week-times] input[type=time]') || !e.target.value) return;
    const box = e.target.closest('[data-week-times] > div');
    const on = box?.querySelector('input[type=checkbox]');
    if (on) on.checked = true;
});

// Facility "Add services" picker: filter as you type, show how many are ticked.
document.addEventListener('input', (e) => {
    const search = e.target.closest('[data-service-picker] [data-picker-search]');
    if (!search) return;
    const term = search.value.trim().toLowerCase();
    const picker = search.closest('[data-service-picker]');
    picker.querySelectorAll('[data-picker-group]').forEach((group) => {
        const groupMatch = group.querySelector('legend').textContent.toLowerCase().includes(term);
        let visible = 0;
        group.querySelectorAll('[data-picker-item]').forEach((item) => {
            const show = !term || groupMatch || item.textContent.toLowerCase().includes(term);
            item.hidden = !show;
            visible += show ? 1 : 0;
        });
        group.hidden = visible === 0;
    });
});
document.addEventListener('change', (e) => {
    const picker = e.target.closest('[data-service-picker]');
    if (!picker || !e.target.matches('input[type=checkbox]')) return;
    const n = picker.querySelectorAll('input[name="catalog_ids[]"]:checked').length;
    (picker.closest('dialog') ?? picker).querySelector('[data-picker-count]').textContent = n ? String(n) : 'selected';
});

// Dropdown menus built on <details data-dropdown>: close on outside click or Esc.
document.addEventListener('click', (e) => {
    document.querySelectorAll('details[data-dropdown][open]').forEach((d) => {
        if (!d.contains(e.target)) d.open = false;
    });
});
document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('details[data-dropdown][open]').forEach((d) => {
        d.open = false;
        d.querySelector('summary')?.focus();
    });
});

// Profile photo: preview the chosen file instantly, then upload without a button.
document.addEventListener('change', (e) => {
    const input = e.target.closest('[data-avatar-input]');
    if (!input?.files?.[0]) return;
    const file = input.files[0];
    if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
        toast('Please choose a JPG, PNG or WebP photo.', 'error');
        input.value = '';
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        toast('Photos must be 5 MB or smaller.', 'error');
        input.value = '';
        return;
    }
    const form = input.closest('[data-avatar-form]');
    const preview = form.querySelector('[data-avatar-preview]');
    const img = document.createElement('img');
    img.src = URL.createObjectURL(file);
    img.alt = 'New profile photo';
    const size = preview.firstElementChild?.className.match(/size-\S+/)?.[0] ?? 'size-24';
    img.className = `${size} rounded-full object-cover ring-2 ring-surface`;
    preview.replaceChildren(img);
    form.querySelector('[data-avatar-busy]')?.classList.replace('hidden', 'grid');
    form.submit();
});

// Collapse the dashboard sidebar to icons (desktop), remembered per browser.
document.addEventListener('click', (e) => {
    if (!e.target.closest('[data-sidebar-collapse]')) return;
    const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
    try { localStorage.setItem('cs-sidebar', collapsed ? 'collapsed' : 'open'); } catch {}
});

// Admin sidebar: slide-out drawer on phones.
function initAdminDrawer() {
    const sidebar = document.querySelector('[data-admin-sidebar]');
    const backdrop = document.querySelector('[data-admin-backdrop]');
    if (!sidebar) return;
    const setOpen = (open) => {
        sidebar.classList.toggle('-translate-x-full', !open);
        backdrop?.classList.toggle('hidden', !open);
        document.body.classList.toggle('overflow-hidden', open);
        if (open) sidebar.querySelector('a, button')?.focus();
    };
    document.querySelector('[data-admin-open]')?.addEventListener('click', () => setOpen(true));
    document.querySelector('[data-admin-close]')?.addEventListener('click', () => setOpen(false));
    backdrop?.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', (e) => e.key === 'Escape' && setOpen(false));
}

// Lists with a hidden scrollbar fade at the bottom until you reach the end.
function initScrollFades(root = document) {
    root.querySelectorAll('[data-scroll-fade]').forEach((el) => {
        const update = () => el.toggleAttribute('data-end', el.scrollTop + el.clientHeight >= el.scrollHeight - 4);
        el.addEventListener('scroll', update, { passive: true });
        update();
    });
}

function paintLocation(loc) {
    $$('[data-location-label]').forEach((el) => (el.textContent = label(loc)));
    $$('[data-location-label-hero]').forEach((el) => (el.textContent = loc?.inCoverage ? `Near ${loc.area}` : 'Near you'));
}

async function refreshNearbyPanel() {
    const panel = document.querySelector('[data-nearby-panel]');
    if (!panel) return;
    panel.setAttribute('aria-busy', 'true');
    panel.innerHTML = `<div class="grid gap-4 lg:grid-cols-4">${'<div class="skeleton h-28"></div>'.repeat(4)}</div>
        <div class="mt-6 grid gap-6 lg:grid-cols-5"><div class="grid gap-4 lg:col-span-2">${'<div class="skeleton h-36"></div>'.repeat(3)}</div><div class="skeleton min-h-[420px] lg:col-span-3"></div></div>`;
    try {
        const res = await fetch(panel.dataset.endpoint, { headers: { Accept: 'text/html' }, credentials: 'same-origin' });
        panel.innerHTML = await res.text();
        initMaps(panel);
        initAnimations(panel);
        initScrollFades(panel);
    } finally {
        panel.removeAttribute('aria-busy');
    }
}

// Location detection
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-locate]');
    if (!btn) return;
    $$('[data-locate]').forEach((b) => b.setAttribute('disabled', ''));
    $$('[data-location-label]').forEach((el) => (el.textContent = 'Finding you…'));
    try {
        const loc = await detect();
        toast(loc.inCoverage ? `Showing care near ${loc.area}` : 'Looks like you’re outside Accra and Tema', loc.inCoverage ? 'success' : 'info');
        // On search pages, re-run the query so results are sorted by distance.
        if (location.pathname === '/search') {
            const params = new URLSearchParams(location.search);
            params.set('sort', 'distance');
            params.delete('page');
            location.search = params.toString();
        }
    } catch (err) {
        paintLocation(savedLocation());
        toast(err.message, 'error');
        $$('[data-locate-error]').forEach((el) => {
            el.textContent = err.message;
            el.classList.remove('hidden');
        });
    } finally {
        $$('[data-locate]').forEach((b) => b.removeAttribute('disabled'));
    }
});

onLocation((loc) => {
    paintLocation(loc);
    refreshNearbyPanel();
});

// Auto-detect silently when permission was granted before; never prompt on page load.
async function autoDetect() {
    const loc = savedLocation();
    paintLocation(loc);
    if (loc) return;
    try {
        const perm = await navigator.permissions?.query({ name: 'geolocation' });
        if (perm?.state === 'granted') await detect();
    } catch {}
}

// Theme toggle (system default, manual override persisted per device)
document.addEventListener('click', (e) => {
    if (!e.target.closest('[data-theme-toggle]')) return;
    const root = document.documentElement;
    const isDark = root.classList.contains('dark') || (!root.classList.contains('light') && matchMedia('(prefers-color-scheme: dark)').matches);
    root.classList.remove('dark', 'light');
    root.classList.add(isDark ? 'light' : 'dark');
    try {
        localStorage.setItem('cs-theme', isDark ? 'light' : 'dark');
    } catch {}
    $$('[data-map]').forEach((m) => m.classList.toggle('dark-tiles', !isDark));
});

// Filters submit as soon as they change
document.addEventListener('change', (e) => {
    const form = e.target.closest('form[data-autosubmit]');
    if (form && e.target.matches('select, input[type=radio], input[type=checkbox]')) {
        form.querySelector('[name=tab]')?.remove();
        form.requestSubmit();
    }
});

// Client-side filtering of long lists (tests on facility pages)
document.addEventListener('input', (e) => {
    const input = e.target.closest('[data-filter-list]');
    if (!input) return;
    const term = input.value.trim().toLowerCase();
    const root = document.querySelector(input.dataset.filterList);
    $$('[data-filter-item]', root).forEach((li) => (li.hidden = term && !li.textContent.toLowerCase().includes(term)));
    $$('details', root).forEach((d) => {
        if (term) d.open = $$('[data-filter-item]', d).some((li) => !li.hidden);
        d.hidden = term && !$$('[data-filter-item]', d).some((li) => !li.hidden);
    });
});

// Admin table cards: the search box and filter selects narrow the rows in place.
function filterTableCard(card) {
    const term = card.querySelector('[data-table-search]')?.value.trim().toLowerCase() ?? '';
    const filters = $$('select[data-table-filter]', card).filter((s) => s.value).map((s) => [s.dataset.tableFilter, s.value]);
    let shown = 0;
    $$('tr[data-row]', card).forEach((tr) => {
        const text = `${tr.textContent} ${tr.dataset.search ?? ''}`.toLowerCase();
        const match = (!term || text.includes(term)) && filters.every(([key, value]) => tr.dataset[key] === value);
        tr.hidden = !match;
        shown += match ? 1 : 0;
    });
    // Grouped tables: hide a group whose rows are all filtered out.
    $$('tbody', card).forEach((tbody) => {
        if (tbody.querySelector('.group-row')) tbody.hidden = !tbody.querySelector('tr[data-row]:not([hidden])');
    });
    const active = term || filters.length;
    card.querySelector('.admin-table-wrap').hidden = active && !shown;
    card.querySelector('[data-table-empty]').hidden = !active || shown > 0;
}
['input', 'change'].forEach((type) => document.addEventListener(type, (e) => {
    const card = e.target.closest('[data-table-card]');
    if (card && e.target.matches('[data-table-search], select[data-table-filter]')) filterTableCard(card);
}));
$$('[data-table-card]').forEach((card) => card.querySelector('[data-table-search]')?.value && filterTableCard(card));

initHeader();
initFlashToasts();
initModals();
initAdminDrawer();
initRichEditors();
initSelects();
initPasswordToggles();
initFileUploads();
initSuggest();
initScrollFades();
// Filter sidebar is always expanded on desktop; collapsible on phones.
if (matchMedia('(min-width: 1024px)').matches) document.querySelectorAll('details[data-filters]').forEach((d) => (d.open = true));
initAnimations();
recordCurrentSearch();
renderRecent();
initMaps();
autoDetect();

// Available to inline scripts and other modules: window.CareSenda.toast('Saved', 'success').
window.CareSenda = { ...(window.CareSenda || {}), toast };
