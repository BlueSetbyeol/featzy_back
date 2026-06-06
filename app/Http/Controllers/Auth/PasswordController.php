<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ResetUserPasswordAction;
use App\Data\Auth\ResetPasswordData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink(['email' => $request->validated('email')]);

        return response()->json(['message' => 'Si un compte avec cette adresse e-mail existe, un lien de réinitialisation a été envoyé.']);
    }

    public function reset(ResetPasswordRequest $request, ResetUserPasswordAction $resetPassword): JsonResponse
    {
        $status = $resetPassword->handle(
            ResetPasswordData::from($request->validated()),
        );

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['message' => __($status)]);
    }
}
