// Adds a show/hide eye button inside every password field.
const svg = (path) => `<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${path}</svg>`;
const eye = svg('<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>');
const eyeOff = svg('<path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a17.6 17.6 0 0 1-2.7 3.6M6.6 6.6C3.9 8.4 2 12 2 12s3.5 7 10 7a9.8 9.8 0 0 0 5.4-1.6M9.9 9.9a3 3 0 0 0 4.2 4.2M3 3l18 18"/>');

function enhance(input) {
    const wrap = document.createElement('div');
    wrap.className = 'relative';
    input.before(wrap);
    wrap.append(input);
    input.classList.add('!pr-12');

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'absolute inset-y-0 right-0 grid w-12 place-items-center rounded-r-2xl text-muted transition hover:text-brand';
    const sync = () => {
        const shown = input.type === 'text';
        button.innerHTML = shown ? eyeOff : eye;
        button.setAttribute('aria-label', shown ? 'Hide password' : 'Show password');
        button.setAttribute('aria-pressed', String(shown));
    };
    button.addEventListener('click', () => {
        input.type = input.type === 'password' ? 'text' : 'password';
        sync();
        input.focus({ preventScroll: true });
    });
    // Hide it again on submit so password managers still recognise the field.
    input.form?.addEventListener('submit', () => {
        input.type = 'password';
    });
    sync();
    wrap.append(button);
}

export function initPasswordToggles(root = document) {
    root.querySelectorAll('input[type=password]:not([data-password-toggle])').forEach((input) => {
        input.dataset.passwordToggle = '1';
        enhance(input);
    });
}
