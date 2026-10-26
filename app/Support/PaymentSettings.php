<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/** Paystack and access settings the team edits in /admin/settings. Environment values are only starting defaults. */
class PaymentSettings
{
    public const CHANNELS = ['mobile_money' => 'Mobile Money', 'card' => 'Card', 'bank' => 'Bank', 'ussd' => 'USSD'];

    public static function enabled(): bool
    {
        return (bool) (self::raw('enabled') ?? config('caresenda.payments_enabled'));
    }

    public static function currency(): string
    {
        return self::raw('currency') ?? 'GHS';
    }

    /** How long a search stays open after one search credit is spent. */
    public static function unlockHours(): int
    {
        return max(1, (int) (self::raw('unlock_hours') ?? 24));
    }

    /** Paystack's charge as a percentage of each payment (1.95% for local payments in Ghana). The payer covers it. */
    public static function feePercent(): float
    {
        return max(0, (float) (self::raw('fee_percent') ?? 1.95));
    }

    /**
     * The fee added to a price, in pesewas. Paystack takes its percentage of the whole bill,
     * so the bill is grossed up until what's left after the fee is the full price.
     */
    public static function feeFor(int $price): int
    {
        $percent = self::feePercent();

        return $percent > 0 && $price > 0 ? (int) ceil($price / (1 - $percent / 100)) - $price : 0;
    }

    /** @return list<string> */
    public static function channels(): array
    {
        $saved = self::raw('channels');

        return $saved !== null ? array_values(array_filter(explode(',', $saved))) : ['mobile_money', 'card'];
    }

    public static function secretKey(): ?string
    {
        $saved = self::raw('paystack_secret');
        if ($saved) {
            try {
                return Crypt::decryptString($saved);
            } catch (DecryptException) {
                return null; // APP_KEY changed; the team must enter the key again.
            }
        }

        return config('services.paystack.secret_key') ?: null;
    }

    /** "sk_live_…a1b2", safe to show on the settings page. */
    public static function maskedSecretKey(): ?string
    {
        $key = self::secretKey();

        return $key ? substr($key, 0, 8).'…'.substr($key, -4) : null;
    }

    public static function save(array $values): void
    {
        if (isset($values['paystack_secret'])) {
            $values['paystack_secret'] = Crypt::encryptString($values['paystack_secret']);
        }
        Settings::put(collect($values)->mapWithKeys(fn ($v, $k) => ["payments.{$k}" => $v])->all());
    }

    private static function raw(string $key): ?string
    {
        return Settings::get("payments.{$key}");
    }
}
