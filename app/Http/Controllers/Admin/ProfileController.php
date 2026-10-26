<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ImageUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.profile', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:60', 'alpha_dash', Rule::unique('users')->ignore($user)],
            'email' => ['required', 'email', 'max:190', Rule::unique('users')->ignore($user)],
        ]);
        $user->forceFill($data)->save();

        return back()->with('status', 'Profile saved.');
    }

    public function avatar(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($request->boolean('remove')) {
            ImageUploader::deleteAvatar($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();

            return back()->with('status', 'Profile photo removed.');
        }

        $request->validate(
            ['avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=100,min_height=100']],
            ['avatar.required' => 'Choose a photo to upload.', 'avatar.image' => 'Please upload a photo (JPG, PNG or WebP).', 'avatar.max' => 'Photos must be 5 MB or smaller.', 'avatar.dimensions' => 'Photos must be at least 100 pixels wide and tall.'],
        );
        $old = $user->avatar_path;
        $user->forceFill(['avatar_path' => ImageUploader::storeAvatar($request->file('avatar'), $user->username ?? 'user')])->save();
        ImageUploader::deleteAvatar($old);

        return back()->with('status', 'Profile photo updated.');
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], ['current_password.current_password' => 'Your current password is not correct.']);

        $request->user()->forceFill(['password' => $request->input('password')])->save();
        // Sign out other browsers that used the old password.
        auth()->logoutOtherDevices($request->input('password'));

        return back()->with('status', 'Password changed.');
    }
}
