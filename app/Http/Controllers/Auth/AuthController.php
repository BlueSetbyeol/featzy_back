<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterUserAction;
use App\Data\Auth\RegisterUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly RegisterUserAction $registerUser,
    ) {}

    /**
     * Register a new client account. Sends an email verification link; the user
     * is not logged in and must authenticate via login afterwards.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->registerUser->handle(
            RegisterUserData::from($request->validated()),
        );

        return UserResource::make($user)
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    /**
     * Authenticate a user against the web (session) guard for SPA cookie auth.
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request): UserResource
    {
        if (! Auth::guard('web')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Prevent session fixation now that the user is authenticated.
        $request->session()->regenerate();

        return UserResource::make($request->user()->load('roles'));
    }

    /**
     * Authenticate a user on a app from Expo.
     *
     * @throws ValidationException
     */
    public function loginMobile(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $token = $user->createToken('expo-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => UserResource::make($user->load('roles')),
        ]);
    }

    /**
     * Return the currently authenticated user.
     */
    public function user(Request $request): UserResource
    {
        return UserResource::make($request->user()->load('roles'));
    }

    /**
     * Log the user out of the SPA session and invalidate it.
     */
    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
