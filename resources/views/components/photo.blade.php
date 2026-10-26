{{--
    Responsive photo. Use either:
      slug="lab-malaria-test"   a built-in photo from config('caresenda.photos')
      cms="home.hero_image"     an editable image slot (uploaded photo, or its default)
      :image="[...]"            an image array from App\Support\Cms
    Shows the licence credit for built-in photos that require it.
--}}
@props(['slug' => null, 'cms' => null, 'image' => null, 'alt' => null, 'sizes' => '(min-width: 1024px) 50vw, 100vw', 'eager' => false, 'credit' => null, 'linkCredit' => true, 'imgClass' => 'size-full object-cover'])
@php
    $img = $image ?? ($cms ? \App\Support\Cms::image($cms, $alt) : \App\Support\Cms::fromSlug($slug, (string) $alt));
    $c = $img['credit'];
    $showCredit = $c && ($credit ?? true);
@endphp
<figure {{ $attributes->class(['relative overflow-hidden']) }}>
    <img src="{{ $img['src'] }}" srcset="{{ $img['srcset'] }}" sizes="{{ $sizes }}" width="{{ $img['width'] }}" height="{{ $img['height'] }}"
         alt="{{ $img['alt'] }}" decoding="async" @if ($eager) fetchpriority="high" @else loading="lazy" @endif class="{{ $imgClass }}">
    @if ($showCredit)
        <figcaption class="absolute bottom-2 right-2 rounded-full bg-black/45 px-2 py-0.5 text-[10px] text-white/90 backdrop-blur-sm">
            {{-- Plain text when the photo sits inside a link: nested anchors break the layout. --}}
            @if ($linkCredit && $c['source'])
                Photo: <a href="{{ $c['source'] }}" target="_blank" rel="noopener" class="underline-offset-2 hover:underline">{{ $c['creator'] }}</a>, <a href="{{ $c['license_url'] }}" target="_blank" rel="noopener license" class="underline-offset-2 hover:underline">{{ $c['license'] }}</a>
            @else
                Photo: {{ $c['creator'] }}, {{ $c['license'] }}
            @endif
        </figcaption>
    @endif
</figure>
