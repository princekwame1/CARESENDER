<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Who works on the team dashboard: administrators, and the secretariat who keep the directory up to date and
 * answer messages. Adding someone whose email already has a member account gives that account the role.
 * Admins cannot change or remove their own role, so there is always at least one admin.
 */
class TeamController extends Controller
{
    public function index(): View
    {
        return view('admin.team', [
            'team' => User::staff()->orderByRaw('role = ?', [User::SECRETARIAT])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'role' => ['required', Rule::in(array_keys(User::STAFF_ROLES))],
            'password' => ['nullable', Password::min(8)],
        ]);

        $user = User::firstWhere('email', $data['email']);
        if ($user?->isStaff()) {
            return back()->withInput()->withErrors(['email' => "{$user->name} is already on the team."]);
        }
        if (! $user && blank($data['password'] ?? null)) {
            return back()->withInput()->withErrors(['password' => 'Choose a password for this new account (at least 8 characters).']);
        }

        $user ??= User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
        $user->forceFill(['role' => $data['role']])->save();

        return back()->with('status', "{$user->name} can now sign in as ".User::STAFF_ROLES[$data['role']].'.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);
        $data = $request->validate(['role' => ['required', Rule::in(array_keys(User::STAFF_ROLES))]]);
        if ($user->is($request->user())) {
            return back()->withErrors(['role' => 'You can’t change your own role.']);
        }

        $user->forceFill(['role' => $data['role']])->save();

        return back()->with('status', "{$user->name} is now ".User::STAFF_ROLES[$data['role']].'.');
    }

    /** Takes someone off the team. Their account stays as an ordinary member account. */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);
        if ($user->is($request->user())) {
            return back()->withErrors(['role' => 'You can’t remove yourself from the team.']);
        }

        $user->forceFill(['role' => User::MEMBER])->save();
        $user->tokens()->delete();

        return back()->with('status', "{$user->name} no longer has access to the team dashboard.");
    }
}
