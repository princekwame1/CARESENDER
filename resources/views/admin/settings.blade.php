@php
    $on = \App\Support\PaymentSettings::enabled();
    $masked = \App\Support\PaymentSettings::maskedSecretKey();
    $channels = \App\Support\PaymentSettings::channels();
    $logo = \App\Support\Branding::logoUrl();
    $favicon = \App\Support\Settings::get('brand.favicon') ? '/storage/'.\App\Support\Settings::get('brand.favicon') : null;
    $color = \App\Support\Branding::color();
    // Reuse typed values only when this modal is the one reopening after a failed save.
    $reopen = fn (string $id) => request()->hasSession() && old('_modal') === $id;
@endphp
<x-layouts.admin :title="'Settings · '.($tab === 'branding' ? 'Branding' : 'Paystack & payments')">
    <div class="space-y-6">
        @php($tabs = ['branding' => ['Branding', 'sparkle'], 'payments' => ['Paystack & payments', 'shield']])
        <nav class="flex gap-1 overflow-x-auto rounded-2xl border border-line bg-surface p-1" aria-label="Settings sections">
            @foreach ($tabs as $key => [$label, $icon])
                <a href="{{ route('admin.settings.edit', $key) }}" @if ($tab === $key) aria-current="page" @endif
                   @class(['flex items-center gap-2 whitespace-nowrap rounded-xl px-4 py-2 text-sm font-medium transition', 'bg-brand-soft text-brand' => $tab === $key, 'text-muted hover:bg-surface-2 hover:text-ink' => $tab !== $key])>
                    <x-icon :name="$icon" class="size-4" />{{ $label }}
                </a>
            @endforeach
        </nav>

        @if ($tab === 'branding')
            <div class="card p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">Branding</h2>
                    </div>
                    <button type="button" class="btn-secondary" data-modal-open="edit-branding">Edit</button>
                </div>
                <dl class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <dt class="text-sm text-muted">Logo</dt>
                        <dd class="mt-2"><span class="grid size-16 place-items-center overflow-hidden rounded-2xl border border-line bg-surface-2">@if ($logo)<img src="{{ $logo }}" alt="Logo" class="size-full object-contain p-1.5">@else<span class="text-2xl text-muted">+</span>@endif</span></dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Name</dt>
                        <dd class="mt-2 font-medium">{{ \App\Support\Branding::name() }}</dd>
                        <dd class="text-sm text-muted">{{ \App\Support\Branding::tagline() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Main colour</dt>
                        <dd class="mt-2 flex items-center gap-2"><span class="size-8 rounded-lg border border-line" style="background: {{ $color }}"></span><span class="font-mono text-sm">{{ $color }}</span></dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Favicon</dt>
                        <dd class="mt-2 flex items-center gap-2">
                            <span class="grid size-10 place-items-center overflow-hidden rounded-xl border border-line bg-surface-2">@if ($favicon ?? $logo)<img src="{{ $favicon ?? $logo }}" alt="Favicon" class="size-6 object-contain">@else<span class="text-muted">+</span>@endif</span>
                            <span class="text-sm text-muted">{{ $favicon ? 'Your own' : ($logo ? 'Using your logo' : 'Default') }}</span>
                        </dd>
                    </div>
                </dl>
            </div>

            <x-admin.modal id="edit-branding" title="Edit branding" size="lg">
                <form id="edit-branding-form" method="POST" action="{{ route('admin.settings.branding') }}" enctype="multipart/form-data" class="grid gap-4">
                    @csrf @method('PUT')
                    <input type="hidden" name="_modal" value="edit-branding">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-admin.field label="Name" name="name" :value="\App\Support\Branding::name()" modal="edit-branding" required maxlength="40" />
                        <x-admin.field label="Tagline in the app" name="tagline" :value="\App\Support\Branding::tagline()" modal="edit-branding" maxlength="80" />
                    </div>
                    @php($formColor = $reopen('edit-branding') ? old('color', $color) : $color)
                    <label class="block">
                        <span class="label">Main colour</span>
                        <span class="flex items-center gap-3">
                            <input type="color" value="{{ $formColor }}" aria-label="Pick a colour" class="h-11 w-14 cursor-pointer rounded-xl border border-line bg-surface p-1" oninput="this.nextElementSibling.value = this.value">
                            <input name="color" required value="{{ $formColor }}" pattern="#[0-9a-fA-F]{6}" aria-label="Colour code" class="field w-32 font-mono" oninput="if (/^#[0-9a-f]{6}$/i.test(this.value)) this.previousElementSibling.value = this.value">
                        </span>
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <span class="label">Logo</span>
                            <x-file-upload name="logo" accept="image/png,image/jpeg,image/webp" label="Browse a logo to upload" hint="A square PNG works best" />
                            @if ($logo)<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remove_logo" value="1"> Remove the logo</label>@endif
                        </div>
                        <div class="space-y-2">
                            <span class="label">Favicon</span>
                            <x-file-upload name="favicon" accept=".png,.ico,.webp,image/png,image/x-icon,image/webp" label="Browse a favicon to upload" hint="PNG or ICO" />
                            @if ($favicon)<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remove_favicon" value="1"> Remove the favicon</label>@endif
                        </div>
                    </div>
                </form>
                <x-slot:footer>
                    <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
                    <button form="edit-branding-form" class="btn-primary">Save branding</button>
                </x-slot:footer>
            </x-admin.modal>
        @else
            <div class="card p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">Paystack &amp; payments</h2>
                    </div>
                    <button type="button" class="btn-secondary" data-modal-open="edit-payments">Edit</button>
                </div>
                <dl class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <dt class="text-sm text-muted">Payments</dt>
                        <dd class="mt-1 font-medium">{{ $on ? 'On' : 'Off, the app is free' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Paystack key</dt>
                        <dd class="mt-1 font-mono text-sm">{{ $masked ?? 'Not added yet' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Ways to pay</dt>
                        <dd class="mt-1">{{ collect($channels)->map(fn ($c) => \App\Support\PaymentSettings::CHANNELS[$c] ?? $c)->join(', ') ?: 'Any' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">An opened search stays open for</dt>
                        <dd class="mt-1">{{ \App\Models\AccessPlan::durationLabel(\App\Support\PaymentSettings::unlockHours()) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Paystack fee the payer covers</dt>
                        <dd class="mt-1">{{ rtrim(rtrim(number_format(\App\Support\PaymentSettings::feePercent(), 2), '0'), '.') }}%</dd>
                    </div>
                </dl>
                <div class="mt-6 rounded-2xl bg-surface-2 p-4 text-sm">
                    <p class="font-medium">Webhook URL for Paystack</p>
                    <code class="mt-2 block break-all rounded-lg bg-surface px-3 py-2 text-xs">{{ route('api.v1.payments.webhook') }}</code>
                </div>
            </div>

            @php($mine = $reopen('edit-payments'))
            <x-admin.modal id="edit-payments" title="Edit payment settings" size="md">
                <form id="edit-payments-form" method="POST" action="{{ route('admin.settings.payments') }}" class="grid gap-4">
                    @csrf @method('PUT')
                    <input type="hidden" name="_modal" value="edit-payments">
                    <input type="hidden" name="enabled" value="0">
                    <label class="flex items-center gap-2 text-sm font-medium"><input type="checkbox" name="enabled" value="1" @checked($mine ? old('enabled') : $on)> Ask app users to pay for full details</label>
                    <label class="block">
                        <span class="label">Paystack secret key</span>
                        <input type="password" name="paystack_secret" autocomplete="off" placeholder="{{ $masked ? 'Leave empty to keep '.$masked : 'sk_live_…' }}" class="field">
                    </label>
                    <x-admin.field label="Hours an opened search stays open" name="unlock_hours" type="number" :value="\App\Support\PaymentSettings::unlockHours()" modal="edit-payments" min="1" max="720" required />
                    <x-admin.field label="Paystack fee added to each payment (%)" name="fee_percent" type="number" :value="\App\Support\PaymentSettings::feePercent()" modal="edit-payments" min="0" max="10" step="0.01" required />
                    <fieldset>
                        <legend class="label">Ways to pay</legend>
                        <div class="flex flex-wrap gap-4">
                            @foreach (\App\Support\PaymentSettings::CHANNELS as $value => $label)
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="channels[]" value="{{ $value }}" @checked(in_array($value, $mine ? (array) old('channels', []) : $channels, true))>{{ $label }}</label>
                            @endforeach
                        </div>
                    </fieldset>
                </form>
                <x-slot:footer>
                    <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
                    <button form="edit-payments-form" class="btn-primary">Save</button>
                </x-slot:footer>
            </x-admin.modal>
        @endif
    </div>
</x-layouts.admin>
