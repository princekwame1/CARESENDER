@props(['name'])
@php
    $paths = [
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/>',
        'home' => '<path d="m3 10 9-7 9 7v11H3zM9 21v-8h6v8"/>',
        'pin' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'locate' => '<circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="2.5"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/>',
        'arrow' => '<path d="M5 12h14m-5-5 5 5-5 5"/>',
        'back' => '<path d="M19 12H5m5-5-5 5 5 5"/>',
        'doctor' => '<path d="M5 3v6a5 5 0 0 0 10 0V3M3 3h4m6 0h4m-7 11v3a4 4 0 0 0 8 0v-3"/><circle cx="18" cy="11" r="3"/>',
        'test' => '<path d="M9 3h6m-5 0v7l-6 9q-1 2 2 2h12q3 0 2-2l-6-9V3M8 15h8"/>',
        'scan' => '<path d="M3 7V5a2 2 0 0 1 2-2h2m10 0h2a2 2 0 0 1 2 2v2m0 10v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><circle cx="12" cy="12" r="4"/>',
        'pulse' => '<path d="M3 12h4l3-8 4 16 3-8h4"/>',
        'building' => '<path d="M4 21V6h16v15M2 21h20M9 21v-5h6v5M8 9h2m4 0h2M8 12h2m4 0h2M10 3h4"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'shield' => '<path d="m12 2 8 4v6c0 5-8 10-8 10S4 17 4 12V6zM8 12l3 3 5-6"/>',
        'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'heart' => '<path d="M20 5c-4-4-8 1-8 1S8 1 4 5c-5 6 8 15 8 15s13-9 8-15Z"/>',
        'phone' => '<path d="m7 3 3 5-3 3 6 6 3-3 5 3c0 4-3 5-6 3C7 17 3 11 3 6c0-2 2-3 4-3Z"/>',
        'chat' => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/>',
        'route' => '<circle cx="6" cy="19" r="2.5"/><circle cx="18" cy="5" r="2.5"/><path d="M8.5 19H16a3 3 0 0 0 0-6H8a3 3 0 0 1 0-6h7.5"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4m8-4v4"/>',
        'filter' => '<path d="M3 5h18l-7 9v6l-4-2v-4z"/>',
        'chart' => '<path d="M4 20V10m6 10V4m6 16v-7m4 7H3"/>',
        'list' => '<path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'logout' => '<path d="M15 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4M10 17l5-5-5-5M15 12H3"/>',
        'code' => '<path d="m8 8-5 4 5 4m8-8 5 4-5 4M14 4l-4 16"/>',
        'download' => '<path d="M12 3v12m-5-5 5 5 5-5M4 21h16"/>',
        'sparkle' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8zM19 17l.8 2.2L22 20l-2.2.8L19 23l-.8-2.2L16 20l2.2-.8z"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5m0-8h.01"/>',
        'alert' => '<path d="M12 3 2 20h20zM12 10v4m0 3h.01"/>',
        'moon' => '<path d="M20 14.5A8 8 0 0 1 9.5 4 8 8 0 1 0 20 14.5Z"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4m11.4-11.4 1.4-1.4"/>',
    ];
@endphp
@php($sized = preg_match('/(^|\s)([a-z0-9-]+:)*!?size-/', (string) $attributes->get('class')))
<svg {{ $attributes->merge(['class' => $sized ? '' : 'size-5', 'stroke-width' => '1.75']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? $paths['heart'] !!}</svg>
