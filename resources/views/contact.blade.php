@php
    use App\Models\ContactMessage;
    // Dashboard values first, then the .env fallbacks.
    $c = collect(['phone', 'whatsapp', 'email', 'hours'])->mapWithKeys(fn ($k) => [$k => cms("contact.$k", config("caresenda.contact.$k") ?? '')])->all();
@endphp
<x-layouts.app title="Contact us" description="Get in touch with CareSenda to report wrong information, add your facility, or ask a question.">
    <div class="container-page max-w-7xl py-12">
        <div class="grid gap-10 lg:grid-cols-[1fr_1.2fr]">
            <div data-reveal>
                <p class="section-label">Contact us</p>
                <h1 class="mt-2 text-[28px] font-bold sm:text-4xl">{{ cms('contact.title') }}</h1>
                <div class="cms-text mt-5 text-base sm:text-lg">{{ cms_html('contact.intro') }}</div>

                <ul class="mt-8 grid gap-3">
                    @if ($c['phone'])
                        <li><a href="tel:{{ $c['phone'] }}" class="card flex items-center gap-4 p-4 transition hover:shadow-glow"><span class="icon-tile"><x-icon name="phone" /></span><span><span class="block text-xs text-muted">Call us</span><span class="font-semibold text-ink">{{ $c['phone'] }}</span></span></a></li>
                    @endif
                    @if ($c['whatsapp'])
                        <li><a href="https://wa.me/{{ ltrim($c['whatsapp'], '+') }}" target="_blank" rel="noopener" class="card flex items-center gap-4 p-4 transition hover:shadow-glow"><span class="icon-tile"><x-icon name="chat" /></span><span><span class="block text-xs text-muted">WhatsApp</span><span class="font-semibold text-ink">{{ $c['whatsapp'] }}</span></span></a></li>
                    @endif
                    @if ($c['email'])
                        <li><a href="mailto:{{ $c['email'] }}" class="card flex items-center gap-4 p-4 transition hover:shadow-glow"><span class="icon-tile"><x-icon name="info" /></span><span><span class="block text-xs text-muted">Email</span><span class="font-semibold text-ink">{{ $c['email'] }}</span></span></a></li>
                    @endif
                    @if ($c['hours'])
                        <li class="flex items-center gap-2 px-1 text-sm"><x-icon name="clock" class="size-4 text-brand" />{{ $c['hours'] }}</li>
                    @endif
                </ul>

                <div class="relative mt-8">
                    <x-photo cms="contact.image" class="aspect-[16/10] rounded-[2rem] shadow-card" img-class="size-full object-cover object-[55%_30%]" sizes="(min-width: 1024px) 40vw, 100vw" />
                    <div class="absolute inset-x-4 bottom-4 flex items-center gap-3 rounded-2xl bg-surface/90 p-3 shadow-card backdrop-blur sm:inset-x-auto sm:left-4">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-[#07689f] text-white"><x-icon name="chat" class="size-5" /></span>
                        <div><p class="text-sm font-semibold text-ink">{{ cms('contact.image_title') }}</p><p class="text-xs text-muted">{{ cms('contact.image_text') }}</p></div>
                    </div>
                </div>

                <div class="mt-8 rounded-3xl bg-panel p-6 text-sm">
                    <p class="font-semibold text-ink">{{ cms('contact.emergency_title') }}</p>
                    @php($emergency = (string) cms_html('contact.emergency_text'))
                    {{-- Make emergency numbers tappable unless the text already has links. --}}
                    <div class="cms-text mt-1.5">{!! str_contains($emergency, '<a') ? $emergency : preg_replace('/\b(112|191|193)\b/', '<a href="tel:$1" class="font-semibold text-brand">$1</a>', $emergency) !!}</div>
                </div>
            </div>

            <div data-reveal="right" class="card p-6 sm:p-8">
                @if (session('sent'))
                    <div class="flex flex-col items-center gap-4 py-10 text-center">
                        <span class="grid size-16 place-items-center rounded-full bg-brand-soft text-brand"><x-icon name="check" class="size-8" /></span>
                        <h2 class="text-2xl font-bold">{{ cms('contact.sent_title') }}</h2>
                        <div class="cms-text max-w-sm">{{ cms_html('contact.sent_text') }}</div>
                        <a href="{{ route('home') }}" class="btn-secondary mt-2">Back to home</a>
                    </div>
                @else
                    <h2 class="text-xl font-semibold">{{ cms('contact.form_title') }}</h2>
                    <form method="POST" action="{{ route('contact.store') }}" class="mt-6 grid gap-5" novalidate>
                        @csrf
                        <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <label><span class="label">Your name</span><input name="name" value="{{ old('name') }}" required autocomplete="name" class="field">@error('name')<span class="mt-1 block text-xs text-danger">{{ $message }}</span>@enderror</label>
                            <label><span class="label">Email or phone</span><input name="contact" value="{{ old('contact') }}" required autocomplete="email" class="field" placeholder="So we can reply">@error('contact')<span class="mt-1 block text-xs text-danger">{{ $message }}</span>@enderror</label>
                        </div>
                        <label><span class="label">What’s it about?</span>
                            <select name="topic" class="field">
                                @foreach (ContactMessage::TOPICS as $value => $label)
                                    <option value="{{ $value }}" @selected(old('topic', $topic) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label><span class="label">Facility name <span class="font-normal">(optional)</span></span><input name="facility_name" value="{{ old('facility_name') }}" class="field" placeholder="If it’s about a specific clinic, hospital or lab"></label>
                        <label><span class="label">Message</span><textarea name="message" rows="5" required class="field resize-y" placeholder="Tell us what’s going on">{{ old('message') }}</textarea>@error('message')<span class="mt-1 block text-xs text-danger">{{ $message }}</span>@enderror</label>
                        <button class="btn-primary btn-lg w-full sm:w-fit">Send message<x-icon name="arrow" /></button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
