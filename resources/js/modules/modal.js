// Dashboard modals (<dialog data-modal>). Buttons with data-modal-open="id" open one;
// [data-modal-close], Esc, or a click on the dimmed backdrop close it.
export function initModals() {
    document.addEventListener('click', (e) => {
        const opener = e.target.closest('[data-modal-open]');
        if (opener) {
            const dialog = document.getElementById(opener.dataset.modalOpen);
            if (dialog?.showModal) {
                e.preventDefault();
                dialog.showModal();
                dialog.querySelector('input:not([type=hidden]):not([type=checkbox]):not([type=file]), textarea, select')?.focus();
            }
            return;
        }
        if (e.target.closest('[data-modal-close]')) {
            e.target.closest('dialog')?.close();
            return;
        }
        // A click on the backdrop lands on the <dialog> element itself.
        if (e.target.matches?.('dialog[data-modal]')) {
            const r = e.target.getBoundingClientRect();
            const inside = e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom;
            if (!inside) e.target.close();
        }
    });

    // Reopen the modal whose form failed to save.
    document.querySelector('dialog[data-modal][data-reopen], dialog[data-modal][data-open-on-load]')?.showModal();
}
