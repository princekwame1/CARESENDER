// Replaces native <select> popups (which the OS draws and we cannot style) with a
// themed listbox. The native select stays in the form, hidden, as the source of truth:
// we write its value and dispatch "change", so forms and auto-submit keep working.
const check = '<svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>';
const chevron = '<svg class="size-4 shrink-0 text-muted transition-transform duration-200 group-aria-expanded:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
let uid = 0;

function enhance(select) {
    const id = `cs-select-${++uid}`;
    const wrap = document.createElement('div');
    wrap.className = `relative ${select.classList.contains('w-full') || select.classList.contains('field') ? 'w-full' : ''} ${select.className.includes('sm:w-auto') ? 'sm:w-auto' : ''}`;

    const button = document.createElement('button');
    button.type = 'button';
    button.className = `group ${select.className} flex items-center justify-between gap-2 text-left`;
    button.setAttribute('aria-haspopup', 'listbox');
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute('aria-controls', `${id}-list`);
    const labelText = select.getAttribute('aria-label') || select.closest('label')?.querySelector('.label, .sr-only')?.textContent?.trim();
    if (labelText) button.setAttribute('aria-label', labelText);

    // Long lists in pop-up forms (or any select marked data-searchable) get a filter box above the options.
    const searchable = select.hasAttribute('data-searchable') || (!!select.closest('dialog') && select.options.length >= 6);

    const panel = document.createElement('div');
    panel.className = 'absolute left-0 z-50 hidden min-w-full w-max max-w-[min(20rem,90vw)] rounded-2xl border border-line bg-surface p-1.5 shadow-[0_18px_50px_-16px_rgb(7_104_159/.35)]';

    const search = document.createElement('input');
    if (searchable) {
        search.type = 'search';
        search.placeholder = 'Search…';
        search.autocomplete = 'off';
        search.className = 'mb-1.5 w-full rounded-xl border border-line bg-surface-2 px-3 py-2 text-sm text-ink outline-none placeholder:text-muted focus:border-brand';
        search.setAttribute('role', 'combobox');
        search.setAttribute('aria-controls', `${id}-list`);
        search.setAttribute('aria-expanded', 'true');
        search.setAttribute('aria-label', `Search ${labelText ?? 'options'}`);
        panel.append(search);
    }

    const list = document.createElement('ul');
    list.id = `${id}-list`;
    list.setAttribute('role', 'listbox');
    list.tabIndex = -1;
    list.className = 'max-h-72 overflow-y-auto [scrollbar-width:thin]';
    panel.append(list);

    select.hidden = true;
    select.after(wrap);
    wrap.append(select, button, panel);

    let active = -1;
    let filter = '';
    const options = () => [...select.options];
    // Indexes of the options shown right now (all of them, or those matching the search).
    const visible = () => options().map((o, i) => [o, i]).filter(([o]) => !filter || o.text.toLowerCase().includes(filter)).map(([, i]) => i);

    const paintButton = () => {
        const current = select.options[select.selectedIndex];
        button.innerHTML = `<span class="truncate">${esc(current?.text ?? '')}</span>${chevron}`;
        button.disabled = select.disabled;
        button.classList.toggle('opacity-50', select.disabled);
    };

    const paintList = () => {
        const shown = visible();
        list.innerHTML = shown.length ? shown.map((i) => {
            const o = select.options[i];
            const selected = i === select.selectedIndex;
            return `<li role="option" id="${id}-${i}" data-index="${i}" aria-selected="${selected}" ${o.disabled ? 'aria-disabled="true"' : ''}
                class="flex cursor-pointer items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm transition-colors
                ${i === active ? 'bg-brand-soft' : ''} ${selected || i === active ? 'text-brand' : 'text-ink'} ${selected ? 'font-semibold' : ''} ${o.disabled ? 'pointer-events-none opacity-40' : 'hover:bg-brand-soft hover:text-brand'}">
                <span>${esc(o.text)}</span>${selected ? check : '<span class="size-4"></span>'}</li>`;
        }).join('') : '<li class="px-3 py-2 text-sm text-muted">No matches</li>';
        (searchable ? search : button).setAttribute('aria-activedescendant', active >= 0 ? `${id}-${active}` : '');
        list.querySelector(`[data-index="${active}"]`)?.scrollIntoView({ block: 'nearest' });
    };

    const open = () => {
        if (select.disabled) return;
        document.dispatchEvent(new CustomEvent('cs-select-open', { detail: wrap }));
        filter = '';
        search.value = '';
        active = select.selectedIndex;
        paintList();
        panel.classList.remove('hidden');
        // Open upwards when there isn't room below.
        const rect = button.getBoundingClientRect();
        const up = innerHeight - rect.bottom < Math.min(panel.scrollHeight, 340) + 16 && rect.top > innerHeight - rect.bottom;
        panel.classList.toggle('bottom-full', up);
        panel.classList.toggle('mb-2', up);
        panel.classList.toggle('top-full', !up);
        panel.classList.toggle('mt-2', !up);
        button.setAttribute('aria-expanded', 'true');
        if (searchable) search.focus({ preventScroll: true });
    };

    const isOpen = () => !panel.classList.contains('hidden');

    const close = () => {
        panel.classList.add('hidden');
        button.setAttribute('aria-expanded', 'false');
        active = -1;
    };

    const choose = (i) => {
        if (i < 0 || options()[i]?.disabled) return;
        const changed = select.selectedIndex !== i;
        select.selectedIndex = i;
        paintButton();
        close();
        button.focus();
        if (changed) select.dispatchEvent(new Event('change', { bubbles: true }));
    };

    // Steps through the shown options, skipping disabled ones.
    const move = (step) => {
        const shown = visible().filter((i) => !select.options[i].disabled);
        if (!shown.length) return;
        const at = shown.indexOf(active);
        active = shown[at < 0 ? (step > 0 ? 0 : shown.length - 1) : (at + step + shown.length) % shown.length];
        paintList();
    };

    button.addEventListener('click', () => (isOpen() ? close() : open()));
    list.addEventListener('mousedown', (e) => e.preventDefault());

    if (searchable) {
        search.addEventListener('input', () => {
            filter = search.value.trim().toLowerCase();
            const shown = visible().filter((i) => !select.options[i].disabled);
            active = shown.includes(active) ? active : (shown[0] ?? -1);
            paintList();
        });
        search.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                move(e.key === 'ArrowDown' ? 1 : -1);
            } else if (e.key === 'Enter') {
                e.preventDefault(); // never submit the surrounding form from the search box
                choose(active);
            } else if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation(); // close this list, not the dialog around it
                close();
                button.focus();
            } else if (e.key === 'Tab') {
                close();
            }
        });
    }
    list.addEventListener('click', (e) => {
        const li = e.target.closest('[data-index]');
        if (li) choose(Number(li.dataset.index));
    });

    let typed = '';
    let typedTimer;
    button.addEventListener('keydown', (e) => {
        const opened = isOpen();
        if (['ArrowDown', 'ArrowUp'].includes(e.key)) {
            e.preventDefault();
            if (!opened) open();
            else move(e.key === 'ArrowDown' ? 1 : -1);
        } else if ((e.key === 'Enter' || e.key === ' ') && opened) {
            e.preventDefault();
            choose(active);
        } else if (e.key === 'Escape' && opened) {
            e.preventDefault();
            e.stopPropagation();
            close();
        } else if (e.key === 'Home' || e.key === 'End') {
            if (!opened) return;
            e.preventDefault();
            active = e.key === 'Home' ? 0 : options().length - 1;
            paintList();
        } else if (e.key === 'Tab') {
            close();
        } else if (searchable && e.key.length === 1 && /\S/.test(e.key)) {
            // Start typing on the closed button: open it and send the letter to the search box.
            e.preventDefault();
            if (!opened) open();
            search.value += e.key;
            search.dispatchEvent(new Event('input'));
            search.focus();
        } else if (e.key.length === 1 && /\S/.test(e.key)) {
            typed += e.key.toLowerCase();
            clearTimeout(typedTimer);
            typedTimer = setTimeout(() => (typed = ''), 600);
            const i = options().findIndex((o) => o.text.toLowerCase().startsWith(typed));
            if (i >= 0) {
                if (opened) {
                    active = i;
                    paintList();
                } else {
                    choose(i);
                }
            }
        }
    });

    document.addEventListener('click', (e) => !wrap.contains(e.target) && close());
    document.addEventListener('cs-select-open', (e) => e.detail !== wrap && close());
    select.addEventListener('change', paintButton);
    paintButton();
}

export function initSelects(root = document) {
    root.querySelectorAll('select:not([data-native]):not([data-enhanced])').forEach((select) => {
        select.dataset.enhanced = '1';
        enhance(select);
    });
}
