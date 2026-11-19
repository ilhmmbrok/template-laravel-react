<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AvatarController;
use App\Http\Controllers\Api\V1\EmailVerificationController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\PasswordController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\TwoFactorChallengeController;
use App\Http\Controllers\Api\V1\TwoFactorController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:api-auth');
        Route::post('two-factor-challenge', TwoFactorChallengeController::class)->middleware('throttle:api-two-factor');
        Route::post('forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:api-auth');
        Route::post('reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:api-auth');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('email/verification-notification', [EmailVerificationController::class, 'send'])->middleware('throttle:6,1');

        Route::prefix('me')->group(function () {
            Route::get('/', [MeController::class, 'show']);
            Route::patch('/', [MeController::class, 'update']);
            Route::delete('/', [MeController::class, 'destroy']);

            Route::put('password', [PasswordController::class, 'update']);

            Route::post('avatar', [AvatarController::class, 'store']);
            Route::delete('avatar', [AvatarController::class, 'destroy']);

            Route::prefix('two-factor')->group(function () {
                Route::get('/', [TwoFactorController::class, 'show']);
                Route::post('/', [TwoFactorController::class, 'enable']);
                Route::get('setup', [TwoFactorController::class, 'setup']);
                Route::post('confirm', [TwoFactorController::class, 'confirm']);
                Route::post('recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes']);
                Route::delete('/', [TwoFactorController::class, 'destroy']);
            });
        });
    });
});
