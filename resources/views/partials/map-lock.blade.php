{{-- Blurs a facilities map for viewers whose plan doesn't open facility details. The markers they get are already rough, unnamed dots. --}}
@unless (app(\App\Services\AccessGate::class)->opensAllFacilities(request()))
    <div class="absolute inset-0 z-[500] grid place-items-center bg-surface/30 p-6 backdrop-blur-[3px]">
        <p class="flex max-w-xs flex-col items-center gap-2 rounded-2xl bg-surface/95 px-5 py-4 text-center text-sm shadow-card">
            <x-icon name="lock" class="size-5 text-brand" />
            <span>Exact locations and names are for members with a plan that includes facility details.</span>
            @php($freeTrial = \App\Services\AccessGate::freeTrialOffer())
            <a href="{{ auth()->check() ? route('plans') : route('register', ['back' => '/']) }}" class="font-semibold text-brand underline">{{ auth()->check() ? 'Choose a plan' : ($freeTrial ? "Sign up for {$freeTrial}" : 'Create an account') }}</a>
        </p>
    </div>
@endunless
