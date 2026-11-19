<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Api\IssueApiToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class AuthController extends Controller
{
    public function register(Request $request, CreatesNewUsers $creator, IssueApiToken $issueApiToken): JsonResponse
    {
        $request->validate([
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $input = $request->except('device_name');

        if (is_string($input['email'] ?? null)) {
            $input['email'] = Str::lower($input['email']);
        }

        $user = $creator->create($input);

        event(new Registered($user));

        return response()->json([
            'data' => $issueApiToken(
                $user,
                $request->input('device_name')
            ),
        ], 201);
    }

    public function login(LoginRequest $request, IssueApiToken $issueApiToken): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::query()
            ->where('email', Str::lower($credentials['email']))
            ->first();

        if (
            ! $user ||
            ! Hash::check($credentials['password'], $user->password)
        ) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $challenge = Str::random(64);

            Cache::put(
                'api-two-factor:'.hash('sha256', $challenge),
                [
                    'user_id' => $user->id,
                    'device_name' => $credentials['device_name'],
                ],
                now()->addMinutes(5)
            );

            return response()->json([
                'data' => [
                    'two_factor' => true,
                    'challenge_token' => $challenge,
                ],
            ]);
        }

        return response()->json([
            'data' => $issueApiToken(
                $user,
                $credentials['device_name']
            ),
        ]);
    }

    public function logout(Request $request): Response
    {
        $request->user()
            ->currentAccessToken()
            ->delete();

        return response()->noContent();
    }
}
