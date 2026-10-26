// Scroll-triggered reveals and count-up numbers. Elements opt in with
// data-reveal (optionally "left" | "right" | "zoom"); a parent with
// data-stagger staggers its revealed children. Numbers use data-count.
const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;

function countUp(el) {
    const target = parseFloat(el.dataset.count);
    if (!Number.isFinite(target) || reduced) return;
    const suffix = el.dataset.suffix ?? '';
    const duration = 1400;
    const start = performance.now();
    const format = (n) => Math.round(n).toLocaleString('en-GB') + suffix;
    const tick = (now) => {
        const t = Math.min(1, (now - start) / duration);
        el.textContent = format(target * (1 - Math.pow(1 - t, 3)));
        if (t < 1) requestAnimationFrame(tick);
    };
    el.textContent = format(0);
    requestAnimationFrame(tick);
}

const io = 'IntersectionObserver' in window
    ? new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (!entry.isIntersecting) continue;
            const el = entry.target;
            io.unobserve(el);
            if (el.hasAttribute('data-count')) countUp(el);
            else el.classList.add('is-visible');
        }
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 })
    : null;

export function initAnimations(root = document) {
    root.querySelectorAll('[data-stagger]').forEach((group) => {
        [...group.querySelectorAll(':scope > [data-reveal], :scope > * > [data-reveal]')]
            .forEach((child, i) => child.style.setProperty('--i', String(i % 8)));
    });

    root.querySelectorAll('[data-reveal]:not(.is-visible), [data-count]').forEach((el) => {
        if (!io || reduced) {
            el.classList.add('is-visible');
            return;
        }
        io.observe(el);
    });
}
