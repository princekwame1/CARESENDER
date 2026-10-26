<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            // Older app versions send no country; their numbers are read as Ghanaian.
            'phone_country' => ['nullable', Rule::in(array_keys(PhoneNumber::COUNTRIES))],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[\d\s()+\-.]+$/'],
            'password' => ['required', Password::min(8)],
            'device_name' => ['required', 'string', 'max:120'],
        ], [
            'phone.regex' => 'Use only numbers in the phone number.',
        ]);

        if (filled($data['phone'] ?? null)) {
            $data['phone'] = PhoneNumber::format($data['phone_country'] ?? PhoneNumber::DEFAULT_COUNTRY, $data['phone'])
                ?? throw ValidationException::withMessages(['phone' => 'That phone number doesn’t look right. Check it and try again.']);
        }

        $user = User::create(Arr::except($data, ['device_name', 'phone_country']));

        return $this->tokenResponse($user, $data['device_name'], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'max:190'], // email address or username
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:120'],
        ]);

        $user = User::where(str_contains($data['email'], '@') ? 'email' : 'username', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        return $this->tokenResponse($user, $data['device_name']);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->userPayload($request->user())]);
    }

    private function tokenResponse(User $user, string $device, int $status = 200): JsonResponse
    {
        $abilities = $user->isAdmin() ? ['*'] : ['member'];

        return response()->json([
            'token_type' => 'Bearer',
            'token' => $user->createToken($device, $abilities)->plainTextToken,
            'data' => $this->userPayload($user),
        ], $status);
    }

    private function userPayload(User $user): array
    {
        return $user->only(['id', 'name', 'email', 'phone', 'role', 'created_at']);
    }
}
