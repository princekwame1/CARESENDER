<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccessGate;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Member accounts (people who use the website, not the team). Admins can create one for someone, for example
 * after a phone call; it starts with the same free trial as signing up. Team roles are given on /admin/team.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->validate(['q' => ['nullable', 'string', 'max:120']])['q'] ?? null;

        return view('admin.users', [
            'search' => $search,
            'users' => User::where('role', User::MEMBER)
                ->when($search, function ($query, $term) {
                    $like = '%'.addcslashes($term, '%_\\').'%';
                    $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like));
                })
                ->latest()
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[\d\s()+\-.]+$/'],
            'password' => ['required', Password::min(8)],
        ], [
            'email.unique' => 'There is already an account with this email.',
            'phone.regex' => 'Use only numbers in the phone number.',
        ]);

        if (filled($data['phone'] ?? null)) {
            $data['phone'] = PhoneNumber::format(PhoneNumber::DEFAULT_COUNTRY, $data['phone'])
                ?? throw ValidationException::withMessages(['phone' => 'That phone number doesn’t look right. Check it and try again.']);
        }

        $user = DB::transaction(function () use ($data) {
            $user = User::create($data);
            AccessGate::startFreeTrial($user);

            return $user;
        });

        return back()->with('status', "{$user->name} can now sign in with {$user->email}.");
    }
}
