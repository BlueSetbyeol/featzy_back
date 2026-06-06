<?php

namespace App\Actions\Auth;

use App\Data\Auth\ResetPasswordData;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetUserPasswordAction
{
    /**
     * Reset the user's password using a valid token and return the broker status.
     * The closure only runs when the token and credentials are valid.
     *
     * @return string One of the Password::* status constants.
     */
    public function handle(ResetPasswordData $data): string
    {
        return Password::reset(
            [
                'email' => $data->email,
                'password' => $data->password,
                'token' => $data->token,
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            },
        );
    }
}
