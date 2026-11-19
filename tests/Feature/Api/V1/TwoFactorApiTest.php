<?php

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

test('user can enable and confirm two-factor authentication', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Pixel 9')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/me/two-factor', ['current_password' => 'password'])
        ->assertOk()
        ->assertJsonStructure(['data' => ['secret', 'otpauth_url', 'qr_code_svg']]);

    $secret = decrypt($user->refresh()->two_factor_secret);

    $this->withToken($token)
        ->postJson('/api/v1/me/two-factor/confirm', [
            'code' => app(Google2FA::class)->getCurrentOtp($secret),
        ])
        ->assertOk()
        ->assertJsonCount(8, 'data.recovery_codes');
});

test('login with two-factor enabled requires a challenge', function () {
    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1', 'recovery-code-2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'Pixel 9',
    ])
        ->assertOk()
        ->assertJsonPath('data.two_factor', true);

    $this->postJson('/api/v1/auth/two-factor-challenge', [
        'challenge_token' => $login->json('data.challenge_token'),
        'recovery_code' => 'recovery-code-1',
    ])
        ->assertOk()
        ->assertJsonStructure(['data' => ['token', 'user']]);
});
