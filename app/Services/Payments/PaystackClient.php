<?php

namespace App\Services\Payments;

use App\Support\PaymentSettings;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Minimal Paystack transaction API: hosted checkout + server-side verification. */
class PaystackClient
{
    public function configured(): bool
    {
        return filled(PaymentSettings::secretKey());
    }

    /**
     * Returns Paystack's `data`: authorization_url, access_code, reference. Paystack sends the payer to
     * $callbackUrl afterwards (the website's own page), or to the app's "head back to the app" page.
     */
    public function initialize(string $email, int $amount, string $currency, string $reference, array $metadata = [], ?string $callbackUrl = null): array
    {
        return $this->call(fn (PendingRequest $http) => $http->post('/transaction/initialize', array_filter([
            'email' => $email,
            'amount' => $amount,
            'currency' => $currency,
            'reference' => $reference,
            'callback_url' => $callbackUrl ?? (config('services.paystack.callback_url') ?: route('payments.done')),
            'channels' => PaymentSettings::channels() ?: null,
            'metadata' => $metadata,
        ])));
    }

    /** Returns Paystack's `data` for the transaction (status, amount, currency, channel, ...). */
    public function verify(string $reference): array
    {
        return $this->call(fn (PendingRequest $http) => $http->get('/transaction/verify/'.rawurlencode($reference)));
    }

    public function validSignature(string $payload, ?string $signature): bool
    {
        return $this->configured() && $signature
            && hash_equals(hash_hmac('sha512', $payload, (string) PaymentSettings::secretKey()), $signature);
    }

    private function call(callable $request): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('Paystack is not configured (PAYSTACK_SECRET_KEY).');
        }

        $response = $request(Http::baseUrl('https://api.paystack.co')
            ->withToken(PaymentSettings::secretKey())
            ->acceptJson()
            ->timeout(15));

        if (! $response->successful() || ! $response->json('status')) {
            throw new RuntimeException('Paystack request failed: '.($response->json('message') ?? $response->status()));
        }

        return $response->json('data') ?? [];
    }
}
