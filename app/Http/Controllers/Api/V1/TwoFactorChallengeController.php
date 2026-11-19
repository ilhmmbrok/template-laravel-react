<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Api\IssueApiToken;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\TwoFactorAuthenticationProvider;

class TwoFactorChallengeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, IssueApiToken $issueToken, TwoFactorAuthenticationProvider $provider): JsonResponse
    {
        $validated = $request->validate([
            'challenge_token' => ['required', 'string'],
            'code' => ['nullable', 'string', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'required_without:code'],
        ]);

        $key = 'api-two-factor:'.hash('sha256', $validated['challenge_token']);
        $challenge = Cache::get($key);

        if (
            ! is_array($challenge) ||
            ! isset($challenge['user_id'], $challenge['device_name']) ||
            ! is_numeric($challenge['user_id']) ||
            ! is_string($challenge['device_name'])
        ) {
            throw ValidationException::withMessages([
                'challenge_token' => [
                    'The two-factor challenge has expired. Please log in again.',
                ],
            ]);
        }

        $user = User::query()->findOrFail(
            (int) $challenge['user_id']
        );

        if (! $this->hasValidCode($user, $provider, $request) && ! $this->hasValidRecoveryCode($user, $request)) {
            throw ValidationException::withMessages([
                'code' => [__('The provided two factor authentication code was invalid.')],
            ]);
        }

        Cache::forget($key);

        return response()->json([
            'data' => $issueToken(
                $user,
                $challenge['device_name']
            ),
        ]);
    }

    private function hasValidCode(User $user, TwoFactorAuthenticationProvider $provider, Request $request): bool
    {
        $code = $request->input('code');
        $secret = $user->two_factor_secret;

        return is_string($code)
            && $code !== ''
            && is_string($secret)
            && $secret !== ''
            && $provider->verify(decrypt($secret), $code);
    }

    private function hasValidRecoveryCode(User $user, Request $request): bool
    {
        $recoveryCode = $request->input('recovery_code');

        if (! is_string($recoveryCode) || $recoveryCode === '') {
            return false;
        }

        $code = collect($user->recoveryCodes())
            ->first(fn (string $code): bool => hash_equals($code, $recoveryCode));

        if ($code === null) {
            return false;
        }

        $user->replaceRecoveryCode($code);

        return true;
    }
}
