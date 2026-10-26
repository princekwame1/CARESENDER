<x-layouts.app title="Payment received">
    <div class="container-page max-w-xl py-16 text-center">
        <span class="icon-tile mx-auto"><x-icon name="shield" /></span>
        <h1 class="mt-5 text-[28px] font-bold">Thank you — you're all set</h1>
        <p class="mt-3 text-base text-muted">Head back to the CareSenda app. It will notice your payment within a few seconds and open the details for you.</p>
        <p class="mt-6 text-sm text-muted">If the app still shows the payment as pending, pull down to refresh, or reach us through the <a class="underline" href="{{ route('contact') }}">contact page</a> with the reference from your Paystack receipt.</p>
    </div>
</x-layouts.app>
