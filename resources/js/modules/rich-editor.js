// Quill rich text editor for <div data-rich><textarea></div> fields. Quill and its CSS
// load only on pages that use it. The textarea stays in the form and receives the HTML.
export async function initRichEditors(root = document) {
    const boxes = [...root.querySelectorAll('[data-rich]:not([data-rich-ready])')];
    if (!boxes.length) return;
    const [{ default: Quill }] = await Promise.all([import('quill'), import('quill/dist/quill.snow.css')]);

    for (const box of boxes) {
        box.dataset.richReady = '1';
        const textarea = box.querySelector('textarea');
        const holder = document.createElement('div');
        box.prepend(holder);

        const quill = new Quill(holder, {
            theme: 'snow',
            placeholder: textarea.dataset.placeholder || '',
            modules: {
                toolbar: [['bold', 'italic', 'underline'], ['link'], [{ list: 'bullet' }, { list: 'ordered' }], ['clean']],
                clipboard: { matchVisual: false },
            },
            formats: ['bold', 'italic', 'underline', 'link', 'list'],
        });

        // Plain-text values become paragraphs; HTML values load as-is.
        const value = textarea.value.trim();
        if (value.includes('<')) quill.clipboard.dangerouslyPasteHTML(value, 'silent');
        else if (value) quill.setText(value);

        const sync = () => {
            textarea.value = quill.getText().trim() === '' ? '' : quill.root.innerHTML;
        };
        quill.on('text-change', sync);
        textarea.form?.addEventListener('submit', sync);
        textarea.hidden = true;
    }
}
