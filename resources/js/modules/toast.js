// Global toasts: top-right, auto-dismiss after 5 s (paused on hover), success or error.
const DURATION = 5000;
const icons = {
    success: '<path d="m5 12 4 4L19 6"/>',
    error: '<path d="M12 3 2 20h20zM12 10v4m0 3h.01"/>',
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v5m0-8h.01"/>',
};
const tones = {
    success: 'border-brand/25 [--toast:var(--brand)]',
    error: 'border-danger/30 [--toast:var(--danger)]',
    info: 'border-line [--toast:var(--accent)]',
};

export function toast(message, type = 'success') {
    const stack = document.getElementById('toasts');
    if (!stack || !message) return;

    const el = document.createElement('div');
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    el.className = `toast-item pointer-events-auto relative overflow-hidden rounded-2xl border bg-surface shadow-[0_18px_50px_-16px_rgb(7_104_159/.4)] ${tones[type] ?? tones.info}`;
    el.innerHTML = `
        <div class="flex items-start gap-3 p-4 pr-10">
            <span class="mt-0.5 grid size-7 shrink-0 place-items-center rounded-full bg-[color-mix(in_oklab,var(--toast),transparent_85%)] text-[var(--toast)]">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${icons[type] ?? icons.info}</svg>
            </span>
            <p class="text-sm font-medium !text-ink"></p>
        </div>
        <button type="button" class="absolute right-2 top-2 grid size-7 place-items-center rounded-full text-muted transition hover:bg-surface-2 hover:text-ink" aria-label="Dismiss">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
        <span class="toast-bar absolute bottom-0 left-0 h-[3px] w-full bg-[var(--toast)]"></span>`;
    el.querySelector('p').textContent = message;
    stack.prepend(el);

    let remaining = DURATION;
    let started = Date.now();
    let timer;
    const bar = el.querySelector('.toast-bar');
    const close = () => {
        clearTimeout(timer);
        el.classList.add('toast-leave');
        setTimeout(() => el.remove(), 250);
    };
    const run = () => {
        started = Date.now();
        bar.style.transition = `transform ${remaining}ms linear`;
        requestAnimationFrame(() => (bar.style.transform = 'scaleX(0)'));
        timer = setTimeout(close, remaining);
    };
    el.addEventListener('mouseenter', () => {
        clearTimeout(timer);
        remaining -= Date.now() - started;
        const scale = getComputedStyle(bar).transform;
        bar.style.transition = 'none';
        bar.style.transform = scale;
    });
    el.addEventListener('mouseleave', run);
    el.querySelector('button').addEventListener('click', close);
    requestAnimationFrame(run);
}

// Show messages flashed by the server (saved, errors…).
export function initFlashToasts() {
    const data = document.getElementById('flash-data');
    if (!data) return;
    try {
        JSON.parse(data.textContent || '[]').forEach((f) => toast(f.message, f.type));
    } catch {}
}
