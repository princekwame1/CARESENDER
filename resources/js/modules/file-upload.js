// <x-file-upload>: shows the chosen file's name, clears it, and accepts a file dropped on the dashed area.
const box = (el) => el.closest('[data-file-upload]');

function show(root) {
    const input = root.querySelector('[data-file-input]');
    const file = input.files?.[0];
    const name = root.querySelector('[data-file-name]');
    name.textContent = file ? file.name : 'No file selected';
    name.classList.toggle('text-ink', !!file);
    name.classList.toggle('text-muted', !file);
    root.querySelector('[data-file-clear]').disabled = !file;
    preview(root, file);
}

// Shows the chosen image in the drop area; anything the browser can't draw (e.g. some .ico files) keeps the icon.
function preview(root, file) {
    const box = root.querySelector('[data-file-preview]');
    const empty = root.querySelector('[data-file-empty]');
    const img = box.querySelector('img');
    if (img.src) URL.revokeObjectURL(img.src);
    const showEmpty = () => {
        img.removeAttribute('src');
        box.hidden = true;
        empty.hidden = false;
    };
    if (!file || !file.type.startsWith('image/')) return showEmpty();
    img.onerror = showEmpty;
    img.src = URL.createObjectURL(file);
    box.hidden = false;
    empty.hidden = true;
}

export function initFileUploads() {
    document.addEventListener('change', (e) => {
        if (e.target.matches('[data-file-upload] [data-file-input]')) show(box(e.target));
    });

    document.addEventListener('click', (e) => {
        const clear = e.target.closest('[data-file-clear]');
        if (!clear) return;
        const root = box(clear);
        root.querySelector('[data-file-input]').value = '';
        show(root);
    });

    const zone = (e) => e.target.closest?.('[data-file-drop]');
    ['dragenter', 'dragover'].forEach((type) => document.addEventListener(type, (e) => {
        const drop = zone(e);
        if (!drop || !e.dataTransfer?.types.includes('Files')) return;
        e.preventDefault();
        drop.dataset.dragging = '';
    }));
    document.addEventListener('dragleave', (e) => {
        const drop = zone(e);
        if (drop && !drop.contains(e.relatedTarget)) delete drop.dataset.dragging;
    });
    document.addEventListener('drop', (e) => {
        const drop = zone(e);
        if (!drop || !e.dataTransfer?.files.length) return;
        e.preventDefault();
        delete drop.dataset.dragging;
        const input = box(drop).querySelector('[data-file-input]');
        const files = new DataTransfer();
        files.items.add(e.dataTransfer.files[0]);
        input.files = files.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });
}
