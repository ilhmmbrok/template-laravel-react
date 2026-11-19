<?php

namespace App\Actions\Api;

use App\Http\Resources\UserResource;
use App\Models\User;

class IssueApiToken
{
    /**
     * @return array{token: string, token_type: string, expires_at: string, user: array<string, mixed>}
     */
    public function __invoke(User $user, string $deviceName): array
    {
        $user->tokens()->where('name', $deviceName)->delete();

        $expiresAt = now()->addDays(7);

        $token = $user->createToken($deviceName, ['*'], $expiresAt);

        return [
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => (new UserResource($user))->resolve(),
        ];
    }
}
