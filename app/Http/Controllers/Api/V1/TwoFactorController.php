<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ConfirmPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

class TwoFactorController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'enabled' => $user->hasEnabledTwoFactorAuthentication(),
                'pending_confirmation' => $this->isPending($user),
            ],
        ]);
    }

    public function enable(ConfirmPasswordRequest $request, EnableTwoFactorAuthentication $enable): JsonResponse
    {
        $user = $request->user();

        abort_if($user->hasEnabledTwoFactorAuthentication(), 409, 'Two-factor authentication is already enabled.');

        $enable($user);

        return response()->json(['data' => $this->setupPayload($user)]);
    }

    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($this->isPending($user), 404);

        return response()->json(['data' => $this->setupPayload($user)]);
    }

    public function confirm(Request $request, ConfirmTwoFactorAuthentication $confirm): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        $confirm($user, $request->input('code'));

        return response()->json([
            'data' => ['recovery_codes' => $user->recoveryCodes()],
        ]);
    }

    public function regenerateRecoveryCodes(ConfirmPasswordRequest $request, GenerateNewRecoveryCodes $generate): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->hasEnabledTwoFactorAuthentication(), 409, 'Two-factor authentication is not enabled.');

        $generate($user);

        return response()->json([
            'data' => ['recovery_codes' => $user->recoveryCodes()],
        ]);
    }

    public function destroy(ConfirmPasswordRequest $request, DisableTwoFactorAuthentication $disable): Response
    {
        $disable($request->user());

        return response()->noContent();
    }

    private function isPending(User $user): bool
    {
        return ! is_null($user->two_factor_secret) && is_null($user->two_factor_confirmed_at);
    }

    /**
     * @return array<string, string>
     */
    private function setupPayload(User $user): array
    {
        return [
            'secret' => decrypt($user->two_factor_secret),
            'otpauth_url' => $user->twoFactorQrCodeUrl(),
            'qr_code_svg' => $user->twoFactorQrCodeSvg(),
        ];
    }
}
