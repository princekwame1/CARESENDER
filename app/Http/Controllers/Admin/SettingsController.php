<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessPlan;
use App\Support\Branding;
use App\Support\PaymentSettings;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Branding (name, logo, colour) and Paystack/payment settings. */
class SettingsController extends Controller
{
    public const TABS = ['branding', 'payments'];

    public function edit(string $tab = 'branding'): View
    {
        abort_unless(in_array($tab, self::TABS, true), 404);

        return view('admin.settings', ['tab' => $tab]);
    }

    public function branding(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'tagline' => ['nullable', 'string', 'max:80'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            // No SVG: it can carry scripts.
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:min_width=64,min_height=64'],
            'remove_logo' => ['boolean'],
            // .ico has no reliable image mime type, so check the extension instead.
            'favicon' => ['nullable', 'file', 'extensions:png,ico,webp', 'mimetypes:image/png,image/webp,image/x-icon,image/vnd.microsoft.icon,application/octet-stream', 'max:512'],
            'remove_favicon' => ['boolean'],
        ], [
            'color.regex' => 'Pick a colour, or type it like #07689f.',
            'favicon.extensions' => 'Use a PNG, ICO or WebP file for the favicon.',
        ]);

        $values = ['brand.name' => $data['name'], 'brand.tagline' => $data['tagline'] ?? null, 'brand.color' => strtolower($data['color'])];
        foreach (['logo', 'favicon'] as $slot) {
            if ($request->hasFile($slot)) {
                $values["brand.{$slot}"] = Branding::store($slot, $request->file($slot));
            } elseif ($request->boolean("remove_{$slot}")) {
                Branding::delete($slot);
                $values["brand.{$slot}"] = null;
            }
        }
        Settings::put($values);

        return back()->with('status', 'Branding saved.');
    }

    public function payments(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['boolean'],
            'paystack_secret' => ['nullable', 'string', 'max:200', 'regex:/^sk_(test|live)_\w+$/'],
            'unlock_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'fee_percent' => ['required', 'numeric', 'min:0', 'max:10'],
            'channels' => ['array'],
            'channels.*' => [Rule::in(array_keys(PaymentSettings::CHANNELS))],
        ], ['paystack_secret.regex' => 'Paste the secret key from Paystack (it starts with sk_live_ or sk_test_).']);

        $enabled = $request->boolean('enabled');
        $hasKey = filled($data['paystack_secret'] ?? null) || PaymentSettings::secretKey();
        if ($enabled && ! $hasKey) {
            return back()->withErrors(['enabled' => 'Add your Paystack secret key before switching payments on.']);
        }
        if ($enabled && ! AccessPlan::where('is_active', true)->exists()) {
            return back()->withErrors(['enabled' => 'Add at least one visible plan before switching payments on.']);
        }

        PaymentSettings::save(array_filter([
            'enabled' => $enabled,
            'unlock_hours' => (string) $data['unlock_hours'],
            'fee_percent' => (string) (float) $data['fee_percent'],
            'channels' => $data['channels'] ?? [],
            'paystack_secret' => $data['paystack_secret'] ?? null,
        ], fn ($v) => $v !== null));

        return back()->with('status', $enabled ? 'Saved. Payments are on, so app users now need a plan to see full details.' : 'Saved. Payments are off, so everything in the app is free.');
    }
}
