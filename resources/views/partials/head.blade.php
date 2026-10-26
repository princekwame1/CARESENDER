{{-- Shared <head> for the public site and the admin area. --}}
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @php($brandName = \App\Support\Branding::name())
    <title>{{ isset($title) ? $title.' · '.$brandName : $brandName.' · Find your way to care' }}</title>
    <meta name="description" content="{{ $description ?? 'Find specialists, tests and health facilities near you in Accra and Tema, checked by phone by the CareSenda team.' }}">
    <meta name="theme-color" content="{{ \App\Support\Branding::color() }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if ($favicon = \App\Support\Branding::faviconUrl())<link rel="icon" href="{{ $favicon }}"><link rel="apple-touch-icon" href="{{ \App\Support\Branding::logoUrl() ?? $favicon }}">@else
    <link rel="icon" href="/favicon.ico" sizes="48x48"><link rel="icon" type="image/png" sizes="32x32" href="/images/brand/favicon-32.png"><link rel="icon" type="image/png" sizes="512x512" href="/images/brand/favicon.png"><link rel="apple-touch-icon" href="/images/brand/apple-touch-icon.png">@endif
    <link rel="manifest" href="/manifest.webmanifest">
    <script>document.documentElement.classList.add('js');try{const t=localStorage.getItem('cs-theme');if(t)document.documentElement.classList.add(t)}catch(e){}</script>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
