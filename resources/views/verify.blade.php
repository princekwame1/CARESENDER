<x-layouts.app title="How we verify">
    <div class="container-page max-w-7xl py-12">
        <div class="grid items-center gap-10 lg:grid-cols-2">
            <div data-reveal>
                <p class="section-label">How we check</p>
                <h1 class="mt-2.5 text-[28px] font-bold sm:text-4xl">{{ cms('verify.title') }}</h1>
                <div class="cms-text mt-5 text-base text-muted sm:text-lg">{{ cms_html('verify.intro') }}</div>
            </div>
            <x-photo data-reveal="right" cms="verify.image" class="aspect-[4/3] rounded-[2rem] shadow-card" sizes="(min-width: 1024px) 40vw, 100vw" eager />
        </div>

        <div data-stagger class="mt-12 grid gap-5 sm:grid-cols-2">
            @foreach ([
                ['doctor', cms('verify.card1_title'), cms_html('verify.card1_text')],
                ['test', cms('verify.card2_title'), cms_html('verify.card2_text')],
                ['alert', cms('verify.card3_title'), cms_html('verify.card3_text')],
                ['clock', cms('verify.card4_title'), cms_html('verify.card4_text')],
            ] as [$icon, $h, $p])
                <div data-reveal class="card flex gap-5 p-6"><span class="icon-tile"><x-icon :name="$icon" /></span><div><h2 class="font-bold">{{ $h }}</h2><div class="cms-text mt-1.5 text-sm text-muted">{{ $p }}</div></div></div>
            @endforeach
        </div>

        <h2 class="mt-14 text-2xl font-bold">{{ cms('verify.labels_title') }}</h2>
        <ul class="mt-5 space-y-4">
            <li class="card flex flex-wrap items-center gap-4 p-4"><span class="badge-verified"><x-icon name="shield" />Checked by our team</span><div class="cms-text text-sm text-muted">{{ cms_html('verify.label_verified') }}</div></li>
            <li class="card flex flex-wrap items-center gap-4 p-4"><span class="badge-confirmed"><x-icon name="shield" />Confirmed by CareSenda</span><div class="cms-text text-sm text-muted">{{ cms_html('verify.label_confirmed') }}</div></li>
            <li class="card flex flex-wrap items-center gap-4 p-4"><span class="badge-checklist"><x-icon name="clock" />Waiting for a phone check</span><div class="cms-text text-sm text-muted">{{ cms_html('verify.label_checklist') }}</div></li>
            <li class="card flex flex-wrap items-center gap-4 p-4"><span class="badge-pending"><x-icon name="clock" />Not checked yet</span><div class="cms-text text-sm text-muted">{{ cms_html('verify.label_pending') }}</div></li>
        </ul>

        <div class="mt-12 rounded-2xl bg-brand-soft p-6 text-sm">
            <p class="font-semibold text-brand">{{ cms('verify.distance_title') }}</p>
            <div class="cms-text mt-1.5 text-muted">{{ cms_html('verify.distance_text') }}</div>
        </div>
    </div>
</x-layouts.app>
