<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return $this->tokenResponse(Auth::login($user), $user, Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $token = Auth::attempt($request->validated());

        if (! $token) {
            return response()->json(['message' => 'Invalid email or password.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->tokenResponse($token, Auth::user());
    }

    public function me(): UserResource
    {
        return new UserResource(Auth::user());
    }

    public function refresh(): JsonResponse
    {
        return $this->tokenResponse(Auth::refresh(), Auth::user());
    }

    public function logout(): JsonResponse
    {
        Auth::logout();

        return response()->json(['message' => 'Successfully logged out.']);
    }

    private function tokenResponse(string $token, User $user, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
            'user' => new UserResource($user),
        ], $status);
    }
}
