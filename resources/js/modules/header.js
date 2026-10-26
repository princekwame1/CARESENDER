// Floating header: tightens on scroll; the pill highlight slides to the hovered link.
export function initHeader() {
    const header = document.querySelector('[data-header]');
    const bar = document.querySelector('[data-header-bar]');
    if (header && bar) {
        const onScroll = () => {
            const scrolled = window.scrollY > 12;
            header.classList.toggle('!pt-1.5', scrolled);
            bar.classList.toggle('lg:!h-16', scrolled);
            bar.classList.toggle('!shadow-[0_10px_40px_-14px_rgb(7_104_159/.4)]', scrolled);
        };
        addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    const nav = document.querySelector('[data-pill-nav]');
    const pill = nav?.querySelector('[data-pill]');
    if (!nav || !pill) return;
    const links = [...nav.querySelectorAll('a')];
    const current = links.find((a) => a.getAttribute('aria-current') === 'page');
    const moveTo = (a) => {
        if (!a) return (pill.style.opacity = '0');
        pill.style.opacity = '1';
        pill.style.width = `${a.offsetWidth}px`;
        pill.style.transform = `translateX(${a.offsetLeft - 4}px)`;
    };
    pill.style.left = '4px';
    moveTo(current);
    links.forEach((a) => a.addEventListener('mouseenter', () => moveTo(a)));
    nav.addEventListener('mouseleave', () => moveTo(current));

    // "/" focuses the nearest search box (or goes to the search page).
    document.addEventListener('keydown', (e) => {
        if (e.key !== '/' || e.target.closest('input, textarea, select, [contenteditable]')) return;
        const input = document.querySelector('input[data-suggest]');
        e.preventDefault();
        if (input) {
            input.focus();
            input.scrollIntoView({ block: 'center', behavior: 'smooth' });
        } else {
            location.href = '/search';
        }
    });
}
