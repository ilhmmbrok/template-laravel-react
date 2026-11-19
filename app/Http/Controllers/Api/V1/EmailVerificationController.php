<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof MustVerifyEmail || $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email address is already verified.',
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification link sent.',
        ], 202);
    }
}
